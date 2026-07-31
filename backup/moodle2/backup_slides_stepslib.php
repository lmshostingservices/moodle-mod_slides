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
 * Definition backup-steps
 *
 * @package   mod_slides
 * @copyright 2024, LMSACE Dev Team <https://www.lmsace.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete slides structure for backup, with file and id annotations.
 */
class backup_slides_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define backup steps structure.
     */
    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated - table fields.
        $slides = new backup_nested_element('slides', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'containerheight', 'autotextsize',
            'completionendreach', 'timecreated', 'timemodified',
        ));

        $content = new backup_nested_element('slide');
        $slidescontent = new backup_nested_element('slides_slide', array('id'), array(
            'slidesid', 'slidetype', 'name', 'title', 'titleformat', 'sortorder', 'status',
            'additional', 'dynamiccontent', 'dynamiccontentformat', 'timecreated', 'timemodified',
        ));

        $this->add_subplugin_structure('slidetype', $slides, true);

        $options = new backup_nested_element('slidesopitons');
        $slidesoptions = new backup_nested_element('slides_options', array('id'), array(
            'slidetype', 'slideinstanceid', 'audiosplitmethod', 'audiotime', 'fontstyle',
            'animation', 'imageanimation', 'forcelisten', 'forcenext', 'listenduration',
            'timecreated', 'timemodified',
        ));

        // Per-slide user completion: maps to slides_slide_completion table.
        $slideviews = new backup_nested_element('slideviews');
        $slideviewscompletion = new backup_nested_element('slides_slide_completion', array('id'), array(
            'slideinstanceid', 'userid', 'contentscount', 'viewed', 'completion',
            'additional', 'timemodified',
        ));

        // Overall activity completion: maps to slides_completion table.
        $completion = new backup_nested_element('slidescompletion');
        $slidescompletion = new backup_nested_element('slides_completion', array('id'), array(
            'slidesid', 'userid', 'slidecount', 'slideviewed', 'completion', 'timecreated',
        ));

        $slides->add_child($slideviews);
        $slideviews->add_child($slideviewscompletion);

        $slides->add_child($completion);
        $completion->add_child($slidescompletion);

        $slides->add_child($content);
        $content->add_child($slidescontent);

        $slides->add_child($options);
        $options->add_child($slidesoptions);

        // Define sources.
        $slides->set_source_table('slides', array('id' => backup::VAR_ACTIVITYID));
        $slidescontent->set_source_table('slides_slide', array('slidesid' => backup::VAR_PARENTID));

        $sql = 'SELECT co.* FROM {slides_slide} cc
                JOIN {slides_options} co ON co.slideinstanceid = cc.id
                WHERE cc.slidesid = :slidesid';
        $slidesoptions->set_source_sql($sql, ['slidesid' => backup::VAR_PARENTID]);

        // User-specific data only when userinfo is included.
        if ($userinfo) {
            $sql = 'SELECT co.* FROM {slides_slide} cc
                    JOIN {slides_slide_completion} co ON co.slideinstanceid = cc.id
                    WHERE cc.slidesid = :slidesid';
            $slideviewscompletion->set_source_sql($sql, ['slidesid' => backup::VAR_PARENTID]);
            $slideviewscompletion->annotate_ids('user', 'userid');

            $slidescompletion->set_source_table('slides_completion',
                array('slidesid' => backup::VAR_PARENTID));
            $slidescompletion->annotate_ids('user', 'userid');
        }

        // Define file annotations (intro image + all slide media files).
        $slides->annotate_files('mod_slides', 'intro', null);

        $audios = mod_slides\helper::backup_include_listenaudio($this->task->get_activityid());
        $areas = [];
        foreach ($audios as $audio) {
            $areas[] = $audio->filearea;
        }
        $areas = array_merge($areas, mod_slides\helper::backup_include_slides_areafiles($this->task->get_activityid()));
        $areas = array_unique($areas);
        foreach ($areas as $filearea) {
            $slides->annotate_files('mod_slides', $filearea, null);
        }

        // Return the root element (data), wrapped into standard activity structure.
        return $this->prepare_activity_structure($slides);
    }
}
