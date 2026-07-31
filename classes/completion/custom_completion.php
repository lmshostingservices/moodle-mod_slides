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

/**
 * Custom slides activity completion.
 *
 * @package   mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_slides\completion;

require_once($CFG->dirroot.'/lib/completionlib.php');

use completion_info;
use core_completion\activity_custom_completion;

/**
 * Custom activity completion defined for content designer.
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        switch ($rule) {

            case 'completionendreach':
                $status = $DB->record_exists('slides_completion',
                    ['slidesid' => $this->cm->instance, 'userid' => $this->userid, 'completion' => 1]);
                break;
            default:
                $status = false;
                break;
        }

        return $status ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionendreach'
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {

        return [
            'completionendreach' => get_string('completiondetail:reachend', 'slides'),
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionendreach',
        ];
    }

    /**
     * Nct slides completion.
     *
     * @param object $ncttabs
     * @param object $course
     * @param cm_info $cm
     *
     * @return void
     */
    public static function slides_completion($slideinstance, $course, $cm) {
        global $USER, $DB;

        // Completion.
        if ($completion = $DB->get_field('slides_completion', 'completion', ['slidesid' => $slideinstance->slidesid, 'userid' => $USER->id])) {

            // Update completion state.
            $completion = new completion_info($course);

            if ($completion->is_enabled($cm) && !empty($cm->customdata['customcompletionrules']['completionendreach'])) {
                $completion->update_state($cm, COMPLETION_COMPLETE);
            }
        }

    }
}
