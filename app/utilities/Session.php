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

    /**
     * Set session variable
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session variable
     * 
     * @param string $key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * Check if session variable exists
     * 
     * @param string $key
     * @return bool
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session variable
     * 
     * @param string $key
     * @return void
     */
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Set flash message
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setFlash($key, $value) {
        $_SESSION['flash'][$key] = $value;
    }

    /**
     * Get flash message and remove it
     * 
     * @param string $key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function getFlash($key, $default = null) {
        $value = $default;
        
        if (isset($_SESSION['flash'][$key])) {
            $value = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
        }
        
        return $value;
    }

    /**
     * Check if flash message exists
     * 
     * @param string $key
     * @return bool
     */
    public function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }

    /**
     * Destroy session
     * 
     * @return void
     */
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

    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOldSession Whether to delete the old session data
     * @return bool
     */
    public function regenerate($deleteOldSession = true) {
        return session_regenerate_id($deleteOldSession);
    }

    /**
     * Set session timeout
     * 
     * @param int $seconds Timeout in seconds
     * @return void
     */
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

    /**
     * Check if session has timed out
     * 
     * @param int $maxIdleTime Maximum idle time in seconds
     * @return bool
     */
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
