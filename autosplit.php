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
 * mod_slides file.
 *
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

// require_once();

$id = required_param('id', PARAM_INT);

$url = new moodle_url('/mod/slides/autosplit.php', ['id' => $id]);
$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('slides', $id)) {
    // NOTE this is invalid use of print_error, must be a lang string id.
    throw new moodle_exception('invalidcoursemodule', 'slides');
}

$PAGE->set_url('/mod/slides/autosplit.php', array('id' => $cm->id, 'sesskey' => sesskey()));

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
$PAGE->add_body_class('slide-autosplit');


$splitform = new mod_slides\form\autosplit_form(null, [
    'cmid' => $cm->id,
    'id' => $id,
]);

$PAGE->requires->js_call_amd('mod_slides/autosplit', 'init', ['id' => $id, 'contextid' => $context->id]);

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_slides/autosplit', ['form' => $splitform->render()]);

echo $OUTPUT->footer();
