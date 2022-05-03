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
 * Educsaite course format.
 *
 * @package    format_moodlemoot
 * @copyright  2019 Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$page = optional_param('page', null, PARAM_TEXT);

$context = context_course::instance($course->id);
// Retrieve course format option fields and add them to the $course object.
$course = course_get_format($course)->get_course();
$course->coursedisplay = COURSE_DISPLAY_SINGLEPAGE;
$course->hiddensections = true;

// Make sure section 0 is created.
course_create_sections_if_missing($course, 0);

$caneditcourse = false;
if (!has_capability('moodle/course:update', $context)) {
    $caneditcourse = true;
}
// If is guest user or non-enrolled, can only access introduction page.
// Don't allow access for anyone to anywhere in course.
if (isguestuser($USER) || (!is_enrolled($context, $USER) && !$caneditcourse)) {
    $page = 'introduction';
}

if (!$page && (is_enrolled($context, $USER->id))) {
    $page = 'introduction';
}

if (!$page && ($caneditcourse || $PAGE->user_is_editing())) {
    $page = 'course';
}

if ($page != 'introduction' && (!$caneditcourse)) {
    $page = 'introduction';
}

$url = new \moodle_url(course_get_url($course), array('page' => $page));
$PAGE->set_url($url);

$renderer = $PAGE->get_renderer('format_moodlemoot');

switch ($page) {
    case 'course':
        $renderer->print_multiple_section_page($course, null, null, null, null);
    break;
    case 'introduction':
    default:
        $renderer->introduction_page($course);
    break;
}

if ($page == 'course' || $page == 'introduction') {
    // Include course format js module.
    $PAGE->requires->js('/course/format/moodlemoot/format.js');
}