/**
 * FlexPress main JavaScript file
 */
(function ($) {
  "use strict";

  // FlexPress global object
  window.FlexPress = {
    // Initialize the application
    init: function () {
      this.initHeroVideo();
      this.initPromoVideo();
      this.initMobileMenu();
      this.setupEventHandlers();
    },

    // Initialize video player
    initVideoPlayer: function () {
      if ($(".episode-video").length) {
        // Video player initialization code
      }
    },

    // Setup event handlers
    setupEventHandlers: function () {
      $(".episode-card")
        .on("mouseenter", function () {
          // Show preview if available
        })
        .on("mouseleave", function () {
          // Hide preview
        });

      $(".favorite-toggle").on("click", function (e) {
        e.preventDefault();
        // Toggle favorite state
      });

      $(".watchlist-toggle").on("click", function (e) {
        e.preventDefault();
        // Toggle watchlist state
      });
    },

    // Initialize trailer modal
    initTrailerModal: function () {
      $("#trailerModal").on("hidden.bs.modal", function () {
        // The iframe will be recreated on next show, no need to reset src
      });
    },

    // Initialize hero video (disabled - using new hero-video.js)
    initHeroVideo: function () {
      // Disabled - hero video functionality moved to hero-video.js
    },

    // Initialize promo video
    initPromoVideo: function () {
      const container = document.getElementById("promoVideo");
      if (!container) return;

      const thumbnail = container.querySelector(".promo-thumbnail");
      const video = container.querySelector(".promo-video");

      if (!video || !thumbnail) return;

      // Preload the video
      video.load();

      // After 3 seconds, fade out thumbnail and start video
      setTimeout(() => {
        // Ensure video is ready to play
        if (video.readyState >= 3) {
          startVideoTransition();
        } else {
          // Wait for video to be ready
          video.addEventListener("canplay", startVideoTransition, {
            once: true,
          });
        }
      }, 3000);

      function startVideoTransition() {
        // Show video behind thumbnail
        video.style.display = "block";
        video.style.opacity = "0";

        // Start playing
        video
          .play()
          .then(() => {
            // Fade out thumbnail, fade in video
            requestAnimationFrame(() => {
              thumbnail.style.opacity = "0";
              video.style.opacity = "1";

              // Remove thumbnail after transition
              setTimeout(() => {
                thumbnail.style.display = "none";
              }, 1000); // Match CSS transition duration
            });
          })
          .catch(() => {
            // If autoplay fails, keep thumbnail visible
            return;
          });
      }

      // No play button hover effects needed - entire video is clickable
    },

    // Initialize mobile menu
    initMobileMenu: function () {
      $(".navbar-nav .menu-item-has-children > a").after(
        '<span class="dropdown-toggle"></span>'
      );

      $(".dropdown-toggle").on("click", function (e) {
        e.preventDefault();
        $(this).toggleClass("active").next(".sub-menu").slideToggle(200);
      });

      $(window).on("resize", function () {
        if ($(window).width() > 991) {
          $(".sub-menu").removeAttr("style");
          $(".dropdown-toggle").removeClass("active");
        }
      });
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    FlexPress.init();
  });

  // PPV Episode Unlock System
  window.FlexPressUnlock = {
    init: function () {
      this.bindPurchaseButtons();
    },

    bindPurchaseButtons: function () {
      $(document).on(
        "click",
        ".purchase-btn",
        this.handlePurchaseClick.bind(this)
      );
      $(document).on(
        "click",
        ".gallery-preview-purchase",
        this.handleGalleryPurchaseClick.bind(this)
      );
    },

    handlePurchaseClick: function (e) {
      e.preventDefault();

      const $button = $(e.currentTarget);
      const episodeId = $button.data("episode-id");
      const basePrice = parseFloat($button.data("original-price")); // Use original price for validation
      const finalPrice = parseFloat($button.data("price")); // This is the discounted price
      const memberDiscount = parseFloat($button.data("discount")) || 0;
      const isActiveMember = $button.data("is-active-member") === "true";

      // Debug logging
      console.log("PPV Purchase Debug:", {
        episodeId: episodeId,
        basePrice: basePrice,
        finalPrice: finalPrice,
        memberDiscount: memberDiscount,
        isActiveMember: isActiveMember,
        dataAttributes: {
          "data-episode-id": $button.data("episode-id"),
          "data-price": $button.data("price"),
          "data-original-price": $button.data("original-price"),
          "data-discount": $button.data("discount"),
          "data-is-active-member": $button.data("is-active-member"),
        },
      });

      if (!episodeId || !basePrice) {
        alert("Invalid episode data. Please refresh the page and try again.");
        return;
      }

      // Check if user is logged in first - REQUIRED for PPV purchases
      if (typeof FlexPressData === "undefined" || !FlexPressData.isLoggedIn) {
        // Redirect to login with return URL
        const currentUrl = window.location.href;
        const loginUrl = "/login?redirect_to=" + encodeURIComponent(currentUrl);

        if (
          confirm(
            "You need to be logged in to purchase episodes. Redirect to login page?"
          )
        ) {
          window.location.href = loginUrl;
        }
        return;
      }

      // Use the pre-calculated final price from the template
      // The template already applies member discounts correctly
      let discountText = "";
      if (memberDiscount > 0 && isActiveMember) {
        discountText = ` (${memberDiscount}% member discount applied)`;
      }

      // Process purchase directly without confirmation dialog

      this.processPurchase(
        episodeId,
        finalPrice,
        basePrice,
        memberDiscount,
        $button
      );
    },

    processPurchase: function (
      episodeId,
      finalPrice,
      basePrice,
      memberDiscount,
      $button
    ) {
      // Disable button and show loading state
      const originalText = $button.text();
      $button.prop("disabled", true).text("Processing...");

      // Make AJAX request to create payment URL
      $.ajax({
        url: FlexPressData.ajaxurl,
        type: "POST",
        data: {
          action: "flexpress_create_ppv_purchase",
          episode_id: episodeId,
          final_price: finalPrice,
          base_price: basePrice,
          member_discount: memberDiscount,
          nonce: FlexPressData.nonce,
        },
        success: function (response) {
          if (response.success && response.data.payment_url) {
            // Redirect to payment URL
            window.location.href = response.data.payment_url;
          } else {
            alert(
              response.data.message ||
                "Error creating payment. Please try again."
            );
            $button.prop("disabled", false).text(originalText);
          }
        },
        error: function () {
          alert("Connection error. Please try again.");
          $button.prop("disabled", false).text(originalText);
        },
      });
    },

    handleGalleryPurchaseClick: function (e) {
      e.preventDefault();

      const $link = $(e.currentTarget);
      const episodeId = $link.data("episode-id");
      const extrasId = $link.data("extras-id");
      const finalPrice = parseFloat($link.data("price"));
      const basePrice = parseFloat($link.data("original-price"));
      const discount = parseFloat($link.data("discount")) || 0;
      const accessType = $link.data("access-type");
      const isActiveMember = $link.data("is-active-member") === "true";

      // Determine if this is an episode or extras purchase
      const contentId = episodeId || extrasId;
      const contentType = episodeId ? "episode" : "extras";

      // Debug logging
      console.log("Gallery PPV Purchase Debug:", {
        contentId: contentId,
        contentType: contentType,
        finalPrice: finalPrice,
        basePrice: basePrice,
        discount: discount,
        accessType: accessType,
        isActiveMember: isActiveMember,
      });

      if (!contentId || !finalPrice || !basePrice) {
        alert("Invalid content data. Please refresh the page and try again.");
        return;
      }

      // Check if user is logged in first - REQUIRED for PPV purchases
      if (!FlexPressData.isLoggedIn) {
        alert(
          "You must be logged in to purchase content. Redirecting to login..."
        );
        window.location.href =
          "/login?redirect_to=" + encodeURIComponent(window.location.href);
        return;
      }

      // Disable the link to prevent multiple clicks
      $link.addClass("disabled").css("pointer-events", "none");

      // Create the purchase
      $.ajax({
        url: FlexPressData.ajaxurl,
        type: "POST",
        data: {
          action: "flexpress_create_ppv_purchase",
          episode_id: episodeId,
          extras_id: extrasId,
          final_price: finalPrice,
          base_price: basePrice,
          member_discount: discount,
          nonce: FlexPressData.nonce,
        },
        success: function (response) {
          if (response.success && response.data.payment_url) {
            // Redirect to payment URL
            window.location.href = response.data.payment_url;
          } else {
            alert(
              response.data.message ||
                "Error creating payment. Please try again."
            );
            $link.removeClass("disabled").css("pointer-events", "auto");
          }
        },
        error: function () {
          alert("Connection error. Please try again.");
          $link.removeClass("disabled").css("pointer-events", "auto");
        },
      });
    },
  };

  // Initialize PPV unlock system
  $(document).ready(function () {
    FlexPressUnlock.init();
  });
})(jQuery);

