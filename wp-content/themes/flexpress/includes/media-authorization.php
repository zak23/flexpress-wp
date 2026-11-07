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
