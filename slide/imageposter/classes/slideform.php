<?php

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
