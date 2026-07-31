<?php

namespace mod_slides;

use Exception;
use mod_slides\form\general_slide_form;
use mod_slides\slideinstance as Mod_slidesSlideinstance;
use moodle_exception;
use moodle_url;
use slideinstance;
use xmldb_table;

// use slidetype_introduction\slideinstance as Slidetype_introductionSlideinstance;

abstract class slidetype implements slidetype_interface, \templatable, \renderable {

    public const LISTORDER = -1;

    /**
     * Context data for the current module.
     *
     * @var context_module
     */
    protected $context;

    /**
     * Course module ID.
     *
     * @var int $cmid
     */
    protected $cmid;

    /**
     * Short name of the slide type.
     *
     * @var string
     */
    protected $shortname;

    /**
     * Table name of the slide type.
     *
     * @var string
     */
    protected $tablename;

    /**
     * ID of the style element stored in the table slides type.
     *
     * @var int $slidetypeid
     */
    protected $slidetypeid;

    /**
     * Course module
     *
     * @var cm_info $cm course module data
     */
    protected $cm;

    /**
     * Course Objecet
     *
     * @var mixed $course Course object
     */
    protected $course;

    protected const SPLIT_HEAD_COUNT = 1;

    protected const SPLIT_CONTENT_COUNT = 1;

    protected const SPLIT_MAXINSTANCE_COUNT = 1;

    /**
     * Constructor method, Setup the element basic information and context.
     *
     * @param int $cmid
     */
    public function __construct($cmid) {
        $this->cmid = $cmid;

        $this->shortname = static::slide_shortname();
        $this->tablename = static::slide_tablename();

        // $this->slidetypeid = $this->slidetype_id();

        if (empty($cmid)) {
            throw new moodle_exception('cmidmissing', 'mod_slides');
        }
        // @throws moodle exception if the cmid not found.
        list($course, $cm) = get_course_and_cm_from_cmid($cmid);

        $this->cm = $cm;
        $this->course = $course;
        $this->context = \context_module::instance($cmid);

    }

    // abstract public static function slide_name() : string;
    public function __get($property) {
        return property_exists($this, $property) ? $this->$property : '';
    }

    abstract public function slide_form($url, $customdata);

    abstract public static function backup_files() : array;

    /**
     * Get the slide instance.
     *
     * @param int $instanceid
     * @return Mod_slidesSlideinstance
     */
    final public function get_instance(int $instanceid) : Mod_slidesSlideinstance {
        $instanceclass = "\slidetype_" . $this->shortname . '\slideinstance';
        if (class_exists($instanceclass)) {
            $slide = $instanceclass::instance($instanceid, $this->cm, $this->context);
            $slide->set_slidetype($this);
            return $slide;
        } else {
            throw new moodle_exception('slidetypeinstancenofound', 'mod_slides');
        }
    }

    /**
     * Simple information about the element. Used in the element box.
     *
     * @return object
     */
    public static function info() {
        global $OUTPUT;

        return (object) [
            // 'elementid' => $this->slidetype_id(),
            'name' => static::slide_name(),
            'shortname' => static::slide_shortname(),
            'icon' => static::slide_icon(),
            'description' => static::slide_description(),
        ];
    }

    /**
     * Get the Id of the element in the list of available elements list, this id created during the element installation.
     *
     * @return int ID of the element.
     */
    /* public static function slidetype_id() {
        global $DB;
        return $DB->get_field('slides_type', 'id', ['shortname' => static::slide_shortname()]);
    } */

    /**
     * Get the table name of the current element. By default its the shortname followed by keyword (element_SHORTNAME)
     *
     * @return string Name of the table.
     */
    public static function slide_tablename() : string {
        return 'slidetype_' . static::slide_shortname();
    }

    public function get_context() {
        return $this->context;
    }

    public function supports_multiple_instance() : bool {
        return true;
    }

    public function supports_standard_slides() :bool {
        return true;
    }

    protected function supports_custom_table() : bool {
        return true;
    }

    public function supports_autosplit() : bool {
        return false;
    }

    public function supports_autoresize() : bool {
        return true;
    }

    public function supports_listenoptions() : bool {
        return true;
    }

    public function includes_custom_jsmodule() : string {
        return '';
    }

    /**
     * Split head count.
     *
     * @return int
     */
    public function split_head_count() : int {
        return static::SPLIT_HEAD_COUNT;
    }

    public function split_content_count() : int {
        return static::SPLIT_CONTENT_COUNT;
    }

    public function split_maxinstance_count() : int {
        return static::SPLIT_MAXINSTANCE_COUNT;
    }


    /**
     * Prepare data for the element moodle form.
     *
     * @param int $instanceid Element instance id.
     * @return object
     */
    public function prepare_formdata($instanceid) {
        $instancedata = (array) ($this->get_instance($instanceid))->get_data_for_form();
        $instancedata['cmid'] = $this->cmid;
        return (object) ($instancedata);
    }

