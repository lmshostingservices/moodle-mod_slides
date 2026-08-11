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

namespace slidetype_summary;

use mod_slides\slide_editor;
use renderer_base;
use stdClass;

class slideinstance extends \mod_slides\slideinstance {
    protected $data;

    public const COMPLETION_COUNT = 8;

    /**
     * Display the content image in left side.
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */
    public const IMAGELEFT = 1;

    /**
     * Display the content image in right default side.
     */
    public const IMAGERIGHT = 0;

   /*  public static function instance(int $instanceid): self  {
        return new self($instanceid);
    } */

    public function get_completion_content_count() : int {
        $contents = $this->get_contents();
        $contents = array_filter($contents, fn($value) => $value->content);
        return !empty($contents) ? count($contents): self::COMPLETION_COUNT;
    }

    /**
     * Get the data for form.
     *
     * Include addtional data or update the data record to form fields data.
     *
     * @return stdClass
     */
    public function get_data_for_form(): stdClass {
        $contents = $this->get_contents();
        $this->slidedata->contents = $contents;
        $this->slidedata->listencounts = $this->slidedata->additional['count'] ?? 1;

        return $this->slidedata;
    }


    /**
     * Get the contents.
     *
     * @return array
     */
    protected function get_contents(): array {
        global $DB;

        if (empty($this->slidedata->slideinstanceid)) {
            return [];
        }

        $contents = $DB->get_records('slidetype_summary', ['slideinstanceid' => $this->slidedata->slideinstanceid]);

        return $contents;
    }

    /**
     * Export the data for template.
     *
     * @param renderer_base $output
     * @return void
     */
    public function export_for_template(renderer_base $output) {

        $this->get_data_for_form();

        // $this->slidedata->contentimage = $this->get_slide_file_url('contentimage', $this->slidedata->slideinstanceid, 'mod_slides');
        $data = $this->slidedata;

        $data->contents = array_filter($data->contents, fn($value) => $value->content);

        list($viewedindex, $completed) = $this->has_view_option();
        // User viewed the count of listen content, or completes the slide before or has option to view.
        $viewedindex = $completed ? $this->get_completion_content_count() : $viewedindex;

        $contents = array_values($data->contents);
        $listenitems = [];
        $notavailable = false;

        $index = 0;
        if (!empty($contents)) {
            foreach ($contents as $key => $content) {
                $index = $key + 1;
                $viewed = $viewedindex >= $index || $completed;

                $text = file_rewrite_pluginfile_urls(
                    $content->content, 'pluginfile.php', $this->context->id, 'mod_slides', 'summarycontent_' . $index, $content->slideinstanceid);

                // TODO: File rewrite url.
                $item = (object) [
                    'contentitemindex' => $index,
                    'content' => $text,
                    'viewed' => $viewed, // Viewed status of this slide.
                    'currentitem' => true,
                    'notavailable' => $notavailable,
                    // 'completed' => $completed ? true : false,
                ];

                // Inlcudes the listen options related data for the item.
                $this->include_listen_data($item, $index);

                $listenitems[] = $item;

                if (!$viewed && (!$this->slidesinstance->autotextsize || $this->forceavailableitem)) {
                    break;
                } else if (!$viewed && $this->slidesinstance->autotextsize) {
                    $notavailable = true;
                }
            }
        } else {
            $completed = true;
        }

        // Format the listen content.
        $this->format_listencontent_data($data, $listenitems, true, $completed, $viewedindex, $index);

        return $data;

    }

}
