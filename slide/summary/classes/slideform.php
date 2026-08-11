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

class slideform extends \mod_slides\form\general_slide_form {
    protected int $listendurationcounts = 8;

    public function definition() {
        global $PAGE;

        // Callback to the parent is important. setup the context and other info.
        parent::definition();

        $mform = $this->_form;

        $this->standard_intro_elements();

        $mform->addElement('header', 'slidesettings', get_string('pluginname', 'slidetype_summary'));
        $mform->setExpanded('slidesettings');


        $mform->addElement('editor', 'additional[heading]', get_string('heading', 'mod_slides'), array('rows' => 3));


        $options = array_combine(range(1, $this->listendurationcounts), range(1, $this->listendurationcounts));
        $mform->addElement('select', 'additional[count]', get_string('summarycount', 'slidetype_summary'), $options);

        $mform->registerNoSubmitButton('updatesummarycount');
        $mform->addElement('submit', 'updatesummarycount', get_string('updatesummarycount', 'slidetype_summary'), [
            'class' => 'd-none',
        ]);

        $PAGE->requires->js_amd_inline("
            document.querySelector('select[name=\"additional[count]\"]') !== null ? document.querySelector('select[name=\"additional[count]\"]')
                .onchange = (e) => document.querySelector('input[name=updatesummarycount]').click() : ''; "
        );

        $mform->addElement('static', 'contentsection', '');

    }

    /**
     * Definied the summary form fields to attach with form after the forms are defined,
     *
     * @return void
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public function definition_after_data() {
        global $PAGE;

        $mform = $this->_form;
        $summarycount = $mform->getElementValue('additional[count]');
        $summarycount = !empty($summarycount) ? reset($summarycount) : 1;

        // Tab content config.
        $this->definition_summary($mform, $summarycount);

        $this->include_listen_options($summarycount);

        $this->include_animations(false);

        $this->include_appearance();


        $this->add_action_buttons();

        // $PAGE->requires->js_call_amd('mod_nctsummary/nctsummary', 'loadFontExample', ['contextid' => $this->context->id]);

        parent::definition_after_data();
    }

    /**
     * Definition of the summary configs.
     */
    public function definition_summary(&$mform, $summarycount) {

        // Force listen the feedback options.
        /* $feedbackoptions = [
            0 => get_string('none'),
            self::FORCEAUDIO => get_string('forceaudio', 'slidetype_summary'),
            self::FORCESECONDS => get_string('forceduration', 'slidetype_summary'),
        ]; */


        for ($i = 1; $i <= $summarycount; $i++) {

            $list[] = &$mform->addElement('header', "contentsectionhdr", get_string('contentheader', 'slidetype_summary'));

            $list[] = &$mform->addElement('hidden', "content[$i][id]");
            $mform->setType("content[$i][id]", PARAM_INT);

            // Content 1.
            // Not file upload.
            $list[] = &$mform->addElement('editor', "content[$i]", get_string('content', 'slidetype_summary', $i), ['rows' => 7]);
            // , slides_get_editor_options($this->context));

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
            $contents = $DB->get_records('slidetype_summary', ['slideinstanceid' => $data->slideinstanceid]);

            if (!empty($contents)) {
                $i = 1;

                $filearea = 'summarycontent_';
                foreach ($contents as $content) {

                    $draftitemid = file_get_submitted_draft_itemid("content[$i]");
                    $contenttext = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_slides',
                        $filearea . $i, $data->slideinstanceid, slides_get_editor_options($this->context), $content->content
                    );

                    $format = $content->contentformat;
                    $data->content[$i] = [
                        'text' => $contenttext,
                        'itemid' => $draftitemid,
                        'format' => $format
                    ];

                    $i++;
                }
            }
        }
    }

    /**
     * Data post processing.
     *
     * @param \stdClass $data
     * @return void
     */
    protected function data_postprocessing(&$data) {

        // // Save the intro image files.
        // $this->save_filearea($data, 'introimage', 'mod_slides');

        // Slide editor file options.
        $options = slides_get_editor_options($this->context);

        // Intro content.
        $data->heading_editor = $data->additional['heading'];
        $data = file_postupdate_standard_editor(
            $data, 'heading', $options, $this->context, 'mod_slides', 'summary_heading', $data->slideinstanceid ?: 0
        );

        $data->additional['heading'] = [
            'text' => $data->heading,
            'format' => $data->headingformat,
        ];

        parent::data_postprocessing($data);
    }
}
