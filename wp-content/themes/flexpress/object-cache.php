<?php
/**
 * Redis Object Cache Drop-in for FlexPress
 * 
 * This file enables Redis as a persistent object cache for WordPress.
 * It replaces the default WordPress object cache with Redis for better performance.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Redis configuration
define('WP_REDIS_HOST', 'redis');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_DATABASE', 0);
define('WP_REDIS_PASSWORD', '');
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_READ_TIMEOUT', 1);

// Cache key prefix for this site
define('WP_REDIS_PREFIX', 'flexpress:' . (defined('WP_ENV') ? WP_ENV : 'prod') . ':');

// Enable Redis object cache
define('WP_REDIS_DISABLED', false);

// Redis object cache implementation
class Redis_Object_Cache {
    
    private $redis;
    private $connected = false;
    private $prefix;
    
    public function __construct() {
        $this->prefix = WP_REDIS_PREFIX;
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->redis = new Redis();
            $this->connected = $this->redis->connect(
                WP_REDIS_HOST,
                WP_REDIS_PORT,
                WP_REDIS_TIMEOUT
            );
            
            if ($this->connected && WP_REDIS_PASSWORD) {
                $this->redis->auth(WP_REDIS_PASSWORD);
            }
            
            if ($this->connected && WP_REDIS_DATABASE) {
                $this->redis->select(WP_REDIS_DATABASE);
            }
            
        } catch (Exception $e) {
            $this->connected = false;
            error_log('Redis connection failed: ' . $e->getMessage());
        }
    }
    
    private function get_key($key) {
        return $this->prefix . $key;
    }
    
    public function get($key, $group = 'default') {
        if (!$this->connected) {
            return false;
        }
        
        $full_key = $this->get_key($group . ':' . $key);
        $value = $this->redis->get($full_key);
        
        if ($value === false) {
            return false;
        }
        
        return maybe_unserialize($value);
    }
    
    public function set($key, $data, $group = 'default', $expire = 0) {
        if (!$this->connected) {
            return false;
        }
        
        $full_key = $this->get_key($group . ':' . $key);
        $serialized = maybe_serialize($data);
        
        if ($expire > 0) {
            return $this->redis->setex($full_key, $expire, $serialized);
        } else {
            return $this->redis->set($full_key, $serialized);
        }
    }
    
    public function delete($key, $group = 'default') {
        if (!$this->connected) {
            return false;
        }
        
        $full_key = $this->get_key($group . ':' . $key);
        return $this->redis->del($full_key);
    }
    
    public function flush() {
        if (!$this->connected) {
            return false;
        }
        
        $keys = $this->redis->keys($this->prefix . '*');
        if (!empty($keys)) {
            return $this->redis->del($keys);
        }
        
        return true;
    }
    
    public function flush_group($group) {
        if (!$this->connected) {
            return false;
        }
        
        $keys = $this->redis->keys($this->prefix . $group . ':*');
        if (!empty($keys)) {
            return $this->redis->del($keys);
        }
        
        return true;
    }
    
    public function get_stats() {
        if (!$this->connected) {
            return array();
        }
        
        $info = $this->redis->info();
        return array(
            'connected' => $this->connected,
            'redis_version' => $info['redis_version'] ?? 'unknown',
            'used_memory' => $info['used_memory_human'] ?? 'unknown',
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
        );
    }
}

// Disable this theme-level drop-in: WordPress loads wp-content/object-cache.php instead.
// Leaving an extra object cache here can conflict with the official drop-in and cause
// user-specific state (e.g., is_user_logged_in) to be cached incorrectly across users.
// We still ship the class for reference, but do not initialize it here.
// The canonical drop-in is copied into wp-content/object-cache.php by the Docker build.
// If you need to test this implementation, do it explicitly and never in production.

// $GLOBALS['wp_object_cache'] = new Redis_Object_Cache();

// Add admin notice for Redis status
add_action('admin_notices', function() {
    global $wp_object_cache;
    
    if (current_user_can('manage_options')) {
        $stats = $wp_object_cache->get_stats();
        
        if ($stats['connected']) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Redis Object Cache:</strong> Connected and active. ';
            echo 'Memory usage: ' . $stats['used_memory'] . ', ';
            echo 'Hits: ' . $stats['keyspace_hits'] . ', ';
            echo 'Misses: ' . $stats['keyspace_misses'] . '</p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Redis Object Cache:</strong> Not connected. Using default object cache.</p>';
            echo '</div>';
        }
    }
});