<?php

/** Shared content authorization and bounded Bunny Stream embed issuance. */
if (!defined('ABSPATH')) {
    exit;
}

function flexpress_authorize_content_video($post_id, $video_id, $role)
{
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, ['episode', 'extras'], true) || $post->post_status !== 'publish' ||
        !empty($post->post_password) || !is_string($video_id) ||
        !preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/Di', $video_id)) {
        return false;
    }
    if ($post->post_type === 'episode' && !get_current_user_id() && get_field('hidden_from_public', $post_id)) {
        return false;
    }
    if (!in_array($role, ['preview', 'trailer', 'full'], true) ||
        ($role === 'trailer' && $post->post_type !== 'episode')) {
        return false;
    }
    $configured = get_field($role . '_video', $post_id);
    if (!is_string($configured) || !hash_equals($configured, $video_id)) {
        return false;
    }
    // A preview/trailer alias must still satisfy the full video's paid/release policy.
    if ($role === 'full' || get_field('full_video', $post_id) === $video_id) {
        $user_id = get_current_user_id();
        if ($user_id) {
            wp_cache_delete($user_id, 'user_meta');
            if (flexpress_get_membership_status($user_id) === 'banned' && !flexpress_user_is_founder($user_id)) {
                return false;
            }
        }
        $access = $post->post_type === 'episode'
            ? flexpress_check_episode_access($post_id, $user_id)
            : flexpress_check_extras_access($post_id, $user_id);
        if (empty($access['has_access'])) {
            return false;
        }
    }
    return true;
}

/** The AJAX card API remains episode-only. */
function flexpress_authorize_preview_video($post_id, $video_id)
{
    $post = get_post($post_id);
    return $post && $post->post_type === 'episode' &&
        flexpress_authorize_content_video($post_id, $video_id, 'preview');
}

/** Internal signer: callers must first bind the ID to authorized content or site configuration. */
function flexpress_issue_bunny_embed_token($video_id)
{
    if (!is_string($video_id) || !preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/Di', $video_id)) {
        throw new RuntimeException('Video unavailable', 403);
    }
    $settings = get_option('flexpress_video_settings', []);
    $library = $settings['bunnycdn_library_id'] ?? '';
    $key = $settings['bunnycdn_token_key'] ?? '';
    if ((!is_string($library) && !is_int($library)) ||
        !preg_match('/^[1-9][0-9]*$/D', (string) $library) || !is_string($key) || $key === '') {
        throw new RuntimeException('Video configuration unavailable', 503);
    }
    $expires = time() + 300;
    if (function_exists('flexpress_exclusv_age_enabled') && flexpress_exclusv_age_enabled()) {
        try {
            $deadline = flexpress_exclusv_age_media_deadline();
            if (!is_int($deadline) || $deadline <= time()) {
                throw new RuntimeException('Expired assurance');
            }
            $expires = min($expires, $deadline);
        } catch (Throwable $error) {
            throw new RuntimeException('Age access required', 403);
        }
    }
    return [
        'token' => hash('sha256', $key . $video_id . $expires),
        'expires' => $expires,
        'libraryId' => (string) $library,
    ];
}

