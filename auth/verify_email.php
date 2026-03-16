<?php
require_once dirname(__DIR__).'/config.php';
require_once dirname(__DIR__).'/includes/EmailVerification.php';
require_once dirname(__DIR__).'/includes/AuditLogger.php';

startSession();

$emailVerification = new EmailVerification(db());
$auditLogger = new AuditLogger(db());

$token = $_GET['token'] ?? '';
$success = false;
$error = '';

if ($token) {
    $result = $emailVerification->verifyToken($token);

    if ($result['success']) {
        $success = true;
        $auditLogger->setUserId($result['user_id']);
        $auditLogger->log('email_verified', 'auth', 'success', [
            'target_type' => 'user',
            'target_id' => $result['user_id'],
            'email' => $result['email']
        ]);
    } else {
        $error = $result['error'];
        $auditLogger->log('email_verification_failed', 'auth', 'failed', [
            'error_message' => $error,
            'token' => substr($token, 0, 10) . '...'
        ]);
    }
} else {
    $error = 'No verification token provided.';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Email Verification — BulkReplace</title>
    <link rel="icon" type="image/png" href="/img/logo.png">
    <link rel="stylesheet" href="/assets/main.css">
    <style>
        .auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;}
        .auth-card{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:40px;width:100%;max-width:500px;box-shadow:0 24px 80px rgba(0,0,0,.5);animation:fadeUp .3s ease;text-align:center;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:none}}
        .icon-success{width:80px;height:80px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;animation:scaleIn .5s ease;}
        .icon-error{width:80px;height:80px;background:linear-gradient(135deg,#ef4444,#dc2626);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;animation:scaleIn .5s ease;}
        @keyframes scaleIn{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
        .icon-success svg, .icon-error svg{width:48px;height:48px;stroke:#fff;stroke-width:3;fill:none;}
        h1{font-size:28px;font-weight:800;color:#fff;margin:0 0 12px;}
        p{color:var(--muted);line-height:1.6;margin:0 0 24px;}
    </style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <?php if ($success): ?>
            <div class="icon-success">
                <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <h1>Email Verified!</h1>
            <p>Your email address has been successfully verified. You can now access all features of your BulkReplace account.</p>
            <a href="<?= APP_URL ?>/dashboard/" class="btn btn-amber" style="display:inline-flex;align-items:center;gap:8px;">
                Go to Dashboard →
            </a>
        <?php else: ?>
            <div class="icon-error">
                <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </div>
            <h1>Verification Failed</h1>
            <p><?= htmlspecialchars($error) ?></p>
            <?php if (isLoggedIn()): ?>
                <a href="<?= APP_URL ?>/auth/resend_verification.php" class="btn btn-amber" style="display:inline-flex;align-items:center;gap:8px;">
                    Resend Verification Email
                </a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/auth/login.php" class="btn btn-amber" style="display:inline-flex;align-items:center;gap:8px;">
                    Back to Login
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top:24px;padding-top:24px;border-top:1px solid var(--border);">
            <a href="<?= APP_URL ?>/" style="color:var(--muted);text-decoration:none;font-size:13px;">← Back to Home</a>
        </div>
    </div>
</div>
</body>
</html>
