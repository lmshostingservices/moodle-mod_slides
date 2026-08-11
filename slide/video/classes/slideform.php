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

namespace slidetype_video;

class slideform extends \mod_slides\form\general_slide_form {
    public const VIDEO_MUTE = 1;

    public function definition() {

        // Callback to the parent is important. setup the context and other info.
        parent::definition();

        $mform = $this->_form;

        $this->standard_intro_elements();

        $mform->addElement('header', 'slidesettings', get_string('pluginname', 'slidetype_video'));
        $mform->setExpanded('slidesettings');

        // TODO: Add upload video option.

        // Introcontent options.
        // $options = slides_get_editor_options($this->context);
        $mform->addElement('text', 'additional[videourl]', get_string('videourl', 'mod_slides'), '');
        $mform->setType('additional[videourl]', PARAM_URL);
        $mform->addRule('additional[videourl]', null, 'required', null, 'client');


        $options = [
            0 => get_string('none', 'mod_slides'),
            self::VIDEO_MUTE => get_string('mute', 'mod_slides'),
        ];
        $mform->addElement('select', 'additional[mute]', get_string('videomute', 'mod_slides'), $options);


        $this->include_listen_options(1);

        $this->include_animations(false);

        $this->include_appearance(true, false);

        $this->add_action_buttons();
    }

    /**
     * Include the listen options.
     *
     * @param int $durationcount Number of listen options need to include in the form.
     *
     * @return void
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    protected function include_listen_options(int $durationcount=1) {

        $mform = $this->_form;

        $this->listendurationcounts = $durationcount;

        $mform->addElement('header', 'listenoptions', get_string('listenoptions', 'mod_slides'));

        // Force listen the feedback.
        $options = [
            0 => get_string('none'),
            slideinstance::FORCEEND => get_string('forceend', 'slidetype_video'),
            slideinstance::FORCESECONDS => get_string('forceduration', 'mod_slides'),
        ];
        $mform->addElement('select', 'options[forcelisten]', get_string('forcelisten', 'mod_slides'), $options);

        // Force the next to hide or disable until the user listen the feedback.
        $options = [
            slideinstance::NEXTDISABLE => get_string('disable'),
            slideinstance::NEXTHIDDEN => get_string('hide'),
        ];
        $mform->addElement('select', 'options[forcenext]', get_string('forcenextconfig', 'mod_slides'), $options);
        $mform->hideIf('forcenext', 'options[forcelisten]', 'eq', 0);

        for ($i = 1; $i <= $durationcount; $i++) {
            $name = "options[listenduration][$i]";
            // Force seconds to listen the feedback.
            $mform->addElement('text', $name, get_string('listenduration', 'mod_slides', $i), 60);
            $mform->setType($name, PARAM_INT);
            $mform->addRule($name, get_string('error'), 'numeric', null, 'client');
            $mform->hideIf($name, 'options[forcelisten]', 'neq', slideinstance::FORCESECONDS);
        }

        for ($i = 1; $i <= $durationcount; $i++) {
            // Audio element.
            $audioname = "listenaudio[$i]";
            $fileoptions = slides_get_file_options(['audio']);
            $mform->addElement('filemanager', $audioname, get_string('listenaudio', 'slidetype_video', 1), null, $fileoptions);
        }

    }


}
