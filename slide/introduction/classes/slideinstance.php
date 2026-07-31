<?php

namespace slidetype_introduction;

use mod_slides\slide_editor;
use renderer_base;
use stdClass;

class slideinstance extends \mod_slides\slideinstance {

    protected $data;

    public const COMPLETION_COUNT = 1;

   /*  public static function instance(int $instanceid): self  {
        return new self($instanceid);
    } */

    public function get_completion_content_count() : int {
        return self::COMPLETION_COUNT;
    }

    public function get_data_for_form(): stdClass {

        $this->slidedata->introcontent = $this->slidedata->additional ?? [];

        $this->slidedata->introimageheight = $this->slidedata->additional['introimageheight'] ?? '';

        // Todo update intro image.

        return $this->slidedata;
    }


    public function export_for_template(renderer_base $output) {

        $this->get_data_for_form();

        $this->slidedata->introimage = $this->get_slide_file_url('introimage', $this->slidedata->slideinstanceid, 'mod_slides');

        if (empty($this->slidedata->introcontent['text']) && empty($this->slidedata->introimage)) {
            return false;
        }

        $data = $this->slidedata;
        $data->introimageheight = $data->introimageheight ? $data->introimageheight .'px' : '100%';

        list($viewedindex, $completed) = $this->has_view_option();

        // Complete the intro if the slide is empty.
        if (empty($this->slidedata->introcontent['text'])) {
            $completed = true;
        }
        // User viewed the count of listen content, or completes the slide before or has option to view.
        $viewedindex = $completed ? self::COMPLETION_COUNT : $viewedindex;

        $index = 1;
        $content = (object) [
            'contentitemindex' => $index,
            'content' => $this->slidedata->introcontent['text'],
            'viewed' => $viewedindex >= $index, // Viewed status of this slide.
            'currentitem' => true,
        ];

        // Inlcudes the listen options related data for the item.
        $this->include_listen_data($content, $index);

        // Format the listen content.
        $this->format_listencontent_data($data, [$content], true, $completed, $viewedindex, $index);

        // Hide content section if the intro content is empty.
        $data->hidecontentsection = empty($this->slidedata->introcontent['text']) && empty($content->listenaudio);

        return $data;

    }

}
