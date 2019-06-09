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

/**
 * Course renderer
 *
 * @package    format_moodlemoot
 * @copyright  2019 Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends base implements page {

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

        $headersettings = $this->context_header_settings_menu()->get_secondary_actions();

        $data['settingsmenu'] = $this->extract_settingsmenu_icons($headersettings);
        $data['hassettingsmenu'] = count($data['settingsmenu']) == 0 ? false : true;

        return $this->render_from_template('format_moodlemoot/header_course', $data);
    }

    /**
     * Renders the page body.
     *
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_page_body() {
        global $PAGE;

        $data = new \stdClass();

        $context = \context_course::instance($this->course->id);
        $editmode = $PAGE->user_is_editing() && has_capability('moodle/course:update', $context);

        $sections = course_get_format($this->course)->get_sections();

        foreach ($sections as $section) {
            $data->sections[] = $this->get_section_data($section);
        }

        if ($editmode) {
            $data->changenumsections = $this->change_number_sections($this->course, 0);
        }

        return $this->render_from_template('format_moodlemoot/body_course', $data);
    }

    protected function get_section_data(\section_info $section) {
        global $PAGE;

        $context = \context_course::instance($this->course->id);
        $editmode = $PAGE->user_is_editing() && has_capability('moodle/course:update', $context);

        // Section is not visible.
        if (!$section->uservisible) {
            return;
        }

        // Section data object.
        $data = new \stdClass();
        $sectiondata = new \stdClass();

        // Basic sections info.
        $sectionnum = $section->section;

        $sectiondata->url = course_get_url($this->course, $section, array('navigation' => true))->out();
        $sectiondata->id = $sectionnum;
        $sectiondata->visible = $section->visible;
        $sectiondata->editmode = $editmode;
        $sectiondata->title = !empty($section->name) ? $section->name : get_section_name($this->course, $section);

        $sectiondata->cm_list = $this->courserenderer->course_section_cm_list($this->course, $sectionnum);

        if ($editmode) {
            $controls = $this->formatrenderer->get_section_edit_controls($this->course, $section);

            $addresourcecontrol = $this->courserenderer->course_section_add_cm_control($this->course, $section->section);

            $sectiondata->controls = '';
            foreach ($controls as $control) {
                $sectiondata->controls .= is_string($controls) ? $controls : $this->render($control);
            }

//            echo "<pre>";
//            print_r($controls);
//            exit;

            $sectiondata->cm_control = $addresourcecontrol;
        }

        $data->section = $sectiondata;
        $data->completiontracking = $this->course->enablecompletion;

        return $this->render_from_template('format_moodlemoot/body_course_section', $data);
    }
}