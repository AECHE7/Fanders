

<?php
/**
 * Session - Manages user sessions
 */
class Session {
    public function __construct() {
        // Increase time limit for session operations
        set_time_limit(300); // 5 minutes

        // Only set session cookie parameters and start session if session is not active
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0, // Session cookie, expires when browser closes
                'path' => '/',
                'domain' => '',
                'secure' => false, // Set to true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }

        // Update last activity timestamp
        $this->updateLastActivity();
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    

    public function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
 
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    

    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public function clear() {
        session_unset();
    }
    

    public function destroy() {
        // Check if session is active before destroying
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->clear();
            session_destroy();
        }

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }
    
 
    public function regenerate($deleteOldSession = true) {
        session_regenerate_id($deleteOldSession);
    }

    public function setFlash($key, $value) {
        $_SESSION['flash'][$key] = $value;
    }
    
 
    public function getFlash($key, $default = null) {
        $value = $default;
        
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
        }
        
        return $value;
    }
    
 
    public function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }
    

    public function setCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $this->set('csrf_token', $token);
        return $token;
    }


    public function getCsrfToken() {
        if (!$this->has('csrf_token')) {
            return $this->setCsrfToken();
        }
        return $this->get('csrf_token');
    }


    public function validateCsrfToken($token) {
        $storedToken = $this->get('csrf_token');
        if (!$storedToken || !$token || !hash_equals($storedToken, $token)) {
            return false;
        }
        // Regenerate token after successful validation
        $this->setCsrfToken();
        return true;
    }

    public function updateLastActivity() {
        $this->set('last_activity', time());
    }

    public function getLastActivity() {
        return $this->get('last_activity', 0);
    }

    public function isExpired($lifetime = null) {
        if ($lifetime === null) {
            $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800;
        }
        $lastActivity = $this->getLastActivity();
        return (time() - $lastActivity) > $lifetime;
    }
}
