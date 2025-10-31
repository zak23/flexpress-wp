<?php

/**
 * FlexPress Trial Links Settings
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * FlexPress Trial Links Settings Class
 */
class FlexPress_Trial_Links_Settings
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_trial_links_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_create_trial_link', array($this, 'ajax_create_trial_link'));
        add_action('wp_ajax_delete_trial_link', array($this, 'ajax_delete_trial_link'));
        add_action('wp_ajax_update_trial_link', array($this, 'ajax_update_trial_link'));
        add_action('wp_ajax_get_trial_link', array($this, 'ajax_get_trial_link'));
    }

    /**
     * Add the trial links settings page to admin menu
     */
    public function add_trial_links_settings_page()
    {
        add_submenu_page(
            'flexpress-settings',
            __('Trial Links', 'flexpress'),
            __('Trial Links', 'flexpress'),
            'manage_options',
            'flexpress-trial-links-settings',
            array($this, 'render_trial_links_settings_page')
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'flexpress-settings_page_flexpress-trial-links-settings') {
            return;
        }

        wp_enqueue_script('jquery');
    }

    /**
     * Render the trial links settings page
     */
    public function render_trial_links_settings_page()
    {
        // Ensure table exists when page loads
        if (!flexpress_trial_links_table_exists()) {
            flexpress_trial_links_create_table();
        }
        
        $all_trial_links = flexpress_get_all_trial_links();
        
        // Separate links by status
        $active_links = array();
        $used_links = array();
        
        foreach ($all_trial_links as $link) {
            if ($link->use_count >= $link->max_uses) {
                $used_links[] = $link;
            } else {
                $active_links[] = $link;
            }
        }
?>
        <div class="wrap">
            <h1><?php echo esc_html__('Trial Links Management', 'flexpress'); ?></h1>

            <div class="notice notice-info">
                <p><?php esc_html_e('Create and manage free trial links that bypass payment and grant immediate access to users.', 'flexpress'); ?></p>
            </div>

            <div class="trial-links-container">
                <div class="trial-links-header">
                    <button type="button" class="button button-primary" id="add-new-trial-link">
                        <?php esc_html_e('Create New Trial Link', 'flexpress'); ?>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="trial-links-tabs">
                    <nav class="nav-tab-wrapper">
                        <a href="#" class="nav-tab nav-tab-active" data-tab="active">
                            <?php esc_html_e('Active', 'flexpress'); ?>
                            <span class="trial-link-count">(<?php echo count($active_links); ?>)</span>
                        </a>
                        <a href="#" class="nav-tab" data-tab="used">
                            <?php esc_html_e('Used', 'flexpress'); ?>
                            <span class="trial-link-count">(<?php echo count($used_links); ?>)</span>
                        </a>
                        <a href="#" class="nav-tab" data-tab="all">
                            <?php esc_html_e('All', 'flexpress'); ?>
                            <span class="trial-link-count">(<?php echo count($all_trial_links); ?>)</span>
                        </a>
                    </nav>
                </div>

                <div id="trial-links-list">
                    <!-- Active Links Tab -->
                    <div class="trial-links-tab-content" id="tab-active" style="display: block;">
                        <?php if (empty($active_links)): ?>
                            <div class="no-trial-links-message">
                                <p><?php esc_html_e('No active trial links. Click "Create New Trial Link" to get started.', 'flexpress'); ?></p>
                            </div>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Token', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Duration', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Uses', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Expires', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Created', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_links as $link): ?>
                                        <?php $this->render_trial_link_row($link); ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Used Links Tab -->
                    <div class="trial-links-tab-content" id="tab-used" style="display: none;">
                        <?php if (empty($used_links)): ?>
                            <div class="no-trial-links-message">
                                <p><?php esc_html_e('No used trial links yet.', 'flexpress'); ?></p>
                            </div>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Token', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Duration', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Uses', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Expires', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Created', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($used_links as $link): ?>
                                        <?php $this->render_trial_link_row($link); ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- All Links Tab -->
                    <div class="trial-links-tab-content" id="tab-all" style="display: none;">
                        <?php if (empty($all_trial_links)): ?>
                            <div class="no-trial-links-message">
                                <p><?php esc_html_e('No trial links created yet. Click "Create New Trial Link" to get started.', 'flexpress'); ?></p>
                            </div>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Token', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Duration', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Uses', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Status', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Expires', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Created', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_trial_links as $link): ?>
                                        <?php $this->render_trial_link_row($link); ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <div id="trial-link-modal" class="trial-link-modal" style="display: none;">
                <div class="trial-link-modal-content">
                    <div class="trial-link-modal-header">
                        <h2 id="modal-title"><?php esc_html_e('Create Trial Link', 'flexpress'); ?></h2>
                        <span class="trial-link-modal-close">&times;</span>
                    </div>
                    <div class="trial-link-modal-body">
                        <?php $this->render_trial_link_form(); ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .trial-links-container {
                margin-top: 20px;
            }
            .trial-links-header {
                margin-bottom: 20px;
            }
            .trial-links-tabs {
                margin: 20px 0;
            }
            .trial-links-tabs .nav-tab-wrapper {
                border-bottom: 1px solid #ccc;
                margin-bottom: 0;
            }
            .trial-links-tabs .nav-tab {
                position: relative;
            }
            .trial-link-count {
                font-size: 11px;
                font-weight: normal;
                opacity: 0.7;
                margin-left: 4px;
            }
            .trial-links-tab-content {
                margin-top: 0;
            }
            .trial-links-tab-content .no-trial-links-message {
                padding: 40px 20px;
                text-align: center;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-top: none;
                color: #666;
            }
            .trial-link-modal {
                display: none;
                position: fixed;
                z-index: 100000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
            }
            .trial-link-modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 0;
                border: 1px solid #888;
                width: 80%;
                max-width: 600px;
                border-radius: 4px;
            }
            .trial-link-modal-header {
                padding: 20px;
                background: #23282d;
                color: #fff;
                border-radius: 4px 4px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .trial-link-modal-header h2 {
                margin: 0;
            }
            .trial-link-modal-close {
                color: #aaa;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            .trial-link-modal-close:hover {
                color: #fff;
            }
            .trial-link-modal-body {
                padding: 20px;
            }
            .trial-link-row-actions {
                display: flex;
                gap: 5px;
                flex-wrap: wrap;
            }
            .trial-link-details-row {
                background: #f9f9f9;
            }
            .trial-link-details-row td {
                padding: 20px;
                border-top: 2px solid #ddd;
            }
            .trial-link-details-content {
                background: #fff;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .trial-link-details-content h4 {
                margin: 0 0 15px 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #ddd;
            }
            .trial-link-details-content table {
                margin-top: 10px;
            }
            .trial-link-details-content table th {
                font-weight: 600;
                padding: 10px;
                background: #f5f5f5;
            }
            .trial-link-details-content table td {
                padding: 10px;
                vertical-align: top;
            }
            .status-used {
                color: #666;
                font-style: italic;
            }
            .status-active {
                color: #46b450;
            }
            .status-inactive {
                color: #dc3232;
            }
            .status-expired {
                color: #f56e28;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.trial-links-tabs .nav-tab').on('click', function(e) {
                e.preventDefault();
                
                var tab = $(this).data('tab');
                
                // Update active tab
                $('.trial-links-tabs .nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show/hide tab content
                $('.trial-links-tab-content').hide();
                $('#tab-' + tab).show();
            });

            // Open modal
            $('#add-new-trial-link').on('click', function() {
                $('#modal-title').text('Create Trial Link');
                $('#trial-link-form').attr('data-action', 'create');
                $('#trial-link-form')[0].reset();
                $('#trial-link-modal').show();
            });

            // Close modal
            $('.trial-link-modal-close').on('click', function() {
                $('#trial-link-modal').hide();
            });

            // Submit form
            $('#trial-link-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'create_trial_link',
                    nonce: '<?php echo wp_create_nonce('flexpress_trial_links'); ?>',
                    plan_id: $('#trial-plan-id').val(),
                    duration: $('#trial-duration').val(),
                    expires_at: $('#trial-expires-at').val(),
                    max_uses: $('#trial-max-uses').val(),
                    notes: $('#trial-notes').val()
                };

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to create trial link'));
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });

            // Copy link
            $(document).on('click', '.copy-trial-link', function(e) {
                e.preventDefault();
                var url = $(this).data('url');
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(url).select();
                document.execCommand('copy');
                $temp.remove();
                alert('Trial link copied to clipboard!');
            });

            // Delete link
            $(document).on('click', '.delete-trial-link', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to delete this trial link?')) {
                    return;
                }
                
                var linkId = $(this).data('id');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'delete_trial_link',
                        nonce: '<?php echo wp_create_nonce('flexpress_trial_links'); ?>',
                        link_id: linkId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to delete trial link'));
                        }
                    }
                });
            });

            // Toggle active status
            $(document).on('click', '.toggle-trial-link-status', function(e) {
                e.preventDefault();
                var linkId = $(this).data('id');
                var currentStatus = $(this).data('status');
                var newStatus = currentStatus ? 0 : 1;
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_trial_link',
                        nonce: '<?php echo wp_create_nonce('flexpress_trial_links'); ?>',
                        link_id: linkId,
                        is_active: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to update trial link'));
                        }
                    }
                });
            });

            // Toggle details row
            $(document).on('click', '.show-trial-link-details', function(e) {
                e.preventDefault();
                var linkId = $(this).data('link-id');
                var $detailsRow = $('#trial-link-details-' + linkId);
                var $detailsText = $(this).find('.details-text');
                var $hideDetailsText = $(this).find('.hide-details-text');
                
                if ($detailsRow.is(':visible')) {
                    $detailsRow.slideUp();
                    $detailsText.show();
                    $hideDetailsText.hide();
                } else {
                    $detailsRow.slideDown();
                    $detailsText.hide();
                    $hideDetailsText.show();
                }
            });
        });
        </script>
