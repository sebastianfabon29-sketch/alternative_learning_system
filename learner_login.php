<?php
// ══════════════════════════════════════════════════════════════
// learner_login.php — AJAX endpoint for learner portal login
// Handles authentication for role_id = 3 (learner) only
// ══════════════════════════════════════════════════════════════
session_name('als_learner_session');
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter your email and password.']);
    exit;
}

// Fetch user by email
$stmt = $conn->prepare("SELECT user_id, password, role_id, first_name, last_name, status FROM users WHERE LOWER(TRIM(email)) = LOWER(TRIM(?)) LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account found with this email. Please check your email address.']);
    exit;
}

// Check role — only learners (role_id = 3) can use this portal login
if (intval($user['role_id']) !== 3) {
    echo json_encode(['success' => false, 'message' => 'This login is for ALS Learners only. Staff and teachers should use the <a href="login.php" style="color:#2563eb;font-weight:700;">Staff Login page</a>.']);
    exit;
}

// Check account status
if (strtolower($user['status'] ?? 'active') === 'inactive') {
    echo json_encode(['success' => false, 'message' => 'Your account has been deactivated. Please contact the administrator.']);
    exit;
}

// Verify password
$password_valid = password_verify($password, $user['password']);
if (!$password_valid && $password === $user['password']) {
    $password_valid = true;
    // Auto-migrate legacy plain text password to secure hash
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $upd_hash = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    if ($upd_hash) {
        $uid_to_upd = intval($user['user_id']);
        $upd_hash->bind_param("si", $new_hash, $uid_to_upd);
        $upd_hash->execute();
        $upd_hash->close();
    }
}

if (!$password_valid) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password. Please check your password or click "Forgot Password?" below to reset it.']);
    exit;
}

// ── Set session ──
session_regenerate_id(true);
$_SESSION['user_id']    = intval($user['user_id']);
$_SESSION['role_id']    = 3;
$_SESSION['role']       = 'learner';
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name']  = $user['last_name'];
$_SESSION['email']      = $email;

// Log audit
$uid = intval($user['user_id']);
$log = $conn->prepare("INSERT INTO audit_logs (user_id, action, log_time) VALUES (?, 'login', NOW())");
if ($log) { $log->bind_param('i', $uid); $log->execute(); $log->close(); }

echo json_encode([
    'success'  => true,
    'redirect' => 'learner/dashboard.php',
    'message'  => 'Login successful! Redirecting to your dashboard...'
]);
exit;
