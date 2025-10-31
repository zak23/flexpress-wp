<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('FLEXPRESS_FOUNDER_CAPABILITY')) {
    define('FLEXPRESS_FOUNDER_CAPABILITY', 'manage_flexpress_founder_settings');
}

/**
 * Get the capability string used to identify FlexPress founders.
 *
 * @return string
 */
function flexpress_get_founder_capability()
{
    return FLEXPRESS_FOUNDER_CAPABILITY;
}

/**
 * Ensure the founder option exists and is populated with administrators the first time.
 * This prevents accidental lock-out when the feature is introduced.
 */
function flexpress_bootstrap_founder_option()
{
    $stored = get_option('flexpress_founder_user_ids', null);
    if ($stored !== null) {
        return;
    }

    $administrators = get_users(
        array(
            'role'   => 'administrator',
            'fields' => 'ID',
        )
    );

    $admin_ids = array_map('absint', $administrators);
    update_option('flexpress_founder_user_ids', $admin_ids, false);
}
add_action('after_setup_theme', 'flexpress_bootstrap_founder_option', 5);

/**
 * Retrieve the list of user IDs flagged as founders.
 *
 * @return int[]
 */
function flexpress_get_founder_user_ids()
{
    $ids = get_option('flexpress_founder_user_ids', array());
    if (!is_array($ids)) {
        return array();
    }

    $ids = array_map('absint', array_unique($ids));

    return array_values(array_filter($ids));
}

/**
 * Persist a sanitized list of founder user IDs and sync capabilities.
 *
 * @param int[] $user_ids
 */
function flexpress_set_founder_user_ids($user_ids)
{
    if (!is_array($user_ids)) {
        $user_ids = array();
    }

    $user_ids = array_map('absint', array_unique($user_ids));

    update_option('flexpress_founder_user_ids', array_values(array_filter($user_ids)), false);

    // After updating the option, ensure capabilities are in sync
    flexpress_rebuild_founder_capabilities();
}

/**
 * Assign the founder capability to a specific user if required.
 *
 * @param int $user_id
 */
function flexpress_grant_founder_capability($user_id)
{
    $user = $user_id ? get_user_by('ID', $user_id) : false;
    if (!$user instanceof WP_User) {
        return;
    }

    $capability = flexpress_get_founder_capability();

    if (!$user->has_cap($capability)) {
        $user->add_cap($capability);
    }
}

/**
 * Remove the founder capability from a user if present.
 *
 * @param int $user_id
 */
function flexpress_revoke_founder_capability($user_id)
{
    $user = $user_id ? get_user_by('ID', $user_id) : false;
    if (!$user instanceof WP_User) {
        return;
    }

    $capability = flexpress_get_founder_capability();

    if ($user->has_cap($capability)) {
        $user->remove_cap($capability);
    }
}

/**
 * Sync founder capability assignments on each request.
 */
function flexpress_sync_founder_capabilities()
{
    $capability = flexpress_get_founder_capability();
    $founders   = flexpress_get_founder_user_ids();

    if (empty($founders)) {
        return;
    }

    foreach ($founders as $user_id) {
        flexpress_grant_founder_capability($user_id);
    }
}
add_action('init', 'flexpress_sync_founder_capabilities', 12);

/**
 * Revoke founder caps from users no longer listed and assign to the current list.
 */
function flexpress_rebuild_founder_capabilities()
{
    $capability = flexpress_get_founder_capability();
    $current    = flexpress_get_founder_user_ids();

    // Remove capability from users who no longer qualify
    $users_with_cap = get_users(
        array(
            'capability' => $capability,
            'fields'     => 'ID',
        )
    );

    foreach ($users_with_cap as $user_id) {
        if (!in_array((int) $user_id, $current, true)) {
            flexpress_revoke_founder_capability((int) $user_id);
        }
    }

    // Ensure current founders have the capability
    foreach ($current as $user_id) {
        flexpress_grant_founder_capability((int) $user_id);
    }
}

/**
 * Check whether the provided (or current) user is a FlexPress founder.
 *
 * @param int|null $user_id
 * @return bool
 */
function flexpress_user_is_founder($user_id = null)
{
    if ($user_id === null) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    $capability = flexpress_get_founder_capability();
    if (user_can($user_id, $capability)) {
        return true;
    }

    $founders = flexpress_get_founder_user_ids();
    if (empty($founders)) {
        return flexpress_is_founder_entity($user_id);
    }

    return in_array((int) $user_id, $founders, true);
}

/**
 * Evaluate whether a mixed user reference should be treated as founder.
 *
 * @param mixed $user
 * @return bool
 */
function flexpress_is_founder_entity($user)
{
    if ($user instanceof WP_User) {
        return flexpress_user_is_founder($user->ID);
    }

    if (is_numeric($user)) {
        return flexpress_user_is_founder((int) $user);
    }

    return false;
}

/**
 * Convenience wrapper for current user founder check.
 *
 * @return bool
 */
function flexpress_current_user_is_founder()
{
    return flexpress_user_is_founder(get_current_user_id());
}

/**
 * Ensure the current user has founder access or trigger wp_die.
 */
function flexpress_require_founder_capability()
{
    if (flexpress_current_user_is_founder()) {
        return;
    }

    wp_die(
        esc_html__('You do not have permission to access this resource.', 'flexpress'),
        esc_html__('Permission Denied', 'flexpress'),
        array('response' => 403)
    );
}


