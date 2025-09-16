<?php
/**
 * Template Name: Content Removal
 */

get_header();
?>

<div class="site-main legal-page">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3"><?php the_title(); ?></h1>
                            <div class="lead text-muted mb-4"><?php the_content(); ?></div>
                        </div>

                        <?php
                        // Show any error messages
                        if (isset($_GET['sent']) && $_GET['sent'] === 'failed') {
                            echo '<div class="alert alert-danger">';
                            echo esc_html__('Failed to submit request. Please try again.', 'flexpress');
                            echo '</div>';
                        }

                        // Show success message
                        if (isset($_GET['sent']) && $_GET['sent'] === 'success') {
                            echo '<div class="alert alert-success">';
                            echo esc_html__('Request submitted successfully. We\'ll review your request and get back to you soon.', 'flexpress');
                            echo '</div>';
                        }
                        ?>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="needs-validation" novalidate>
                            <?php wp_nonce_field('removal_form', 'removal_nonce'); ?>
                            <input type="hidden" name="action" value="removal_form">

                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <?php esc_html_e('Please provide the necessary information to help us identify and review the content in question.', 'flexpress'); ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label"><?php esc_html_e('Full Name', 'flexpress'); ?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback">
                                        <?php esc_html_e('Please enter your full name.', 'flexpress'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?> <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback">
                                        <?php esc_html_e('Please enter a valid email address.', 'flexpress'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="content_url" class="form-label"><?php esc_html_e('Content URL', 'flexpress'); ?> <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="content_url" name="content_url" placeholder="<?php esc_attr_e('https://example.com/path-to-content', 'flexpress'); ?>" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter a valid URL to the content.', 'flexpress'); ?>
                                </div>
                                <small class="form-text text-muted">
                                    <?php esc_html_e('Please provide the direct URL to the specific content you want removed.', 'flexpress'); ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="reason" class="form-label"><?php esc_html_e('Reason for Removal', 'flexpress'); ?> <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="reason" name="reason" rows="4" required></textarea>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please provide a reason for removal.', 'flexpress'); ?>
                                </div>
                                <small class="form-text text-muted">
                                    <?php esc_html_e('Please explain why this content should be removed (e.g., copyright infringement, privacy concern, etc.).', 'flexpress'); ?>
                                </small>
                            </div>

                            <div class="mb-4">
                                <label for="verification" class="form-label"><?php esc_html_e('Identity Verification', 'flexpress'); ?></label>
                                <textarea class="form-control" id="verification" name="verification" rows="4"></textarea>
                                <small class="form-text text-muted">
                                    <?php esc_html_e('If you are the owner/subject of the content, please provide information to verify your identity. We may contact you for additional verification.', 'flexpress'); ?>
                                </small>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" value="" id="agreeCheck" required>
                                <label class="form-check-label" for="agreeCheck">
                                    <?php esc_html_e('I confirm that all information provided is accurate and complete.', 'flexpress'); ?> <span class="text-danger">*</span>
                                </label>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('You must agree before submitting.', 'flexpress'); ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <?php esc_html_e('Submit Request', 'flexpress'); ?>
                            </button>
                        </form>

                        <?php 
                        // Display additional content if set
                        flexpress_display_legal_additional_content(); 
                        ?>

                        <?php 
                        // Display additional contact form if configured (separate from removal form)
                        $additional_form_id = get_field('legal_contact_form_id');
                        if ($additional_form_id) {
                            $form_title = get_field('legal_contact_form_title') ?: __('Additional Questions?', 'flexpress');
                            ?>
                            <div class="mt-5 pt-4 border-top">
                                <div class="text-center mb-4">
                                    <h2 class="h4 mb-3"><?php echo esc_html($form_title); ?></h2>
                                    <p class="text-muted"><?php esc_html_e('Have other questions not related to content removal? Use the form below.', 'flexpress'); ?></p>
                                </div>
                                
                                <?php
                                // Support both Contact Form 7 and WPForms
                                if (class_exists('WPCF7')) {
                                    echo do_shortcode('[contact-form-7 id="' . esc_attr($additional_form_id) . '"]');
                                } elseif (function_exists('wpforms')) {
                                    echo do_shortcode('[wpforms id="' . esc_attr($additional_form_id) . '"]');
                                } else {
                                    echo '<div class="alert alert-warning">';
                                    echo '<p>' . esc_html__('Contact form plugin not found. Please install Contact Form 7 or WPForms.', 'flexpress') . '</p>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>

                        <?php if (flexpress_should_show_legal_last_updated()): ?>
                        <div class="mt-5">
                            <h2 class="h4 mb-3"><?php esc_html_e('Last Updated', 'flexpress'); ?></h2>
                            <p class="text-muted">
                                <?php echo esc_html(flexpress_get_legal_last_updated_date()); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php
get_footer(); 