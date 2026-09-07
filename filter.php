<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

// Moodle 4.4 and earlier look for this legacy class name. Moodle 4.5 and later
// load the namespaced class directly from classes/text_filter.php.
class_alias(\filter_drmx5\text_filter::class, \filter_drmx5::class);
