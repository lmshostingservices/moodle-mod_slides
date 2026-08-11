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

use stdClass;
use html_writer;
use mod_slides_mod_form;

class helper {
    /**
     * Returns the given slides class instance object.
     *
     * @param int|string $slide
     * @param int|null $cmid
     * @return \slides
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public static function get_slide($slidetype, $cmid=null, $exist=MUST_EXIST) {
        global $DB;

        if (is_number($slidetype)) {
            $slidetype = $DB->get_field('slides_slides', 'shortname', ['id' => $slidetype]);
        }

        // echo $slidetype;
        $slideclass = self::get_slideclass($slidetype);
        // echo $slideclass;
        if (class_exists($slideclass) && is_subclass_of($slideclass, "mod_slides\slidetype")) {
            return new $slideclass($cmid, []);
        } else if ($exist == MUST_EXIST) {
            throw new \moodle_exception('slidenotfound', 'mod_slides');
        }

        return false;
    }

    /**
     * Get list of available slides for the modal to insert.
     *
     * @param int $cmid course module id.
     * @return string HTML of the slides list.
     */
    public static function get_slides_list(int $cmid) {

        $plugins = \core_plugin_manager::instance()->get_installed_plugins('slidetype');

        $li = [];
        foreach ($plugins as $plugin => $version) {

            $slideobj = self::get_slide($plugin, $cmid, IGNORE_MISSING);

            if (empty($slideobj)) {
                continue;
            }

            if (!$slideobj->supports_multiple_instance()) {
                continue;
            }

            $info = $slideobj::info();
            $description = html_writer::span($info->description, 'slide-description');
            $name = html_writer::span($info->name, 'slide-name');

            $li[$slideobj::LISTORDER] = html_writer::tag('li',
                $info->icon . $name,
                ['data-slidetype' => $info->shortname, 'class' => 'slide-item']
            );
        }

        ksort($li); // Sort by the weight of plugin.

        return html_writer::tag('ul', implode('', $li), ['class' => 'slides-list']);
    }

    /**
     * Get the slide type class namespace.
     *
     * @param [type] $slidetype
     * @return string
     */
    public static function get_slideclass(string $slidetype) {

        return '\slidetype_' . $slidetype . '\slide';
    }

    public static function is_slides_completed(int $slidesid, int $userid=0) {
        global $DB, $USER;

        if ($completion = $DB->get_record('slides_completion', ['slidesid' => $slidesid, 'userid' => $userid ?: $USER->id])) {
            return $completion->completion ? true : false;
        }

        return false;
    }

    public static function update_slides_completion(int $slidesid, int $userid=0) {
        global $DB, $USER;

        // Try to fetch the existing record in the 'completion' table for the user and course
        $completion = $DB->get_record('slides_completion', ['slidesid' => $slidesid, 'userid' => $userid ?: $USER->id], '*', IGNORE_MULTIPLE);

        // If the record exists, increment the 'tabviewed' field
        $sql = 'SELECT count(*)
                FROM {slides_slide_completion}
                WHERE userid = :userid AND completion=:completion AND slideinstanceid IN (
                    SELECT id FROM {slides_slide} WHERE slidesid = :slidesid
                )';

        $slideviewed = $DB->count_records_sql($sql, ['slidesid' => $slidesid, 'userid'=> $userid ?: $USER->id, 'completion' => 1]);
        $slidecount = $DB->count_records('slides_slide', ['slidesid' => $slidesid, 'status' => slideinstance::STATUS_ENABLE]);

        if ($completion) {

            /* if ($completion->slideviewed >= $slidecount) {
                return null;
            } */

            $completion->slideviewed = $slideviewed;
            $completion->slidecount = $slidecount;
            $completion->completion = $slideviewed >= $slidecount;

            $completion->timemodified = time(); // Update the timestamp.
            $DB->update_record('slides_completion', $completion); // Update the record.

            $completionid = $completion->id;
        } else {
            // If no record exists, insert a new record into the 'completion' table.
            $completion = new stdClass();
            $completion->slidesid = $slidesid;
            $completion->userid = $USER->id;
            $completion->timecreated = time();
            $completion->slideviewed = $slideviewed;
            $completion->slidecount = $slidecount;
            $completion->completion = $slideviewed >= $slidecount;
            $completionid = $DB->insert_record('slides_completion', $completion); // Insert the completion.
        }
    }

    public static function get_slides_slide_list($slidesid) : array {
        global $DB, $PAGE;

        $jsdata = [];

        // Get all the slide created for this slides module, ordered by sortorder
        // so get_next_slide() walks them in display order, not DB insertion order.
        // Without this, slides added/reordered after initial creation have higher
        // DB ids and appear at the end of the array — causing the "jumps to last
        // slide" bug where e.g. slide 7's next is slide 19 instead of slide 8.
        $slideinstances = $DB->get_records('slides_slide', ['slidesid' => $slidesid, 'status' => slideinstance::STATUS_ENABLE], 'sortorder ASC');

        if (empty($slideinstances)) {
            return [];
        }

        return $slideinstances;

    }

    public static function get_next_slide(int $slideinstanceid) {
        global $DB;

        $slidesid = $DB->get_field('slides_slide', 'slidesid', ['id' => $slideinstanceid]);

        if (!$slidesid) {
            return false;
        }

        $listofslide = self::get_slides_slide_list($slidesid);

        while ($current = current($listofslide)) {
            $next = next($listofslide);
            if ($current->id == $slideinstanceid) {
                break;
            }
        }

        return $next ?? [];
    }


    public static function get_slide_from_instance(int $slideinstanceid, int $cmid) {
        global $DB;

        $slidetype = $DB->get_field('slides_slide', 'slidetype', ['id' => $slideinstanceid]);

        if (!$slidetype) {
            return false;
        }

        $slide = self::get_slide($slidetype, $cmid);

        return $slide;
    }

    public static function backup_include_files($pluginfile, $pluginname) {
        global $DB;

        // Merge sub slides area files.
        $areas = \mod_slides\slide_editor::get_slides_areafiles();
        $list = $areas[$pluginname] ?? [];
        foreach ($list as $filearea) {
            $pluginfile->annotate_files('mod_slides', $filearea, null);
        }
        // return $list;
    }

    public static function backup_get_relatedfiles($pluginname) {
        $areas = \mod_slides\slide_editor::get_slides_areafiles();
        return $areas[$pluginname] ?? [];
    }


}
