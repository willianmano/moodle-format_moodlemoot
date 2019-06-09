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

namespace format_moodlemoot;

use moodle_url;

defined('MOODLE_INTERNAL') || die;

class util {
    public static function get_course_redirect_url($course) {
        $lastvitedmodule = self::get_last_visited_coursemodule();

        if ($lastvitedmodule) {
            return $lastvitedmodule;
        }

        return self::get_first_course_activity($course);
    }

    public static function get_last_visited_coursemodule() {
        global $USER, $DB;

        $sql = "SELECT id, objecttable, contextinstanceid, courseid
                FROM {logstore_standard_log}
                WHERE target = :target AND userid = :userid
                ORDER BY id DESC LIMIT 1";

        $params = ['target' => 'course_module', 'userid' => $USER->id];

        $lastvitedmodule = $DB->get_record_sql($sql, $params);

        if (!$lastvitedmodule) {
            return false;
        }

        return new moodle_url('/mod/' . $lastvitedmodule->objecttable . '/view.php', ['id' => $lastvitedmodule->contextinstanceid]);
    }

    public static function get_first_course_activity($course) {
        $sections = course_get_format($course)->get_sections();

        foreach ($sections as $section) {
            foreach ($section->modinfo->cms as $coursemodule) {
                if ($coursemodule->visible) {
                    return $coursemodule->url;
                }
            }
        }

        return new moodle_url('/course/view.php', ['id' => $course->id, 'page' => 'introduction']);
    }
}