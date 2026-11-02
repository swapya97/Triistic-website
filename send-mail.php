<?php
/**
 * Triistic Global - Contact & Inquiry Form Mailer
 * Integrated with Zoho Mail SMTP
 *
 * Configured:
 * - SMTP Provider: Zoho Mail (smtppro.zoho.com / smtppro.zoho.in)
 * - FROM: admin@triistic.com
 * - TO: enquiry@triistic.com
 * - Hosting compatible: AWS (EC2/Lightsail), GCP (Compute Engine/App Engine/Cloud Run), or any PHP server
 */

// ============================================================================
// 1. ZOHO SMTP CONFIGURATION
// ============================================================================
// If your Zoho account was created in India (.in), use: 'smtppro.zoho.in'
// If your Zoho account was created in the US / Global (.com), use: 'smtppro.zoho.com'
define('SMTP_HOST', 'smtppro.zoho.com'); 

// Port: 465 (SSL) is recommended for Zoho. Or 587 (TLS).
define('SMTP_PORT', 465); 
define('SMTP_SECURE', 'ssl'); // 'ssl' or 'tls'

// Your Zoho Mail credentials
define('SMTP_USER', 'admin@triistic.com');

// IMPORTANT: Generate an "App Password" from your Zoho Account:
// Go to: https://accounts.zoho.com -> Security -> App Passwords -> Generate New Password
define('SMTP_PASS', 'hTlq@9je'); 

// Mail Recipients
define('TO_EMAIL', 'swapnilakulwar97@gmail.com');
define('FROM_EMAIL', 'admin@triistic.com');
define('FROM_NAME', 'Triistic Global Website');
define('REDIRECT_SUCCESS', 'thank-you.html');

// Set to true while testing to view connection logs if any error occurs
define('DEBUG_MODE', false);

// ============================================================================
// 2. REQUEST & ANTI-SPAM VALIDATION
// ============================================================================

// Prevent direct GET access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.html');
    exit;
}

// Anti-spam honeypot: if botcheck field is filled, silently discard
if (!empty($_POST['botcheck'])) {
    header('Location: ' . REDIRECT_SUCCESS);
    exit;
}

// ============================================================================
// 3. SANITIZE FORM INPUTS
// ============================================================================
function clean_input($data) {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

$form_type   = clean_input($_POST['form_type'] ?? 'Website Inquiry');
$subject_raw = clean_input($_POST['subject'] ?? 'New Inquiry from Triistic Website');
$name        = clean_input($_POST['name'] ?? '');
$email       = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$company     = clean_input($_POST['company'] ?? 'Not specified');
$phone       = clean_input($_POST['phone'] ?? 'Not provided');
$country     = clean_input($_POST['country'] ?? 'Not specified');
$service     = clean_input($_POST['service'] ?? '');
$message     = clean_input($_POST['message'] ?? '');

// Free trial specific fields
$practice_type = clean_input($_POST['practice_type'] ?? '');
$call_time     = clean_input($_POST['call_time'] ?? '');

$trial_needs = [];
if (!empty($_POST['need_bookkeeping']))    $trial_needs[] = 'Bookkeeping';
if (!empty($_POST['need_ap_ar']))          $trial_needs[] = 'AP / AR';
if (!empty($_POST['need_reconciliation'])) $trial_needs[] = 'Reconciliation';
if (!empty($_POST['need_controller']))     $trial_needs[] = 'Controller support';

// Validate required fields
if (empty($name) || !$email) {
    http_response_code(400);
    echo '<p style="font-family:sans-serif;color:#c00;">Error: Please provide your name and a valid email address. <a href="javascript:history.back()">Go back</a></p>';
    exit;
}

// ============================================================================
// 4. COMPOSE EMAIL
// ============================================================================
$timestamp = date('d M Y, H:i:s T');
$user_ip   = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
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
    .header h2 { margin: 0; font-size: 20px; font-weight: 600; }
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
        <h4 style="margin: 20px 0 8px; color: #334155; font-size: 14px; text-transform: uppercase;">Message / Function Details:</h4>
        <div class="message-box">' . nl2br(htmlspecialchars($message)) . '</div>';
}

$html_body .= '
    </div>
    <div class="footer">
        IP Address: ' . htmlspecialchars($user_ip) . ' &bull; Sent via Zoho SMTP (' . htmlspecialchars(FROM_EMAIL) . ')
    </div>
</div>
</body>
</html>';

// Plain text fallback
$plain_body = "Triistic Global - " . $form_type . "\n"
    . "-----------------------------------------\n"
    . "Date: " . $timestamp . "\n"
    . "Name: " . $name . "\n"
    . "Email: " . $email . "\n"
    . "Company: " . $company . "\n"
    . "Phone: " . $phone . "\n"
    . "Country: " . $country . "\n";
