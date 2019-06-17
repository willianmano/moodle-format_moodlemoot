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

namespace format_moodlemoot\output\pages;

use format_moodlemoot\manager;
use format_moodlemoot\output\pages\interfaces\page;
use moodle_page;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/enrollib.php');

/**
 * Introduction renderer
 *
 * @package    format_moodlemoot
 * @copyright  2019 Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class introduction extends \format_moodlemoot_renderer {

    protected $course;

    /**
     * format_moodlemoot_overview_renderer constructor.
     *
     * @param moodle_page $page
     * @param $target
     * @param $course
     */
    public function __construct(moodle_page $page, $target, $course) {
        $this->course = $course;

        parent::__construct($page, $target);
    }

    /**
     * Render page
     *
     * @return string Output
     *
     * @throws \moodle_exception
     */
    public function render_page() {
        echo $this->render_page_header();

        echo $this->render_page_body();
    }

    /**
     * Renders the page header.
     *
     * @return bool|string
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function render_page_header() {
        $manager = new manager($this->course);

        $data = $manager->get_courseheader_data();
        $data['introduction'] = true;

        return $this->render_from_template('format_moodlemoot/header_introduction', $data);
    }

    /**
     * Renders the page body.
     *
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_page_body() {
        $data = course_get_format($this->course)->get_introduction_data();

        $data['course'] = $this->course;

        $coursecat = \core_course_category::get($data['course']->category);
        $coursecatstr = $coursecat->get_formatted_name();
        $data['course']->categorystr = $coursecatstr;

        $data['enrolmentinstances'] = $this->get_enrolment_instances();

        ob_start();
        $this->print_multiple_section_page($this->course, null, null, null, null, $this->is_course_enrolled());
        $sectionshtml = ob_get_contents();
        ob_end_clean();

        $data['sections'] = $sectionshtml;
        $data['isenrolled'] = $this->is_course_enrolled();

        return $this->render_from_template('format_moodlemoot/body_introduction', $data);
    }

    protected function get_enrolment_instances() {
        $data['hasenroldata'] = false;

        if (isguestuser()) {
            $data['enrolinstances'][] = [
                'name' => get_string('dologin', 'format_moodlemoot')
            ];

            return $data;
        }

        // Enrolled users doesn't need see this link anymore.
        if ($this->is_course_enrolled()) {
            return false;
        }

        // For logged in users, we must check course configs.
        $instances = enrol_get_instances($this->course->id, true);

        $enrolinstances = [];
        foreach ($instances as $instance) {
            if ($this->is_enrolinstance_enabled($instance)) {
                $enrolinstances[] = [
                    'id' => $instance->id,
                    'enrol' => $instance->enrol,
                    'courseid' => $instance->courseid,
                    'name' => $instance->name ?: get_string('enrolme', 'enrol_self'),
                    'cost' => $instance->cost ? number_format($instance->cost, 2, ',', '.') : false,
                    'currency' => $instance->currency ?: false,
                    'class' => $instance->enrol == 'self' ? 'btn-success' : 'btn-primary'
                ];
            }
        }

        if (!$enrolinstances) {
            $data['enrolinstances'][] = [
                'name' => get_string('enrolnotavailable', 'format_moodlemoot')
            ];

            return $enrolinstances;
        }

        $data['hasenroldata'] = true;
        $data['enrolinstances'] = $enrolinstances;

        return $data;
    }

    protected function is_enrolinstance_enabled($instance) {
        if (!in_array($instance->enrol, ['self', 'pagseguro'])) {
            return false;
        }

        if ($instance->enrolstartdate > 0 && $instance->enrolstartdate > time()) {
            return false;
        }

        if ($instance->enrolenddate > 0 && $instance->enrolenddate < time()) {
            return false;
        }

        return true;
    }

    /**
     * Returns if the whether the user is enrolled in the course or not.
     *
     * @return bool
     */
    public function is_course_enrolled() {
        global $USER;

        // Enrolled users doesn't need see this link anymore.
        $coursecontext = \context_course::instance($this->course->id);
        if (is_enrolled($coursecontext, $USER->id)) {
            return true;
        }

        return false;
    }
}
