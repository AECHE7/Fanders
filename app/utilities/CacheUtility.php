<?php

/**
 * CacheUtility Class
 * Provides simple file-based caching for frequently accessed data
 */
class CacheUtility
{
    private static $cacheDir = null;
    private static $defaultTtl = 3600; // 1 hour default TTL

    /**
     * Initialize cache directory
     */
    private static function initCacheDir() {
        if (self::$cacheDir === null) {
            self::$cacheDir = BASE_PATH . '/storage/cache';
            if (!is_dir(self::$cacheDir)) {
                mkdir(self::$cacheDir, 0755, true);
            }
        }
    }

    /**
     * Generate cache file path
     * @param string $key Cache key
     * @return string
     */
    private static function getCacheFilePath($key) {
        self::initCacheDir();
        return self::$cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * Store data in cache
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $ttl Time to live in seconds
     * @return bool
     */
    public static function set($key, $data, $ttl = null) {
        if ($ttl === null) {
            $ttl = self::$defaultTtl;
        }

        $cacheData = [
            'data' => $data,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];

        $filePath = self::getCacheFilePath($key);
        $result = file_put_contents($filePath, serialize($cacheData), LOCK_EX);
        
        return $result !== false;
    }

    /**
     * Retrieve data from cache
     * @param string $key Cache key
     * @param mixed $default Default value if cache miss
     * @return mixed
     */
    public static function get($key, $default = null) {
        $filePath = self::getCacheFilePath($key);
        
        if (!file_exists($filePath)) {
            return $default;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return $default;
        }

        $cacheData = unserialize($content);
        if ($cacheData === false) {
            // Corrupted cache, remove it
            unlink($filePath);
            return $default;
        }

        // Check if expired
        if (time() > $cacheData['expires_at']) {
            unlink($filePath);
            return $default;
        }

        return $cacheData['data'];
    }

    /**
     * Check if cache key exists and is not expired
     * @param string $key Cache key
     * @return bool
     */
    public static function has($key) {
        $filePath = self::getCacheFilePath($key);
        
        if (!file_exists($filePath)) {
            return false;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $cacheData = unserialize($content);
        if ($cacheData === false) {
            unlink($filePath);
            return false;
        }

        // Check if expired
        if (time() > $cacheData['expires_at']) {
            unlink($filePath);
            return false;
        }

        return true;
    }

    /**
     * Remove specific cache entry
     * @param string $key Cache key
     * @return bool
     */
    public static function forget($key) {
        $filePath = self::getCacheFilePath($key);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }

    /**
     * Clear all cache entries
     * @return bool
     */
    public static function clear() {
        self::initCacheDir();
        
        $files = glob(self::$cacheDir . '/*.cache');
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Get or set cache with callback
     * @param string $key Cache key
     * @param callable $callback Function to generate data if cache miss
     * @param int $ttl Time to live in seconds
     * @return mixed
     */
    public static function remember($key, $callback, $ttl = null) {
        $data = self::get($key);
        
        if ($data !== null) {
            return $data;
        }
        
        $data = call_user_func($callback);
        self::set($key, $data, $ttl);
        
        return $data;
    }

    /**
     * Get cache statistics
     * @return array
     */
    public static function getStats() {
        self::initCacheDir();
        
        $files = glob(self::$cacheDir . '/*.cache');
        $totalSize = 0;
        $expiredCount = 0;
        $validCount = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $content = file_get_contents($file);
            if ($content !== false) {
                $cacheData = unserialize($content);
                if ($cacheData !== false) {
                    if (time() > $cacheData['expires_at']) {
                        $expiredCount++;
                    } else {
                        $validCount++;
                    }
                }
            }
        }
        
        return [
            'total_files' => count($files),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'valid_entries' => $validCount,
            'expired_entries' => $expiredCount
        ];
    }

    /**
     * Clean expired cache entries
     * @return int Number of cleaned entries
     */
    public static function cleanExpired() {
        self::initCacheDir();
        
        $files = glob(self::$cacheDir . '/*.cache');
        $cleanedCount = 0;
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $cacheData = unserialize($content);
                if ($cacheData !== false && time() > $cacheData['expires_at']) {
                    if (unlink($file)) {
                        $cleanedCount++;
                    }
                }
            }
        }
        
        return $cleanedCount;
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