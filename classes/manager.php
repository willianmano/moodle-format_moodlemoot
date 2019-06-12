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

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use action_link;
use action_menu;
use navigation_node;
use pix_icon;

class manager {
    protected $course;

    /**
     * The manager constructor.
     *
     * @param $course
     */
    public function __construct($course) {
        $this->course = $course;
    }

    /**
     * Get the course header data.
     *
     * @return mixed
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_courseheader_data() {
        global $USER;

        $context = \context_course::instance($this->course->id);

        $data['course'] = $this->course;
        $data['courseheader'] = $this->get_courseheader_url($this->course);

        $data['showgotocourse'] = (isloggedin() && is_enrolled($context, $USER->id) || is_siteadmin($USER->id));

        $url = new moodle_url(course_get_url($this->course), array('page' => 'course'));
        $data['courseurl'] = $url->out();

        $headersettings = $this->context_header_settings_menu()->get_secondary_actions();

        $data['settingsmenu'] = $this->extract_settingsmenu_icons($headersettings);
        $data['hassettingsmenu'] = count($data['settingsmenu']) == 0 ? false : true;

        return $data;
    }

    /**
     * Recover background url to section
     *
     * @return string
     * @throws \dml_exception
     */
    public function get_courseheader_url() {
        global $CFG;

        require_once($CFG->dirroot . '/course/format/moodlemoot/lib.php');

        $defaultimgurl = "$CFG->wwwroot/course/format/moodlemoot/pix/default_course_header.jpg";

        if (!isset($this->course->courseheader)) {
            return $defaultimgurl;
        }

        $file = format_moodlemoot_get_file($this->course->courseheader);

        if (is_null($file)) {
            return $defaultimgurl;
        }

        $pathcomponents = [
            $CFG->wwwroot,
            '/pluginfile.php',
            $file->contextid,
            $file->component,
            $file->filearea,
            $file->itemid,
            $file->filename . '?forcedownload=1'
        ];

        return implode('/', $pathcomponents);
    }

