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
 * This file contains the restore code for the feedback_file plugin.
 *
 * @package    slidetype_imagetext
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore subplugin class.
 *
 * Provides the necessary information needed to restore element_h5p subplugin.
 */
class restore_slidetype_imagetext_subplugin extends restore_subplugin {

    protected function define_slides_subplugin_structure() {
        $paths = array();

        $elementname = $this->get_namefor('instance');
        $elementpath = $this->get_pathfor('/slidetype_imagetext');

        $paths[] = new restore_path_element($elementname, $elementpath);
        return $paths;
    }

    public function process_slidetype_imagetext_instance($data) {
        global $DB;

        $data = (object)$data;

        $oldid = $data->id;
        $oldslideinstanceid = $data->slideinstanceid;

        $newid = $DB->insert_record('slidetype_imagetext', $data);

        $this->set_mapping('slidetype_imagetext_'.$oldid, $oldslideinstanceid, $newid);

        // Restore related files.
        $files = mod_slides\helper::backup_get_relatedfiles('imagetext');
        foreach ($files as $filearea) {
            $this->add_related_files('mod_slides', $filearea, 'slideinstance', null, $oldid);
        }
    }
}