    /**
     * Prepare the form editor elements file data before render the elemnent form.
     *
     * @param stdclass $formdata
     * @return stdclass
     */
    /* public function prepare_standard_file_editor(&$formdata) {
        if (isset($formdata->instance)) {
            $draftitemid = file_get_submitted_draft_itemid('bgimage');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_contentdesigner', $this->element_shortname().'elementbg',
                $formdata->instance, array('subdirs' => 0, 'maxfiles' => 1));
            $formdata->bgimage = $draftitemid;
        }
        return $formdata;
    } */


   /**
     * Update the element instance. Override the function in elements element class to add custom rules.
     *
     * @param stdclass $data
     * @return void
     */
    final public function manage_slide_instance($data) {
        global $DB;

        if (empty($data->slidesid) || empty($data->slidetype)) {
            // TODO: Add string.
            throw new moodle_exception('invalideslideparentorslidetype', 'slides');
        }

        // Change the array to json.
        if (!empty($data->additional) && is_array($data->additional)) {
            $data->additional = json_encode($data->additional);
        }

        // Slide instance id.
        if (empty($data->slideinstanceid) || (isset($data->slideinstaceid) && $data->slideinstaceid == 0)) {

            $data->timemodified = time();
            $data->timecreated = time();

            $lastslide = (int) $DB->get_field_sql(
                'SELECT max(sortorder) from {slides_slide} WHERE slidesid = ? ', [$data->slidesid]);

            $data->sortorder = $lastslide ? $lastslide + 1 : 1;
            // print_obj($data);exit;
            return $DB->insert_record('slides_slide', $data);

        } else {
            $data->timemodified = time();
            $data->id = $data->slideinstanceid;

            if ($DB->update_record('slides_slide', $data)) {
                return $data->id;
            }
        }

        return false;
    }

    /**
     * Manage the instance data of slide type that supports the custom tabel method.
     *
     * @param [type] $data
     * @return void
     */
    protected function manage_custom_instance(&$data) {
    }


    protected function delete_custom_instance(int $slideinstanceid) {
        global $DB;

        $shortname = static::slide_shortname();
        $dbman = $DB->get_manager();

        $table = new xmldb_table('slidetype_'.$shortname);
        if ($dbman->table_exists($table)) {
            $DB->delete_records('slidetype_'.$shortname, ['slideinstanceid' => $slideinstanceid]);
        }
    }

    protected function update_slide_options($data) {
        global $DB;

        $options = (object) $data->options;

        $options->slidetype = $data->slidetype;

        // Slide instanceid.
        $options->slideinstanceid = $data->slideinstanceid;

        // Update the fontstyle.
        $options->fontstyle = json_encode($options->fontstyle);

        // Update the listen durations.
        $options->listenduration = json_encode($options->listenduration ?? []);

        // Update the audio time.
        $options->audiotime = json_encode($options->audiotime ?? []);

        // Insert global options for slide instance.
        if ($data->slideinstanceid && $record = $DB->get_record('slides_options', ['slideinstanceid' => $data->slideinstanceid])) {

            $options->timemodified = time();
            $options->id = $record->id;

            if ($DB->update_record('slides_options', $options)) {
                return $options->id;
            }

        } else {

            $options->timecreated = time();
            return $DB->insert_record('slides_options', $options);
        }
    }

    /**
     * Update slide data.
     *
     * @param stdClass $data
     * @return void
     */
    public function update_slide(&$data) {
        global $DB;

        try {

            $transaction = $DB->start_delegated_transaction();

            // ...Create/Update the slide instance.
            $slideinstanceid = $this->manage_slide_instance($data);


            if (empty($slideinstanceid)) {
                throw new moodle_exception('slideinstancenotcreated', 'slides');
            }

            // Setup instanceid if the slide is not inserted before.
            $data->slideinstanceid = $data->slideinstanceid ?: $slideinstanceid;

            // Update slide type instance records.
            // Some slide type like introduction not have separate table.
            if ($this->supports_custom_table()) {
                $this->manage_custom_instance($data);
            }

            // Update the slide general options.
            $this->update_slide_options($data);

            $transaction->allow_commit();

        } catch (\Exception $e) {
            // Extra cleanup steps.
            $transaction->rollback($e); // Rethrows exception.
        }
    }