// Add a SHA-256 hash function for client-side token generation
async function sha256(message) {
  // Encode the message as UTF-8
  const msgBuffer = new TextEncoder().encode(message);
  // Hash the message
  const hashBuffer = await crypto.subtle.digest("SHA-256", msgBuffer);
  // Convert ArrayBuffer to Array
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  // Convert bytes to hex string
  const hashHex = hashArray
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
  return hashHex;
}

// Generate BunnyCDN token
async function generateBunnyCDNToken(videoId) {
  // Make sure we have the required data and FlexPressData exists
  if (!FlexPressData || !FlexPressData.token || !FlexPressData.expires) {
    console.error("Missing token or expiration timestamp");
    return "";
  }

  // Use the pre-generated token from the server
  return FlexPressData.token;
}

(function ($) {
  "use strict";

  // Video preview functionality
  const videoPreviews = {
    init: function () {
      this.bindEvents();
    },

    bindEvents: function () {
      $(".episode-card").on("mouseenter", this.handleMouseEnter.bind(this));
      $(".episode-card").on("mouseleave", this.handleMouseLeave.bind(this));
    },

    handleMouseEnter: function (e) {
      const $card = $(e.currentTarget);
      const videoId = $card.data("preview-video");
      const $thumbnail = $card.find("img[data-preview-url]");

      if (videoId) {
        this.createPreviewPlayer($card, videoId);
      }

      if ($thumbnail.length) {
        const previewUrl = $thumbnail.data("preview-url");
        if (previewUrl) {
          // Always use the full URL as provided by the server
          $thumbnail.attr("src", previewUrl);
        }
      }
    },

    handleMouseLeave: function (e) {
      const $card = $(e.currentTarget);
      const $thumbnail = $card.find("img[data-preview-url]");

      this.removePreviewPlayer($card);

      if ($thumbnail.length) {
        const originalSrc = $thumbnail.data("original-src");
        if (originalSrc) {
          // Always use the full URL as provided by the server
          $thumbnail.attr("src", originalSrc);
        }
      }
    },

    createPreviewPlayer: async function ($card, videoId) {
      // If we don't have BunnyCDN URL or FlexPressData is not defined, don't try to create player
      if (
        !FlexPressData ||
        !FlexPressData.bunnycdnUrl ||
        !FlexPressData.libraryId
      ) {
        if (FlexPressData && FlexPressData.isDebug) {
          console.error("Missing BunnyCDN configuration");
        }
        return;
      }

      // Get the preview container
      const $previewContainer = $card.find(".preview-container");
      if (!$previewContainer.length) {
        $card.append(
          '<div class="preview-container position-absolute top-0 start-0 w-100 h-100"></div>'
        );
      }

      // Get token from server via AJAX for this specific video ID
      let token = "";
      let expires = "";

      try {
        const response = await $.ajax({
          url: FlexPressData.ajaxurl,
          type: "POST",
          data: {
            action: "flexpress_generate_bunnycdn_token",
            video_id: videoId,
            nonce: FlexPressData.nonce,
          },
        });

        if (response.success) {
          token = response.data.token;
          expires = response.data.expires;
        }
      } catch (error) {
        console.error("Error getting token:", error);
      }

      // Create video element
      const $video = $("<video>", {
        class: "preview-video w-100 h-100 object-fit-cover",
        muted: true,
        loop: true,
        playsinline: true,
        autoplay: false,
      });

      // Create source element with token
      let videoSrc =
        "https://" +
        FlexPressData.bunnycdnUrl +
        "/play/" +
        FlexPressData.libraryId +
        "/" +
        videoId;
      if (token && expires) {
        videoSrc += "?token=" + token + "&expires=" + expires;
      }

      const $source = $("<source>", {
        src: videoSrc,
        type: "video/mp4",
      });

      // Append source to video
      $video.append($source);

      // Add video to card
      $card.find(".preview-container").append($video);

      // Try playing video with a delay and error handling
      setTimeout(function () {
        try {
          const playPromise = $video[0].play();
          if (playPromise !== undefined) {
            playPromise.catch(function (error) {
              console.log("Auto-play prevented:", error);
            });
          }
        } catch (e) {
          console.log("Error playing video:", e);
        }
      }, 100);
    },

    removePreviewPlayer: function ($card) {
      const $video = $card.find(".preview-video");
      if ($video.length) {
        $video[0].pause();
        $video.remove();
      }
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    videoPreviews.init();
  });
})(jQuery);

