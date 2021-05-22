<?php

namespace format_moodlemoot\output;

use renderable;
use renderer_base;
use templatable;
use format_moodlemoot\manager;

class settingsmenu implements renderable, templatable {
    public $course;

    public function __construct($course) {
        $this->course = $course;
    }

    public function export_for_template(renderer_base $output) {
        $manager = new manager($this->course);

        $headersettingsmenu = $manager->get_header_settings_menu();

        if (!$headersettingsmenu) {
            return [
                'hassettingsmenu' => false,
                'settingsmenu' => null
            ];
        }

        return [
            'hassettingsmenu' => true,
            'settingsmenu' => $headersettingsmenu
        ];
    }
}