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

namespace mod_slides;

use moodle_url;
use renderer_base;

class widget implements \templatable, \renderable {
    /**
     * Coursemodule instance
     *
     * @var cminfo
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    protected $cm;

    /**
     * Course record object
     *
     * @var stdclass
     */
    protected $course;

    /**
     * Course module context object.
     *
     * @var context_module
     */
    protected $cmcontext;

    /**
     * Instance data of module slides.
     *
     * @var [type]
     */
    protected $instance;

    protected $slidesid;

    protected $jsdata;

    /**
     * Constructor, setup the class variables and course module objects.
     *
     * @param stdclass $cm Course Moudle instance record.
     * @param stdclass $course Course record object.
     */
    public function __construct($cm, $course) {
        global $DB;

        $this->cm = $cm;
        $this->course = $course;
        $this->cmcontext = \context_module::instance($cm->id);

        $this->slidesid = $cm->instance;

        // Slides module instance data.
        $this->instance = $DB->get_record('slides', ['id' => $this->slidesid], "*", MUST_EXIST);

        $this->jsdata = [];
    }

    public function initiate_js() {
        global $PAGE;

        $PAGE->requires->data_for_js('slideOptions', $this->jsdata);

        $PAGE->requires->js_call_amd('mod_slides/nctslides', 'init', [
            'contextid' => $this->cmcontext->id, 'slideid' => $this->cm->instance, 'cmid' => $this->cm->id
        ]);
    }

    public function __get($property) {
        return property_exists($this, $property) ? $this->$property : false;
    }

    /**
     * Returns the given slides class instance object.
     *
     * @param int|string $slide
     * @param int|null $cmid
     * @return \slides
     */
    public static function get_slide($slide, $cmid=null, $exist=MUST_EXIST) {
        global $DB;
        if (is_number($slide)) {
            $slide = $DB->get_field('slides_slides', 'shortname', ['id' => $slide]);
        }
        $class = 'slidetype_'.$slide.'\slide';
        if (class_exists($class)) {
            return new $class($cmid);
        } else if ($exist == MUST_EXIST) {
            throw new \moodle_exception("slide $slide - notfound", 'mod_slides');
        }
    }


    protected function get_list_of_slides() : array {
        global $DB;

        $sql = "
            SELECT *, sl.id as id
            FROM {slides_slide} sl
            JOIN {slides_options} so ON so.slideinstanceid = sl.id
            WHERE sl.slidesid = :cminstanceid ORDER BY sl.sortorder ASC";

        $slidelist = $DB->get_records_sql($sql, ['cminstanceid' => $this->cm->instance]);

        return $slidelist;
    }

    /**
     * Generate the styles for the container, this styles are added as inline style.
     *
     * * Generate the container height.
     *
     * @return string
     */
    protected function generate_basic_container_styles() : string {

        $style = [];

        if ($this->instance->containerheight) {
            $style[] = 'height:' . $this->instance->containerheight . 'px;';
        }

        return implode('', $style);
    }

    protected function get_slides_instance_slide_list() : array {
        global $DB, $PAGE;

        $jsdata = [];

        // Get all the slide created for this slides module.
        $slideinstances = $DB->get_records('slides_slide', [
            'slidesid' => $this->cm->instance, 'status' => slideinstance::STATUS_ENABLE], 'sortorder ASC');

        if (empty($slideinstances)) {
            return [];
        }

        return $slideinstances;

    }

    public function render_slides($slideinstances, $force=false) {
        global $DB, $PAGE;

        if (empty($slideinstances)) {
            return [];
        }
        // Render the core.
        $renderer = $PAGE->get_renderer('core');

        $rendered = [];
        // Convert the record to instance object for all the slide instance.
        foreach($slideinstances as $key => $slide) {
            $type = $slide->slidetype;
            $slidetype = self::get_slide($type, $this->cm->id);
            $slideinstance = $slidetype->get_instance($slide->id);
            $slideinstance->set_slidesmoddata($this->instance);
            $slideinstance->set_force($force);
            $slide = $renderer->render($slideinstance);
            // Fetch js data after the render this will allow slides to copy the options export for the template to use as js data.
            $jsdata[$slideinstance->slideinstanceid] = $slideinstance->join_js_data();
            $jsdata[$slideinstance->slideinstanceid]->customslidemodule = $slidetype->includes_custom_jsmodule();

            $rendered[$key] = $slide;
            // TODO: Don't rmeove this.
            if (isset($jsdata[$slideinstance->slideinstanceid]->completed) && $jsdata[$slideinstance->slideinstanceid]->completed == false) {
                break;
            }
        }

        // Use the slides js data for the entire data.
        $this->jsdata += $jsdata;

        return $rendered;
    }

    /**
     * Generate the data for render.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $PAGE;

        $slides = $this->get_slides_instance_slide_list();
        $widgets = $this->render_slides($slides);

        $pagination = $slides;
        $completed = array_keys($widgets);
        $i = 0;
        array_walk($pagination, function (&$slide) use ($completed, &$i) {
            $slide->viewitem = in_array($slide->id, $completed) ? true : false;
            $slide->slideto = $i;
            $i++;
            $slide->slideno = $i;
        });

        $data = [
            'slidecontainerstyles' => $this->generate_basic_container_styles(),
            'slides' => array_values($widgets),
            'pagination' => array_values($pagination),
            'notnav' => count($pagination) <= 1 ? true : false,
            'viewoption' => has_capability('mod/slides:addinstance', $this->cmcontext),
            'containerheight' => $this->instance->containerheight ?: '',
        ];

        $data['noslide'] = count($data['slides']) >= 1 ? false : true;

        $this->jsdata['general'] = [
            'slidesid' => $this->cm->instance,
            'autotextsize' => (bool) $this->instance->autotextsize,
            'slidescount' => count($slides),
            'containerheight' => $this->instance->containerheight ?: '',
        ];


        $render = $PAGE->get_renderer('mod_slides');
        $data['nextmoduleurl'] = $render->activity_navigation()->nextlink->url ?? new moodle_url('/course/view.php', ['id' => $this->course->id]);

        return $data;
    }
}
