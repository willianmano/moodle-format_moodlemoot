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
 * This file contains main class for the course format moodlemoot
 *
 * @package    format_educsaite
 * @copyright  2019 Willian Mano {@link http://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/format/lib.php');

/**
 * Main class for the Topics course format
 */
class format_moodlemoot extends format_base
{
    /**
     * Returns true if this course format uses sections
     * @return bool
     */
    public function uses_sections() {
        return true;
    }

    /**
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     *
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     *
     * @throws moodle_exception
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);

        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                array('context' => context_course::instance($this->courseid)));
        } else {
            return $this->get_default_section_name($section);
        }
    }

    /**
     * Returns the default section name for the moodlemoot course format.
     *
     * If the section number is 0, it will use the string with key = section0name from the course format's lang file.
     * If the section number is not 0, the base implementation of format_base::get_default_section_name which uses
     * the string with the key = 'sectionname' from the course format's lang file + the section number will be used.
     *
     * @param stdClass $section Section object from database or just field course_sections section
     *
     * @return string The default value for the section name.
     *
     * @throws coding_exception
     */
    public function get_default_section_name($section) {
        if ($section->section == 0) {
            // Return the general section.
            return get_string('section0name', 'format_moodlemoot');
        } else {
            // Use format_base::get_default_section_name implementation which
            // will display the section name in "Topic n" format.
            return parent::get_default_section_name($section);
        }
    }

    /**
     * The URL to use for the specified course (with section)
     *
     * @param int|stdClass $section Section object from database or just field course_sections.section
     *     if omitted the course view page is returned
     * @param array $options options for view URL. At the moment core uses:
     *     'navigation' (bool) if true and section has no separate page, the function returns null
     *     'sr' (int) used by multipage formats to specify to which section to return
     *
     * @return null|moodle_url
     *
     * @throws moodle_exception
     */
    public function get_view_url($section, $options = array()) {
        $course = $this->get_course();

        $url = new moodle_url('/course/view.php', array('id' => $course->id));

        return $url;
    }

    /**
     * Returns the information about the ajax support in the given source format
     *
     * The returned object's property (boolean)capable indicates that
     * the course format supports Moodle course ajax features.
     *
     * @return stdClass
     */
    public function supports_ajax() {
        $ajaxsupport = new stdClass();
        $ajaxsupport->capable = true;

        return $ajaxsupport;
    }

    /**
     * Custom action after section has been moved in AJAX mode
     *
     * Used in course/rest.php
     *
     * @return array This will be passed in ajax respose
     *
     * @throws moodle_exception
     */
    public function ajax_section_move() {
        global $PAGE;

        $titles = array();
        $course = $this->get_course();
        $modinfo = get_fast_modinfo($course);
        $renderer = $this->get_renderer($PAGE);
        if ($renderer && ($sections = $modinfo->get_section_info_all())) {
            foreach ($sections as $number => $section) {
                $titles[$number] = $renderer->section_title($section, $course);
            }
        }
        return array('sectiontitles' => $titles, 'action' => 'move');
    }

    /**
     * Returns the list of blocks to be automatically added for the newly created course
     *
     * @return array of default blocks, must contain two keys BLOCK_POS_LEFT and BLOCK_POS_RIGHT
     *     each of values is an array of block names (for left and right side columns)
     */
    public function get_default_blocks() {
        return array(
            BLOCK_POS_LEFT => array(),
            BLOCK_POS_RIGHT => array()
        );
    }

    /**
     * Whether this format allows to delete sections
     *
     * Do not call this function directly, instead use {@link course_can_delete_section()}
     *
     * @param int|stdClass|section_info $section
     * @return bool
     */
    public function can_delete_section($section) {
        return true;
    }

    /**
     * Prepares the templateable object to display section name
     *
     * @param \section_info|\stdClass $section
     * @param bool $linkifneeded
     * @param bool $editable
     * @param null|lang_string|string $edithint
     * @param null|lang_string|string $editlabel
     *
     * @return \core\output\inplace_editable
     *
     * @throws coding_exception
     */
    public function inplace_editable_render_section_name($section, $linkifneeded = true,
                                                         $editable = null, $edithint = null, $editlabel = null) {
        if (empty($edithint)) {
            $edithint = new lang_string('editsectionname', 'format_moodlemoot');
        }

        if (empty($editlabel)) {
            $title = get_section_name($section->course, $section);
            $editlabel = new lang_string('newsectionname', 'format_moodlemoot', $title);
        }

        return parent::inplace_editable_render_section_name($section, $linkifneeded, $editable, $edithint, $editlabel);
    }

    /**
     * Indicates whether the course format supports the creation of a news forum.
     *
     * @return bool
     */
    public function supports_news() {
        return true;
    }

    /**
     * Returns whether this course format allows the activity to
     * have "triple visibility state" - visible always, hidden on course page but available, hidden.
     *
     * @param stdClass|cm_info $cm course module (may be null if we are displaying a form for adding a module)
     * @param stdClass|section_info $section section where this module is located or will be added to
     * @return bool
     */
    public function allow_stealth_module_visibility($cm, $section) {
        // Allow the third visibility state inside visible sections or in section 0.
        return !$section->section || $section->visible;
    }

    /**
     * Section action.
     *
     * @param section_info|stdClass $section
     * @param string $action
     * @param int $sr
     *
     * @return array|stdClass|null
     *
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public function section_action($section, $action, $sr) {
        global $PAGE;

        if ($section->section && ($action === 'setmarker' || $action === 'removemarker')) {
            // Format 'moodlemoot' allows to set and remove markers in addition to common section actions.
            require_capability('moodle/course:setcurrentsection', context_course::instance($this->courseid));
            course_set_marker($this->courseid, ($action === 'setmarker') ? $section->section : 0);
            return null;
        }

        // For show/hide actions call the parent method and return the new content for .section_availability element.
        $rv = parent::section_action($section, $action, $sr);
        $renderer = $PAGE->get_renderer('format_moodlemoot');
        $rv['section_availability'] = $renderer->section_availability($this->get_section($section));
        return $rv;
    }

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of configuration settings
     * @since Moodle 3.5
     */
    public function get_config_for_external() {
        // Return everything (nothing to hide).
        return $this->get_format_options();
    }

    /**
     * Definitions of the additional options that this course format uses for course
     *
     * @param bool $foreditform
     * @return array of options
     * @throws coding_exception
     */
    public function course_format_options($foreditform = false) {
        $courseformat = array(
            'courseheader' => array(
                'type' => PARAM_FILE,
                'label' => get_string('courseheader', 'format_moodlemoot'),
                'help' => 'courseheader',
                'element_type' => 'filepicker',
                'element_attributes' => array(
                    null,
                    array(
                        'maxfiles' => 1,
                        'accepted_types' => array('.jpg', '.png')
                    )
                ),
            ),
            'price' => array(
                'type' => PARAM_TEXT,
                'label' => get_string('price', 'format_moodlemoot'),
                'help' => 'price',
                'element_type' => 'text',
                'default' => '',
                'cache' => true,
                'cachedefault' => 0,
            ),
            'enrollperiod' => array(
                'type' => PARAM_CLEANHTML,
                'label' => get_string('enrollperiod', 'format_moodlemoot'),
                'help' => 'enrollperiod',
                'element_type' => 'textarea',
                'default' => '',
                'cache' => true,
                'cachedefault' => '',
            ),
            'date' => array(
                'type' => PARAM_CLEANHTML,
                'label' => get_string('date', 'format_moodlemoot'),
                'help' => 'date',
                'element_type' => 'textarea',
                'default' => '',
                'cache' => true,
                'cachedefault' => '',
            ),
            'place' => array(
                'type' => PARAM_CLEANHTML,
                'label' => get_string('place', 'format_moodlemoot'),
                'help' => 'place',
                'element_type' => 'textarea',
                'default' => '',
                'cache' => true,
                'cachedefault' => '',
            ),
            'placemapurl' => array(
                'type' => PARAM_CLEANHTML,
                'label' => get_string('placemapurl', 'format_moodlemoot'),
                'help' => 'placemapurl',
                'element_type' => 'text',
                'default' => '',
                'cache' => true,
                'cachedefault' => '',
            )
        );

        return $courseformat;
    }

    /**
     * Handle the course format form before saving data and files
     *
     * @param array $data
     * @param array $files
     * @param array $errors
     * @return array
     * @throws dml_exception
     */
    public function edit_form_validation($data, $files, $errors) {
        global $USER;

        $courseid = $data['id'];
        $context = $courseid != 0 ? \context_course::instance($courseid) : \context_user::instance($USER->id);

        $courseheader = file_get_submitted_draft_itemid('courseheader');

        file_save_draft_area_files($courseheader, $context->id, 'format_moodlemoot', 'moodlemoot', $courseheader);

        return parent::edit_form_validation($data, $files, $errors);
    }

    /**
     * Get overview page data
     * @return stdClass data for course welcome message
     */
    public function get_introduction_data() {
        $introduction['price'] = !empty($this->course->price) ? $this->course->price : null;
        $introduction['enrollperiod'] = !empty($this->course->enrollperiod) ? $this->course->enrollperiod : null;
        $introduction['date'] = !empty($this->course->date) ? $this->course->date : null;
        $introduction['place'] = !empty($this->course->place) ? $this->course->place : null;
        $introduction['placemapurl'] = !empty($this->course->placemapurl) ? $this->course->placemapurl : null;

        return $introduction;
    }
}

/**
 * Implements callback inplace_editable() allowing to edit values in-place
 *
 * @param string $itemtype
 * @param int $itemid
 * @param mixed $newvalue
 * @return \core\output\inplace_editable
 */
function format_moodlemoot_inplace_editable($itemtype, $itemid, $newvalue) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/course/lib.php');

    if ($itemtype === 'sectionname' || $itemtype === 'sectionnamenl') {
        $section = $DB->get_record_sql(
            'SELECT s.* FROM {course_sections} s JOIN {course} c ON s.course = c.id WHERE s.id = ? AND c.format = ?',
            array($itemid, 'moodlemoot'), MUST_EXIST);
        return course_get_format($section->course)->inplace_editable_update_section_name($section, $itemtype, $newvalue);
    }
}

