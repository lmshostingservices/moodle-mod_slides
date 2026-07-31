<?php

namespace mod_slides;

interface dynamicsplit_interface {

    public function split_head_count() : int;

    public function split_content_count() : int;

    public function split_maxinstance_count() : int;

    public function split_dynamic_content($content, $mform) : void;

    public function supports_autosplit() : bool;

}
