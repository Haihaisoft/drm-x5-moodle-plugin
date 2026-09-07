<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'filter_drmx5/accountheading',
        get_string('accountheading', 'filter_drmx5'),
        get_string('accountheading_desc', 'filter_drmx5')
    ));

    $settings->add(new admin_setting_configtext(
        'filter_drmx5/adminemail',
        get_string('adminemail', 'filter_drmx5'),
        get_string('adminemail_desc', 'filter_drmx5'),
        '',
        PARAM_EMAIL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'filter_drmx5/webserviceauthstr',
        get_string('webserviceauthstr', 'filter_drmx5'),
        get_string('webserviceauthstr_desc', 'filter_drmx5'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'filter_drmx5/groupid',
        get_string('groupid', 'filter_drmx5'),
        get_string('groupid_desc', 'filter_drmx5'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $settings->add(new admin_setting_configselect(
        'filter_drmx5/servicearea',
        get_string('servicearea', 'filter_drmx5'),
        get_string('servicearea_desc', 'filter_drmx5'),
        'international',
        [
            'international' => get_string('servicearea_international', 'filter_drmx5'),
            'china' => get_string('servicearea_china', 'filter_drmx5'),
        ]
    ));

    $settings->add(new admin_setting_heading(
        'filter_drmx5/rightsheading',
        get_string('rightsheading', 'filter_drmx5'),
        get_string('rightsheading_desc', 'filter_drmx5')
    ));

    $settings->add(new admin_setting_configselect(
        'filter_drmx5/rightsmode',
        get_string('rightsmode', 'filter_drmx5'),
        get_string('rightsmode_desc', 'filter_drmx5'),
        'post',
        [
            'fixed' => get_string('rightsmode_fixed', 'filter_drmx5'),
            'post' => get_string('rightsmode_post', 'filter_drmx5'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'filter_drmx5/fixedrightsid',
        get_string('fixedrightsid', 'filter_drmx5'),
        get_string('fixedrightsid_desc', 'filter_drmx5'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $licenseurl = new moodle_url('/filter/drmx5/licstore5.php');
    $settings->add(new admin_setting_heading(
        'filter_drmx5/integrationheading',
        get_string('integrationheading', 'filter_drmx5'),
        get_string('integrationheading_desc', 'filter_drmx5', $licenseurl->out(false))
    ));
}
