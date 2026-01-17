<?php
/**
 * Input Validation & Sanitization Helpers
 */

/**
 * Sanitize string input
 */
function sanitize_string($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and sanitize email
 */
function validate_email($email) {
    $email = trim($email);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

/**
 * Validate phone number (basic check)
 */
function validate_phone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($phone) >= 10 ? $phone : false;
}

/**
 * Validate integer within range
 */
function validate_int($value, $min = null, $max = null) {
    $value = filter_var($value, FILTER_VALIDATE_INT);
    if ($value === false) {
        return false;
    }
    if ($min !== null && $value < $min) {
        return false;
    }
    if ($max !== null && $value > $max) {
        return false;
    }
    return $value;
}

/**
 * Validate password strength
 */
function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    return empty($errors) ? true : $errors;
}

/**
 * Sanitize filename for uploads
 */
function sanitize_filename($filename) {
    // Remove any path components
    $filename = basename($filename);
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return $filename;
}

/**
 * Validate URL
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
}

/**
 * Escape output for HTML
 */
function esc_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for HTML attributes
 */
function esc_attr($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for JavaScript
 */
function esc_js($text) {
    return json_encode($text, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

/**
 * Validate and sanitize array of integers
 */
function validate_int_array($array) {
    if (!is_array($array)) {
        return [];
    }
    return array_map('intval', array_filter($array, 'is_numeric'));
}

/**
 * Rate limiting check (simple implementation)
 */
function check_rate_limit($key, $max_attempts = 5, $time_window = 300) {
    init_session();
    
    $rate_key = 'rate_limit_' . $key;
    $now = time();
    
    if (!isset($_SESSION[$rate_key])) {
        $_SESSION[$rate_key] = ['count' => 1, 'time' => $now];
        return true;
    }
    
    $rate_data = $_SESSION[$rate_key];
    
    // Reset if time window expired
    if ($now - $rate_data['time'] > $time_window) {
        $_SESSION[$rate_key] = ['count' => 1, 'time' => $now];
        return true;
    }
    
    // Increment counter
    $rate_data['count']++;
    $_SESSION[$rate_key] = $rate_data;
    
    // Check if limit exceeded
    return $rate_data['count'] <= $max_attempts;
}

/**
 * Clean and validate post code/zip code
 */
function validate_postal_code($code, $country = 'PH') {
    $code = preg_replace('/[^0-9A-Za-z-]/', '', $code);
    
    if ($country === 'PH') {
        // Philippine postal codes are 4 digits
        return preg_match('/^\d{4}$/', $code) ? $code : false;
    }
    
    return $code;
}
