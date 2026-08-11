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

namespace slidetype_imagetext;

use mod_slides\slide_editor;
use renderer_base;
use stdClass;

class slideinstance extends \mod_slides\slideinstance {
    protected $data;

    public const COMPLETION_COUNT = 3;

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
        if (empty($this->slidedata->contents)) {
            $this->get_data_for_form();
        }
        $contents = array_filter($this->slidedata->contents, fn($value) => $value->content);
        return count($contents);
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

        $contents = $DB->get_records('slidetype_imagetext', ['slideinstanceid' => $this->slidedata->slideinstanceid]);

        return $contents;
    }

/*     protected function generate_styles($uniqueid) {

        $style = parent::generate_styles($uniqueid);
        $contentimageheight = $this->slidedata->additional['contentimageheight'];

        if ($contentimageheight) {
            $style .= '.slide-imagetext#'.$uniqueid.' .slides-imagesection{height:'.$contentimageheight.'px;}';
        }

        return $style;
    } */

    /**
     * Export the data for template.
     *
     * @param renderer_base $output
     * @return void
     */
    public function export_for_template(renderer_base $output) {

        $this->get_data_for_form();

        $this->slidedata->contentimage = $this->get_slide_file_url('contentimage', $this->slidedata->slideinstanceid, 'mod_slides');
        $data = $this->slidedata;

        $contentimageheight = $this->slidedata->additional['contentimageheight'] ?? '';
        $data->contentimageheight = $contentimageheight ? $contentimageheight . 'px' : 'auto';

        list($viewedindex, $completed) = $this->has_view_option();

        $contents = array_values($this->slidedata->contents);
        $contents = array_filter($contents, fn($value) => $value->content);

        if (empty($contents)) {
            $completed = true;
        }

        // User viewed the count of listen content, or completes the slide before or has option to view.
        $viewedindex = $completed ? $this->get_completion_content_count() : $viewedindex;

        $listenitems = [];
        $notavailable = false;

        if (!empty($contents)) {

            foreach ($contents as $key => $content) {
            // array_walk($contents, function ($content, $key, $viewedindex) {
                $index = $key + 1;
                $viewed = $viewedindex >= $index || $completed;

                $text = file_rewrite_pluginfile_urls(
                    $content->content, 'pluginfile.php', $this->context->id, 'mod_slides', 'imagetextcontent_' . $index, $content->slideinstanceid);

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
        }

        // Format the listen content.
        $this->format_listencontent_data($data, $listenitems, true, $completed, $viewedindex, $index ?? 0);

        if (empty($this->slidedata->contentimage)) {
            $data->fullcontent = true;
        }

        if (empty($listenitems)) {
            $data->fullimage = true;
        }
        return $data;

    }

}
