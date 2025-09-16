<?php
/**
 * Template Name: Register (Flowguard)
 * Description: A cheeky user registration form before joining with Flowguard
 */

get_header();

// Check if user is already logged in
if (is_user_logged_in()) {
    wp_redirect(home_url('/join-flowguard'));
    exit;
}

// Check for promo code in URL
$promo_code = get_query_var('promo');
if (empty($promo_code)) {
    $promo_code = isset($_GET['promo']) ? sanitize_text_field($_GET['promo']) : '';
}
$promo_code = sanitize_text_field($promo_code);
?>

<main id="primary" class="site-main register-page-flowguard py-5">
    <div class="container">
        <!-- Header Section -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <h1 class="register-title mb-3">
                    <i class="fas fa-rocket me-2"></i>
                    Ready to Join the Fun?
                </h1>
                <p class="register-subtitle lead">
                    Create your account and unlock exclusive content with our premium membership.
                </p>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="registration-card">
                    <div class="card-header text-center">
                        <h2 class="mb-3">
                            <i class="fas fa-user-plus me-2"></i>
                            Create Your Account
                        </h2>
                        <p class="text-muted">Just a few quick details to get you started</p>
                    </div>
                    
                    <div class="card-body">
                        <form id="flowguard-registration-form" novalidate>
                            <!-- Username Field -->
                            <div class="form-group mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-1"></i>
                                    Username
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       required
                                       placeholder="Choose a cool username">
                                <div class="invalid-feedback"></div>
                                <small class="form-text text-muted">
                                    This will be your display name on the site
                                </small>
                            </div>

                            <!-- Email Field -->
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>
                                    Email Address
                                </label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       required
                                       placeholder="your@email.com">
                                <div class="invalid-feedback"></div>
                                <small class="form-text text-muted">
                                    We'll send you important updates here
                                </small>
                            </div>

                            <!-- Password Field -->
                            <div class="form-group mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>
                                    Password
                                </label>
                                <div class="password-input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required
                                           placeholder="Create a strong password">
                                    <button type="button" 
                                            class="btn btn-outline-secondary password-toggle"
                                            onclick="togglePassword('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                                
                                <!-- Password Strength Indicator -->
                                <div class="password-strength mt-2">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strength-fill"></div>
                                    </div>
                                    <small class="strength-text" id="strength-text">Enter a password</small>
                                </div>
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="form-group mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>
                                    Confirm Password
                                </label>
                                <div class="password-input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           required
                                           placeholder="Confirm your password">
                                    <button type="button" 
                                            class="btn btn-outline-secondary password-toggle"
                                            onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Promo Code Field (if provided) -->
                            <?php if (!empty($promo_code)): ?>
                            <div class="form-group mb-3">
                                <label for="promo_code" class="form-label">
                                    <i class="fas fa-gift me-1"></i>
                                    Promo Code
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="promo_code" 
                                       name="promo_code" 
                                       value="<?php echo esc_attr($promo_code); ?>"
                                       readonly>
                                <small class="form-text text-success">
                                    <i class="fas fa-check me-1"></i>
                                    Special promo code applied!
                                </small>
                            </div>
                            <?php endif; ?>

                            <!-- Terms and Privacy -->
                            <div class="form-group mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="agree_terms" 
                                           name="agree_terms" 
                                           required>
                                    <label class="form-check-label" for="agree_terms">
                                        I agree to the <a href="<?php echo home_url('/terms'); ?>" target="_blank">Terms of Service</a> 
                                        and <a href="<?php echo home_url('/privacy'); ?>" target="_blank">Privacy Policy</a>
                                    </label>
                                </div>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" 
                                    class="btn btn-primary w-100 btn-lg" 
                                    id="register-btn">
                                <i class="fas fa-rocket me-2"></i>
                                Create Account & Continue
                            </button>
                        </form>
                    </div>
                    
                    <div class="card-footer text-center">
                        <p class="mb-0">
                            Already have an account? 
                            <a href="<?php echo wp_login_url(home_url('/join-flowguard')); ?>" class="text-primary">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Info -->
        <div class="row justify-content-center mt-5">
            <div class="col-lg-8 text-center">
                <div class="security-info">
                    <h4 class="mb-3">
                        <i class="fas fa-shield-alt me-2"></i>
                        Your Privacy Matters
                    </h4>
                    <div class="security-badges">
                        <span class="badge bg-success me-2">
                            <i class="fas fa-lock me-1"></i>
                            Encrypted Data
                        </span>
                        <span class="badge bg-info me-2">
                            <i class="fas fa-user-shield me-1"></i>
                            Privacy Protected
                        </span>
                        <span class="badge bg-warning">
                            <i class="fas fa-ban me-1"></i>
                            No Spam
                        </span>
                    </div>
                    <p class="text-muted mt-3">
                        We never share your personal information and you can unsubscribe anytime.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.register-page-flowguard {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    min-height: 100vh;
}

