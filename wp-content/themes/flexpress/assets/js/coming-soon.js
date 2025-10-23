/**
 * Coming Soon Page JavaScript
 * Handles video loading, error handling, and animations
 */

(function ($) {
  "use strict";

  // Wait for DOM to be ready
  $(document).ready(function () {
    initComingSoon();
  });

  function initComingSoon() {
    const videoContainer = document.getElementById("comingSoonVideo");
    const iframe = videoContainer
      ? videoContainer.querySelector("iframe")
      : null;

    if (videoContainer && iframe && flexpressComingSoon.videoId) {
      setupBunnyCDNPlayerHandling(videoContainer, iframe);
    }

    // Add smooth scrolling for anchor links
    setupSmoothScrolling();

    // Add keyboard navigation
    setupKeyboardNavigation();

    // Setup newsletter modal
    setupNewsletterModal();
  }

  function setupBunnyCDNPlayerHandling(container, iframe) {
    // Handle iframe load events
    iframe.addEventListener("load", function () {
      console.log("BunnyCDN player iframe loaded");
      // Add class to indicate player is ready
      container.classList.add("player-loaded");

      // Hide thumbnail if it exists
      const thumbnail = container.querySelector(".coming-soon-thumbnail");
      if (thumbnail) {
        setTimeout(() => {
          thumbnail.style.opacity = "0";
          setTimeout(() => {
            thumbnail.style.display = "none";
          }, 500);
        }, 1000); // Give player time to start
      }
    });

    // Handle iframe error
    iframe.addEventListener("error", function (e) {
      console.error("BunnyCDN player iframe error:", e);
      // Keep thumbnail visible if player fails
      container.classList.remove("player-loaded");
    });

    // Listen for messages from BunnyCDN player
    window.addEventListener("message", function (event) {
      // Check if message is from BunnyCDN player
      if (event.origin.includes("mediadelivery.net")) {
        try {
          const data = JSON.parse(event.data);

          if (data.type === "video-play") {
            console.log("BunnyCDN video started playing");
            container.classList.add("video-playing");
          } else if (data.type === "video-pause") {
            console.log("BunnyCDN video paused");
            container.classList.remove("video-playing");
          } else if (data.type === "video-end") {
            console.log("BunnyCDN video ended");
            container.classList.remove("video-playing");
          }
        } catch (e) {
          // Ignore non-JSON messages
        }
      }
    });

    // Fallback: assume player is working after a delay
    setTimeout(() => {
      if (!container.classList.contains("player-loaded")) {
        console.log("BunnyCDN player fallback - assuming loaded");
        container.classList.add("player-loaded");

        // Hide thumbnail
        const thumbnail = container.querySelector(".coming-soon-thumbnail");
        if (thumbnail) {
          thumbnail.style.opacity = "0";
          setTimeout(() => {
            thumbnail.style.display = "none";
          }, 500);
        }
      }
    }, 3000);
  }

  function setupSmoothScrolling() {
    // Handle anchor links with smooth scrolling
    $('.coming-soon-link[href^="#"]').on("click", function (e) {
      const target = $(this.getAttribute("href"));
      if (target.length) {
        e.preventDefault();
        $("html, body").animate(
          {
            scrollTop: target.offset().top,
          },
          800
        );
      }
    });
  }

  function setupNewsletterModal() {
    const modal = document.getElementById("newsletter-modal");
    const closeBtn = document.querySelector(".newsletter-modal-close");
    const form = document.querySelector(".newsletter-form");

    // Open modal when clicking "Get Notified" button
    $(".coming-soon-buttons .btn-primary").on("click", function (e) {
      const buttonText = $(this).text().toLowerCase();
      if (
        buttonText.includes("notified") ||
        buttonText.includes("get notified")
      ) {
        e.preventDefault();
        modal.style.display = "flex";
        setTimeout(() => {
          modal.classList.add("show");
        }, 10);
      }
    });

    // Close modal
    function closeModal() {
      modal.classList.remove("show");
      setTimeout(() => {
        modal.style.display = "none";
      }, 300);
    }

    // Close button
    closeBtn.addEventListener("click", closeModal);

    // Close on backdrop click
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });

    // Close on Escape key
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal.classList.contains("show")) {
        closeModal();
      }
    });

    // Handle form submission
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      const email = form.querySelector("input[type='email']").value;

      if (email) {
        // Here you would typically send the email to your backend
        console.log("Newsletter signup:", email);

        // Show success message
        const submitBtn = form.querySelector("button");
        const originalText = submitBtn.textContent;
        submitBtn.textContent = "Subscribed!";
        submitBtn.style.background = "#28a745";

        setTimeout(() => {
          closeModal();
          submitBtn.textContent = originalText;
          submitBtn.style.background = "";
          form.reset();
        }, 2000);
      }
    });
  }

  function setupKeyboardNavigation() {
    // Add keyboard support for accessibility
    $(document).on("keydown", function (e) {
      // Enter key on links
      if (e.keyCode === 13) {
        const focused = document.activeElement;
        if (focused && focused.classList.contains("coming-soon-link")) {
          focused.click();
        }
      }
    });
  }

  // Handle window resize for responsive iframe
  $(window).on("resize", function () {
    // Iframe videos are responsive by default with CSS
    // No additional handling needed
  });
})(jQuery);
