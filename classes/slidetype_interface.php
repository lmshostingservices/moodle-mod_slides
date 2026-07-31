<?php

namespace mod_slides;

interface slidetype_interface {

    public static function slide_name() : string;

    public static function slide_shortname() : string;

    public static function slide_tablename() : string;

    public static function slide_description() : string;

    public static function slide_icon() : string;

    public static function slide_fileareas_list() : array;


    // public static function slidetype_id() : int;

    public function is_user_completed(int $userid, int $instanceid) : bool;

}
