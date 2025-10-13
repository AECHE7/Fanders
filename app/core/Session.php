

<?php
/**
 * Session - Manages user sessions
 */
class Session {
    private $sessionLifetime = 1800; // 30 minutes
    

    public function __construct() {
        // Only set session cookie parameters if session is not active
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => $this->sessionLifetime,
                'path' => '/',
                'domain' => '',
                'secure' => false, // Set to true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }

        // Start the session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session needs to be regenerated
        $this->checkSessionLifetime();
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
        $this->clear();
        session_destroy();
        
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
        // Update last activity time on regeneration
        $this->set('last_activity', time());
    }
    

    private function checkSessionLifetime() {
        $lastActivity = $this->get('last_activity');
        $currentTime = time();
        
        // If last activity timestamp is set
        if ($lastActivity) {
            // If session has expired
            if (($currentTime - $lastActivity) > $this->sessionLifetime) {
                // Clear session data
                $this->clear();
                // Regenerate session ID
                $this->regenerate();
            } else if (($currentTime - $lastActivity) > ($this->sessionLifetime / 2)) {
                // Regenerate session ID halfway through the lifetime as well
                $this->regenerate();
            }
        }
        
        // Update last activity time
        $this->set('last_activity', $currentTime);
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
        return $token === $this->getCsrfToken();
    }
}
