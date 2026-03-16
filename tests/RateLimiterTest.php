<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/RateLimiter.php';

class RateLimiterTest extends TestCase
{
    private $conn;
    private $rateLimiter;

    protected function setUp(): void
    {
        $this->conn = db();
        $this->rateLimiter = new RateLimiter($this->conn);

        $this->conn->exec("DELETE FROM rate_limits");
    }

    public function testAllowsRequestUnderLimit()
    {
        $identifier = 'user_123';
        $endpoint = '/api/test';
        $maxAttempts = 5;
        $windowSeconds = 60;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $result = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
            $this->assertTrue($result['allowed'], "Request $i should be allowed");
        }
    }

    public function testBlocksRequestOverLimit()
    {
        $identifier = 'user_456';
        $endpoint = '/api/test';
        $maxAttempts = 3;
        $windowSeconds = 60;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        }

        $result = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->assertFalse($result['allowed'], 'Request over limit should be blocked');
        $this->assertGreaterThan(0, $result['retry_after']);
    }

    public function testResetsAfterWindow()
    {
        $identifier = 'user_789';
        $endpoint = '/api/test';
        $maxAttempts = 2;
        $windowSeconds = 1;

        $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);

        sleep(2);

        $result = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->assertTrue($result['allowed'], 'Should allow after window expires');
    }

    public function testDifferentEndpointsIndependent()
    {
        $identifier = 'user_101';
        $endpoint1 = '/api/endpoint1';
        $endpoint2 = '/api/endpoint2';
        $maxAttempts = 2;
        $windowSeconds = 60;

        $this->rateLimiter->checkLimit($identifier, $endpoint1, $maxAttempts, $windowSeconds);
        $this->rateLimiter->checkLimit($identifier, $endpoint1, $maxAttempts, $windowSeconds);

        $result = $this->rateLimiter->checkLimit($identifier, $endpoint2, $maxAttempts, $windowSeconds);
        $this->assertTrue($result['allowed'], 'Different endpoints should have separate limits');
    }

    public function testDifferentIdentifiersIndependent()
    {
        $identifier1 = 'user_201';
        $identifier2 = 'user_202';
        $endpoint = '/api/test';
        $maxAttempts = 2;
        $windowSeconds = 60;

        $this->rateLimiter->checkLimit($identifier1, $endpoint, $maxAttempts, $windowSeconds);
        $this->rateLimiter->checkLimit($identifier1, $endpoint, $maxAttempts, $windowSeconds);

        $result = $this->rateLimiter->checkLimit($identifier2, $endpoint, $maxAttempts, $windowSeconds);
        $this->assertTrue($result['allowed'], 'Different identifiers should have separate limits');
    }

    public function testReturnsCorrectRemainingAttempts()
    {
        $identifier = 'user_301';
        $endpoint = '/api/test';
        $maxAttempts = 5;
        $windowSeconds = 60;

        $result1 = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->assertEquals(4, $result1['remaining']);

        $result2 = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->assertEquals(3, $result2['remaining']);

        $result3 = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->assertEquals(2, $result3['remaining']);
    }

    public function testBlockDuration()
    {
        $identifier = 'user_401';
        $endpoint = '/api/test';
        $maxAttempts = 2;
        $blockDuration = 10;

        $this->rateLimiter->blockIdentifier($identifier, $endpoint, $blockDuration);

        $result = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, 60);
        $this->assertFalse($result['allowed'], 'Should be blocked');
        $this->assertLessThanOrEqual($blockDuration, $result['retry_after']);
    }

    public function testResetLimit()
    {
        $identifier = 'user_501';
        $endpoint = '/api/test';
        $maxAttempts = 2;
        $windowSeconds = 60;

        $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);

        $result = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->assertFalse($result['allowed']);

        $this->rateLimiter->resetLimit($identifier, $endpoint);

        $result = $this->rateLimiter->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds);
        $this->assertTrue($result['allowed'], 'Should allow after reset');
    }
}
