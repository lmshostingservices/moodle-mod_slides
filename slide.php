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
 * Slides add / edit instance form.
 *
 * @package    mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @author     LMSACE Dev Team <lmsace.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot."/mod/slides/lib.php");

$id = optional_param('id', 0, PARAM_INT); // Slide instance id.
$cmid = required_param('cmid', PARAM_INT); // Course module id.

// Looking for the action to perform for this instance, by default this is edit.
$action = optional_param('action', '', PARAM_ALPHA);

if (!empty($id)) {

    // Verify the existence fo the isntance.
    $sliderecord = $DB->get_record("slides_slide", array('id' => $id));
    if (!$sliderecord) {
        throw new moodle_exception('invaildrecord', 'mod_slides');
    }
    // Type of the slide.
    $slidetype = $sliderecord->slidetype;

} else {

    $slidetype = required_param('slidetype', PARAM_ALPHANUM);
}

// Check slide exist or not.
$slides = slides_get_slidetype_pluginnames();
if (!in_array($slidetype, $slides)) {
    throw new moodle_exception('invaildslide', 'mod_slides');
}

list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'slides');
$context = context_module::instance($cm->id);

$slidetypeobj = mod_slides\slide_editor::get_slide($slidetype, $cmid);

require_login($course, true, $cm);

require_sesskey();

require_capability('mod/slides:viewslideeditor', $context);

if ($action == 'duplicate') {
    $result = $slidetypeobj->duplicate_slide($id, [
        'slidetype' => $slidetype,
        'context' => $context,
        'slideinstanceid' => $id,
        'cmid' => $cmid,
    ]);
    redirect(new moodle_url('/mod/slides/editor.php', array('id' => $cmid, 'sesskey' => sesskey())));
    exit;
}

$record = new stdClass();
$record->course = $course->id;
$record->cmid = $cmid;
$record->slidetype = $slidetype;
$record->slidesid = $cm->instance;

$urlparams = array(
    'id' => $id,
    'action' => $action,
    'cmid' => $cmid,
    'slidetype' => $slidetype,
    'sesskey' => sesskey()
);
$url = new moodle_url('/mod/slides/slide.php', $urlparams);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($course->shortname.': '.get_string('createnewslide', 'slides'));

/* $mform = new \mod_slides\form\general_slide_form($PAGE->url->out(false), [
    'slidetype' => $slidetype,
    'context' => $context,
    'slideinstanceid' => $id,
    'cmid' => $cmid,
]);
 */

$mform = $slidetypeobj->slide_form($PAGE->url->out(false), [
    'slidetype' => $slidetype,
    'context' => $context,
    'slideinstanceid' => $id,
    'cmid' => $cmid,
]);


if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/slides/editor.php', ['id' => $cmid, 'sesskey' => sesskey()]));
} else if ($formdata = $mform->get_data()) {

    $formdata->course = $course->id;
    $formdata->cmid = $cm->id;
    $formdata->slidetype = $slidetype;
    $formdata->contextid = $context->id;
    $formdata->instanceid = isset($sliderecord) ? $sliderecord->id : 0;
    $formdata->slidesid = $cm->instance;
    $formdata->slidetypeid = 1;

    $splitscount = 1;
    if ($slidetypeobj->supports_autosplit()) {
        $splits = $slidetypeobj->split_dynamic_content($formdata, $mform);
    } else {
        // $slidetypeobj->update_split_content($formdata, $splits[$i] ?? []);
        $slidetypeobj->update_slide($formdata);
        $mform->post_update_files_editors($formdata, $slidetypeobj);
    }

    $editorurl = new moodle_url('/mod/slides/editor.php', ['id' => $cmid, 'sesskey' => sesskey()]);
    redirect($editorurl, get_string('savechanges'), null, \core\output\notification::NOTIFY_INFO);
}

$data = (object) $slidetypeobj->prepare_formdata($id);
$mform->prepare_files_editors($data, $slidetypeobj);

$mform->set_data($data);
// PAGE header.
echo $OUTPUT->header();
// Render and Display the add elemet instance form contents.
echo $mform->display();
// Page footer.
echo $OUTPUT->footer();
