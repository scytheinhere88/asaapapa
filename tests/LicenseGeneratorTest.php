<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/LicenseGenerator.php';

class LicenseGeneratorTest extends TestCase
{
    private $conn;
    private $licenseGenerator;

    protected function setUp(): void
    {
        $this->conn = db();
        $this->licenseGenerator = new LicenseGenerator($this->conn);

        $this->conn->exec("DELETE FROM licenses");
        $this->conn->exec("DELETE FROM users");
    }

    public function testGenerateLicenseKey()
    {
        $prefix = 'PRO';
        $licenseKey = $this->licenseGenerator->generateLicenseKey($prefix);

        $this->assertStringStartsWith($prefix . '-', $licenseKey);
        $this->assertMatchesRegularExpression('/^PRO-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $licenseKey);
    }

    public function testGenerateUniqueLicenseKeys()
    {
        $key1 = $this->licenseGenerator->generateLicenseKey('TEST');
        $key2 = $this->licenseGenerator->generateLicenseKey('TEST');

        $this->assertNotEquals($key1, $key2, 'License keys should be unique');
    }

    public function testCreateLicense()
    {
        $licenseData = [
            'product_id' => 'prod_123',
            'product_slug' => 'pro-monthly',
            'email' => 'test@example.com',
            'sale_id' => 'sale_456'
        ];

        $licenseKey = $this->licenseGenerator->createLicense($licenseData);

        $this->assertNotEmpty($licenseKey);

        $stmt = $this->conn->prepare("SELECT * FROM licenses WHERE license_key = ?");
        $stmt->execute([$licenseKey]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotFalse($license);
        $this->assertEquals('prod_123', $license['product_id']);
        $this->assertEquals('pro-monthly', $license['product_slug']);
        $this->assertEquals('test@example.com', $license['email']);
        $this->assertEquals('inactive', $license['status']);
    }

    public function testActivateLicense()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, plan) VALUES (1, 'user@test.com', 'Test User', 'hash', 'free')");

        $licenseKey = $this->licenseGenerator->createLicense([
            'product_id' => 'prod_789',
            'product_slug' => 'pro-monthly',
            'email' => 'user@test.com',
            'sale_id' => 'sale_789'
        ]);

        $result = $this->licenseGenerator->activateLicense($licenseKey, 1);

        $this->assertTrue($result['success']);

        $stmt = $this->conn->prepare("SELECT * FROM licenses WHERE license_key = ?");
        $stmt->execute([$licenseKey]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('active', $license['status']);
        $this->assertEquals(1, $license['user_id']);
        $this->assertNotNull($license['activated_at']);
    }

    public function testCannotActivateSameLicenseTwice()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, plan) VALUES (1, 'user@test.com', 'Test User', 'hash', 'free')");

        $licenseKey = $this->licenseGenerator->createLicense([
            'product_id' => 'prod_999',
            'product_slug' => 'pro-monthly',
            'email' => 'user@test.com',
            'sale_id' => 'sale_999'
        ]);

        $this->licenseGenerator->activateLicense($licenseKey, 1);
        $result = $this->licenseGenerator->activateLicense($licenseKey, 1);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already activated', $result['error']);
    }

    public function testValidateLicense()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, plan) VALUES (1, 'user@test.com', 'Test User', 'hash', 'free')");

        $licenseKey = $this->licenseGenerator->createLicense([
            'product_id' => 'prod_111',
            'product_slug' => 'pro-monthly',
            'email' => 'user@test.com',
            'sale_id' => 'sale_111'
        ]);

        $this->licenseGenerator->activateLicense($licenseKey, 1);

        $isValid = $this->licenseGenerator->validateLicense($licenseKey);
        $this->assertTrue($isValid);

        $invalidKey = 'INVALID-KEY-1234';
        $isValid = $this->licenseGenerator->validateLicense($invalidKey);
        $this->assertFalse($isValid);
    }

    public function testRevokeLicense()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, plan) VALUES (1, 'user@test.com', 'Test User', 'hash', 'free')");

        $licenseKey = $this->licenseGenerator->createLicense([
            'product_id' => 'prod_222',
            'product_slug' => 'pro-monthly',
            'email' => 'user@test.com',
            'sale_id' => 'sale_222'
        ]);

        $this->licenseGenerator->activateLicense($licenseKey, 1);
        $result = $this->licenseGenerator->revokeLicense($licenseKey);

        $this->assertTrue($result['success']);

        $stmt = $this->conn->prepare("SELECT status FROM licenses WHERE license_key = ?");
        $stmt->execute([$licenseKey]);
        $license = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertEquals('revoked', $license['status']);
    }

    public function testGetLicenseInfo()
    {
        $licenseKey = $this->licenseGenerator->createLicense([
            'product_id' => 'prod_333',
            'product_slug' => 'platinum-yearly',
            'email' => 'premium@test.com',
            'sale_id' => 'sale_333'
        ]);

        $info = $this->licenseGenerator->getLicenseInfo($licenseKey);

        $this->assertNotFalse($info);
        $this->assertEquals('prod_333', $info['product_id']);
        $this->assertEquals('platinum-yearly', $info['product_slug']);
        $this->assertEquals('premium@test.com', $info['email']);
    }

    public function testExpiredLicenseValidation()
    {
        $this->conn->exec("INSERT INTO users (id, email, name, password, plan) VALUES (1, 'user@test.com', 'Test User', 'hash', 'free')");

        $licenseKey = $this->licenseGenerator->createLicense([
            'product_id' => 'prod_444',
            'product_slug' => 'pro-monthly',
            'email' => 'user@test.com',
            'sale_id' => 'sale_444'
        ]);

        $this->licenseGenerator->activateLicense($licenseKey, 1);

        $this->conn->exec("UPDATE licenses SET expires_at = datetime('now', '-1 day') WHERE license_key = '$licenseKey'");

        $isValid = $this->licenseGenerator->validateLicense($licenseKey);
        $this->assertFalse($isValid, 'Expired license should not be valid');
    }
}
