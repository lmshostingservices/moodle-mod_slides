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
 * NCT Slides module instance add and update form.
 *
 * @package    mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/slides/lib.php');

/**
 * NCT slides module form.
 */
class mod_slides_mod_form extends moodleform_mod {

    /**
     * Define the mform elements.
     * @return void
     */
    public function definition() {
        global $CFG, $DB, $OUTPUT;

        $mform =& $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('text', 'containerheight', get_string('containerheight', 'mod_slides'), array('size' => '48'));
        $mform->addRule('containerheight', null, 'numeric', null, 'client');

        $mform->addHelpButton('containerheight', 'containerheight', 'mod_slides');
        $mform->setType('containerheight', PARAM_INT);

        // Auto text size.
        $mform->addElement('advcheckbox', 'autotextsize', get_string('autotextsize', 'mod_slides'));

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    /**
     * Custom completion rules definition.
     *
     * @return void
     */
    public function add_completion_rules() {

        $mform = $this->_form;

        $suffix = $this->get_suffix();

        $mform->addElement('checkbox', 'completionendreach' . $suffix, get_string('completionendreach', 'slides'));
        $mform->setDefault('completionendreach' . $suffix, 0);
        $mform->addHelpButton('completionendreach' . $suffix, 'completionendreach', 'slides');

        return ['completionendreach' . $suffix];
    }

    /**
     * Validate the form to check custom completion has selected conditions.
     *
     * @param array $data Input data not yet validated.
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
        return (!empty($data['completionendreach' . $this->get_suffix()]));
    }

    public function data_postprocessing($data) {
        global $PAGE;

        // Custom completion
        $sufix = $this->get_suffix();
        $data->{"completionendreach"} = isset($data->{"completionendreach$sufix"}) ? 1 : 0;
    }


    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
         // Custom completion
        if (isset($defaultvalues['completionendreach'])) {
            $sufix = $this->get_suffix();
            $defaultvalues["completionendreach$sufix"] = $defaultvalues['completionendreach'];
        }
    }
}
