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
 * Form for editing a general slide.
 *
 * @package    mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_slides\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

use curl;
use stdClass;
use html_writer;
use mod_slides\editor;
use cache;
use mod_slides\autohelper;
use mod_slides\slideinstance;

require_once($CFG->dirroot.'/lib/formslib.php');


/**
 * General option form to create slides.
 *
 * @copyright 2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class general_slide_form extends \moodleform {
    /**
     * Make the custom data as public varaible to access on the slides forms.
     *
     * @var array
     */
    public $_customdata;

    protected $slide;

    protected $context;

    protected bool $supportaudio = true;

    protected int $listendurationcounts = 1;

    protected bool $ffmpeginstalled = false;

    public function definition() {

        global $USER, $CFG, $COURSE, $PAGE;

        $mform = $this->_form;

        $slidetype = $this->_customdata['slidetype'];
        $slideinstanceid = $this->_customdata['slideinstanceid'] ?? 0;
        $cmid = $this->_customdata['cmid'];

        $this->context = \context_module::instance($cmid);

        // CM id.
        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        // Slide instance id.
        $mform->addElement('hidden', 'slideinstanceid', $slideinstanceid);
        $mform->setType('slideinstanceid', PARAM_INT);

        // Slide type.
        $mform->addElement('hidden', 'slidetype', $slidetype);
        $mform->setType('slidetype', PARAM_ALPHANUM);

        // Slide type.
        $mform->addElement('hidden', 'slidesid', $this->_customdata['slidesid'] ?? 0);
        $mform->setType('slidesid', PARAM_INT);
    }

    public function standard_intro_elements() {

        $mform = $this->_form;

        $slidetype = $this->_customdata['slidetype'];
        $slideinstanceid = $this->_customdata['slideinstanceid'] ?? 0;
        $cmid = $this->_customdata['cmid'];

        $slide = \mod_slides\slide_editor::get_slide($slidetype, $cmid);

        if ($slide->supports_autosplit()) {
            $this->include_autosplit_method();
        }
        // $mform->addElement('header', 'slidesettings', get_string('slidesettings', 'mod_slides', ucfirst($slide->slide_name())));
        $mform->addElement('header', 'generalsettings', get_string('general'));

        $mform->addElement('text', 'name',  get_string('slidetitle', 'mod_slides'),  'maxlength="100" size="30"');
        $mform->setType('name', PARAM_NOTAGS);

        // Visibility for General slide.
        $visibleoptions = [
            slideinstance::STATUS_ENABLE => get_string('visible'),
            slideinstance::STATUS_DISABLE => get_string('hidden', 'mod_slides'),
        ];

        $mform->addElement('select', 'status', get_string('visibility', 'mod_slides'), $visibleoptions);
        $mform->addHelpButton('status', 'visibility', 'mod_slides');

        // Title.
        // $options = slides_get_editor_options($this->context);
        $editor = $mform->addElement('editor', 'title', get_string('title', 'mod_slides'), ['rows' => 3]);

    }

    /**
     * Add action buttons.
     *
     * @param boolean $cancel
     * @param [type] $submitlabel
     * @return void
     */
    public function add_action_buttons($cancel = true, $submitlabel = null) {

        $mform = $this->_form;
        $slideinstanceid = $this->_customdata['slideinstanceid'] ?? 0;

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUMEXT);

        $buttonstr = '';
        if ($slideinstanceid) {
            $buttonstr = get_string('slideupdate', 'mod_slides');
        } else {
            $buttonstr = get_string('slidecreate', 'mod_slides');
        }

        parent::add_action_buttons(true, $buttonstr);
    }

    /**
     * Defined the standard general options moodle form slides for slides.
     *
     * @param moodle_form $mform Moodle quick form.
     * @return void
     */
    public function standard_slide_settings($mform) {
        global $CFG;

        // Accessibility: "Required" is bad legend text.
        $strrequired = get_string('required');

        // Print the required moodle fields first.
        // Title for General slide.
        // $mform->addElement('header', 'generalsettings', get_string('generaltitle', 'mod_slides'));
    }


    public function include_animations(bool $image=true, bool $content=true) {

        $mform = $this->_form;
        $mform->addElement('header', 'animationsettings', get_string('animationtitle', 'mod_slides'));

        $animations = $this->get_animations();
        if ($image) {
            $mform->addElement('select', 'options[imageanimation]', get_string('imageanimation', 'slides'), $animations);
        }

        if ($content) {
            // Animation of the contents.
            $mform->addElement('select', 'options[animation]', get_string('animation', 'slides'), $animations);
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

        $mform->addElement('header', 'appearancesettings', get_string('appearancetitle', 'mod_slides'));

         // Font style and size.
        $mform->addElement('static', 'answerheader', html_writer::tag('p', "<b>" . get_string('staticfontstylesize', 'slides') . "</b>"));

        // Fonts styling.
        $fonts = ['' => 'None'] + $this->get_google_webfonts();

        // Heading font style and size.
        if ($heading) {
            $group = [];
            $group[] = $mform->createElement('autocomplete', 'options[fontstyle][headingfont]', get_string('headingfonts', 'slides'), $fonts);
            $group[] = $mform->createElement('html', '<div class="slides-fontstyle-demo" data-target="#id_options_fontstyle_headingfont" style="color:var(--primary);flex:2;padding:10px;padding-top:35px;"><h3>This is Demo Heading Style </h3></div>');
            $mform->addGroup($group, 'headingfontstyles', get_string('headingfonts', 'slides'), '', false);

            // Font size
            $mform->addElement('text', 'options[fontstyle][headingsize]', get_string('headingfontsize', 'slides'), '16');
            $mform->setType('options[fontstyle][headingsize]', PARAM_INT);
            $mform->addRule('options[fontstyle][headingsize]', get_string('onlynumeric', 'mod_slides'), 'numeric', null, 'client');
            // $mform->hideIf('fontstyle[headingsize]', 'autotextsize', 'eq', 1);
        }

        if ($content) {
            // Paragraph font style.
            $group = [];
            $group[] = $mform->createElement('autocomplete', 'options[fontstyle][contentfont]', get_string('contentfonts', 'slides'), $fonts);
            $group[] = $mform->createElement('html', '<div class="slides-fontstyle-demo" data-target="#id_options_fontstyle_contentfont" style="flex:2;padding:5px;">
                <p> Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                Cras lacinia nisl accumsan tincidunt bibendum. Aliquam eget mattis metus.
                Aenean id dolor a orci accumsan rhoncus. Lorem ipsum dolor sit amet </p></div>'
            );
            $mform->addGroup($group, 'contentfontstyles', get_string('contentfonts', 'slides'), '', false);

        // Auto text size.
        // $mform->addElement('advcheckbox', 'options[autotextsize]', get_string('autotextsize', 'slides'));

        // Paragraph font size.
            $mform->addElement('text', 'options[fontstyle][contentsize]', get_string('contentfontsize', 'slides'), '16');
            $mform->setType('options[fontstyle][contentsize]', PARAM_INT);
            $mform->addRule('options[fontstyle][contentsize]', get_string('onlynumeric', 'mod_slides'), 'numeric', null, 'client');
            $mform->hideIf('options[fontstyle][contentsize]', 'autotextsize', 'eq', 1);
        }

        $PAGE->requires->js_call_amd('mod_slides/slide_editor', 'loadFontExample', ['demoSelectors' => ['#id_options_fontstyle_contentfont', '#id_options_fontstyle_headingfont'] ]);
    }

    /**
     * Include the listen options.
     *
     * @param int $durationcount Number of listen options need to include in the form.
     *
     * @return void
     */
    protected function include_listen_options(int $durationcount=1) {

        $mform = $this->_form;

        $this->listendurationcounts = $durationcount;

        $mform->addElement('header', 'listenoptions', get_string('listenoptions', 'mod_slides'));

        // Force listen the feedback.
        $options = [
            0 => get_string('none'),
            slideinstance::FORCEAUDIO => get_string('forceaudio', 'mod_slides'),
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
            $mform->addElement('filemanager', $audioname, get_string('listenaudioconfig', 'mod_slides', 1), null, $fileoptions);
            // $mform->hideIf($audioname, 'options[forcelisten]', 'neq', slideinstance::FORCEAUDIO);
        }
    }

    protected function include_autosplit_method() {

        $mform = $this->_form;

        // ...Auto split content and audio section.
        $mform->addElement('header', 'autosplit', get_string('autosplit', 'mod_slides'));

        $mform->addElement('editor', 'dynamiccontent', get_string('dynamiccontent', 'mod_slides'));

        if (!$this->supportaudio) {
            $this->add_split_buttons();
            return;
        }

        // $mform->updateAttributes(['class' => 'mform form-inline']);
        if (autohelper::is_ffmpeg_installed()) {

            $this->ffmpeginstalled = true;
            // ...Audio split content.
            $mform->addElement('filemanager', 'dynamicaudio', get_string('dynamicaudio', 'mod_slides'), null, slides_get_file_options(['audio']));

            // Set config of interval for split.
            $seconds = get_config('slides', 'audiosilenceduration');
            $seconds = $seconds ?: autohelper::SILENCE_TWOSECOND;
            $mform->addElement('html', html_writer::div(html_writer::tag('div', get_string('dynamicaudioinstruction', 'mod_slides', $seconds), ['class' => 'ffmpeg-instruction']), 'nct-config-warning'));

            /* if (!empty($this->_customdata['slideinstanceid'])) {
                $methods = [
                    0 => get_string('autosplitaudio', 'slides'),
                    1 => get_string('splitaudiocustom', 'slides'),
                ];
                $mform->addElement('select', 'audiosplitmethod', get_string('audiosplitmethod', 'mod_slides'), $methods);
                // $this->add_custom_audiotimes();
            } else { */
            $mform->addElement('hidden', 'audiosplitmethod', 0); // get_string('audiosplitmethod', 'mod_slides'), $methods);
            $mform->setType('audiosplitmethod', PARAM_INT);
            // }

        } else {
            // Warning for ffmpeg missing.
            $mform->addElement('html', html_writer::div(html_writer::tag('div', get_string('installffmpeg', 'mod_slides'), ['class' => 'ffmpeg-missing']), 'nct-config-warning'));
        }

        $this->add_split_buttons();
    }

    /**
     * Add the split content buttons group.
     *
     * @param bool $cancel show cancel button
     * @param string $submitlabel null means default, false means none, string is label text
     * @param string $submit2label  null means default, false means none, string is label text
     * @return void
     */
    public function add_split_buttons() {

        $mform = $this->_form;

        $splitbutton = [];

        if (!$this->supportaudio) {

            $splitbutton[] = &$mform->createElement('submit', 'splitcontent', get_string('splitcontent', 'mod_slides'), [
                'data-formatchooser-field' => 'updateButton',
            ]);

        } else {

            if ($this->ffmpeginstalled) {
                $splitbutton[] = &$mform->createElement('submit', 'updatedynamic', get_string('updatedynamic', 'mod_slides'), [
                    'data-formatchooser-field' => 'updateButton',
                ]);
            }

            $splitbutton[] = &$mform->createElement('submit', 'splitcontent', get_string('splitcontent', 'mod_slides'), [
                'data-formatchooser-field' => 'updateButton',
            ]);

            /* $splitbutton[] = &$mform->createElement('button', 'splitpreview', get_string('preview', 'core'), [
                'data-formatchooser-field' => 'updateButton',
            ]); */

            if ($this->ffmpeginstalled && !empty($this->_customdata['slideinstanceid'])) {
                $splitbutton[] = &$mform->createElement('submit', 'splitaudio', get_string('splitaudio', 'mod_slides'), [
                    'data-formatchooser-field' => 'updateButton',
                ]);
            }

        }

        $mform->addGroup($splitbutton, 'splitbuttonar', '', [' '], false);
        $mform->setType('splitbuttonar', PARAM_RAW); // pipeline-ignore: PARAM_RAW — JSON blob decoded immediately after form submit
    }

    /**
     * GEt the google web fonts list from json.
     *
     * @return array
     */
    protected function get_google_webfonts() {

        $cache = cache::make('mod_slides', 'webfonts');

        if ($fonts = $cache->get('fonts')) {
            return $fonts;
        } else {
            $curl = new curl();
            $jsonlist = $curl->get('https://raw.githubusercontent.com/jonathantneal/google-fonts-complete/master/google-fonts.json');
            if (!empty($jsonlist)) {
                $data = json_decode($jsonlist, true);
                $font = array_keys($data);
                $fonts = array_combine($font, $font);
                $cache->set('fonts', $fonts);
            }
        }

        return $fonts ?? [];
    }

    /**
     * Animations list.
     *
     * @return array
     */
    protected function get_animations() {
        // Animation.
        $animations = [
            0 => get_string('none'),
            'fadeIn' => get_string('fadein', 'slides'),
            'fadeInUp' => get_string('fadeinup', 'slides'),
            'fadeInDown' => get_string('fadeindown', 'slides'),
            'fadeInLeft' => get_string('fadeinleft', 'slides'),
            'fadeInRight' => get_string('fadeinright', 'slides'),
            'backInUp' => get_string('backinup', 'slides'),
            'slideInUp' => get_string('slideinup', 'slides'),
            'slideInRight' => get_string('slideinright', 'slides'),
            'slideInLeft' => get_string('slideinleft', 'slides'),
            'slideInDown' => get_string('slideindown', 'slides'),
            'zoomIn' => get_string('zoomin', 'slides'),
            'zoomInUp' => get_string('zoominup', 'slides'),
            'bounceIn' => get_string('bouncein', 'slides'),
            'bounceInDown' => get_string('bounceindown', 'slides'),
            'bounceInLeft' => get_string('bounceinleft', 'slides'),
            'bounceInRight' => get_string('bounceinright', 'slides'),
            'bounceInUp' => get_string('bounceinup', 'slides'),
        ];

        return $animations;
    }

    /**
     *
     *
     * @param [type] $data
     * @return void
     */
    protected function data_postprocessing(&$data) {
        // Save the titles.
        $options = slides_get_editor_options($this->context);

        // Title content.
        $data->title_editor = $data->title;
        $data = file_postupdate_standard_editor(
            $data, 'title', $options, $this->context, 'mod_slides', 'title', $data->slideinstanceid ?: 0
        );

        $data->dynamiccontentformat = $data->dynamiccontent['format'] ?? FORMAT_HTML;
        $data->dynamiccontent = $data->dynamiccontent['text'] ?? '';

        $this->save_filearea($data, 'dynamicaudio', 'mod_slides', 'dynamicaudio');
    }

    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function prepare_additional_file_editors(&$data, array $areas=[]) {

        foreach ($areas as $field => $filearea) {
            $field = is_string($field) ?: $filearea;
            $this->prepare_filearea($data, $field, 'mod_slides', $filearea);
        }

        $this->prepare_filearea($data, 'dynamicaudio', 'mod_slides', 'dynamicaudio');
    }

    /**
     *
     *
     * @param [type] $data
     * @return void
     */
    protected function post_update_additional_file_editors(&$data, array $areas=[]) {

        foreach ($areas as $field => $filearea) {
            $field = is_string($field) ?: $filearea;
            $this->save_filearea($data, $field, 'mod_slides', $filearea);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    protected function data_preprocessing(&$data) {

    }

    /**
     * Prepare the files editors.
     *
     * @param stdClass $data
     * @param slidetype $slidetype
     * @return void
     */
    public function prepare_files_editors(&$data, $slidetype) {

        // Prepare the child fileareas.
        $areas = $slidetype::slide_fileareas_list();
        $this->prepare_additional_file_editors($data, $areas);

        if (!$this->listendurationcounts || empty($data->slideinstanceid)) {
            return false;
        }

        $filearea = $slidetype->shortname . '_listenaudio_';

        $counts = $data->listencounts ?? $this->listendurationcounts;

        for ($i = 1; $i <= $counts; $i++) {
            $audioname = "listenaudio[$i]";
            $itemid = $this->prepare_filearea($data, $audioname, 'mod_slides', $filearea . $i, $data->slideinstanceid);
            $data->listenaudio[$i] = $itemid;
        }

    }

    /**
     * Post update files editors.
     *
     * @param [type] $data
     * @param [type] $slidetype
     * @return void
     */
    public function post_update_files_editors(&$data, $slidetype) {

        // Post udpate the files editors.
        $areas = $slidetype::slide_fileareas_list();
        $this->post_update_additional_file_editors($data, $areas);

        // print_obj($data);exit;
        if (empty($data->listenaudio)) {
            return false;
        }

        $filearea = $slidetype::slide_shortname() . '_listenaudio_';

        foreach ($data->listenaudio as $key => $itemid) {
            // Save draft files to area.
            file_save_draft_area_files(
                $data->listenaudio[$key], $this->context->id, 'mod_slides', $filearea . $key, $data->slideinstanceid
            );
        }


    }

    /**
     * Save area files.
     *
     * @param stdClass $data
     * @param string $field
     * @param string $component
     * @param string $filearea
     *
     * @return void
     */
    protected function save_filearea(stdClass $data, string $field, string $component='mod_slides', string $filearea='', int $itemid=null) {

        // Use the field as filearea.
        $filearea = $filearea ?: $field;

        if (!isset($data->$field)) {
            return false;
        }
        // Save draft files to area.
        file_save_draft_area_files(
            $data->$field, $this->context->id, $component, $filearea, $itemid ?: $data->slideinstanceid
        );
    }

    /**
     * Prepare area files.
     *
     * @param stdClass $data
     * @param string $field
     * @param string $component
     * @param string $filearea
     *
     * @return void
     */
    protected function prepare_filearea(stdClass &$data, string $field, string $component='mod_slides', string $filearea='', int $itemid=null) {

        // Use the field as filearea.
        $filearea = $filearea ?: $field;

        $draftitemid = file_get_submitted_draft_itemid($field);

        $fileitem = $data->slideinstanceid ?? 0;
        $itemid = $itemid ?: $fileitem;

        file_prepare_draft_area(
            $draftitemid, $this->context->id, $component, $filearea, $itemid, slides_get_file_options($this->context)
        );

        $data->$field = $draftitemid;

        return $draftitemid;
    }

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * Do not override this method, override data_postprocessing() instead.
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data) {
            // Trim name for all activity name.
            if (isset($data->name)) {
                $data->name = trim($data->name);
            }

            $this->data_postprocessing($data);
        }
        return $data;
    }

    /**
     * Load in existing data as form defaults. Usually new entry defaults are stored directly in
     * form definition (new entry form); this function is used to load in data where values
     * already exist and data is being edited (edit entry form).
     *
     * @param mixed $defaultvalues object or array of default values
     */
    public function set_data($defaultvalues) {
        if (is_object($defaultvalues)) {
            $defaultvalues = (array)$defaultvalues;
        }

        $this->data_preprocessing($defaultvalues);

        parent::set_data($defaultvalues);
    }
}
