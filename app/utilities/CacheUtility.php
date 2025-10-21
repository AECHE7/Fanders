<?php
/**
 * Cache Utility Class
 * Provides caching functionality for improved performance
 */

class CacheUtility
{
    private $cacheDir;
    private $defaultTtl = 3600; // 1 hour default TTL

    public function __construct($cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?: BASE_PATH . '/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cached data
     */
    public function get($key)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return false;
        }

        try {
            $content = file_get_contents($file);
            if ($content === false) {
                return false;
            }

            // Use error suppression and validation for unserialize
            $data = @unserialize($content, ['allowed_classes' => false]);

            // Check if unserialize was successful
            if ($data === false) {
                // If unserialize failed, delete the corrupted cache
                $this->delete($key);
                return false;
            }

            // Validate data structure
            if (!is_array($data) || !isset($data['value']) || !isset($data['expires'])) {
                $this->delete($key);
                return false;
            }

            // Check if cache has expired
            if (time() > $data['expires']) {
                $this->delete($key);
                return false;
            }

            return $data['value'];
        } catch (Exception $e) {
            // Log the error and delete corrupted cache
            error_log("Cache read error for key '{$key}': " . $e->getMessage());
            $this->delete($key);
            return false;
        }
    }

    /**
     * Set cached data
     */
    public function set($key, $value, $ttl = null)
    {
        try {
            $ttl = $ttl ?: $this->defaultTtl;
            $data = [
                'value' => $value,
                'expires' => time() + $ttl,
                'created' => time(),
                'version' => 1 // Add version for future compatibility
            ];

            $file = $this->getCacheFile($key);
            $serialized = serialize($data);

            // Verify serialization was successful by attempting to unserialize
            $test = @unserialize($serialized, ['allowed_classes' => false]);
            if ($test === false) {
                error_log("Cache serialization failed for key '{$key}'");
                return false;
            }

            return file_put_contents($file, $serialized) !== false;
        } catch (Exception $e) {
            error_log("Cache write error for key '{$key}': " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete cached data
     */
    public function delete($key)
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * Clear all cache
     */
    public function clear()
    {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    /**
     * Clear corrupted cache entries
     * Removes all cache files that cannot be properly unserialized
     */
    public function clearCorrupted()
    {
        $files = glob($this->cacheDir . '/*');
        $cleared = 0;

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            try {
                $content = file_get_contents($file);
                if ($content === false) {
                    unlink($file);
                    $cleared++;
                    continue;
                }

                $data = @unserialize($content, ['allowed_classes' => false]);

                if ($data === false || !is_array($data) || !isset($data['expires'])) {
                    unlink($file);
                    $cleared++;
                }
            } catch (Exception $e) {
                if (file_exists($file)) {
                    unlink($file);
                    $cleared++;
                }
            }
        }

        error_log("Cache cleanup: Removed {$cleared} corrupted entries");
        return $cleared;
    }

    /**
     * Check if cache exists and is valid
     */
    public function has($key)
    {
        return $this->get($key) !== false;
    }

    /**
     * Get or set cache (with callback)
     */
    public function remember($key, $ttl, $callback)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    /**
     * Get cache file path
     */
    private function getCacheFile($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * Get cache statistics
     */
    public function getStats()
    {
        $files = glob($this->cacheDir . '/*');
        $stats = [
            'total_files' => count($files),
            'total_size' => 0,
            'valid_entries' => 0,
            'expired_entries' => 0,
            'corrupted_entries' => 0
        ];

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $stats['total_size'] += filesize($file);

            try {
                $content = file_get_contents($file);
                if ($content === false) {
                    $stats['corrupted_entries']++;
                    continue;
                }

                $data = @unserialize($content, ['allowed_classes' => false]);

                // Check if unserialize was successful and data is valid
                if ($data === false || !is_array($data) || !isset($data['expires'])) {
                    $stats['corrupted_entries']++;
                    continue;
                }

                if (time() > $data['expires']) {
                    $stats['expired_entries']++;
                } else {
                    $stats['valid_entries']++;
                }
            } catch (Exception $e) {
                $stats['corrupted_entries']++;
                error_log("Cache stats error: " . $e->getMessage());
            }
        }

        return $stats;
    }

    /**
     * Generate cache key for common data types
     * @param string $type Data type (stats, dropdown, etc.)
     * @param array $params Parameters for the cache key
     * @return string
     */
    public static function generateKey($type, $params = []) {
        $keyParts = [$type];

        if (!empty($params)) {
            ksort($params); // Ensure consistent ordering
            $keyParts[] = http_build_query($params);
        }

        return implode(':', $keyParts);
    }
}
