# Deployment Checklist

Use this checklist before deploying to production.

## Pre-Deployment

### 1. Environment Configuration

- [ ] `.env` file configured with production values
- [ ] Database credentials correct
- [ ] SMTP settings configured and tested
- [ ] API keys added (OpenAI, Anthropic, Google Places)
- [ ] Gumroad tokens configured
- [ ] `CRON_AUTH_KEY` set to random secure value
- [ ] `BACKUP_ENCRYPTION_KEY` set to 64-character hex
- [ ] `APP_URL` set to production domain

### 2. Security

- [ ] `.env` file NOT committed to git
- [ ] Strong admin password set in `.env`
- [ ] Database password is strong and unique
- [ ] HTTPS enabled on production server
- [ ] Admin IP whitelist configured (if using)
- [ ] Email verification enabled
- [ ] Rate limiting configured
- [ ] Security headers enabled

### 3. Database

- [ ] Database created
- [ ] Database user has correct permissions
- [ ] AutoMigration will run on first load
- [ ] Backup directory writable (`backups/`)
- [ ] Session directory writable (`sessions/`)

### 4. Email

- [ ] SMTP credentials tested
- [ ] Email templates reviewed
- [ ] "From" email and name configured
- [ ] Welcome email tested
- [ ] Verification email tested

### 5. Payment Integration

- [ ] Gumroad webhook URL configured
- [ ] Product mappings verified
- [ ] License activation tested
- [ ] Refund handling tested
- [ ] Webhook signature verification enabled

### 6. Testing

- [ ] All unit tests passing
  ```bash
  ./vendor/bin/phpunit
  ```
- [ ] Registration flow tested
- [ ] Email verification tested
- [ ] Login/logout tested
- [ ] Payment flow tested
- [ ] Autopilot feature tested (if using)

## Deployment Steps

### 1. Upload Files

```bash
# Exclude these from upload:
- .git/
- .env (upload separately)
- node_modules/
- tests/
- coverage/
- *.log files
```

### 2. Set File Permissions

```bash
chmod 755 backups/
chmod 755 sessions/
chmod 755 data/
chmod 644 .env
chmod 644 config.php
```

### 3. Install Dependencies

```bash
php composer.phar install --no-dev
```

### 4. Database Initialization

- Visit any page - AutoMigration runs automatically
- Or run manually:
  ```php
  require_once 'config.php';
  require_once 'includes/AutoMigration.php';
  $migration = new AutoMigration(db());
  $migration->runMigrations();
  ```

### 5. Create Admin Account

- Register first user account
- Manually set as admin in database:
  ```sql
  UPDATE users SET plan = 'lifetime' WHERE id = 1;
  ```

### 6. Configure Cron Jobs

Add to crontab:

```bash
# Email queue processing (every 5 minutes)
*/5 * * * * curl -s "https://your-domain.com/api/cron_email_queue.php?key=YOUR_CRON_KEY" > /dev/null

# Daily backup (2 AM)
0 2 * * * curl -s "https://your-domain.com/api/cron_backup.php?key=YOUR_CRON_KEY" > /dev/null

# Cleanup (daily at 3 AM)
0 3 * * * curl -s "https://your-domain.com/api/cron_cleanup.php?key=YOUR_CRON_KEY" > /dev/null

# Email verification cleanup (daily at 4 AM)
0 4 * * * curl -s "https://your-domain.com/api/cron_email_verification_cleanup.php?key=YOUR_CRON_KEY" > /dev/null

# Plan downgrade (daily at 1 AM)
0 1 * * * curl -s "https://your-domain.com/api/cron_plan_downgrade.php?key=YOUR_CRON_KEY" > /dev/null

# Email expiry warnings (daily at 9 AM)
0 9 * * * curl -s "https://your-domain.com/api/cron_email_expiry_warnings.php?key=YOUR_CRON_KEY" > /dev/null

# Usage warnings (daily at 10 AM)
0 10 * * * curl -s "https://your-domain.com/api/cron_usage_warnings.php?key=YOUR_CRON_KEY" > /dev/null

# Monitoring (every hour)
0 * * * * curl -s "https://your-domain.com/api/cron_monitoring.php?key=YOUR_CRON_KEY" > /dev/null

# Webhook retry (every 30 minutes)
*/30 * * * * curl -s "https://your-domain.com/api/cron_webhook_retry.php?key=YOUR_CRON_KEY" > /dev/null

# Security maintenance (every 6 hours)
0 */6 * * * curl -s "https://your-domain.com/api/cron_security_maintenance.php?key=YOUR_CRON_KEY" > /dev/null

# Heartbeat monitor (every 15 minutes)
*/15 * * * * curl -s "https://your-domain.com/api/cron_heartbeat_monitor.php?key=YOUR_CRON_KEY" > /dev/null
```

