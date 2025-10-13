<?php
/**
 * PasswordHash - Handles password hashing and verification
 * Uses PHP's built-in password_hash and password_verify functions
 */
class PasswordHash {
    /**
     * Hash a password
     * 
     * @param string $password
     * @return string
     */
    public function hash($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a hash
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verify($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Check if a password needs to be rehashed
     * 
     * @param string $hash
     * @return bool
     */
    public function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }
} 