    /**
     * This is an optional menu that can be added to a layout by a theme. It contains the
     * menu for the course administration, only on the course main page.
     *
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function context_header_settings_menu() {
        global $PAGE;

        $context = $PAGE->context;
        $menu = new action_menu();

        $items = $PAGE->navbar->get_items();
        $currentnode = end($items);

        $showcoursemenu = false;
        $showfrontpagemenu = false;
        $showusermenu = false;

        // We are on the course home page.
        if (($context->contextlevel == CONTEXT_COURSE) &&
            !empty($currentnode) &&
            ($currentnode->type == navigation_node::TYPE_COURSE || $currentnode->type == navigation_node::TYPE_SECTION)
        ) {
            $showcoursemenu = true;
        }

        $courseformat = course_get_format($PAGE->course);
        // This is a single activity course format, always show the course menu on the activity main page.
        if (
            $context->contextlevel == CONTEXT_MODULE &&
            !$courseformat->has_view_page()
        ) {
            $PAGE->navigation->initialise();

            $activenode = $PAGE->navigation->find_active_node();
            // If the settings menu has been forced then show the menu.
            if ($PAGE->is_settings_menu_forced()) {
                $showcoursemenu = true;
            } else if (!empty($activenode) && ($activenode->type == navigation_node::TYPE_ACTIVITY ||
                $activenode->type == navigation_node::TYPE_RESOURCE)) {
                    // We only want to show the menu on the first page of the activity. This means
                    // the breadcrumb has no additional nodes.
                if ($currentnode && ($currentnode->key == $activenode->key && $currentnode->type == $activenode->type)) {
                    $showcoursemenu = true;
                }
            }
        }

        // This is the site front page.
        if ($context->contextlevel == CONTEXT_COURSE && !empty($currentnode) && $currentnode->key === 'home') {
            $showfrontpagemenu = true;
        }

        // This is the user profile page.
        if ($context->contextlevel == CONTEXT_USER && !empty($currentnode) && ($currentnode->key === 'myprofile')) {
            $showusermenu = true;
        }

        if ($showfrontpagemenu) {
            $settingsnode = $PAGE->settingsnav->find('frontpage', navigation_node::TYPE_SETTING);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $skipped = $this->build_action_menu_from_navigation($menu, $settingsnode, false, true);

                // We only add a list to the full settings menu if we didn't include every node in the short menu.
                if ($skipped) {
                    $text = get_string('morenavigationlinks');
                    $url = new moodle_url('/course/admin.php', array('courseid' => $PAGE->course->id));
                    $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
                    $menu->add_secondary_action($link);
                }
            }
        } else if ($showcoursemenu) {
            $settingsnode = $PAGE->settingsnav->find('courseadmin', navigation_node::TYPE_COURSE);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $skipped = $this->build_action_menu_from_navigation($menu, $settingsnode, false, true);

                // We only add a list to the full settings menu if we didn't include every node in the short menu.
                if ($skipped) {
                    $text = get_string('morenavigationlinks');
                    $url = new moodle_url('/course/admin.php', array('courseid' => $PAGE->course->id));
                    $link = new action_link($url, $text, null, null, new pix_icon('t/edit', $text));
                    $menu->add_secondary_action($link);
                }
            }
        } else if ($showusermenu) {
            // Get the course admin node from the settings navigation.
            $settingsnode = $PAGE->settingsnav->find('useraccount', navigation_node::TYPE_CONTAINER);
            if ($settingsnode) {
                // Build an action menu based on the visible nodes from this navigation tree.
                $this->build_action_menu_from_navigation($menu, $settingsnode);
            }
        }

        return $menu;
    }

    /**
     * Take a node in the nav tree and make an action menu out of it.
     * The links are injected in the action menu.
     *
     * @param action_menu $menu
     * @param navigation_node $node
     * @param boolean $indent
     * @param boolean $onlytopleafnodes
     * @return boolean nodesskipped - True if nodes were skipped in building the menu
     * @throws \moodle_exception
     */
    protected function build_action_menu_from_navigation(
        action_menu $menu,
        navigation_node $node,
        $indent = false,
        $onlytopleafnodes = false
        ) {

        $skipped = false;
        // Build an action menu based on the visible nodes from this navigation tree.
        foreach ($node->children as $menuitem) {
            if ($menuitem->display) {
                if ($onlytopleafnodes && $menuitem->children->count()) {
                    $skipped = true;
                    continue;
                }

                if ($menuitem->action) {
                    if ($menuitem->action instanceof action_link) {
                        $link = $menuitem->action;
                        // Give preference to setting icon over action icon.
                        if (!empty($menuitem->icon)) {
                            $link->icon = $menuitem->icon;
                        }
                    } else {
                        $link = new action_link($menuitem->action, $menuitem->text, null, null, $menuitem->icon);
                    }
                } else {
                    if ($onlytopleafnodes) {
                        $skipped = true;
                        continue;
                    }
                    $link = new action_link(new moodle_url('#'), $menuitem->text, null, ['disabled' => true], $menuitem->icon);
                }

                if ($indent) {
                    $link->add_class('ml-4');
                }

                if (!empty($menuitem->classes)) {
                    $link->add_class(implode(" ", $menuitem->classes));
                }

                $menu->add_secondary_action($link);
                $skipped = $skipped || $this->build_action_menu_from_navigation($menu, $menuitem, true);
            }
        }

        return $skipped;
    }

    /**
     * @param $items
     * @return array
     * @throws \coding_exception
     */
    private function extract_settingsmenu_icons($items) {
        global $USER, $OUTPUT;

        $context = \context_course::instance($this->course->id);
        $iseditingteacher = user_has_role_assignment($USER->id, 3, $context->id);

        $menu = [];
        if (!is_siteadmin() && !$iseditingteacher) {
            return $menu;
        }

        foreach ($items as $item) {
            $menu[] = [
            'url' => htmlspecialchars_decode($item->url),
            'text' => $item->text,
            'icon' => $OUTPUT->render($item->icon)
            ];
        }

        return $menu;
    }

    /**
     * Get the section by the course module id.
     *
     * @param $cmid
     *
     * @return mixed
     *
     * @throws \dml_exception
     */
    public function get_section_by_cmid($cmid) {
        global $DB;

        $sql = 'SELECT s.* FROM {course_modules} cm
        INNER JOIN {course_sections} s ON cm.section = s.id
        WHERE cm.id = :cmid';

        return $DB->get_record_sql($sql, ['cmid' => $cmid]);
    }
}

