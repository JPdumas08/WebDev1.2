# Form Validation Testing Guide

## Overview
Comprehensive form validation has been implemented for both Sign Up and Sign In forms with:
- **Frontend validation** for real-time user feedback
- **Backend validation** for security enforcement
- **Rate limiting** to prevent brute-force attacks
- **Password strength meter** with visual feedback
- **CSRF protection** on all forms

## Test Cases

### 1. SIGN UP FORM VALIDATION

#### 1.1 First/Last Name Validation
**Rules:** 2-50 characters, letters/spaces/hyphens only

- ✅ Valid: "John", "Mary Jane", "Jean-Paul", "O'Connor"
- ❌ Invalid: "J" (too short), "1234" (numbers), "John123", "John@" (special chars)
- **Test:** Try entering each and observe real-time error messages

#### 1.2 Email Validation
**Rules:** Valid email format, no spaces, must be unique in database

- ✅ Valid: "user@example.com", "test.user@domain.co.uk"
- ❌ Invalid: "invalid email", "missing@domain", "user @example.com" (space)
- ❌ Invalid: Email already used if registering twice with same email
- **Test:** 
  1. Enter invalid email and watch error appear on blur
  2. Try to register twice with same email
  3. Check browser console for backend error: "This email address is already in use."

#### 1.3 Username Validation
**Rules:** 4-20 characters, letters/numbers/underscores only, must be unique

- ✅ Valid: "john_doe", "User123", "test_user_2024"
- ❌ Invalid: "bob" (too short), "user-name" (hyphen), "user name" (space)
- ❌ Invalid: Username already used if registering twice
- **Test:**
  1. Try "ab" (too short) - should show error
  2. Try "john-doe" (hyphen) - should show error
  3. Register once, try again with same username - should fail

