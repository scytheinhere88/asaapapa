# BulkReplace Developer Guide

Quick start guide for developers working on the BulkReplace platform.

## Project Structure

```
project/
├── admin/              # Admin panel pages
├── api/                # API endpoints & cron jobs
├── auth/               # Authentication pages
├── dashboard/          # User dashboard pages
├── includes/           # Core classes & utilities
│   ├── autopilot/      # Autopilot feature modules
│   ├── csv/            # CSV processing modules
│   └── *.php           # Core system classes
├── assets/             # CSS, JS, images
├── backups/            # Database backups (encrypted)
├── data/               # JSON data files
├── tests/              # PHPUnit test suite
├── vendor/             # Composer dependencies
├── config.php          # Main configuration
└── .env                # Environment variables
```

## Quick Start

### 1. Install Dependencies

```bash
# Install Composer dependencies
php composer.phar install

# Or if composer is installed globally
composer install
```

### 2. Configure Environment

Copy `.env.example` to `.env` and configure:

```env
# Database
DB_HOST=localhost
DB_NAME=bulkreplace
DB_USER=your_user
DB_PASS=your_password

# Email (SMTP)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your@email.com
SMTP_PASS=your_password
SMTP_FROM_EMAIL=noreply@bulkreplace.com
SMTP_FROM_NAME=BulkReplace

# API Keys (optional for development)
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GOOGLE_PLACES_API_KEY=...

# Gumroad (for payments)
GUMROAD_ACCESS_TOKEN=...
GUMROAD_PING_TOKEN=...

# Security
CRON_AUTH_KEY=random_secure_key_here
BACKUP_ENCRYPTION_KEY=64_character_hex_key_here
```

### 3. Initialize Database

The database will auto-initialize on first load via `AutoMigration.php`.

Alternatively, run migrations manually:

```php
require_once 'config.php';
require_once 'includes/AutoMigration.php';

$migration = new AutoMigration(db());
$migration->runMigrations();
```

### 4. Run Tests

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit tests/SecurityManagerTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/

# Run with detailed output
./vendor/bin/phpunit --testdox
```

### 5. Local Development

```bash
# PHP built-in server
php -S localhost:8000

# Or use your preferred local server (XAMPP, WAMP, Laravel Valet, etc.)
```

Visit: `http://localhost:8000`

## Core Components

### Security Manager

Handles authentication, password security, and input/output sanitization.

```php
require_once 'includes/SecurityManager.php';

$security = new SecurityManager(db());

// Validate password strength
$result = $security->validatePasswordStrength($password);

// Hash password
$hash = $security->hashPassword($password);

// Verify password
$valid = $security->verifyPassword($password, $hash);

// Sanitize input
$clean = $security->sanitizeInput($_POST['data']);

// Escape output
echo $security->escapeOutput($userInput);
```

### Email Verification

Manages email verification tokens and verification flow.

```php
require_once 'includes/EmailVerification.php';

$emailVerification = new EmailVerification(db());

// Send verification email
$emailVerification->sendVerificationEmail($userId, $email, $name);

// Verify token
$result = $emailVerification->verifyToken($token);

// Check if verified
$isVerified = $emailVerification->isEmailVerified($userId);

// Resend verification
$result = $emailVerification->resendVerificationEmail($userId);
```

### Rate Limiter

Prevents API abuse through request throttling.

```php
require_once 'includes/RateLimiter.php';

$rateLimiter = new RateLimiter(db());

// Check rate limit
$result = $rateLimiter->checkLimit(
    $identifier,    // User ID or IP
    $endpoint,      // '/api/scraper'
    $maxAttempts,   // 10
    $windowSeconds  // 60
);

if (!$result['allowed']) {
    http_response_code(429);
    die("Rate limit exceeded. Retry after {$result['retry_after']} seconds.");
}

// Block identifier manually
$rateLimiter->blockIdentifier($identifier, $endpoint, $durationSeconds);

// Reset limit
$rateLimiter->resetLimit($identifier, $endpoint);
```

### License Generator

Manages product licenses and activations.

```php
require_once 'includes/LicenseGenerator.php';

$licenseGen = new LicenseGenerator(db());

// Generate license key
$key = $licenseGen->generateLicenseKey('PRO');

// Create license
$licenseKey = $licenseGen->createLicense([
    'product_id' => 'prod_123',
    'product_slug' => 'pro-monthly',
    'email' => 'user@example.com',
    'sale_id' => 'sale_456'
]);

// Activate license
$result = $licenseGen->activateLicense($licenseKey, $userId);

// Validate license
$valid = $licenseGen->validateLicense($licenseKey);

// Revoke license
$result = $licenseGen->revokeLicense($licenseKey);
```

### Autopilot Components

```php
// Job Manager
require_once 'includes/autopilot/AutopilotJobManager.php';

$jobManager = new AutopilotJobManager(db());

$jobId = $jobManager->createJob($userId, $domains, $keywordHint, $userHints);
$job = $jobManager->getJob($jobId);
$progress = $jobManager->getJobProgress($jobId);
$results = $jobManager->getJobResults($jobId);

// Domain Processor
require_once 'includes/autopilot/AutopilotDomainProcessor.php';

$processor = new AutopilotDomainProcessor(db(), $openaiApiKey);

$domains = $processor->getPendingDomains($jobId, 10);
$scrapeResult = $processor->scrapeDomainContent($domain);
$text = $processor->extractTextFromHtml($html);
$analysis = $processor->analyzeWithAI($domain, $content, $hint);
```

### CSV Components

