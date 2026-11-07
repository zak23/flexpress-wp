<?php
// Offline regression: login return URLs must not be mistaken for admin requests.
$root = getenv('FLEXPRESS_TEST_ROOT') ?: dirname(__DIR__);
$source = file_get_contents($root . '/wp-content/themes/flexpress/functions.php');
preg_match('/^function flexpress_redirect_wp_admin_to_login\(\)\s*\{.*?^\}/ms', $source, $match);
if (!$match) throw new RuntimeException('Missing admin redirect handler');
eval($match[0]);
$admin_context = false;
function is_admin() { return $GLOBALS['admin_context']; }
function is_user_logged_in() { return false; }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function home_url($path) { return 'https://example.invalid' . $path; }
function wp_redirect($url) { throw new RuntimeException('Unexpected redirect: ' . $url); }
$requests = [
    '/wp-login.php?redirect_to=https%3A%2F%2Fexample.invalid%2Fwp-admin%2F&reauth=1',
    '/wp-login.php?redirect_to=/wp-admin/',
    '/wp-login.php',
    '/login/?redirect_to=/wp-admin/',
    '/?search=wp-admin',
];
foreach ($requests as $uri) {
    $_SERVER['REQUEST_URI'] = $uri;
    flexpress_redirect_wp_admin_to_login();
}
$admin_context = true;
foreach (['/wp-admin/', '/wp-admin/admin-ajax.php'] as $uri) {
    $_SERVER['REQUEST_URI'] = $uri;
    flexpress_redirect_wp_admin_to_login();
}
echo "Admin login routing passed (7 cases).\n";
