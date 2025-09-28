<?php
/**
 * FlexPress Plunk Frontend Integration
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Get Plunk settings
 */
function flexpress_get_plunk_settings() {
    return get_option('flexpress_plunk_settings', array());
}

/**
 * Check if Plunk is enabled
 */
function flexpress_is_plunk_enabled() {
    $settings = flexpress_get_plunk_settings();
    return !empty($settings['public_api_key']) && !empty($settings['secret_api_key']) && !empty($settings['install_url']);
}

/**
 * Get Plunk public API key for frontend use
 */
function flexpress_get_plunk_public_api_key() {
    $settings = flexpress_get_plunk_settings();
    return $settings['public_api_key'] ?? '';
}

/**
 * Check if newsletter modal should be shown
 */
function flexpress_should_show_newsletter_modal() {
    $settings = flexpress_get_plunk_settings();
    return flexpress_is_plunk_enabled() && !empty($settings['enable_newsletter_modal']);
}

/**
 * Get newsletter modal delay
 */
function flexpress_get_newsletter_modal_delay() {
    $settings = flexpress_get_plunk_settings();
    return absint($settings['modal_delay'] ?? 5);
}

/**
 * Render newsletter modal
 */
function flexpress_render_newsletter_modal() {
    if (!flexpress_should_show_newsletter_modal()) {
        return;
    }
    
    $modal_delay = flexpress_get_newsletter_modal_delay();
    ?>
    <!-- Newsletter Modal -->
    <div class="modal fade" id="newsletterModal" tabindex="-1" aria-labelledby="newsletterModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content newsletter-modal">
                <div class="modal-body text-center">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    
                    <h3 class="modal-title mb-3" id="newsletterModalLabel">Never Miss an Episode!</h3>
                    <p class="mb-4">Subscribe to our newsletter and be the first to know when new content drops!</p>

                    <form class="newsletter-form" id="newsletterForm">
                        <!-- Honeypot field -->
                        <div class="d-none">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <input type="email" class="form-control" id="newsletterEmail" name="email" placeholder="Enter your email" required>
                        </div>

                        <?php if (flexpress_is_turnstile_enabled()): ?>
                            <!-- Turnstile widget -->
                            <div class="mb-3">
                                <?php echo flexpress_render_turnstile_widget(array(
                                    'callback' => 'flexpressNewsletterTurnstileCallback',
                                    'expired-callback' => 'flexpressNewsletterTurnstileExpired',
                                    'error-callback' => 'flexpressNewsletterTurnstileError',
                                    'id' => 'newsletter-turnstile'
                                )); ?>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-light">Subscribe Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        // LocalStorage key to persist dismissal
        var NEWSLETTER_LS_KEY = 'flexpress_newsletter_modal_dismissed';

        function hasDismissedNewsletterModal() {
            try {
                return window.localStorage && localStorage.getItem(NEWSLETTER_LS_KEY) === 'true';
            } catch (e) {
                return false;
            }
        }

        function markNewsletterModalDismissed() {
            try {
                if (window.localStorage) {
                    localStorage.setItem(NEWSLETTER_LS_KEY, 'true');
                }
            } catch (e) {}
        }

        // Show modal after delay if not previously dismissed
        // Wait for age verification to complete first
        if (!hasDismissedNewsletterModal()) {
            function showNewsletterModal() {
                $('#newsletterModal').modal('show');
            }
            
            // Check if age verification is already completed
            var ageVerified = false;
            try {
                ageVerified = localStorage.getItem('flexpress_age_verified') === 'true';
            } catch (e) {
                // If localStorage is not available, assume age verification is needed
                ageVerified = false;
            }
            
            if (ageVerified) {
                // Age already verified, show newsletter modal after delay
                setTimeout(showNewsletterModal, <?php echo $modal_delay * 1000; ?>);
            } else {
                // Wait for age verification to complete
                document.addEventListener('flexpress:ageVerified', function() {
                    setTimeout(showNewsletterModal, <?php echo $modal_delay * 1000; ?>);
                }, { once: true });
            }
        }

        // When modal fully hides (any dismissal path), remember preference
        $('#newsletterModal').on('hidden.bs.modal', function() {
            markNewsletterModalDismissed();
        });

        <?php if (flexpress_is_turnstile_enabled()): ?>
        // Explicitly render Turnstile when modal is shown to ensure widget exists
        var flexpressNewsletterWidgetId = null;
        var flexpressTurnstileRendered = false;

        $('#newsletterModal').on('shown.bs.modal', function() {
            try {
                if (window.turnstile && !flexpressTurnstileRendered) {
                    flexpressNewsletterWidgetId = turnstile.render('#newsletter-turnstile', {
                        sitekey: '<?php echo esc_js(flexpress_get_turnstile_site_key()); ?>',
                        theme: '<?php echo esc_js(flexpress_get_turnstile_theme()); ?>',
                        size: '<?php echo esc_js(flexpress_get_turnstile_size()); ?>',
                        callback: 'flexpressNewsletterTurnstileCallback',
                        'expired-callback': 'flexpressNewsletterTurnstileExpired',
                        'error-callback': 'flexpressNewsletterTurnstileError'
                    });
                    flexpressTurnstileRendered = true;
                }
            } catch (e) {
                console.error('Turnstile render error:', e);
            }
        });
        <?php endif; ?>

        // Handle form submission
        $('#newsletterForm').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const email = $('#newsletterEmail').val();

            if (!email) {
                return;
            }

            <?php if (flexpress_is_turnstile_enabled()): ?>
            if (!window.turnstile) {
                alert('Security system not loaded. Please wait a moment and try again.');
                return;
            }
            var token = null;
            try {
                token = flexpressNewsletterWidgetId ? turnstile.getResponse(flexpressNewsletterWidgetId) : turnstile.getResponse();
            } catch (e) {
                console.error('Turnstile getResponse error:', e);
            }
            if (!token) {
                alert('Please complete the security verification');
                return;
            }
            <?php endif; ?>

            $submitBtn.prop('disabled', true).text('Subscribing...');

            var ajaxData = {
                action: 'plunk_newsletter_signup',
                email: email,
                website: $form.find('input[name="website"]').val()
            };
            
            <?php if (flexpress_is_turnstile_enabled()): ?>
            ajaxData['cf-turnstile-response'] = token;
            <?php endif; ?>

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        const successHtml = `
                            <div class="text-center newsletter-success">
                                <div class="bg-white rounded p-4">
                                    <h4 class="text-pink mb-3">${response.data.message}</h4>
                                    <p class="text-dark mb-0">Please check your email to confirm your subscription.</p>
                                </div>
                            </div>`;
                        
                        $form.html(successHtml);
                        
                        // Close modal after delay
                        setTimeout(function() {
                            $('#newsletterModal').modal('hide');
                        }, 3000);
                    } else {
                        $submitBtn.prop('disabled', false).text('Subscribe Now');
                        <?php if (flexpress_is_turnstile_enabled()): ?>
                        try { if (window.turnstile) { turnstile.reset(flexpressNewsletterWidgetId || undefined); } } catch (e) { console.warn('Turnstile reset error:', e); }
                        <?php endif; ?>
                        $form.prepend(`<div class="alert alert-danger">${response.data}</div>`);
                    }
                },
                error: function() {
                    $submitBtn.prop('disabled', false).text('Subscribe Now');
                    <?php if (flexpress_is_turnstile_enabled()): ?>
                    try { if (window.turnstile) { turnstile.reset(flexpressNewsletterWidgetId || undefined); } } catch (e) { console.warn('Turnstile reset error:', e); }
                    <?php endif; ?>
                    $form.prepend('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                }
            });
        });

        // Turnstile callback functions
        <?php if (flexpress_is_turnstile_enabled()): ?>
        window.flexpressNewsletterTurnstileCallback = function(token) {
            console.log('Newsletter Turnstile token received:', token);
        };
        
        window.flexpressNewsletterTurnstileExpired = function() {
            console.log('Newsletter Turnstile token expired');
        };
        
        window.flexpressNewsletterTurnstileError = function(error) {
            console.log('Newsletter Turnstile error:', error);
        };
        <?php endif; ?>
    });
    </script>
    <?php
}