## Post-Deployment

### 1. Smoke Tests

- [ ] Homepage loads correctly
- [ ] Registration works
- [ ] Email verification email received
- [ ] Login works
- [ ] Dashboard accessible
- [ ] Admin panel accessible
- [ ] Payment webhook works

### 2. Monitoring

- [ ] Check error logs
  ```bash
  tail -f php_errors.log
  ```
- [ ] Monitor cron job execution
  ```sql
  SELECT * FROM cron_heartbeats ORDER BY last_execution DESC;
  ```
- [ ] Check email queue
  ```sql
  SELECT status, COUNT(*) FROM email_queue GROUP BY status;
  ```
- [ ] Monitor system metrics
  - Visit `/admin/monitoring.php`

### 3. Security Verification

- [ ] HTTPS working correctly
- [ ] Admin panel requires login
- [ ] Email verification enforced
- [ ] Rate limiting working
- [ ] CSRF protection active
- [ ] No sensitive data in logs

### 4. Backup Verification

- [ ] First backup created successfully
- [ ] Backup file encrypted
- [ ] Backup download works from admin panel
- [ ] Backup restoration tested on dev environment

## Rollback Plan

If issues occur:

1. **Database:** Keep backup before migration
2. **Files:** Keep previous version in separate directory
3. **Rollback command:**
   ```bash
   mv current production_backup
   mv previous_version current
   ```

## Performance Optimization

### After First Week

- [ ] Review slow query log
- [ ] Add missing database indexes
- [ ] Optimize email queue processing
- [ ] Review API rate limits
- [ ] Check backup file sizes
- [ ] Monitor disk usage

### Ongoing

- [ ] Weekly backup verification
- [ ] Monthly security audit
- [ ] Quarterly dependency updates
- [ ] Review and rotate logs

## Troubleshooting

### Email Not Sending

1. Check SMTP credentials in `.env`
2. Check email queue: `SELECT * FROM email_queue WHERE status = 'failed'`
3. Test SMTP connection manually
4. Check email_deliverability in admin panel

### Database Connection Issues

1. Verify credentials in `.env`
2. Check database user permissions
3. Verify database exists
4. Check PDO extension enabled

### Cron Jobs Not Running

1. Verify cron auth key matches
2. Check cron heartbeats table
3. Review cron execution logs
4. Verify cURL available on server

### Payment Webhooks Not Working

1. Check Gumroad webhook URL
2. Verify webhook signature
3. Review webhook retry queue
4. Check error logs for details

## Support Contacts

- Database Admin: [contact]
- Server Admin: [contact]
- Payment Support: Gumroad
- Email Support: SMTP provider

---

## Final Checklist

Before going live:

- [ ] All tests passing
- [ ] .env configured correctly
- [ ] Database initialized
- [ ] Cron jobs configured
- [ ] Email sending works
- [ ] Payment integration tested
- [ ] Backups working
- [ ] HTTPS enabled
- [ ] Monitoring active
- [ ] Documentation reviewed

**Date Deployed:** __________________
**Deployed By:** __________________
**Production URL:** __________________

---

**Status:** Ready for Production ✅
