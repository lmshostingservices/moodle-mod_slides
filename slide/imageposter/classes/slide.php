<?php

namespace slidetype_imageposter;

use mod_slides\slideinstance as Mod_slidesSlideinstance;

class slide extends \mod_slides\slidetype {

    public const SHORTNAME = 'imageposter';

    public const LISTORDER = 6;

    public static function slide_shortname(): string {
        return self::SHORTNAME;
    }

    public static function slide_description(): string {
        return get_string('slidedescription', 'slidetype_imageposter');
    }

    public static function slide_name(): string {
        return get_string('pluginname', 'slidetype_imageposter');
    }

    public static function slide_icon(): string {
        global $OUTPUT;

        return $OUTPUT->image_icon('monologo', get_string('pluginname', 'slidetype_imageposter'), 'slidetype_imageposter', ['class' => 'icon']);
    }

    public static function slide_fileareas_list() : array {
        return ['posterimage'];
    }


    public static function backup_files() : array {
        return ['posterimage', 'imageposter_listenaudio_1'];
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

    /**
     * Slide form
     *
     * @param [type] $mform
     * @param [type] $instance
     * @return void
     */
    public function slide_form($url, $customdata) {
        $slideform =  new \slidetype_imageposter\slideform($url, $customdata);
        return $slideform;
    }

    public function export_for_template($output) : array {
        return [];
    }


}


