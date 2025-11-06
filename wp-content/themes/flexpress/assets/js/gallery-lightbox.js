/**
 * FlexPress Gallery Lightbox (vanilla JS, no jQuery dependency)
 */

(function () {
  "use strict";

  class FlexPressGalleryLightbox {
    constructor() {
      this.currentIndex = 0;
      this.images = [];
      this.isOpen = false;
      this.lightbox = null;
      this.image = null;
      this.caption = null;
      this.counter = null;

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

      document.body.insertAdjacentHTML("beforeend", lightboxHTML);
      this.lightbox = document.getElementById("gallery-lightbox");
      this.image = this.lightbox.querySelector(".lightbox-image");
      this.caption = this.lightbox.querySelector(".lightbox-caption");
      this.counter = this.lightbox.querySelector(".lightbox-counter");
    }

    bindEvents() {
      // Open lightbox when gallery images are clicked (capture phase to beat other handlers)
      document.addEventListener(
        "click",
        (evt) => {
          const target = evt.target;
          if (!target) return;
          const link = target.closest && target.closest(".gallery-link");
          if (!link) return;

          const inGallery = link.closest(".episode-gallery, .extras-gallery");
          if (!inGallery) return;

          evt.preventDefault();
          if (typeof evt.stopImmediatePropagation === "function") {
            evt.stopImmediatePropagation();
          }
          evt.stopPropagation();

          this.openLightbox(link);
        },
        true
      );

      // Close lightbox
      this.lightbox.addEventListener("click", (e) => {
        if (
          e.target.classList.contains("lightbox-close") ||
          e.target === this.lightbox
        ) {
          this.closeLightbox();
        }
      });

      // Navigation
      const prevBtn = this.lightbox.querySelector(".lightbox-prev");
      const nextBtn = this.lightbox.querySelector(".lightbox-next");
      if (prevBtn)
        prevBtn.addEventListener("click", () => this.previousImage());
      if (nextBtn) nextBtn.addEventListener("click", () => this.nextImage());

      // Keyboard navigation
      document.addEventListener("keydown", (e) => {
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

      // Resize handling
      window.addEventListener("resize", () => {
        if (this.isOpen) {
          this.fitImageToViewport();
        }
      });
    }

    openLightbox(clickedLink) {
      const gallery = clickedLink.closest(".episode-gallery, .extras-gallery");
      if (!gallery) return;

      const links = Array.from(gallery.querySelectorAll(".gallery-link"));

      // Build images array from links
      this.images = links
        .map((link) => {
          const item = link.closest(".gallery-item");
          const img = item ? item.querySelector("img") : null;
          const captionEl = item
            ? item.querySelector(".gallery-caption")
            : null;
          const href = link.getAttribute("href") || "";
          if (!href) return null;
          return {
            src: href,
            alt: img ? img.getAttribute("alt") || "" : "",
            caption: captionEl ? captionEl.textContent.trim() : "",
          };
        })
        .filter(Boolean);

      this.currentIndex = Math.max(0, links.indexOf(clickedLink));

      this.lightbox.classList.add("active");
      this.isOpen = true;
      this.showImage();
      document.body.classList.add("lightbox-open");
    }

    closeLightbox() {
      this.lightbox.classList.remove("active");
      this.isOpen = false;
      document.body.classList.remove("lightbox-open");
    }

    showImage() {
      if (!this.images.length) return;
      const image = this.images[this.currentIndex];

      // Loading state
      this.image.classList.add("loading");

      const img = new Image();
      img.onload = () => {
        this.image.setAttribute("src", image.src);
        this.image.setAttribute("alt", image.alt);
        this.image.classList.remove("loading");
        this.fitImageToViewport();
      };
      img.onerror = () => {
        this.image.classList.remove("loading");
        console.error("Failed to load image:", image.src);
      };
      img.src = image.src;

      this.caption.textContent = image.caption;
      this.counter.textContent = `${this.currentIndex + 1} / ${
        this.images.length
      }`;

      // Update nav disabled state
      const prevBtn = this.lightbox.querySelector(".lightbox-prev");
      const nextBtn = this.lightbox.querySelector(".lightbox-next");
      if (prevBtn) prevBtn.disabled = this.currentIndex === 0;
      if (nextBtn)
        nextBtn.disabled = this.currentIndex === this.images.length - 1;
    }

    fitImageToViewport() {
      const img = this.image;
      const content = this.lightbox.querySelector(".lightbox-content");
      if (!img || !content) return;

      // Reset
      img.style.maxWidth = "";
      img.style.maxHeight = "";
      img.style.width = "";
      img.style.height = "";

      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight;
      // Use viewport-based sizing; fallback if content has no size yet
      const availableWidth = viewportWidth * 0.9;
      const contentHeight = content.clientHeight || viewportHeight * 0.9;
      const availableHeight =
        Math.min(viewportHeight * 0.9, contentHeight) - 60;

      img.style.maxWidth = availableWidth + "px";
      img.style.maxHeight = availableHeight + "px";
      img.style.width = "auto";
      img.style.height = "auto";
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

  // Initialize immediately (no jQuery needed)
  if (
    document.querySelector(".episode-gallery") ||
    document.querySelector(".extras-gallery")
  ) {
    new FlexPressGalleryLightbox();
  }
})();
