<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_slides;

use cm_info;
use renderable;
use renderer_base;
use stdClass;
use stored_file;
use templatable;

abstract class slideinstance implements renderable, templatable {
    public const STATUS_ENABLE = 1;

    public const STATUS_DISABLE = 0;

    /**
     * Force the user to listern the seconds.
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public const FORCENONE = 0;

    /**
     * Force the user to listen the audio.
     */
    public const FORCEAUDIO = 1;

    /**
     * Force the user to listern the seconds.
     */
    public const FORCESECONDS = 2;

    /**
     * Disable the next button to force the learner to listen the feedback.
     */
    public const NEXTDISABLE = 0;

    /**
     * Hideen the next button to force the learner to listen the feedback.
     */
    public const NEXTHIDDEN = 1;

    /**
     * Slide instance id.
     *
     * @var [type]
     */
    protected $instanceid;

    protected $slidedata;

    protected $cm;

    protected $context;

    protected stdClass $jsdata;

    protected $slideobj;

    protected $slidesinstance;

    protected $forceavailableitem;

    abstract public function get_data_for_form(): stdClass;

    abstract public function get_completion_content_count() : int;


    public static function instance(int $instanceid, cm_info $cm, \context_module $context=null): static {
        return new static($instanceid, $cm, $context);
    }

    public static function instance_from_id(int $instanceid) {
        // TODO: if only needed.
    }


    public function __get($index) {
        return $this->slidedata->$index ?? '';
    }

    public function set_slidetype($slide) {
        $this->slideobj = $slide;
    }

    public function set_slidesmoddata(stdClass $slidesinstancedata) {
        $this->slidesinstance = $slidesinstancedata;
    }

    public function set_force($force) {
        $this->forceavailableitem = $force;

    }

    /**
     * Create a instance for the slide type.
     *
     * Builds the slide data for the instance, SEtup the cm and context.
     *
     * @param integer $instanceid
     * @param cm_info $cm
     * @param \context_module|null $context
     */
    protected function __construct(int $instanceid, cm_info $cm, \context_module $context=null) {

        $this->instanceid = $instanceid;

        $this->slidedata = $this->get_slide_data_forinstance();

        $this->cm = $cm;

        $this->context = is_null($context) ? \context_module::instance($this->cm->id) : $context;

        $this->jsdata = new stdClass();

    }

    /**
     * Include the js data related to the slide instance.
     *
     * @param stdClass $jsdata
     * @return void
     */
    public function join_js_data() : stdClass {
        return $this->jsdata;
    }

    /**
     * Fetch the files from for the filearea.
     *
     * @param string $filearea Name of the filearea.
     * @param int $itemid Id for the filearea.
     * @param string $component Plugin component name.
     * @param context_module $context Course module instance object.
     * @return string File Path of the given fileareas, If not false.
     */
    public function get_slide_file_url($filearea, $itemid=0, $component='mod_slides', $context=null) {

        $context = ($context === null) ? \context_module::instance($this->cm->id) : $context;
        $files = get_file_storage()->get_area_files(
            $context->id, $component, $filearea, $itemid, 'itemid, filepath, filename', false);
        if (empty($files) ) {
            return '';
        }
        $file = current($files);
        $fileurl = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(), false);

