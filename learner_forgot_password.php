<?php
// ═══════════════════════════════════════════════════════════════════
// FILE: learner_forgot_password.php — Learner Password Reset Endpoint
// Dedicated AJAX endpoint for ALS Learner (role_id = 3) OTP recovery
// ═══════════════════════════════════════════════════════════════════
session_name('als_learner_session');
session_start();
require_once 'db_connect.php';
require_once 'send_mail_helper.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Ensure required columns exist on users table
$checkOTP = $conn->query("SHOW COLUMNS FROM users LIKE 'otp_code'");
if ($checkOTP && $checkOTP->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN otp_code VARCHAR(10) NULL DEFAULT NULL");
}
$checkOTPExp = $conn->query("SHOW COLUMNS FROM users LIKE 'otp_expires_at'");
if ($checkOTPExp && $checkOTPExp->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN otp_expires_at DATETIME NULL DEFAULT NULL");
}

$action = trim($_POST['action'] ?? '');

// ── ACTION 1: SEND RECOVERY OTP CODE TO GMAIL ───────────────────────
if ($action === 'send_otp') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid Gmail address.']);
        exit;
    }

    // Query user by email & verify role_id = 3 (Learner)
    $stmt = $conn->prepare("SELECT user_id, first_name, last_name, role_id, status FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 1");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No account found with this Gmail address.']);
        exit;
    }

    if (intval($user['role_id']) !== 3) {
        echo json_encode(['success' => false, 'message' => 'This password recovery is for ALS Learners only. Staff and teachers should use the Staff Login.']);
        exit;
    }

    if (strtolower($user['status'] ?? 'active') === 'inactive') {
        echo json_encode(['success' => false, 'message' => 'Your account is deactivated. Please contact your ALS teacher or administrator.']);
        exit;
    }

    // Generate 6-digit OTP code & 15 min expiry
    $otp = strval(rand(100000, 999999));
    $upd = $conn->prepare("UPDATE users SET otp_code = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?");
    if (!$upd) {
        echo json_encode(['success' => false, 'message' => 'Database update error.']);
        exit;
    }

    $uid = intval($user['user_id']);
    $upd->bind_param('si', $otp, $uid);
    $exec_ok = $upd->execute();
    $upd->close();

    if (!$exec_ok) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate reset code. Please try again.']);
        exit;
    }

    // Dispatch email via Gmail SMTP helper
    $full_name = trim($user['first_name'] . ' ' . $user['last_name']);
    try {
        $mail = createMailer();
        $mail->addAddress($email, $full_name);
        $mail->Subject = 'ALS Learner Recovery Code: ' . $otp;

        $mail->Body = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>ALS Learner Password Recovery</title></head><body style="margin:0;padding:0;background-color:#f0f4f8;font-family:Arial,Helvetica,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f4f8;padding:30px 0;"><tr><td align="center"><table width="560" cellpadding="0" cellspacing="0" border="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:560px;"><tr><td style="background:linear-gradient(135deg,#1e3a8a,#7c3aed);padding:28px 36px;text-align:center;"><p style="margin:0 0 4px;font-size:11px;font-weight:700;color:#c4b5fd;letter-spacing:3px;text-transform:uppercase;">Alternative Learning System</p><h1 style="margin:0;font-size:20px;font-weight:800;color:#fff;">Learner Password Reset</h1></td></tr><tr><td style="padding:36px 40px 28px;"><p style="margin:0 0 6px;font-size:15px;color:#1e293b;font-weight:700;">Hello, ' . htmlspecialchars($full_name) . '!</p><p style="margin:0 0 24px;font-size:14px;color:#475569;line-height:1.7;">You requested to reset your password for the <strong>ALS Learner Portal</strong>. Use the 6-digit verification code below to reset your password.</p><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background:#f0fdf4;border:2px solid #bbf7d0;border-radius:14px;padding:24px 20px;"><p style="margin:0 0 8px;font-size:11px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:2px;">Your Recovery Code</p><p style="margin:0;font-size:40px;font-weight:900;letter-spacing:14px;color:#15803d;font-family:Courier New,Courier,monospace;">' . $otp . '</p></td></tr></table><p style="margin:18px 0 0;font-size:13px;color:#94a3b8;text-align:center;">&#9200; Valid for <strong>15 minutes</strong>. Do not share this code with anyone.</p><hr style="border:none;border-top:1px solid #f1f5f9;margin:28px 0;"><p style="margin:0;font-size:13px;color:#94a3b8;text-align:center;">If you did not request this code, please ignore this email.</p></td></tr><tr><td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 40px;text-align:center;"><p style="margin:0;font-size:12px;color:#94a3b8;">&copy; ALS Culaba District MIS &bull; Biliran Province, Philippines</p></td></tr></table></td></tr></table></body></html>';

        $mail->AltBody =
            "ALS MIS — Learner Password Reset\r\n" .
            "=================================\r\n\r\n" .
            "Hello, " . $full_name . "!\r\n\r\n" .
            "Your password reset code is:\r\n\r\n   " . $otp . "\r\n\r\n" .
            "Valid for 15 minutes. Do not share it.\r\n\r\n" .
            "-- ALS Culaba District MIS | Biliran Province, Philippines";

        $mail->send();

        echo json_encode([
            'success' => true,
            'email'   => $email,
            'message' => 'A 6-digit recovery code has been sent to your Gmail inbox!'
        ]);
        exit;

    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please check your connection or try again.']);
        exit;
    }
}

