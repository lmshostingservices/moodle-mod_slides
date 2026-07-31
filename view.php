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
 * Slides module content view page.
 *
 * @package    mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT);    // Course Module ID.

if (!$cm = get_coursemodule_from_id('slides', $id)) {
    // NOTE this is invalid use of print_error, must be a lang string id.
    throw new moodle_exception('invalidcoursemodule', 'slides');
}

$PAGE->set_url('/mod/slides/view.php', array('id' => $cm->id));

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    // Thorw error if the given moulde coure is exists.
    throw new moodle_exception('invalidcourse', 'core');
}
require_course_login($course, false, $cm);

if (!$data = $DB->get_record('slides', array('id' => $cm->instance))) {
    throw new moodle_exception('moduleinstancemissing', 'slides');
}

$context = context_module::instance($cm->id);

require_capability('mod/slides:view', $context);


$PAGE->set_title($course->shortname.': '.$data->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($data);
$PAGE->add_body_class('limitedwidth');

// Hide the page content until process the images and styles for the slides, and make the slide to top.
// $PAGE->add_body_class('nctslidesview-onloading');
// Add animation of given slides.
$PAGE->requires->css('/mod/slides/animate.css');

// Completion and trigger events.
slides_view($data, $course, $cm, $context);

echo $OUTPUT->header();

$render = $PAGE->get_renderer('core');

// mod_slides\helper::get_next_slide(13);

// Render the page view of the slides.
$slideswidget = new mod_slides\widget($cm, $course);

echo $render->render($slideswidget);
$slideswidget->initiate_js();

echo $OUTPUT->footer();
