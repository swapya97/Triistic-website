<?php
/**
 * Triistic Global - Contact & Inquiry Form Mailer
 *
 * Configured recipients:
 * - TO: enquiry@triistic.com
 * - FROM: admin@triistic.com
 */

// ---------------------- CONFIGURATION ----------------------
define('TO_EMAIL', 'enquiry@triistic.com');
define('FROM_EMAIL', 'admin@triistic.com');
define('FROM_NAME', 'Triistic Global Website');
define('REDIRECT_SUCCESS', 'thank-you.html');

// Prevent direct GET access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html');
    exit;
}

// Anti-spam honeypot check (botcheck input should be left empty by humans)
if (!empty($_POST['botcheck'])) {
    // Silently redirect bots to success page without sending email
    header('Location: ' . REDIRECT_SUCCESS);
    exit;
}

// ---------------------- SANITIZE INPUTS ----------------------
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

$form_type = clean_input($_POST['form_type'] ?? 'Website Inquiry');
$subject_raw = clean_input($_POST['subject'] ?? 'New Inquiry from Triistic Website');
$name = clean_input($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$company = clean_input($_POST['company'] ?? 'Not specified');
$phone = clean_input($_POST['phone'] ?? 'Not provided');
$country = clean_input($_POST['country'] ?? 'Not specified');
$service = clean_input($_POST['service'] ?? '');
$message = clean_input($_POST['message'] ?? '');

// Free trial specific fields
$practice_type = clean_input($_POST['practice_type'] ?? '');
$call_time = clean_input($_POST['call_time'] ?? '');

$trial_needs = [];
if (!empty($_POST['need_bookkeeping'])) $trial_needs[] = 'Bookkeeping';
if (!empty($_POST['need_ap_ar'])) $trial_needs[] = 'AP / AR';
if (!empty($_POST['need_reconciliation'])) $trial_needs[] = 'Reconciliation';
if (!empty($_POST['need_controller'])) $trial_needs[] = 'Controller support';

// Basic validation
if (empty($name) || !$email) {
    http_response_code(400);
    echo '<p style="font-family:sans-serif;color:#c00;">Error: Please provide a valid name and email address. <a href="javascript:history.back()">Go back</a></p>';
    exit;
}

// ---------------------- COMPOSE EMAIL ----------------------
$timestamp = date('d M Y, H:i:s T');
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

$email_subject = "[Triistic Website] " . $subject_raw;

// HTML Body
$html_body = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; line-height: 1.6; color: #1e293b; background: #f8fafc; padding: 20px; }
    .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .header { background: #001f3f; color: #ffffff; padding: 24px; text-align: center; }
    .header h2 { margin: 0; font-size: 20px; font-weight: 600; letter-spacing: 0.5px; }
    .header p { margin: 4px 0 0; font-size: 13px; color: #94a3b8; }
    .content { padding: 24px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    th { width: 35%; color: #64748b; font-weight: 600; background: #f8fafc; }
    td { color: #0f172a; font-weight: 500; }
    .message-box { background: #f8fafc; border-left: 4px solid #f59e0b; padding: 14px 18px; border-radius: 4px; font-size: 14px; color: #334155; white-space: pre-line; }
    .footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>' . htmlspecialchars($form_type) . '</h2>
        <p>Submitted via triistic.com on ' . $timestamp . '</p>
    </div>
    <div class="content">
        <table>
            <tr><th>Full Name</th><td><strong>' . htmlspecialchars($name) . '</strong></td></tr>
            <tr><th>Email Address</th><td><a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></td></tr>
            <tr><th>Company / Firm</th><td>' . htmlspecialchars($company) . '</td></tr>
            <tr><th>Phone Number</th><td>' . htmlspecialchars($phone) . '</td></tr>
            <tr><th>Country</th><td>' . htmlspecialchars($country) . '</td></tr>';

if (!empty($service)) {
    $html_body .= '<tr><th>Service Needed</th><td>' . htmlspecialchars($service) . '</td></tr>';
}

if (!empty($practice_type)) {
    $html_body .= '<tr><th>Practice Type</th><td>' . htmlspecialchars($practice_type) . '</td></tr>';
}

if (!empty($trial_needs)) {
    $html_body .= '<tr><th>Required Services</th><td>' . htmlspecialchars(implode(', ', $trial_needs)) . '</td></tr>';
}

if (!empty($call_time)) {
    $html_body .= '<tr><th>Scoping Call Time</th><td>' . htmlspecialchars($call_time) . '</td></tr>';
}

$html_body .= '
        </table>';

if (!empty($message)) {
    $html_body .= '
        <h4 style="margin: 20px 0 8px; color: #334155; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Message / Function Details:</h4>
        <div class="message-box">' . nl2br(htmlspecialchars($message)) . '</div>';
}

$html_body .= '
    </div>
    <div class="footer">
        IP Address: ' . htmlspecialchars($user_ip) . ' &bull; This email was automatically generated by triistic.com
    </div>
</div>
</body>
</html>';

// Plain Text fallback
$plain_body = "Triistic Global - " . $form_type . "\n"
    . "-----------------------------------------\n"
    . "Date: " . $timestamp . "\n"
    . "Name: " . $name . "\n"
    . "Email: " . $email . "\n"
    . "Company: " . $company . "\n"
    . "Phone: " . $phone . "\n"
    . "Country: " . $country . "\n";

if (!empty($service)) {
    $plain_body .= "Service: " . $service . "\n";
}
if (!empty($practice_type)) {
    $plain_body .= "Practice Type: " . $practice_type . "\n";
}
if (!empty($trial_needs)) {
    $plain_body .= "Trial Needs: " . implode(', ', $trial_needs) . "\n";
}
if (!empty($call_time)) {
    $plain_body .= "Scoping Call Time: " . $call_time . "\n";
}
if (!empty($message)) {
    $plain_body .= "\nMessage:\n" . $message . "\n";
}
$plain_body .= "\nIP: " . $user_ip . "\n";

// ---------------------- EMAIL HEADERS ----------------------
$boundary = "==Multipart_Boundary_x" . md5(time()) . "x";

$headers = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>';
$headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
$headers[] = 'Return-Path: <' . FROM_EMAIL . '>';
$headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$full_body = "--" . $boundary . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 7bit\r\n\r\n"
    . $plain_body . "\r\n\r\n"
    . "--" . $boundary . "\r\n"
    . "Content-Type: text/html; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 7bit\r\n\r\n"
    . $html_body . "\r\n\r\n"
    . "--" . $boundary . "--";

$headers_string = implode("\r\n", $headers);

// Send email using PHP mail()
// -f parameter sets the envelope sender to admin@triistic.com
$mail_sent = @mail(TO_EMAIL, $email_subject, $full_body, $headers_string, "-f" . FROM_EMAIL);

// Handle JSON response for AJAX / Fetch requests
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'redirect' => REDIRECT_SUCCESS
    ]);
    exit;
}

// Redirect to thank-you.html
header('Location: ' . REDIRECT_SUCCESS);
exit;
