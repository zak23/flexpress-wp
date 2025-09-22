<?php
/**
 * Template Name: Contact
 */

get_header();
?>

<div class="site-main">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3"><?php the_title(); ?></h1>
                            <p class="lead text-muted mb-4"><?php the_content(); ?></p>
                        </div>

                        <?php
                        // Display Contact Form 7 form
                        if (class_exists('WPCF7')) {
                            flexpress_display_cf7_form('contact');
                        } else {
                            echo '<div class="alert alert-warning">';
                            echo '<p>' . esc_html__('Contact Form 7 plugin is required for this form to work. Please install and activate Contact Form 7.', 'flexpress') . '</p>';
                            echo '</div>';
                        }
                        ?>

                        <hr class="my-4">

                        <!-- Contact Information -->
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="bi bi-geo-alt display-4 text-primary mb-3"></i>
                                    <h3 class="h6 mb-2"><?php esc_html_e('Address', 'flexpress'); ?></h3>
                                    <p class="text-muted mb-0">
                                        <?php echo nl2br(esc_html(get_theme_mod('contact_address', '123 Main Street<br>City, State 12345'))); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="bi bi-envelope display-4 text-primary mb-3"></i>
                                    <h3 class="h6 mb-2"><?php esc_html_e('Email', 'flexpress'); ?></h3>
                                    <p class="text-muted mb-0">
                                        <a href="mailto:<?php echo esc_attr(get_theme_mod('contact_email', 'contact@example.com')); ?>" class="text-decoration-none">
                                            <?php echo esc_html(get_theme_mod('contact_email', 'contact@example.com')); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="bi bi-telephone display-4 text-primary mb-3"></i>
                                    <h3 class="h6 mb-2"><?php esc_html_e('Phone', 'flexpress'); ?></h3>
                                    <p class="text-muted mb-0">
                                        <a href="tel:<?php echo esc_attr(get_theme_mod('contact_phone', '+1 (555) 123-4567')); ?>" class="text-decoration-none">
                                            <?php echo esc_html(get_theme_mod('contact_phone', '+1 (555) 123-4567')); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Social Media -->
                        <div class="text-center mt-4">
                            <h3 class="h6 mb-3"><?php esc_html_e('Follow Us', 'flexpress'); ?></h3>
                            <div class="social-links">
                                <?php if (get_theme_mod('social_facebook')): ?>
                                    <a href="<?php echo esc_url(get_theme_mod('social_facebook')); ?>" class="btn btn-outline-primary me-2" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-facebook"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (get_theme_mod('social_twitter')): ?>
                                    <a href="<?php echo esc_url(get_theme_mod('social_twitter')); ?>" class="btn btn-outline-primary me-2" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-twitter"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (get_theme_mod('social_instagram')): ?>
                                    <a href="<?php echo esc_url(get_theme_mod('social_instagram')); ?>" class="btn btn-outline-primary me-2" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-instagram"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (get_theme_mod('social_youtube')): ?>
                                    <a href="<?php echo esc_url(get_theme_mod('social_youtube')); ?>" class="btn btn-outline-primary" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-youtube"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
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