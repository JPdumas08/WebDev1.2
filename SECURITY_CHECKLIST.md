# Security Audit Checklist

## Pre-Deployment Security Audit

### Configuration
- [ ] `APP_ENV` set to `'production'` in `config.php`
- [ ] `display_errors` is OFF in production
- [ ] Error logging is ON and logs are secure
- [ ] Database credentials use strong passwords
- [ ] Database user has minimal required privileges (not root)
- [ ] `.htaccess` file is properly configured
- [ ] HTTPS is enabled and enforced

### Authentication & Sessions
- [ ] Passwords are hashed using `password_hash()`
- [ ] Login uses `password_verify()`
- [ ] Sessions use secure, httponly cookies
- [ ] Session timeout is configured (2 hours default)
- [ ] Session hijacking protection is active
- [ ] User agent validation is working
- [ ] Rate limiting is enabled on login (5 attempts/15 min)
- [ ] CSRF tokens are present on all forms
- [ ] CSRF verification is enforced on all POST requests

### Input Validation
- [ ] All user inputs are validated server-side
- [ ] Email addresses are validated
- [ ] Phone numbers are validated
- [ ] Postal codes are validated
- [ ] Integer inputs have range checks
- [ ] String inputs have length limits
- [ ] Special characters are properly handled
- [ ] File uploads (if any) are validated for type and size

### Output Encoding
- [ ] All database outputs use `htmlspecialchars()`
- [ ] JSON responses are properly encoded
- [ ] JavaScript outputs use proper escaping
- [ ] HTML attributes are escaped
- [ ] No raw `echo` of user input
- [ ] SQL queries use prepared statements only

### Database Security
- [ ] All queries use prepared statements (PDO)
- [ ] No dynamic query building with user input
- [ ] Database errors are logged, not displayed
- [ ] Connection uses PDO::ATTR_EMULATE_PREPARES = false
- [ ] Indexes are created for performance
- [ ] Foreign keys are properly set
- [ ] Database backups are configured

### File Security
- [ ] Sensitive files are protected (.htaccess)
- [ ] Directory browsing is disabled
- [ ] Upload directory is outside webroot (if applicable)
- [ ] File permissions are correct (644 for files, 755 for directories)
- [ ] Config files are not accessible via web
- [ ] No sensitive data in version control
- [ ] `.git` directory is blocked

### Headers & Policies
- [ ] X-Frame-Options header is set
- [ ] X-XSS-Protection header is set
- [ ] X-Content-Type-Options header is set
- [ ] Referrer-Policy is configured
- [ ] Content-Security-Policy is configured (optional)
- [ ] HSTS header is set (if using HTTPS)

### Code Security
- [ ] No `eval()` or similar dangerous functions
- [ ] No `system()`, `exec()`, or shell commands with user input
- [ ] No `include/require` with user-controlled paths
- [ ] No serialized user input
- [ ] Error handling doesn't expose system info
- [ ] No hardcoded credentials
- [ ] Debug code removed from production

### APIs & AJAX
- [ ] API endpoints require authentication
- [ ] API endpoints validate CSRF tokens
- [ ] API rate limiting is implemented
- [ ] API responses don't expose sensitive data
- [ ] JSON responses have proper Content-Type
- [ ] CORS is properly configured (if needed)

### Business Logic
- [ ] Cart operations verify user ownership
- [ ] Order operations verify user ownership
- [ ] Address operations verify user ownership
- [ ] Users can't access other users' data
- [ ] Price manipulation is prevented
- [ ] Quantity limits are enforced
- [ ] Order total is recalculated server-side

### Testing Completed
- [ ] Manual testing of all forms
- [ ] XSS testing on all inputs
- [ ] SQL injection testing
- [ ] CSRF testing
- [ ] Session hijacking testing
- [ ] Brute force testing (rate limiting)
- [ ] Authorization bypass testing
- [ ] File upload testing (if applicable)
- [ ] Payment flow testing

### Monitoring & Logging
- [ ] Error logs are being written
- [ ] Failed login attempts are logged
- [ ] Sensitive operations are logged
- [ ] Log files are secured (not web-accessible)
- [ ] Log rotation is configured
- [ ] Monitoring alerts are set up

### Dependencies & Updates
- [ ] PHP version is up to date
- [ ] Bootstrap/jQuery versions are current
- [ ] Database version is supported
- [ ] No known vulnerabilities in dependencies
- [ ] Update plan is documented

### Backup & Recovery
- [ ] Database backup schedule configured
- [ ] Backup restoration tested
- [ ] File backup configured
- [ ] Disaster recovery plan documented

### Documentation
- [ ] SECURITY_FIXES.md is up to date
- [ ] SETUP_GUIDE.md is accurate
- [ ] Code comments are adequate
- [ ] API documentation exists
- [ ] Admin procedures documented

## Quick Security Test Commands

### Test CSRF Protection
```javascript
// In browser console, try to submit form without CSRF token
document.querySelector('form').submit();
// Should be rejected
```

### Test XSS Protection
```javascript
// Try to inject script in form fields
<script>alert('XSS')</script>
// Should be escaped in output
```

### Test SQL Injection
```sql
-- Try in login field
' OR '1'='1
-- Should be safely handled by prepared statements
```

### Test Rate Limiting
```bash
# Try 6 failed logins quickly
# Should be blocked after 5 attempts
```

### Test Session Security
```javascript
// Check session cookie attributes in DevTools
// Should see: HttpOnly, Secure (if HTTPS), SameSite=Lax
```

## Post-Deployment Monitoring

### Daily Checks
- [ ] Review error logs for anomalies
- [ ] Check failed login attempts
- [ ] Monitor disk space
- [ ] Verify backup completion

### Weekly Checks
- [ ] Review all logs
- [ ] Check for suspicious activity
- [ ] Verify SSL certificate validity
- [ ] Test backup restoration

### Monthly Checks
- [ ] Security audit
- [ ] Dependency updates
- [ ] Performance review
- [ ] User feedback review

## Incident Response Plan

If a security issue is discovered:

1. **Assess Impact**
   - Determine what data may be affected
   - Identify affected users
   - Document the issue

2. **Contain**
   - Take affected systems offline if necessary
   - Change relevant credentials
   - Block suspicious IPs

3. **Remediate**
   - Fix the vulnerability
   - Deploy the fix
   - Verify the fix works

4. **Notify**
   - Inform affected users if required
   - Report to authorities if required
   - Document the incident

5. **Review**
   - Update security procedures
   - Improve monitoring
   - Train staff if needed

## Security Contacts

- **Web Host Security:** [Contact Info]
- **Database Admin:** [Contact Info]
- **Lead Developer:** [Contact Info]
- **System Admin:** [Contact Info]

---

**Last Security Audit:** [DATE]
**Next Scheduled Audit:** [DATE]
**Auditor:** [NAME]
