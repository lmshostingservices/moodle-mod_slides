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
 * Slides slide add / edit instance form.
 *
 * @package    mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @author     LMSACE Dev Team <lmsace.com>
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/slides/lib.php');

// Course Module ID.
$id = required_param('id', PARAM_INT);


if (!$cm = get_coursemodule_from_id('slides', $id)) {
    // NOTE this is invalid use of print_error, must be a lang string id.
    throw new moodle_exception('invalidcoursemodule', 'slides');
}

$PAGE->set_url('/mod/slides/editor.php', array('id' => $cm->id, 'sesskey' => sesskey()));

if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
    throw new moodle_exception('invalidcourse', 'core');  // NOTE As above.
}
require_course_login($course, false, $cm);

if (!$data = $DB->get_record('slides', array('id' => $cm->instance))) {
    throw new moodle_exception('moduleinstancemissing', 'slides');
}
$context = context_module::instance($cm->id);

require_sesskey();

require_capability('mod/slides:viewslideeditor', $context);

$PAGE->set_title($course->shortname.': '.$data->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($data);
$PAGE->add_body_class('limitedwidth');

echo $OUTPUT->header();

$editor = new \mod_slides\slide_editor($cm, $course);

$render = $PAGE->get_renderer('core');

echo $render->render($editor);

echo $OUTPUT->footer();