// ── ACTION 2: VERIFY RECOVERY OTP CODE ──────────────────────────────
elseif ($action === 'verify_otp') {
    $email = trim($_POST['email'] ?? '');
    $otp   = trim($_POST['otp_code'] ?? '');

    if (empty($email) || empty($otp)) {
        echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit recovery code.']);
        exit;
    }

    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit code.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT user_id, otp_code, otp_expires_at FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) AND role_id = 3 LIMIT 1");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No learner account found with this Gmail address.']);
        exit;
    }

    if ($user['otp_code'] !== $otp || strtotime($user['otp_expires_at']) <= time()) {
        echo json_encode(['success' => false, 'message' => 'Incorrect or expired recovery code. Please check your Gmail or request a new code.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Recovery code verified successfully! Please enter your new password below.'
    ]);
    exit;
}

// ── ACTION 3: RESET PASSWORD ─────────────────────────────────────────
elseif ($action === 'reset_password') {
    $email        = trim($_POST['email'] ?? '');
    $otp          = trim($_POST['otp_code'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_pw   = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($otp) || empty($new_password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    if (strlen($otp) !== 6 || !ctype_digit($otp)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid 6-digit code.']);
        exit;
    }

    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
        exit;
    }

    if ($new_password !== $confirm_pw) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match. Please re-type your password.']);
        exit;
    }

    // Verify user, role_id = 3, otp_code, and expiration
    $stmt = $conn->prepare("SELECT user_id, otp_code, otp_expires_at FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) AND role_id = 3 LIMIT 1");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
        exit;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'No learner account found with this Gmail address.']);
        exit;
    }

    if ($user['otp_code'] !== $otp || strtotime($user['otp_expires_at']) <= time()) {
        echo json_encode(['success' => false, 'message' => 'Incorrect or expired recovery code. Please start recovery again.']);
        exit;
    }

    // Hash new password using bcrypt
    $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
    $uid       = intval($user['user_id']);

    $upd = $conn->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expires_at = NULL WHERE user_id = ?");
    if (!$upd) {
        echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
        exit;
    }

    $upd->bind_param('si', $hashed_pw, $uid);
    $ok = $upd->execute();
    $upd->close();

    if ($ok) {
        // Audit log
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, log_time) VALUES (?, 'learner_password_reset', NOW())");
        if ($log) { $log->bind_param('i', $uid); $log->execute(); $log->close(); }

        echo json_encode([
            'success' => true,
            'message' => 'Password reset successful! You can now log in with your new password.'
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error updating password.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;
