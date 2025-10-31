<?php

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_Permissions_Settings
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_menu'), 20);
        add_action('admin_init', array($this, 'handle_submission'));
    }

    public function register_menu()
    {
        add_submenu_page(
            'flexpress-settings',
            __('Permissions', 'flexpress'),
            __('Permissions', 'flexpress'),
            flexpress_get_founder_capability(),
            'flexpress-permissions-settings',
            array($this, 'render_page')
        );
    }

    public function handle_submission()
    {
        if (!isset($_POST['flexpress_permissions_action']) || $_POST['flexpress_permissions_action'] !== 'update_founders') {
            return;
        }

        check_admin_referer('flexpress_permissions_save', 'flexpress_permissions_nonce');

        if (!flexpress_current_user_is_founder()) {
            flexpress_require_founder_capability();
        }

        $submitted = isset($_POST['founder_ids']) ? (array) $_POST['founder_ids'] : array();
        $submitted = array_map('absint', $submitted);
        $submitted = array_filter($submitted);

        if (empty($submitted)) {
            add_settings_error(
                'flexpress_permissions',
                'founder_required',
                __('At least one founder must remain assigned.', 'flexpress')
            );
            return;
        }

        $current_user_id = get_current_user_id();
        if ($current_user_id && !in_array($current_user_id, $submitted, true)) {
            add_settings_error(
                'flexpress_permissions',
                'cannot_remove_self',
                __('You cannot remove yourself from the founders list. Ask another founder to make that change.', 'flexpress')
            );
            return;
        }

        flexpress_set_founder_user_ids($submitted);

        add_settings_error(
            'flexpress_permissions',
            'founder_saved',
            __('Founder permissions updated successfully.', 'flexpress'),
            'updated'
        );
    }

    public function render_page()
    {
        flexpress_require_founder_capability();

        $current_founders = flexpress_get_founder_user_ids();
        $users            = get_users(
            array(
                'orderby' => 'display_name',
                'order'   => 'ASC',
                'fields'  => array('ID', 'display_name', 'user_email', 'roles'),
            )
        );

        settings_errors('flexpress_permissions');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('FlexPress Permissions', 'flexpress'); ?></h1>
            <p class="description">
                <?php esc_html_e('Founders retain full control over site settings, memberships, and content unlocks. Use this screen to manage which accounts are treated as founders.', 'flexpress'); ?>
            </p>

            <form method="post" action="">
                <?php wp_nonce_field('flexpress_permissions_save', 'flexpress_permissions_nonce'); ?>
                <input type="hidden" name="flexpress_permissions_action" value="update_founders" />

                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-cb check-column">
                                <input type="checkbox" id="flexpress-select-all-founders" />
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('User', 'flexpress'); ?>
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('Email', 'flexpress'); ?>
                            </th>
                            <th scope="col" class="manage-column">
                                <?php esc_html_e('Roles', 'flexpress'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)) : ?>
                            <tr>
                                <td colspan="4">
                                    <?php esc_html_e('No users found.', 'flexpress'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php
                            foreach ($users as $user) :
                                $is_founder = in_array($user->ID, $current_founders, true);

                                $role_labels = array();
                                $roles       = wp_roles();

                                if ($roles instanceof WP_Roles) {
                                    foreach ((array) $user->roles as $role_key) {
                                        $label = $roles->roles[$role_key]['name'] ?? $role_key;
                                        $role_labels[] = translate_user_role($label);
                                    }
                                } else {
                                    $role_labels = (array) $user->roles;
                                }
                                ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="founder_ids[]" value="<?php echo esc_attr($user->ID); ?>" <?php checked($is_founder); ?> />
                                    </th>
                                    <td>
                                        <strong><?php echo esc_html($user->display_name ?: $user->user_email); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td>
                                        <?php echo esc_html(implode(', ', $role_labels)); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Save Founder Assignments', 'flexpress'); ?>
                    </button>
                </p>
            </form>
        </div>

        <script>
            (function () {
                const selectAll = document.getElementById('flexpress-select-all-founders');
                if (!selectAll) {
                    return;
                }

                selectAll.addEventListener('change', function (event) {
                    const checkboxes = document.querySelectorAll('input[name="founder_ids[]"]');
                    checkboxes.forEach(function (checkbox) {
                        checkbox.checked = event.target.checked;
                    });
                });
            })();
        </script>
        <?php
    }
}

if (is_admin()) {
    new FlexPress_Permissions_Settings();
}


