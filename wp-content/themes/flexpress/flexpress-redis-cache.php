<?php
/**
 * FlexPress Redis Object Cache (optional)
 *
 * Redis is OFF by default. To enable, add to wp-config.php (before "That's all"):
 *   define('WP_REDIS_ENABLED', true);
 *
 * When disabled, WordPress uses its default in-memory object cache. When enabled,
 * Redis is used if the PHP Redis extension is available and the server is reachable.
 * All FlexPress code lives in the theme — do not deploy to wp-content/plugins/.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Redis is off by default; set WP_REDIS_ENABLED in wp-config.php to enable
if (!defined('WP_REDIS_ENABLED')) {
    define('WP_REDIS_ENABLED', false);
}

// Redis configuration (allow wp-config or env to override, only used when enabled)
if (!defined('WP_REDIS_HOST')) {
    define('WP_REDIS_HOST', 'redis');
}
if (!defined('WP_REDIS_PORT')) {
    define('WP_REDIS_PORT', 6379);
}
if (!defined('WP_REDIS_DATABASE')) {
    define('WP_REDIS_DATABASE', 0);
}
if (!defined('WP_REDIS_PASSWORD')) {
    define('WP_REDIS_PASSWORD', '');
}
if (!defined('WP_REDIS_TIMEOUT')) {
    define('WP_REDIS_TIMEOUT', 1);
}
if (!defined('WP_REDIS_READ_TIMEOUT')) {
    define('WP_REDIS_READ_TIMEOUT', 1);
}
if (!defined('WP_REDIS_PREFIX')) {
    define('WP_REDIS_PREFIX', 'flexpress:' . (defined('WP_ENV') ? WP_ENV : 'prod') . ':');
}

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
        if (!extension_loaded('redis') || !class_exists('Redis')) {
            $this->connected = false;
            return;
        }
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
    
    /**
     * WordPress object cache API: get. Accepts WP's optional $force and &$found.
     */
    public function get($key, $group = 'default', $force = false, &$found = null) {
        if (empty($group)) {
            $group = 'default';
        }
        if (!$this->connected) {
            $found = false;
            return false;
        }

        $full_key = $this->get_key($group . ':' . $key);
        $value = $this->redis->get($full_key);

        if ($value === false) {
            $found = false;
            return false;
        }
        $found = true;
        return maybe_unserialize($value);
    }

    /**
     * WordPress object cache API: add. Only stores if key does not exist.
     */
    public function add($key, $data, $group = 'default', $expire = 0) {
        if (empty($group)) {
            $group = 'default';
        }
        if (!$this->connected) {
            return false;
        }
        $full_key = $this->get_key($group . ':' . $key);
        if ($this->redis->exists($full_key)) {
            return false;
        }
        return $this->set($key, $data, $group, $expire);
    }

    /**
     * WordPress object cache API: replace. Only stores if key already exists.
     */
    public function replace($key, $data, $group = 'default', $expire = 0) {
        if (empty($group)) {
            $group = 'default';
        }
        if (!$this->connected) {
            return false;
        }
        $full_key = $this->get_key($group . ':' . $key);
        if (!$this->redis->exists($full_key)) {
            return false;
        }
        return $this->set($key, $data, $group, $expire);
    }

    public function set($key, $data, $group = 'default', $expire = 0) {
        if (empty($group)) {
            $group = 'default';
        }
        if (!$this->connected) {
            return false;
        }

        $full_key = $this->get_key($group . ':' . $key);
        $serialized = maybe_serialize($data);

        if ($expire > 0) {
            return $this->redis->setex($full_key, (int) $expire, $serialized);
        }
        return $this->redis->set($full_key, $serialized);
    }

    /**
     * WordPress object cache API: delete. Third param $deprecated is ignored.
     */
    public function delete($key, $group = 'default', $deprecated = false) {
        if (empty($group)) {
            $group = 'default';
        }
        if (!$this->connected) {
            return false;
        }
        $full_key = $this->get_key($group . ':' . $key);
        return (bool) $this->redis->del($full_key);
    }
    
    /**
     * WordPress object cache API: incr. Increment numeric value.
     */
    public function incr($key, $offset = 1, $group = 'default') {
        if (empty($group)) {
            $group = 'default';
        }
        if (!$this->connected) {
            return false;
        }
        $full_key = $this->get_key($group . ':' . $key);
        $val = $this->redis->get($full_key);
        if ($val === false) {
            return false;
        }
        $new = (int) $val + (int) $offset;
        $this->redis->set($full_key, (string) $new);
        return $new;
    }

    /**
     * WordPress object cache API: decr. Decrement numeric value.
     */
    public function decr($key, $offset = 1, $group = 'default') {
        if (empty($group)) {
            $group = 'default';
        }
        if (!$this->connected) {
            return false;
        }
        $full_key = $this->get_key($group . ':' . $key);
        $val = $this->redis->get($full_key);
        if ($val === false) {
            return false;
        }
        $new = max(0, (int) $val - (int) $offset);
        $this->redis->set($full_key, (string) $new);
        return $new;
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

// Initialize Redis object cache only when explicitly enabled and extension is available
add_action('init', function() {
    global $wp_object_cache;

    if (!defined('WP_REDIS_ENABLED') || !WP_REDIS_ENABLED) {
        return;
    }
    if (!extension_loaded('redis') || !class_exists('Redis')) {
        return;
    }
    if (!isset($wp_object_cache) || !($wp_object_cache instanceof FlexPress_Redis_Object_Cache)) {
        $wp_object_cache = new FlexPress_Redis_Object_Cache();
    }
});

// Add admin notice for Redis status (founders only)
add_action('admin_notices', function() {
    global $wp_object_cache;

    if (!flexpress_current_user_is_founder()) {
        return;
    }
    if (!defined('WP_REDIS_ENABLED') || !WP_REDIS_ENABLED) {
        return;
    }
    if (!extension_loaded('redis')) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Redis Object Cache:</strong> Enabled but the Redis PHP extension is not installed. Using default object cache.</p>';
        echo '</div>';
        return;
    }
    if ($wp_object_cache instanceof FlexPress_Redis_Object_Cache) {
        $stats = $wp_object_cache->get_stats();
        if ($stats['connected']) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Redis Object Cache:</strong> Connected. ';
            echo 'Memory: ' . esc_html($stats['used_memory']) . ', ';
            echo 'Hits: ' . esc_html($stats['keyspace_hits']) . ', Misses: ' . esc_html($stats['keyspace_misses']) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Redis Object Cache:</strong> Enabled but not connected to Redis. Using default object cache.</p>';
            echo '</div>';
        }
    }
});