    public function duplicate_slide(int $slideinstanceid, array $additionalinfo) {
        global $DB, $PAGE;

        /* $formdata = $this->prepare_formdata($slideinstanceid);
        $formdata->id = 0;
        unset($formdata->options['id']);
        unset($formdata->options['slideinstanceid']);
        $formdata->slideinstanceid = 0; */

       /*  if (isset($formdata->content)) {
            array_walk($formdata->content, function(&$item) {
                $item->id = 0;
                if (isset($item->slideinstanceid)) {
                    unset($item->slideinstanceid); //= 0;
                }
            });
        } */

        // $url = new moodle_url('/mod/slides/slide.php', ['id' => $PAGE->cm->id, 'action' => 'duplicate']);
        // $this->slide_form($url, $additionalinfo)->data_postprocessing($formdata);

        $slideinstance = $DB->get_record('slides_slide', ['id' => $slideinstanceid]);
        $options = $DB->get_record('slides_options', ['slideinstanceid' => $slideinstanceid]);



        $transaction = $DB->start_delegated_transaction();

        try {
            unset($slideinstance->id);
            $newinstanceid = $DB->insert_record('slides_slide', $slideinstance);

            $options->slideinstanceid = $newinstanceid;
            $options->id = $DB->insert_record('slides_options', $options);

            $i = 1;
            if ($DB->get_manager()->table_exists('slidetype_'.$this->shortname)) {
                $records = $DB->get_records('slidetype_'.$this->shortname, ['slideinstanceid' => $slideinstanceid]);
                foreach ($records as $record) {
                    unset($record->id);
                    $record->slideinstanceid = $newinstanceid;
                    $inserts[] = $record;
                    $this->duplicate_listen_options($slideinstanceid, $newinstanceid, $i);
                    $i++;
                }
                // Insert the records.
                $DB->insert_records('slidetype_'.$this->shortname, $inserts);
            } else {
                $this->duplicate_listen_options($slideinstanceid, $newinstanceid, $i);
            }

            $this->duplicate_fileareas($slideinstanceid, $newinstanceid);

            // Update the slide instance custom table data.
            $this->update_duplicate_custom_tabledata($slideinstanceid, $newinstanceid);

            $transaction->allow_commit();

        } catch (\Exception $e) {
            // Extra cleanup steps.
            $transaction->rollback($e); // Rethrows exception.
        }

        return true;
    }


    public function update_duplicate_custom_tabledata(int $slideinstanceid, int $newinstanceid) : void {

    }

    /**
     * Duplicate the listen options like audio.
     *
     * @param [type] $slideinstanceid
     * @param [type] $newinstanceid
     * @return void
     */
    public function duplicate_listen_options($slideinstanceid, $newinstanceid, int $index = 1) {

        $filearea = $this->shortname . '_listenaudio_' . $index;
        $context = $this->context;
        // return $this->get_slide_file_url($filearea, $this->slidedata->slideinstanceid, 'mod_slides', $this->context);
        $context = ($context === null) ? \context_module::instance($this->cm->id) : $context;

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id, 'mod_slides', $filearea, $slideinstanceid, 'itemid, filepath, filename', false);

        if (empty($files) ) {
            return '';
        }

        foreach ($files as $file) {
            $record = new \stdClass();
            $record->contextid = $file->get_contextid();
            $record->component = $file->get_component();
            $record->filearea = $file->get_filearea();
            $record->itemid = $newinstanceid;
            $record->filepath = $file->get_filepath();
            $record->filename = $file->get_filename();

            $fs->create_file_from_storedfile($record, $file);
        }
    }

    protected function duplicate_fileareas($slideinstanceid, $newinstanceid) {
        global $DB;

        $fileareas = static::slide_fileareas_list();

        foreach ($fileareas as $filearea) {
            $context = $this->context;
            $context = ($context === null) ? \context_module::instance($this->cm->id) : $context;

            $fs = get_file_storage();
            $files = $fs->get_area_files(
                $context->id, 'mod_slides', $filearea, $slideinstanceid, 'itemid, filepath, filename', false);

            if (empty($files) ) {
                continue;
            }

            foreach ($files as $file) {
                $record = new \stdClass();
                $record->contextid = $file->get_contextid();
                $record->component = $file->get_component();
                $record->filearea = $file->get_filearea();
                $record->itemid = $newinstanceid;
                $record->filepath = $file->get_filepath();
                $record->filename = $file->get_filename();

                $fs->create_file_from_storedfile($record, $file);
            }
        }
    }

    /**
     * Delete the element settings.
     *
     * @param int $instanceid
     * @return boolean status.
     */
    public function delete_slide($slideinstanceid) {
        global $DB;

        try {

            $transaction = $DB->start_delegated_transaction();
            // Delete the slide instance.
            $DB->delete_records('slides_slide', array('id' => $slideinstanceid));

            if ($this->supports_custom_table()) {
                $this->delete_custom_instance($slideinstanceid);
            }

            // Delete the element general settings.
            $DB->delete_records('slides_options', array('slideinstanceid' => $slideinstanceid));

            $DB->delete_records('slides_slide_completion', array('slideinstanceid' => $slideinstanceid));

            $transaction->allow_commit();

            return true;

        } catch (\Exception $e) {
            // Extra cleanup steps.
            // $transaction->rollback($e); // Rethrows exception.
            throw new \moodle_exception('chapternotdeleted', 'element_chapter');
        }
    }

    /**
     * Update the visibility of the elements instance.
     *
     * @param int $instanceid Element instance id.
     * @param int $visibility Status of the element visibility
     * @return bool Result of the DB update
     */
    public function update_visibility($instanceid, $visibility) {
        global $DB;
        $DB->set_field('slides_slide', 'status', $visibility, ['id' => $instanceid]);
        $DB->set_field('slides_slide', 'timemodified', time(), ['id' => $instanceid]);

        return true;
    }

    public function split_dynamic_content($data, $mform) : void {
        $autohelper = new autohelper($this->context);
        $autohelper->update_module_files($data, $this, $mform);
    }

}