if (!empty($service))       $plain_body .= "Service: " . $service . "\n";
if (!empty($practice_type)) $plain_body .= "Practice Type: " . $practice_type . "\n";
if (!empty($trial_needs))   $plain_body .= "Trial Needs: " . implode(', ', $trial_needs) . "\n";
if (!empty($call_time))     $plain_body .= "Scoping Call Time: " . $call_time . "\n";
if (!empty($message))       $plain_body .= "\nMessage:\n" . $message . "\n";
$plain_body .= "\nIP: " . $user_ip . "\n";

// ============================================================================
// 5. NATIVE PURE-PHP ZOHO SMTP CLIENT
// (No composer or external libraries required; works on AWS EC2 & GCP)
// ============================================================================
function zoho_smtp_send($to, $from_email, $from_name, $reply_to_email, $reply_to_name, $subject, $html_content, $plain_content) {
    $host   = SMTP_HOST;
    $port   = SMTP_PORT;
    $secure = SMTP_SECURE;
    $user   = SMTP_USER;
    $pass   = SMTP_PASS;

    $connection_target = ($secure === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
    
    $socket = @stream_socket_client($connection_target, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        if (DEBUG_MODE) { echo "Socket connect error: {$errstr} ({$errno})"; }
        return false;
    }

    $read = function() use ($socket) {
        $response = "";
        while ($str = fgets($socket, 512)) {
            $response .= $str;
            if (substr($str, 3, 1) === " ") { break; }
        }
        return $response;
    };

    $send = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $greeting = $read();
    if (substr($greeting, 0, 3) !== '220') { return false; }

    // EHLO
    $send("EHLO triistic.com");
    $read();

    // STARTTLS if port 587
    if ($secure === 'tls') {
        $send("STARTTLS");
        $tls_resp = $read();
        if (substr($tls_resp, 0, 3) !== '220') { return false; }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $send("EHLO triistic.com");
        $read();
    }

    // AUTH LOGIN
    $send("AUTH LOGIN");
    $read();

    $send(base64_encode($user));
    $read();

    $send(base64_encode($pass));
    $auth_resp = $read();
    if (substr($auth_resp, 0, 3) !== '235') {
        if (DEBUG_MODE) { echo "Zoho Authentication Failed: " . htmlspecialchars($auth_resp); }
        return false;
    }

    // MAIL FROM
    $send("MAIL FROM: <{$from_email}>");
    $mf_resp = $read();
    if (substr($mf_resp, 0, 3) !== '250') { return false; }

    // RCPT TO
    $send("RCPT TO: <{$to}>");
    $rcpt_resp = $read();
    if (substr($rcpt_resp, 0, 3) !== '250') { return false; }

    // DATA
    $send("DATA");
    $data_resp = $read();
    if (substr($data_resp, 0, 3) !== '354') { return false; }

    // Build MIME message
    $boundary = "==Multipart_Boundary_x" . md5(time()) . "x";
    $date = date('r');

    $headers = [
        "Date: {$date}",
        "To: <{$to}>",
        "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <{$from_email}>",
        "Reply-To: =?UTF-8?B?" . base64_encode($reply_to_name) . "?= <{$reply_to_email}>",
        "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
        "MIME-Version: 1.0",
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
        "X-Mailer: Triistic Zoho Mailer (PHP/" . phpversion() . ")"
    ];

    $payload = implode("\r\n", $headers) . "\r\n\r\n";
    $payload .= "--{$boundary}\r\n";
    $payload .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $payload .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $payload .= $plain_content . "\r\n\r\n";
    $payload .= "--{$boundary}\r\n";
    $payload .= "Content-Type: text/html; charset=UTF-8\r\n";
    $payload .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $payload .= $html_content . "\r\n\r\n";
    $payload .= "--{$boundary}--\r\n";
    $payload .= ".";

    $send($payload);
    $final_resp = $read();

    $send("QUIT");
    fclose($socket);

    return (substr($final_resp, 0, 3) === '250');
}

// ============================================================================
// 6. DISPATCH & REDIRECT
// ============================================================================
// Send through Zoho SMTP
$sent = zoho_smtp_send(
    TO_EMAIL, 
    FROM_EMAIL, 
    FROM_NAME, 
    $email, 
    $name, 
    $email_subject, 
    $html_body, 
    $plain_body
);

// Fallback to PHP mail() if SMTP password is not yet configured or fails
if (!$sent && SMTP_PASS === 'YOUR_ZOHO_APP_PASSWORD') {
    $fallback_boundary = "==Multipart_Boundary_x" . md5(time()) . "x";
    $fallback_headers = [
        'MIME-Version: 1.0',
        'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
        'Reply-To: ' . $name . ' <' . $email . '>',
        'Content-Type: multipart/alternative; boundary="' . $fallback_boundary . '"'
    ];
    $fallback_body = "--{$fallback_boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n" . $html_body . "\r\n\r\n--{$fallback_boundary}--";
    @mail(TO_EMAIL, $email_subject, $fallback_body, implode("\r\n", $fallback_headers), "-f" . FROM_EMAIL);
}

// Handle AJAX requests
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => REDIRECT_SUCCESS]);
    exit;
}

// Redirect user to Thank You page
header('Location: ' . REDIRECT_SUCCESS);
exit;
