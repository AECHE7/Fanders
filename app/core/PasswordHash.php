<?php
/**
 * PasswordHash - Handles password hashing and verification
 * Uses PHP's built-in password_hash and password_verify functions,
 * respecting the PASSWORD_ALGORITHM defined in configuration for security compliance.
 */
class PasswordHash {
    /**
     * Hash a password using the configured algorithm (e.g., PASSWORD_BCRYPT).
     * * @param string $password
     * @return string
     */
    public function hash($password) {
        // Use the globally defined constant from app/config/config.php
        if (defined('PASSWORD_ALGORITHM')) {
            $algo = PASSWORD_ALGORITHM;
        } else {
            // Fallback for safety if config isn't loaded
            $algo = PASSWORD_DEFAULT; 
        }

        return password_hash($password, $algo);
    }

    /**
     * Verify a password against a hash
     * * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verify($password, $hash) {
        if (is_null($hash)) {
            return false;
        }
        return password_verify($password, $hash);
    }

    /**
     * Check if a password needs to be rehashed based on the configured algorithm.
     * * @param string $hash
     * @return bool
     */
    public function needsRehash($hash) {
        // Use the globally defined constant from app/config/config.php
        if (defined('PASSWORD_ALGORITHM')) {
            $algo = PASSWORD_ALGORITHM;
        } else {
            $algo = PASSWORD_DEFAULT;
        }

        return password_needs_rehash($hash, $algo);
    }
}
