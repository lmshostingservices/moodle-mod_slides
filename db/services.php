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

$functions = [

        'mod_slides_update_fontsize' => [
            'classname' => 'mod_slides\external',
            'methodname' => 'update_fontsize',
            'description' => 'Update the font size automatically for the text image',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true,
        ],

        'mod_slides_update_slidecompletion' => [
            'classname' => 'mod_slides\external',
            'methodname' => 'update_slidecompletion',
            'description' => 'Update the completion of slide in slides module.',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true,
        ],
];
