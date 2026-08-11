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

namespace slidetype_flip;

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


    public function get_completion_content_count() : int {

        if (!isset($this->slidedata->contents)) {
            $this->get_data_for_form();
        }

        $contents = array_filter($this->slidedata->contents, fn($value) => $value->content);

        return !empty($contents) ? count($contents) : self::COMPLETION_COUNT;
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
        $this->slidedata->listencounts = $this->slidedata->additional['flipscount'] ?? 1;

        return $this->slidedata;
    }

    /**
     * Update the slide content viewed index.
     *
     * @param integer $slideinstanceid
     * @param integer $userid
     * @param integer $contentviewedindex
     * @return void
     */
    public function update_slide_content_viewed(int $slideinstanceid, int $userid, int $contentviewedindex) {
        global $DB;

        $condition = ['slideinstanceid' => $slideinstanceid, 'userid' => $userid];

        $contentcount = $this->get_completion_content_count();

        if ($record = $DB->get_record('slides_slide_completion', $condition)) {
            $record->contentscount = $contentcount;
            $record->timemodified = time();
            $additional = is_string($record->additional) ? explode(',', $record->additional) : [];
            $additional[] = $contentviewedindex;
            $uniqueviews = array_unique($additional);
            $record->additional = implode(',', $uniqueviews);
            $record->viewed = count($uniqueviews);
            // Completion record.
            $record->completion = $record->viewed >= $contentcount;

            if ($DB->update_record('slides_slide_completion', $record)) {
                return $record->id;
            }

        } else {

            $record = (object) $condition;
            $record->viewed = 1;
            $record->contentcount = $contentcount;
            $record->timecreated = time();
            $additional = [$contentviewedindex];
            $record->additional = implode(',', $additional);
            // Completion record.
            $record->completion = $record->viewed >= $contentcount;

            return $DB->insert_record('slides_slide_completion', $record);
        }

        return false;
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

        $contents = $DB->get_records('slidetype_flip', ['slideinstanceid' => $this->slidedata->slideinstanceid]);

        return $contents;
    }

    /**
     * Export the data for template.
     *
     * @param renderer_base $output
     * @return void
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $this->get_data_for_form();

        $this->slidedata->contentimage = $this->get_slide_file_url('contentimage', $this->slidedata->slideinstanceid, 'mod_slides');
        $data = $this->slidedata;

        list($viewedindex, $completed) = $this->has_view_option(false);

        $contents = array_values($this->slidedata->contents);
        $contents = array_filter($contents, fn($value) => $value->content);

        // Complete the slide if not content found.
        if (empty($contents)) {
            $completed = true;
        }

        // User viewed the count of listen content, or completes the slide before or has option to view.
        $viewedindex = $completed ? $this->get_completion_content_count() : $viewedindex;

        // Find the fliped contents.
        $additional = [];
        if (!$completed) {
            $completion = $this->get_completion($this->instanceid, $USER->id);
            $additional = array_flip(!empty($completion->additional) ? explode(',', $completion->additional) : []);
        }

        $listenitems = [];
        if (!empty($contents)) {

            foreach ($contents as $key => $content) {

                $index = $key + 1;
                $viewed = array_key_exists($index, $additional) || $completed;

                $text = file_rewrite_pluginfile_urls(
                    $content->content, 'pluginfile.php', $this->context->id, 'mod_slides', 'flipcontent_' . $index, $content->slideinstanceid, );

                $feedback = file_rewrite_pluginfile_urls(
                    $content->feedback, 'pluginfile.php', $this->context->id, 'mod_slides', 'flipfeedback_' . $index, $content->slideinstanceid);

                // Get front image URL if exists.
                $frontimageurl = $this->get_flip_frontimage_url($index, $content->slideinstanceid);

                // TODO: File rewrite url.
                $item = (object) [
                    'contentitemindex' => $index,
                    'content' => $text,
                    'feedback' => $feedback,
                    'frontimageurl' => $frontimageurl,
                    'hasfrontimage' => !empty($frontimageurl),
                    'viewed' => $viewed, // Viewed status of this slide.
                    'currentitem' => true,
                    'itemcompleted' => $viewed ? true : false,
                    'isflipped' =>  $completed || array_key_exists($index, $additional) ? true : false,
                ];

                // Inlcudes the listen options related data for the item.
                $this->include_listen_data($item, $index);

                $listenitems[] = $item;
            }
        }

        // Format the listen content.
        $this->format_listencontent_data($data, $listenitems, false, $completed, $viewedindex, $index ?? 0);

        // print_obj($data);

        return $data;

    }


    /**
     * Generate the styles.
     *
     * @return void
     */
    protected function generate_styles($uniqueid) {

        $style = parent::generate_styles($uniqueid);

        $uniqueid = "#$uniqueid" . '.slide-' . $this->slidedata->slidetype;
        $styles = $this->slidedata->fontstyle ? $this->slidedata->fontstyle : [];

        $fonturl = 'https://fonts.googleapis.com/css2?family=';
        $imports = '';

        $feedback = [];
        if (array_key_exists('feedbacksize', $styles) && !empty($styles['feedbacksize'])) {
            $size = $styles['feedbacksize'] ? $styles['feedbacksize'] . 'px' : '';
            $feedback[] = "font-size: $size; !important";
        }

        // Include the heading fonts style.
        if (array_key_exists('feedbackfont', $styles) && !empty($styles['feedbackfont'])) {
            $font = str_replace(' ', '+', $styles['feedbackfont']);
            $headingstyle = $fonturl . $font;
            $imports .= "@import url('$headingstyle');";

            $feedback[] = 'font-family: "'.$styles['feedbackfont'].'", serif;
                        font-optical-sizing: auto;';
        }

        if (!empty($feedback)) {
            $rules = implode('', $feedback);
            $style .= '
                .nct-slides-view-content ' . $uniqueid . '  .flip-feedback-side .content {
                    '.$rules.'
                }';
        }

        return $imports . $style;
    }

    /**
     * Get the front image URL for a flip card.
     *
     * @param int $index The card index (1-based)
     * @param int $slideinstanceid The slide instance ID
     * @return string|null The image URL or null if no image
     */
    protected function get_flip_frontimage_url(int $index, int $slideinstanceid): ?string {
        $fs = get_file_storage();
        $filearea = 'flip_frontimage_' . $index;

        $files = $fs->get_area_files(
            $this->context->id,
            'mod_slides',
            $filearea,
            $slideinstanceid,
            'itemid, filepath, filename',
            false
        );

        if (!empty($files)) {
            $file = reset($files);

            return \moodle_url::make_pluginfile_url(
                $this->context->id,
                'mod_slides',
                $filearea,
                $slideinstanceid,
                $file->get_filepath(),
                $file->get_filename()
            )->out();
        }

        return null;
    }
}
