<?php
/**
 * PasswordHash - Handles password hashing and verification
 */
class PasswordHash {
    /**
     * Hash a password
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public function hash($password) {
        // Use PHP's built-in password hashing function
        // PASSWORD_DEFAULT uses the strongest algorithm (currently bcrypt)
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * Verify password against hash
     * 
     * @param string $password Plain text password
     * @param string $hash Hashed password
     * @return bool True if password matches hash
     */
    public function verify($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehash
     * 
     * @param string $hash Hashed password
     * @return bool True if password needs rehash
     */
    public function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * Generate a random password
     * 
     * @param int $length Password length
     * @return string Random password
     */
    public function generateRandomPassword($length = 12) {
        // Define character sets
        $sets = [
            'abcdefghjkmnpqrstuvwxyz',
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            '23456789',
            '!@#$%&*?'
        ];
        
        $password = '';
        
        // Add one character from each set
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
        }
        
        // Fill remaining length with random characters from all sets
        $all = implode('', $sets);
        $allChars = str_split($all);
        
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[array_rand($allChars)];
        }
        
        // Shuffle the password characters
        $passwordArray = str_split($password);
        shuffle($passwordArray);
        
        return implode('', $passwordArray);
    }

    /**
     * Check password strength
     * 
     * @param string $password Password to check
     * @return array Associative array with strength info
     */
    public function checkStrength($password) {
        $strength = [
            'length' => strlen($password) >= 8,
            'lowercase' => preg_match('/[a-z]/', $password),
            'uppercase' => preg_match('/[A-Z]/', $password),
            'number' => preg_match('/[0-9]/', $password),
            'special' => preg_match('/[^a-zA-Z0-9]/', $password),
            'score' => 0,
            'valid' => false
        ];
        
        // Calculate score based on criteria
        $strength['score'] += $strength['length'] ? 1 : 0;
        $strength['score'] += $strength['lowercase'] ? 1 : 0;
        $strength['score'] += $strength['uppercase'] ? 1 : 0;
        $strength['score'] += $strength['number'] ? 1 : 0;
        $strength['score'] += $strength['special'] ? 1 : 0;
        
        // A valid password meets at least 4 criteria
        $strength['valid'] = $strength['score'] >= 4;
        
        return $strength;
    }
}
