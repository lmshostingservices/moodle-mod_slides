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

namespace slidetype_matching;

class slideform extends \mod_slides\form\general_slide_form {
    protected bool $supportaudio = false;

    public function definition() {
        global $PAGE;

        // Callback to the parent is important. setup the context and other info.
        parent::definition();

        $mform = $this->_form;

        $this->standard_intro_elements();

        $mform->setDefault('title', ['text' => get_string('matchingactivity', 'mod_slides')]);

        $mform->addElement('header', 'slidesettings', get_string('pluginname', 'slidetype_matching'));
        $mform->setExpanded('slidesettings');


        $options = array_combine(range(1, 8), range(1, 8));

        $mform->addElement('select', 'additional[matchingcount]', get_string('matchingscount', 'slidetype_matching'), $options);

        $mform->registerNoSubmitButton('updatematchingcount');
        $mform->addElement('submit', 'updatematchingcount', get_string('updatematchingscount', 'slidetype_matching'), [
            'class' => 'd-none',
        ]);

        $PAGE->requires->js_amd_inline("
            document.querySelector('select[name=\"additional[matchingcount]\"]') !== null ? document.querySelector('select[name=\"additional[matchingcount]\"]')
                .onchange = (e) => document.querySelector('input[name=updatematchingcount]').click() : ''; "
        );

        $mform->addElement('static', 'contentsection', '');
    }


    /**
     * Definied the matchings form fields to attach with form after the forms are defined,
     *
     * @return void
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public function definition_after_data() {
        global $PAGE;

        $mform = $this->_form;
        $matchingscount = $mform->getElementValue('additional[matchingcount]');
        $matchingscount = !empty($matchingscount) ? reset($matchingscount) : 1;

        // Tab content config.
        $this->definition_matchings($mform, $matchingscount);

        // $this->include_listen_options();

        $this->include_animations(false);

        $this->include_appearance();

        $this->add_action_buttons();

        // $mform->setDefault('name', get_string('matchingactivity', 'mod_slides'));

        parent::definition_after_data();

    }

    /**
     * Definition of the matchings configs.
     */
    public function definition_matchings(&$mform, $matchingscount) {

        for ($i = 1; $i <= $matchingscount; $i++) {

            $list[] = &$mform->addElement('header', "contentsectionhdr", get_string('contentheader', 'slidetype_matching'));

            $list[] = &$mform->addElement('hidden', "content[$i][id]");
            $mform->setType("content[$i][id]", PARAM_INT);

            // Content 1.
            $list[] = &$mform->addElement('editor', "content[$i]", get_string('content', 'slidetype_matching', $i), ['rows' => 7], slides_get_editor_options($this->context));

            // Content 1 Audio.
            $list[] = &$mform->addElement('text', "answer[$i]", get_string('answer', 'slidetype_matching', $i));
            $mform->setType("answer[$i]", PARAM_RAW);

        }

        foreach ($list as &$element) {
            $mform->insertElementBefore($mform->removeElement($element->getName(), false), 'contentsection');
        }
    }


    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function prepare_additional_file_editors(&$data, array $areas=[]) {
        global $DB;

        parent::prepare_additional_file_editors($data, $areas);

        if (!empty($data->slideinstanceid)) {

            // Get the contents of the image and text.
            $contents = $DB->get_records('slidetype_matching', ['slideinstanceid' => $data->slideinstanceid]);

            if (!empty($contents)) {
                $i = 1;

                $filearea = "matchingcontent_";

                foreach ($contents as $content) {

                    $draftitemid = file_get_submitted_draft_itemid("content[$i]");
                    $contenttext = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_slides',
                        $filearea . $i, $data->slideinstanceid, slides_get_editor_options($this->context), $content->content
                    );

                    $format = $content->contentformat;
                    $data->content[$i] = [
                        'text' => $contenttext,
                        'itemid' => $draftitemid,
                        'format' => $format,
                        'id' => $content->id,
                    ];

                    $data->answer[$i] = $content->answer;

                    $i++;
                }
            }
        }
    }

}
