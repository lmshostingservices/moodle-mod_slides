<?php

namespace mod_slides;

use stdClass;

class autohelper {

    protected $data;

    protected $context;

    public const SILENCE_ONESECOND = "1.0";

    public const SILENCE_TWOSECOND = "2.0";

    public const SILENCE_THREESECOND = "3.0";


    protected $slide;

    protected $slideform;

    public function __construct($context) {
        $this->context = $context;
    }

    public function update_module_files($data, $slideobj, $mform) {

        $this->slide = $slideobj;

        $this->slideform = $mform;
        /* if (!empty($data->dynamicaudio)) {
            file_save_draft_area_files(
                $data->dynamicaudio, $this->context->id, 'mod_slides', 'dynamicaudio', 0, slides_get_file_options(['audio'])
            );
        }
 */
        if (property_exists($data, 'updatedynamic')) {
            // Split both audio and content.
            $this->init_content_split($data);
            $this->init_audio_split($data);

        } else if (property_exists($data, 'splitcontent')) {
            // Split the content only.
            $this->init_content_split($data);
        } else if (property_exists($data, 'splitaudio')) {
            // Split the audio only.
            $this->init_audio_split($data);
        } else {

            $this->slide->update_slide($data);
            $this->slideform->post_update_files_editors($data, $this->slide);
        }
    }