```php
// CSV Parser
require_once 'includes/csv/CsvParser.php';

$parser = new CsvParser();
$parser->setDelimiter(',');

$result = $parser->parseFile($filePath);
$result = $parser->parseString($csvString);

$delimiter = $parser->detectDelimiter($filePath);
$validation = $parser->validateHeaders($headers, $requiredColumns);

// CSV Generator
require_once 'includes/csv/CsvGenerator.php';

$generator = new CsvGenerator();
$generator->setDelimiter(',')
          ->setIncludeHeaders(true);

$result = $generator->generate($data, $headers);
$result = $generator->generateFile($data, $filePath, $headers);
$generator->downloadCsv($data, 'export.csv', $headers);

// CSV Validator
require_once 'includes/csv/CsvValidator.php';

$validator = new CsvValidator();

$rules = [
    'email' => ['required' => true, 'email' => true],
    'age' => ['numeric' => true, 'min' => 18]
];

$result = $validator->validateRow($row, $rules);
$result = $validator->validateData($data, $rules);
$sanitized = $validator->sanitizeRow($row, $schema);
```

## Database Helpers

### Query Execution

```php
// Get database connection
$conn = db();

// Simple query
$stmt = $conn->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepared statement (ALWAYS use for user input)
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Insert
$stmt = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
$stmt->execute([$name, $email]);
$userId = $conn->lastInsertId();

// Update
$stmt = $conn->prepare("UPDATE users SET plan = ? WHERE id = ?");
$stmt->execute(['pro', $userId]);

// Delete
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);
```

## Common Patterns

### CSRF Protection

```php
// In forms
<form method="POST">
    <?= csrf_field() ?>
    <!-- form fields -->
</form>

// In handlers
if (!csrf_verify()) {
    die('CSRF validation failed');
}
```

### Audit Logging

```php
require_once 'includes/AuditLogger.php';

$auditLogger = new AuditLogger(db());
$auditLogger->setUserId($userId);

$auditLogger->log('user_login', 'auth', 'success', [
    'target_type' => 'user',
    'target_id' => $userId,
    'request_data' => ['ip' => $_SERVER['REMOTE_ADDR']]
]);
```

### Email Sending

```php
require_once 'includes/EmailSystem.php';

$emailSystem = new EmailSystem(db());

// Send from template
$emailSystem->sendFromTemplate(
    'welcome',           // template key
    $email,              // recipient email
    $name,               // recipient name
    ['user_name' => $name], // variables
    $userId,             // user ID
    5                    // priority (1-5)
);

// Queue custom email
$emailSystem->queueEmail(
    $userId,
    $toEmail,
    $toName,
    $fromEmail,
    $fromName,
    $subject,
    $bodyHtml,
    $bodyText,
    'custom_template',
    3  // priority
);
```

## Testing Guidelines

### Writing Tests

1. **Create test file** in `tests/` directory
2. **Extend PHPUnit TestCase**
3. **Use setUp/tearDown** for initialization/cleanup
4. **Test one behavior per test**
5. **Use descriptive test names**

Example:

```php
<?php
use PHPUnit\Framework\TestCase;

class MyFeatureTest extends TestCase
{
    private $conn;

    protected function setUp(): void
    {
        $this->conn = db();
        // Clean database state
        $this->conn->exec("DELETE FROM my_table");
    }

    public function testFeatureBehavior()
    {
        // Arrange
        $input = 'test data';

        // Act
        $result = myFunction($input);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('expected', $result);
    }

    protected function tearDown(): void
    {
        // Cleanup if needed
    }
}
```

### Running Tests

```bash
# All tests
./vendor/bin/phpunit

# Specific test
./vendor/bin/phpunit tests/MyFeatureTest.php

# With output
./vendor/bin/phpunit --testdox

# With coverage
./vendor/bin/phpunit --coverage-html coverage/
```

## Debugging

### Enable Error Display

```php
// In development only
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Database Query Debugging

```php
// Enable PDO error mode
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Log queries
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
error_log("Query: " . $stmt->queryString);
$stmt->execute([1]);
```

### Check Logs

```bash
# PHP error log
tail -f php_errors.log

# Apache/Nginx logs
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

## Code Style

Follow PSR-12 coding standards:

- Use 4 spaces for indentation
- Class names in PascalCase
- Method names in camelCase
- Constants in UPPER_CASE
- Always use type hints where possible
- Write PHPDoc comments for public methods

Example:

```php
<?php

class MyFeature
{
    private $conn;

    /**
     * Initialize feature with database connection
     *
     * @param PDO $conn Database connection
     */
    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Process data and return result
     *
     * @param string $input Input data
     * @return array Result array
     */
    public function processData(string $input): array
    {
        // Implementation
        return ['success' => true];
    }
}
```

## Performance Tips

1. **Use prepared statements** - Faster and more secure
2. **Index database columns** - Speed up queries
3. **Cache query results** - Use QueryCache class
4. **Minimize external API calls** - Cache when possible
5. **Use transactions** - For bulk operations

## Security Checklist

- ✅ Always use prepared statements
- ✅ Validate and sanitize all user input
- ✅ Escape all output
- ✅ Use CSRF tokens on forms
- ✅ Implement rate limiting on APIs
- ✅ Hash passwords with BCrypt
- ✅ Use HTTPS in production
- ✅ Keep dependencies updated
- ✅ Log security events
- ✅ Never commit .env file

## Getting Help

- Check existing tests for examples
- Read class PHPDoc comments
- Review `IMPROVEMENTS.md` for recent changes
- Check audit logs for debugging user issues

## Contributing

1. Write tests for new features
2. Follow PSR-12 code style
3. Update documentation
4. Run tests before commit
5. Use meaningful commit messages

---

Happy coding! 🚀
