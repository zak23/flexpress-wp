/**
 * Model Social Links - Members-Only Functionality
 * Handles locked social links with clever login prompts
 */

(function ($) {
  "use strict";

  // Show login prompt when clicking locked social links
  window.showLoginPrompt = function (platform) {
    // Get platform display name
    const platformNames = {
      instagram: "Instagram",
      twitter: "Twitter/X",
      tiktok: "TikTok",
      onlyfans: "OnlyFans",
      website: "Personal Website",
    };

    const platformName = platformNames[platform] || platform;
    const modelName =
      $("h1.hero-episode-title").first().text().trim() ||
      $(".model-section-heading")
        .first()
        .text()
        .replace("Connect with ", "")
        .trim() ||
      "this model";

    // Create modal HTML
    const modalHTML = `
            <div class="social-login-modal-overlay" id="socialLoginModal">
                <div class="social-login-modal">
                    <button class="social-modal-close" onclick="closeSocialLoginModal()">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="social-modal-content">
                        <div class="social-modal-icon">
                            <i class="fas fa-lock-open"></i>
                        </div>
                        <h3>Exclusive Member Access</h3>
                        <p class="social-modal-message">
                            Want to connect with <strong>${modelName}</strong> on <strong>${platformName}</strong>?
                        </p>
                        <p class="social-modal-benefit">
                            Members get exclusive access to:
                        </p>
                        <ul class="social-modal-benefits">
                            <li><i class="fas fa-check-circle"></i> All model social media profiles</li>
                            <li><i class="fas fa-check-circle"></i> Leave messages for models</li>
                            <li><i class="fas fa-check-circle"></i> Exclusive behind-the-scenes content</li>
                            <li><i class="fas fa-check-circle"></i> Priority access to new releases</li>
                        </ul>
                        <div class="social-modal-actions">
                            <a href="/login/" class="btn-social-login">
                                <i class="fas fa-sign-in-alt"></i>
                                Log In to Continue
                            </a>
                            <a href="/join/" class="btn-social-register">
                                <i class="fas fa-user-plus"></i>
                               Join now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;

    // Remove existing modal if any
    $("#socialLoginModal").remove();

    // Append to body
    $("body").append(modalHTML);

    // Add animation class after a brief delay
    setTimeout(function () {
      $("#socialLoginModal").addClass("active");
    }, 10);

    // Prevent body scroll
    $("body").css("overflow", "hidden");

    // Close on overlay click
    $("#socialLoginModal").on("click", function (e) {
      if ($(e.target).hasClass("social-login-modal-overlay")) {
        closeSocialLoginModal();
      }
    });

    // Track event (if analytics available)
    if (typeof gtag !== "undefined") {
      gtag("event", "social_link_locked_click", {
        platform: platform,
        model: modelName,
      });
    }
  };

  // Close modal function
  window.closeSocialLoginModal = function () {
    $("#socialLoginModal").removeClass("active");
    setTimeout(function () {
      $("#socialLoginModal").remove();
      $("body").css("overflow", "");
    }, 300);
  };

  // Close on ESC key
  $(document).on("keydown", function (e) {
    if (e.key === "Escape" && $("#socialLoginModal").length) {
      closeSocialLoginModal();
    }
  });

  // Add subtle animations to social icons on scroll
  if (typeof IntersectionObserver !== "undefined") {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry, index) => {
          if (entry.isIntersecting) {
            setTimeout(() => {
              entry.target.style.opacity = "1";
              entry.target.style.transform = "translateY(0)";
            }, index * 80);
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1 }
    );

    $(".social-icon-link").each(function () {
      $(this).css({
        opacity: "0",
        transform: "translateY(20px)",
        transition: "opacity 0.5s ease, transform 0.5s ease",
      });
      observer.observe(this);
    });
  }
})(jQuery);
