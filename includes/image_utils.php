<?php
/**
 * Image Path Utilities
 * Handles consistent image path resolution across admin and customer sites
 */

/**
 * Get proper image URL from database path
 * Resolves relative paths to correct absolute URLs
 * 
 * @param string $db_path The image path stored in database (e.g., 'image/product.jpg')
 * @param string $context Context where image is being used ('admin' or 'web')
 * @return string Full URL to the image with fallback to placeholder
 */
function get_image_url($db_path, $context = 'auto') {
    // Default fallback
    $default_placeholder = '/WebDev1.2/image/placeholder.png';
    
    // If empty or invalid, return placeholder
    if (empty($db_path) || !is_string($db_path)) {
        return $default_placeholder;
    }
    
    // Auto-detect context based on PHP_SELF if not specified
    if ($context === 'auto') {
        $context = (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false) ? 'admin' : 'web';
    }
    
    // If already a full URL, return as-is
    if (filter_var($db_path, FILTER_VALIDATE_URL) || strpos($db_path, 'http') === 0) {
        return htmlspecialchars($db_path, ENT_QUOTES, 'UTF-8');
    }
    
    // Get the application base path
    $base_path = '/WebDev1.2';
    
    // For admin context, relative paths need ../ prefix
    if ($context === 'admin') {
        // Convert relative paths to admin-appropriate paths
        // 'image/file.jpg' becomes '../image/file.jpg'
        if (strpos($db_path, '../') !== 0 && strpos($db_path, '/') === 0) {
            // Absolute path from web root
            return $base_path . $db_path;
        } elseif (strpos($db_path, '../') !== 0 && strpos($db_path, '/') !== 0) {
            // Relative path, needs ../ prefix for admin
            return '../' . $db_path;
        }
    } else {
        // For web context, ensure path starts with /WebDev1.2 or is relative
        if (strpos($db_path, '/') === 0) {
            // Already absolute from root
            return $base_path . $db_path;
        } else {
            // Relative path, resolve from web root
            return $base_path . '/' . $db_path;
        }
    }
    
    return htmlspecialchars($db_path, ENT_QUOTES, 'UTF-8');
}

/**
 * Get image path for display in admin
 * Uses relative paths for admin subdirectory
 * 
 * @param string $db_path The image path stored in database
 * @return string Path suitable for admin img src attribute
 */
function get_admin_image_path($db_path) {
    if (empty($db_path) || !is_string($db_path)) {
        return '../image/placeholder.png';
    }
    
    // If it's a relative path like 'image/file.jpg'
    if (strpos($db_path, '/') === 0) {
        // It's absolute from web root, add .. prefix
        return '..' . $db_path;
    } elseif (strpos($db_path, '../') === 0) {
        // Already prefixed for admin
        return $db_path;
    } else {
        // Relative path without leading /, add ../
        return '../' . $db_path;
    }
}

/**
 * Get image path for display in web/customer site
 * Uses relative paths from web root
 * 
 * @param string $db_path The image path stored in database
 * @return string Path suitable for web img src attribute
 */
function get_web_image_path($db_path) {
    if (empty($db_path) || !is_string($db_path)) {
        return 'image/placeholder.png';
    }
    
    // Remove any leading ../ since we're at web root
    $db_path = preg_replace('/^\.\.\//', '', $db_path);
    
    // If it starts with /, it's from WebDev1.2 root, remove leading /
    if (strpos($db_path, '/') === 0) {
        $db_path = substr($db_path, 1);
    }
    
    return $db_path;
}

/**
 * Verify if image file exists on filesystem
 * Helpful for debugging broken image references
 * 
 * @param string $db_path The image path stored in database
 * @return boolean True if file exists, false otherwise
 */
function image_exists($db_path) {
    if (empty($db_path)) {
        return false;
    }
    
    // Construct file path based on context
    if (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false) {
        // In admin context
        $file_path = __DIR__ . '/../' . trim($db_path, '/');
    } else {
        // In web context
        $file_path = __DIR__ . '/' . trim($db_path, '/');
    }
    
    return file_exists($file_path);
}

/**
 * Get image file size in human-readable format
 * 
 * @param string $db_path The image path stored in database
 * @return string File size or 'N/A' if file doesn't exist
 */
function get_image_size_formatted($db_path) {
    if (empty($db_path)) {
        return 'N/A';
    }
    
    // Construct file path
    if (strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false) {
        $file_path = __DIR__ . '/../' . trim($db_path, '/');
    } else {
        $file_path = __DIR__ . '/' . trim($db_path, '/');
    }
    
    if (!file_exists($file_path)) {
        return 'N/A';
    }
    
    $size = filesize($file_path);
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * Sanitize filename for safe storage
 * 
 * @param string $filename Original filename
 * @return string Sanitized filename safe for filesystem
 */
function sanitize_filename($filename) {
    // Remove path information
    $filename = basename($filename);
    
    // Remove special characters except . and -
    $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '', $filename);
    
    // Remove multiple dots
    $filename = preg_replace('/\.{2,}/', '.', $filename);
    
    // Ensure lowercase
    $filename = strtolower($filename);
    
    // Remove leading/trailing dots and spaces
    $filename = trim($filename, '. ');
    
    return $filename;
}

/**
 * Validate image file type and size
 * 
 * @param array $file $_FILES array element
 * @param int $max_size Maximum file size in bytes (default 5MB)
 * @return array ['valid' => bool, 'error' => string or null]
 */
function validate_image_upload($file, $max_size = 5242880) {
    // Allowed MIME types
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Allowed extensions
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'No file uploaded'];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size exceeds ' . ($max_size / 1048576) . 'MB limit'];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type: ' . $mime_type];
    }
    
    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed_extensions)) {
        return ['valid' => false, 'error' => 'Invalid file extension: ' . $extension];
    }
    
    return ['valid' => true, 'error' => null];
}

?>