/**
 * Render newsletter status shortcode
 */
function flexpress_render_newsletter_status() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to manage your newsletter preferences.</p>';
    }

    if (!flexpress_is_plunk_enabled()) {
        return '<p>Newsletter management is not available.</p>';
    }

    $user = wp_get_current_user();
    $plunk_subscriber = new FlexPress_Plunk_Subscriber();
    $contact = $plunk_subscriber->get_user_contact_data($user->ID);
    
    $is_subscribed = false;
    if (!is_wp_error($contact) && isset($contact['subscribed'])) {
        $is_subscribed = $contact['subscribed'];
    }

    ob_start();
    ?>
    <div class="newsletter-toggle d-flex justify-content-center align-items-center">
        <label class="toggle-switch">
            <input type="checkbox" 
                   id="newsletter-toggle" 
                   <?php echo $is_subscribed ? 'checked' : ''; ?>>
            <span class="toggle-slider round"></span>
        </label>
        <span class="status-text">
            <?php echo $is_subscribed ? 'Subscribed' : 'Not Subscribed'; ?>
        </span>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#newsletter-toggle').on('change', function() {
            const isChecked = $(this).is(':checked');
            const action = isChecked ? 'subscribe' : 'unsubscribe';
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'plunk_toggle_subscription',
                    action_type: action,
                    nonce: '<?php echo wp_create_nonce('plunk_toggle_subscription'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('.status-text').text(isChecked ? 'Subscribed' : 'Not Subscribed');
                    } else {
                        alert(response.data);
                        $('#newsletter-toggle').prop('checked', !isChecked);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $('#newsletter-toggle').prop('checked', !isChecked);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Add newsletter modal to footer
 */
function flexpress_add_newsletter_modal_to_footer() {
    flexpress_render_newsletter_modal();
}
add_action('wp_footer', 'flexpress_add_newsletter_modal_to_footer');

/**
 * Register newsletter status shortcode
 */
function flexpress_register_newsletter_shortcode() {
    add_shortcode('newsletter_status', 'flexpress_render_newsletter_status');
}
add_action('init', 'flexpress_register_newsletter_shortcode');

/**
 * Enqueue Plunk frontend scripts
 */
function flexpress_enqueue_plunk_frontend_scripts() {
    if (flexpress_is_plunk_enabled()) {
        // Enqueue Turnstile script if enabled
        if (flexpress_is_turnstile_enabled()) {
            flexpress_enqueue_turnstile_script();
        }
        
        // Add newsletter modal styles
        wp_add_inline_style('flexpress-style', '
            .newsletter-modal .modal-content {
                background: linear-gradient(135deg, #e91e63, #f06292);
                border: none;
                border-radius: 15px;
            }
            
            .newsletter-modal .modal-title {
                color: white;
                font-weight: bold;
            }
            
            .newsletter-modal p {
                color: rgba(255, 255, 255, 0.9);
            }
            
            .newsletter-modal .form-control {
                border-radius: 25px;
                border: none;
                padding: 12px 20px;
            }
            
            .newsletter-modal .btn-light {
                border-radius: 25px;
                padding: 12px 30px;
                font-weight: bold;
                background: white;
                color: #e91e63;
                border: none;
            }
            
            .newsletter-modal .btn-light:hover {
                background: #f8f9fa;
                color: #e91e63;
            }
            
            .newsletter-modal .btn-close {
                position: absolute;
                top: 15px;
                right: 15px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 50%;
                width: 30px;
                height: 30px;
                opacity: 1;
            }
            
            .newsletter-success h4 {
                color: #e91e63 !important;
            }
            
            .toggle-switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
                margin-right: 15px;
            }
            
            .toggle-switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }
            
            .toggle-slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }
            
            input:checked + .toggle-slider {
                background-color: #e91e63;
            }
            
            input:checked + .toggle-slider:before {
                transform: translateX(26px);
            }
        ');
    }
}
add_action('wp_enqueue_scripts', 'flexpress_enqueue_plunk_frontend_scripts');

