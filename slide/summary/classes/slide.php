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

namespace slidetype_summary;


use mod_slides\slideinstance as Mod_slidesSlideinstance;
use stdClass;

class slide extends \mod_slides\slidetype implements \mod_slides\dynamicsplit_interface {
    public const SHORTNAME = 'summary';

    public const LISTORDER = 7;

    protected const SPLIT_CONTENT_COUNT = 8;

    protected const SPLIT_MAXINSTANCE_COUNT = 1;


    public static function slide_shortname(): string {
        return self::SHORTNAME;
    }

    public static function slide_description(): string {
        return get_string('slidedescription', 'slidetype_summary');
    }

    public static function slide_name(): string {
        return get_string('pluginname', 'slidetype_summary');
    }

    public static function slide_icon(): string {
        global $OUTPUT;

        return $OUTPUT->image_icon('monologo', get_string('pluginname', 'slidetype_summary'), 'slidetype_summary', ['class' => 'icon']);
    }

    public static function slide_fileareas_list() : array {
        return ['contentimage'];
    }

    public static function backup_files() : array {

        // Include file areas.
        $filearea = "summarycontent_";

        $list[] = 'contentimage';
        foreach (range(1, 10) as $index) {
            $list[] = $filearea . $index;
            $list[] = 'summary_listenaudio_'.$index;
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
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
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
     * Manage the instance data of slide type that supports the custom tabel method.
     *
     * @param [type] $data
     * @return void
     */
    protected function manage_custom_instance(&$data) {
        global $DB;

        $filearea = 'summarycontent_';
        $editor = slides_get_editor_options($this->context);

        $currentsummarys = !empty($data->id) ? $DB->get_records('slidetype_summary', ['slideinstanceid' => $data->id]) : [];

        $transaction = $DB->start_delegated_transaction();

        // for ($data-> = 1; $i <= slideinstance::COMPLETION_COUNT; $i++) {

        $updates = [];

        foreach ($data->content as $key => $content) {

            // Fetch the tabrecord based on the tab's ID and instance ID
            $summaryid = $content['id'] ?? null;

            /* if (!empty($content['itemid'])) {
                // Draft item.
                $draftitemid = $content['itemid'];
                // Save the draft area files.
                $data->content[$key]['text'] = file_save_draft_area_files(
                    $draftitemid, $this->context->id, 'mod_slides', $filearea . $key, $data->slideinstanceid, $editor, $data->content[$key]['text']
                ); */

                // Contents to insert.
                $newcontent = new stdClass();
                $newcontent->slideinstanceid = $data->slideinstanceid;
                $newcontent->content = $data->content[$key]['text'];
                $newcontent->contentformat = $data->content[$key]['format'] ?? FORMAT_HTML;

                if ($summaryid) {
                    $newcontent->id = $summaryid;
                    $updates[] = $newcontent;
                } else {
                    $inserts[] = $newcontent;
                }
                // Combine the contents to insert in single query.
            // }

        }

        // print_obj($inserts);exit;

        // Inser new summarys.
        if (isset($inserts)) { //
            $DB->insert_records('slidetype_summary', $inserts);
        }

        // Update the summary content.
        if (isset($updates)) {
            foreach ($updates as $update) {
                $DB->update_record('slidetype_summary', $update);
            }
        }

        if (!empty($currentsummarys)) {
            $diff = array_diff(array_keys($currentsummarys), array_column($updates, 'id'));
            $DB->delete_records_list('slidetype_summary', 'id', $diff);
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
        $slideform =  new \slidetype_summary\slideform($url, $customdata);
        return $slideform;
    }


    public function export_for_template($output) : array {
        return [];
    }


}