    protected function init_content_split(&$data) {

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
        $name = $data->name;

        $data->slidelist = []; // Store all the created contents for audio split.

        foreach ($slide as $key => $split) {

            if (empty($split['title']) && empty($split['contents'])) {
                continue;
            }

            $data->title = $split['title']['text'] ?? '';
            $data->name = $data->name && $name ? $data->name : strip_tags($data->title);

            if (empty($split['contents'])) {
                $data->content = [];
            } else {
                $data->content = array_combine(range(1, count($split['contents'])), array_values($split['contents']));
            }

            $data->dynamiccontent = $data->title . implode("\n", array_column($data->content, 'text'));

            $data->additional = is_string($data->additional) ? json_decode($data->additional, true) : [];
            $data->additional['count'] = count($data->content);
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

    /**
     * Get the tab file from the file system.
     *
     * @param string $filearea
     * @return stored_file
     */
    public function get_audio_file(int $itemid) {
        $fs = get_file_storage();

        $files = $fs->get_area_files($this->context->id, 'mod_slides', 'dynamicaudio', $itemid, "", false);

        if (!empty($files)) {
            $file = reset($files);

            return $file;
        }

        return false;
    }

    protected function init_audio_split($data) {
        global $CFG;

        // print_obj($data);exit;
        // Split the audio.
        $file = $this->get_audio_file($data->slideinstanceid ?? 0);

        if (!empty($file)) {

            $tempdir = make_temp_directory('slides');
            if (!is_dir($tempdir)) {
                $tempdir = $CFG->dirroot . '/mod/slides/pix/temp';
            }

            if (!$file->copy_content_to($tempdir . '/'. $file->get_filename())) {
                $data->submitbutton = 1;
                $data->return = 0;
                return false;
            }

            $copiedfile = $tempdir . '/'. $file->get_filename();

            if (isset($data->audiosplitmethod) && $data->audiosplitmethod == 1 && isset($data->audiotime) && !empty($data->audiotime)) {
                $times = $data->audiotime;
                self::audio_split($copiedfile, $times);
            } else {
                // Find the count of available contents.
                $contentcount = 0;
                if (!empty($data->slideinstanceid)) {
                    $contentcount = count($data->content);
                } else {
                    $contentcount = -1;
                }

                $times = self::audio_detect_silencetime($copiedfile, $contentcount);

                $data->audiotime = $times;
                self::audio_split($copiedfile, $times);
            }

            $fs = get_file_storage();

            $newfile = [
                'contextid'    => $this->context->id,
                'component'    => 'mod_slides',
                'filepath'     => '/',
                'timecreated'  => time(),
                'timemodified' => time(),
            ];

            $list = $data->slidelist ?? [$data];

            $filearea = $this->slide::slide_shortname() . '_listenaudio_';

            $counter = 1;
            foreach ($list as $slideindex => $slide) {

                foreach ($slide->content as $contentindex => $content) {

                    if (empty($content['text'])) {
                        continue;
                    }

                    $filename = $filearea . $contentindex;
                    $outputfile = $tempdir . '/content-' . $counter . '.mp3';

                    if (file_exists($outputfile)) {

                        $newfile['filename'] = $filename . "-" . time() . ".mp3";
                        $newfile['filearea'] = $filename;
                        $newfile['itemid'] = $slide->slideinstanceid;

                        // Clear the existing area files to move the new split audios.
                        $fs->delete_area_files($newfile['contextid'], $newfile['component'], $newfile['filearea'], $slide->slideinstanceid);

                        // Create a file record in filestorage, from the saved split audio.
                        $stfile = $fs->create_file_from_pathname($newfile, $outputfile);

                        // Remove the split one from temp, otherwise the upcoming split audios are not replaced.
                        // unlink($outputfile);

                        $counter++;

                    }
                }
            }

            // Clear up the temp directory.
            // unlink($copiedfile);
        }


        $data->submitbutton = 1;
        $data->return = 0;
    }

    public static function audio_detect_silencetime($file, $contentcount) {

        // Get the configured silence seconds.
        $silenceseconds = get_config('slides', 'audiosilenceduration');
        $silenceseconds = $silenceseconds ?: self::SILENCE_ONESECOND;

        $cmd = ' -i ' . escapeshellarg($file) . ' -af silencedetect=n=-15dB:d='. escapeshellarg($silenceseconds) .' -f null -';

        $cmd = self::build_ffmpeg_cmd($cmd);

        $output = '';

        $times = [];
        $ffmpeg = exec($cmd . ' 2>&1', $output);

        if ($output) {

            // Find the duration of audio file.
            foreach ($output as $line) {
                $matches = '';
                // Extract the duration using regex.
                if (preg_match('/Duration:\s([0-9]{2}):([0-9]{2}):([0-9]{2})\.([0-9]{2})/', $line, $matches)) {
                    // Check if duration was found
                    if (isset($matches[1], $matches[2], $matches[3], $matches[4])) {
                        $hours = $matches[1];
                        $minutes = $matches[2];
                        $seconds = $matches[3];
                        $milliseconds = $matches[4];
                        // Convert the extracted time to total seconds.
                        $duration = ($hours * 3600) + ($minutes * 60) + $seconds + ($milliseconds / 100);
                        break;
                    }
                }
            }

            // Get the silences parts in the audio.
            $i = 0;
            $silences = [];
            foreach ($output as $line) {

                if (preg_match('/silence_start:\s([0-9.]+)/', $line, $matches)) {
                    $i++;
                    $silences[$i]['start'] = $matches[1] > 0 ? $matches[1] : 0;
                }

                if (preg_match('/silence_end:\s([0-9.]+)/', $line, $matches)) {
                    $silences[$i]['end'] = $matches[1];
                }

                if (preg_match('/silence_duration:\s([0-9.]+)/', $line, $matches)) {
                    $silences[$i]['duration'] = $matches[1];
                }
            }

            // Detected more than 2 silence. then create the audio clips.
            if (count($silences) > 1) {
                $i = 1;
                $times[$i] = ['start' => 0, 'end' => 0];
                // Generate the audio clips start and end times based on detected silence.
                foreach ($silences as $silence) {
                    // Duration found in the silence. use the half of the duration with the end.
                    // This will help prevent audio cutt on edges with high volume is some senarios.
                    $end = $silence['start'];
                    if (isset($silence['duration']) && !empty($silence['duration'])) {
                        $end += ($silence['duration'] / 2);
                    }

                    $times[$i]['end'] = $end;
                    $i = $i + 1;

                    if ($contentcount == -1 || $i <= $contentcount) {
                        $times[$i] = ['start' => $end, 'end' => 0];
                    }
                }
            }

            // Set end time for the last segment if it exists
            if (isset($times[$i])) {
                $times[$i]['end'] = $duration; // Use the total duration as the end time.
            }
        }

        // Filter the times with empty end.
        $times = array_filter($times, function($v) {
            return $v['end'] > 0 ? true : false;
        });

        return $times ?: [];
    }

    /**
     * Audio split.
     *
     * @return void
     */
    public static function audio_split($file, $times) {
        global $CFG;

        $start = 0;
        $counter = 1;
        $tempdir = make_temp_directory('slides');

        if (!is_dir($tempdir)) {
            $tempdir = $CFG->dirroot . '/mod/slides/pix/temp';
        }

        foreach ($times as $k => $time) {

            if (!isset($time['start']) || !isset($time['end'])) {
                continue;
            }

            $start = $time['start'];
            $end = $time['end'];

            $outputfile = $tempdir . "/content-$counter.mp3";
            $cmd = self::build_ffmpeg_cmd("-y -i " . escapeshellarg($file) . " -ss " . escapeshellarg($start) . " -to " . escapeshellarg($end) . " -c copy ". escapeshellarg($outputfile) . " 2>&1");

            $nn = exec($cmd);
            $counter++;
        }

        return true;
    }

    /**
     * Verify this method is heading.
     *
     * @param string $text
     * @return bool
     */
    protected static function is_heading($text) {

        $lesslenth = (strlen(strip_tags(trim($text))) <= 500);
        if ((preg_match('/^<h[1-6].*>/i', $text)) && $lesslenth) {
            return true;
        }
        return false;
    }

    /**
     * Split the content.
     *
     * @param string $content
     *
     * @return object
     */
    public static function split_content($content) {

        // Dynamic content.
        $dynamiccontent = $content;

        // Split the dynamic content.
        $split = explode("\n", $dynamiccontent);

        // Remove empty tags.
        $split = array_values(array_filter($split, fn($v) => strlen(trim(strip_tags($v))) >= 1));

        // $heads = array_filter($split, fn($a) => self::is_heading($a));

        $result = (object) ['title' => '', 'contents' => []];
        // No title.
        // Given content is odd number, then use the first element as title otherwise pair the elements.
        // With this method without title it can be updated to contents.

        $head = false;
        $content = [];
        foreach ($split as $text) {
            $text = trim($text);
            if (empty($text)) {
                continue;
            }
            $content[] = ['type' => self::is_heading($text) ? 'heading':'content', 'text' => $text];
        }

        return $content;
    }

    /**
     * Verify the FFMPEG is installed on the server.
     *
     * @return bool
     */
    public static function is_ffmpeg_installed() {

        $ffmpegpath = self::get_ffmpeg_path();
        $output = shell_exec( $ffmpegpath . ' -version');

        return ($output && strpos($output, 'ffmpeg version') !== false);
    }

    /**
     * Get the FFMpeg path from the config.
     *
     * @return string
     */
    public static function get_ffmpeg_path() {

        $ffmpegpath = get_config('slides', 'ffmpegpath');

        if (!str_ends_with($ffmpegpath, 'ffmpeg') && !str_ends_with($ffmpegpath, 'ffmpeg.exe') && !str_ends_with($ffmpegpath, '/ffmpeg/')) {
            $ffmpegpath .= 'ffmpeg';
        }

        return escapeshellarg($ffmpegpath);
    }

    /**
     * Build the command of ffmpeg.
     *
     * Fetch the ffmpeg cli path and appends the command.
     *
     * @param string $cmd
     * @return string
     */
    protected static function build_ffmpeg_cmd($cmd) {
        $path = self::get_ffmpeg_path();

        return $path. " " . $cmd;
    }

}
