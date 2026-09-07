<?php
// DRM-X 5.0 license endpoint for Moodle.

// DRM-X/ZJGet can arrive through a cross-site POST. Browsers may omit the
// Moodle session cookie on that first request because of SameSite rules. Relay
// the allowed DRM fields once from this Moodle-origin page before bootstrapping
// Moodle, so an existing login cookie can be included in the same-site POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['_drmx5samesiterelay'])) {
    $relayfields = [
        'profileid',
        'clientinfo',
        'rightsid',
        'yourproductid',
        'platform',
        'contenttype',
        'version',
        'return_url',
        'mac',
    ];
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>DRM-X 5.0</title></head><body>';
    echo '<form id="drmx5-relay" method="post" action="">';
    foreach ($relayfields as $field) {
        if (!isset($_POST[$field]) || !is_scalar($_POST[$field])) {
            continue;
        }
        echo '<input type="hidden" name="' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') .
            '" value="' . htmlspecialchars((string)$_POST[$field], ENT_QUOTES, 'UTF-8') . '">';
    }
    echo '<input type="hidden" name="_drmx5samesiterelay" value="1">';
    echo '<noscript><button type="submit">Continue</button></noscript></form>';
    echo '<script>document.getElementById("drmx5-relay").submit();</script>';
    echo '</body></html>';
    exit;
}

define('NO_OUTPUT_BUFFERING', true);
require_once(__DIR__ . '/../../config.php');

use filter_drmx5\local\course_authorizer;
use filter_drmx5\local\drmx_client;
use filter_drmx5\local\request;

/**
 * Render a standalone, escaped error page.
 *
 * @param string $message Error message.
 */
function filter_drmx5_error_page($message) {
    $title = get_string('licenseerror', 'filter_drmx5');
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . s($title) . '</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f7fa;margin:0;padding:32px}' .
        '.card{max-width:620px;margin:10vh auto;background:#fff;padding:32px;border-radius:12px;' .
        'box-shadow:0 4px 20px rgba(0,0,0,.08);text-align:center}' .
        'h1{font-size:22px;color:#c62828}p{line-height:1.6;color:#333}</style></head><body>';
    echo '<main class="card"><h1>' . s($title) . '</h1><p>' . s($message) . '</p></main></body></html>';
    exit;
}

$config = get_config('filter_drmx5');
$rightsmode = !empty($config->rightsmode) ? $config->rightsmode : 'post';

if (request::is_new_request()) {
    $drmrequest = request::capture();
    $SESSION->filter_drmx5_request = $drmrequest->export();
} else if (optional_param('resume', 0, PARAM_BOOL) && !empty($SESSION->filter_drmx5_request)) {
    $drmrequest = request::restore((array)$SESSION->filter_drmx5_request);
} else {
    filter_drmx5_error_page(get_string('norequest', 'filter_drmx5'));
}

if (!isloggedin() || isguestuser()) {
    $SESSION->wantsurl = (new moodle_url('/filter/drmx5/licstore5.php', ['resume' => 1]))->out(false);
    redirect(get_login_url());
}
require_login();

$validationerror = $drmrequest->validate($rightsmode);
if ($validationerror !== '') {
    filter_drmx5_error_page(get_string($validationerror, 'filter_drmx5'));
}

if (empty($config->adminemail) || empty($config->webserviceauthstr) || empty($config->groupid)) {
    filter_drmx5_error_page(get_string('configurationmissing', 'filter_drmx5'));
}

$course = course_authorizer::get_accessible_course($drmrequest->get('yourproductid'), $USER);
if (!$course) {
    filter_drmx5_error_page(get_string('notenrolled', 'filter_drmx5'));
}

if ($rightsmode === 'fixed') {
    $rightsid = isset($config->fixedrightsid) ? trim($config->fixedrightsid) : '';
    if ($rightsid === '') {
        filter_drmx5_error_page(get_string('fixedrightsidmissing', 'filter_drmx5'));
    }
    $permissionenddate = empty($course->enddate) ? strtotime('+10 years') : (int)$course->enddate;
    if (empty($course->startdate) || $permissionenddate < $course->startdate) {
        filter_drmx5_error_page(get_string('invalidcoursedates', 'filter_drmx5'));
    }
} else {
    $rightsid = $drmrequest->get('rightsid');
}

try {
    $servicearea = !empty($config->servicearea) ? $config->servicearea : 'international';
    $client = new drmx_client(
        $config->adminemail,
        $config->webserviceauthstr,
        $config->groupid,
        $servicearea
    );
    $useralreadyexists = $client->ensure_user_exists($USER);
    if ($useralreadyexists) {
        $blockreason = $client->get_user_block_reason($USER->username);
        if ($blockreason !== '') {
            filter_drmx5_error_page(get_string($blockreason, 'filter_drmx5'));
        }
    }

    if ($rightsmode === 'fixed') {
        $watermark = !empty($USER->email) ? $USER->email : $USER->username;
        $client->update_rights($rightsid, $watermark, $course->startdate, $permissionenddate);
    }

    list($license, $message) = $client->get_license($drmrequest, $USER, $rightsid);
} catch (Throwable $exception) {
    debugging($exception->getMessage(), DEBUG_DEVELOPER);
    filter_drmx5_error_page(get_string('serviceerror', 'filter_drmx5'));
}

if (stripos($license, 'license_div_drm-x5') === false) {
    $details = trim($message . ' ' . strip_tags($license));
    filter_drmx5_error_page(get_string('licensefailed', 'filter_drmx5', $details));
}

unset($SESSION->filter_drmx5_request);
$returnurl = $drmrequest->get('return_url');
$returnurljson = json_encode($returnurl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo s($returnurl); ?></title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fa; margin: 0; padding: 32px; }
        .card { max-width: 620px; margin: 10vh auto; background: #fff; padding: 32px;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); text-align: center; }
        button { border: 0; border-radius: 6px; background: #1677ff; color: #fff;
            padding: 12px 28px; cursor: pointer; }
    </style>
</head>
<body>
    <?php echo $license; // Intentionally output the license HTML returned by DRM-X. ?>
    <main class="card" id="drmx5-content">
        <p><?php echo s($message); ?></p>
        <button type="button" id="drmx5-open"><?php echo s(get_string('opencontent', 'filter_drmx5')); ?></button>
    </main>
    <script>
        (function() {
            var returnUrl = <?php echo $returnurljson; ?>;
            var button = document.getElementById('drmx5-open');
            button.addEventListener('click', function() {
                if (returnUrl) {
                    window.location.href = returnUrl;
                }
            });
            if (returnUrl === 'ios_x') {
                document.getElementById('drmx5-content').style.display = 'none';
            } else if (returnUrl) {
                button.click();
            }
        }());
    </script>
</body>
</html>
