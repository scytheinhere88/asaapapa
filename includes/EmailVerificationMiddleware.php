<?php

class EmailVerificationMiddleware
{
    private $conn;
    private $exemptPaths = [
        '/auth/verify_email.php',
        '/auth/resend_verification.php',
        '/auth/logout.php',
        '/api/'
    ];

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function requireVerifiedEmail()
    {
        if (!isset($_SESSION['uid'])) {
            return;
        }

        $currentPath = $_SERVER['PHP_SELF'] ?? '';

        foreach ($this->exemptPaths as $exemptPath) {
            if (strpos($currentPath, $exemptPath) !== false) {
                return;
            }
        }

        $stmt = $this->conn->prepare("SELECT email_verified FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['uid']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !$user['email_verified']) {
            if (strpos($currentPath, '/dashboard/') !== false || strpos($currentPath, '/admin/') !== false) {
                header('Location: ' . APP_URL . '/auth/resend_verification.php');
                exit;
            }
        }
    }

    public function isEmailVerified($userId)
    {
        $stmt = $this->conn->prepare("SELECT email_verified FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['email_verified'] == 1;
    }
}
