<?php

namespace slidetype_flip;

class autohelper extends \mod_slides\autohelper {

    protected function init_content_split(&$data) {

        // Update the end of paragraph as new line.
        $data->dynamiccontent = str_replace('<br>', "<br>\n", $data->dynamiccontent);

        $contents = self::split_content($data->dynamiccontent);

        $headcount = $this->slide->split_head_count();
        $contentcount = $this->slide->split_content_count();
        $maxslide = $this->slide->split_maxinstance_count();

        $slide = [];
        $instancecount = 0;

        for ($i = 0; $i <= count($contents); $i++) {

            if ($instancecount > $maxslide) {
                break;
            }

            if (isset($contents[$i]) && $contents[$i]['type'] == 'heading') {
                $instancecount++;
                if ($instancecount > $maxslide) {
                    break;
                }

                $slide[$instancecount] = ['title' => [
                    'text' => $contents[$i]['text'], 'format' => FORMAT_HTML, 'itemid' => file_get_unused_draft_itemid()]
                ];
            } else if (isset($contents[$i]['text'])) {
                // Once the max content is reached for the instance, stop adding to current instance. Store as new instance without title.
                if (!empty($slide[$instancecount]['contents']) && count($slide[$instancecount]['contents']) >= $contentcount) {
                    $instancecount++;
                    if ($instancecount > $maxslide) {
                        break;
                    }
                }

                $slide[$instancecount]['contents'][] = [
                    'text' => $contents[$i]['text'] ?? '', 'format' => FORMAT_HTML, 'itemid' => file_get_unused_draft_itemid()
                ];
            }
        }

        $slideinstanceid = $data->slideinstanceid ?? 0; // Confirm this request is for new instance.
        $data->slidelist = []; // Store all the created contents for audio split.

        foreach ($slide as $key => $split) {

            if (empty($split['title']) && empty($split['contents'])) {
                continue;
            }

            $data->title = $split['title']['text'] ?? '';
            $data->name ?: strip_tags($data->title);

            $content = array_combine(range(1, count($split['contents'])), array_values($split['contents']));
            $data->dynamiccontent = $data->title . implode("\n", array_column($content, 'text'));

            $contents = array_filter($content, fn($i) => $i % 2 != 0 , ARRAY_FILTER_USE_KEY);
            $feedback = array_filter($content, fn($i) => $i % 2 == 0, ARRAY_FILTER_USE_KEY);

            $data->content = !empty($contents) ? array_combine(range(1, count($contents)), array_values($contents)) : [];
            $data->feedback = !empty($feedback) ? array_combine(range(1, count($feedback)), array_values($feedback)) : [];

            $data->additional = is_string($data->additional) ? json_decode($data->additional, true) : [];
            $data->additional['flipscount'] = count($data->content);
            $data->additional = json_encode($data->additional);

            $this->slide->update_slide($data);
            $this->slideform->post_update_files_editors($data, $this->slide);

            $data->slidelist[] = clone $data;
            // Remove the slideinstanceid to prevent update the exisitng slide.
            if (empty($slideinstanceid) && isset($data->slideinstanceid)) {
                $data->slideinstanceid = 0;
            }
        }

        $data->submitbutton = 1;
        $data->return = 0;

    }
}
