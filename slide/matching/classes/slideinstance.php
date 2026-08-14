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

namespace slidetype_matching;

use gradereport_singleview\local\ui\unique_name;
use mod_slides\slide_editor;
use renderer_base;
use stdClass;

class slideinstance extends \mod_slides\slideinstance {
    protected $data;

    public const COMPLETION_COUNT = 1;

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


    public function get_completion_content_count() : int {
        return self::COMPLETION_COUNT;
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

        $contents = $DB->get_records('slidetype_matching', ['slideinstanceid' => $this->slidedata->slideinstanceid]);

        return $contents;
    }

    /**
     * Export the data for template.
     *
     * @param renderer_base $output
     * @return void
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;

        $this->get_data_for_form();

        $this->slidedata->contentimage = $this->get_slide_file_url('contentimage', $this->slidedata->slideinstanceid, 'mod_slides');
        $data = $this->slidedata;

        list($viewedindex, $completed) = $this->has_view_option(false);

        // User viewed the count of listen content, or completes the slide before or has option to view.
        $viewedindex = $completed ? $this->get_completion_content_count() : $viewedindex;
        $viewed = $completed;

        $contents = array_values($this->slidedata->contents);
        $contents = array_filter($contents, fn($value) => $value->content);

        $listenitems = [];
        if (!empty($contents)) {
            foreach ($contents as $key => $content) {

                $index = $key + 1;

                $text = file_rewrite_pluginfile_urls(
                    $content->content, 'pluginfile.php', $this->context->id, 'mod_slides', 'matchingcontent_'. $index, $content->slideinstanceid);
                // TODO: File rewrite url.
                $item = (object) [
                    'contentid' => $content->id,
                    'contentitemindex' => $index,
                    'content' => $text,
                    'answer' => '',
                    // The correct answer for THIS question (used to render the completed/preview view
                    // where each drop should already show its correct match, not a shuffled one).
                    'correctanswer' => format_string($content->answer),
                    'correctanswercontentid' => $content->id,
                    'viewed' => $viewed, // Viewed status of this slide.
                    'currentitem' => true,
                    'itemcompleted' => $viewed ? true : false,
                ];

                $answers[] = ['answer' => format_string($content->answer), 'answercontentid' => $content->id];

                // Inlcudes the listen options related data for the item.
                $this->include_listen_data($item, $index);

                $listenitems[] = $item;
            }
        }

        if (!$completed && !empty($answers)) {
            shuffle($answers); // Suffle the answers.
        }

        $response = [];
        foreach ($listenitems as $item) {
            $key = array_search($item->contentid, array_column($answers, 'answercontentid'));
            $response[$item->contentitemindex] = $answers[$key]['answercontentid'] ?? '';
        }

        foreach ($listenitems as &$item) {
            $item = (object) array_merge((array) $item, array_shift($answers));
        }

        // Format the listen content.
        $this->format_listencontent_data($data, $listenitems, false, $completed, $viewedindex, $index ?? 0);

        $this->jsdata->response = $response;

        // $data->uniqid = uniqid('ddmatching');

        if (!$completed) {
            $PAGE->requires->js_call_amd('slidetype_matching/ddmatching', 'init', [$data->uniqid, false, $response]);
        }

        return $data;

    }

    /**
     * Suffle the contents and questions.
     *
     * @param [type] $list
     * @return void
     */
    protected function shuffle($list) {

        if (!is_array($list)) {
            return $list;
        }

        $keys = array_keys($list);
        shuffle($keys);
        $random = array();

        foreach ($keys as $key) {
            $random[$key] = $list[$key];
        }
        return $random;
    }

}
