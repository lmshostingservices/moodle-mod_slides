<?php

namespace slidetype_video;

use html_writer;
use media_videojs_plugin;
use mod_slides\slide_editor;
use moodle_url;
use renderer_base;
use stdClass;

class slideinstance extends \mod_slides\slideinstance {

    protected $data;

    public const COMPLETION_COUNT = 1;

    public const FORCEEND = 1; // Same as audio, not used the audio as listen option.

    public function get_completion_content_count() : int {
        return self::COMPLETION_COUNT;
    }

    public function get_data_for_form(): stdClass {
        return $this->slidedata;
    }

    public function export_for_template(renderer_base $output) {
        global $OUTPUT;

        $this->get_data_for_form();

        $data = $this->slidedata;

        list($viewedindex, $completed) = $this->has_view_option();

        if (empty($this->slidedata->additional['videourl'])) {
            $completed = true;
        }

        // User viewed the count of listen content, or completes the slide before or has option to view.
        $viewedindex = $completed ? self::COMPLETION_COUNT : $viewedindex;

        // Image block.
        $videoembed = html_writer::start_div('video-block', ['width' => '100%;']);
        if (!empty($this->slidedata->additional['videourl'])) {
            $controls = ['controls' => 'controls', 'width' => '1800', 'id' => 'video-element-' . $data->id];
            if (!empty($this->slidedata->additional['mute'])) {
                $controls['muted'] = true;
            }

            $videoembed .= html_writer::tag('video',
                html_writer::empty_tag('source', ['src' => $this->slidedata->additional['videourl']]), $controls);
        }
        $videoembed .= html_writer::end_div();

        /* if (!empty($this->slidedata->additional['videourl'])) {
            $plugin = new media_videojs_plugin();
            $url = new moodle_url($this->slidedata->additional['videourl']);
            $controls = ['controls' => 'controls', 'width' => '1800', 'id' => 'video-element-'.$data->id];
            if (!empty($this->slidedata->additional['mute'])) {
                $controls['muted'] = true;
            }
            $videoembed = $plugin->embed([$url], 'slidetype_video', '100%', '100%', $controls);
        } */

        $index = 1;
        $content = (object) [
            'contentitemindex' => $index,
            'content' => format_text($videoembed),
            'viewed' => $viewedindex >= $index, // Viewed status of this slide.
            'currentitem' => true,
        ];

        // Inlcudes the listen options related data for the item.
        $this->include_listen_data($content, $index);

        // Format the listen content.
        $this->format_listencontent_data($data, [$content], true, $completed, $viewedindex, $index);

        if (!has_capability('mod/slides:addinstance', $this->context)) {
            $data->hideaudio = true;
        }

        return $data;
    }

}
