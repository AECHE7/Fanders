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

        $data = unserialize(file_get_contents($file));

        // Check if cache has expired
        if (time() > $data['expires']) {
            $this->delete($key);
            return false;
        }

        return $data['value'];
    }

    /**
     * Set cached data
     */
    public function set($key, $value, $ttl = null)
    {
        $ttl = $ttl ?: $this->defaultTtl;
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];

        $file = $this->getCacheFile($key);
        return file_put_contents($file, serialize($data)) !== false;
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
            'expired_entries' => 0
        ];

        foreach ($files as $file) {
            $stats['total_size'] += filesize($file);

            $data = unserialize(file_get_contents($file));
            if (time() > $data['expires']) {
                $stats['expired_entries']++;
            } else {
                $stats['valid_entries']++;
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
