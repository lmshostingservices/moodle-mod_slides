<?php

namespace slidetype_matching;


use mod_slides\slideinstance as Mod_slidesSlideinstance;
use stdClass;

class slide extends \mod_slides\slidetype implements \mod_slides\dynamicsplit_interface {

    public const SHORTNAME = 'matching';

    public const LISTORDER = 4;

    protected const SPLIT_CONTENT_COUNT = 16;

    protected const SPLIT_MAXINSTANCE_COUNT = 1;

    public static function slide_shortname(): string {
        return self::SHORTNAME;
    }

    public static function slide_description(): string {
        return get_string('slidedescription', 'slidetype_matching');
    }

    public static function slide_name(): string {
        return get_string('pluginname', 'slidetype_matching');
    }

    public static function slide_icon(): string {
        global $OUTPUT;

        return $OUTPUT->image_icon('monologo', get_string('pluginname', 'slidetype_matching'), 'slidetype_matching', ['class' => 'icon']);
    }

    public static function slide_fileareas_list() : array {
        return [];
    }

    public static function backup_files() : array {
        // Include file areas.
        $filearea = "matchingcontent_";

        foreach (range(1, 10) as $index) {
            $list[] = $filearea . $index;
            $list[] = 'matching_listenaudio_'.$index;
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

    public function supports_listenoptions() : bool {
        return false;
    }

    /**
     * Includes custom js module.
     *
     * @return string
     */
    public function includes_custom_jsmodule() : string {
        return 'slidetype_matching/slide';
    }

    /**
     * Supports auto split.
     *
     * @return bool
     */
    public function supports_autosplit() : bool {
        return true;
    }

    public function supports_autoresize() : bool {
        return false;
    }


    /**
     * Manage the instance data of slide type that supports the custom tabel method.
     *
     * @param [type] $data
     * @return void
     */
    protected function manage_custom_instance(&$data) {
        global $DB;

        $filearea = 'matchingcontent_';
        $editor = slides_get_editor_options($this->context);

        $currentmatchings = !empty($data->id) ? $DB->get_records('slidetype_matching', ['slideinstanceid' => $data->id]) : [];

        $transaction = $DB->start_delegated_transaction();

        // for ($data-> = 1; $i <= slideinstance::COMPLETION_COUNT; $i++) {

        $updates = [];

        foreach ($data->content as $key => $content) {

            // Fetch the tabrecord based on the tab's ID and instance ID
            $matchingid = $content['id'] ?? null;

             if (!empty($content['itemid'])) {
                // Draft item.
                $draftitemid = $content['itemid'];
                // Save the draft area files.
                $data->content[$key]['text'] = file_save_draft_area_files(
                    $draftitemid, $this->context->id, 'mod_slides', $filearea . $key, $data->slideinstanceid, $editor, $data->content[$key]['text']
                );
                // Contents to insert.
                $newcontent = new stdClass();
                $newcontent->slideinstanceid = $data->slideinstanceid;
                $newcontent->content = $data->content[$key]['text'];
                $newcontent->contentformat = $data->content[$key]['format'] ?? FORMAT_HTML;
                $newcontent->answer = $data->answer[$key] ?? '';

                if ($matchingid) {
                    $newcontent->id = $matchingid;
                    $updates[] = $newcontent;
                } else {
                    $inserts[] = $newcontent;
                }
                // Combine the contents to insert in single query.
            }

        }

        // Inser new matchings.
        if (isset($inserts)) { //
            /* print_obj($inserts);
            exit; */
            $DB->insert_records('slidetype_matching', $inserts);
        }

        // Update the matching content.
        if (isset($updates)) {
            foreach ($updates as $update) {
                $DB->update_record('slidetype_matching', $update);
            }
        }

        if (!empty($currentmatchings)) {
            $diff = array_diff(array_keys($currentmatchings), array_column($updates, 'id'));
            $DB->delete_records_list('slidetype_matching', 'id', $diff);
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
        $slideform =  new \slidetype_matching\slideform($url, $customdata);
        return $slideform;
    }


    public function export_for_template($output) : array {
        return [];
    }


    public function split_dynamic_content($data, $mform) : void {
        $autohelper = new autohelper($this->context);
        $autohelper->update_module_files($data, $this, $mform);
    }

}


