<?php
// This file is part of Moodle - http://moodle.org/

namespace filter_drmx5\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves DRM product IDs to Moodle courses and checks active enrolment.
 */
class course_authorizer {
    /**
     * Find the first requested course in which the user is actively enrolled.
     *
     * @param string $productids Hyphen-separated Moodle course record IDs.
     * @param \stdClass $user Moodle user.
     * @return \stdClass|false The matched course record, or false.
     */
    public static function get_accessible_course($productids, \stdClass $user) {
        global $DB;

        $tokens = preg_split('/\s*-\s*/', trim($productids), -1, PREG_SPLIT_NO_EMPTY);
        foreach (array_unique($tokens) as $token) {
            if (!ctype_digit($token) || (int)$token <= 0) {
                continue;
            }

            $id = (int)$token;
            $course = $DB->get_record('course', ['id' => $id], 'id,startdate,enddate', IGNORE_MISSING);
            if (!$course) {
                continue;
            }
            $context = \context_course::instance($id, IGNORE_MISSING);

            if ($context && is_enrolled($context, $user, '', true)) {
                return $course;
            }
        }

        return false;
    }

    /**
     * Check whether a user is enrolled in at least one requested course.
     *
     * @param string $productids Hyphen-separated Moodle course record IDs.
     * @param \stdClass $user Moodle user.
     * @return bool
     */
    public static function user_has_access($productids, \stdClass $user) {
        return (bool)self::get_accessible_course($productids, $user);
    }
}
