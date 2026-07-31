<?php

namespace slidetype_introduction;

class slideform extends \mod_slides\form\general_slide_form {

    public function definition() {

        // Callback to the parent is important. setup the context and other info.
        parent::definition();

        $mform = $this->_form;

        $this->standard_intro_elements();

        $mform->addElement('header', 'slidesettings', get_string('pluginname', 'slidetype_introduction'));
        $mform->setExpanded('slidesettings');

        // Introcontent options.
        $options = slides_get_editor_options($this->context);
        $mform->addElement('editor', 'introcontent', get_string('content', 'mod_slides'), $options);

        // Intro image.
        $fileoptions = slides_get_file_options();
        $mform->addElement('filemanager', 'introimage', get_string('introimage', 'mod_slides'), 'introimage', $fileoptions);

        $mform->addElement('text', 'introimageheight', get_string('introimageheight', 'mod_slides'), null);
        $mform->setType('introimageheight', PARAM_INT);


        $this->include_listen_options(1);

        $this->include_animations();

        $this->include_appearance();

        $this->add_action_buttons();
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
        $data->introcontent_editor = $data->introcontent;
        $data = file_postupdate_standard_editor(
            $data, 'introcontent', $options, $this->context, 'mod_slides', 'slidetype_introduction', $data->slideinstanceid ?: 0
        );

        $data->additional = json_encode([
            'text' => $data->introcontent,
            'format' => $data->introcontentformat,
            'introimageheight' => $data->introimageheight
        ]);

        parent::data_postprocessing($data);
    }

    /**
     * Prepare the data before processing.
     *
     * Note: Only use this method for processing fileareas, to update the data structute please use the get_data_for_form.
     * @param [type] $data
     * @return void
     */
    /* public function prepare_files_editors(&$data) {

        $this->prepare_filearea($data, 'introimage', 'mod_slides');

    } */

}
