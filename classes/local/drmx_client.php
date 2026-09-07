<?php
// This file is part of Moodle - http://moodle.org/

namespace filter_drmx5\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Small wrapper around the DRM-X 5.0 SOAP service.
 */
class drmx_client {
    /** DRM-X 5.0 international service WSDL. */
    const WSDL_INTERNATIONAL = 'https://5.drm-x.com/haihaisoftlicenseservice.asmx?wsdl';

    /** DRM-X 5.0 China service WSDL. */
    const WSDL_CHINA = 'https://5.drm-x.cn/haihaisoftlicenseservice.asmx?wsdl';

    /** @var \SoapClient */
    private $soap;

    /** @var string */
    private $adminemail;

    /** @var string */
    private $authstring;

    /** @var string */
    private $groupid;

    /**
     * @param string $adminemail DRM-X administrator email.
     * @param string $authstring DRM-X web service authorisation string.
     * @param string $groupid DRM-X group ID.
     * @param string $servicearea DRM-X service area: international or china.
     */
    public function __construct($adminemail, $authstring, $groupid, $servicearea = 'international') {
        if (!extension_loaded('soap')) {
            throw new \RuntimeException(get_string('soapmissing', 'filter_drmx5'));
        }
        $this->adminemail = $adminemail;
        $this->authstring = $authstring;
        $this->groupid = $groupid;
        $wsdl = $servicearea === 'china' ? self::WSDL_CHINA : self::WSDL_INTERNATIONAL;
        $this->soap = new \SoapClient($wsdl, [
            'trace' => false,
            'exceptions' => true,
            'connection_timeout' => 20,
            'cache_wsdl' => WSDL_CACHE_BOTH,
        ]);
    }

    /**
     * Create the DRM-X user if it does not already exist.
     *
     * @param \stdClass $user Moodle user record.
     * @return bool True when the user already existed, false when newly created.
     * @throws \RuntimeException On an unexpected service result.
     */
    public function ensure_user_exists(\stdClass $user) {
        $response = $this->soap->__soapCall('CheckUserExists', ['parameters' => [
            'UserName' => $user->username,
            'AdminEmail' => $this->adminemail,
            'WebServiceAuthStr' => $this->authstring,
        ]]);
        $exists = isset($response->CheckUserExistsResult) ? trim((string)$response->CheckUserExistsResult) : '';
        if (strcasecmp($exists, 'True') === 0 || $exists === '1') {
            return true;
        }
        if (strcasecmp($exists, 'False') !== 0 && $exists !== '0') {
            throw new \RuntimeException(get_string('checkuserfailed', 'filter_drmx5', $exists));
        }

        $fullname = fullname($user);
        $response = $this->soap->__soapCall('AddNewUser', ['parameters' => [
            'AdminEmail' => $this->adminemail,
            'WebServiceAuthStr' => $this->authstring,
            'GroupID' => $this->groupid,
            'UserLoginName' => $user->username,
            'UserPassword' => 'N/A',
            'UserEmail' => $user->email,
            'UserFullName' => $fullname !== '' ? $fullname : 'N/A',
            'Title' => 'N/A',
            'Company' => 'N/A',
            'Address' => 'N/A',
            'City' => !empty($user->city) ? $user->city : 'N/A',
            'Province' => 'N/A',
            'ZipCode' => 'N/A',
            'Phone' => !empty($user->phone1) ? $user->phone1 : 'N/A',
            'CompanyURL' => 'N/A',
            'SecurityQuestion' => 'N/A',
            'SecurityAnswer' => 'N/A',
            'IP' => getremoteaddr(),
            'Money' => '0',
            'BindNumber' => '1',
            'IsApproved' => 'yes',
            'IsLockedOut' => 'no',
        ]]);
        $result = isset($response->AddNewUserResult) ? trim((string)$response->AddNewUserResult) : '';
        if ($result !== '1') {
            throw new \RuntimeException(get_string('adduserfailed', 'filter_drmx5', $result));
        }

        return false;
    }

    /**
     * Check whether an existing DRM-X user is revoked or locked out.
     *
     * @param string $username DRM-X login name.
     * @return string Empty when active, otherwise a language string key.
     * @throws \RuntimeException When the service response cannot be interpreted.
     */
    public function get_user_block_reason($username) {
        $response = $this->soap->__soapCall('CheckUserIsRevoked', ['parameters' => [
            'AdminEmail' => $this->adminemail,
            'WebServiceAuthStr' => $this->authstring,
            'UserLoginName' => $username,
        ]]);
        if (!isset($response->CheckUserIsRevokedResult)) {
            throw new \RuntimeException(get_string('checkrevokedfailed', 'filter_drmx5'));
        }
        if ($this->is_true($response->CheckUserIsRevokedResult)) {
            return 'userrevoked';
        }

        $response = $this->soap->__soapCall('GetUserDetails', ['parameters' => [
            'AdminEmail' => $this->adminemail,
            'WebServiceAuthStr' => $this->authstring,
            'UserLoginName' => $username,
        ]]);
        if (!isset($response->GetUserDetailsResult) || !is_object($response->GetUserDetailsResult) ||
                !property_exists($response->GetUserDetailsResult, 'IsLockedOut')) {
            throw new \RuntimeException(get_string('getuserdetailsfailed', 'filter_drmx5'));
        }
        if ($this->is_true($response->GetUserDetailsResult->IsLockedOut)) {
            return 'userlocked';
        }

        return '';
    }

