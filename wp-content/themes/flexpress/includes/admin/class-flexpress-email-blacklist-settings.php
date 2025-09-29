<?php
/**
 * FlexPress Email Blacklist Admin Settings
 * 
 * Admin interface for managing email blacklist
 * 
 * @package FlexPress
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class FlexPress_Email_Blacklist_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flexpress-settings',
            __('Email Blacklist', 'flexpress'),
            __('Email Blacklist', 'flexpress'),
            'manage_options',
            'flexpress-email-blacklist',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'flexpress-settings_page_flexpress-email-blacklist') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_admin_js());
        wp_add_inline_style('wp-admin', $this->get_admin_css());
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Email Blacklist Management', 'flexpress'); ?></h1>
            
            <div class="flexpress-blacklist-admin">
                <div class="blacklist-stats">
                    <?php
                    $blacklist = FlexPress_Email_Blacklist::get_blacklist();
                    $total_blacklisted = count($blacklist);
                    ?>
                    <div class="stat-box">
                        <h3><?php esc_html_e('Total Blacklisted Emails', 'flexpress'); ?></h3>
                        <span class="stat-number"><?php echo esc_html($total_blacklisted); ?></span>
                    </div>
                </div>
                
                <div class="blacklist-actions">
                    <h2><?php esc_html_e('Add Email to Blacklist', 'flexpress'); ?></h2>
                    <form id="add-blacklist-form">
                        <?php wp_nonce_field('flexpress_blacklist_nonce', 'blacklist_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="blacklist_email"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <input type="email" id="blacklist_email" name="email" class="regular-text" required />
                                    <p class="description"><?php esc_html_e('Enter the email address to blacklist.', 'flexpress'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="blacklist_reason"><?php esc_html_e('Reason', 'flexpress'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="blacklist_reason" name="reason" class="regular-text" />
                                    <p class="description"><?php esc_html_e('Reason for blacklisting (optional).', 'flexpress'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php esc_attr_e('Add to Blacklist', 'flexpress'); ?>" />
                        </p>
                    </form>
                </div>
                
                <div class="blacklist-list">
                    <h2><?php esc_html_e('Blacklisted Emails', 'flexpress'); ?></h2>
                    <div id="blacklist-table-container">
                        <?php $this->render_blacklist_table($blacklist); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render blacklist table
     */
    private function render_blacklist_table($blacklist) {
        if (empty($blacklist)) {
            echo '<p>' . esc_html__('No emails are currently blacklisted.', 'flexpress') . '</p>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Email Address', 'flexpress'); ?></th>
                    <th><?php esc_html_e('Reason', 'flexpress'); ?></th>
                    <th><?php esc_html_e('Date Added', 'flexpress'); ?></th>
                    <th><?php esc_html_e('Added By', 'flexpress'); ?></th>
                    <th><?php esc_html_e('Actions', 'flexpress'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blacklist as $email => $data): ?>
                <tr data-email="<?php echo esc_attr($email); ?>">
                    <td><?php echo esc_html($email); ?></td>
                    <td><?php echo esc_html($data['reason'] ?: 'Not specified'); ?></td>
                    <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($data['date_added']))); ?></td>
                    <td>
                        <?php
                        if ($data['added_by'] === 'system') {
                            echo '<span class="system-badge">' . esc_html__('System', 'flexpress') . '</span>';
                        } else {
                            $user = get_userdata($data['added_by']);
                            echo $user ? esc_html($user->display_name) : esc_html__('Unknown', 'flexpress');
                        }
                        ?>
                    </td>
                    <td>
                        <button type="button" class="button remove-blacklist-btn" data-email="<?php echo esc_attr($email); ?>">
                            <?php esc_html_e('Remove', 'flexpress'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * Get admin JavaScript
     */
    private function get_admin_js() {
        return "
        jQuery(document).ready(function($) {
            // Add to blacklist
            $('#add-blacklist-form').on('submit', function(e) {
                e.preventDefault();
                
                var email = $('#blacklist_email').val();
                var reason = $('#blacklist_reason').val();
                var nonce = $('#blacklist_nonce').val();
                
                if (!email) {
                    alert('Please enter an email address.');
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flexpress_add_to_blacklist',
                        email: email,
                        reason: reason,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Email added to blacklist successfully.');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while adding the email to blacklist.');
                    }
                });
            });
            
            // Remove from blacklist
            $('.remove-blacklist-btn').on('click', function() {
                if (!confirm('Are you sure you want to remove this email from the blacklist?')) {
                    return;
                }
                
                var email = $(this).data('email');
                var nonce = $('#blacklist_nonce').val();
                var row = $(this).closest('tr');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'flexpress_remove_from_blacklist',
                        email: email,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            row.fadeOut(function() {
                                row.remove();
                            });
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while removing the email from blacklist.');
                    }
                });
            });
        });
        ";
    }
    
    /**
     * Get admin CSS
     */
    private function get_admin_css() {
        return "
        .flexpress-blacklist-admin {
            max-width: 1200px;
        }
        
        .blacklist-stats {
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            display: inline-block;
            margin-right: 20px;
            min-width: 200px;
        }
        
        .stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #d63638;
        }
        
        .blacklist-actions {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .blacklist-list {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .system-badge {
            background: #f0f0f1;
            color: #50575e;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .remove-blacklist-btn {
            color: #d63638;
            border-color: #d63638;
        }
        
        .remove-blacklist-btn:hover {
            background: #d63638;
            color: #fff;
        }
        ";
    }
}

// Initialize the admin settings
new FlexPress_Email_Blacklist_Settings();
