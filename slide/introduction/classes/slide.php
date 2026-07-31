<?php

namespace slidetype_introduction;


use mod_slides\slideinstance as Mod_slidesSlideinstance;


class slide extends \mod_slides\slidetype {

    public const SHORTNAME = 'introduction';

    public const LISTORDER = 1;

    public static function slide_shortname(): string {
        return self::SHORTNAME;
    }

    public static function slide_description(): string {
        return get_string('slidedescription', 'slidetype_introduction');
    }

    public static function slide_name(): string {
        return get_string('pluginname', 'slidetype_introduction');
    }

    public static function slide_icon(): string {
        global $OUTPUT;

        return $OUTPUT->image_icon('monologo', get_string('pluginname', 'slidetype_introduction'), 'slidetype_introduction', ['class' => 'icon']);
    }

    public static function slide_fileareas_list() : array {
        return ['introimage'];
    }

    public static function backup_files() : array {
        $list = ['slidetype_introduction', 'introimage', 'introduction_listenaudio_1'];
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

    public function supports_autosplit() : bool {
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
        $slideform =  new \slidetype_introduction\slideform($url, $customdata);
        return $slideform;
    }


    public function export_for_template($output) : array {
        return [];
    }


}


