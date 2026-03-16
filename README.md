# BulkReplace SaaS Platform

Professional bulk content replacement platform with AI-powered automation, comprehensive security, and enterprise-grade features.

## Quick Start

### 1. Install Dependencies

```bash
php composer.phar install
```

### 2. Configure Environment

Copy `.env.example` to `.env` and configure your settings.

### 3. Access the Platform

- **Frontend:** `http://your-domain.com`
- **Admin Panel:** `http://your-domain.com/admin/`
- **Dashboard:** `http://your-domain.com/dashboard/`

## Features

- ✅ **Email Verification** - Secure user registration with verified emails
- ✅ **AI Autopilot** - Automated content analysis with OpenAI/Anthropic
- ✅ **CSV Generator** - Professional CSV parsing and generation
- ✅ **License Management** - Gumroad integration with automated license activation
- ✅ **Rate Limiting** - API protection with intelligent throttling
- ✅ **Two-Factor Authentication** - Optional 2FA for enhanced security
- ✅ **Comprehensive Analytics** - Track usage, revenue, and user behavior
- ✅ **Automated Backups** - Encrypted daily backups
- ✅ **Email System** - Queue-based email with templates
- ✅ **Audit Logging** - Complete activity tracking

## Recent Improvements (v2.0.0)

### 🔒 Email Verification System
- New users must verify email before full access
- 24-hour secure tokens
- Professional email templates
- Prevents spam and fake accounts

### 📦 Modular Code Architecture
- Refactored 4,355 lines into focused components
- Autopilot: 3 modules (JobManager, DomainProcessor, Renderer)
- CSV: 3 modules (Parser, Generator, Validator)
- Better maintainability and testability

### ✅ Comprehensive Test Suite
- 52 unit tests covering critical functions
- ~80% test coverage on security paths
- Automated regression prevention
- TDD-ready architecture

**Read More:** [IMPROVEMENTS.md](IMPROVEMENTS.md) | [CHANGELOG.md](CHANGELOG.md)

## Documentation

- **[DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md)** - Complete developer onboarding
- **[IMPROVEMENTS.md](IMPROVEMENTS.md)** - Detailed technical improvements
- **[CHANGELOG.md](CHANGELOG.md)** - Version history
- **[tests/README.md](tests/README.md)** - Test suite guide
- **[UPGRADE_SUMMARY.txt](UPGRADE_SUMMARY.txt)** - Quick upgrade summary

## Testing

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test
./vendor/bin/phpunit tests/SecurityManagerTest.php

# Generate coverage report
./vendor/bin/phpunit --coverage-html coverage/
```

**Expected:** 52 tests, 0 failures ✅

## Tech Stack

- **Backend:** PHP 7.3+ with PDO
- **Database:** MySQL/InnoDB
- **Payment:** Gumroad webhooks
- **AI:** OpenAI GPT-4o-mini + Anthropic
- **Email:** PHPMailer with SMTP
- **Testing:** PHPUnit 9.6

## Security Features

- ✅ Email verification for all new users
- ✅ BCrypt password hashing (cost 12)
- ✅ CSRF protection on all forms
- ✅ Rate limiting per endpoint
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (input/output sanitization)
- ✅ Session fingerprinting
- ✅ Two-factor authentication
- ✅ Admin IP whitelist
- ✅ Comprehensive audit logging

## Project Structure

```
project/
├── admin/              # Admin panel
├── api/                # API endpoints & cron jobs
├── auth/               # Authentication pages
├── dashboard/          # User dashboard
├── includes/           # Core classes
│   ├── autopilot/      # Autopilot modules
│   ├── csv/            # CSV modules
│   └── *.php           # Core system classes
├── tests/              # PHPUnit tests
├── assets/             # CSS, JS, images
├── backups/            # Encrypted backups
└── vendor/             # Composer dependencies
```

## License

Proprietary - All rights reserved

## Support

For technical issues or questions, refer to the documentation files listed above.

---

**Version:** 2.0.0
**Last Updated:** 2024-03-16
**Status:** ✅ Production Ready
