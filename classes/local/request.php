<?php
// This file is part of Moodle - http://moodle.org/

namespace filter_drmx5\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Captures and validates the values posted by ZJGet/DRM-X.
 */
class request {
    /** @var array DRM request values. */
    private $values;

    /** @var bool Whether rightsid arrived in a POST request. */
    private $rightsidfrompost;

    /**
     * @param array $values Sanitised values.
     * @param bool $rightsidfrompost Whether rightsid was posted.
     */
    private function __construct(array $values, $rightsidfrompost) {
        $this->values = $values;
        $this->rightsidfrompost = (bool)$rightsidfrompost;
    }

    /**
     * Return true when the current HTTP request contains a new DRM request.
     *
     * @return bool
     */
    public static function is_new_request() {
        return isset($_POST['profileid']) || isset($_GET['profileid']);
    }

    /**
     * Capture a new request without accepting cookie values from $_REQUEST.
     *
     * @return self
     */
    public static function capture() {
        $source = array_merge($_GET, $_POST);
        $fields = [
            'profileid' => 255,
            'clientinfo' => 8192,
            'rightsid' => 255,
            'yourproductid' => 1024,
            'platform' => 255,
            'contenttype' => 255,
            'version' => 255,
            'return_url' => 2048,
            'mac' => 1024,
        ];
        $values = [];
        foreach ($fields as $field => $maxlength) {
            $value = isset($source[$field]) && is_scalar($source[$field]) ? (string)$source[$field] : '';
            $value = clean_param($value, PARAM_RAW_TRIMMED);
            $values[$field] = substr($value, 0, $maxlength);
        }

        return new self($values, isset($_POST['rightsid']));
    }

    /**
     * Restore a request previously stored in the Moodle session.
     *
     * @param array $data Session data.
     * @return self
     */
    public static function restore(array $data) {
        return new self($data['values'], !empty($data['rightsidfrompost']));
    }

    /**
     * Convert to session-safe data.
     *
     * @return array
     */
    public function export() {
        return [
            'values' => $this->values,
            'rightsidfrompost' => $this->rightsidfrompost,
        ];
    }

    /**
     * Get a captured value.
     *
     * @param string $name Field name.
     * @return string
     */
    public function get($name) {
        return isset($this->values[$name]) ? $this->values[$name] : '';
    }

    /**
     * Validate required fields and the selected rights mode.
     *
     * @param string $rightsmode Configured rights mode.
     * @return string Empty on success, otherwise a language string key.
     */
    public function validate($rightsmode) {
        foreach (['profileid', 'clientinfo', 'yourproductid'] as $required) {
            if ($this->get($required) === '') {
                return 'missingrequestparameter';
            }
        }
        if ($rightsmode === 'post' && (!$this->rightsidfrompost || $this->get('rightsid') === '')) {
            return 'missingpostedrightsid';
        }
        if (preg_match('~^(?:javascript|data|vbscript):~i', $this->get('return_url'))) {
            return 'invalidreturnurl';
        }
        return '';
    }
}
