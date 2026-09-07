<?php
// This file is part of Moodle - http://moodle.org/

namespace filter_drmx5\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Describes personal data sent to the external DRM-X service.
 */
class provider implements \core_privacy\local\metadata\provider {
    /**
     * Describe the external data exchange.
     *
     * @param \core_privacy\local\metadata\collection $collection Metadata collection.
     * @return \core_privacy\local\metadata\collection
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection) :
            \core_privacy\local\metadata\collection {
        $collection->add_external_location_link('drmx5', [
            'username' => 'privacy:metadata:drmx5:username',
            'email' => 'privacy:metadata:drmx5:email',
            'fullname' => 'privacy:metadata:drmx5:fullname',
            'ip' => 'privacy:metadata:drmx5:ip',
            'clientinfo' => 'privacy:metadata:drmx5:clientinfo',
        ], 'privacy:metadata:drmx5');
        return $collection;
    }
}
