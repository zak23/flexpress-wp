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
                        // Show any error messages
                        if (isset($_GET['sent']) && $_GET['sent'] === 'failed') {
                            echo '<div class="alert alert-danger">';
                            echo esc_html__('Failed to send message. Please try again.', 'flexpress');
                            echo '</div>';
                        }

                        // Show success message
                        if (isset($_GET['sent']) && $_GET['sent'] === 'success') {
                            echo '<div class="alert alert-success">';
                            echo esc_html__('Message sent successfully. We\'ll get back to you soon.', 'flexpress');
                            echo '</div>';
                        }
                        ?>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="needs-validation" novalidate>
                            <?php wp_nonce_field('contact_form', 'contact_nonce'); ?>
                            <input type="hidden" name="action" value="contact_form">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label"><?php esc_html_e('Name', 'flexpress'); ?></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                    <div class="invalid-feedback">
                                        <?php esc_html_e('Please enter your name.', 'flexpress'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?php esc_html_e('Email Address', 'flexpress'); ?></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="invalid-feedback">
                                        <?php esc_html_e('Please enter a valid email address.', 'flexpress'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label"><?php esc_html_e('Subject', 'flexpress'); ?></label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter a subject.', 'flexpress'); ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label"><?php esc_html_e('Message', 'flexpress'); ?></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                <div class="invalid-feedback">
                                    <?php esc_html_e('Please enter your message.', 'flexpress'); ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <?php esc_html_e('Send Message', 'flexpress'); ?>
                            </button>
                        </form>

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