<?php
require_once dirname(__DIR__).'/config.php';
require_once dirname(__DIR__).'/includes/EmailVerification.php';
require_once dirname(__DIR__).'/includes/AuditLogger.php';

startSession();

if (!isLoggedIn()) {
    header('Location: '.APP_URL.'/auth/login.php');
    exit;
}

$emailVerification = new EmailVerification(db());
$auditLogger = new AuditLogger(db());
$auditLogger->setUserId($_SESSION['uid']);

$success = '';
$error = '';

if ($emailVerification->isEmailVerified($_SESSION['uid'])) {
    header('Location: '.APP_URL.'/dashboard/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Security validation failed.';
    } else {
        $result = $emailVerification->resendVerificationEmail($_SESSION['uid']);

        if ($result['success']) {
            $success = $result['message'];
            $auditLogger->log('verification_email_resent', 'auth', 'success', [
                'target_type' => 'user',
                'target_id' => $_SESSION['uid']
            ]);
        } else {
            $error = $result['error'];
            $auditLogger->log('verification_email_resend_failed', 'auth', 'failed', [
                'target_type' => 'user',
                'target_id' => $_SESSION['uid'],
                'error_message' => $error
            ]);
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Resend Verification Email — BulkReplace</title>
    <link rel="icon" type="image/png" href="/img/logo.png">
    <link rel="stylesheet" href="/assets/main.css">
    <style>
        .auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
        .auth-card{background:var(--card);border:1px solid var(--border);border-top:2px solid var(--a1);border-radius:20px;padding:40px;width:100%;max-width:480px;box-shadow:0 24px 80px rgba(0,0,0,.5);animation:fadeUp .3s ease;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:28px;">
            <img src="/img/logo.png" alt="BulkReplace" style="width:48px;height:48px;border-radius:12px;">
            <div>
                <div style="font-size:22px;font-weight:800;color:#fff;">Email Verification</div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:9px;color:var(--muted);letter-spacing:3px;text-transform:uppercase;">Resend Confirmation</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="err-box">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="suc-box">✓ <?= htmlspecialchars($success) ?></div>
            <p style="color:var(--muted);margin:16px 0;">Please check your inbox and spam folder for the verification email.</p>
            <a href="<?= APP_URL ?>/dashboard/" class="btn btn-secondary" style="width:100%;justify-content:center;">Go to Dashboard</a>
        <?php else: ?>
            <div class="info-box" style="margin-bottom:24px;">
                <p style="margin:0;"><strong>Email not verified yet?</strong></p>
                <p style="margin:8px 0 0;font-size:13px;">Click the button below to receive a new verification email. The link will be valid for 24 hours.</p>
            </div>

            <form method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-amber" style="width:100%;justify-content:center;">
                    Send Verification Email
                </button>
            </form>
        <?php endif; ?>

        <div style="margin-top:24px;padding-top:24px;border-top:1px solid var(--border);text-align:center;">
            <a href="<?= APP_URL ?>/dashboard/" style="color:var(--muted);text-decoration:none;font-size:13px;">← Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