#### 1.4 Password Validation
**Rules:** 8+ chars, 1 uppercase, 1 lowercase, 1 number, 1 special char (!@#$%^&*?)

- ✅ Valid: "Password123!", "MyPass@2024", "Secure#Pass88"
- ❌ Invalid: "password123" (no uppercase), "PASSWORD123!" (no lowercase)
- ❌ Invalid: "Pass123" (no special char), "Pass@" (no number)
- ❌ Invalid: "Pass @123!" (contains space)

**Visual Feedback:**
- ✓ Watch the strength meter change colors as you type:
  - None (gray) - no password
  - Weak (red) - 0-1 requirement met
  - Fair (yellow) - 2-3 requirements met
  - Good (green) - 4 requirements met
  - Strong (dark green) - all 5 requirements met

- ✓ Watch requirement checklist update in real-time:
  - [✓] Minimum 8 characters
  - [✓] One uppercase letter (A-Z)
  - [✓] One lowercase letter (a-z)
  - [✓] One number (0-9)
  - [✓] One special character (!@#$%^&*?)

#### 1.5 Confirm Password Validation
**Rules:** Must match password field exactly

- ✅ Valid: Same text as password field
- ❌ Invalid: Different text from password field
- **Test:** Enter password "Test@123", then type "Test@124" in confirm - should show error

#### 1.6 Terms & Conditions Checkbox
**Rules:** Must be checked

- ✅ Valid: Checkbox checked
- ❌ Invalid: Checkbox unchecked
- **Test:** Try to submit without checking - should show error

#### 1.7 Submit Button Behavior
- When all validations pass: Button becomes enabled, form submits via AJAX
- During submission: Button shows spinner and "Creating Account..." text
- On success: Toast notification shows, modal switches to Sign In
- On error: Toast notification shows error message, button re-enables

### 2. SIGN IN FORM VALIDATION

#### 2.1 Email or Username Field
**Rules:** Valid email OR 4-20 alphanumeric/underscore characters

- ✅ Valid: "user@example.com" or "john_doe"
- ❌ Invalid: "invalid email" (space), "bob" (too short as username)
- **Test:** Try both email and username formats

#### 2.2 Password Field
**Rules:** Required, not empty, no whitespace-only input

- ✅ Valid: Any non-empty password
- ❌ Invalid: Empty field, only spaces
- **Test:** Leave empty or type only spaces

#### 2.3 Rate Limiting (Brute-Force Protection)
**Rules:** Maximum 5 failed attempts in 15 minutes

- **Test Steps:**
  1. Try to login with valid username but wrong password
  2. Repeat 5 times
  3. On 6th attempt, should see message: "Too many login attempts. Please try again in 15 minutes."
  4. Wait 15 minutes or test in a new browser session (rate limit is per session)

#### 2.4 Generic Error Messages (Security)
- Invalid credentials should show: "Invalid email/username or password. Please try again."
- **Security Feature:** System doesn't reveal if username/email exists or password is wrong
- **Test:** Try with non-existent username vs. valid username with wrong password - should see same message

#### 2.5 Successful Login
- Valid email/username + correct password should login and redirect
- Check localStorage or session for user data
- Check header displays logged-in user information

### 3. REAL-TIME VALIDATION FEEDBACK

#### 3.1 Field Validation on Blur
- When you click out of a field, validation occurs
- Invalid fields show red border and error message
- Valid fields show green border (optional, if configured)

#### 3.2 Show Password Toggle
- Click "Show Password" checkbox to toggle password visibility
- Field type changes from password (•••) to text (visible)
- Works on both Sign Up and Sign In forms

#### 3.3 Form-Level Validation
- "was-validated" class added to form on submit
- All validation rules checked before allowing submission
- Submission button disabled during AJAX request
- Prevents double-submission

### 4. BACKEND VALIDATION TESTS

#### 4.1 Database Uniqueness Checks
**Email Uniqueness:**
```bash
# Test 1: Register user with email "test@example.com"
# Test 2: Try to register another user with same email
# Expected: "This email address is already in use."
```

**Username Uniqueness:**
```bash
# Test 1: Register user with username "john_doe"
# Test 2: Try to register another user with username "john_doe"
# Expected: "This username is already in use."
```

#### 4.2 Password Hashing Verification
- Check database: passwords should NOT be stored as plain text
- All passwords should use PASSWORD_BCRYPT hashing
- Never log or display actual password hashes
- Always use `password_verify()` for authentication

#### 4.3 CSRF Token Protection
```javascript
// In browser console, open registration form and inspect HTML:
// Should see hidden input: <input type="hidden" name="csrf_token" value="...">

// Try to submit form without token using fetch:
fetch('register.php', {
  method: 'POST',
  body: new FormData(/* form without csrf_token */)
})
// Expected: 400 Bad Request with "Invalid security token" message
```

### 5. SECURITY TESTS

#### 5.1 SQL Injection Prevention
- Try email: "admin'--" or "' OR '1'='1"
- Username: "admin'; DROP TABLE users--"
- Expected: Validation error (regex pattern fails) or safe query execution

#### 5.2 XSS Prevention
- Try name: "<script>alert('xss')</script>"
- Try username: "<img src=x onerror=alert('xss')>"
- Expected: Validation error or HTML-encoded safely

#### 5.3 Password Space Prevention
- Try password with space: "Pass @123"
- Expected: "Password cannot contain spaces."

### 6. UI/UX TESTS

#### 6.1 Loading States
- Click Submit button and watch for:
  - Button becomes disabled
  - Spinner icon appears
  - Button text changes to "Creating Account..." or "Signing In..."
  - Button re-enables after success/error

#### 6.2 Toast Notifications
- Success: Green notification with checkmark
- Error: Red notification with X
- Messages are clear and helpful
- Auto-dismiss after 5 seconds

#### 6.3 Modal Transitions
- After successful registration, should switch from "Create Account" modal to "Sign In" modal
- Sign In modal should be in focus after switch

#### 6.4 Responsive Design
- Test on mobile viewport (375px width)
- Test on tablet viewport (768px width)
- Test on desktop viewport (1920px width)
- Forms should remain usable and readable

### 7. INTEGRATION TESTS

#### 7.1 Complete Registration Flow
```
1. Open website
2. Click "Sign Up" 
3. Enter all fields with valid data
4. Verify all inline validations pass
5. Click "Create Account"
6. Wait for loading state
7. See success toast
8. Modal switches to Sign In
9. Login with credentials
10. Verify redirect to home page
11. Check header shows "Welcome, [First Name]"
```

#### 7.2 Complete Login Flow
```
1. Open website  
2. Click "Sign In"
3. Enter email (or username) and password
4. Click "Sign In"
5. Wait for loading state
6. Verify redirect to home page (or current page if logged out)
7. Check header shows logged-in user
```

#### 7.3 Failed Registration with Existing Email
```
1. Register user with email "john@example.com"
2. Try to register again with same email
3. Should see: "This email address is already in use."
4. Form should remain on Sign Up modal
5. Should still be able to correct and try different email
```

#### 7.4 Rate Limit After Failed Attempts
```
1. Click "Sign In"
2. Try to login with wrong password 5 times
3. On 6th attempt, should see: "Too many login attempts. Please try again in 15 minutes."
4. Button should still be clickable but request should fail
5. Test in new session after 15 minutes (or just check the code works)
```

## Browser Developer Tools Checks

### 1. Console (F12 → Console tab)
- Should see NO JavaScript errors
- Should see no 404s for missing files
- CSRF token should be logged if debug mode enabled

### 2. Network Tab (F12 → Network tab)
- **POST to login_handler.php**: Should return 200 (success) or 401 (failed login)
- **POST to register.php**: Should return 200 (success) or 400 (validation error)
- **Request headers**: Should include `Content-Type: application/x-www-form-urlencoded`
- **Response**: Should be JSON with `{success: true/false, message: "..."}`

### 3. Application → Cookies
- Should see `PHPSESSID` cookie created on login
- Cookie should have `HttpOnly` and `Secure` flags set

### 4. Application → Storage
- Check localStorage for any user data (if using)
- Verify no sensitive data stored in localStorage

## Debugging Tips

### If validation doesn't appear:
1. Check browser console for JavaScript errors
2. Verify CSS file is loaded (check Network → style.css)
3. Check that form elements have `data-validate` attributes
4. Verify `validateField()` function exists

### If backend validation fails:
1. Check Network tab for POST response status
2. Look at response body for detailed error message
3. Check PHP error log: `php_errors.log` in XAMPP
4. Verify database connection works

### If CSRF token fails:
1. Verify `csrf_field()` is in form HTML
2. Check Network tab Request → Form Data for `csrf_token` parameter
3. Verify `verify_csrf_token()` function is called in handler

### If rate limiting not working:
1. Check `$_SESSION` is being set correctly
2. Verify session is saved between requests
3. Check system clock is correct (rate limit uses `time()`)

## Performance Tests

- Form validation should be instant (< 100ms)
- AJAX submission should complete in < 2 seconds
- Password strength meter should update < 50ms while typing
- No console errors during normal use

## Accessibility Tests

- Form labels should be associated with inputs (for=id)
- Error messages should be in aria-live regions
- Tab order should be logical
- Color alone should not convey meaning (use icons + text)

## Summary Checklist

- [ ] All validation rules working on frontend
- [ ] All validation rules enforced on backend
- [ ] Password strength meter displays correctly
- [ ] CSRF tokens present and verified
- [ ] Rate limiting prevents brute force
- [ ] Uniqueness checks prevent duplicate email/username
- [ ] Generic error messages for security
- [ ] Loading states and toasts work
- [ ] Modal transitions smooth
- [ ] Logout works correctly
- [ ] No console errors
- [ ] Works on all screen sizes
- [ ] Passwords stored with bcrypt hashing
- [ ] Sessions secure (HttpOnly, Secure, SameSite)
