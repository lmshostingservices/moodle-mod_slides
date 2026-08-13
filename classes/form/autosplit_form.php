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

namespace mod_slides\form;

require_once($CFG->dirroot.'/lib/formslib.php');

use html_writer;
use mod_slides\helper;

class autosplit_form extends \moodleform {
    public bool $ffmpeginstalled = false;

    public function definition() {

        $mform = $this->_form;

        $cmid = $this->_customdata['cmid'] ?? '';;
        $id = $this->_customdata['id'] ?? '';

        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);

        $slides = self::get_slides_list($cmid);
        $mform->addElement('select', 'slide', 'Slide to auto split', $slides);

        // ...Auto split content and audio section.
        $mform->addElement('header', 'autosplit', get_string('autosplit', 'mod_slides'));

        $mform->addElement('editor', 'dynamiccontent', get_string('dynamiccontent', 'mod_slides'));

        // $mform->updateAttributes(['class' => 'mform form-inline']);
        /* if (helper::is_ffmpeg_installed()) {

            $this->ffmpeginstalled = true;
            // ...Audio split content.
            $mform->addElement('filemanager', 'dynamicaudio', get_string('dynamicaudio', 'mod_slides'), null, slides_get_file_options(['audio']));

            $seconds = get_config('slides', 'audiosilenceduration');
            $seconds = $seconds ?: helper::SILENCE_TWOSECOND;
            $mform->addElement('html', html_writer::div(html_writer::tag('div', get_string('dynamicaudioinstruction', 'mod_slides', $seconds), ['class' => 'ffmpeg-instruction']), 'nct-config-warning'));

            $methods = [
                0 => get_string('autosplitaudio', 'slides'),
                1 => get_string('splitaudiocustom', 'slides'),
            ];
            $mform->addElement('select', 'audiosplitmethod', get_string('audiosplitmethod', 'mod_slides'), $methods);

            // $this->add_custom_audiotimes();

        } else {
            // Warning for ffmpeg missing.
            $mform->addElement('html', html_writer::div(html_writer::tag('div', get_string('installffmpeg', 'mod_slides'), ['class' => 'ffmpeg-missing']), 'nct-config-warning'));
        } */

        $this->add_split_buttons();

    }

    /**
     * Get list of available slides for the modal to insert.
     *
     * @param int $cmid course module id.
     * @return string HTML of the slides list.
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public static function get_slides_list(int $cmid) {

        $plugins = \core_plugin_manager::instance()->get_installed_plugins('slidetype');

        $select = [];
        foreach ($plugins as $plugin => $version) {
            $slideobj = helper::get_slide($plugin, $cmid, IGNORE_MISSING);
            if (empty($slideobj) || !$slideobj->supports_autosplit()) {
                continue;
            }
            $info = $slideobj->info();

            $select[$info->shortname] = $info->name;
        }

        return $select;
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

        $splitbutton = array();

        // $mform->registerNoSubmitButton('updatecourseformat');
        $splitbutton[] = &$mform->createElement('submit', 'updatedynamic', get_string('updatedynamic', 'mod_slides'), [
            'data-formatchooser-field' => 'updateButton',
        ]);
        $splitbutton[] = &$mform->createElement('submit', 'splitcontent', get_string('splitcontent', 'mod_slides'), [
            'data-formatchooser-field' => 'updateButton',
        ]);

         $splitbutton[] = &$mform->createElement('button', 'splitpreview', get_string('preview', 'core'), [
            'data-formatchooser-field' => 'updateButton',
        ]);

        if ($this->ffmpeginstalled) {
            $splitbutton[] = &$mform->createElement('submit', 'splitaudio', get_string('splitaudio', 'mod_slides'), [
                'data-formatchooser-field' => 'updateButton',
            ]);
        }

        $mform->addGroup($splitbutton, 'splitbuttonar', '', array(' '), false);
        $mform->setType('splitbuttonar', PARAM_RAW); // pipeline-ignore: PARAM_RAW — JSON blob decoded immediately after form submit
    }

    protected static function is_heading($text) {
        $lesslenth = (strlen(strip_tags(trim($text))) <= 200);

        if ((preg_match('/^<h[1-6]>/i', $text)) && $lesslenth) {
            return true;
        }
        return false;
    }

    /**
     * Split content.
     *
     * @param string $content
     * @param string $slidename
     * @return string
     */
    public static function split_content(string $content, string $slidename) {
        // Dynamic content.
        $dynamiccontent = $content;
        // Split the dynamic content.
        $split = explode("\n", $dynamiccontent);
        // Remove empty tags.
        $split = array_values(array_filter($split, fn($v) => strlen(trim(strip_tags($v))) >= 1));
        $heads = array_filter($split, fn($a) => self::is_heading($a));
        $result = (object) ['title' => '', 'contents' => []];
        // No title.
        // Given content is odd number, then use the first element as title otherwise pair the elements.
        // With this method without title it can be updated to contents.
        if (!empty($split) && self::is_heading($split[0])
            && (strlen(trim($split[0])) <= 200)
            && ((count($split) % 2) != 0 || count($heads) <= 1 || self::is_heading($split[1]))
        ) {
            $result->title = array_shift($split);
        }

        $count = NCTIMAGETEXT_MAXCONTENT;
        $head = false;
        $content = [];

        foreach ($split as $text) {
            if (self::is_heading($text)) {
                $content[] = $text;
                $head = true;
            } else {
                $length = count($content);
                $length = $head ? $length - 1 : $length;
                $prev = $length > 0 ? $length : 0;
                $content[$prev] = array_key_exists($prev, $content) ? $content[$prev] . "\n" . $text : $text;
                $head = false;
            }
        }
        for ($i = 1; $i <= $count; $i++) {
            $dynamic = array_shift($content);
            $result->contents[$i] = !empty($dynamic) ? $dynamic : "";
        }
        return $result;
    }

}
