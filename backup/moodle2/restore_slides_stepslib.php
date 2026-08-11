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
 * Definition restore structure steps.
 *
 * @package   mod_slides
 * @copyright 2022, bdecent gmbh bdecent.de
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_slides_activity_task
 */

/**
 * Structure step to restore slides activity.
 */
class restore_slides_activity_structure_step extends restore_activity_structure_step {
    /**
     * Restore steps structure definition.
     */
    protected function define_structure() {
        $paths = array();

        $userinfo = $this->get_setting_value('userinfo');

        // Restore path.
        $element = new restore_path_element('slides', '/activity/slides');
        $paths[] = $element;

        // Restore elements.
        // $elements = new restore_path_element('slides_type',
        //     '/activity/slides/slidetype/slides_type');
        // $paths[] = $elements;

        if ($userinfo) {
            // Per-slide completion (slides_slide_completion table).
            $paths[] = new restore_path_element('slides_slide_completion',
                '/activity/slides/slideviews/slides_slide_completion');
            // Overall activity completion (slides_completion table).
            $paths[] = new restore_path_element('slides_completion',
                '/activity/slides/slidescompletion/slides_completion');
        }

        $paths[] = new restore_path_element('slides_slide',
        '/activity/slides/slide/slides_slide');

        $this->add_subplugin_structure('slidetype', $element);

        // Restor general options of element instance.
        $paths[] = new restore_path_element('slides_options',
            '/activity/slides/slidesopitons/slides_options');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process activity slides restore.
     * @param mixed $data restore slides table data.
     */
    protected function process_slides($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        // Insert instance into Database.
        $newitemid = $DB->insert_record('slides', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process slides users records.
     *
     * @param object $data The data in object form
     * @return void
     */
    /* protected function process_slides_type($data) {
        global $DB;

        $data = (object) $data;

        // If already element inserted during the plugin installation then use the current element id.
        $elementid = $DB->get_field('slides_type', 'id', ['shortname' => $data->shortname]);
        if (!$elementid) {
            // If not inserted then create new one.
            $elementid = $DB->insert_record('slides_type', $data);
        }
        $this->set_mapping('slidetype', $data->shortname, $data->shortname);
    } */

    /**
     * Process slides users records.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_slides_slide($data) {
        global $DB;

        $data = (object) $data;
        $oldslideid = $data->id;

        $data->slidesid = $this->get_new_parentid('slides');
        $data->timemodified = time();

        $newslideid = $DB->insert_record('slides_slide', $data);
        $this->set_mapping('slideinstance', $oldslideid, $newslideid);

        // Table name.
        $tablename = 'slidetype_' . $data->slidetype;

        // Parmas.
        $param['restoreid'] = $this->get_restoreid();
        $param['itemname'] = $tablename . "_%";
        $param['itemid'] = $oldslideid;

        $like = $DB->sql_like('itemname', ':itemname');
        $lists = $DB->get_records_select('backup_ids_temp', "{$like} AND itemid=:itemid AND backupid=:restoreid", $param);

        foreach ($lists as $list) {
            $DB->set_field($tablename, 'slideinstanceid', $newslideid, ['id' => $list->newitemid]);
        }

    }

    /**
     * Process slides general options records.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_slides_options($data) {
        global $DB;

        $data = (object) $data;

        $data->slidesid = $this->get_new_parentid('slides');
        $data->slideinstanceid = $this->get_mappingid('slideinstance', $data->slideinstanceid);
        $data->timemodified = time();

        // Insert the general options for the element instance.
        $DB->insert_record('slides_options', $data);
    }

    /**
     * Process slides users completions.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_slides_slide_completion($data) {
        global $DB;

        $data = (object) $data;

        $data->slideinstanceid = $this->get_mappingid('slideinstance', $data->slideinstanceid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('slides_slide_completion', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder).
    }

    /**
     * Update the files of editors after restore execution.
     *
     * @return void
     */
    /**
     * Process overall activity completion records (slides_completion table).
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_slides_completion($data) {
        global $DB;

        $data = (object) $data;
        $data->slidesid = $this->get_new_parentid('slides');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('slides_completion', $data);
    }

    /**
     * Update the files of editors after restore execution.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_slides', 'intro', null);
    }
}
