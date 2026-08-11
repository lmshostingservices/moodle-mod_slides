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

namespace slidetype_imagetext;

class slideform extends \mod_slides\form\general_slide_form {
    public function definition() {

        // Callback to the parent is important. setup the context and other info.
        parent::definition();

        $mform = $this->_form;

        $this->standard_intro_elements();

        $mform->addElement('header', 'slidesettings', get_string('pluginname', 'slidetype_imagetext'));
        $mform->setExpanded('slidesettings');
        // // Introcontent options.
        // $options = slides_get_editor_options($this->context);
        // $mform->addElement('editor', 'introcontent', get_string('content', 'mod_slides'), $options);

        // Content 1.
        $mform->addElement('editor', 'content[1]', get_string('content', 'slidetype_imagetext', 1), ['rows' => 10], slides_get_editor_options($this->context));
        // $mform->addElement('filemanager', 'contentaudio[1]', get_string('contentaudio', 'slidetype_imagetext', 1), null, nctimagetext_get_file_options(['audio']));

        // Content 2.
        $mform->addElement('editor', 'content[2]', get_string('content', 'slidetype_imagetext', 2), ['rows' => 10], slides_get_editor_options($this->context));
        // $mform->addElement('filemanager', 'contentaudio[2]', get_string('contentaudio', 'slidetype_imagetext', 2), null, nctimagetext_get_file_options(['audio']));

        // Content 3.
        $mform->addElement('editor', 'content[3]', get_string('content', 'slidetype_imagetext', 3), ['rows' => 10], slides_get_editor_options($this->context));
        // $mform->addElement('filemanager', 'contentaudio[3]', get_string('contentaudio', 'slidetype_imagetext', 3), null, nctimagetext_get_file_options(['audio']));


        // Intro image.
        $fileoptions = slides_get_file_options();
        $mform->addElement('filemanager', 'contentimage', get_string('contentimage', 'slidetype_imagetext'), 'contentimage', $fileoptions);

        $mform->addElement('text', 'additional[contentimageheight]', get_string('contentimageheight', 'slidetype_imagetext'), null);
        $mform->setType('additional[contentimageheight]', PARAM_INT);

        $sides = [
            slideinstance::IMAGERIGHT => get_string('right', 'slidetype_imagetext'),
            slideinstance::IMAGELEFT => get_string('left', 'slidetype_imagetext'),
        ];
        $mform->addElement('select', 'additional[imagedirection]', get_string('imagedirection', 'slidetype_imagetext'), $sides);


        $this->include_listen_options(3);

        $this->include_animations();

        $this->include_appearance();

        $this->add_action_buttons();
    }

    /**
     * Set the default image height.
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public function definition_after_data() {
        $mform = $this->_form;

        // Additional content image height.
        if (!$mform->getElementValue('additional[contentimageheight]')) {
            $defaultimageheight = get_config('slides', 'imageheight');
            $mform->setDefault('additional[contentimageheight]', $defaultimageheight ?: 600);
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
            $contents = $DB->get_records('slidetype_imagetext', ['slideinstanceid' => $data->slideinstanceid]);

            if (!empty($contents)) {
                $i = 1;

                $filearea = 'imagetextcontent_';
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
                    ];

                    $i++;
                }
            }
        }
    }

}
