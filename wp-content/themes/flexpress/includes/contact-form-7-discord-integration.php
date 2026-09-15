<?php
/**
 * Contact Form 7 Discord Integration
 * 
 * Sends Discord notifications when Contact Form 7 forms are submitted.
 * Integrates with FlexPress Discord notification system.
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Contact Form 7 Discord Integration Class
 */
class FlexPress_CF7_Discord_Integration {

    /** @var array<int, array> Casting reviews retained only for the current request. */
    private $casting_reviews = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Screen casting before CF7 creates its email, allowing strong spam to
        // be skipped while preserving CF7's normal success response.
        add_action('wpcf7_before_send_mail', array($this, 'screen_casting_before_mail'), 10, 3);
        add_filter('wpcf7_skip_mail', array($this, 'skip_mail_for_spam_troll'), 10, 2);

        // Hook into Contact Form 7 mail sent event
        add_action('wpcf7_mail_sent', array($this, 'handle_form_submission'), 10, 1);
        
        // Hook into Contact Form 7 mail failed event
        add_action('wpcf7_mail_failed', array($this, 'handle_form_failure'), 10, 1);
    }
    
    /**
     * Handle successful form submission
     * 
     * @param WPCF7_ContactForm $contact_form The contact form object
     */
    public function handle_form_submission($contact_form) {
        $form_id = $contact_form->id();
        $form_title = $contact_form->title();
        
        // Get form data
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }
        
        $posted_data = $submission->get_posted_data();
        
        // Determine form type based on FlexPress form IDs
        $form_type = $this->get_form_type($form_id);
        
        $ai_review = $form_type === 'casting' ? $this->get_cached_casting_review($form_id, $posted_data) : null;

        // Send Discord notification
        $this->send_discord_notification($form_type, $form_title, $posted_data, $ai_review);
    }

    /**
     * Review the configured casting form before CF7 mail is generated.
     * A suspicious application still receives mail with a visible subject tag;
     * only the highest-confidence spam/troll classification is suppressed.
     */
    public function screen_casting_before_mail($contact_form, &$abort, $submission) {
        $form_id = $contact_form->id();
        if ($this->get_form_type($form_id) !== 'casting' || !$submission) {
            return;
        }

        $review = $this->get_casting_ai_review($submission->get_posted_data());
        $this->casting_reviews[$form_id] = $review;

        if (($review['classification'] ?? '') !== 'suspicious') {
            return;
        }

        $mail = $contact_form->prop('mail');
        if (!is_array($mail) || empty($mail['subject'])) {
            return;
        }

        $mail['subject'] = '[Potential spam] ' . $mail['subject'];
        $contact_form->set_properties(array('mail' => $mail));
    }

    /** Skip both the staff email and CF7 autoresponder for a strong troll/spam match. */
    public function skip_mail_for_spam_troll($skip_mail, $contact_form) {
        $review = $this->casting_reviews[$contact_form->id()] ?? null;
        if (is_array($review) && ($review['classification'] ?? '') === 'spam_troll') {
            return true;
        }

        return $skip_mail;
    }
    
    /**
     * Handle form submission failure
     * 
     * @param WPCF7_ContactForm $contact_form The contact form object
     */
    public function handle_form_failure($contact_form) {
        $form_id = $contact_form->id();
        $form_title = $contact_form->title();
        
        // Get form data
        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }
        
        $posted_data = $submission->get_posted_data();
        
        // Determine form type
        $form_type = $this->get_form_type($form_id);
        
        // Send failure notification
        $this->send_discord_failure_notification($form_type, $form_title, $posted_data);
    }
    
    /**
     * Determine form type based on FlexPress form ID
     * 
     * @param int $form_id Contact Form 7 form ID
     * @return string Form type
     */
    private function get_form_type($form_id) {
        $contact_form_id = get_option('flexpress_contact_form_id');
        $casting_form_id = get_option('flexpress_casting_form_id');
        $support_form_id = get_option('flexpress_support_form_id');
        $content_removal_form_id = get_option('flexpress_content_removal_form_id');
        
        if ($form_id == $contact_form_id) {
            return 'contact';
        } elseif ($form_id == $casting_form_id) {
            return 'casting';
        } elseif ($form_id == $support_form_id) {
            return 'support';
        } elseif ($form_id == $content_removal_form_id) {
            return 'content_removal';
        } else {
            return 'general';
        }
    }
    
    /**
     * Send Discord notification for successful form submission
     * 
     * @param string $form_type Type of form (contact, casting, support, general)
     * @param string $form_title Form title
     * @param array $posted_data Form submission data
     */
    private function send_discord_notification($form_type, $form_title, $posted_data, $ai_review = null) {
        $discord_settings = get_option('flexpress_discord_settings', []);
        
        // Check if Discord notifications are enabled for this form type
        if (!$this->is_notification_enabled($form_type, $discord_settings)) {
            return;
        }
        
        // Create Discord embed based on form type
        $embed = $this->create_form_embed($form_type, $form_title, $posted_data, $ai_review);
        
        // Send notification
        $discord = new FlexPress_Discord_Notifications();
        $content = $this->get_notification_content($form_type);
        
        $suppress_notifications = $form_type === 'casting' && !empty($ai_review['potential_spam']);
        $discord->send_notification($embed, $content, $form_type === 'casting' ? 'casting' : 'contact', $suppress_notifications);
    }

    /** Return a standard unreviewed state if the optional classifier is unavailable. */
    private function get_casting_ai_review($posted_data) {
        if (!class_exists('FlexPress_Grok_Spam_Classifier')) {
            return array('status' => 'unreviewed', 'classification' => 'unreviewed', 'potential_spam' => false, 'reason' => __('AI screening is unavailable.', 'flexpress'));
        }
        return (new FlexPress_Grok_Spam_Classifier())->classify_casting_submission($posted_data);
    }

    /** Use the pre-mail review when available and avoid a second Grok request. */
    private function get_cached_casting_review($form_id, $posted_data) {
        if (isset($this->casting_reviews[$form_id])) {
            return $this->casting_reviews[$form_id];
        }

        return $this->get_casting_ai_review($posted_data);
    }
    
    /**
     * Send Discord notification for form submission failure
     * 
     * @param string $form_type Type of form
     * @param string $form_title Form title
     * @param array $posted_data Form submission data
     */
    private function send_discord_failure_notification($form_type, $form_title, $posted_data) {
        $discord_settings = get_option('flexpress_discord_settings', []);
        
        // Check if failure notifications are enabled
        if (!($discord_settings['notify_form_failures'] ?? false)) {
            return;
        }
        
        // Create failure embed
        $embed = $this->create_failure_embed($form_type, $form_title, $posted_data);
        
        // Send notification
        $discord = new FlexPress_Discord_Notifications();
        $discord->send_notification($embed, '⚠️ **Form submission failed!**', 'contact');
    }
    
    /**
     * Check if notifications are enabled for this form type
     * 
     * @param string $form_type Form type
     * @param array $discord_settings Discord settings
     * @return bool
     */
    private function is_notification_enabled($form_type, $discord_settings) {
        switch ($form_type) {
            case 'contact':
                return $discord_settings['notify_contact_forms'] ?? true;
            case 'casting':
                return $discord_settings['notify_casting_applications'] ?? true;
            case 'support':
                return $discord_settings['notify_support_requests'] ?? true;
            case 'content_removal':
                return $discord_settings['notify_content_removal'] ?? true;
            default:
                return $discord_settings['notify_general_forms'] ?? true;
        }
    }
    
    /**
     * Create Discord embed for form submission
     * 
     * @param string $form_type Form type
     * @param string $form_title Form title
     * @param array $posted_data Form data
     * @return array Discord embed
     */
    private function create_form_embed($form_type, $form_title, $posted_data, $ai_review = null) {
        $discord = new FlexPress_Discord_Notifications();
        
        // Get form-specific data
        $title = $this->get_form_title($form_type);
        $color = $this->get_form_color($form_type);
        $fields = $this->get_form_fields($form_type, $posted_data, $form_title, $ai_review);
        
        $description = sprintf(
            __('New %s form submission received from %s', 'flexpress'),
            $form_type,
            get_bloginfo('name')
        );
        
        return $discord->create_general_embed($title, $description, $color, $fields);
    }
    
    /**
     * Create Discord embed for form failure
     * 
     * @param string $form_type Form type
     * @param string $form_title Form title
     * @param array $posted_data Form data
     * @return array Discord embed
     */
    private function create_failure_embed($form_type, $form_title, $posted_data) {
        $discord = new FlexPress_Discord_Notifications();
        
        $title = sprintf(__('%s Form Submission Failed', 'flexpress'), ucfirst($form_type));
        $description = sprintf(
            __('A %s form submission failed to send properly. Please check the form configuration.', 'flexpress'),
            $form_type
        );
        
        $fields = [
            [
                'name' => __('Form Title', 'flexpress'),
                'value' => $form_title,
                'inline' => true
            ],
            [
                'name' => __('Form Type', 'flexpress'),
                'value' => ucfirst($form_type),
                'inline' => true
            ],
            [
                'name' => __('Timestamp', 'flexpress'),
                'value' => current_time('Y-m-d H:i:s'),
                'inline' => true
            ]
        ];
        
        return $discord->create_general_embed($title, $description, 0xff0000, $fields);
    }
    
    /**
     * Get notification content based on form type
     * 
     * @param string $form_type Form type
     * @return string Notification content
     */
    private function get_notification_content($form_type) {
        switch ($form_type) {
            case 'contact':
                return '📧 **New contact form submission!**';
            case 'casting':
                return '🌟 **New casting application received!**';
            case 'support':
                return '🆘 **New support request submitted!**';
            case 'content_removal':
                return '⚠️ **New content removal request submitted!**';
            default:
                return '📝 **New form submission received!**';
        }
    }
    
    /**
     * Get form title for Discord embed
     * 
     * @param string $form_type Form type
     * @return string Form title
     */
    private function get_form_title($form_type) {
        switch ($form_type) {
            case 'contact':
                return __('📧 Contact Form Submission', 'flexpress');
            case 'casting':
                return __('🌟 Casting Application', 'flexpress');
            case 'support':
                return __('🆘 Support Request', 'flexpress');
            case 'content_removal':
                return __('⚠️ Content Removal Request', 'flexpress');
            default:
                return __('📝 Form Submission', 'flexpress');
        }
    }
    
    /**
     * Get form color for Discord embed
     * 
     * @param string $form_type Form type
     * @return int Color hex value
     */
    private function get_form_color($form_type) {
        switch ($form_type) {
            case 'contact':
                return 0x0099ff; // Blue
            case 'casting':
                return 0xff6b35; // Orange
            case 'support':
                return 0xff0000; // Red
            case 'content_removal':
                return 0xff8c00; // Dark Orange
            default:
                return 0x00ff00; // Green
        }
    }
    
    /**
     * Get form fields for Discord embed
     * 
     * @param string $form_type Form type
     * @param array $posted_data Form data
     * @param string $form_title Form title
     * @return array Discord embed fields
     */
    private function get_form_fields($form_type, $posted_data, $form_title = '', $ai_review = null) {
        $fields = [];
        
        // Helper function to sanitize field values
        $sanitize_field_value = function($value) {
            if (empty($value)) {
                return __('Not provided', 'flexpress');
            }
            
            // Handle arrays (like checkboxes, multi-selects)
            if (is_array($value)) {
                $value = implode(', ', array_filter($value));
            }
            
            // Convert to string if not already
            $value = (string) $value;
            
            // Remove any potential Discord markdown that could cause issues
            $value = str_replace(['<', '>', '`', '*', '_', '~'], '', $value);
            
            // Truncate to Discord's 1024 character limit
            if (strlen($value) > 1024) {
                $value = substr($value, 0, 1021) . '...';
            }
            
            return $value;
        };
        
        // Helper function to format social media links for Discord
        $format_social_link = function($platform, $value) {
            if (empty($value)) {
                return __('Not provided', 'flexpress');
            }
            
            // Convert to string if not already
            $value = (string) $value;
            
            // Remove leading @ if present
            $value = ltrim($value, '@');
            
            // Check if value is already a URL
            $is_url = (strpos($value, 'http://') === 0 || strpos($value, 'https://') === 0);
            
            if ($is_url) {
                // Use URL as-is
                $url = $value;
                $display_text = $value;
            } else {
                // Build URL from handle
                switch ($platform) {
                    case 'instagram':
                        $url = 'https://instagram.com/' . $value;
                        $display_text = '@' . $value;
                        break;
                    case 'twitter':
                        // Use x.com as primary domain (Twitter's new domain)
                        $url = 'https://x.com/' . $value;
                        $display_text = '@' . $value;
                        break;
                    default:
                        // Fallback to sanitize if platform not recognized
                        return $sanitize_field_value($value);
                }
            }
            
            // Format as Discord markdown link: [text](url)
            // Discord supports markdown links in embed field values
            $link = '[' . $display_text . '](' . $url . ')';
            
            // Truncate to Discord's 1024 character limit
            if (strlen($link) > 1024) {
                $link = substr($link, 0, 1021) . '...';
            }
            
            return $link;
        };
        
        // Common fields for all forms (excluding name for casting form which uses applicant_name)
        if ($form_type !== 'casting' && isset($posted_data['name'])) {
            $fields[] = [
                'name' => __('Name', 'flexpress'),
                'value' => $sanitize_field_value($posted_data['name']),
                'inline' => true
            ];
        }
        
        if (isset($posted_data['email'])) {
            $fields[] = [
                'name' => __('Email', 'flexpress'),
                'value' => $sanitize_field_value($posted_data['email']),
                'inline' => true
            ];
        }
        
        // Add timestamp to all forms
        $fields[] = [
            'name' => __('Submitted', 'flexpress'),
            'value' => current_time('Y-m-d H:i:s'),
            'inline' => true
        ];

        if ($form_type === 'casting' && is_array($ai_review)) {
            $classification = $ai_review['classification'] ?? 'unreviewed';
            $potential_spam = !empty($ai_review['potential_spam']);
            $screening_label = __('AI unavailable / unreviewed', 'flexpress');

            if ($potential_spam && $classification === 'suspicious') {
                $screening_label = __('Potential spam — suspicious', 'flexpress');
            } elseif ($potential_spam && $classification === 'spam_troll') {
                $screening_label = __('Potential spam — spam/troll', 'flexpress');
            } elseif ($classification === 'legit') {
                $screening_label = __('Likely genuine', 'flexpress');
            }

            $fields[] = [
                'name' => __('AI Screening', 'flexpress'),
                'value' => $screening_label,
                'inline' => true
            ];
            $fields[] = [
                'name' => __('AI Assessment', 'flexpress'),
                'value' => $sanitize_field_value($ai_review['reason'] ?? __('No assessment available.', 'flexpress')),
                'inline' => false
            ];
        }
        
        // Form-specific fields
        switch ($form_type) {
            case 'contact':
                if (isset($posted_data['subject'])) {
                    $fields[] = [
                        'name' => __('Subject', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['subject']),
                        'inline' => true
                    ];
                }
                break;
                
            case 'casting':
                // Use applicant_name for casting form instead of name
                if (isset($posted_data['applicant_name'])) {
                    $fields[] = [
                        'name' => __('Name', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['applicant_name']),
                        'inline' => true
                    ];
                } elseif (isset($posted_data['name'])) {
                    // Fallback to name for backward compatibility
                    $fields[] = [
                        'name' => __('Name', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['name']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['gender_identity'])) {
                    $fields[] = [
                        'name' => __('Gender Identity', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['gender_identity']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['stage_age'])) {
                    $fields[] = [
                        'name' => __('Preferred Stage Age', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['stage_age']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['applicant_location'])) {
                    $fields[] = [
                        'name' => __('Based/Located', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['applicant_location']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['instagram']) && !empty($posted_data['instagram'])) {
                    $fields[] = [
                        'name' => __('Instagram', 'flexpress'),
                        'value' => $format_social_link('instagram', $posted_data['instagram']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['twitter']) && !empty($posted_data['twitter'])) {
                    $fields[] = [
                        'name' => __('Twitter', 'flexpress'),
                        'value' => $format_social_link('twitter', $posted_data['twitter']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['link']) && !empty($posted_data['link'])) {
                    $fields[] = [
                        'name' => __('Link to Work', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['link']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['about_you'])) {
                    $fields[] = [
                        'name' => __('About You', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['about_you']),
                        'inline' => false
                    ];
                }
                if (isset($posted_data['agreement'])) {
                    $fields[] = [
                        'name' => __('Agreement', 'flexpress'),
                        'value' => __('Confirmed', 'flexpress'),
                        'inline' => true
                    ];
                }
                break;
                
            case 'support':
                if (isset($posted_data['username']) && !empty($posted_data['username'])) {
                    $fields[] = [
                        'name' => __('Username', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['username']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['account_type']) && !empty($posted_data['account_type'])) {
                    $fields[] = [
                        'name' => __('Account Type', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['account_type']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['support_category'])) {
                    $fields[] = [
                        'name' => __('Support Category', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['support_category']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['priority']) && !empty($posted_data['priority'])) {
                    $fields[] = [
                        'name' => __('Priority', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['priority']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['subject'])) {
                    $fields[] = [
                        'name' => __('Subject', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['subject']),
                        'inline' => false
                    ];
                }
                if (isset($posted_data['message'])) {
                    $fields[] = [
                        'name' => __('Description', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['message']),
                        'inline' => false
                    ];
                }
                if (isset($posted_data['browser_info']) && !empty($posted_data['browser_info'])) {
                    $fields[] = [
                        'name' => __('Browser & Device', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['browser_info']),
                        'inline' => false
                    ];
                }
                if (isset($posted_data['attachments']) && !empty($posted_data['attachments'])) {
                    $fields[] = [
                        'name' => __('Attachments', 'flexpress'),
                        'value' => __('Files attached', 'flexpress'),
                        'inline' => true
                    ];
                }
                break;
                
            case 'content_removal':
                if (isset($posted_data['content_url'])) {
                    $fields[] = [
                        'name' => __('Content URL', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['content_url']),
                        'inline' => false
                    ];
                }
                if (isset($posted_data['removal_reason'])) {
                    $fields[] = [
                        'name' => __('Reason for Removal', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['removal_reason']),
                        'inline' => true
                    ];
                }
                if (isset($posted_data['identity_verification']) && !empty($posted_data['identity_verification'])) {
                    $fields[] = [
                        'name' => __('Identity Verification', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['identity_verification']),
                        'inline' => false
                    ];
                }
                if (isset($posted_data['additional_details']) && !empty($posted_data['additional_details'])) {
                    $fields[] = [
                        'name' => __('Additional Details', 'flexpress'),
                        'value' => $sanitize_field_value($posted_data['additional_details']),
                        'inline' => false
                    ];
                }
                if (isset($posted_data['confirmation'])) {
                    $fields[] = [
                        'name' => __('Confirmation', 'flexpress'),
                        'value' => __('Confirmed', 'flexpress'),
                        'inline' => true
                    ];
                }
                break;
        }
        
        // Add message field if present
        if (isset($posted_data['message'])) {
            $fields[] = [
                'name' => __('Message', 'flexpress'),
                'value' => $sanitize_field_value($posted_data['message']),
                'inline' => false
            ];
        }
        
        // Add form title
        if (!empty($form_title)) {
            $fields[] = [
                'name' => __('Form', 'flexpress'),
                'value' => $sanitize_field_value($form_title),
                'inline' => true
            ];
        }
        
        // Add timestamp
        $fields[] = [
            'name' => __('Submitted', 'flexpress'),
            'value' => current_time('Y-m-d H:i:s'),
            'inline' => true
        ];
        
        // Ensure we don't exceed Discord's 25 field limit
        if (count($fields) > 25) {
            $fields = array_slice($fields, 0, 25);
            $fields[24] = [
                'name' => __('Note', 'flexpress'),
                'value' => __('Some fields were truncated due to Discord limits', 'flexpress'),
                'inline' => false
            ];
        }
        
        return $fields;
    }
}

// Initialize the integration
new FlexPress_CF7_Discord_Integration();
