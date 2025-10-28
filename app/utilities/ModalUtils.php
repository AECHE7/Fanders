<?php
/**
 * Modal Display Utilities - Safe field rendering for confirmation modals
 * Prevents undefined array key and deprecated function warnings
 */

class ModalUtils {
    
    /**
     * Safely display a text field with fallbacks
     * @param array $data Data array
     * @param array $keys Keys to try in order of preference  
     * @param string $fallback Default value if all keys fail
     * @return string Safe HTML-escaped text
     */
    public static function safeText($data, $keys, $fallback = 'Not specified') {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        
        foreach ($keys as $key) {
            if (isset($data[$key]) && !empty(trim((string)$data[$key]))) {
                return htmlspecialchars(trim((string)$data[$key]));
            }
        }
        
        return htmlspecialchars($fallback);
    }
    
    /**
     * Safely display a date field with fallbacks  
     * @param array $data Data array
     * @param array $keys Keys to try in order of preference
     * @param string $format Date format (default: 'M j, Y') 
     * @param string $fallback Default value if all dates fail
     * @return string Safe formatted date or fallback
     */
    public static function safeDate($data, $keys, $format = 'M j, Y', $fallback = 'Not specified') {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        
        foreach ($keys as $key) {
            if (isset($data[$key]) && !empty(trim((string)$data[$key]))) {
                $dateValue = trim((string)$data[$key]);
                
                // Skip invalid date values
                if ($dateValue === '0000-00-00' || $dateValue === '0000-00-00 00:00:00') {
                    continue;
                }
                
                $timestamp = strtotime($dateValue);
                if ($timestamp !== false && $timestamp > 0) {
                    return date($format, $timestamp);
                }
            }
        }
        
        return $fallback;
    }
    
    /**
     * Safely display a numeric field with formatting
     * @param array $data Data array
     * @param string $key Key to extract
     * @param int $decimals Number of decimal places
     * @param string $fallback Default value if key fails
     * @return string Safe formatted number
     */
    public static function safeNumber($data, $key, $decimals = 2, $fallback = '0.00') {
        if (isset($data[$key]) && is_numeric($data[$key])) {
            return number_format((float)$data[$key], $decimals);
        }
        
        return $fallback;
    }
    
    /**
     * Safely get officer/user name with ID fallback
     * @param array $data Data array
     * @param string $nameKey Name field key
     * @param string $idKey ID field key  
     * @param string $fallback Default fallback
     * @return string Safe HTML-escaped name or fallback
     */
    public static function safeOfficerName($data, $nameKey = 'officer_name', $idKey = 'officer_id', $fallback = 'Unknown Officer') {
        // Try name field first
        if (isset($data[$nameKey]) && !empty(trim((string)$data[$nameKey]))) {
            return htmlspecialchars(trim((string)$data[$nameKey]));
        }
        
        // Fallback to ID if available
        if (isset($data[$idKey]) && !empty($data[$idKey])) {
            return htmlspecialchars('Officer ID: ' . $data[$idKey]);
        }
        
        return htmlspecialchars($fallback);
    }
    
    /**
     * Safely display a currency amount
     * @param array $data Data array
     * @param string $key Amount field key
     * @param string $currency Currency symbol
     * @param string $fallback Default fallback
     * @return string Safe formatted currency
     */
    public static function safeCurrency($data, $key, $currency = '₱', $fallback = '0.00') {
        if (isset($data[$key]) && is_numeric($data[$key])) {
            return $currency . number_format((float)$data[$key], 2);
        }
        
        return $currency . $fallback;
    }
    
    /**
     * Safely display a badge with status
     * @param array $data Data array  
     * @param string $key Status field key
     * @param array $statusClasses Mapping of status to CSS classes
     * @param string $fallback Default status
     * @return string Safe HTML badge
     */
    public static function safeBadge($data, $key, $statusClasses = [], $fallback = 'unknown') {
        $status = isset($data[$key]) ? trim((string)$data[$key]) : $fallback;
        $safeStatus = htmlspecialchars($status);
        
        $cssClass = isset($statusClasses[$status]) ? $statusClasses[$status] : 'bg-secondary';
        
        return "<span class=\"badge {$cssClass}\">{$safeStatus}</span>";
    }
    
    /**
     * Log debug information for modal data troubleshooting
     * @param string $context Context description
     * @param array $data Data to log
     * @param array $requiredKeys Keys that should be present
     */
    public static function debugLog($context, $data, $requiredKeys = []) {
        if (defined('DEBUG_MODALS') && DEBUG_MODALS) {
            error_log("ModalUtils Debug - {$context}");
            error_log("Data keys available: " . implode(', ', array_keys($data)));
            
            foreach ($requiredKeys as $key) {
                $status = isset($data[$key]) ? 'PRESENT' : 'MISSING';
                $value = isset($data[$key]) ? (string)$data[$key] : 'null';
                error_log("Required key '{$key}': {$status} (value: {$value})");
            }
        }
    }
}

// Convenience functions for backward compatibility
if (!function_exists('safe_text')) {
    function safe_text($data, $keys, $fallback = 'Not specified') {
        return ModalUtils::safeText($data, $keys, $fallback);
    }
}

if (!function_exists('safe_date')) {
    function safe_date($data, $keys, $format = 'M j, Y', $fallback = 'Not specified') {
        return ModalUtils::safeDate($data, $keys, $format, $fallback);
    }
}

if (!function_exists('safe_currency')) {
    function safe_currency($data, $key, $currency = '₱', $fallback = '0.00') {
        return ModalUtils::safeCurrency($data, $key, $currency, $fallback);
    }
}

if (!function_exists('safe_officer')) {
    function safe_officer($data, $nameKey = 'officer_name', $idKey = 'officer_id', $fallback = 'Unknown Officer') {
        return ModalUtils::safeOfficerName($data, $nameKey, $idKey, $fallback);
    }
}