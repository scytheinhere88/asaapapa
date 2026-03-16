# BulkReplace Test Suite

Comprehensive unit tests for the BulkReplace platform covering security, payment processing, email verification, rate limiting, and CSV operations.

## Setup

Install PHPUnit and dependencies:

```bash
php composer.phar install
```

## Running Tests

Run all tests:

```bash
./vendor/bin/phpunit
```

Run specific test file:

```bash
./vendor/bin/phpunit tests/SecurityManagerTest.php
```

Run with coverage report:

```bash
./vendor/bin/phpunit --coverage-html coverage/
```

## Test Coverage

### SecurityManagerTest
Tests for authentication and security features:
- Password strength validation
- Password hashing and verification
- Input sanitization and XSS prevention
- CSRF token generation
- Email validation
- SQL injection prevention
- Session fingerprinting

### RateLimiterTest
Tests for API rate limiting:
- Request throttling under limit
- Request blocking over limit
- Window reset behavior
- Independent endpoint limits
- Independent user limits
- Remaining attempts tracking
- Manual limit reset

### LicenseGeneratorTest
Tests for license key management:
- License key generation with prefixes
- Unique key generation
- License creation and storage
- License activation
- Duplicate activation prevention
- License validation
- License revocation
- Expiration handling

### EmailVerificationTest
Tests for email verification flow:
- Verification token generation
- Token expiration (24 hours)
- Valid token verification
- Invalid token rejection
- Token reuse prevention
- Expired token handling
- Email verification status
- Expired token cleanup

### CsvParserTest
Tests for CSV parsing functionality:
- Valid CSV parsing
- Different delimiter support
- Delimiter auto-detection
- Header validation
- Quoted field handling
- Empty file handling
- File size limits
- String parsing

## Test Database

Tests use an in-memory SQLite database that is recreated for each test. No actual production data is affected.

## Writing New Tests

1. Create test file in `tests/` directory
2. Extend `PHPUnit\Framework\TestCase`
3. Use `setUp()` to initialize test environment
4. Use `tearDown()` to clean up resources
5. Follow naming convention: `test{FeatureName}`

Example:

```php
<?php
use PHPUnit\Framework\TestCase;

class MyFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        // Initialize
    }

    public function testMyFeature()
    {
        $this->assertTrue(true);
    }
}
```

## Continuous Integration

Tests should be run before every deployment:

```bash
./vendor/bin/phpunit --testdox
```

## Coverage Goals

- Security functions: 90%+
- Payment processing: 85%+
- Email system: 80%+
- API endpoints: 75%+

## Best Practices

1. **Test one thing at a time** - Each test should verify a single behavior
2. **Use descriptive names** - Test names should explain what they verify
3. **Clean up after tests** - Use tearDown() to prevent test pollution
4. **Mock external services** - Don't rely on external APIs in tests
5. **Test edge cases** - Include tests for error conditions and boundary values
