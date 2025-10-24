<?php
/**
 * Safe Export Wrapper Utility
 * Provides a safe wrapper for all export operations to prevent PHP warnings/errors from contaminating output
 */

class SafeExportWrapper
{
    private static $originalErrorReporting;
    private static $originalErrorHandler;
    private static $exportStarted = false;
    
    /**
     * Initialize safe export environment
     */
    public static function beginSafeExport(): void
    {
        // Mark export started
        self::$exportStarted = true;
        // Store original settings
        self::$originalErrorReporting = error_reporting();
        
        // Set up custom error handler that prevents output
        self::$originalErrorHandler = set_error_handler(function($severity, $message, $file, $line) {
            // Log the error instead of outputting it
            error_log("Export Warning: $message in $file on line $line");
            
            // Return true to prevent PHP's internal error handler from running
            return true;
        });
        
        // Suppress all warnings and notices
        error_reporting(E_ERROR | E_PARSE);
        
        // Ensure binary-safe output and enough resources for large exports
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');
        @ini_set('memory_limit', '256M');
        @set_time_limit(120);
        @ignore_user_abort(true);
        
        // Clear any existing output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
    
    /**
     * Restore normal error handling after export
     */
    public static function endSafeExport(): void
    {
        if (!self::$exportStarted) {
            return;
        }
        // Restore original error reporting
        if (self::$originalErrorReporting !== null) {
            error_reporting(self::$originalErrorReporting);
        }
        
        // Restore original error handler
        if (self::$originalErrorHandler !== null) {
            restore_error_handler();
        }

        self::$exportStarted = false;
    }
    
    /**
     * Execute export function safely
     */
    public static function safeExecute(callable $exportFunction): void
    {
        try {
            self::beginSafeExport();
            $exportFunction();
        } catch (Exception $e) {
            self::endSafeExport();
            throw $e;
        } catch (Error $e) {
            self::endSafeExport();
            throw $e;
        }
        // Note: endSafeExport() is called within the export utility after headers are sent
    }
}