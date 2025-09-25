<?php

/**
 * Template Name: Model for Us / Casting
 */

get_header();
?>


<?php get_template_part('template-parts/casting-section'); ?>

<?php if (get_field('casting_requirements_cards')): ?>
    <div class="casting-requirements">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-4">
                    <h2><?php echo get_field('casting_requirements_title') ?: 'Requirements'; ?></h2>
                    <p class="lead"><?php echo get_field('casting_requirements_subtitle') ?: 'What you need to get started'; ?></p>
                </div>
            </div>

            <div class="row">
                <?php
                $req_json = get_field('casting_requirements_cards');
                $requirement_cards = json_decode($req_json, true);
                if ($requirement_cards && is_array($requirement_cards)):
                    foreach ($requirement_cards as $card):
                ?>
                        <div class="col-md-4">
                            <div class="requirement-card">
                                <i class="<?php echo esc_attr($card['icon_class']); ?>"></i>
                                <h3><?php echo esc_html($card['title']); ?></h3>
                                <ul class="requirements-list">
                                    <?php
                                    if (isset($card['requirements']) && is_array($card['requirements'])):
                                        foreach ($card['requirements'] as $requirement):
                                    ?>
                                            <li><i class="fas fa-check"></i><?php echo esc_html($requirement); ?></li>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </ul>
                            </div>
                        </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (get_field('casting_faq_items')): ?>
    <div class="casting-faq">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-4">
                    <h2><?php echo get_field('casting_faq_title') ?: 'Frequently Asked Questions'; ?></h2>
                </div>

                <div class="col-md-8 mx-auto">
                    <div class="accordion" id="castingFAQ">
                        <?php
                        $faq_json = get_field('casting_faq_items');
                        $faq_items = json_decode($faq_json, true);
                        if ($faq_items && is_array($faq_items)):
                            foreach ($faq_items as $index => $faq_item):
                                $faq_id = 'faq' . ($index + 1);
                                $expanded_class = isset($faq_item['expanded']) && $faq_item['expanded'] ? ' show' : '';
                                $button_class = isset($faq_item['expanded']) && $faq_item['expanded'] ? '' : ' collapsed';
                        ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button<?php echo $button_class; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $faq_id; ?>">
                                            <?php echo esc_html($faq_item['question']); ?>
                                        </button>
                                    </h2>
                                    <div id="<?php echo $faq_id; ?>" class="accordion-collapse collapse<?php echo $expanded_class; ?>" data-bs-parent="#castingFAQ">
                                        <div class="accordion-body">
                                            <?php echo wp_kses_post($faq_item['answer']); ?>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>



<?php if (get_field('casting_text_block')): ?>
    <div class="casting-text-block">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="text-content">
                        <?php echo get_field('casting_text_block'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    /* Fix checkbox alignment - override Contact Form 7 styling */
    .form-check p {
        display: flex !important;
        align-items: flex-start !important;
        gap: 0.5rem !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .form-check-input {
        margin-top: 0.25rem !important;
        flex-shrink: 0 !important;
        margin-right: 0.5rem !important;
    }

    .form-check-label {
        margin-bottom: 0 !important;
        line-height: 1.5 !important;
        flex: 1 !important;
    }

    .form-check br {
        display: none !important;
    }

    /* Center the submit button */
    .wpcf7-submit {
        display: block !important;
        margin: 0 auto !important;
    }
</style>

<script>
    // Form validation and UX enhancements
    (function() {
        'use strict'

        // Form validation
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                } else {
                    // Add loading state to submit button
                    var submitBtn = form.querySelector('.btn-primary')
                    if (submitBtn) {
                        submitBtn.disabled = true
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Submitting...'
                    }
                }
                form.classList.add('was-validated')
            }, false)
        })

        // Instagram handle formatting
        var instagramInput = document.getElementById('instagram')
        if (instagramInput) {
            instagramInput.addEventListener('input', function() {
                var value = this.value
                // Remove @ symbol if user adds it
                if (value.startsWith('@')) {
                    this.value = value.substring(1)
                }
            })
        }

        // Twitter handle formatting
        var twitterInput = document.getElementById('twitter')
        if (twitterInput) {
            twitterInput.addEventListener('input', function() {
                var value = this.value
                // Remove @ symbol if user adds it
                if (value.startsWith('@')) {
                    this.value = value.substring(1)
                }
            })
        }

        // Agreement checkbox validation
        var agreementCheckbox = document.getElementById('agreement')
        if (agreementCheckbox) {
            agreementCheckbox.addEventListener('change', function() {
                if (!this.checked) {
                    this.setCustomValidity('You must agree to the terms to submit your application.')
                } else {
                    this.setCustomValidity('')
                }
            })
        }
    })()
</script>

<div class="site-main">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card-header text-center py-4">
                    <h2 class="h2 mb-3 text-white">Ready to Get Started?</h2>
                    <div class="lead text-white-50">Fill out our casting application form below and we'll be in touch with you shortly to discuss the next steps. We look forward to potentially working with you!</div>
                </div>
                <div class="card shadow-lg">

                    <div class="card-body p-4">
                        <?php
                        // Show any error messages
                        if (isset($_GET['sent']) && $_GET['sent'] === 'failed') {
                            echo '<div class="alert alert-danger">';
                            echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
                            echo esc_html__('Failed to submit application. Please try again.', 'flexpress');
                            echo '</div>';
                        }

                        // Show success message
                        if (isset($_GET['sent']) && $_GET['sent'] === 'success') {
                            echo '<div class="alert alert-success">';
                            echo '<i class="bi bi-check-circle-fill me-2"></i>';
                            echo esc_html__('Application submitted successfully. We\'ll get back to you soon.', 'flexpress');
                            echo '</div>';
                        }
                        ?>

                        <?php
                        // Display Contact Form 7 casting form
                        if (class_exists('WPCF7')) {
                            flexpress_display_cf7_form('casting');
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<p>' . esc_html__('Contact Form 7 plugin is required for this form to work. Please install and activate Contact Form 7.', 'flexpress') . '</p>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer();
