<?php
/**
 * PasswordHash - Handles password hashing and verification
 */
class PasswordHash {

    public function hash($password) {
        // Use PHP's built-in password hashing function
        // PASSWORD_DEFAULT uses the strongest algorithm (currently bcrypt)
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }


    public function verify($password, $hash) {
        return password_verify($password, $hash);
    }

    public function needsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12]);
    }

  
    public function generateRandomPassword($length = null) {
        $requirements = self::getPasswordRequirements();
        $length = $length ?? $requirements['min_length'];
        
        // Define character sets
        $sets = [
            'lowercase' => 'abcdefghjkmnpqrstuvwxyz',
            'uppercase' => 'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'numbers' => '23456789',
            'special' => '!@#$%&*?'
        ];
        
        $password = '';
        
        // Add required characters based on requirements
        if ($requirements['require_lowercase']) {
            $password .= $sets['lowercase'][array_rand(str_split($sets['lowercase']))];
        }
        if ($requirements['require_uppercase']) {
            $password .= $sets['uppercase'][array_rand(str_split($sets['uppercase']))];
        }
        if ($requirements['require_numbers']) {
            $password .= $sets['numbers'][array_rand(str_split($sets['numbers']))];
        }
        if ($requirements['require_special']) {
            $password .= $sets['special'][array_rand(str_split($sets['special']))];
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


    public function checkStrength($password) {
        $requirements = self::getPasswordRequirements();
        
        $strength = [
            'length' => strlen($password) >= $requirements['min_length'],
            'lowercase' => $requirements['require_lowercase'] ? preg_match('/[a-z]/', $password) : true,
            'uppercase' => $requirements['require_uppercase'] ? preg_match('/[A-Z]/', $password) : true,
            'number' => $requirements['require_numbers'] ? preg_match('/[0-9]/', $password) : true,
            'special' => $requirements['require_special'] ? preg_match('/[^a-zA-Z0-9]/', $password) : true,
            'score' => 0,
            'valid' => false
        ];
        
        // Calculate score based on criteria
        $strength['score'] += $strength['length'] ? 1 : 0;
        $strength['score'] += $strength['lowercase'] ? 1 : 0;
        $strength['score'] += $strength['uppercase'] ? 1 : 0;
        $strength['score'] += $strength['number'] ? 1 : 0;
        $strength['score'] += $strength['special'] ? 1 : 0;
        
        // A valid password meets all required criteria
        $strength['valid'] = $strength['length'] && 
                           (!$requirements['require_lowercase'] || $strength['lowercase']) &&
                           (!$requirements['require_uppercase'] || $strength['uppercase']) &&
                           (!$requirements['require_numbers'] || $strength['number']) &&
                           (!$requirements['require_special'] || $strength['special']);
        
        return $strength;
    }
}