/** Internal formatter; only fixed boolean player options may be appended. */
function flexpress_format_bunny_embed_url($video_id, array $credentials, array $options)
{
    $query = ['token' => $credentials['token'], 'expires' => $credentials['expires']];
    foreach (['autoplay' => true, 'loop' => true, 'muted' => true, 'controls' => false] as $key => $default) {
        $query[$key] = ($options[$key] ?? $default) === true ? 'true' : 'false';
    }
    return 'https://iframe.mediadelivery.net/embed/' . $credentials['libraryId'] . '/' . $video_id .
        '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

/** Never accepts a caller-supplied video ID or an entitlement flag. */
function flexpress_get_content_video_embed_url($post_id, $role, array $options = [])
{
    if (!in_array($role, ['preview', 'trailer', 'full'], true)) {
        return '';
    }
    $video_id = get_field($role . '_video', $post_id);
    if (!flexpress_authorize_content_video($post_id, $video_id, $role)) {
        return '';
    }
    try {
        return flexpress_format_bunny_embed_url($video_id, flexpress_issue_bunny_embed_token($video_id), $options);
    } catch (Throwable $error) {
        return '';
    }
}

/** The dedicated marketing clip is explicitly published through the site's landing-page settings. */
function flexpress_get_coming_soon_video_embed_url()
{
    $settings = get_option('flexpress_general_settings', []);
    if (empty($settings['coming_soon_enabled'])) {
        return '';
    }
    $video_id = $settings['coming_soon_video_url'] ?? '';
    try {
        return flexpress_format_bunny_embed_url($video_id, flexpress_issue_bunny_embed_token($video_id), ['muted' => false]);
    } catch (Throwable $error) {
        return '';
    }
}

/** Gallery previews are the first five sorted images; only the first four open when more are locked. */
function flexpress_get_authorized_gallery_image_url($post_id, $index, $size = 'thumbnail', $admin = false)
{
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, ['episode', 'extras'], true) || !is_int($index) || $index < 0 ||
        !in_array($size, ['thumbnail', 'medium', 'large', 'full'], true)) { return ''; }
    if ($admin) {
        if (!is_admin() || wp_doing_ajax() || !current_user_can('edit_post', $post_id)) { return ''; }
    } elseif ($post->post_status !== 'publish' || !empty($post->post_password) ||
        ($post->post_type === 'episode' && !get_current_user_id() && get_field('hidden_from_public', $post_id))) {
        return '';
    }
    $images = $post->post_type === 'episode' ? flexpress_get_episode_gallery($post_id) : flexpress_get_extras_gallery($post_id);
    if (!is_array($images) || count(array_filter($images, 'is_array')) !== count($images)) { return ''; }
    $images = flexpress_sort_gallery_images_by_filename($images);
    if (!isset($images[$index])) { return ''; }
    $user_id = get_current_user_id();
    if ($user_id) { wp_cache_delete($user_id, 'user_meta'); }
    $banned = $user_id && !flexpress_user_is_founder($user_id) && flexpress_get_membership_status($user_id) === 'banned';
    if ($banned && !$admin) { return ''; }
    $access = $post->post_type === 'episode' ? flexpress_check_episode_access($post_id, $user_id) : flexpress_check_extras_access($post_id, $user_id);
    if (!$admin && empty($access['has_access']) && ($index >= 5 ||
        (count($images) > 5 && $index === 4 && in_array($size, ['large', 'full'], true)))) { return ''; }
    $image = $images[$index];
    $small = in_array($size, ['thumbnail', 'medium'], true);
    if (!$admin && empty($access['has_access']) && count($images) > 5 && $index === 4 && $small &&
        (empty($image['bunnycdn_thumbnail_url']) || $image['bunnycdn_thumbnail_url'] === ($image['bunnycdn_url'] ?? ''))) {
        // Never expose the fifth full image through a missing or aliased thumbnail.
        return '';
    }
    $url = $small && !empty($image['bunnycdn_thumbnail_url']) ? $image['bunnycdn_thumbnail_url'] : ($image['bunnycdn_url'] ?? '');
    if ($url !== '') { return flexpress_sign_cdn_image($url, 'gallery', [], $admin ? $post_id : 0); }
    // Native uploads require their separate signed static delivery path as well.
    return flexpress_get_bunnycdn_optimized_image_url($image[$size] ?? '', [], $admin ? $post_id : 0);
}

/** Compatibility helper: match the exact stored asset; never sign an arbitrary supplied URL. */
function flexpress_get_bound_gallery_url($file_path, $post_id)
{
    $post = get_post($post_id);
    if (!$post || !in_array($post->post_type, ['episode', 'extras'], true)) { return ''; }
    $images = $post->post_type === 'episode' ? flexpress_get_episode_gallery($post_id) : flexpress_get_extras_gallery($post_id);
    if (!is_array($images) || count(array_filter($images, 'is_array')) !== count($images)) { return ''; }
    $images = flexpress_sort_gallery_images_by_filename($images);
    foreach ($images as $index => $image) {
        foreach (['bunnycdn_url' => 'full', 'bunnycdn_thumbnail_url' => 'thumbnail'] as $key => $size) {
            if (is_string($file_path) && $file_path !== '' && ($image[$key] ?? null) === $file_path) {
                return flexpress_get_authorized_gallery_image_url($post_id, $index, $size, is_admin() && !wp_doing_ajax());
            }
        }
    }
    return '';
}
