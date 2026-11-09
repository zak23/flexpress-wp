<?php

/** Optional gateway adapter. Disabled unless the host explicitly enables and configures it. */
if (!defined('ABSPATH')) {
    exit;
}

function flexpress_exclusv_age_enabled()
{
    return defined('FLEXPRESS_EXCLUSV_AGE_ENABLED') && FLEXPRESS_EXCLUSV_AGE_ENABLED === true;
}

function flexpress_exclusv_age_private_file($path)
{
    $real = is_string($path) ? realpath($path) : false;
    $root = realpath(ABSPATH);
    if (!$real || !$root || !is_file($real) || !is_readable($real) ||
        $real === $root || strpos($real, $root . DIRECTORY_SEPARATOR) === 0) {
        throw new RuntimeException('Private age configuration is unavailable.');
    }
    return $real;
}

function flexpress_exclusv_age_app()
{
    static $app;
    if ($app) { return $app; }
    if (PHP_VERSION_ID < 80200 || !extension_loaded('sodium') || is_multisite() ||
        !defined('FLEXPRESS_EXCLUSV_AGE_CONFIG')) {
        throw new RuntimeException('Age integration is unavailable.');
    }
    $settings = require flexpress_exclusv_age_private_file(FLEXPRESS_EXCLUSV_AGE_CONFIG);
    if (!is_array($settings)) { throw new RuntimeException('Age integration is unavailable.'); }
    $autoload = flexpress_exclusv_age_private_file($settings['runtime_autoload'] ?? null);
    $manifest = dirname($autoload, 2) . '/runtime-manifest.json';
    if (!is_file($manifest) || filesize($manifest) > 512000 ||
        (json_decode(file_get_contents($manifest), true)['prefix'] ?? '') !== 'ExclusvAgeRuntime') {
        throw new RuntimeException('Age runtime is unavailable.');
    }
    require_once $autoload;
    foreach (array('StorageUnavailable', 'Database', 'RecordCipher', 'Records', 'Transactions', 'Sessions', 'SiteConfig', 'Adapter') as $class) {
        require_once __DIR__ . '/exclusv-age/' . $class . '.php';
    }
    $config = new \FlexPress\Age\SiteConfig($settings);
    global $wpdb;
    $records = \FlexPress\Age\Records::connect(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST,
        new \FlexPress\Age\RecordCipher($config->keys, $config->activeKey), $wpdb->prefix);
    return $app = new \FlexPress\Age\Adapter($config, $records);
}

function flexpress_exclusv_age_exempt()
{
    if ((defined('WP_CLI') && WP_CLI) || wp_doing_cron()) { return true; }
    if (wp_doing_ajax()) {
        $action = $_REQUEST['action'] ?? null;
        return is_string($action) && in_array($action, array('flowguard_webhook', 'flexpress_ppv_webhook'), true);
    }
    global $pagenow;
    // WordPress's own admin/login authentication remains responsible for these operator pages.
    // admin-post and every non-payment AJAX action still require age access.
    return $pagenow === 'wp-login.php' || (is_admin() && $pagenow !== 'admin-post.php');
}

function flexpress_exclusv_age_unavailable()
{
    nocache_headers();
    status_header(503);
    header('Cache-Control: private, no-store, max-age=0');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Age access is temporarily unavailable. Please try again shortly.';
    exit;
}

function flexpress_exclusv_age_media_deadline()
{
    // Media callers must call this for each issuance when the adapter is enabled.
    return flexpress_exclusv_age_app()->mediaDeadline();
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('flexpress-age install', function () {
        try {
            flexpress_exclusv_age_app()->install();
            if (!wp_next_scheduled('flexpress_exclusv_age_cleanup')) {
                wp_schedule_event(time() + 60, 'hourly', 'flexpress_exclusv_age_cleanup');
            }
            WP_CLI::success('Age storage schema and hourly cleanup are installed. Enforcement was not enabled.');
        } catch (Throwable $error) { WP_CLI::error('Age storage setup failed. Check private configuration and database availability.'); }
    });
    WP_CLI::add_command('flexpress-age purge', function () {
        try {
            $counts = flexpress_exclusv_age_app()->purge();
            WP_CLI::success('Expired records removed: ' . $counts['records'] . '; rate buckets: ' . $counts['limits']);
        } catch (Throwable $error) { WP_CLI::error('Age cleanup failed. Check private configuration and database availability.'); }
    });
}

add_action('flexpress_exclusv_age_cleanup', function () {
    try {
        // Bounded work per cron tick. A host runner should monitor failures and call purge again for a backlog.
        flexpress_exclusv_age_app()->purge();
        delete_option('flexpress_exclusv_age_cleanup_failed');
    } catch (Throwable $error) {
        update_option('flexpress_exclusv_age_cleanup_failed', time(), false);
    }
});

if (!flexpress_exclusv_age_enabled()) { return; }

add_action('init', function () {
    if (flexpress_exclusv_age_exempt()) { return; }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $rest_prefix = '/' . rest_get_url_prefix();
    if (isset($_GET['rest_route']) || $path === $rest_prefix ||
        (is_string($path) && strpos($path, $rest_prefix . '/') === 0)) { return; }
    try {
        // Keep the registered Yoursafe endpoints reachable before the visitor guard.
        if (in_array($path, array('/age-verification/yoursafe/start/', '/age-verification/yoursafe/callback/'), true) &&
            ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && flexpress_yoursafe_is_enabled()) {
            nocache_headers();
            header('Cache-Control: private, no-store, max-age=0');
            header('Referrer-Policy: no-referrer');
            flexpress_yoursafe_route_request();
        }
        $app = flexpress_exclusv_age_app();
        $app->publicPrivacy();
        if ($path === '/_age' || (is_string($path) && strpos($path, '/_age/') === 0)) {
            $app->routes();
        }
        $app->guard(wp_doing_ajax());
    } catch (Throwable $error) { flexpress_exclusv_age_unavailable(); }
}, -1000);

add_filter('rest_pre_dispatch', function ($result, $server, $request) {
    if ($request->get_route() === '/flexpress/v1/flowguard/webhook') { return $result; }
    try {
        $app = flexpress_exclusv_age_app();
        if ($app->requiresAge()) { $app->eligible(); }
        \FlexPress\Age\Adapter::privateHeaders();
        return $result;
    } catch (Throwable $error) {
        nocache_headers();
        return new WP_Error('age_required', 'Age access is required.', array('status' => 403));
    }
}, -1000, 3);

add_filter('wp_headers', function ($headers) {
    $headers['Cache-Control'] = 'private, no-store, max-age=0';
    // Content may send the origin to existing media hotlink checks; callbacks stay no-referrer.
    $headers['Referrer-Policy'] = 'origin';
    return $headers;
}, PHP_INT_MAX);

add_action('wp_logout', function () {
    flexpress_clear_age_verified_cookie();
    try { flexpress_exclusv_age_app()->clear(); } catch (Throwable $error) {
        setcookie('__Host-flexpress_age', '', array('expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax'));
    }
});
