<?php
/**
 * Template Name: Model for Us / Casting
 */

get_header();
?>
  <div class="card-header text-center py-4">
                        <h1 class="h2 mb-3 text-white"><?php echo get_field('casting_header_title') ?: get_the_title(); ?></h1>
                        <div class="lead text-white-50"><?php echo get_field('casting_header_subtitle') ?: get_the_content(); ?></div>
                    </div>

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

<script>
// Form validation and UX enhancements
(function () {
    'use strict'
    
    // Form validation
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
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
    
    // Age validation
    var ageInput = document.getElementById('age')
    if (ageInput) {
        ageInput.addEventListener('input', function() {
            var age = parseInt(this.value)
            if (age < 18 && age > 0) {
                this.setCustomValidity('You must be at least 18 years old.')
            } else {
                this.setCustomValidity('')
            }
        })
    }
    
    // Phone number formatting
    var phoneInput = document.getElementById('phone')
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            var value = this.value.replace(/\D/g, '')
            if (value.length >= 10) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3')
            }
            this.value = value
        })
    }
})()
</script>

<div class="site-main">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                
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

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="needs-validation" novalidate>
                            <?php wp_nonce_field('casting_form', 'casting_nonce'); ?>
                            <input type="hidden" name="action" value="casting_form">

                            <div class="alert alert-info mb-4">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <?php esc_html_e('All applicants must be at least 18 years of age. ID verification will be required if selected.', 'flexpress'); ?>
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

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label"><?php esc_html_e('Phone Number', 'flexpress'); ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="age" class="form-label"><?php esc_html_e('Age', 'flexpress'); ?> <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="age" name="age" min="18" required>
                                    <div class="invalid-feedback">
                                        <?php esc_html_e('You must be at least 18 years old.', 'flexpress'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="experience" class="form-label"><?php esc_html_e('Previous Experience', 'flexpress'); ?></label>
                                <textarea class="form-control" id="experience" name="experience" rows="3" placeholder="<?php esc_attr_e('Please list any previous modeling or adult industry experience.', 'flexpress'); ?>"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="social_media" class="form-label"><?php esc_html_e('Social Media Profiles', 'flexpress'); ?></label>
                                <textarea class="form-control" id="social_media" name="social_media" rows="3" placeholder="<?php esc_attr_e('Instagram, Twitter, OnlyFans, etc.', 'flexpress'); ?>"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label"><?php esc_html_e('Why would you like to work with us?', 'flexpress'); ?></label>
                                <textarea class="form-control" id="message" name="message" rows="5"></textarea>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" value="" id="agreeCheck" required>
                                <label class="form-check-label" for="agreeCheck">
                                    <?php esc_html_e('I confirm that I am at least 18 years of age and consent to the processing of my personal data.', 'flexpress'); ?> <span class="text-danger">*</span>
                                </label>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('You must agree before submitting.', 'flexpress'); ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <?php esc_html_e('Submit Application', 'flexpress'); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); 