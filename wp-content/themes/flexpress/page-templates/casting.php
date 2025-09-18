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
                <div class="card shadow-lg">
                    <div class="card-header text-center py-4">
                        <h1 class="h2 mb-3 text-white"><?php the_title(); ?></h1>
                        <div class="lead text-white-50"><?php the_content(); ?></div>
                    </div>
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

<div class="casting-faq">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h2>Frequently Asked Questions</h2>
            </div>

            <div class="col-md-8 mx-auto">
                <div class="accordion" id="castingFAQ">
                    <!-- FAQ Item 1 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How long does a typical shoot day last?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#castingFAQ">
                            <div class="accordion-body">
                                Shoot days typically last 6-8 hours, including breaks, hair, and makeup time. We ensure regular breaks and maintain a comfortable, professional environment throughout the day.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 2 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                What should I bring to a shoot?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#castingFAQ">
                            <div class="accordion-body">
                                While we provide professional hair, makeup, and wardrobe options, you're welcome to bring your favorite outfits or accessories. We recommend bringing comfortable clothes to wear between scenes and any personal items you might need throughout the day.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 3 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                How quickly will I hear back after applying?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#castingFAQ">
                            <div class="accordion-body">
                                We typically respond to all applications within 2-3 business days. If selected, we'll schedule an initial video call to discuss opportunities and answer any questions you might have.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 4 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Do you provide transportation?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#castingFAQ">
                            <div class="accordion-body">
                                While we don't provide regular transportation, we can assist with travel arrangements for shoots and may cover travel expenses for certain productions. This is discussed on a case-by-case basis.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 5 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                What about privacy and discretion?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#castingFAQ">
                            <div class="accordion-body">
                                We take privacy very seriously. All shoots are conducted in secure, private locations. Your personal information is kept strictly confidential, and we offer flexible content agreements regarding distribution and marketing.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 6 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                Do you accept newcomers?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#castingFAQ">
                            <div class="accordion-body">
                                Yes! We welcome both experienced performers and newcomers. Our professional team provides guidance and support throughout the process, ensuring everyone feels comfortable and confident on set.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 7 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                What about health and safety?
                            </button>
                        </h2>
                        <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#castingFAQ">
                            <div class="accordion-body">
                                Health and safety are our top priorities. We require recent health certificates and maintain strict hygiene protocols on set. Our team follows industry-standard safety practices, and we provide a clean, professional environment for all shoots.
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Item 8 -->
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                What kind of content do you produce?
                            </button>
                        </h2>
                        <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#castingFAQ">
                            <div class="accordion-body">
                                We produce high-quality adult content with a focus on professionalism and creativity. During the application process, we'll discuss the types of content you're comfortable with and ensure all boundaries are respected.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="casting-requirements">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h2>Requirements</h2>
                <p class="lead">What you need to get started</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="requirement-card">
                    <i class="fas fa-id-card"></i>
                    <h3>Legal Requirements</h3>
                    <ul class="requirements-list">
                        <li><i class="fas fa-check"></i>Must be 18+ years old</li>
                        <li><i class="fas fa-check"></i>Valid government ID</li>
                        <li><i class="fas fa-check"></i>Right to work in Australia</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="requirement-card">
                    <i class="fas fa-clipboard-check"></i>
                    <h3>Health &amp; Safety</h3>
                    <ul class="requirements-list">
                        <li><i class="fas fa-check"></i> Recent health certificates</li>
                        <li><i class="fas fa-check"></i> Professional attitude</li>
                        <li><i class="fas fa-check"></i> Reliable transportation</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="requirement-card">
                    <i class="fas fa-star"></i>
                    <h3>Personal Qualities</h3>
                    <ul class="requirements-list">
                        <li><i class="fas fa-check"></i> Positive attitude</li>
                        <li><i class="fas fa-check"></i> Reliable and punctual</li>
                        <li><i class="fas fa-check"></i> Team player mindset</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="casting-text-block">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="text-content">
                    <p>Indulge in the opulence of our distinctive adult entertainment. We are the polar opposite of the sordid Adult Industry, upholding the worth of sophistication and refinement.</p>
<p>Our mission is to produce Australian High-Class Glamour Porn in a Professional setting. Dolls Downunder is an adult and lifestyle brand, celebrated for its top-tier, diverse, and relevant content in Adult Entertainment.</p>
<p>We strive to be the most inclusive sex-positive brand, creating content that encompasses a wide spectrum of sexualities, genders, races, body types, and ages. To witness our brand style, visit our social media:</p>
<p>Instagram: <a href="https://instagram.com/dollsdownunderofficial" target="_blank" rel="noopener">@dollsdownunderofficial</a></p>
<p>Twitter: <a href="https://x.com/dollsdownunder_" target="_blank" rel="noopener">@dollsdownunder_</a></p>
<p><strong>What we seek</strong></p>
<p>We're looking for models with natural allure that commands attention. Models of all shapes, sizes, backgrounds, and ethnicities are welcome. What we value most is AUTHENTICITY.</p>
<p>With numerous casting applications daily, ensure yours stands out! If you're interested in collaborating with the best and gaining significant exposure, apply here:</p>
                </div>
            </div>
        </div>
    </div>
</div>

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

<?php
get_footer(); 