/**
 * Serve files for the
 *
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @throws dml_exception
 */
function format_moodlemoot_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    // Default params.
    $itemid = $args[0];
    $filter = 0;
    $forcedownload = true;

    if (array_key_exists('filter', $options)) {
        $filter = $options['filter'];
    }

    // Recover file and stored_file objects.
    $file = format_moodlemoot_get_file($itemid);

    if (is_null($file)) {
        send_file_not_found();
    }

    $fs = get_file_storage();
    $storedfile = $fs->get_file_by_hash($file->pathnamehash);

    if (!$storedfile) {
        send_file_not_found();
    }

    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'moodlemoot') {
        send_stored_file($storedfile, 86400, $filter, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_COURSE && $filearea === 'moodlemoot') {
        send_stored_file($storedfile, 86400, $filter, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_COURSECAT && $filearea === 'moodlemoot') {
        send_stored_file($storedfile, 86400, $filter, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_USER && $filearea === 'moodlemoot') {
        send_stored_file($storedfile, 86400, $filter, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_BLOCK && $filearea === 'moodlemoot') {
        send_stored_file($storedfile, 86400, $filter, $forcedownload, $options);
    }

    if ($context->contextlevel == CONTEXT_MODULE && $filearea === 'moodlemoot') {
        send_stored_file($storedfile, 86400, $filter, $forcedownload, $options);
    }
}

/**
 * Get a course related file
 *
 * @param int!$id            File ID
 * @return stdClass|null    File object or null if file not exists
 * @throws dml_exception
 */
function format_moodlemoot_get_file($id) {
    global $DB;

    $file = null;
    $entries = $DB->get_records('files', array('itemid' => $id), 'id', '*');

    foreach ($entries as $entry) {
        if (strlen($entry->filename) > 1 && !is_null($entry->mimetype)) {
            $file = $entry;
        }
    }

    return $file;
}
