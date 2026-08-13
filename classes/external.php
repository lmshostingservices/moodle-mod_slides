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

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_slides\completion\custom_completion;
use moodle_exception;

require_once($CFG->dirroot . '/lib/externallib.php');

class external extends \core_external\external_api {
    /**
     * Update fontsize
     *
     * @return external_function_parameters
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public static function update_fontsize_parameters() {

        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'ID of the tabs instance table', VALUE_REQUIRED),
            'normalsize' => new external_value(PARAM_FLOAT, 'Size of the content', VALUE_REQUIRED),
            'completionsize' => new external_value(PARAM_FLOAT, 'Size of the content on completion', VALUE_REQUIRED),
        ]);
    }

    /**
     * Update the font size.
     *
     * @param int $instanceid
     * @param int $head
     * @param int $content
     *
     * @return bool
     */
    public static function update_fontsize(int $instanceid, float $slideinstanceid, float $contentsize) {
        global $DB;

        $params = self::validate_parameters(self::update_fontsize_parameters(), [
            'instanceid' => $instanceid,
            'normalsize' => $slideinstanceid,
            'completionsize' => $contentsize,
        ]);
        $instanceid = $params['instanceid'];

        if (!$record = $DB->get_record('slides', ['id' => $instanceid])) {
            return false;
        }

        // Validate the caller against this activity's context and capability.
        $cm = get_coursemodule_from_instance('slides', $instanceid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/slides:view', $context);

        if (!$record->autotextsize) {
            return false;
        }

        // The autofontsize column is optional; only write it if the schema has it.
        if (!$DB->get_manager()->field_exists('slides_options', 'autofontsize')) {
            return false;
        }

        return (bool) $DB->set_field('slides_options', 'autofontsize', $contentsize,
            ['slideinstanceid' => (int) $slideinstanceid]);
    }

    public static function update_fontsize_returns() {
        return new external_value(PARAM_BOOL, 'Result of the font size update');
    }

    public static function update_slidecompletion_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'slideinstanceid' => new external_value(PARAM_INT, 'Slide instance id'),
            'slidetype' => new external_value(PARAM_ALPHA, 'Slide instance id'),
            'listenitem' => new external_value(PARAM_INT, 'Listen content item index'),
        ]);
    }

    public static function update_slidecompletion($cmid, $slideinstanceid, $slidetype, $listenitem) {
        global $USER;

        $params = self::validate_parameters(self::update_slidecompletion_parameters(), array(
            'cmid' => $cmid,
            'slideinstanceid' => $slideinstanceid,
            'slidetype' => $slidetype,
            'listenitem' => $listenitem,
        ));

        if (!confirm_sesskey()) {
            throw new moodle_exception('sessionexpired', 'core');
        }

        // Validate the caller against the activity context before recording completion.
        $cm = get_coursemodule_from_id('slides', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/slides:view', $context);

        // Get the slide type and the related slideinstance.
        $slidetype = helper::get_slide($slidetype, $cmid);
        $slideinstance = $slidetype->get_instance($slideinstanceid);

        // Store the completion for the item.
        $result = $slideinstance->update_slide_content_viewed($slideinstanceid, $USER->id, $listenitem);

        // Update the module slides completion.
        helper::update_slides_completion($slideinstance->slidesid);

        // Get the status of current slide completion.
        list($completed, $viewedindex, $itemcount) = $slideinstance->is_slide_completed();

        $slidescompleted = helper::is_slides_completed($slideinstance->slidesid);

        if ($slidescompleted) {
            custom_completion::slides_completion($slideinstance, $slidetype->course, $slidetype->cm);
        }

        $data = [
            'status' => $result ? true : false,
            'updateitem' => !$completed && $viewedindex < $itemcount,
            'updatenextslide' => $completed && !$slidescompleted ? true : false,
            'updatenextbuttons' => $slidescompleted
        ];

        return $data;
    }

    public static function update_slidecompletion_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Status of status'),
            'updateitem' => new external_value(PARAM_BOOL, 'Status of updateitem'),
            'updatenextslide' => new external_value(PARAM_BOOL, 'Status of updatenextslide'),
            'updatenextbuttons' => new external_value(PARAM_BOOL, 'Status of updatenextbuttons'),
        ]);
    }
}
