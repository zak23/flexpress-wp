<?php

/** Synthetic content/entitlements; actual preview handler and access helpers, no provider calls. */
define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);
$theme = dirname(__DIR__) . '/wp-content/themes/flexpress';
$checks = 0;
$age_enabled = false;
$age_deadline = time() + 30;
$age_denied = false;
function flexpress_exclusv_age_enabled() { return $GLOBALS['age_enabled']; }
function flexpress_exclusv_age_media_deadline() {
    if ($GLOBALS['age_denied']) { throw new RuntimeException('Synthetic denied assurance'); }
    return $GLOBALS['age_deadline'];
}
$user = 0;
$founder = false;
$meta = [];
$preview = '11111111-1111-1111-1111-111111111111';
$full = '22222222-2222-2222-2222-222222222222';
$post = (object) ['ID' => 101, 'post_type' => 'episode', 'post_status' => 'publish', 'post_password' => ''];
$fields = ['preview_video' => $preview, 'full_video' => $full, 'access_type' => 'membership_only'];
$configured = true;
$private_headers = 0;
class MediaResponse extends Exception {
    public function __construct(public bool $success, public $data, public int $status) {}
}
function get_post($id) { return $id === 101 ? $GLOBALS['post'] : null; }
function get_field($key, $id) { return $GLOBALS['fields'][$key] ?? null; }
function get_current_user_id() { return $GLOBALS['user']; }
function get_the_ID() { return 101; }
function flexpress_user_is_founder($id) { return $id > 0 && $GLOBALS['founder']; }
function flexpress_is_episode_released($id) { return !($GLOBALS['fields']['unreleased'] ?? false); }
function get_user_meta($id, $key, $single) { return $GLOBALS['meta'][$key] ?? ''; }
function flexpress_update_membership_status($id, $status) { $GLOBALS['meta']['membership_status'] = $status; }
function current_time($format) { return time(); }
function wp_cache_delete(...$args) {}
function wp_unslash($value) { return stripslashes($value); }
function wp_verify_nonce($value, $action) { return $value === 'fixture'; }
function nocache_headers() { ++$GLOBALS['private_headers']; }
function get_template_directory() { return $GLOBALS['theme']; }
function get_option($key, $default) {
    return $GLOBALS['configured'] ? ['bunnycdn_library_id' => 'fixture', 'bunnycdn_token_key' => 'synthetic-signing-key'] : [];
}
function wp_send_json_error($data, $status = 200) { throw new MediaResponse(false, $data, $status); }
function wp_send_json_success($data, $status = 200) { throw new MediaResponse(true, $data, $status); }
function check($condition, $message) {
    ++$GLOBALS['checks'];
    if (!$condition) { throw new RuntimeException($message); }
}
function loadFunction($name) {
    $source = file_get_contents($GLOBALS['theme'] . '/functions.php');
    if (!preg_match('/^function ' . preg_quote($name, '/') . '\([^\n]*\)\s*\{.*?^\}/ms', $source, $match)) {
        throw new RuntimeException('Missing actual theme function');
    }
    eval($match[0]);
}
foreach (['flexpress_check_episode_access', 'flexpress_get_membership_status', 'flexpress_generate_bunnycdn_token'] as $name) {
    loadFunction($name);
}
require $theme . '/includes/media-authorization.php';
function request(array $overrides = [], string $method = 'POST'): MediaResponse {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_POST = array_merge(['post_id' => '101', 'video_id' => $GLOBALS['preview'], 'nonce' => 'fixture'], $overrides);
    try { flexpress_generate_bunnycdn_token(); } catch (MediaResponse $response) { return $response; }
    throw new RuntimeException('Handler returned without a response');
}
$response = request();
check($response->success && $response->data['expires'] <= time() + 300, 'Published preview was not issued with a bounded expiry');
check($private_headers === 1, 'Signing response was not private');
check(request([], 'GET')->status === 405, 'GET signed a video');
check(request(['nonce' => 'invalid'])->status === 403, 'Bad nonce accepted');
check(request(['nonce' => []])->status === 403, 'Array nonce accepted');
foreach (['', '-1', '0', '101.0', '101x', [], '999999999999999999'] as $id) {
    check(request(['post_id' => $id])->status === 400, 'Invalid post ID accepted');
}
check(request(['post_id' => '999'])->status === 403, 'Unknown content signed');
check(request(['video_id' => $full])->status === 403, 'Arbitrary full video ID signed');
check(request(['video_id' => []])->status === 400, 'Array video ID accepted');
check(request(['video_id' => '../video'])->status === 403, 'Invalid video identifier accepted');
foreach (['draft', 'private', 'future', 'trash'] as $status) {
    $post->post_status = $status;
    check(request()->status === 403, 'Non-public content signed');
}
$post->post_status = 'publish';
$post->post_password = 'fixture';
check(request()->status === 403, 'Password-protected content signed');
$post->post_password = '';
$post->post_type = 'page';
check(request()->status === 403, 'Unrelated post type signed');
$post->post_type = 'episode';
$configured = false;
check(request()->status === 503, 'Missing signing configuration accepted');
$configured = true;

// A preview accidentally configured as the full video still needs ordinary content entitlement.
$fields['full_video'] = $preview;
check(request()->status === 403, 'Signed-out visitor received full video');
$user = 41;
$meta = ['membership_status' => 'active', 'membership_expires' => '2099-01-01'];
check(request()->success, 'Active ordinary member denied included episode');
$meta['membership_expires'] = '2000-01-01';
check(request()->status === 403, 'Expired ordinary member received full video');
$meta = ['membership_status' => 'active', 'membership_expires' => '2099-01-01'];
$fields['access_type'] = 'ppv_only';
check(request()->status === 403, 'Membership bypassed PPV requirement');
$meta['ppv_purchases'] = [102];
check(request()->status === 403, 'Purchase of another item granted access');
$meta['ppv_purchases'] = [101];
check(request()->success, 'Own PPV purchase denied');
$meta['membership_status'] = 'banned';
check(request()->status === 403, 'Banned account used retained PPV metadata');
$meta['membership_status'] = 'active';
$fields['unreleased'] = true;
check(request()->status === 403, 'Unreleased full episode signed for ordinary purchaser');
$founder = true;
check(request()->success, 'Founder paid-access invariant changed');
$founder = false;
$fields['unreleased'] = false;
$fields['access_type'] = 'free';
$user = 0;
check(request()->success, 'Explicit free full preview denied');
$age_enabled = true;
$response = request();
check($response->success && $response->data['expires'] === $age_deadline, 'Preview outlived age access');
$age_denied = true;
check(request()->status === 403, 'Missing age access signed a preview');
$founder = true;
$user = 1;
check(request()->status === 403, 'Founder paid bypass also bypassed age access');
$age_denied = false;
$age_deadline = time() - 1;
check(request()->status === 403, 'Expired age proof signed a preview');
echo "Preview authorization tests passed ($checks assertions). No provider calls or real entitlements used.\n";
