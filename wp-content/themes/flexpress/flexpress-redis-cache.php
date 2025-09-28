<?php
/**
 * Plugin Name: FlexPress Redis Object Cache
 * Description: Enables Redis as a persistent object cache for WordPress
 * Version: 1.0.0
 * Author: FlexPress
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
define('WP_REDIS_PREFIX', 'flexpress:' . (defined('WP_ENV') ? WP_ENV : 'prod') . ':');

// Redis Object Cache Class
class FlexPress_Redis_Object_Cache {
    
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

// Initialize Redis object cache
add_action('init', function() {
    global $wp_object_cache;
    
    // Only replace if not already set
    if (!isset($wp_object_cache) || !($wp_object_cache instanceof FlexPress_Redis_Object_Cache)) {
        $wp_object_cache = new FlexPress_Redis_Object_Cache();
    }
});

// Add admin notice for Redis status
add_action('admin_notices', function() {
    global $wp_object_cache;
    
    if (current_user_can('manage_options')) {
        if ($wp_object_cache instanceof FlexPress_Redis_Object_Cache) {
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
    }
});

// Add admin menu for Redis cache management
add_action('admin_menu', function() {
    add_management_page(
        'Redis Cache',
        'Redis Cache',
        'manage_options',
        'redis-cache',
        function() {
            global $wp_object_cache;
            
            if ($wp_object_cache instanceof FlexPress_Redis_Object_Cache) {
                $stats = $wp_object_cache->get_stats();
                
                echo '<div class="wrap">';
                echo '<h1>Redis Object Cache Status</h1>';
                
                if ($stats['connected']) {
                    echo '<div class="notice notice-success">';
                    echo '<p><strong>Status:</strong> Connected and active</p>';
                    echo '<p><strong>Redis Version:</strong> ' . $stats['redis_version'] . '</p>';
                    echo '<p><strong>Memory Usage:</strong> ' . $stats['used_memory'] . '</p>';
                    echo '<p><strong>Cache Hits:</strong> ' . $stats['keyspace_hits'] . '</p>';
                    echo '<p><strong>Cache Misses:</strong> ' . $stats['keyspace_misses'] . '</p>';
                    echo '</div>';
                    
                    if (isset($_POST['flush_cache']) && wp_verify_nonce($_POST['_wpnonce'], 'flush_redis_cache')) {
                        $wp_object_cache->flush();
                        echo '<div class="notice notice-success"><p>Redis cache flushed successfully!</p></div>';
                    }
                    
                    echo '<form method="post">';
                    wp_nonce_field('flush_redis_cache');
                    echo '<p><input type="submit" name="flush_cache" value="Flush Redis Cache" class="button button-secondary" /></p>';
                    echo '</form>';
                } else {
                    echo '<div class="notice notice-error">';
                    echo '<p><strong>Status:</strong> Not connected</p>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
        }
    );
});
