<?php

namespace slidetype_flip;


use mod_slides\slideinstance as Mod_slidesSlideinstance;
use stdClass;

class slide extends \mod_slides\slidetype implements \mod_slides\dynamicsplit_interface {

    public const SHORTNAME = 'flip';

    public const LISTORDER = 5;

    protected const SPLIT_CONTENT_COUNT = 18;

    protected const SPLIT_MAXINSTANCE_COUNT = 10;

    public static function slide_shortname(): string {
        return self::SHORTNAME;
    }

    public static function slide_description(): string {
        return get_string('slidedescription', 'slidetype_flip');
    }

    public static function slide_name(): string {
        return get_string('pluginname', 'slidetype_flip');
    }

    public static function slide_icon(): string {
        global $OUTPUT;

        return $OUTPUT->image_icon('monologo', get_string('pluginname', 'slidetype_flip'), 'slidetype_flip', ['class' => 'icon']);
    }

    public static function slide_fileareas_list() : array {
        return [];
    }

    public static function backup_files() : array {

        // Include file areas.
        $filearea = "flipcontent_";
        $feedbackarea = "flipfeedback_";
        $frontimagearea = "flip_frontimage_";

        foreach (range(1, 10) as $index) {
            $list[] = $filearea . $index;
            $list[] = $feedbackarea . $index;
            $list[] = 'flip_listenaudio_'.$index;
            $list[] = $frontimagearea . $index;
        }

        return $list;
    }

    public function is_user_completed(int $userid, int $instanceid): bool {
        return false;
    }

    /**
     * This slide doesn't supports the custom table.
     *
     * @return bool
     */
    protected function supports_custom_table() : bool {
        return true;
    }

    /**
     * Supports auto split.
     *
     * @return bool
     */
    public function supports_autosplit() : bool {
        return true;
    }

    /**
     * Includes custom js module.
     *
     * @return string
     */
    public function includes_custom_jsmodule() : string {
        return 'slidetype_flip/slide';
    }


    /**
     * Manage the instance data of slide type that supports the custom tabel method.
     *
     * @param [type] $data
     * @return void
     */
    protected function manage_custom_instance(&$data) {
        global $DB;


        $editor = slides_get_editor_options($this->context);

        $currentflips = !empty($data->id) ? $DB->get_records('slidetype_flip', ['slideinstanceid' => $data->id]) : [];
        $transaction = $DB->start_delegated_transaction();

        $updates = [];
        foreach ($data->content as $key => $content) {

            // Fetch the tabrecord based on the tab's ID and instance ID
            $flipid = $content['id'] ?? null;

             if (!empty($content['itemid'])) {
                // Draft item.
                $draftitemid = $content['itemid'];
                // Save the draft area files.
                $data->content[$key]['text'] = file_save_draft_area_files(
                    $draftitemid, $this->context->id, 'mod_slides', 'flipcontent_' . $key, $data->slideinstanceid, $editor, $data->content[$key]['text'] ?? ''
                );

                $data->feedback[$key]['text'] = file_save_draft_area_files(
                    $draftitemid, $this->context->id, 'mod_slides', 'flipfeedback_' . $key, $data->slideinstanceid, $editor, $data->feedback[$key]['text'] ?? ''
                );

                // Save front image if provided.
                if (!empty($data->flip_frontimage[$key])) {
                    file_save_draft_area_files(
                        $data->flip_frontimage[$key],
                        $this->context->id,
                        'mod_slides',
                        'flip_frontimage_' . $key,
                        $data->slideinstanceid,
                        [
                            'subdirs' => 0,
                            'maxfiles' => 1,
                            'accepted_types' => ['image']
                        ]
                    );
                }

                // Contents to insert.
                $newcontent = new stdClass();
                $newcontent->slideinstanceid = $data->slideinstanceid;
                $newcontent->content = $data->content[$key]['text'];
                $newcontent->contentformat = $data->content[$key]['format'] ?? FORMAT_HTML;
                $newcontent->feedback = $data->feedback[$key]['text'];
                $newcontent->feedbackformat = $data->feedback[$key]['format'] ?? FORMAT_HTML;

                if ($flipid) {
                    $newcontent->id = $flipid;
                    $updates[] = $newcontent;
                } else {
                    $inserts[] = $newcontent;
                }
                // Combine the contents to insert in single query.
            }

        }

        // Inser new flips.
        if (isset($inserts)) { //
            $DB->insert_records('slidetype_flip', $inserts);
        }

        // Update the flip content.
        if (isset($updates)) {
            foreach ($updates as $update) {
                $DB->update_record('slidetype_flip', $update);
            }
        }

        if (!empty($currentflips)) {
            $diff = array_diff(array_keys($currentflips), array_column($updates, 'id'));
            $DB->delete_records_list('slidetype_flip', 'id', $diff);
            // $DB->delete_records('slidetype_flip_contents', ['slideinstanceid' => $data->slideinstanceid]);
        }

        $transaction->allow_commit();

    }

    /**
     * Slide form
     *
     * @param [type] $mform
     * @param [type] $instance
     * @return void
     */
    public function slide_form($url, $customdata) {
        $slideform =  new \slidetype_flip\slideform($url, $customdata);
        return $slideform;
    }


    public function export_for_template($output) : array {
        return [];
    }

    public function split_dynamic_content($data, $mform) : void {
        $autohelper = new autohelper($this->context);
        $autohelper->update_module_files($data, $this, $mform);

        // $this->update_slide($data);
        // $mform->post_update_files_editors($formdata, $slidetypeobj);
        // return $slide;
    }

    /**
     * Duplicate the custom table data.
     *
     * @param object $formdata
     * @return void
     */
    public function update_duplicate_custom_tabledata(int $slideinstanceid, int $newinstanceid) : void {

    }

/*     public function get_custom_tabledata($slideinstanceid) {
        global $DB;
        $data = $DB->get_records('slidetype_flip', ['slideinstanceid' => $slideinstanceid]);
        return $data;
    } */

}


