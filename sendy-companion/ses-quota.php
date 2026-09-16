<?php
/**
 * ses-quota.php - optional companion endpoint for Quillrush Newsletter Studio.
 *
 * Returns your Amazon SES sending limits as JSON so the WordPress plugin can
 * display them (daily quota, sent in the last 24h, remaining today, send rate).
 * Sendy has the AWS keys; WordPress does not, so this small file bridges the gap.
 *
 * INSTALL: upload this file into your Sendy install's /api/ folder, next to the
 * other api endpoints, so it is reachable at:
 *     https://your-sendy-url/api/ses-quota.php
 *
 * It is read-only (calls SES GetSendQuota) and is protected by your Sendy API
 * key. If you don't install it, the plugin simply doesn't show the SES panel.
 */

ob_start();
$root = dirname(__DIR__); // this file lives at <sendy-root>/api/, so parent = root
$config_file = $root . '/includes/config.php';
$ses_class   = $root . '/includes/helpers/class.amazonses.php';
if (!is_file($config_file) || !is_file($ses_class)) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(array('error' => 'Not inside a Sendy install (place this file in Sendy /api/).'));
    exit;
}
include $config_file;   // defines $dbHost, $dbUser, $dbPass, $dbName
require_once $ses_class; // defines AmazonSES
ob_end_clean();

header('Content-Type: application/json');

$mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(array('error' => 'Database connection failed.'));
    exit;
}

// Authenticate against the Sendy API key (constant-time compare).
$api_key = isset($_POST['api_key']) ? (string) $_POST['api_key'] : '';
$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT api_key, s3_key, s3_secret, ses_endpoint, timezone FROM login LIMIT 1"));
if (!$row || $api_key === '' || !hash_equals((string) $row['api_key'], $api_key)) {
    http_response_code(403);
    echo json_encode(array('error' => 'Not authenticated.'));
    exit;
}
if ($row['s3_key'] === '' || $row['ses_endpoint'] === '') {
    echo json_encode(array('error' => 'Amazon SES is not configured in Sendy.'));
    exit;
}

// Sign and call SES GetSendQuota using Sendy's own SES helper.
$ses = new AmazonSES();
$ses->aws_access_key_id  = $row['s3_key'];
$ses->aws_secret_key     = $row['s3_secret'];
$ses->amazonSES_base_url = 'https://' . $row['ses_endpoint'];
$ses->Timezone           = $row['timezone'] ? $row['timezone'] : 'UTC';

$payload = 'Action=GetSendQuota&Version=2010-12-01';
$headers = $ses->make_required_http_headers_for_query_string($payload);

$ch = curl_init('https://' . $row['ses_endpoint'] . '/');
curl_setopt_array($ch, array(
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
));
$resp = curl_exec($ch);
curl_close($ch);

if ($resp === false || strpos($resp, '<Max24HourSend>') === false) {
    echo json_encode(array('error' => 'Could not read the SES quota.'));
    exit;
}

preg_match('#<Max24HourSend>([^<]*)#', $resp, $m_max);
preg_match('#<MaxSendRate>([^<]*)#', $resp, $m_rate);
preg_match('#<SentLast24Hours>([^<]*)#', $resp, $m_sent);

$max  = isset($m_max[1])  ? (float) $m_max[1]  : 0;
$sent = isset($m_sent[1]) ? (float) $m_sent[1] : 0;
$rate = isset($m_rate[1]) ? (float) $m_rate[1] : 0;

echo json_encode(array(
    'max_24_hour'        => $max,
    'sent_last_24_hours' => $sent,
    'remaining_today'    => $max - $sent,
    'max_send_rate'      => $rate,
));
