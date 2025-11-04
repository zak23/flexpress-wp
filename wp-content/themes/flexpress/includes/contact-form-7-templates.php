<?php
/**
 * Contact Form 7 Templates for FlexPress
 * 
 * This file contains pre-configured Contact Form 7 templates
 * for contact, casting, and support forms.
 *
 * @package FlexPress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Create Contact Form 7 forms programmatically
 */
function flexpress_create_cf7_forms() {
    // Only run if Contact Form 7 is active
    if (!class_exists('WPCF7_ContactForm')) {
        return;
    }

    // Create Contact Form
    flexpress_create_contact_form();
    
    // Create Casting Form
    flexpress_create_casting_form();
    
    // Create Support Form
    flexpress_create_support_form();
    
    // Create Content Removal Form
    flexpress_create_content_removal_form();
}

/**
 * Create the main contact form
 */
function flexpress_create_contact_form() {
    $form_id = get_option('flexpress_contact_form_id');
    
    // Check if form already exists
    if ($form_id && get_post($form_id)) {
        return $form_id;
    }

    $form_content = '
<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">' . __('Name', 'flexpress') . ' <span class="text-danger">*</span></label>
        [text* name id:name class:form-control placeholder "' . __('Your full name', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter your name.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">' . __('Email Address', 'flexpress') . ' <span class="text-danger">*</span></label>
        [email* email id:email class:form-control placeholder "' . __('your@email.com', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter a valid email address.', 'flexpress') . '</div>
    </div>
</div>

<div class="mb-3">
    <label for="subject" class="form-label">' . __('Subject', 'flexpress') . ' <span class="text-danger">*</span></label>
    [text* subject id:subject class:form-control placeholder "' . __('What is this about?', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter a subject.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <label for="message" class="form-label">' . __('Message', 'flexpress') . ' <span class="text-danger">*</span></label>
    [textarea* message id:message class:form-control rows:5 placeholder "' . __('Tell us more...', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter your message.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    [submit class:btn class:btn-primary "' . __('Send Message', 'flexpress') . '"]
</div>';

    $mail_template = '
<p><strong>' . __('Name:', 'flexpress') . '</strong> [name]</p>
<p><strong>' . __('Email:', 'flexpress') . '</strong> [email]</p>
<p><strong>' . __('Subject:', 'flexpress') . '</strong> [subject]</p>
<p><strong>' . __('Message:', 'flexpress') . '</strong></p>
<p>[message]</p>

<hr>
<p><em>' . __('This message was sent from the contact form on', 'flexpress') . ' ' . get_bloginfo('name') . '</em></p>';

    $mail_2_template = '
<p>' . __('Hello [name],', 'flexpress') . '</p>

<p>' . __('Thank you for contacting us. We have received your message and will get back to you as soon as possible.', 'flexpress') . '</p>

<p><strong>' . __('Your message:', 'flexpress') . '</strong></p>
<p>[message]</p>

<hr>
<p>' . __('Best regards,', 'flexpress') . '<br>
' . get_bloginfo('name') . '</p>';

    $form_data = array(
        'post_title' => 'FlexPress Contact Form',
        'post_content' => $form_content,
        'post_status' => 'publish',
        'post_type' => 'wpcf7_contact_form',
        'meta_input' => array(
            '_form' => $form_content,
            '_mail' => array(
                'active' => true,
                'subject' => sprintf(__('Contact Form: %s', 'flexpress'), '[subject]'),
                'sender' => '[name] <[email]>',
                'body' => $mail_template,
                'recipient' => flexpress_get_contact_email('contact'),
                'additional_headers' => 'Reply-To: [email]',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            ),
            '_mail_2' => array(
                'active' => true,
                'subject' => sprintf(__('Thank you for contacting %s', 'flexpress'), get_bloginfo('name')),
                'sender' => get_bloginfo('name') . ' <' . flexpress_get_contact_email('contact') . '>',
                'body' => $mail_2_template,
                'recipient' => '[email]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            )
        )
    );

    $form_id = wp_insert_post($form_data);
    
    if ($form_id && !is_wp_error($form_id)) {
        update_option('flexpress_contact_form_id', $form_id);
        return $form_id;
    }
    
    return false;
}

/**
 * Create the casting application form
 */
function flexpress_create_casting_form() {
    $form_id = get_option('flexpress_casting_form_id');
    
    // Check if form already exists
    if ($form_id && get_post($form_id)) {
        return $form_id;
    }

    $form_content = '
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle-fill me-2"></i>
    ' . __('All applicants must be at least 18 years of age. ID verification will be required if selected.', 'flexpress') . '
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="applicant_name" class="form-label">' . __('Your Name', 'flexpress') . ' <span class="text-danger">*</span></label>
        [text* applicant_name id:applicant_name class:form-control placeholder "' . __('Enter your full name', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter your full name.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">' . __('Your Email', 'flexpress') . ' <span class="text-danger">*</span></label>
        [email* email id:email class:form-control placeholder "' . __('Enter your email address', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter a valid email address.', 'flexpress') . '</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="gender_identity" class="form-label">' . __('Gender Identity', 'flexpress') . ' <span class="text-danger">*</span></label>
        [select* gender_identity id:gender_identity class:form-control include_blank "' . __('Please select...', 'flexpress') . '" "' . __('Female', 'flexpress') . '" "' . __('Male', 'flexpress') . '" "' . __('Non-binary', 'flexpress') . '" "' . __('Transgender', 'flexpress') . '" "' . __('Genderfluid', 'flexpress') . '" "' . __('Agender', 'flexpress') . '" "' . __('Other', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please select your gender identity.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="stage_age" class="form-label">' . __('Preferred Stage Age', 'flexpress') . ' <span class="text-danger">*</span></label>
        [select* stage_age id:stage_age class:form-control include_blank "' . __('Please select...', 'flexpress') . '" "' . __('18-21', 'flexpress') . '" "' . __('22-25', 'flexpress') . '" "' . __('26-30', 'flexpress') . '" "' . __('31-35', 'flexpress') . '" "' . __('36-40', 'flexpress') . '" "' . __('41-45', 'flexpress') . '" "' . __('46-50', 'flexpress') . '" "' . __('50+', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please select your preferred stage age.', 'flexpress') . '</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="instagram" class="form-label">' . __('Instagram', 'flexpress') . '</label>
        [text instagram id:instagram class:form-control placeholder "' . __('Your Instagram handle (optional)', 'flexpress') . '"]
    </div>
    <div class="col-md-6 mb-3">
        <label for="twitter" class="form-label">' . __('Twitter', 'flexpress') . '</label>
        [text twitter id:twitter class:form-control placeholder "' . __('Your Twitter handle (optional)', 'flexpress') . '"]
    </div>
</div>

<div class="mb-3">
    <label for="about_you" class="form-label">' . __('About You', 'flexpress') . ' <span class="text-danger">*</span></label>
    [textarea* about_you id:about_you class:form-control rows:5 placeholder "' . __('Tell us about yourself, including any relevant experience, links to your work, social media profiles, and professional references...', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please tell us about yourself.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <div class="form-check">
        <input type="checkbox" name="agreement" id="agreement" class="form-check-input" required>
        <label class="form-check-label" for="agreement">
            ' . sprintf(__('I confirm I am over 18 years old and understand that submitting this form does not guarantee acceptance. I agree that %s may contact me regarding casting opportunities.', 'flexpress'), get_bloginfo('name')) . ' <span class="text-danger">*</span>
        </label>
        <div class="invalid-feedback">' . __('You must agree to the terms to submit your application.', 'flexpress') . '</div>
    </div>
</div>

<div class="mb-3">
    [submit class:btn class:btn-primary "' . __('Submit Application', 'flexpress') . '"]
</div>';

    $mail_template = '
<p><strong>' . __('Casting Application Received', 'flexpress') . '</strong></p>

<p><strong>' . __('Applicant Details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Name:', 'flexpress') . '</strong> [applicant_name]</p>
<p><strong>' . __('Email:', 'flexpress') . '</strong> [email]</p>
<p><strong>' . __('Gender Identity:', 'flexpress') . '</strong> [gender_identity]</p>
<p><strong>' . __('Preferred Stage Age:', 'flexpress') . '</strong> [stage_age]</p>

<p><strong>' . __('Social Media:', 'flexpress') . '</strong></p>
<p><strong>' . __('Instagram:', 'flexpress') . '</strong> [instagram]</p>
<p><strong>' . __('Twitter:', 'flexpress') . '</strong> [twitter]</p>

<p><strong>' . __('About You:', 'flexpress') . '</strong></p>
<p>[about_you]</p>

<p><strong>' . __('Agreement:', 'flexpress') . '</strong> ' . __('Confirmed', 'flexpress') . '</p>

<hr>
<p><em>' . __('This casting application was submitted from', 'flexpress') . ' ' . get_bloginfo('name') . '</em></p>';

    $mail_2_template = '
<p>' . __('Hello [applicant_name],', 'flexpress') . '</p>

<p>' . __('Thank you for your interest in joining our cast! We have received your application and will review it carefully.', 'flexpress') . '</p>

<p>' . __('If we think you might be a good fit, we will contact you within 7-10 business days to discuss next steps.', 'flexpress') . '</p>

<p>' . __('Thank you for your interest in', 'flexpress') . ' ' . get_bloginfo('name') . '!</p>

<hr>
<p>' . __('Best regards,', 'flexpress') . '<br>
' . __('The Casting Team', 'flexpress') . '<br>
' . get_bloginfo('name') . '</p>';

    $form_data = array(
        'post_title' => 'FlexPress Casting Application',
        'post_content' => $form_content,
        'post_status' => 'publish',
        'post_type' => 'wpcf7_contact_form',
        'meta_input' => array(
            '_form' => $form_content,
            '_mail' => array(
                'active' => true,
                'subject' => sprintf(__('Casting Application: %s', 'flexpress'), '[applicant_name]'),
                'sender' => '[applicant_name] <[email]>',
                'body' => $mail_template,
                'recipient' => flexpress_get_contact_email('contact'),
                'additional_headers' => 'Reply-To: [email]',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            ),
            '_mail_2' => array(
                'active' => true,
                'subject' => sprintf(__('Thank you for your casting application - %s', 'flexpress'), get_bloginfo('name')),
                'sender' => get_bloginfo('name') . ' <' . flexpress_get_contact_email('contact') . '>',
                'body' => $mail_2_template,
                'recipient' => '[email]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            )
        )
    );

    $form_id = wp_insert_post($form_data);
    
    if ($form_id && !is_wp_error($form_id)) {
        update_option('flexpress_casting_form_id', $form_id);
        return $form_id;
    }
    
    return false;
}

/**
 * Create the support form
 */
function flexpress_create_support_form() {
    $form_id = get_option('flexpress_support_form_id');
    
    // Check if form already exists
    if ($form_id && get_post($form_id)) {
        return $form_id;
    }

    $form_content = '
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle-fill me-2"></i>
    ' . __('Please provide as much detail as possible to help us assist you quickly and effectively.', 'flexpress') . '
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">' . __('Full Name', 'flexpress') . ' <span class="text-danger">*</span></label>
        [text* name id:name class:form-control placeholder "' . __('Your full name', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter your name.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">' . __('Email Address', 'flexpress') . ' <span class="text-danger">*</span></label>
        [email* email id:email class:form-control placeholder "' . __('your@email.com', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter a valid email address.', 'flexpress') . '</div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="username" class="form-label">' . __('Username (if applicable)', 'flexpress') . '</label>
        [text username id:username class:form-control placeholder "' . __('Your account username', 'flexpress') . '"]
    </div>
    <div class="col-md-6 mb-3">
        <label for="account_type" class="form-label">' . __('Account Type', 'flexpress') . '</label>
        [select account_type id:account_type class:form-control include_blank "' . __('Select account type', 'flexpress') . '" "' . __('Free Member', 'flexpress') . '" "' . __('Premium Member', 'flexpress') . '" "' . __('VIP Member', 'flexpress') . '" "' . __('Not a member', 'flexpress') . '"]
    </div>
</div>

<div class="mb-3">
    <label for="support_category" class="form-label">' . __('Support Category', 'flexpress') . ' <span class="text-danger">*</span></label>
    [select* support_category id:support_category class:form-control include_blank "' . __('Select a support category', 'flexpress') . '" "' . __('Account Help', 'flexpress') . '" "' . __('Billing Help', 'flexpress') . '" "' . __('Technical Support', 'flexpress') . '" "' . __('Content Access', 'flexpress') . '" "' . __('Password Reset', 'flexpress') . '" "' . __('Subscription Management', 'flexpress') . '" "' . __('Payment Issues', 'flexpress') . '" "' . __('Video Playback', 'flexpress') . '" "' . __('Mobile App', 'flexpress') . '" "' . __('Other', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please select a support category.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <label for="priority" class="form-label">' . __('Priority Level', 'flexpress') . '</label>
    [select priority id:priority class:form-control "' . __('Low - General question', 'flexpress') . '" "' . __('Medium - Minor issue', 'flexpress') . '" "' . __('High - Major issue', 'flexpress') . '" "' . __('Urgent - Cannot access account/content', 'flexpress') . '"]
    <small class="form-text text-muted">' . __('Help us prioritize your request based on urgency.', 'flexpress') . '</small>
</div>

<div class="mb-3">
    <label for="subject" class="form-label">' . __('Subject', 'flexpress') . ' <span class="text-danger">*</span></label>
    [text* subject id:subject class:form-control placeholder "' . __('Brief description of your issue', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter a subject.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <label for="message" class="form-label">' . __('Detailed Description', 'flexpress') . ' <span class="text-danger">*</span></label>
    [textarea* message id:message class:form-control rows:6 placeholder "' . __('Please provide detailed information about your issue, including any error messages, steps to reproduce the problem, and what you were trying to do when the issue occurred...', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter a detailed description.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <label for="browser_info" class="form-label">' . __('Browser & Device Info', 'flexpress') . '</label>
    [text browser_info id:browser_info class:form-control placeholder "' . __('e.g., Chrome 120 on Windows 11, Safari on iPhone 15', 'flexpress') . '"]
    <small class="form-text text-muted">' . __('Helpful for technical issues - include browser, operating system, and device type.', 'flexpress') . '</small>
</div>

<div class="mb-3">
    <label for="attachments" class="form-label">' . __('Screenshots or Files', 'flexpress') . '</label>
    [file attachments id:attachments class:form-control accept:image/*,.pdf,.txt]
    <small class="form-text text-muted">' . __('Upload screenshots, error messages, or relevant files (images, PDFs, text files only).', 'flexpress') . '</small>
</div>

<div class="mb-3">
    [submit class:btn class:btn-primary "' . __('Submit Support Request', 'flexpress') . '"]
</div>';

    $mail_template = '
<p><strong>' . __('Support Request Received', 'flexpress') . '</strong></p>

<p><strong>' . __('Customer Details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Name:', 'flexpress') . '</strong> [name]</p>
<p><strong>' . __('Email:', 'flexpress') . '</strong> [email]</p>
<p><strong>' . __('Username:', 'flexpress') . '</strong> [username]</p>
<p><strong>' . __('Account Type:', 'flexpress') . '</strong> [account_type]</p>

<p><strong>' . __('Support Request Details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Category:', 'flexpress') . '</strong> [support_category]</p>
<p><strong>' . __('Priority:', 'flexpress') . '</strong> [priority]</p>
<p><strong>' . __('Subject:', 'flexpress') . '</strong> [subject]</p>

<p><strong>' . __('Description:', 'flexpress') . '</strong></p>
<p>[message]</p>

<p><strong>' . __('Technical Information:', 'flexpress') . '</strong></p>
<p><strong>' . __('Browser & Device:', 'flexpress') . '</strong> [browser_info]</p>
<p><strong>' . __('Attachments:', 'flexpress') . '</strong> [attachments]</p>

<hr>
<p><em>' . __('This support request was submitted from', 'flexpress') . ' ' . get_bloginfo('name') . ' ' . __('on', 'flexpress') . ' ' . date('Y-m-d H:i:s') . '</em></p>';

    $mail_2_template = '
<p>' . __('Hello [name],', 'flexpress') . '</p>

<p>' . __('Thank you for contacting our support team. We have received your request and will respond as soon as possible.', 'flexpress') . '</p>

<p><strong>' . __('Your request details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Category:', 'flexpress') . '</strong> [support_category]</p>
<p><strong>' . __('Priority:', 'flexpress') . '</strong> [priority]</p>
<p><strong>' . __('Subject:', 'flexpress') . '</strong> [subject]</p>

<p><strong>' . __('Your message:', 'flexpress') . '</strong></p>
<p>[message]</p>

<p>' . __('We typically respond within 24-48 hours during business days. For urgent issues, we may respond sooner.', 'flexpress') . '</p>

<p>' . __('If you have any additional information or questions, please reply to this email.', 'flexpress') . '</p>

<hr>
<p>' . __('Best regards,', 'flexpress') . '<br>
' . __('Support Team', 'flexpress') . '<br>
' . get_bloginfo('name') . '</p>';

    $form_data = array(
        'post_title' => 'FlexPress Support Request',
        'post_content' => $form_content,
        'post_status' => 'publish',
        'post_type' => 'wpcf7_contact_form',
        'meta_input' => array(
            '_form' => $form_content,
            '_mail' => array(
                'active' => true,
                'subject' => sprintf(__('Support Request: %s', 'flexpress'), '[subject]'),
                'sender' => '[name] <[email]>',
                'body' => $mail_template,
                'recipient' => flexpress_get_contact_email('support'),
                'additional_headers' => 'Reply-To: [email]',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            ),
            '_mail_2' => array(
                'active' => true,
                'subject' => sprintf(__('Support Request Received - %s', 'flexpress'), get_bloginfo('name')),
                'sender' => get_bloginfo('name') . ' <' . flexpress_get_contact_email('support') . '>',
                'body' => $mail_2_template,
                'recipient' => '[email]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            )
        )
    );

    $form_id = wp_insert_post($form_data);
    
    if ($form_id && !is_wp_error($form_id)) {
        update_option('flexpress_support_form_id', $form_id);
        return $form_id;
    }
    
    return false;
}

/**
 * Get Contact Form 7 form ID by type
 */
function flexpress_get_cf7_form_id($type) {
    switch ($type) {
        case 'contact':
            return get_option('flexpress_contact_form_id');
        case 'casting':
            return get_option('flexpress_casting_form_id');
        case 'support':
            return get_option('flexpress_support_form_id');
        case 'content_removal':
            return get_option('flexpress_content_removal_form_id');
        default:
            return false;
    }
}

/**
 * Display Contact Form 7 form by type
 */
function flexpress_display_cf7_form($type, $args = array()) {
    $form_id = flexpress_get_cf7_form_id($type);
    
    if (!$form_id) {
        // Create form if it doesn't exist
        switch ($type) {
            case 'contact':
                $form_id = flexpress_create_contact_form();
                break;
            case 'casting':
                $form_id = flexpress_create_casting_form();
                break;
            case 'support':
                $form_id = flexpress_create_support_form();
                break;
            case 'content_removal':
                $form_id = flexpress_create_content_removal_form();
                break;
        }
    }
    
    if ($form_id) {
        $class = isset($args['class']) ? $args['class'] : 'needs-validation';
        echo '<div class="' . esc_attr($class) . '">';
        echo do_shortcode('[contact-form-7 id="' . esc_attr($form_id) . '"]');
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning">';
        echo '<p>' . esc_html__('Form could not be loaded. Please contact the administrator.', 'flexpress') . '</p>';
        echo '</div>';
    }
}

/**
 * Create the content removal form
 */
function flexpress_create_content_removal_form() {
    $form_id = get_option('flexpress_content_removal_form_id');
    
    // Check if form already exists
    if ($form_id && get_post($form_id)) {
        return $form_id;
    }

    $form_content = '
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    ' . __('We take all content removal requests very seriously. Please provide accurate information to help us process your request efficiently.', 'flexpress') . '
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">' . __('Full Name', 'flexpress') . ' <span class="text-danger">*</span></label>
        [text* name id:name class:form-control placeholder "' . __('Enter your full name', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter your full name.', 'flexpress') . '</div>
    </div>
    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">' . __('Email Address', 'flexpress') . ' <span class="text-danger">*</span></label>
        [email* email id:email class:form-control placeholder "' . __('your@email.com', 'flexpress') . '"]
        <div class="invalid-feedback">' . __('Please enter a valid email address.', 'flexpress') . '</div>
    </div>
</div>

<div class="mb-3">
    <label for="content_url" class="form-label">' . __('Content URL', 'flexpress') . ' <span class="text-danger">*</span></label>
    [url* content_url id:content_url class:form-control placeholder "' . __('https://example.com/content-to-remove', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please enter a valid URL to the content.', 'flexpress') . '</div>
    <small class="form-text text-muted">' . __('Please provide the direct URL to the specific content you want removed.', 'flexpress') . '</small>
</div>

<div class="mb-3">
    <label for="removal_reason" class="form-label">' . __('Reason for Removal', 'flexpress') . ' <span class="text-danger">*</span></label>
    [select* removal_reason id:removal_reason class:form-control include_blank "' . __('Please select a reason', 'flexpress') . '" "' . __('Non-consensual content', 'flexpress') . '" "' . __('Copyright infringement', 'flexpress') . '" "' . __('Privacy concern', 'flexpress') . '" "' . __('Personal information exposed', 'flexpress') . '" "' . __('Revenge porn/blackmail', 'flexpress') . '" "' . __('Underage content', 'flexpress') . '" "' . __('Other', 'flexpress') . '"]
    <div class="invalid-feedback">' . __('Please select a reason for removal.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    <label for="identity_verification" class="form-label">' . __('Identity Verification', 'flexpress') . '</label>
    [textarea identity_verification id:identity_verification class:form-control rows:3 placeholder "' . __('If you are the owner/subject of the content, please provide information to verify your identity. We may contact you for additional verification.', 'flexpress') . '"]
    <small class="form-text text-muted">' . __('Optional: Provide details to help us verify your identity if you are the content owner/subject.', 'flexpress') . '</small>
</div>

<div class="mb-3">
    <label for="additional_details" class="form-label">' . __('Additional Details', 'flexpress') . '</label>
    [textarea additional_details id:additional_details class:form-control rows:4 placeholder "' . __('Please provide any additional information that may help us process your request...', 'flexpress') . '"]
    <small class="form-text text-muted">' . __('Optional: Any additional context or information that may be relevant to your request.', 'flexpress') . '</small>
</div>

<div class="mb-3">
    <div class="form-check d-flex align-items-start">
        [checkbox* confirmation id:confirmation class:form-check-input me-2 "1"]
        <label class="form-check-label flex-grow-1" for="confirmation">
            ' . __('I confirm that all information provided is accurate and complete.', 'flexpress') . ' <span class="text-danger">*</span>
        </label>
    </div>
    <div class="invalid-feedback">' . __('You must confirm that the information is accurate.', 'flexpress') . '</div>
</div>

<div class="mb-3">
    [submit class:btn class:btn-warning "' . __('Submit Request', 'flexpress') . '"]
</div>';

    $mail_template = '
<p><strong>' . __('Content Removal Request Received', 'flexpress') . '</strong></p>

<p><strong>' . __('Requestor Details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Name:', 'flexpress') . '</strong> [name]</p>
<p><strong>' . __('Email:', 'flexpress') . '</strong> [email]</p>

<p><strong>' . __('Content Details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Content URL:', 'flexpress') . '</strong> [content_url]</p>
<p><strong>' . __('Reason for Removal:', 'flexpress') . '</strong> [removal_reason]</p>

<p><strong>' . __('Identity Verification:', 'flexpress') . '</strong></p>
<p>[identity_verification]</p>

<p><strong>' . __('Additional Details:', 'flexpress') . '</strong></p>
<p>[additional_details]</p>

<p><strong>' . __('Confirmation:', 'flexpress') . '</strong> ' . __('Confirmed', 'flexpress') . '</p>

<hr>
<p><em>' . __('This content removal request was submitted from', 'flexpress') . ' ' . get_bloginfo('name') . '</em></p>';

    $mail_2_template = '
<p>' . __('Hello [name],', 'flexpress') . '</p>

<p>' . __('Thank you for submitting your content removal request. We take all such requests very seriously and will review your submission promptly.', 'flexpress') . '</p>

<p><strong>' . __('Your request details:', 'flexpress') . '</strong></p>
<p><strong>' . __('Content URL:', 'flexpress') . '</strong> [content_url]</p>
<p><strong>' . __('Reason:', 'flexpress') . '</strong> [removal_reason]</p>

<p>' . __('Our legal and compliance team will carefully review your request and respond within the following timeframes:', 'flexpress') . '</p>
<ul>
    <li>' . __('Non-consensual use of an image and/or illegal content: 24 hours', 'flexpress') . '</li>
    <li>' . __('All other requests: 7 business days', 'flexpress') . '</li>
</ul>

<p>' . __('If we need additional information, we will contact you directly.', 'flexpress') . '</p>

<hr>
<p>' . __('Best regards,', 'flexpress') . '<br>
' . __('Legal & Compliance Team', 'flexpress') . '<br>
' . get_bloginfo('name') . '</p>';

    $form_data = array(
        'post_title' => 'FlexPress Content Removal Request',
        'post_content' => $form_content,
        'post_status' => 'publish',
        'post_type' => 'wpcf7_contact_form',
        'meta_input' => array(
            '_form' => $form_content,
            '_mail' => array(
                'active' => true,
                'subject' => sprintf(__('Content Removal Request: %s', 'flexpress'), '[removal_reason]'),
                'sender' => '[name] <[email]>',
                'body' => $mail_template,
                'recipient' => flexpress_get_contact_email('contact'),
                'additional_headers' => 'Reply-To: [email]',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            ),
            '_mail_2' => array(
                'active' => true,
                'subject' => sprintf(__('Content Removal Request Received - %s', 'flexpress'), get_bloginfo('name')),
                'sender' => get_bloginfo('name') . ' <' . flexpress_get_contact_email('contact') . '>',
                'body' => $mail_2_template,
                'recipient' => '[email]',
                'additional_headers' => '',
                'attachments' => '',
                'use_html' => true,
                'exclude_blank' => false
            )
        )
    );

    $form_id = wp_insert_post($form_data);
    
    if ($form_id && !is_wp_error($form_id)) {
        update_option('flexpress_content_removal_form_id', $form_id);
        return $form_id;
    }
    
    return false;
}

// Initialize forms when Contact Form 7 is active
add_action('wpcf7_init', 'flexpress_create_cf7_forms');

/**
 * Validate casting form to require at least one social media link or URL
 * 
 * @param WPCF7_Validation $result Validation result
 * @param array $tags Form tags
 * @return WPCF7_Validation Modified validation result
 */
function flexpress_validate_casting_form($result, $tags) {
    // Get the current form ID
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return $result;
    }
    
    $contact_form = $submission->get_contact_form();
    if (!$contact_form) {
        return $result;
    }
    
    $form_id = $contact_form->id();
    
    // Only validate casting form
    $casting_form_id = get_option('flexpress_casting_form_id');
    if (!$form_id || $form_id != $casting_form_id) {
        return $result;
    }
    
    // Get posted data
    $posted_data = $submission->get_posted_data();
    
    // Check if at least one social media link or URL is provided
    $has_instagram = !empty($posted_data['instagram'] ?? '');
    $has_twitter = !empty($posted_data['twitter'] ?? '');
    $has_url = false;
    
    // Check if about_you contains a URL pattern
    $about_you = $posted_data['about_you'] ?? '';
    if (!empty($about_you)) {
        // Check for URL patterns: http://, https://, www.
        $url_patterns = [
            '/(https?:\/\/[^\s]+)/i',
            '/(www\.[^\s]+)/i',
            '/([a-zA-Z0-9-]+\.(com|net|org|io|co|tv|me|cc|xyz|info|biz|us|uk|au|ca)[^\s]*)/i'
        ];
        
        foreach ($url_patterns as $pattern) {
            if (preg_match($pattern, $about_you)) {
                $has_url = true;
                break;
            }
        }
    }
    
    // If none of the above are provided, show validation error
    if (!$has_instagram && !$has_twitter && !$has_url) {
        // Find the about_you tag to attach the error
        foreach ($tags as $tag) {
            if ($tag->name === 'about_you') {
                $result->invalidate($tag, __('Please provide at least one social media handle (Instagram or Twitter) or include a link to your work in the About You section.', 'flexpress'));
                break;
            }
        }
    }
    
    return $result;
}
add_filter('wpcf7_validate', 'flexpress_validate_casting_form', 10, 2);
