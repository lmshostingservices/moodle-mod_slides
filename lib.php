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
 * Define for lib functions.
 *
 * @package    mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @author     LMSACE Dev Team <lmsace.com>
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

/**
 * Add slides instance.
 * @param stdClass $data
 * @param mod_slides_mod_form $mform
 * @return int instance id
 */
function slides_add_instance($data, $mform = null) {
    global $CFG, $DB, $OUTPUT;

    // Process the data before insert to table.
    slides_process_pre_save($data);
    $moduleid = $DB->insert_record('slides', $data);

    // Expected completion time.
    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;

    // Update the completion event time in calender.
    \core_completion\api::update_completion_date_event($data->coursemodule, 'slides', $moduleid, $completiontimeexpected);

    return $moduleid;
}


/**
 * Runs any processes that must run before a slides insert/update
 *
 * @param object $data content form data
 * @return void
 **/
function slides_process_pre_save(&$data) {
    // Whether id exist or not.
    if (!isset($data->id)) {
        $data->timecreated = time();
    }
    $data->timemodified = time();
}

/**
 * Update page instance.
 *
 * @param stdClass $data
 * @param mod_slides_mod_form $mform
 * @return bool true
 */
function slides_update_instance($data, $mform) {
    global $CFG, $DB;

    $data->id = $data->instance;

    // Process the submitted data before update to db.
    slides_process_pre_save($data);

    // Expected completion time.
    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;

    // Update the completion event time in calender.
    \core_completion\api::update_completion_date_event($data->coursemodule, 'slides', $data->id, $completiontimeexpected);

    // Update the slide updated data.
    $DB->update_record('slides', $data);

    return true;
}

/**
 * Delete page instance.
 * @param int $id
 * @return bool true
 */
