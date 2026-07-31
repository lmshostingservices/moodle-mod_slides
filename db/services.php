<?php

$functions = [

        'mod_slides_update_fontsize' => [
            'classname' => 'mod_slides\external',
            'methodname' => 'update_fontsize',
            'description' => 'Update the font size automatically for the text image',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true,
        ],

        'mod_slides_update_slidecompletion' => [
            'classname' => 'mod_slides\external',
            'methodname' => 'update_slidecompletion',
            'description' => 'Update the completion of slide in slides module.',
            'type' => 'write',
            'ajax' => true,
            'loginrequired' => true,
        ],
];