<?php
    }

    /**
     * Get users who used a specific trial link
     * 
     * @param int $trial_link_id Trial link ID
     * @return array Array of user objects
     */
    private function get_trial_link_users($trial_link_id)
    {
        $users = get_users(array(
            'meta_key' => 'trial_link_id',
            'meta_value' => $trial_link_id,
            'number' => -1,
            'orderby' => 'registered',
            'order' => 'DESC'
        ));
        
        return $users;
    }

    /**
     * Render trial link row
     */
    private function render_trial_link_row($link)
    {
        $trial_url = flexpress_get_trial_link_url($link->token);
        $is_expired = !empty($link->expires_at) && strtotime($link->expires_at) < current_time('timestamp');
        $is_used = $link->use_count >= $link->max_uses;
        
        // Determine status
        if ($is_used) {
            $status_class = 'used';
            $status_text = 'Used';
        } elseif (!$link->is_active) {
            $status_class = 'inactive';
            $status_text = 'Inactive';
        } elseif ($is_expired) {
            $status_class = 'expired';
            $status_text = 'Expired';
        } else {
            $status_class = 'active';
            $status_text = 'Active';
        }
?>
        <?php $users = $this->get_trial_link_users($link->id); ?>
        <tr class="trial-link-main-row"<?php echo $is_used ? ' style="opacity: 0.7;"' : ''; ?> data-link-id="<?php echo esc_attr($link->id); ?>">
            <td>
                <code style="font-size: 11px;"><?php echo esc_html(substr($link->token, 0, 16) . '...'); ?></code>
            </td>
            <td><?php echo esc_html($link->duration); ?> days</td>
            <td>
                <strong><?php echo esc_html($link->use_count); ?></strong> / <?php echo esc_html($link->max_uses); ?>
                <?php if ($is_used): ?>
                    <span class="status-used" style="margin-left: 5px;">✓</span>
                <?php endif; ?>
            </td>
            <td>
                <span class="status-<?php echo esc_attr($status_class); ?>">
                    <?php echo esc_html($status_text); ?>
                </span>
            </td>
            <td>
                <?php 
                if ($link->expires_at) {
                    echo esc_html(date('M j, Y', strtotime($link->expires_at)));
                } else {
                    echo '—';
                }
                ?>
            </td>
            <td><?php echo esc_html(date('M j, Y', strtotime($link->created_at))); ?></td>
            <td>
                <div class="trial-link-row-actions">
                    <button type="button" class="button button-small copy-trial-link" data-url="<?php echo esc_url($trial_url); ?>">
                        Copy Link
                    </button>
                    <?php if ($link->use_count > 0): ?>
                        <button type="button" class="button button-small show-trial-link-details" data-link-id="<?php echo esc_attr($link->id); ?>">
                            <span class="details-text">Show Details</span>
                            <span class="hide-details-text" style="display: none;">Hide Details</span>
                        </button>
                    <?php endif; ?>
                    <?php if (!$is_used): ?>
                        <button type="button" class="button button-small toggle-trial-link-status" data-id="<?php echo esc_attr($link->id); ?>" data-status="<?php echo esc_attr($link->is_active); ?>">
                            <?php echo $link->is_active ? 'Deactivate' : 'Activate'; ?>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="button button-small delete-trial-link" data-id="<?php echo esc_attr($link->id); ?>">
                        Delete
                    </button>
                </div>
            </td>
        </tr>
        <?php if ($link->use_count > 0): ?>
            <tr class="trial-link-details-row" id="trial-link-details-<?php echo esc_attr($link->id); ?>" style="display: none;">
                <td colspan="7">
                    <div class="trial-link-details-content">
                        <h4><?php esc_html_e('Members Who Used This Link', 'flexpress'); ?></h4>
                        <?php if (empty($users)): ?>
                            <p style="color: #666; font-style: italic;"><?php esc_html_e('No member information found for this link.', 'flexpress'); ?></p>
                        <?php else: ?>
                            <table class="widefat" style="margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Name', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Email', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Registered', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Trial Expires', 'flexpress'); ?></th>
                                        <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): 
                                        $trial_expires_at = get_user_meta($user->ID, 'trial_expires_at', true);
                                        $display_name = $user->display_name ?: $user->user_login;
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($display_name); ?></strong>
                                                <br><small style="color: #666;"><?php echo esc_html($user->user_login); ?></small>
                                            </td>
                                            <td><?php echo esc_html($user->user_email); ?></td>
                                            <td><?php echo esc_html(date('M j, Y g:i a', strtotime($user->user_registered))); ?></td>
                                            <td>
                                                <?php 
                                                if ($trial_expires_at) {
                                                    $expires_timestamp = strtotime($trial_expires_at);
                                                    $is_trial_expired = $expires_timestamp < current_time('timestamp');
                                                    echo '<span class="' . ($is_trial_expired ? 'status-expired' : 'status-active') . '">';
                                                    echo esc_html(date('M j, Y g:i a', $expires_timestamp));
                                                    echo '</span>';
                                                } else {
                                                    echo '—';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=flexpress-manage-members&edit_user=' . $user->ID)); ?>" class="button button-small">
                                                    <?php esc_html_e('Edit Member', 'flexpress'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
<?php
    }

    /**
     * Render trial link form
     */
    private function render_trial_link_form()
    {
?>
        <form id="trial-link-form">
            <table class="form-table">
                <tr>
                    <th><label for="trial-duration"><?php esc_html_e('Duration (days)', 'flexpress'); ?></label></th>
                    <td>
                        <input type="number" id="trial-duration" name="duration" value="7" min="1" max="365" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="trial-max-uses"><?php esc_html_e('Max Uses', 'flexpress'); ?></label></th>
                    <td>
                        <input type="number" id="trial-max-uses" name="max_uses" value="1" min="1" required>
                        <p class="description"><?php esc_html_e('Number of times this link can be used (1 = single use)', 'flexpress'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="trial-expires-at"><?php esc_html_e('Link Expires', 'flexpress'); ?></label></th>
                    <td>
                        <input type="datetime-local" id="trial-expires-at" name="expires_at">
                        <p class="description"><?php esc_html_e('Optional: Set when this trial link expires (leave blank for no expiration)', 'flexpress'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="trial-notes"><?php esc_html_e('Notes', 'flexpress'); ?></label></th>
                    <td>
                        <textarea id="trial-notes" name="notes" rows="3" class="large-text"></textarea>
                        <p class="description"><?php esc_html_e('Optional: Internal notes about this trial link', 'flexpress'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php esc_html_e('Create Trial Link', 'flexpress'); ?></button>
                <button type="button" class="button trial-link-modal-close"><?php esc_html_e('Cancel', 'flexpress'); ?></button>
            </p>
        </form>
<?php
    }

    /**
     * AJAX handler for creating trial link
     */
    public function ajax_create_trial_link()
    {
        try {
            check_ajax_referer('flexpress_trial_links', 'nonce');

            if (!flexpress_current_user_is_founder()) {
                wp_send_json_error(array('message' => 'Insufficient permissions'));
                return;
            }

            // Ensure required functions exist
            if (!function_exists('flexpress_trial_links_table_exists')) {
                wp_send_json_error(array('message' => 'Trial links functions not loaded. Please refresh the page.'));
                return;
            }

            $duration = intval($_POST['duration'] ?? 7);
            $max_uses = intval($_POST['max_uses'] ?? 1);
            $expires_at = !empty($_POST['expires_at']) ? sanitize_text_field($_POST['expires_at']) : null;
            $notes = sanitize_textarea_field($_POST['notes'] ?? '');

            // Ensure table exists - force create if needed
            if (!flexpress_trial_links_table_exists()) {
                if (!function_exists('flexpress_trial_links_create_table')) {
                    wp_send_json_error(array('message' => 'Table creation function not available. Please refresh the page.'));
                    return;
                }
                flexpress_trial_links_create_table();
                // Double check after creation
                if (!flexpress_trial_links_table_exists()) {
                    wp_send_json_error(array('message' => 'Failed to create database table. Please check WordPress debug log.'));
                    return;
                }
            }

            if (!function_exists('flexpress_create_trial_link')) {
                wp_send_json_error(array('message' => 'Trial link creation function not available. Please refresh the page.'));
                return;
            }

            $link_id = flexpress_create_trial_link(array(
                'duration' => $duration,
                'max_uses' => $max_uses,
                'expires_at' => $expires_at,
                'notes' => $notes
            ));

            if ($link_id) {
                // Log activity
                if (class_exists('FlexPress_Activity_Logger')) {
                    FlexPress_Activity_Logger::log_activity(get_current_user_id(), 'trial_link_created', sprintf(
                        'Trial link created: Duration %d days',
                        $duration
                    ));
                }

                wp_send_json_success(array('message' => 'Trial link created successfully', 'link_id' => $link_id));
            } else {
                global $wpdb;
                $error_message = 'Failed to create trial link';
                if ($wpdb->last_error) {
                    $error_message .= ': ' . $wpdb->last_error;
                }
                wp_send_json_error(array('message' => $error_message));
            }
        } catch (Exception $e) {
            error_log('FlexPress Trial Links AJAX Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'An error occurred: ' . $e->getMessage()));
        } catch (Error $e) {
            error_log('FlexPress Trial Links AJAX Fatal Error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'A fatal error occurred: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for deleting trial link
     */
    public function ajax_delete_trial_link()
    {
        check_ajax_referer('flexpress_trial_links', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $link_id = intval($_POST['link_id'] ?? 0);

        if (flexpress_delete_trial_link($link_id)) {
            wp_send_json_success(array('message' => 'Trial link deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete trial link'));
        }
    }

    /**
     * AJAX handler for updating trial link
     */
    public function ajax_update_trial_link()
    {
        check_ajax_referer('flexpress_trial_links', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $link_id = intval($_POST['link_id'] ?? 0);
        $update_data = array();

        if (isset($_POST['is_active'])) {
            $update_data['is_active'] = intval($_POST['is_active']);
        }

        if (flexpress_update_trial_link($link_id, $update_data)) {
            wp_send_json_success(array('message' => 'Trial link updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update trial link'));
        }
    }

    /**
     * AJAX handler for getting trial link
     */
    public function ajax_get_trial_link()
    {
        check_ajax_referer('flexpress_trial_links', 'nonce');

        if (!flexpress_current_user_is_founder()) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $token = sanitize_text_field($_POST['token'] ?? '');
        $trial_link = flexpress_get_trial_link($token);

        if ($trial_link) {
            wp_send_json_success(array('trial_link' => $trial_link));
        } else {
            wp_send_json_error(array('message' => 'Trial link not found'));
        }
    }
}

// Initialize the trial links settings
new FlexPress_Trial_Links_Settings();