    /**
     * Interpret boolean values returned by the SOAP service.
     *
     * @param mixed $value SOAP value.
     * @return bool
     */
    private function is_true($value) {
        if ($value === true || $value === 1) {
            return true;
        }
        return in_array(strtolower(trim((string)$value)), ['true', '1', 'yes'], true);
    }

    /**
     * Update the fixed DRM-X permission using the policy from the reference code.
     *
     * @param string $rightsid Fixed rights ID.
     * @param string $watermarktext User-specific watermark text.
     * @param int $coursestartdate Moodle course start timestamp.
     * @param int $courseenddate Moodle course end timestamp.
     * @throws \RuntimeException On failure.
     */
    public function update_rights($rightsid, $watermarktext, $coursestartdate, $courseenddate) {
        $begindate = userdate((int)$coursestartdate, '%Y/%m/%d', 99, false, false);
        $expirationdate = userdate((int)$courseenddate, '%Y/%m/%d', 99, false, false);
        $response = $this->soap->__soapCall('UpdateRightWithDisableVirtualMachine', ['parameters' => [
            'AdminEmail' => $this->adminemail,
            'WebServiceAuthStr' => $this->authstring,
            'RightsID' => $rightsid,
            'Description' => 'Moodle DRM-X 5.0',
            'PlayCount' => '-1',
            'BeginDate' => $begindate,
            'ExpirationDate' => $expirationdate,
            'ExpirationAfterFirstUse' => '-1',
            'RightsPrice' => '0',
            'AllowPrint' => 'False',
            'AllowClipBoard' => 'False',
            'AllowDoc' => 'False',
            'EnableWatermark' => 'True',
            'WatermarkText' => $watermarktext,
            'WatermarkArea' => '1,2,3,4,5,',
            'RandomChangeArea' => 'True',
            'RandomFrquency' => '12',
            'EnableBlacklist' => 'True',
            'EnableWhitelist' => 'True',
            'ExpireTimeUnit' => 'Day',
            'PreviewTime' => 3,
            'PreviewTimeUnit' => 'Day',
            'PreviewPage' => 3,
            'DisableVirtualMachine' => 'True',
            'require_admin_permission' => 'False',
            'bind_1st_screen' => 'False',
            'enable_wm_3rd' => 1,
            'wm_3rd_random_change_angle' => 1,
            'wm_3rd_textalpha' => 90,
            'wm_3rd_rows' => 4,
            'wm_3rd_columns' => 3,
            'wm_3rd_fixed_angle' => 0,
            'wm_3rd_textsize' => 14,
            'wm_3rd_textcolor' => 16744576,
            'wm_3rd_angle_change_interval' => 10,
            'wm_3rd_text' => $watermarktext,
            'enable_wm_2nd' => 0,
            'wm_2nd_text' => $watermarktext,
            'wm_2nd_speed' => 1,
            'wm_2nd_textsize' => 22,
            'wm_2nd_textcolor' => 16744576,
            'wm_2nd_textalpha' => 90,
        ]]);
        $result = isset($response->UpdateRightWithDisableVirtualMachineResult)
            ? trim((string)$response->UpdateRightWithDisableVirtualMachineResult) : '';
        if ($result !== '1') {
            throw new \RuntimeException(get_string('updaterightsfailed', 'filter_drmx5', $result));
        }
    }

    /**
     * Obtain a DRM-X 5.0 license.
     *
     * @param request $request Captured DRM request.
     * @param \stdClass $user Moodle user.
     * @param string $rightsid Rights ID selected by the integration mode.
     * @return array [license HTML, service message].
     */
    public function get_license(request $request, \stdClass $user, $rightsid) {
        $response = $this->soap->__soapCall('getLicenseRemoteToTableWithVersionWithMac', ['parameters' => [
            'AdminEmail' => $this->adminemail,
            'WebServiceAuthStr' => $this->authstring,
            'ProfileID' => $request->get('profileid'),
            'ClientInfo' => $request->get('clientinfo'),
            'RightsID' => $rightsid,
            'UserLoginName' => $user->username,
            'UserFullName' => fullname($user),
            'GroupID' => $this->groupid,
            'Message' => 'N/A',
            'IP' => getremoteaddr(),
            'Platform' => $request->get('platform'),
            'ContentType' => $request->get('contenttype'),
            'Version' => $request->get('version'),
            'Mac' => $request->get('mac'),
        ]]);
        return [
            isset($response->getLicenseRemoteToTableWithVersionWithMacResult)
                ? (string)$response->getLicenseRemoteToTableWithVersionWithMacResult : '',
            isset($response->Message) ? (string)$response->Message : '',
        ];
    }
}
