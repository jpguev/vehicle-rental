<?php
/**
 * Validation and Error Handling Examples
 * Copy this file to includes/validators.php in your project
 */

/**
 * Validate input data
 * 
 * @param array $data The data to validate
 * @param array $rules Validation rules
 * @return array Validation errors
 */
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule_set) {
        $value = $data[$field] ?? '';
        $rules_array = explode('|', $rule_set);
        
        foreach ($rules_array as $rule) {
            $rule = trim($rule);
            
            // Required field
            if ($rule === 'required' && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                break;
            }
            
            // Email validation
            if (strpos($rule, 'email') === 0 && !empty($value)) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = ucfirst($field) . ' must be a valid email';
                    break;
                }
            }
            
            // Min length
            if (strpos($rule, 'min:') === 0 && !empty($value)) {
                $min = intval(str_replace('min:', '', $rule));
                if (strlen($value) < $min) {
                    $errors[$field] = ucfirst($field) . ' must be at least ' . $min . ' characters';
                    break;
                }
            }
            
            // Max length
            if (strpos($rule, 'max:') === 0 && !empty($value)) {
                $max = intval(str_replace('max:', '', $rule));
                if (strlen($value) > $max) {
                    $errors[$field] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
                    break;
                }
            }
            
            // Numeric
            if ($rule === 'numeric' && !empty($value)) {
                if (!is_numeric($value)) {
                    $errors[$field] = ucfirst($field) . ' must be numeric';
                    break;
                }
            }
            
            // Phone
            if ($rule === 'phone' && !empty($value)) {
                if (!preg_match('/^[0-9\-\(\)\s\+]+$/', $value)) {
                    $errors[$field] = ucfirst($field) . ' must be a valid phone number';
                    break;
                }
            }
            
            // URL
            if ($rule === 'url' && !empty($value)) {
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$field] = ucfirst($field) . ' must be a valid URL';
                    break;
                }
            }
            
            // Regex pattern
            if (strpos($rule, 'regex:') === 0 && !empty($value)) {
                $pattern = str_replace('regex:', '', $rule);
                if (!preg_match($pattern, $value)) {
                    $errors[$field] = ucfirst($field) . ' format is invalid';
                    break;
                }
            }
            
            // Date format
            if (strpos($rule, 'date_format:') === 0 && !empty($value)) {
                $format = str_replace('date_format:', '', $rule);
                $d = DateTime::createFromFormat($format, $value);
                if (!$d || $d->format($format) !== $value) {
                    $errors[$field] = ucfirst($field) . ' must be in format ' . $format;
                    break;
                }
            }
            
            // Unique (check database)
            if (strpos($rule, 'unique:') === 0 && !empty($value)) {
                $parts = explode(':', $rule);
                $table = $parts[1] ?? '';
                $column = $parts[2] ?? 'id';
                
                $pdo = getDatabase();
                $stmt = $pdo->prepare("SELECT id FROM $table WHERE $column = ? LIMIT 1");
                $stmt->execute([$value]);
                
                if ($stmt->fetch()) {
                    $errors[$field] = ucfirst($field) . ' already exists';
                    break;
                }
            }
            
            // Match another field
            if (strpos($rule, 'match:') === 0 && !empty($value)) {
                $match_field = str_replace('match:', '', $rule);
                if ($value !== ($data[$match_field] ?? '')) {
                    $errors[$field] = ucfirst($field) . ' must match ' . $match_field;
                    break;
                }
            }
        }
    }
    
    return $errors;
}

/**
 * Sanitize input
 * 
 * @param string $input The input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize array of inputs
 * 
 * @param array $data Input array
 * @return array Sanitized array
 */
function sanitizeArray($data) {
    $sanitized = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitizeArray($value);
        } else {
            $sanitized[$key] = sanitizeInput($value);
        }
    }
    return $sanitized;
}

/**
 * Validate email
 * 
 * @param string $email Email address
 * @return bool True if valid
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 * 
 * @param string $phone Phone number
 * @return bool True if valid
 */
function validatePhone($phone) {
    // Allows: +1-555-123-4567, (555) 123-4567, 555-123-4567, 5551234567
    return preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/', $phone);
}