        return $fileurl->out(false);
    }


    /**
     * Undocumented function
     *
     * @return array
     */
    protected function get_slide_options_join() : array {

        $sql = "JOIN {slides_options} so ON so.slideinstanceid = :optionslideinstanceid";
        $params = ['optionslideinstanceid' => $this->instanceid];

        return [$sql, $params];
    }

    /**
     * Undocumented function
     *
     * @return stdClass
     */
    protected function get_slide_data_forinstance() : stdClass {
        global $DB;

        list($optionsql, $optionparam) = $this->get_slide_options_join();

        // TODO: Use the custom table join here.

        $sql = "
            SELECT *
            FROM {slides_slide} ss
            $optionsql
            WHERE ss.id = :slideinstanceid";

        $params = ['slideinstanceid' => $this->instanceid] + $optionparam;

        $record = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);

        if (!empty($record)) {
            $record->additional = json_decode($record->additional ?: '', true);
            // TODO: REwrite the plugin files.
            $record->title = ['text' => $record->title, 'format' => $record->titleformat];
            $record->dynamiccontent = ['text' => $record->dynamiccontent, 'format' => $record->dynamiccontentformat];
        }

        if (empty($record)) {
            return (object) [];
        }

        $this->update_option_fields($record);

        return $record;
    }

    /**
     * Undocumented function
     *
     * @param stdClass $record
     * @return void
     */
    private function update_option_fields(stdClass &$record) : void {
        global $DB;

        // Some of the json fields to update to foreach.
        $jsonfields = ['fontstyle', 'audiotime', 'listenduration'];

        $options = [];
        foreach ($jsonfields as $field) {
            if (property_exists($record, $field)) {
                $record->$field = json_decode($record->$field ?: '', true);
            }
        }

        $columns = $DB->get_columns('slides_options', true);
        $fields = array_keys($columns);

        foreach ($fields as $field) {
            if (property_exists($record, $field)) {
                $options[$field] = $record->$field;
            }
        }

        $record->options = $options;
    }

    /**
     * Get the listen audio for this instance.
     *
     * @param integer $index
     * @return void
     */
    protected function get_slide_listen_audio(int $index) {
        $filearea = $this->slidedata->slidetype . '_listenaudio_' . $index;
        return $this->get_slide_file_url($filearea, $this->slidedata->slideinstanceid, 'mod_slides', $this->context);
    }

    /**
     * Include the listen audios and other listen related data.
     *
     * @param [type] $data
     * @param [type] $index
     * @return void
     */
    protected function include_listen_data(&$data, $index) {
        $data->listenaudio = $this->get_slide_listen_audio($index, $this->slidedata->slidetype);
        $data->contentitemindex = $index;

        // Hide content section if the intro content is empty.
        $data->hidecontent = empty($data->content) && empty($data->listenaudio);
    }

  /*   protected function format_listen_contentitem(&$data, $item) {
        $data->listenoptions
    } */

    protected function format_listencontent_data(&$data, $items, $clicktoview=true, bool $completion=false, int $viewed=0, int $currentindex=1) {

        $data->title = $data->title['text'];

        if ($data->options['forcelisten'] == 0 || $completion) {
            $clicktoview = false;
        }

        if ($clicktoview) {
            $clicktoviewstring = $data->options['forcelisten'] == self::FORCEAUDIO
                ? get_string('forceaudiostr', 'mod_slides') : get_string('forcedurationstr', 'mod_slides');
        }

        // print_obj($data);exit;
        // Animation class for the content.
        $contentclass[] = $data->options['animation'] ? 'animate__animated' : '';
        // $contentclass[] = $data->options['imageanimation'] ? 'animate__animated' : '';

        $data->contentclass = implode(' ', $contentclass);
        $data->animation = $data->options['animation'] ? "animate__" . $data->options['animation'] : 'noanimation';
        $data->imageanimation = $data->options['imageanimation'] ? "animate__" . $data->options['imageanimation'] : 'noanimation';


        $data->listencontent = [
            'contentitems' => $items,
            'clicktoview' => $clicktoview,
            'clicktoviewstring' => $clicktoviewstring ?? '',
            'completed' => $completion,
            'hidelisten' => !$completion,
            'viewedcount' => $viewed,
        ];


        $data->uniqid = uniqid('slide-');
        $data->styles = \html_writer::tag('style', $this->generate_styles($data->uniqid));

        // Listen duration.
        if (!empty($data->options['listenduration'])) {
            array_walk($data->options['listenduration'], fn(&$duration) => (int) $duration);
        }

        // Include the js data for slide.
        $this->jsdata = (object) array_merge((array) $this->jsdata, [
            'slideinstanceid' => $data->slideinstanceid,
            'completed' => $completion ? true : false,
            'clicktoview' => $data->listencontent['clicktoview'],
            'forcelisten' => (int) $data->options['forcelisten'],
            'forcenext' => (int) $data->options['forcenext'],
            'currentindex' => $currentindex,
            'listenduration' => $data->options['listenduration'],
            'initaudio' => !$completion,
            'viewedcount' => $viewed,
            'slidetype' => $data->slidetype,
            'uniqid' => $data->uniqid,
            'supportsautofontsize' => $this->slideobj->supports_autoresize(),
        ]);



    }

    /**
     * Get the completion.
     *
     * @param array $data
     * @param int $completion
     *
     * @return stdClass
     */
    public function get_completion(int $slideinstanceid, int $userid) {
        global $DB;

        $completion = $DB->get_record('slides_slide_completion', ['slideinstanceid' => $slideinstanceid, 'userid' => $userid]);

        return $completion;
    }

    /**
     * Update the slide content viewed for the content index.
     *
     * @param int $slideinstanceid
     * @param int $userid
     * @param int $contentviewedindex
     *
     * @return void
     */
   /*  public function update_slide_content_viewed(int $slideinstanceid, int $userid, int $contentviewedindex) {
        global $DB;
        $condition = ['slideinstanceid' => $slideinstanceid, 'userid' => $userid];
        $contentcount = $this->get_completion_content_count();
        if ($record = $DB->get_record('slides_slide_completion', $condition)) {
            $record->viewed = $contentviewedindex;
            $record->contentcount = $contentcount;
            $record->timemodified = time();
            // Completion record.
            $record->completion = $contentviewedindex >= $contentcount;
            if ($DB->update_record('slides_slide_completion', $record)) {
                return $record->id;
            }

        } else {
            $record = (object) $condition;
            $record->viewed = $contentviewedindex;
            $record->contentcount = $contentcount;
            $record->timecreated = time();
            // Completion record.
            $record->completion = $contentviewedindex >= $contentcount;
            return $DB->insert_record('slides_slide_completion', $record);
        }
        return false;
    } */

    /**
     * Update the slide content viewed index.
     *
     * @param integer $slideinstanceid
     * @param integer $userid
     * @param integer $contentviewedindex
     * @return void
     */
    public function update_slide_content_viewed(int $slideinstanceid, int $userid, int $contentviewedindex) {
        global $DB;

        $condition = ['slideinstanceid' => $slideinstanceid, 'userid' => $userid];

        $contentcount = $this->get_completion_content_count();

        if ($record = $DB->get_record('slides_slide_completion', $condition)) {
            $record->contentscount = $contentcount;
            $record->timemodified = time();
            $additional = is_string($record->additional) ? explode(',', $record->additional) : [];
            $additional[] = $contentviewedindex;
            $uniqueviews = array_unique($additional);
            $record->additional = implode(',', $uniqueviews);
            $record->viewed = $contentviewedindex; // Used the content viewed index,
            // Completion record.
            $record->completion = $record->viewed >= $contentcount;

            if ($DB->update_record('slides_slide_completion', $record)) {
                return $record->id;
            }

        } else {

            $record = (object) $condition;
            $record->viewed = 1;
            $record->contentscount = $contentcount;
            $record->timemodified = time();
            $additional = [$contentviewedindex];
            $record->additional = implode(',', $additional);
            // Completion record.
            $record->completion = $record->viewed >= $contentcount;

            return $DB->insert_record('slides_slide_completion', $record);
        }

        return false;
    }

    /**
     * usage: list($result, $viewedindex, $itemcount) = $this->is_slide_completed();
     *
     * @param integer $slideinstanceid
     * @param integer $userid
     * @return boolean
     */
    public function is_slide_completed(int $slideinstanceid=0, int $userid=0) {
        global $DB, $USER;

        $contentcount = $this->get_completion_content_count();

        /* if ($this->slideobj->supports_listenoptions() && $this->slidedata->options['forcelisten'] == self::FORCENONE) {
            return [true, $contentcount, $contentcount];
        } */


        $record = $this->get_completion($slideinstanceid ?: $this->instanceid, $userid ?: $USER->id);

        // Content count is empty they consider as completed otherwise is should be viewed all the contents avilalbe for the slide.
        $result = ($contentcount) ? false : true;

        if ($contentcount && $record) {
            $result = ($record->viewed == $contentcount) ? true : false;
            $viewed = $record->viewed;
        }


        return [$result, $viewed ?? 0, $contentcount];
    }

    public function has_view_option($verifylisten=false) {
        global $USER;

        $hasviewoption = (has_capability('mod/slides:addinstance', $this->context));

        if ($hasviewoption) {
            // Remove the teacher users and users with capability to add instance completion data.
            // helper::remove_teacher_attempts($this->instanceid, $USER->id, $this->context);
        }

        // Get the user completion status.
        $completion = $this->get_completion($this->instanceid, $USER->id);

        $viewed = $completion ? $completion->viewed : 0;
        // Note: teachers/admins are NOT force-completed here. Their completion is
        // reset per view (see widget::export_for_template) so they experience each
        // activity as a student and can trial the interactions.
        $completed = ($completion && $completion->completion);

        if ($verifylisten) {
            $completed = $this->slidedata->options['forcelisten'] == self::FORCENONE ? true : $completed;
        }

        return [$viewed, $completed];
    }

    /**
     * Generate the styles.
     *
     * @return void
     */
    protected function generate_styles($uniqueid) {

        $uniqueid = "#$uniqueid" . '.slide-' . $this->slidedata->slidetype;

        $styles = $this->slidedata->fontstyle ? $this->slidedata->fontstyle : [];

        $style = '';
        $fonturl = 'https://fonts.googleapis.com/css2?family=';
        $imports = '';

        $heading = [];
        if (array_key_exists('headingsize', $styles) && !empty($styles['headingsize'])) {
            $size = $styles['headingsize'] ? $styles['headingsize'] . 'px' : '';
            $heading[] = "font-size: $size; !important";
        }

        // Include the heading fonts style.
        if (array_key_exists('headingfont', $styles) && !empty($styles['headingfont'])) {
            $font = str_replace(' ', '+', $styles['headingfont']);
            $headingstyle = $fonturl . $font;
            $imports .= "@import url('$headingstyle');";

            $heading[] = 'font-family: "'.$styles['headingfont'].'", serif;
                        font-optical-sizing: auto;';
        }

        if (!empty($heading)) {
            $rules = implode('', $heading);
            $style .= '
                .nct-slides-view-content ' . $uniqueid . '  .slide-title {' . $rules . '}
                .nct-slides-view-content ' . $uniqueid . ' .slide-title {
                    h1, h2, h3, h4, h5, h6, p {
                        '.$rules.'
                    }
                }';
        }

        // Include the content font styles.
        $content = [];
        if (!$this->slidesinstance->autotextsize && array_key_exists('contentsize', $styles) && !empty($styles['contentsize'])) {
            $size = $styles['contentsize'] ? $styles['contentsize'] . 'px' : '';
            $content[] = "font-size: $size;";
        }

        if (array_key_exists('contentfont', $styles) && !empty($styles['contentfont'])) {

            // Replace the space with + symbol for fetch fonts.
            $font = str_replace(' ', '+', $styles['contentfont']);
            $contentstyle = $fonturl . $font;
            $imports .= "@import url('$contentstyle');";

            $content[] = 'font-family: "'.$styles['contentfont'].'", serif;
                font-optical-sizing: auto;
                font-style: normal;';
        }

        if (!empty($content)) {
            $rules = implode('', $content);
            $style .= '.nct-slides-view-content ' . $uniqueid . ' .listen-content-item {
                '.$rules.'
            }';
        }

        return $imports . $style;
    }


}
