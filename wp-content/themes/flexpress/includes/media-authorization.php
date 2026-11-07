<?php

/** Content binding for the card-preview signing endpoint; never signs arbitrary library IDs. */
if (!defined('ABSPATH')) {
    exit;
}

function flexpress_authorize_preview_video($post_id, $video_id)
{
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'episode' || $post->post_status !== 'publish' ||
        !empty($post->post_password) || !is_string($video_id) ||
        !preg_match('/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/Di', $video_id)) {
        return false;
    }
    $preview = get_field('preview_video', $post_id);
    if (!is_string($preview) || !hash_equals($preview, $video_id)) {
        return false;
    }
    // A mistaken preview/full-field alias must not turn this endpoint into a paid-content bypass.
    if (get_field('full_video', $post_id) === $video_id) {
        $user_id = get_current_user_id();
        if ($user_id) {
            wp_cache_delete($user_id, 'user_meta');
            if (flexpress_get_membership_status($user_id) === 'banned' && !flexpress_user_is_founder($user_id)) {
                return false;
            }
        }
        $access = flexpress_check_episode_access($post_id, $user_id);
        if (empty($access['has_access'])) {
            return false;
        }
    }
    return true;
}