function slides_delete_instance($id) {
    global $CFG, $DB;

    // Slides module instance not found stop here.
    if (!$record = $DB->get_record('slides', array('id' => $id))) {
        return false;
    }

    // Get the slide course module instance.
    $cm = get_coursemodule_from_instance('slides', $id);

    // Remove the completion date from the calender api.
    \core_completion\api::update_completion_date_event($cm->id, 'slides', $id, null);

    // Delete the instance.
    $DB->delete_records('slides', array('id' => $record->id));

    // Collect all slide instance IDs for this activity.
    $slideinstanceids = $DB->get_fieldset_select('slides_slide', 'id', 'slidesid = ?', [$id]);

    if (!empty($slideinstanceids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($slideinstanceids);

        // Delete slide options for all slides in this activity.
        $DB->delete_records_select('slides_options', "slideinstanceid $insql", $inparams);

        // Delete per-slide user completion records.
        $DB->delete_records_select('slides_slide_completion', "slideinstanceid $insql", $inparams);

        // Delete slide type sub-tables for all known slide types.
        $slidetypes = ['flip', 'imagetext', 'matching', 'summary'];
        foreach ($slidetypes as $type) {
            $table = 'slidetype_' . $type;
            if ($DB->get_manager()->table_exists($table)) {
                $DB->delete_records_select($table, "slideinstanceid $insql", $inparams);
            }
        }

        // Delete all slides_slide rows for this activity.
        $DB->delete_records('slides_slide', ['slidesid' => $id]);
    }

    // Delete overall activity completion records.
    $DB->delete_records('slides_completion', ['slidesid' => $id]);

    // Delete all stored files for this activity.
    $fs = get_file_storage();
    $context = context_module::instance($cm->id);
    $fs->delete_area_files($context->id);

    return true;
}

/**
 * List of features supported in slides module
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function slides_supports($feature) {

    // Add for FEATURE_MOD_PURPOSE.
    if (defined('FEATURE_MOD_PURPOSE') && $feature === FEATURE_MOD_PURPOSE) {
        return MOD_PURPOSE_CONTENT;
    }

    switch($feature) {
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}


/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $data       data object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function slides_view($data, $course, $cm, $context) {

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $data->id
    );

    $event = \mod_slides\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('slides', $data);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}


/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param object $event
 * @param object $factory
 * @param int $userid
 * @return object|null
 */
function mod_slides_core_calendar_provide_event_action($event, $factory, $userid = 0) {

    global $USER;
    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['slides'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/slides/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function slides_reset_userdata($data) {
    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function slides_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function slides_get_post_actions() {
    return array('update', 'add');
}



/**
 * Get the subplugins slidetype plugins for slides.
 *
 * @return array slides
 */
function slides_get_slidetype_pluginnames() {
    $plugins = \core_plugin_manager::instance()->get_plugins_of_type('slidetype');
    return array_keys($plugins);
}

/**
 * Fragment output to load the list of slides to insert.
 *
 * @param array $args Context and cmid.
 * @return string
 */
function slides_output_fragment_get_slides_list($args) {
    if ($args['cmid']) {
        return \mod_slides\helper::get_slides_list($args['cmid']);
    }
    throw new moodle_exception('invalidcoursemodule', 'slides');
}

/**
 * Prepare the next available chapters to users view after the chapter completed.
 *
 * @param array $args
 * @return void
 */
function mod_slides_output_fragment_load_next_slide($args) {
    global $PAGE;

    list ($course, $cm) = get_course_and_cm_from_cmid($args['cmid'], 'slides');
    $currentslideinstanceid = $args['currentslide'];

    // Render the page view of the slides.
    $nextslide = mod_slides\helper::get_next_slide($currentslideinstanceid);
    $slideswidget = new mod_slides\widget($cm, $course);
    $result = $slideswidget->render_slides([$nextslide]);
    $js = $slideswidget->jsdata;

    $PAGE->requires->data_for_js('NextSlideData', $js);

    return current($result);
}

/**
 * Load the next item for the slide.
 *
 * @param [type] $args
 * @return void
 */
function mod_slides_output_fragment_load_next_listeitem($args) {
    global $PAGE, $DB;

    list ($course, $cm) = get_course_and_cm_from_cmid($args['cmid'], 'slides');
    $slideinstanceid = $args['slideinstanceid'];
    $index = $args['index'];

    // Render the page view of the slides.
    // $nextslide = mod_slides\helper::get_slide_from_instance($slideinstanceid, $cm->id);
    $slide = $DB->get_record('slides_slide', ['id' => $slideinstanceid]);
    $slideswidget = new mod_slides\widget($cm, $course);
    $result = $slideswidget->render_slides([$slide], true);
    $js = $slideswidget->jsdata;

    $PAGE->requires->data_for_js('CurrentSlideData', $js);

    return current($result);
}

/**
 * Fragment output to load the list of slides to insert.
 *
 * @param array $args Context and cmid.
 * @return string
 */
function slides_output_fragment_delete_slide($args) {
    global $DB;

    $slideshortname = $args['slideshortname'];
    $slideinstanceid = $args['slideinstanceid'];
    $cmid = $args['cmid'];

    $slideobj = \mod_slides\slide_editor::get_slide($slideshortname, $cmid);
    if ($args['action'] == 'delete') {
        return ($slideobj->delete_slide($slideinstanceid)) ? "" : false;
    }
}

/**
 * Fragment output to load the list of elements to insert.
 *
 * @param array $args Context and cmid.
 * @return string
 */
function slides_output_fragment_update_visibility($args) {
    if (isset($args['context']) && !empty($args['cmid'])) {

        $slideshortname = $args['slideshortname'];
        $slideinstanceid = $args['slideinstanceid'];

        $slideobj = \mod_slides\slide_editor::get_slide($slideshortname, $args['cmid']);
        $slideobj->update_visibility($args['slideinstanceid'], $args['status']);
    }
}
/**
 * Update the slides order.
 *
 * @param array $args
 * @return void
 */
function slides_output_fragment_update_slides_order($args) {
    global $DB;

    if (isset($args['context']) && !empty($args['cmid'])) {
        $slides = $args['slides'];
        $slides = explode(',', $slides);
        foreach ($slides as $order => $slideid) {
            $order++;
            // echo $order;
            $DB->set_field('slides_slide', 'sortorder', $order, ['id' => $slideid]);
        }
    }
}

/**
 * Split content.
 *
 * @param array $args
 *
 * @return string
 */
function slides_output_fragment_split_content($args) {
    global $CFG;
    // require_once($CFG->dirroot . '/mod/slides/mod_form.php');

    $context = $args['context'];
    if ($context->contextlevel !== CONTEXT_MODULE || empty($args['content'])) {
        return false;
    }
    $result = mod_slides\form\autosplit_form::split_content($args['content'], $args['slidename']);

    return json_encode($result);
}


/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $node The node to add module settings to
 */
function slides_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $PAGE;
    if (has_capability('mod/slides:viewslideeditor', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/slides/editor.php', array('id' => $PAGE->cm->id, 'sesskey' => sesskey()));
        $node->add(
            get_string('slideseditor', 'mod_slides'), $url, navigation_node::TYPE_SETTING, null, 'editorslide', null
        );
    }
}


/**
 * Serves file from image.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context Context used in the file.
 * @param string $filearea Filearea the file stored
 * @param array $args Arguments
 * @param bool $forcedownload Force download the file instead of display.
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function slides_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB;

    require_login();

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // Merge sub slides area files.
    $areas = \mod_slides\slide_editor::get_slides_areafiles($context->instanceid);

    // $areas =
    $list = [];
    foreach ($areas as $area) {
        $list = array_merge($list, $area);
    }

    if (!in_array($filearea, $list)) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_slides', $filearea, $args[0], '/', $args[1]);
    if (!$file) {
        return false;
    }
    send_stored_file($file, 0, 0, 0, $options);
}


/**
 * Geneate the course module information data for the slides module.
 *
 * @param object $coursemodule
 * @return void
 */
function slides_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionendreach, autotextsize, timecreated, timemodified';
    if (!$slides = $DB->get_record('slides', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $slides->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('slides', $slides, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionendreach'] = $slides->completionendreach;
        $result->customdata['autotextsize'] = $slides->autotextsize;
    }
    return $result;
}


/**
 * Editor options.
 *
 * @param [type] $context
 * @param [type] $files
 * @return array
 */
function slides_get_editor_options($context, $files=null) {
    global $CFG;
    return array(
        'subdirs' => 1,
        'maxbytes' => $CFG->maxbytes,
        'maxfiles' => $files === null ? -1 : $files,
        'changeformat' => 1,
        'context' => $context,
        'noclean' => 1,
        'trusttext' => 0,
    );
}

/**
 * File options.
 *
 * @param array $type
 * @return array
 */
function slides_get_file_options($type=["web_image"]) {

    return [
        'subdirs' => true,
        'maxfiles' => 1,
        'maxbytes' => 0,
        'accepted_types' => $type,
    ];
}

