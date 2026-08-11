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

namespace slidetype_imagetext;


use mod_slides\slideinstance as Mod_slidesSlideinstance;
use stdClass;

class slide extends \mod_slides\slidetype implements \mod_slides\dynamicsplit_interface {
    public const SHORTNAME = 'imagetext';

    public const LISTORDER = 2;

    protected const SPLIT_CONTENT_COUNT = 3;

    protected const SPLIT_MAXINSTANCE_COUNT = 8;

    public static function slide_shortname(): string {
        return self::SHORTNAME;
    }

    public static function slide_description(): string {
        return get_string('slidedescription', 'slidetype_imagetext');
    }

    public static function slide_name(): string {
        return get_string('pluginname', 'slidetype_imagetext');
    }

    public static function slide_icon(): string {
        global $OUTPUT;

        return $OUTPUT->image_icon('monologo', get_string('pluginname', 'slidetype_imagetext'), 'slidetype_imagetext', ['class' => 'icon']);
    }

    public static function slide_fileareas_list() : array {
        return ['contentimage'];
    }

    public static function backup_files() : array {

        // Include file areas.
        $filearea = "imagetextcontent_";

        $list = ['contentimage'];
        foreach (range(1, 3) as $index) {
            $list[] = $filearea . $index;
            $list[] = 'imagetext_listenaudio_'.$index;
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

        $filearea = 'imagetextcontent_';
        $editor = slides_get_editor_options($this->context);

        for ($i = 1; $i <= slideinstance::COMPLETION_COUNT; $i++) {

            if (!empty($data->content[$i]['itemid'])) {
                // Draft item.
                $draftitemid = $data->content[$i]['itemid'];
                // Save the draft area files.
                $data->content[$i]['text'] = file_save_draft_area_files(
                    $draftitemid, $this->context->id, 'mod_slides', $filearea . $i, $data->slideinstanceid, $editor, $data->content[$i]['text']
                );
                // Contents to insert.
                $content = new stdClass();
                $content->slideinstanceid = $data->slideinstanceid;
                $content->content = $data->content[$i]['text'];
                $content->contentformat = $data->contentformat[$i] ?? FORMAT_HTML;
                // Combine the contents to insert in single query.
                $inserts[] = $content;
            }
        }

        if (isset($inserts) && $DB->delete_records('slidetype_imagetext', ['slideinstanceid' => $data->slideinstanceid])) {
            $DB->insert_records('slidetype_imagetext', $inserts);
        }
    }

    /**
     * Slide form
     *
     * @param [type] $mform
     * @param [type] $instance
     * @return void
     */
    public function slide_form($url, $customdata) {
        $slideform =  new \slidetype_imagetext\slideform($url, $customdata);
        return $slideform;
    }


    public function export_for_template($output) : array {
        return [];
    }


}


