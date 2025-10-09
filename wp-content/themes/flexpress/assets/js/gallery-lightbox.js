/**
 * FlexPress Gallery Lightbox
 */

(function ($) {
  "use strict";

  class FlexPressGalleryLightbox {
    constructor() {
      this.currentIndex = 0;
      this.images = [];
      this.isOpen = false;

      this.init();
    }

    init() {
      this.createLightboxHTML();
      this.bindEvents();
    }

    createLightboxHTML() {
      const lightboxHTML = `
                <div class="gallery-lightbox" id="gallery-lightbox">
                    <div class="lightbox-content">
                        <img class="lightbox-image" src="" alt="">
                        <div class="lightbox-caption"></div>
                    </div>
                    <button class="lightbox-nav lightbox-prev" aria-label="Previous image">‹</button>
                    <button class="lightbox-nav lightbox-next" aria-label="Next image">›</button>
                    <button class="lightbox-close" aria-label="Close lightbox">×</button>
                    <div class="lightbox-counter"></div>
                </div>
            `;

      $("body").append(lightboxHTML);
      this.lightbox = $("#gallery-lightbox");
      this.image = this.lightbox.find(".lightbox-image");
      this.caption = this.lightbox.find(".lightbox-caption");
      this.counter = this.lightbox.find(".lightbox-counter");
    }

    bindEvents() {
      // Open lightbox when gallery images are clicked
      $(document).on("click", ".gallery-link", (e) => {
        e.preventDefault();
        this.openLightbox(e.currentTarget);
      });

      // Close lightbox
      this.lightbox.on("click", ".lightbox-close", () => this.closeLightbox());
      this.lightbox.on("click", (e) => {
        if (e.target === this.lightbox[0]) {
          this.closeLightbox();
        }
      });

      // Navigation
      this.lightbox.on("click", ".lightbox-prev", () => this.previousImage());
      this.lightbox.on("click", ".lightbox-next", () => this.nextImage());

      // Keyboard navigation
      $(document).on("keydown", (e) => {
        if (!this.isOpen) return;

        switch (e.key) {
          case "Escape":
            this.closeLightbox();
            break;
          case "ArrowLeft":
            this.previousImage();
            break;
          case "ArrowRight":
            this.nextImage();
            break;
        }
      });

      // Handle window resize
      $(window).on("resize", () => {
        if (this.isOpen) {
          this.fitImageToViewport();
        }
      });
    }

    openLightbox(clickedElement) {
      const $gallery = $(clickedElement).closest(
        ".episode-gallery, .extras-gallery"
      );
      const $images = $gallery.find(".gallery-item");

      // Build images array
      this.images = [];
      $images.each((index, item) => {
        const $item = $(item);
        const $link = $item.find(".gallery-link");
        const $img = $item.find("img");
        const $caption = $item.find(".gallery-caption");

        this.images.push({
          src: $link.attr("href"),
          alt: $img.attr("alt"),
          caption: $caption.text().trim(),
        });
      });

      // Find clicked image index
      this.currentIndex = $images.index(
        $(clickedElement).closest(".gallery-item")
      );

      // Show lightbox
      this.showImage();
      this.lightbox.addClass("active");
      this.isOpen = true;

      // Prevent body scroll
      $("body").addClass("lightbox-open");
    }

    closeLightbox() {
      this.lightbox.removeClass("active");
      this.isOpen = false;
      $("body").removeClass("lightbox-open");
    }

    showImage() {
      if (this.images.length === 0) return;

      const image = this.images[this.currentIndex];

      // Add loading state
      this.image.addClass("loading");

      // Create new image to preload
      const img = new Image();
      img.onload = () => {
        this.image.attr("src", image.src).attr("alt", image.alt);
        this.image.removeClass("loading");

        // Ensure image fits within viewport
        this.fitImageToViewport();
      };
      img.onerror = () => {
        this.image.removeClass("loading");
        console.error("Failed to load image:", image.src);
      };
      img.src = image.src;

      this.caption.text(image.caption);
      this.counter.text(`${this.currentIndex + 1} / ${this.images.length}`);

      // Update navigation button states
      this.lightbox
        .find(".lightbox-prev")
        .prop("disabled", this.currentIndex === 0);
      this.lightbox
        .find(".lightbox-next")
        .prop("disabled", this.currentIndex === this.images.length - 1);
    }

    fitImageToViewport() {
      const $img = this.image;
      const $content = this.lightbox.find(".lightbox-content");

      // Reset any previous sizing
      $img.css({
        "max-width": "",
        "max-height": "",
        width: "",
        height: "",
      });

      // Get viewport dimensions
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;

      // Calculate available space (accounting for padding and caption)
      const availableWidth = Math.min(viewportWidth * 0.9, $content.width());
      const availableHeight =
        Math.min(viewportHeight * 0.9, $content.height()) - 60; // Account for caption

      // Set max dimensions
      $img.css({
        "max-width": availableWidth + "px",
        "max-height": availableHeight + "px",
        width: "auto",
        height: "auto",
      });
    }

    previousImage() {
      if (this.currentIndex > 0) {
        this.currentIndex--;
        this.showImage();
      }
    }

    nextImage() {
      if (this.currentIndex < this.images.length - 1) {
        this.currentIndex++;
        this.showImage();
      }
    }
  }

  // Initialize lightbox when document is ready
  $(document).ready(() => {
    if ($(".episode-gallery").length || $(".extras-gallery").length) {
      new FlexPressGalleryLightbox();
    }
  });
})(jQuery);
