<?php
/** Exact-file CDN signing. Content callers must authorize the asset before issuing its URL. */
if (!defined('ABSPATH')) { exit; }

final class FlexPress_Cdn_Signer
{
    public static function host($value)
    {
        if (!is_string($value)) { throw new InvalidArgumentException('Invalid CDN host'); }
        $host = preg_replace('#^https://#', '', $value);
        $host = rtrim($host, '/');
        if (!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/D', $host)) {
            throw new InvalidArgumentException('Invalid CDN host');
        }
        return $host;
    }

    public static function path($url, $host)
    {
        if (!is_string($url) || $url === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            throw new InvalidArgumentException('Invalid image URL');
        }
        if (str_starts_with($url, 'https://')) {
            $parts = parse_url($url);
            if (!$parts || ($parts['host'] ?? '') !== $host || isset($parts['port']) ||
                isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
                throw new InvalidArgumentException('Unapproved image origin');
            }
            $path = $parts['path'] ?? '';
        } else { $path = $url; }
        if (!str_starts_with($path, '/') || str_contains($path, '//') || strpbrk($path, '?#') !== false) {
            throw new InvalidArgumentException('Invalid image path');
        }
        foreach (explode('/', substr($path, 1)) as $segment) {
            $decoded = rawurldecode($segment);
            if ($decoded === '' || $decoded === '.' || $decoded === '..' ||
                preg_match('/[\x00-\x1f\x7f\\\\\/%]/', $decoded) || rawurlencode($decoded) !== $segment) {
                throw new InvalidArgumentException('Ambiguous image path');
            }
        }
        if (!preg_match('/\.(?:jpe?g|png|webp|gif|avif)$/Di', $path)) {
            throw new InvalidArgumentException('Unsupported image file');
        }
        return $path;
    }

    public static function sign($url, $host, $key, $expires, array $parameters = [])
    {
        $host = self::host($host);
        $path = self::path($url, $host);
        if (!is_string($key) || $key === '' || !is_int($expires) || $expires <= 0) {
            throw new InvalidArgumentException('Invalid CDN signing settings');
        }
        // No directory grants, ignored parameters, redirects or caller-supplied signature fields.
        foreach ($parameters as $name => $value) {
            if (!in_array($name, ['format', 'quality', 'width', 'height', 'aspect_ratio', 'crop', 'sharpen'], true) ||
                (!is_string($value) && !is_int($value)) || !preg_match('/^[A-Za-z0-9.,:_-]{1,64}$/D', (string)$value)) {
                throw new InvalidArgumentException('Unsupported image parameter');
            }
        }
        ksort($parameters, SORT_STRING);
        $parts = [];
        foreach ($parameters as $name => $value) { $parts[] = $name . '=' . $value; }
        $digest = hash_hmac('sha256', $path . $expires . implode('&', $parts), $key, true);
        $token = 'HS256-' . rtrim(strtr(base64_encode($digest), '+/', '-_'), '=');
        return 'https://' . $host . $path . '?' . http_build_query(
            ['token' => $token] + $parameters + ['expires' => $expires], '', '&', PHP_QUERY_RFC3986
        );
    }
}

/** Recheck age at issuance; only an explicitly authorized native editor may use the admin branch. */
function flexpress_cdn_media_deadline($admin_post_id = 0)
{
    $expires = time() + 300;
    if ($admin_post_id) {
        if (!is_admin() || wp_doing_ajax() || !current_user_can('edit_post', $admin_post_id)) {
            throw new RuntimeException('Editor access required');
        }
        return $expires;
    }
    if (function_exists('flexpress_exclusv_age_enabled') && flexpress_exclusv_age_enabled()) {
        $deadline = flexpress_exclusv_age_media_deadline();
        if (!is_int($deadline) || $deadline <= time()) { throw new RuntimeException('Age access expired'); }
        $expires = min($expires, $deadline);
    }
    return $expires;
}

/** Internal image issuer, using distinct pull-zone keys rather than the iframe token key. */
function flexpress_sign_cdn_image($url, $zone, array $parameters = [], $admin_post_id = 0)
{
    $zones = [
        'stream' => ['bunnycdn_url', 'bunnycdn_cdn_token_key'],
        'gallery' => ['bunnycdn_serve_url', 'bunnycdn_storage_token_key'],
        'static' => ['bunnycdn_static_host', 'bunnycdn_static_token_key'],
    ];
    if (!isset($zones[$zone])) { return ''; }
    $settings = get_option('flexpress_video_settings', []);
    [$host_field, $key_field] = $zones[$zone];
    if (!is_string($settings[$key_field] ?? null) || $settings[$key_field] === '') { return ''; }
    try {
        return FlexPress_Cdn_Signer::sign($url, $settings[$host_field] ?? '', $settings[$key_field] ?? '',
            flexpress_cdn_media_deadline($admin_post_id), $parameters);
    } catch (Throwable $error) { return ''; }
}

function flexpress_stream_image_url($video_id, $filename)
{
    if (!is_string($video_id) || !preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/Di', $video_id) ||
        !is_string($filename) || !preg_match('/^[A-Za-z0-9_-]+\.(?:jpe?g|png|webp|avif)$/Di', $filename)) {
        return '';
    }
    $admin_post_id = 0;
    if (is_admin() && !wp_doing_ajax()) {
        $post_id = get_the_ID();
        $post = get_post($post_id);
        if ($post && in_array($post->post_type, ['episode', 'extras'], true) && current_user_can('edit_post', $post_id)) {
            foreach (['preview_video', 'trailer_video', 'full_video'] as $field) {
                if (get_field($field, $post_id) === $video_id) { $admin_post_id = $post_id; break; }
            }
        }
    }
    return flexpress_sign_cdn_image('/' . $video_id . '/' . $filename, 'stream', [], $admin_post_id);
}
