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
 * Definition restore activity task.
 *
 * @package   mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die('No direct access!');

require_once($CFG->dirroot . '/mod/slides/backup/moodle2/restore_slides_stepslib.php');

/**
 * Pulse restore task that provides all the settings and steps to perform one. complete restore of the activity
 */
class restore_slides_activity_task extends restore_activity_task {
    /**
     * Define particular settings for this activity.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define restore structure steps to restore to database from slides.xml.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_slides_activity_structure_step('slides_structure', 'slides.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = array();
        $contents[] = new restore_decode_content('slides', array('intro'), 'slides');
        // $contents[] = new \restore_decode_content('element_richtext', array('content'), 'richtext_instanceid');
        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        return array();
    }
}
