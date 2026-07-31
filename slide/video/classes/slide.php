<?php

namespace slidetype_video;

use mod_slides\slideinstance as Mod_slidesSlideinstance;

/**
 * Slide.
 */
class slide extends \mod_slides\slidetype {

    public const SHORTNAME = 'video';

    public const LISTORDER = 3;

    /**
     * Undocumented function
     *
     * @return string
     */
    public static function slide_shortname(): string {
        return self::SHORTNAME;
    }

    public static function slide_description(): string {
        return get_string('slidedescription', 'slidetype_video');
    }

    public static function slide_name(): string {
        return get_string('pluginname', 'slidetype_video');
    }

    public static function slide_icon(): string {
        global $OUTPUT;

        return $OUTPUT->image_icon('monologo', get_string('pluginname', 'slidetype_video'), 'slidetype_video', ['class' => 'icon']);
    }

    public static function slide_fileareas_list() : array {
        return ['posterimage'];
    }


    public static function backup_files() : array {
        $list = ['posterimage', 'video_listenaudio_1'];
        return $list;
    }

    public function is_user_completed(int $userid, int $instanceid): bool {
        return false;
    }

    /**
     * This slide doesn't supports the custom table.
     *
     * @return bool
     */
    protected function supports_custom_table() : bool {
        return false;
    }

    public function supports_autoresize() : bool {
        return false;
    }


    /**
     * Includes custom js module.
     *
     * @return string
     */
    public function includes_custom_jsmodule() : string {
        return 'slidetype_video/slide';
    }

    /**
     * Slide form
     *
     * @param [type] $mform
     * @param [type] $instance
     * @return void
     */
    public function slide_form($url, $customdata) {
        $slideform =  new \slidetype_video\slideform($url, $customdata);
        return $slideform;
    }


    public function export_for_template($output) : array {
        return [];
    }


}