// Mobile episode card webP previews (center-first + 10s auto-advance)
const mobileCardPreviews = (() => {
  let images = [];
  let visibleIdxs = [];
  let current = -1;
  let timer = null;
  let io = null;
  let ticking = false;

  const CENTER_BAND_RATIO = 0.25; // middle-ish band: 25% viewport height

  const isMobile = () =>
    window.matchMedia &&
    window.matchMedia('(hover: none)').matches &&
    window.matchMedia('(pointer: coarse)').matches;

  const isEligiblePage = () => {
    const cls = document.body.classList;
    const has = (...c) => c.some((x) => cls.contains(x));
    const starts = (p) => Array.from(cls).some((c) => c.startsWith(p));
    return (
      has('home', 'archive', 'search', 'page-template-page-home') ||
      starts('post-type-archive') ||
      starts('tax-')
    );
  };

  const swapToPreview = (img) => {
    const p = img && img.dataset ? img.dataset.previewUrl : null;
    if (p && img.src !== p) img.src = p;
  };

  const swapToOriginal = (img) => {
    const o = img && img.dataset ? img.dataset.originalSrc : null;
    if (o && img.src !== o) img.src = o;
  };

  const computeCenterCandidate = () => {
    if (!visibleIdxs.length) return -1;
    const vpCenterY = window.innerHeight / 2;
    const bandPx = window.innerHeight * CENTER_BAND_RATIO;

    let bestIdx = -1;
    let bestDist = Infinity;
    visibleIdxs.forEach((i) => {
      const r = images[i].getBoundingClientRect();
      const centerY = r.top + r.height / 2;
      const dist = Math.abs(centerY - vpCenterY);
      if (dist < bestDist) {
        bestDist = dist;
        bestIdx = i;
      }
    });

    return bestDist <= bandPx ? bestIdx : -1;
  };

  const setActive = (idx) => {
    if (idx === current) return;
    if (current !== -1 && images[current]) swapToOriginal(images[current]);
    current = idx;
    if (current !== -1 && images[current]) swapToPreview(images[current]);
  };

  const advance = () => {
    if (!visibleIdxs.length) return;
    // Prefer center candidate if available
    const centerIdx = computeCenterCandidate();
    if (centerIdx !== -1) {
      setActive(centerIdx);
      return;
    }

    // Otherwise, keep-last-active and rotate every 10s among visibles
    const pos = Math.max(0, visibleIdxs.indexOf(current));
    const nextIdx = visibleIdxs[(pos + 1) % visibleIdxs.length];
    setActive(nextIdx);
  };

  const start = () => {
    stop();
    timer = setInterval(advance, 10000);
  };

  const stop = () => {
    if (timer) clearInterval(timer);
    timer = null;
  };

  const onScrollOrResize = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const centerIdx = computeCenterCandidate();
      if (centerIdx !== -1) setActive(centerIdx);
      // If none centered, keep current per keep-last-active rule
      ticking = false;
    });
  };

  const observe = () => {
    if (!('IntersectionObserver' in window)) {
      visibleIdxs = images.map((_, i) => i);
      return;
    }
    io = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          const idx = images.indexOf(e.target);
          if (idx === -1) return;
          if (e.isIntersecting && e.intersectionRatio >= 0.6) {
            if (!visibleIdxs.includes(idx)) visibleIdxs.push(idx);
          } else {
            visibleIdxs = visibleIdxs.filter((i) => i !== idx);
            if (idx === current) {
              swapToOriginal(images[current]);
              current = -1;
            }
          }
        });
        // After visibility changes, try to align to center
        const centerIdx = computeCenterCandidate();
        if (centerIdx !== -1) setActive(centerIdx);
      },
      { threshold: [0, 0.6, 1] }
    );
    images.forEach((img) => io.observe(img));
  };

  const init = () => {
    // Mobile/no-hover only and only on home/archive/search templates
    if (!isMobile() || !isEligiblePage()) return;
    images = Array.from(
      document.querySelectorAll(
        '.episode-card img[data-preview-url][data-original-src]'
      )
    );
    if (!images.length) return;

    observe();
    // Initial alignment
    const centerIdx = computeCenterCandidate();
    if (centerIdx !== -1) setActive(centerIdx);
    start();

    document.addEventListener('visibilitychange', () =>
      document.hidden ? stop() : start()
    );
    window.addEventListener('scroll', onScrollOrResize, { passive: true });
    window.addEventListener('resize', onScrollOrResize);
    window.addEventListener('orientationchange', onScrollOrResize);
  };

  return { init };
})();

