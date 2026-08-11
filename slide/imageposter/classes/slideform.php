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
 * mod_slides file.
 *
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

namespace slidetype_imageposter;

class slideform extends \mod_slides\form\general_slide_form {
    public function definition() {

        // Callback to the parent is important. setup the context and other info.
        parent::definition();

        $mform = $this->_form;

        $this->standard_intro_elements();

        $mform->addElement('header', 'slidesettings', get_string('pluginname', 'slidetype_imageposter'));
        $mform->setExpanded('slidesettings');

        // Introcontent options.
        /* $options = slides_get_editor_options($this->context);
        $mform->addElement('editor', 'introcontent', get_string('content', 'mod_slides'), $options);
 */
        // Intro image.
        $fileoptions = slides_get_file_options();
        $mform->addElement('filemanager', 'posterimage', get_string('posterimage', 'mod_slides'), 'posterimage', $fileoptions);

        /* $mform->addElement('text', 'posterimageheight', get_string('posterimage', 'mod_slides'), null);
        $mform->setType('introimageheight', PARAM_INT); */

        $this->include_listen_options(1);

        $this->include_animations(false, true);

        $this->include_appearance(true, false);

        $this->add_action_buttons();
    }

}
