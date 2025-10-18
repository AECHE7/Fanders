<?php
/**
 * CSRF - Handles Cross-Site Request Forgery protection
 */
class CSRF {
    private $session;
    private $tokenName = 'csrf_token';
    private $tokenLength = 32; // Length of the token in bytes

    /**
     * Constructor
     */
    public function __construct() {
        $this->session = new Session();
        // Ensure token is generated on construction if not exists
        $this->getToken();
    }

    /**
     * Generate a new CSRF token and store it in the session
     * 
     * @return string The generated token
     */
    public function generateToken() {
        // Generate a cryptographically secure random token
        $token = bin2hex(random_bytes($this->tokenLength));
        
        // Store token in session
        $this->session->set($this->tokenName, $token);
        
        return $token;
    }

    /**
     * Get the current CSRF token or generate a new one if none exists
     * 
     * @return string The CSRF token
     */
    public function getToken() {
        if (!$this->session->has($this->tokenName)) {
            return $this->generateToken();
        }
        
        return $this->session->get($this->tokenName);
    }

    /**
     * Validate a submitted CSRF token against the stored one
     * 
     * @param string $token The token to validate
     * @param bool $regenerateOnSuccess Whether to regenerate the token after successful validation
     * @return bool True if valid, false otherwise
     */
    public function validateToken($token, $regenerateOnSuccess = true) {
        $storedToken = $this->session->get($this->tokenName);
        
        // If no token is stored or the tokens don't match, validation fails
        if (!$storedToken || !$token || !hash_equals($storedToken, $token)) {
            return false;
        }
        
        // Regenerate token if needed
        if ($regenerateOnSuccess) {
            $this->generateToken();
        }
        
        return true;
    }

    /**
     * Generate HTML for a hidden input field containing the CSRF token
     * 
     * @return string HTML input element
     */
    public function getTokenField() {
        $token = $this->getToken();
        return '<input type="hidden" name="' . $this->tokenName . '" value="' . $token . '">';
    }

    /**
     * Validate CSRF token from POST data
     * 
     * @param bool $regenerateOnSuccess Whether to regenerate the token after successful validation
     * @return bool True if valid, false otherwise
     */
    public function validateRequest($regenerateOnSuccess = true) {
        $token = isset($_POST[$this->tokenName]) ? $_POST[$this->tokenName] : null;
        return $this->validateToken($token, $regenerateOnSuccess);
    }

    /**
     * Set the CSRF token name
     * 
     * @param string $name
     * @return void
     */
    public function setTokenName($name) {
        $this->tokenName = $name;
    }

    /**
     * Set the CSRF token length in bytes
     * 
     * @param int $length
     * @return void
     */
    public function setTokenLength($length) {
        $this->tokenLength = $length;
    }
}
