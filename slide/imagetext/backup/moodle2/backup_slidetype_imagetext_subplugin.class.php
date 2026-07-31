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
 * This file contains the backup code for the slidetype_imagetext plugin.
 *
 * @package    slidetype_imagetext
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Provides the information to backup feedback files.
 *
 * This just adds its filearea to the annotations and records the number of files.
 */
class backup_slidetype_imagetext_subplugin extends backup_subplugin {

    /**
     * Returns the subplugin information to attach to slidetype imagetext.
     * @return backup_subplugin_element
     */
    protected function define_slides_subplugin_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $imagetext = new backup_nested_element($this->get_recommended_name());

        $subpluginimagetext = new backup_nested_element('slidetype_imagetext', array('id'), array(
            'slideinstanceid', 'content', 'contentformat', 'timemodified',
        ));

        // Connect XML elements into the tree.
        $subplugin->add_child($imagetext);
        $imagetext->add_child($subpluginimagetext);

        // Set source to populate the data.
        $sql = 'SELECT co.*
                FROM {slides_slide} cc
                JOIN {slidetype_imagetext} co ON co.slideinstanceid=cc.id
                WHERE cc.slidesid=:slidesid';

        $subpluginimagetext->set_source_sql($sql, ['slidesid' => backup::VAR_PARENTID]);


        // Include file areas.
        $filearea = "imagetextcontent_";

        foreach (range(1, 3) as $index) {
            $subpluginimagetext->annotate_files('mod_slides', $filearea . $index, null);
        }

        return $subplugin;
    }

}
