<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/EmailVerification.php';

class EmailVerificationTest extends TestCase
{
    private $conn;
    private $emailVerification;

    protected function setUp(): void
    {
        $this->conn = db();
        $this->emailVerification = new EmailVerification($this->conn);

        $this->conn->exec("DELETE FROM users");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS email_verification_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT UNIQUE NOT NULL,
            email TEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            used_at DATETIME NULL
        )");

        $this->conn->exec("DELETE FROM email_verification_tokens");
    }

    public function testGenerateVerificationToken()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password) VALUES (1, 'test@example.com', 'Test', 'hash')");

        $token = $this->emailVerification->generateVerificationToken(1, 'test@example.com');

        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));

        $stmt = $this->conn->prepare("SELECT * FROM email_verification_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($tokenData);
        $this->assertEquals(1, $tokenData['user_id']);
        $this->assertEquals('test@example.com', $tokenData['email']);
        $this->assertNull($tokenData['used_at']);
    }

    public function testTokenExpirationSet()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password) VALUES (1, 'test@example.com', 'Test', 'hash')");

        $token = $this->emailVerification->generateVerificationToken(1, 'test@example.com');

        $stmt = $this->conn->prepare("SELECT expires_at FROM email_verification_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        $expiresAt = strtotime($tokenData['expires_at']);
        $expectedExpiry = strtotime('+24 hours');

        $this->assertGreaterThan(time(), $expiresAt);
        $this->assertLessThanOrEqual($expectedExpiry + 10, $expiresAt);
    }

    public function testVerifyValidToken()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, email_verified) VALUES (1, 'test@example.com', 'Test', 'hash', 0)");

        $token = $this->emailVerification->generateVerificationToken(1, 'test@example.com');
        $result = $this->emailVerification->verifyToken($token);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals('test@example.com', $result['email']);

        $stmt = $this->conn->prepare("SELECT email_verified FROM users WHERE id = 1");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $user['email_verified']);
    }

    public function testVerifyInvalidToken()
    {
        $result = $this->emailVerification->verifyToken('invalid_token_12345');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid', $result['error']);
    }

    public function testCannotReuseToken()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, email_verified) VALUES (1, 'test@example.com', 'Test', 'hash', 0)");

        $token = $this->emailVerification->generateVerificationToken(1, 'test@example.com');

        $this->emailVerification->verifyToken($token);
        $result = $this->emailVerification->verifyToken($token);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already been used', $result['error']);
    }

    public function testExpiredTokenRejected()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, email_verified) VALUES (1, 'test@example.com', 'Test', 'hash', 0)");

        $token = $this->emailVerification->generateVerificationToken(1, 'test@example.com');

        $this->conn->exec("UPDATE email_verification_tokens SET expires_at = datetime('now', '-1 day') WHERE token = '$token'");

        $result = $this->emailVerification->verifyToken($token);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expired', $result['error']);
    }

    public function testIsEmailVerified()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, email_verified) VALUES (1, 'verified@example.com', 'Test', 'hash', 1)");
        $this->conn->exec("INSERT INTO users (id, email, name, password, email_verified) VALUES (2, 'unverified@example.com', 'Test', 'hash', 0)");

        $this->assertTrue($this->emailVerification->isEmailVerified(1));
        $this->assertFalse($this->emailVerification->isEmailVerified(2));
    }

    public function testCleanupExpiredTokens()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password) VALUES (1, 'test@example.com', 'Test', 'hash')");

        $token1 = $this->emailVerification->generateVerificationToken(1, 'test@example.com');

        $this->conn->exec("UPDATE email_verification_tokens SET expires_at = datetime('now', '-1 day') WHERE token = '$token1'");

        $token2 = $this->emailVerification->generateVerificationToken(1, 'test@example.com');

        $this->emailVerification->cleanupExpiredTokens();

        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM email_verification_tokens WHERE used_at IS NULL");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $this->assertEquals(1, $count, 'Only non-expired token should remain');
    }
}
