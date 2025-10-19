<?php

/**
 * ErrorHandler Class
 * Provides centralized error handling, logging, and user-friendly error messages
 */
class ErrorHandler
{
    private static $logDir = null;
    private static $logLevel = 'ERROR'; // DEBUG, INFO, WARNING, ERROR, CRITICAL

    // Error level constants
    const DEBUG = 1;
    const INFO = 2;
    const WARNING = 3;
    const ERROR = 4;
    const CRITICAL = 5;

    private static $levelMap = [
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL'
    ];

    /**
     * Initialize log directory
     */
    private static function initLogDir() {
        if (self::$logDir === null) {
            self::$logDir = BASE_PATH . '/storage/logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
    }

    /**
     * Set log level
     * @param string $level Log level
     */
    public static function setLogLevel($level) {
        self::$logLevel = strtoupper($level);
    }

    /**
     * Log an error with context
     * @param string $message Error message
     * @param int $level Error level
     * @param array $context Additional context
     * @param string $file File where error occurred
     * @param int $line Line number where error occurred
     */
    public static function log($message, $level = self::ERROR, $context = [], $file = null, $line = null) {
        self::initLogDir();

        $levelName = self::$levelMap[$level] ?? 'UNKNOWN';
        
        // Check if we should log this level
        if (!self::shouldLog($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $levelName,
            'message' => $message,
            'context' => $context,
            'file' => $file,
            'line' => $line,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            'ip_address' => self::getUserIP(),
            'session_id' => session_id()
        ];

        // Create log filename based on date and level
        $logFile = self::$logDir . '/' . date('Y-m-d') . '_' . strtolower($levelName) . '.log';
        
        // Format log entry
        $logLine = $timestamp . ' [' . $levelName . '] ' . $message;
        
        if (!empty($context)) {
            $logLine .= ' | Context: ' . json_encode($context);
        }
        
        if ($file && $line) {
            $logLine .= ' | File: ' . $file . ':' . $line;
        }
        
        $logLine .= ' | IP: ' . self::getUserIP() . ' | URI: ' . ($_SERVER['REQUEST_URI'] ?? 'CLI') . PHP_EOL;

        // Write to log file
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);

        // Also write to general log
        $generalLogFile = self::$logDir . '/' . date('Y-m-d') . '_general.log';
        file_put_contents($generalLogFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Check if we should log this level
     * @param int $level
     * @return bool
     */
    private static function shouldLog($level) {
        $configLevel = array_search(self::$logLevel, self::$levelMap);
        return $level >= $configLevel;
    }

    /**
     * Get user IP address
     * @return string
     */
    private static function getUserIP() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return 'unknown';
    }

    /**
     * Handle database errors
     * @param string $operation Operation that failed
     * @param Exception $exception Exception thrown
     * @param array $context Additional context
     * @return string User-friendly error message
     */
    public static function handleDatabaseError($operation, $exception, $context = []) {
        $errorCode = $exception->getCode();
        $errorMessage = $exception->getMessage();

        // Log the detailed error
        self::log(
            "Database error during {$operation}: {$errorMessage}",
            self::ERROR,
            array_merge($context, [
                'operation' => $operation,
                'error_code' => $errorCode,
                'exception_class' => get_class($exception)
            ]),
            $exception->getFile(),
            $exception->getLine()
        );

        // Return user-friendly message
        return self::getUserFriendlyMessage('database_error', $operation);
    }

    /**
     * Handle validation errors
     * @param array $errors Validation errors
     * @param array $context Additional context
     * @return string User-friendly error message
     */
    public static function handleValidationError($errors, $context = []) {
        self::log(
            "Validation failed",
            self::WARNING,
            array_merge($context, ['validation_errors' => $errors])
        );

        if (is_array($errors)) {
            return 'Please correct the following errors: ' . implode(', ', $errors);
        }

        return $errors;
    }

    /**
     * Handle authorization errors
     * @param string $action Action that was denied
     * @param array $context Additional context
     * @return string User-friendly error message
     */
    public static function handleAuthorizationError($action, $context = []) {
        self::log(
            "Authorization denied for action: {$action}",
            self::WARNING,
            array_merge($context, ['action' => $action])
        );

        return self::getUserFriendlyMessage('authorization_error', $action);
    }

    /**
     * Handle general application errors
     * @param string $message Error message
     * @param Exception $exception Exception (optional)
     * @param array $context Additional context
     * @return string User-friendly error message
     */
    public static function handleApplicationError($message, $exception = null, $context = []) {
        $level = self::ERROR;
        $file = null;
        $line = null;

        if ($exception) {
            $file = $exception->getFile();
            $line = $exception->getLine();
            $context['exception_class'] = get_class($exception);
            $context['exception_message'] = $exception->getMessage();
        }

        self::log($message, $level, $context, $file, $line);

        return self::getUserFriendlyMessage('application_error');
    }

    /**
     * Get user-friendly error messages
     * @param string $type Error type
     * @param string $operation Operation context (optional)
     * @return string
     */
    private static function getUserFriendlyMessage($type, $operation = null) {
        $messages = [
            'database_error' => 'A database error occurred. Please try again later.',
            'authorization_error' => 'You do not have permission to perform this action.',
            'application_error' => 'An unexpected error occurred. Please try again later.',
            'validation_error' => 'Please check your input and try again.',
            'not_found' => 'The requested resource could not be found.',
            'server_error' => 'A server error occurred. Please contact support if the problem persists.'
        ];

        $message = $messages[$type] ?? $messages['application_error'];

        if ($operation) {
            $message = "Unable to complete {$operation}. " . $message;
        }

        return $message;
    }

    /**
     * Log debug information
     * @param string $message Debug message
     * @param array $context Context data
     */
    public static function debug($message, $context = []) {
        self::log($message, self::DEBUG, $context);
    }

    /**
     * Log informational message
     * @param string $message Info message
     * @param array $context Context data
     */
    public static function info($message, $context = []) {
        self::log($message, self::INFO, $context);
    }

    /**
     * Log warning
     * @param string $message Warning message
     * @param array $context Context data
     */
    public static function warning($message, $context = []) {
        self::log($message, self::WARNING, $context);
    }

    /**
     * Log error
     * @param string $message Error message
     * @param array $context Context data
     */
    public static function error($message, $context = []) {
        self::log($message, self::ERROR, $context);
    }

    /**
     * Log critical error
     * @param string $message Critical message
     * @param array $context Context data
     */
    public static function critical($message, $context = []) {
        self::log($message, self::CRITICAL, $context);
    }

    /**
     * Get recent log entries
     * @param int $limit Number of entries to retrieve
     * @param string $level Log level to filter (optional)
     * @return array
     */
    public static function getRecentLogs($limit = 100, $level = null) {
        self::initLogDir();
        
        $logFile = self::$logDir . '/' . date('Y-m-d') . '_general.log';
        
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = array_slice(array_reverse($lines), 0, $limit);

        if ($level) {
            $logs = array_filter($logs, function($line) use ($level) {
                return strpos($line, '[' . strtoupper($level) . ']') !== false;
            });
        }

        return array_values($logs);
    }

    /**
     * Clear old log files
     * @param int $daysToKeep Number of days to keep logs
     * @return int Number of files deleted
     */
    public static function cleanOldLogs($daysToKeep = 30) {
        self::initLogDir();
        
        $files = glob(self::$logDir . '/*.log');
        $deletedCount = 0;
        $cutoffDate = strtotime("-{$daysToKeep} days");

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffDate) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }
}