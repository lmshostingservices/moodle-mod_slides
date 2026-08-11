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
 * Slide_editor page helps to  manage slide.
 *
 * @package    mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_slides;

use html_writer;
use moodle_url;

/**
 * Mod contnet designer slide_editor class.
 */
class slide_editor extends widget {
    protected function generate_slidelist_editorinfo() : array {

        // Get the list of slide instances added for this module slides instance.
        $slideslist = $this->get_list_of_slides();

        // Empty list of slide instance for this slides.
        if (empty($slideslist)) {
            return [];
        }

        $slides = [];

        // print_obj($slideslist);
        // exit;

        foreach ($slideslist as $slideinstanceid => $slide) {

            // Form the slide class based on our convencien.
            $slideclass = helper::get_slideclass($slide->slidetype);

            // Verify the slide is instance of slidetype.
            if (!is_subclass_of($slideclass, "mod_slides\slidetype")) {
                continue;
            }

            // Create a slide instance.
            // $slideinstance = $slideclass::instance($slide, $this->cm, $this->cmcontext);

            // Generates the basic data for editor.
            $slidedata = (object) [
                'slideinstanceid' => $slideinstanceid,
                'name' => $slide->name,
                'info' => $slideclass::info(),
                'data' => $slide,
                'editurl' => new moodle_url('/mod/slides/slide.php', [
                    'id' => $slideinstanceid, 'sesskey' => sesskey(), 'cmid' => $this->cm->id
                ]),
                'duplicateurl' => new moodle_url('/mod/slides/slide.php', [
                    'id' => $slideinstanceid, 'sesskey' => sesskey(), 'cmid' => $this->cm->id, 'action' => 'duplicate'
                ]),
            ];

            $slides[] = $slidedata; // Store the slide instance data.
        }

        // print_obj($slides);
// exit;

        return $slides;
    }

    public function export_for_template($render) : array {
        global $PAGE;

        $slides = $this->generate_slidelist_editorinfo();

        $slidesdata = [
            'slides' => $slides,
            'cmid' => $this->cm->id,
        ];

        // print_object($slidesdata);exit;
        // Initiate the js for handle the add slide, edit and delete slide on the editor page.
        $this->init_data_forjs(); // Data of the current module instance for JS.
        $PAGE->requires->js_call_amd('mod_slides/slide_editor', 'init', [$this->cmcontext->id, $this->cm->id]);

        return $slidesdata;
    }

    /**
     * Send the course module details as hidden input, data will fetched in the Slide.js file to prevent the global value issue.
     *
     * @return string
     */
    public function cm_details() {
        $data = [
            'cmid' => $this->cm->id,
            'contextid' => \context_module::instance($this->cm->id)->id,
            'slidesid' => $this->cm->instance
        ];
        return html_writer::empty_tag('input', [
            'type' => 'hidden', 'name' => 'slides_cm_details', 'value' => json_encode($data)
        ]);
    }

    /**
     * Generate the course and cm data used in the JS.
     *
     * @return void
     */
    public function init_data_forjs() {
        global $PAGE;
        $data = ['cm' => $this->cm, 'course' => $this->course, 'contextid' => \context_module::instance($this->cm->id)->id];
        $PAGE->requires->data_for_js('slides', $data);
    }

    /**
     * Initialize the javascript modules from the available slides.
     *
     * Note: if you want to add js for each instance then insert your module call on render function insteed of here.
     *
     * @return void
     */
    public function initiate_js() {
        global $PAGE;
        $plugins = \core_plugin_manager::instance()->get_installed_plugins('slide');
        foreach ($plugins as $plugin => $version) {
            $slideobj = self::get_slide($plugin, $this->cm->id);
            $slideobj->initiate_js();
        }
    }

    /**
     * Create the instance of the slide_editor class.
     *
     * @param int $cmid Course Module id.
     * @return slide_editor Mod_contentdeisnger/slide_editor class instance.
     */
    public static function get_slide_editor($cmid) {
        list($course, $cm) = get_course_and_cm_from_cmid($cmid);
        return new self($cm, $course);
    }

    /**
     * Get list of available slides for the modal to insert.
     *
     * @param int $cmid course module id.
     * @return string HTML of the slides list.
     */
    public static function get_slides_list(int $cmid) {

        $plugins = \core_plugin_manager::instance()->get_installed_plugins('slidetype');

        $li = [];
        foreach ($plugins as $plugin => $version) {
            $slideobj = self::get_slide($plugin, $cmid);
            if (!$slideobj->supports_multiple_instance()) {
                continue;
            }
            $info = $slideobj->info();
            $description = html_writer::span($info->description, 'slide-description');
            $name = html_writer::span($info->name, 'slide-name');

            $li[] = html_writer::tag('li',
                $info->icon . $name . $description,
                ['data-slide' => $info->shortname, 'class' => 'slide-item']
            );
        }

        return html_writer::tag('ul', implode('', $li), ['class' => 'slides-list']);
    }


    /**
     * Fetch list of installed slides.
     *
     * @return array List of slides.
     */
    public static function get_slides() {
        $plugins = \core_plugin_manager::instance()->get_installed_plugins('slidetype');
        return $plugins;
    }


    public static function get_slides_areafiles($cmid=0) {
        $plugins = self::get_slides();

        $files = [];
        foreach ($plugins as $plugin => $version) {
            $slideobj = helper::get_slideclass($plugin);
            $areafiles = (class_exists($slideobj) && method_exists($slideobj, 'backup_files')) ? $slideobj::backup_files() : [];
            $files[$plugin] = $areafiles;
        }

        return $files;
    }

    /**
     * Fetch the genernal options as records.
     *
     * @param int $instanceid Slide instance id
     * @param int $slideid Slide list id.
     * @return void
     */
    public function get_option($instanceid, $slideid) {
        global $DB;
        $record = $DB->get_record('slides_options', ['instance' => $instanceid, 'slide' => $slideid]);
        /* if (!empty($record)) {
            $slide = self::get_slide($record->slide, $this->cm->id);

        } */
        return $record;
    }

}
