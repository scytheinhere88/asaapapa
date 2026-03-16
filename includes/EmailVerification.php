<?php

class EmailVerification
{
    private $conn;
    private $emailSystem;

    public function __construct($conn)
    {
        $this->conn = $conn;
        require_once __DIR__ . '/EmailSystem.php';
        $this->emailSystem = new EmailSystem($conn);
    }

    public function generateVerificationToken($userId, $email)
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $this->conn->prepare("
            INSERT INTO email_verification_tokens (user_id, token, email, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $token, $email, $expiresAt]);

        return $token;
    }

    public function sendVerificationEmail($userId, $email, $name)
    {
        $token = $this->generateVerificationToken($userId, $email);
        $verificationUrl = APP_URL . '/auth/verify_email.php?token=' . $token;

        $subject = 'Verify your email address';
        $bodyHtml = $this->getVerificationEmailTemplate($name, $verificationUrl);
        $bodyText = "Hi {$name},\n\nPlease verify your email address by clicking this link:\n{$verificationUrl}\n\nThis link will expire in 24 hours.\n\nIf you didn't create an account, please ignore this email.";

        return $this->emailSystem->queueEmail(
            $userId,
            $email,
            $name,
            null,
            null,
            $subject,
            $bodyHtml,
            $bodyText,
            'email_verification',
            5
        );
    }

    public function verifyToken($token)
    {
        $stmt = $this->conn->prepare("
            SELECT id, user_id, email, expires_at, used_at
            FROM email_verification_tokens
            WHERE token = ?
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            return ['success' => false, 'error' => 'Invalid verification token.'];
        }

        if ($tokenData['used_at']) {
            return ['success' => false, 'error' => 'This verification link has already been used.'];
        }

        if (strtotime($tokenData['expires_at']) < time()) {
            return ['success' => false, 'error' => 'This verification link has expired. Please request a new one.'];
        }

        $updateStmt = $this->conn->prepare("
            UPDATE users
            SET email_verified = 1, email_verified_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$tokenData['user_id']]);

        $markUsedStmt = $this->conn->prepare("
            UPDATE email_verification_tokens
            SET used_at = NOW()
            WHERE id = ?
        ");
        $markUsedStmt->execute([$tokenData['id']]);

        return [
            'success' => true,
            'user_id' => $tokenData['user_id'],
            'email' => $tokenData['email']
        ];
    }

    public function isEmailVerified($userId)
    {
        $stmt = $this->conn->prepare("SELECT email_verified FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['email_verified'] == 1;
    }

    public function resendVerificationEmail($userId)
    {
        $stmt = $this->conn->prepare("SELECT email, name FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'error' => 'User not found.'];
        }

        if ($this->isEmailVerified($userId)) {
            return ['success' => false, 'error' => 'Email already verified.'];
        }

        $checkRecent = $this->conn->prepare("
            SELECT created_at
            FROM email_verification_tokens
            WHERE user_id = ? AND used_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $checkRecent->execute([$userId]);
        $recent = $checkRecent->fetch(PDO::FETCH_ASSOC);

        if ($recent && strtotime($recent['created_at']) > strtotime('-5 minutes')) {
            return ['success' => false, 'error' => 'Please wait 5 minutes before requesting another verification email.'];
        }

        $this->sendVerificationEmail($userId, $user['email'], $user['name']);

        return ['success' => true, 'message' => 'Verification email sent successfully.'];
    }

    public function cleanupExpiredTokens()
    {
        $stmt = $this->conn->prepare("
            DELETE FROM email_verification_tokens
            WHERE expires_at < NOW() AND used_at IS NULL
        ");
        return $stmt->execute();
    }

    private function getVerificationEmailTemplate($name, $verificationUrl)
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); padding: 40px 30px; text-align: center; }
        .header h1 { margin: 0; color: #ffffff; font-size: 28px; font-weight: 800; }
        .content { padding: 40px 30px; }
        .content h2 { color: #1f2937; margin-top: 0; font-size: 24px; }
        .content p { color: #4b5563; margin: 16px 0; }
        .button { display: inline-block; background: #f59e0b; color: #ffffff !important; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; margin: 24px 0; transition: background 0.3s; }
        .button:hover { background: #d97706; }
        .footer { background: #f9fafb; padding: 20px 30px; text-align: center; color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; border-radius: 4px; }
        .warning p { margin: 0; color: #92400e; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BulkReplace</h1>
        </div>
        <div class="content">
            <h2>Verify Your Email Address</h2>
            <p>Hi <strong>{$name}</strong>,</p>
            <p>Thank you for creating a BulkReplace account! To get started, please verify your email address by clicking the button below:</p>
            <div style="text-align: center;">
                <a href="{$verificationUrl}" class="button">Verify Email Address</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style="background: #f3f4f6; padding: 12px; border-radius: 6px; word-break: break-all; font-family: monospace; font-size: 12px;">{$verificationUrl}</p>
            <div class="warning">
                <p><strong>Important:</strong> This verification link will expire in 24 hours.</p>
            </div>
            <p style="margin-top: 32px; color: #6b7280; font-size: 14px;">If you didn't create a BulkReplace account, you can safely ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; 2024 BulkReplace. All rights reserved.</p>
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
