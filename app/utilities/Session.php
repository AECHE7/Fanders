<?php
/**
 * Session - Handles session management
 */
class Session {
    /**
     * Start session if not already started
     */
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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

    public function destroy() {
        $_SESSION = [];
        
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
        
        session_destroy();
    }

 
    public function regenerate($deleteOldSession = true) {
        return session_regenerate_id($deleteOldSession);
    }


    public function setTimeout($seconds) {
        ini_set('session.gc_maxlifetime', $seconds);
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(
                session_name(),
                $_COOKIE[session_name()],
                time() + $seconds,
                '/'
            );
        }
    }


    public function hasTimedOut($maxIdleTime = SESSION_LIFETIME) {
        $lastActivity = $this->get('last_activity');
        
        if ($lastActivity) {
            $idleTime = time() - $lastActivity;
            
            if ($idleTime > $maxIdleTime) {
                return true;
            }
        }
        
        // Update last activity
        $this->set('last_activity', time());
        
        return false;
    }
}