/**
 * Validate URL
 * 
 * @param string $url URL
 * @return bool True if valid
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate date
 * 
 * @param string $date Date in Y-m-d format
 * @return bool True if valid
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Check if value is unique in database
 * 
 * @param string $value Value to check
 * @param string $table Table name
 * @param string $column Column name
 * @param string $exclude_id ID to exclude (for updates)
 * @return bool True if unique
 */
function isUnique($value, $table, $column, $exclude_id = null) {
    $pdo = getDatabase();
    
    if ($exclude_id) {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE $column = ? AND id != ? LIMIT 1");
        $stmt->execute([$value, $exclude_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE $column = ? LIMIT 1");
        $stmt->execute([$value]);
    }
    
    return !$stmt->fetch();
}

/**
 * Check if email exists
 * 
 * @param string $email Email address
 * @param string $table Table name
 * @param string|null $exclude_id ID to exclude
 * @return bool True if exists
 */
function emailExists($email, $table = 'users', $exclude_id = null) {
    return !isUnique($email, $table, 'email', $exclude_id);
}

/**
 * Get validation error message
 * 
 * @param array $errors Error array
 * @param string $field Field name
 * @return string Error message or empty string
 */
function getErrorMessage($errors, $field) {
    return $errors[$field] ?? '';
}

/**
 * Check if field has error
 * 
 * @param array $errors Error array
 * @param string $field Field name
 * @return bool True if field has error
 */
function hasError($errors, $field) {
    return isset($errors[$field]);
}

/**
 * Get old input value from POST
 * 
 * @param string $field Field name
 * @param mixed $default Default value
 * @return mixed Field value or default
 */
function old($field, $default = '') {
    return sanitizeInput($_POST[$field] ?? $default);
}

/**
 * Get error class for form field
 * 
 * @param array $errors Error array
 * @param string $field Field name
 * @return string CSS class name
 */
function errorClass($errors, $field) {
    return hasError($errors, $field) ? 'is-invalid' : '';
}

/**
 * Log security event
 * 
 * @param string $event Event description
 * @param array $context Additional context
 * @return void
 */
function logSecurityEvent($event, $context = []) {
    $log_file = '../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $context_str = !empty($context) ? json_encode($context) : '';
    $log_entry = "[$timestamp] IP: $ip_address | Event: $event | $context_str\n";
    
    if (!is_dir('../logs')) {
        mkdir('../logs', 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Rate limiting check
 * 
 * @param string $identifier User identifier (IP or user ID)
 * @param int $max_attempts Max attempts allowed
 * @param int $time_window Time window in seconds
 * @return bool True if under limit
 */
function checkRateLimit($identifier, $max_attempts = 5, $time_window = 300) {
    session_start();
    
    if (!isset($_SESSION['rate_limit'][$identifier])) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $current_time = time();
    $first_attempt = $_SESSION['rate_limit'][$identifier]['first_attempt'];
    
    // Reset counter if time window has passed
    if ($current_time - $first_attempt > $time_window) {
        $_SESSION['rate_limit'][$identifier] = [
            'attempts' => 1,
            'first_attempt' => $current_time
        ];
        return true;
    }
    
    $_SESSION['rate_limit'][$identifier]['attempts']++;
    
    return $_SESSION['rate_limit'][$identifier]['attempts'] <= $max_attempts;
}

/**
 * Get remaining attempts
 * 
 * @param string $identifier User identifier
 * @param int $max_attempts Max attempts
 * @return int Remaining attempts
 */
function getRemainingAttempts($identifier, $max_attempts = 5) {
    session_start();
    
    $attempts = $_SESSION['rate_limit'][$identifier]['attempts'] ?? 0;
    return max(0, $max_attempts - $attempts);
}

/**
 * Get error color class
 * 
 * @param string $severity Severity level: error, warning, info
 * @return string CSS class
 */
function getAlertClass($severity = 'error') {
    $classes = [
        'error' => 'alert-error',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
        'success' => 'alert-success'
    ];
    
    return $classes[$severity] ?? 'alert-info';
}

?>