.register-title {
    color: #ffffff;
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.register-subtitle {
    color: #b0b0b0;
    font-size: 1.2rem;
}

.registration-card {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(15px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.card-header {
    background: rgba(255, 255, 255, 0.03);
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 2rem 2rem 1rem;
}

.card-header h2 {
    color: #ffffff;
    font-size: 1.8rem;
    font-weight: 600;
}

.card-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    color: #ffffff;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    color: #ffffff;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    background: rgba(255, 255, 255, 0.12);
    border-color: #ff6b6b;
    box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
    color: #ffffff;
}

.form-control::placeholder {
    color: #888;
}

.password-input-group {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #888;
    padding: 0.5rem;
    border-radius: 5px;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #ff6b6b;
}

.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    height: 4px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 0.25rem;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-fill.weak {
    background: #dc3545;
    width: 25%;
}

.strength-fill.fair {
    background: #fd7e14;
    width: 50%;
}

.strength-fill.good {
    background: #ffc107;
    width: 75%;
}

.strength-fill.strong {
    background: #28a745;
    width: 100%;
}

.strength-text {
    color: #888;
    font-size: 0.8rem;
}

.strength-text.weak {
    color: #dc3545;
}

.strength-text.fair {
    color: #fd7e14;
}

.strength-text.good {
    color: #ffc107;
}

.strength-text.strong {
    color: #28a745;
}

.form-check-input:checked {
    background-color: #ff6b6b;
    border-color: #ff6b6b;
}

.form-check-label {
    color: #b0b0b0;
}

.form-check-label a {
    color: #ff6b6b;
    text-decoration: none;
}

.form-check-label a:hover {
    text-decoration: underline;
}

.btn-primary {
    background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
    border: none;
    border-radius: 12px;
    font-weight: 600;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
}

.btn-primary:disabled {
    opacity: 0.7;
    transform: none;
}

.card-footer {
    background: rgba(255, 255, 255, 0.03);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding: 1.5rem 2rem;
}

.card-footer a {
    color: #ff6b6b;
    text-decoration: none;
    font-weight: 500;
}

.card-footer a:hover {
    text-decoration: underline;
}

.security-info {
    background: rgba(255, 255, 255, 0.03);
    border-radius: 15px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.security-badges .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.form-control.is-valid {
    border-color: #28a745;
}

@media (max-width: 768px) {
    .register-title {
        font-size: 2rem;
    }
    
    .registration-card {
        margin: 0 1rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('flowguard-registration-form');
    const registerBtn = document.getElementById('register-btn');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthFill = document.getElementById('strength-fill');
    const strengthText = document.getElementById('strength-text');
    
    // Password strength checker
    function checkPasswordStrength(password) {
        let strength = 0;
        let feedback = '';
        
        if (password.length >= 8) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;
        
        switch (strength) {
            case 0:
            case 1:
                return { level: 'weak', text: 'Very weak password' };
            case 2:
                return { level: 'fair', text: 'Fair password' };
            case 3:
                return { level: 'good', text: 'Good password' };
            case 4:
            case 5:
                return { level: 'strong', text: 'Strong password' };
        }
    }
    
    // Update password strength indicator
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = checkPasswordStrength(password);
        
        strengthFill.className = 'strength-fill ' + strength.level;
        strengthText.className = 'strength-text ' + strength.level;
        strengthText.textContent = strength.text;
    });
    
    // Password confirmation validation
    confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword && password !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
            this.classList.add('is-invalid');
        } else {
            this.setCustomValidity('');
            this.classList.remove('is-invalid');
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        
        // Check password match
        if (passwordInput.value !== confirmPasswordInput.value) {
            confirmPasswordInput.setCustomValidity('Passwords do not match');
            confirmPasswordInput.classList.add('is-invalid');
            return;
        }
        
        // Show loading state
        registerBtn.disabled = true;
        registerBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
        
        // Prepare form data
        const formData = new FormData(form);
        formData.append('action', 'flexpress_register_user');
        formData.append('nonce', window.flowguardConfig.nonce);
        
        // Submit registration
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Registration successful, redirect to join page
                window.location.href = '/join-flowguard?registered=1';
            } else {
                // Show error
                alert('Registration failed: ' + data.data);
                registerBtn.disabled = false;
                registerBtn.innerHTML = '<i class="fas fa-rocket me-2"></i>Create Account & Continue';
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
            registerBtn.disabled = false;
            registerBtn.innerHTML = '<i class="fas fa-rocket me-2"></i>Create Account & Continue';
        });
    });
});

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php get_footer(); ?>