/**
 * Track user events for Plunk
 */
function flexpress_track_plunk_event($event_name, $event_data = array()) {
    if (!is_user_logged_in() || !flexpress_is_plunk_enabled()) {
        return;
    }
    
    $user_id = get_current_user_id();
    $plunk_subscriber = new FlexPress_Plunk_Subscriber();
    
    $plunk_subscriber->track_user_event($user_id, $event_name, $event_data);
}

/**
 * Track video view for Plunk
 */
function flexpress_track_video_view($video_id, $video_title = '') {
    flexpress_track_plunk_event('video-view', array(
        'videoId' => $video_id,
        'videoTitle' => $video_title,
        'timestamp' => date('c')
    ));
}

/**
 * Track purchase for Plunk
 */
function flexpress_track_purchase($amount, $product_id, $product_name = '') {
    flexpress_track_plunk_event('purchase', array(
        'amount' => $amount,
        'productId' => $product_id,
        'productName' => $product_name,
        'timestamp' => date('c')
    ));
}

/**
 * Track page view for Plunk
 */
function flexpress_track_page_view($page_title = '', $page_url = '') {
    flexpress_track_plunk_event('page-view', array(
        'pageTitle' => $page_title ?: get_the_title(),
        'pageUrl' => $page_url ?: get_permalink(),
        'timestamp' => date('c')
    ));
}