// Add admin menu for Redis cache (status when enabled; instructions when disabled)
add_action('admin_menu', function() {
    add_management_page(
        'Redis Cache',
        'Redis Cache',
        'manage_options',
        'redis-cache',
        function() {
            global $wp_object_cache;

            echo '<div class="wrap">';
            echo '<h1>Redis Object Cache</h1>';

            if (!defined('WP_REDIS_ENABLED') || !WP_REDIS_ENABLED) {
                echo '<div class="notice notice-info">';
                echo '<p><strong>Redis is disabled by default.</strong> To enable, add this to <code>wp-config.php</code> (before "That\'s all, stop editing!"):</p>';
                echo '<pre style="background:#f0f0f0;padding:1em;">define(\'WP_REDIS_ENABLED\', true);</pre>';
                echo '<p>When disabled, WordPress uses its default object cache. No Redis server or PHP extension is required.</p>';
                echo '</div>';
                echo '</div>';
                return;
            }

            if (!($wp_object_cache instanceof FlexPress_Redis_Object_Cache)) {
                echo '<div class="notice notice-warning"><p>Redis is enabled but not active (e.g. Redis extension or server unavailable).</p></div>';
                echo '</div>';
                return;
            }

            $stats = $wp_object_cache->get_stats();
            if ($stats['connected']) {
                echo '<div class="notice notice-success">';
                echo '<p><strong>Status:</strong> Connected</p>';
                echo '<p><strong>Redis Version:</strong> ' . esc_html($stats['redis_version']) . '</p>';
                echo '<p><strong>Memory:</strong> ' . esc_html($stats['used_memory']) . ' | <strong>Hits:</strong> ' . esc_html($stats['keyspace_hits']) . ' | <strong>Misses:</strong> ' . esc_html($stats['keyspace_misses']) . '</p>';
                echo '</div>';
                if (isset($_POST['flush_cache']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'flush_redis_cache')) {
                    $wp_object_cache->flush();
                    echo '<div class="notice notice-success"><p>Cache flushed.</p></div>';
                }
                echo '<form method="post">';
                wp_nonce_field('flush_redis_cache');
                echo '<p><input type="submit" name="flush_cache" value="Flush Redis Cache" class="button button-secondary" /></p>';
                echo '</form>';
            } else {
                echo '<div class="notice notice-error"><p><strong>Status:</strong> Not connected. Check Redis host/port and that the Redis PHP extension is loaded.</p></div>';
            }
            echo '</div>';
        }
    );
});
