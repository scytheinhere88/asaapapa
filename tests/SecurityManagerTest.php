<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/SecurityManager.php';

class SecurityManagerTest extends TestCase
{
    private $conn;
    private $securityManager;

    protected function setUp(): void
    {
        $this->conn = db();
        $this->securityManager = new SecurityManager($this->conn);

        $this->conn->exec("DELETE FROM users");
    }

    public function testPasswordStrengthValidation()
    {
        $weakPassword = 'weak';
        $result = $this->securityManager->validatePasswordStrength($weakPassword);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);

        $strongPassword = 'StrongP@ssw0rd123';
        $result = $this->securityManager->validatePasswordStrength($strongPassword);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testPasswordHashing()
    {
        $password = 'MySecurePassword123!';
        $hash = $this->securityManager->hashPassword($password);

        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    public function testPasswordVerification()
    {
        $password = 'TestPassword123!';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertTrue($this->securityManager->verifyPassword($password, $hash));
        $this->assertFalse($this->securityManager->verifyPassword('WrongPassword', $hash));
    }

    public function testSanitizeInput()
    {
        $maliciousInput = '<script>alert("XSS")</script>';
        $sanitized = $this->securityManager->sanitizeInput($maliciousInput);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('</script>', $sanitized);
    }

    public function testEscapeOutput()
    {
        $input = '<b>Bold</b> & "quotes"';
        $escaped = $this->securityManager->escapeOutput($input);

        $this->assertStringContainsString('&lt;b&gt;', $escaped);
        $this->assertStringContainsString('&amp;', $escaped);
        $this->assertStringContainsString('&quot;', $escaped);
    }

    public function testGenerateToken()
    {
        $token1 = $this->securityManager->generateToken();
        $token2 = $this->securityManager->generateToken();

        $this->assertEquals(64, strlen($token1));
        $this->assertEquals(64, strlen($token2));
        $this->assertNotEquals($token1, $token2);
    }

    public function testValidateEmail()
    {
        $this->assertTrue($this->securityManager->validateEmail('valid@email.com'));
        $this->assertTrue($this->securityManager->validateEmail('user.name+tag@example.co.uk'));

        $this->assertFalse($this->securityManager->validateEmail('invalid'));
        $this->assertFalse($this->securityManager->validateEmail('invalid@'));
        $this->assertFalse($this->securityManager->validateEmail('@invalid.com'));
    }

    public function testStripTags()
    {
        $html = '<p>Hello <strong>World</strong></p>';
        $stripped = $this->securityManager->stripTags($html);

        $this->assertEquals('Hello World', $stripped);
        $this->assertStringNotContainsString('<p>', $stripped);
        $this->assertStringNotContainsString('<strong>', $stripped);
    }

    public function testIsSecurePassword()
    {
        $this->assertFalse($this->securityManager->isSecurePassword('short'));
        $this->assertFalse($this->securityManager->isSecurePassword('NoNumbers!'));
        $this->assertFalse($this->securityManager->isSecurePassword('noupppercase123'));
        $this->assertFalse($this->securityManager->isSecurePassword('NOLOWERCASE123'));
        $this->assertFalse($this->securityManager->isSecurePassword('NoSpecialChar123'));

        $this->assertTrue($this->securityManager->isSecurePassword('Valid123!Pass'));
        $this->assertTrue($this->securityManager->isSecurePassword('MyP@ssw0rd2024'));
    }

    public function testPreventSQLInjection()
    {
        $maliciousEmail = "admin'--";

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$maliciousEmail]);
        $result = $stmt->fetch();

        $this->assertFalse($result);
    }

    public function testSessionFingerprinting()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $fingerprint1 = $this->securityManager->generateSessionFingerprint();

        $_SERVER['HTTP_USER_AGENT'] = 'Different Browser';
        $fingerprint2 = $this->securityManager->generateSessionFingerprint();

        $this->assertNotEquals($fingerprint1, $fingerprint2);
    }
}
