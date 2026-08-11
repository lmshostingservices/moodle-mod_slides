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

namespace slidetype_flip;

class slideform extends \mod_slides\form\general_slide_form {
    public function definition() {
        global $PAGE;

        // Callback to the parent is important. setup the context and other info.
        parent::definition();

        $mform = $this->_form;

        $this->standard_intro_elements();

        $mform->setDefault('title', ['text' => get_string('flipactivity', 'mod_slides')]);

        $mform->addElement('header', 'slidesettings', get_string('pluginname', 'slidetype_flip'));
        $mform->setExpanded('slidesettings');

        $options = array_combine(range(1, 10), range(1, 10));
        $mform->addElement('select', 'additional[flipscount]', get_string('flipscount', 'slidetype_flip'), $options);

        $mform->registerNoSubmitButton('updateflipscount');
        $mform->addElement('submit', 'updateflipscount', get_string('updateflipscount', 'slidetype_flip'), [
            'class' => 'd-none',
        ]);

        $PAGE->requires->js_amd_inline("
            document.querySelector('select[name=\"additional[flipscount]\"]') !== null ? document.querySelector('select[name=\"additional[flipscount]\"]')
                .onchange = (e) => document.querySelector('input[name=updateflipscount]').click() : ''; "
        );

        $mform->addElement('static', 'contentsection', '');



    }


    /**
     * Definied the flips form fields to attach with form after the forms are defined,
     *
     * @return void
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public function definition_after_data() {
        global $PAGE;

        $mform = $this->_form;
        $flipscount = $mform->getElementValue('additional[flipscount]');
        $flipscount = !empty($flipscount) ? reset($flipscount) : 1;

        // Tab content config.
        $this->definition_flips($mform, $flipscount);

        $this->include_listen_options($flipscount);

        $this->include_animations(false);

        $this->include_appearance();


        $this->add_action_buttons();
        // $PAGE->requires->js_call_amd('mod_nctflips/nctflips', 'loadFontExample', ['contextid' => $this->context->id]);

        parent::definition_after_data();
    }

    /**
     * Definition of the flips configs.
     */
    public function definition_flips(&$mform, $flipscount) {

        // Force listen the feedback options.

        for ($i = 1; $i <= $flipscount; $i++) {

            $list[] = &$mform->addElement('header', "contentsectionhdr", get_string('contentheader', 'slidetype_flip'));

            $list[] = &$mform->addElement('hidden', "content[$i][id]");
            $mform->setType("content[$i][id]", PARAM_INT);

            // Front image (optional) - displayed above autosplit text.
            $list[] = &$mform->addElement('filemanager', "flip_frontimage[$i]", get_string('flipfrontimage', 'slidetype_flip'), null, [
                'maxfiles' => 1,
                'accepted_types' => ['image'],
                'subdirs' => 0
            ]);

            // Content 1.
            $list[] = &$mform->addElement('editor', "content[$i]", get_string('content', 'slidetype_flip', $i), ['rows' => 7], slides_get_editor_options($this->context));

            // Content 1 Audio.
            $list[] = &$mform->addElement('editor', "feedback[$i]", get_string('feedback', 'slidetype_flip', $i), ['rows' => 7], slides_get_file_options(['audio']));

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
            $contents = $DB->get_records('slidetype_flip', ['slideinstanceid' => $data->slideinstanceid]);

            if (!empty($contents)) {
                $i = 1;

                $filearea = "flipcontent_";
                $feedbackarea = "flipfeedback_";

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

                    $draftitemid = file_get_submitted_draft_itemid("feedback[$i]");
                    $feedbacktext = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_slides',
                        $feedbackarea . $i, $data->slideinstanceid, slides_get_editor_options($this->context), $content->feedback
                    );

                    $format = $content->feedbackformat;
                    $data->feedback[$i] = [
                        'text' => $feedbacktext,
                        'itemid' => $draftitemid,
                        'format' => $format
                    ];

                    // Prepare front image file area.
                    $frontimagedraftid = file_get_submitted_draft_itemid("flip_frontimage[$i]");
                    file_prepare_draft_area($frontimagedraftid, $this->context->id, 'mod_slides',
                        'flip_frontimage_' . $i, $data->slideinstanceid, [
                            'subdirs' => 0,
                            'maxfiles' => 1,
                            'accepted_types' => ['image']
                        ]
                    );
                    $data->flip_frontimage[$i] = $frontimagedraftid;

                    $i++;
                }
            }
        }
    }

    /**
     * Appearance.
     *
     * @return void
     */
    public function include_appearance(bool $heading=true, bool $content=true) {
        global $PAGE;

        $mform = $this->_form;

        parent::include_appearance($heading);

        // Fonts styling.
        $fonts = ['' => 'None'] + $this->get_google_webfonts();

        // Paragraph font style.
        $group = [];
        $group[] = $mform->createElement('autocomplete', 'options[fontstyle][feedbackfont]', get_string('contentfonts', 'slides'), $fonts);
        $group[] = $mform->createElement('html', '<div class="slides-fontstyle-demo" data-target="#id_options_fontstyle_feedbackfont" style="flex:2;padding:5px;">
            <p> Lorem ipsum dolor sit amet, consectetur adipiscing elit.
            Cras lacinia nisl accumsan tincidunt bibendum. Aliquam eget mattis metus.
            Aenean id dolor a orci accumsan rhoncus. Lorem ipsum dolor sit amet </p></div>'
        );
        $mform->addGroup($group, 'feedbackfontstyles', get_string('feedbackfonts', 'slides'), '', false);

        // Paragraph font size.
        $mform->addElement('text', 'options[fontstyle][feedbacksize]', get_string('feedbackfontsize', 'slides'), '16');
        $mform->setType('options[fontstyle][feedbacksize]', PARAM_INT);
        $mform->addRule('options[fontstyle][feedbacksize]', '', 'numeric', null, 'client');

        // $mform->hideIf('options[fontstyle][feedbacksize]', 'autotextsize', 'eq', 1);

        $PAGE->requires->js_call_amd('mod_slides/slide_editor', 'loadFontExample', ['demoSelectors' => ['#id_options_fontstyle_feedbackfont'] ]);

    }

}
