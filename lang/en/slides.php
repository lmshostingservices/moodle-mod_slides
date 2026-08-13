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
 * mod_slides file.
 *
 * @package    mod_slides
 * @copyright  2026 LMS-Labs
 * @license    http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Slides';
$string['modulename'] = 'Slides';
$string['modulenameplural'] = 'Slides';


$string['subplugintype_slidetype'] = 'Slide type plugin';
$string['subplugintype_slidetype_plural'] = 'Slide type plugins';

// ...config strings
$string['completiondetail:reachend'] = 'Reach the end of the slide to complete';
$string['completionendreach'] = 'Completed when user finish the slide';
$string['completionendreach_help'] = 'User should reach the end of slide to finish this activity.';
$string['pluginadministration'] = 'Slide editor';
$string['slideseditor'] = 'Slide editor';
$string['slidesettings'] = 'Slide general settings';
$string['addslide'] = 'Add slide';
$string['deletechecktype'] = 'Are you sure! you want to <label class="text-danger">delete</label> the slide <b> {$a->slide} </b>';
$string['noslidesconfigured'] = 'Slides are not yet configured.';
$string['listenoptions'] = 'Listen options';
$string['listenoptionsaudio'] = 'Audios';
$string['title'] = 'Title';

// ...form strings.
$string['slideupdate'] = 'Update';
$string['slidecreate'] = 'Create';
$string['generaltitle'] = 'Add/Edit slide';
$string['slidetitle'] = 'Slide title';
$string['visibility'] = 'Visibility';
$string['visibility_help'] = 'Show/hide the slide from the students';
$string['createnewslide'] = 'Create new slide';
$string['hidden'] = 'Hide';
$string['animationtitle'] = 'Animations';
$string['appearancetitle'] = 'Appearance';
$string['imageanimation'] = 'Image animation';
$string['animation'] = 'Content animations';
$string['heading'] = 'Heading';
$string['duplicate'] = 'Duplicate';
$string['move'] = 'Move Slide';

$string['headingfonts'] = 'Heading font style';
$string['headingfontsize'] = 'Heading font size';
$string['contentfonts'] = 'Content font style';
$string['autotextsize'] = 'Auto size the content text';
$string['contentfontsize'] = 'Content font size';
$string['staticfontstylesize'] = 'Font style and size';
$string['feedbackfontsize'] = 'Feedback font size';
$string['feedbackfonts'] = 'Feedback font style';

// ...Listen options strings.
$string['forceaudio'] = 'Audio';
$string['forceduration'] = 'Duration';
$string['forcelisten'] = ' Listen Options';
$string['forceseconds'] = 'Seconds';
$string['listenduration'] = 'Duration Length (s)';
$string['feedbackinfo:listen'] = 'Please listen to the feedback to proceed with the attempt.';
$string['feedbackinfo:read'] = 'Please read the feedback to proceed with the attempt';
$string['forcenextconfig'] = 'Hide/disable next button';
$string['listenaudio'] = 'Listen audio';
$string['forceaudiostr'] = 'Click to display text and listen to voiceover';
$string['forcedurationstr'] = 'Click to display and read text';

// ...Introduction slide strings.
$string['content'] = 'Intro content';
$string['introimage'] = 'Intro image';
$string['introimageheight'] = 'Intro image height';

// ...Image slide strings.
$string['posterimage'] = 'Poster image';

// ...Video slide strings.
$string['slidevideo'] = 'Slide video';
$string['videourl'] = 'Video URL';

// ...Error strings.
$string['invalidcoursemodule'] = 'Invalid course module id used.';
$string['moduleinstancemissing'] = 'Module instance is missing.';
$string['containerheight'] = 'Height of the container';
$string['containerheight_help'] = 'Set a fixed height for the container to prevent it from resizing as slides change. Leave blank to allow automatic adjustment based on the tallest slide.';
$string['listenaudio'] = 'Click to display text and listen to voiceover';

// Animation options.
$string['slideinup'] = 'SlideInUp';
$string['slideinright'] = 'SlideInRight';
$string['slideinleft'] = 'SlideInLeft';
$string['slideindown'] = 'SlideInDown';
$string['zoomin'] = 'ZoomIn';
$string['zoominup'] = 'ZoomInUp';
$string['bouncein'] = 'BounceIn';
$string['bounceindown'] = 'BounceInDown';
$string['bounceinleft'] = 'BounceInLeft';
$string['bounceinright'] = 'BounceInRight';
$string['bounceinup'] = 'BounceInUp';
$string['fadein'] = 'FadeIn';
$string['fadeinup'] = 'FadeInUp';
$string['fadeindown'] = 'FadeInDown';
$string['fadeinleft'] = 'FadeInLeft';
$string['fadeinright'] = 'FadeInRight';
$string['backinup'] = 'BackInUp';

// Auto text split.
$string['dynamiccontent'] = 'Generated content';
$string['updatedynamic'] = 'Split both';
$string['autosplit'] = 'Auto split content';
$string['staticfontstylesize'] = 'Font style and size';
$string['autotextsize'] = 'Auto size the content text';
$string['splitcontent'] = 'Split content';
$string['splitaudio'] = 'Split audio';
$string['audiosplitmethod'] = 'Split audio based on';
$string['autosplitaudio'] = 'Auto - silence';
$string['splitaudiocustom'] = 'Custom time';
$string['installffmpeg'] = 'Please install ffmpeg on your server to use the audio split feature.';
$string['dynamicaudio'] = 'Dynamic audio';
$string['dynamicaudioinstruction'] = 'Note: Set time gap between blocks to {$a} seconds in the voiceover software you use. We recommend MicMonster';
$string['timeaudio'] = 'Audio {$a} start-end time';

$string['audiosplit'] = 'FFmpeg Path Configuration';
$string['audiosplitdesc'] = 'This section contains config related to the audio split method';
$string['ffmpegpath'] = 'FFmpeg path';
$string['ffmpegpathdesc'] = 'Please enter the path where FFmpeg is installed on your server. <br><b>Ex:</b> yourpath/ffmpeg/bin/ffmpeg.exe <br>
The current FFmpeg path is <b> {$a->p} </b>, and it is <b> {$a->e} executable</b> .
<p>
Ensure that the provided path is accurate to allow proper audio processing within the system.</p>';
$string['seconds'] = '{$a} Seconds';
$string['silenceduration'] = 'Silence Duration for Audio Splitting';
$string['silencedurationdesc'] = 'Specify the duration of silence (in seconds) between segments to be used for splitting the audio.';

$string['imageheight'] = 'Image height';
$string['imageheightdesc'] = 'Enter the height of the image to add element';

$string['onlynumeric'] = 'Only numberic values allowed';
$string['matchingactivity'] = 'Matching activity';
$string['flipactivity'] = 'Flip Cards Activity';
$string['none'] = 'None';
$string['mute'] = 'Mute';
$string['videomute'] = 'Mute video';
$string['listenaudioconfig'] = 'Listen audio';
$string['previous'] = 'Previous';
$string['next'] = 'Next';
$string['slide'] = 'Slide';
$string['finish'] = 'Finish';
$string['slides:addinstance'] = 'Add a new Slides activity';
$string['slides:view'] = 'View Slides activity';
$string['slides:viewslideeditor'] = 'View and use the slide editor';

$string['privacy:metadata'] = 'The mod_slides plugin does not store any personal data.';

$string['cachedef_webfonts'] = 'Web fonts used by the Slides activity';
