<?php
/**
 * Application Configuration
 * Central configuration file for the application
 */

// Environment settings
define('APP_ENV', 'development'); // Change to 'production' when deploying
define('APP_DEBUG', APP_ENV === 'development');

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 7200); // 2 hours

// Application paths
define('APP_PATH', __DIR__);
define('UPLOAD_PATH', APP_PATH . '/uploads');
define('LOG_PATH', APP_PATH . '/logs');

// Database configuration (imported from db.php)
// See db.php for database connection settings

/**
 * Custom error handler
 */
function app_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error_message = "Error [$errno]: $errstr in $errfile on line $errline";
    error_log($error_message);
    
    if (APP_DEBUG) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($errstr) . "<br>";
        echo "<strong>File:</strong> " . htmlspecialchars($errfile) . " (Line: $errline)";
        echo "</div>";
    }
    
    return true;
}

/**
 * Custom exception handler
 */
function app_exception_handler($exception) {
    $error_message = "Uncaught exception: " . $exception->getMessage() . " in " . 
                     $exception->getFile() . " on line " . $exception->getLine();
    error_log($error_message);
    
    if (APP_DEBUG) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
        echo "<strong>Exception:</strong> " . htmlspecialchars($exception->getMessage()) . "<br>";
        echo "<strong>File:</strong> " . htmlspecialchars($exception->getFile()) . " (Line: " . $exception->getLine() . ")<br>";
        if (APP_DEBUG) {
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        }
        echo "</div>";
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
        echo '<h1>An error occurred</h1>';
        echo '<p>We apologize for the inconvenience. Please try again later.</p>';
        echo '</body></html>';
    }
}

// Set custom error handlers
set_error_handler('app_error_handler');
set_exception_handler('app_exception_handler');

// Create logs directory if it doesn't exist
if (!file_exists(LOG_PATH)) {
    @mkdir(LOG_PATH, 0755, true);
}