// Bootstrap mobile previews
if (typeof jQuery !== 'undefined') {
  jQuery(function () {
    mobileCardPreviews.init();
  });
} else if (document.readyState !== 'loading') {
  mobileCardPreviews.init();
} else {
  document.addEventListener('DOMContentLoaded', mobileCardPreviews.init);
}

/**
 * FlexPress Main JavaScript
 * Handles video preview and other interactive elements
 */

(function ($) {
  "use strict";

  /**
   * Initialize mobile menu
   */
  function initMobileMenu() {
    // Add dropdown toggle for submenus in mobile view
    $(".navbar-nav .menu-item-has-children > a").after(
      '<span class="dropdown-toggle"></span>'
    );

    // Handle dropdown toggle click
    $(".dropdown-toggle").on("click", function (e) {
      e.preventDefault();
      $(this).toggleClass("active").next(".sub-menu").slideToggle(200);
    });

    // Handle window resize
    $(window).on("resize", function () {
      if ($(window).width() > 991) {
        $(".sub-menu").removeAttr("style");
        $(".dropdown-toggle").removeClass("active");
      }
    });
  }
})(jQuery);

// Plan Type Switching
jQuery(document).ready(function ($) {
  // Plan Type Switching
  $(document).on("change", '[name="plan_type"]', function () {
    const planType = $(this).val();
    const $recurringPlans = $("#recurring-plans");
    const $onetimePlans = $("#onetime-plans");

    // Hide all plan groups first
    $(".plan-group").removeClass("active").hide();

    // Show selected plan group with animation
    if (planType === "recurring") {
      $recurringPlans.addClass("active").fadeIn();
      // Uncheck any selected one-time plans
      $onetimePlans.find('input[type="radio"]').prop("checked", false);
    } else {
      $onetimePlans.addClass("active").fadeIn();
      // Uncheck any selected recurring plans
      $recurringPlans.find('input[type="radio"]').prop("checked", false);
    }

    // Clear any error messages
    $(".alert-danger").fadeOut();
  });

  // Ensure plan type matches selected plan
  $(document).on("change", '[name="selected_plan"]', function () {
    const $selectedPlan = $(this).closest(".plan-card");
    const planType = $selectedPlan.data("plan-type");

    // Update plan type tabs
    if (planType === "one_time") {
      $("#onetime-tab").prop("checked", true).trigger("change");
    } else {
      $("#recurring-tab").prop("checked", true).trigger("change");
    }
  });
});
