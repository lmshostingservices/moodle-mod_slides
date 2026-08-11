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

function xmldb_slides_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();


    if ($oldversion < 2024093003) {
        $table = new xmldb_table('slides_slide');

        $field = new xmldb_field('slidetype', XMLDB_TYPE_CHAR, 50, null, false, false);
        $dbman->change_field_type($table, $field);


        upgrade_mod_savepoint(true, 2024093003, 'slides');
    }

    if ($oldversion < 2024093004) {
        $table = new xmldb_table('slides_options');

        $field = new xmldb_field('slidetype', XMLDB_TYPE_CHAR, 50, null, false, false);
        $dbman->change_field_type($table, $field);


        upgrade_mod_savepoint(true, 2024093004, 'slides');
    }


    if ($oldversion < 2024093009) {

        $table = new xmldb_table('slides_slide_completion');
        $field = new xmldb_field('additional', XMLDB_TYPE_TEXT, null, null, false, false);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024093009, 'slides');
    }


    if ($oldversion < 2024093010) {

        $table = new xmldb_table('slides');
        $field = new xmldb_field('autotextsize', XMLDB_TYPE_INTEGER, 4, null, false, false);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2024093010, 'slides');
    }

    // v1.0.2: AMD DEFINE FIX: Converted all AMD build files (selectors.js,
    //   autosplit.js, slide.js, nctslides.js, flip/amd/build/slide.js,
    //   matching/amd/build/slide.js, video/amd/build/slide.js) from ES module
    //   syntax (import/export statements) to RequireJS define() format.
    //   ES module import statements in any AMD build file cause RequireJS to throw
    //   a SyntaxError when loading core/first, generating "No define call for
    //   core/first" and silently aborting the entire AMD chain -- hiding Moodle's
    //   primary and secondary navigation menus site-wide. No DB schema changes.
    //   version.php -> 2026042300001.
    if ($oldversion < 2026042300001) {
        upgrade_mod_savepoint(true, 2026042300001, 'slides');
    }

    if ($oldversion < 2026061800002) {
        upgrade_mod_savepoint(true, 2026061800002, 'slides');
    }

    if ($oldversion < 2026071700100) {
        // v1.1 — PHP-only fix. No DB schema changes.
        // FIX-SLIDE-SKIP: get_slides_slide_list() was calling get_records() without
        // an ORDER BY, so PHP returned slides in DB insertion order (by id). Slides
        // added or reordered after initial creation had higher ids and appeared at
        // the end of the array — making slide 7's "next" become slide 19 (the last
        // slide created) instead of slide 8. Fix: add 'sortorder ASC' to the query.
        if (function_exists('opcache_invalidate')) {
            $_pluginDir = realpath(__DIR__ . '/..');
            foreach (['classes/helper.php', 'version.php', 'db/upgrade.php'] as $_f) {
                $_full = $_pluginDir . '/' . $_f;
                if (file_exists($_full)) {
                    opcache_invalidate($_full, true);
                }
            }
        } elseif (function_exists('opcache_reset')) {
            opcache_reset();
        }
        upgrade_mod_savepoint(true, 2026071700100, 'slides');
    }

    if ($oldversion < 2026072300002) {
        // v1.0.2 CSS/JS/PHP audit fixes:
        // - Flattened all nested CSS in styles.css and 7 subplugin styles.css (Moodle PHP CSS minifier does not support nesting).
        // - Fixed slides_delete_instance() cascade deletion (orphaned slides_slide, slides_options, slides_slide_completion data).
        // - Fixed external.php update_fontsize() which queried ncttabs/ncttabs_tabs (copy-paste from another plugin).
        // - Added core/notification import to nctslides.min.js and slide.min.js AMD build files.
        // - Fixed backup/restore field names for slides_completion and slides_slide_completion tables.
        // - Removed stale 'use mod_slides\editor' import from lib.php, backup, and restore stepslib.
        // - Removed duplicate require_once('lib.php') in view.php.
        if (function_exists('opcache_invalidate')) {
            $_pluginDir = realpath(__DIR__ . '/..');
            foreach (['lib.php', 'version.php', 'view.php', 'classes/external.php',
                      'backup/moodle2/backup_slides_stepslib.php',
                      'backup/moodle2/restore_slides_stepslib.php', 'db/upgrade.php'] as $_f) {
                $_full = $_pluginDir . '/' . $_f;
                if (file_exists($_full)) {
                    opcache_invalidate($_full, true);
                }
            }
        } elseif (function_exists('opcache_reset')) {
            opcache_reset();
        }
        upgrade_mod_savepoint(true, 2026072300002, 'slides');
    }

    return true;
}
