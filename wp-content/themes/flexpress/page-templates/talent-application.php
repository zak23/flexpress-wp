<?php
/**
 * Template Name: Talent Application
 * 
 * Talent application form with Discord notifications
 * 
 * @package FlexPress
 * @since 1.0.0
 */

get_header();
?>

<main id="primary" class="site-main talent-application-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="talent-application-container">
                    <h1 class="text-center mb-4">🌟 Join Our Team</h1>
                    <p class="text-center mb-5">We're always looking for talented performers to join our exclusive content creation team.</p>
                    
                    <div id="application-success" class="alert alert-success" style="display: none;">
                        <h4>✅ Application Submitted Successfully!</h4>
                        <p>Thank you for your interest in joining our team. We'll review your application and get back to you within 48 hours.</p>
                    </div>
                    
                    <div id="application-error" class="alert alert-danger" style="display: none;"></div>
                    
                    <form id="talent-application-form" class="talent-form">
                        <?php wp_nonce_field('talent_application_nonce', '_wpnonce'); ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="applicant_name">Full Name *</label>
                                    <input type="text" 
                                           id="applicant_name" 
                                           name="name" 
                                           class="form-control" 
                                           required 
                                           placeholder="Enter your full name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="applicant_email">Email Address *</label>
                                    <input type="email" 
                                           id="applicant_email" 
                                           name="email" 
                                           class="form-control" 
                                           required 
                                           placeholder="Enter your email address">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="applicant_phone">Phone Number</label>
                                    <input type="tel" 
                                           id="applicant_phone" 
                                           name="phone" 
                                           class="form-control" 
                                           placeholder="Enter your phone number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="applicant_age">Age *</label>
                                    <input type="number" 
                                           id="applicant_age" 
                                           name="age" 
                                           class="form-control" 
                                           required 
                                           min="18" 
                                           max="99" 
                                           placeholder="Enter your age">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="applicant_location">Location *</label>
                            <input type="text" 
                                   id="applicant_location" 
                                   name="location" 
                                   class="form-control" 
                                   required 
                                   placeholder="City, State/Country">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="applicant_experience">Experience Level *</label>
                            <select id="applicant_experience" name="experience" class="form-control" required>
                                <option value="">Select your experience level</option>
                                <option value="beginner">Beginner - New to adult content</option>
                                <option value="amateur">Amateur - Some experience</option>
                                <option value="professional">Professional - Extensive experience</option>
                                <option value="expert">Expert - Industry veteran</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="applicant_bio">Tell Us About Yourself *</label>
                            <textarea id="applicant_bio" 
                                      name="bio" 
                                      class="form-control" 
                                      rows="5" 
                                      required 
                                      placeholder="Describe your background, interests, and what makes you unique..."></textarea>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="applicant_social">Social Media Links</label>
                            <input type="url" 
                                   id="applicant_social" 
                                   name="social_media" 
                                   class="form-control" 
                                   placeholder="Instagram, Twitter, OnlyFans, etc.">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="applicant_portfolio">Portfolio/Work Samples</label>
                            <input type="url" 
                                   id="applicant_portfolio" 
                                   name="portfolio" 
                                   class="form-control" 
                                   placeholder="Link to your portfolio or work samples">
                        </div>
                        
                        <div class="form-group mb-4">
                            <div class="form-check">
                                <input type="checkbox" 
                                       id="applicant_terms" 
                                       name="terms" 
                                       class="form-check-input" 
                                       required>
                                <label for="applicant_terms" class="form-check-label">
                                    I agree to the <a href="/terms" target="_blank">Terms of Service</a> and <a href="/privacy" target="_blank">Privacy Policy</a> *
                                </label>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>
                                Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.talent-application-page {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.talent-application-container {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 15px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.talent-form .form-control {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff;
    border-radius: 8px;
    padding: 12px 15px;
}

.talent-form .form-control:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    color: #ffffff;
}

.talent-form .form-control::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.talent-form label {
    color: #ffffff;
    font-weight: 600;
    margin-bottom: 8px;
}

.talent-form .btn-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
    border: none;
    border-radius: 25px;
    padding: 12px 30px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.talent-form .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 123, 255, 0.4);
}

.talent-form .form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.talent-form .form-check-label {
    color: rgba(255, 255, 255, 0.9);
}

.talent-form .form-check-label a {
    color: #007bff;
    text-decoration: none;
}

.talent-form .form-check-label a:hover {
    text-decoration: underline;
}

.alert {
    border-radius: 10px;
    border: none;
}

.alert-success {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
}

.alert-danger {
    background: linear-gradient(45deg, #dc3545, #e74c3c);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('talent-application-form');
    const successDiv = document.getElementById('application-success');
    const errorDiv = document.getElementById('application-error');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Hide previous messages
        successDiv.style.display = 'none';
        errorDiv.style.display = 'none';
        
        // Get form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Add nonce
        data._wpnonce = '<?php echo wp_create_nonce('talent_application_nonce'); ?>';
        
        // Validate required fields
        if (!data.name || !data.email || !data.age || !data.location || !data.experience || !data.bio || !data.terms) {
            errorDiv.innerHTML = 'Please fill in all required fields and accept the terms.';
            errorDiv.style.display = 'block';
            return;
        }
        
        // Validate age
        if (parseInt(data.age) < 18) {
            errorDiv.innerHTML = 'You must be at least 18 years old to apply.';
            errorDiv.style.display = 'block';
            return;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        submitBtn.disabled = true;
        
        // Submit form
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'submit_talent_application',
                ...data
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                successDiv.style.display = 'block';
                form.reset();
                form.scrollIntoView({ behavior: 'smooth' });
            } else {
                errorDiv.innerHTML = result.data || 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            errorDiv.innerHTML = 'Network error. Please check your connection and try again.';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
});
</script>

<?php get_footer(); ?>
