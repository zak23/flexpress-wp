<?php
/**
 * Template Name: Model for Us / Casting
 */

get_header();
?>

<div class="site-main">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
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
                            echo esc_html__('Failed to submit application. Please try again.', 'flexpress');
                            echo '</div>';
                        }

                        // Show success message
                        if (isset($_GET['sent']) && $_GET['sent'] === 'success') {
                            echo '<div class="alert alert-success">';
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