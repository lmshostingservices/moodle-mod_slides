<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Nct tabs module global config.
 *
 * @package    mod_slides
 * @copyright  2024 National Corporate Training Pty Ltd (https://nct.net.au/) <believe@nct.net.au>
 * @author     LMSACE Dev Team <lmsace.com>
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    require_once("$CFG->libdir/resourcelib.php");

    $settings->add(new admin_setting_heading('slidesdefaults', get_string('modeditdefaults', 'admin'), get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configtext(
        'slides/imageheight',
        get_string('imageheight', 'slides'),
        get_string('imageheightdesc', 'slides'), 600, PARAM_INT)
    );

    // FFmpeg path.
    $ffmpegpath = mod_slides\autohelper::get_ffmpeg_path();
    $settings->add(new admin_setting_configexecutable(
        'slides/ffmpegpath',
        get_string('ffmpegpath', 'slides'),
        get_string('ffmpegpathdesc', 'slides', ['p' => $ffmpegpath, 'e' => mod_slides\autohelper::is_ffmpeg_installed() ? '' : 'not']), '')
    );

    // Time duration split config.
    $options = [
        mod_slides\autohelper::SILENCE_ONESECOND => get_string('seconds', 'slides', '1'),
        mod_slides\autohelper::SILENCE_TWOSECOND => get_string('seconds', 'slides', '2'),
        mod_slides\autohelper::SILENCE_THREESECOND => get_string('seconds', 'slides', '3'),
    ];
    $settings->add(new admin_setting_configselect(
        'slides/audiosilenceduration',
        get_string('silenceduration', 'slides'),
        get_string('silencedurationdesc', 'slides'), mod_slides\autohelper::SILENCE_ONESECOND, $options)
    );
}
