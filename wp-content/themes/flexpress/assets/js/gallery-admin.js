/**
 * FlexPress Gallery Admin JavaScript
 */

(function ($) {
  "use strict";

  // Gallery Admin Class
  class FlexPressGalleryAdmin {
    constructor() {
      const prefix = flexpressGallery.postType === "extras" ? "extras-" : "";
      this.uploadArea = $(`#${prefix}gallery-upload-area`);
      this.fileInput = $(`#${prefix}gallery-file-input`);
      this.progressBar = $(`#${prefix}upload-progress`);
      this.progressFill = this.progressBar.find(".progress-fill");
      this.progressText = this.progressBar.find(".progress-text");
      this.imagesGrid = $(`#${prefix}gallery-images-grid`);
      this.selectButton = $(`#select-${prefix}gallery-images`);

      // Upload queue management
      this.uploadQueue = [];
      this.isUploading = false;
      this.currentUploadIndex = 0;

      this.init();
    }

    init() {
      console.log("FlexPress Gallery Admin initialized");
      this.bindEvents();
      this.initSortable();
    }

    bindEvents() {
      // File selection button
      this.selectButton.on("click", () => this.openFileSelector());

      // Drag and drop events
      this.uploadArea.on("dragover", (e) => this.handleDragOver(e));
      this.uploadArea.on("dragleave", (e) => this.handleDragLeave(e));
      this.uploadArea.on("drop", (e) => this.handleDrop(e));

      // File input change
      this.fileInput.on("change", (e) => this.handleFileSelect(e));

      // Image management events
      this.imagesGrid.on("click", ".delete-image", (e) => this.deleteImage(e));
      this.imagesGrid.on("click", ".edit-image", (e) => this.editImage(e));
      this.imagesGrid.on("click", ".move-up", (e) => this.moveImage(e, "up"));
      this.imagesGrid.on("click", ".move-down", (e) =>
        this.moveImage(e, "down")
      );
      this.imagesGrid.on("change", ".image-alt, .image-caption", (e) =>
        this.updateImageInfo(e)
      );

      // Delete all button
      const prefix = flexpressGallery.postType === "extras" ? "extras-" : "";
      $(document).on("click", `#delete-all-${prefix}gallery-images`, (e) => {
        console.log("Delete all button clicked");
        this.deleteAllImages(e);
      });

      // Upload control buttons
      $(document).on("click", ".retry-failed", (e) => {
        e.preventDefault();
        this.retryFailedUploads();
      });

      $(document).on("click", ".retry-single", (e) => {
        e.preventDefault();
        const itemId = $(e.target).closest(".upload-item").data("id");
        this.retrySingleUpload(itemId);
      });

      $(document).on("click", ".cancel-upload", (e) => {
        e.preventDefault();
        this.cancelUpload();
      });

      $(document).on("click", ".done-upload", (e) => {
        e.preventDefault();
        const prefix = flexpressGallery.postType === "extras" ? "extras-" : "";
        $(`#${prefix}upload-progress-items`).hide();
        this.refreshGallery();
      });
    }

    initSortable() {
      this.imagesGrid.sortable({
        handle: ".image-order",
        axis: "y",
        update: (event, ui) => this.handleReorder(),
      });
    }

    openFileSelector() {
      this.fileInput.click();
    }

    handleDragOver(e) {
      e.preventDefault();
      e.stopPropagation();
      this.uploadArea.addClass("dragover");
    }

    handleDragLeave(e) {
      e.preventDefault();
      e.stopPropagation();
      this.uploadArea.removeClass("dragover");
    }

    handleDrop(e) {
      e.preventDefault();
      e.stopPropagation();
      this.uploadArea.removeClass("dragover");

      const files = e.originalEvent.dataTransfer.files;
      if (files.length > 0) {
        this.uploadFiles(files);
      }
    }

    handleFileSelect(e) {
      const files = e.target.files;
      if (files.length > 0) {
        this.uploadFiles(files);
      }
    }

    uploadFiles(files) {
      if (this.isUploading) {
        console.log("Upload already in progress, ignoring new files");
        return;
      }

      // Create upload queue items
      this.uploadQueue = Array.from(files).map((file, index) => ({
        id: "upload_" + Date.now() + "_" + index,
        file: file,
        filename: file.name,
        size: file.size,
        status: "pending",
        error: null,
        retryCount: 0,
      }));

      this.isUploading = true;
      this.currentUploadIndex = 0;

      this.showProgress();
      this.createProgressItems();
      this.processUploadQueue();
    }

    createProgressItems() {
      const prefix = flexpressGallery.postType === "extras" ? "extras-" : "";
      const progressContainer = $(`#${prefix}upload-progress-items`);

      if (progressContainer.length === 0) {
        // Create progress container if it doesn't exist
        this.progressBar.after(`
          <div id="${prefix}upload-progress-items" class="upload-progress-items" style="display: none;">
            <div class="upload-items-header">
              <h4>Upload Progress</h4>
              <div class="upload-actions">
                <button type="button" class="button retry-failed" style="display: none;">Retry Failed</button>
                <button type="button" class="button cancel-upload">Cancel</button>
              </div>
            </div>
            <div class="upload-items-list"></div>
          </div>
        `);
      }

      const itemsList = $(`#${prefix}upload-progress-items .upload-items-list`);
      itemsList.empty();

      this.uploadQueue.forEach((item) => {
        const itemHtml = `
          <div class="upload-item" data-id="${item.id}">
            <div class="upload-item-info">
              <span class="upload-status-icon">⏳</span>
              <span class="upload-filename">${item.filename}</span>
              <span class="upload-size">(${this.formatFileSize(
                item.size
              )})</span>
            </div>
            <div class="upload-item-progress">
              <div class="upload-item-bar">
                <div class="upload-item-fill" style="width: 0%"></div>
              </div>
              <span class="upload-item-status">Pending</span>
            </div>
            <div class="upload-item-actions" style="display: none;">
              <button type="button" class="button button-small retry-single">Retry</button>
            </div>
          </div>
        `;
        itemsList.append(itemHtml);
      });

      $(`#${prefix}upload-progress-items`).show();
    }

    formatFileSize(bytes) {
      if (bytes === 0) return "0 Bytes";
      const k = 1024;
      const sizes = ["Bytes", "KB", "MB", "GB"];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    }

    async processUploadQueue() {
      for (let i = 0; i < this.uploadQueue.length; i++) {
        if (!this.isUploading) {
          console.log("Upload cancelled by user");
          break;
        }

        this.currentUploadIndex = i;
        const item = this.uploadQueue[i];

        this.updateItemStatus(item.id, "uploading", "Uploading...");
        this.updateItemProgress(item.id, 0);

        try {
          await this.uploadFileSequential(item);
        } catch (error) {
          console.error("Upload failed for", item.filename, error);
          this.updateItemStatus(
            item.id,
            "failed",
            error.message || "Upload failed"
          );
        }
      }

      this.updateOverallProgress();
      this.checkUploadComplete();
    }

    uploadFileSequential(item) {
      return new Promise((resolve, reject) => {
        // Validate file type
        if (!item.file.type.startsWith("image/")) {
          reject(new Error("Invalid file type: " + item.file.type));
          return;
        }

        // Validate file size (max 10MB)
        if (item.file.size > 10 * 1024 * 1024) {
          reject(
            new Error("File too large: " + this.formatFileSize(item.file.size))
          );
          return;
        }

        const formData = new FormData();
        formData.append("action", "flexpress_upload_gallery_image");
        formData.append("nonce", flexpressGallery.nonce);
        formData.append("post_id", flexpressGallery.postId);
        formData.append("image", item.file);
        formData.append("title", item.filename.replace(/\.[^/.]+$/, ""));
        formData.append("alt", item.filename.replace(/\.[^/.]+$/, ""));
        formData.append("caption", "");
        formData.append("description", "");

        $.ajax({
          url: flexpressGallery.ajaxUrl,
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          xhr: () => {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener(
              "progress",
              (evt) => {
                if (evt.lengthComputable) {
                  const percentComplete = (evt.loaded / evt.total) * 100;
                  this.updateItemProgress(item.id, percentComplete);
                }
              },
              false
            );
            return xhr;
          },
          success: (response) => {
            if (response.success) {
              console.log("Upload successful:", response.data);
              this.updateItemStatus(item.id, "success", "Upload complete");
              this.updateItemProgress(item.id, 100);
              resolve(response.data);
            } else {
              reject(new Error(response.data || "Upload failed"));
            }
          },
          error: (xhr, status, error) => {
            console.error("Upload error:", error);
            reject(new Error(`Upload failed: ${error}`));
          },
        });
      });
    }

    updateItemStatus(itemId, status, message) {
      const item = this.uploadQueue.find((q) => q.id === itemId);
      if (item) {
        item.status = status;
        item.error = status === "failed" ? message : null;
      }

      const $item = $(`.upload-item[data-id="${itemId}"]`);
      const $icon = $item.find(".upload-status-icon");
      const $status = $item.find(".upload-item-status");
      const $actions = $item.find(".upload-item-actions");

      // Update icon
      $icon.removeClass("pending uploading success failed");
      $icon.addClass(status);

      switch (status) {
        case "pending":
          $icon.text("⏳");
          break;
        case "uploading":
          $icon.text("🔵");
          break;
        case "success":
          $icon.text("✅");
          break;
        case "failed":
          $icon.text("❌");
          $actions.show();
          break;
      }

      $status.text(message);
    }

    updateItemProgress(itemId, percent) {
      const $item = $(`.upload-item[data-id="${itemId}"]`);
      $item.find(".upload-item-fill").css("width", percent + "%");
    }

    updateOverallProgress() {
      const total = this.uploadQueue.length;
      const completed = this.uploadQueue.filter(
        (item) => item.status === "success"
      ).length;
      const percentage = total > 0 ? (completed / total) * 100 : 0;

      this.updateProgress(percentage);
    }

    checkUploadComplete() {
      const total = this.uploadQueue.length;
      const completed = this.uploadQueue.filter(
        (item) => item.status === "success"
      ).length;
      const failed = this.uploadQueue.filter(
        (item) => item.status === "failed"
      ).length;

      if (completed === total) {
        // All uploads successful
        this.hideProgress();
        this.refreshGallery();
        this.showSuccessMessage(`All ${total} images uploaded successfully!`);
      } else if (failed > 0) {
        // Some uploads failed
        this.showRetryButton();
        this.showErrorMessage(
          `${failed} of ${total} uploads failed. You can retry the failed uploads.`
        );
      }
    }

    showSuccessMessage(message) {
      const prefix = flexpressGallery.postType === "extras" ? "extras-" : "";
      $(`#${prefix}upload-progress-items`).append(`
        <div class="upload-complete-message success">
          <strong>✅ ${message}</strong>
          <button type="button" class="button button-primary done-upload">Done</button>
        </div>
      `);
    }

    showErrorMessage(message) {
      const prefix = flexpressGallery.postType === "extras" ? "extras-" : "";
      $(`#${prefix}upload-progress-items`).append(`
        <div class="upload-complete-message error">
          <strong>❌ ${message}</strong>
        </div>
      `);
    }

    showRetryButton() {
      $(".retry-failed").show();
    }

    retryFailedUploads() {
      const failedItems = this.uploadQueue.filter(
        (item) => item.status === "failed"
      );
      if (failedItems.length === 0) return;

      this.isUploading = true;
      this.currentUploadIndex = 0;

      // Reset failed items to pending
      failedItems.forEach((item) => {
        item.status = "pending";
        item.retryCount++;
        this.updateItemStatus(item.id, "pending", "Retrying...");
      });

      this.processUploadQueue();
    }

    retrySingleUpload(itemId) {
      const item = this.uploadQueue.find((q) => q.id === itemId);
      if (!item || item.status !== "failed") return;

      item.status = "pending";
      item.retryCount++;
      this.updateItemStatus(item.id, "pending", "Retrying...");

      this.uploadFileSequential(item)
        .then(() => {
          this.updateItemStatus(item.id, "success", "Upload complete");
          this.updateItemProgress(item.id, 100);
          this.updateOverallProgress();
          this.checkUploadComplete();
        })
        .catch((error) => {
          this.updateItemStatus(
            item.id,
            "failed",
            error.message || "Upload failed"
          );
          this.updateOverallProgress();
          this.checkUploadComplete();
        });
    }

    cancelUpload() {
      this.isUploading = false;
      this.hideProgress();
      const prefix = flexpressGallery.postType === "extras" ? "extras-" : "";
      $(`#${prefix}upload-progress-items`).hide();
    }

    showProgress() {
      this.progressBar.show();
      this.updateProgress(0);
    }

    hideProgress() {
      this.progressBar.hide();
    }

    updateProgress(percentage) {
      this.progressFill.css("width", percentage + "%");
      this.progressText.text(Math.round(percentage) + "%");
    }

    refreshGallery() {
      // Instead of reloading the page, we could fetch the updated gallery
      // For now, we'll reload to ensure we have the latest data
      // TODO: Implement dynamic gallery update without page reload
      location.reload();
    }

    deleteImage(e) {
      e.preventDefault();

      if (!confirm(flexpressGallery.strings.deleteConfirm)) {
        return;
      }

      const $item = $(e.target).closest(".gallery-image-item");
      const imageId = $item.data("image-id");

      $.ajax({
        url: flexpressGallery.ajaxUrl,
        type: "POST",
        data: {
          action: "flexpress_delete_gallery_image",
          nonce: flexpressGallery.deleteNonce,
          post_id: flexpressGallery.postId,
          image_id: imageId,
        },
        success: (response) => {
          if (response.success) {
            $item.fadeOut(300, () => {
              $item.remove();
              this.updateOrderNumbers();
            });
          } else {
            alert("Delete failed: " + response.data);
          }
        },
        error: () => {
          alert("Delete failed. Please try again.");
        },
      });
    }

    deleteAllImages(e) {
      e.preventDefault();
      console.log("deleteAllImages function called");

      if (!confirm(flexpressGallery.strings.deleteAllConfirm)) {
        console.log("User cancelled delete all");
        return;
      }

      console.log("Sending AJAX request:", {
        action: "flexpress_delete_all_gallery_images",
        nonce: flexpressGallery.deleteAllNonce,
        post_id: flexpressGallery.postId,
      });

      $.ajax({
        url: flexpressGallery.ajaxUrl,
        type: "POST",
        data: {
          action: "flexpress_delete_all_gallery_images",
          nonce: flexpressGallery.deleteAllNonce,
          post_id: flexpressGallery.postId,
        },
        success: (response) => {
          if (response.success) {
            // Hide the delete all button
            $("#delete-all-gallery-images").hide();

            // Remove all gallery images
            this.imagesGrid.find(".gallery-image-item").fadeOut(300, () => {
              this.imagesGrid.html(
                '<p class="no-images">No gallery images uploaded yet.</p>'
              );
            });

            // Show success message
            alert(flexpressGallery.strings.deleteAllComplete);
          } else {
            alert("Delete all failed: " + response.data);
          }
        },
        error: () => {
          alert("Delete all failed. Please try again.");
        },
      });
    }

    editImage(e) {
      e.preventDefault();

      const $item = $(e.target).closest(".gallery-image-item");
      const $inputs = $item.find(".image-info input");

      // Toggle edit mode
      if ($inputs.prop("readonly")) {
        $inputs.prop("readonly", false).addClass("editing");
        $(e.target)
          .find(".dashicons")
          .removeClass("dashicons-edit")
          .addClass("dashicons-yes");
      } else {
        $inputs.prop("readonly", true).removeClass("editing");
        $(e.target)
          .find(".dashicons")
          .removeClass("dashicons-yes")
          .addClass("dashicons-edit");

        // Save changes
        this.saveImageInfo($item);
      }
    }

    saveImageInfo($item) {
      const imageId = $item.data("image-id");
      const alt = $item.find(".image-alt").val();
      const caption = $item.find(".image-caption").val();

      // Update the image info in the database
      // This would typically be done via AJAX, but for simplicity we'll just update the DOM
      // In a real implementation, you'd want to save this to the database

      console.log("Saving image info:", { imageId, alt, caption });
    }

    updateImageInfo(e) {
      const $item = $(e.target).closest(".gallery-image-item");
      const $inputs = $item.find(".image-info input");

      // Mark as modified
      $inputs.addClass("modified");
    }

    moveImage(e, direction) {
      e.preventDefault();

      const $item = $(e.target).closest(".gallery-image-item");
      const $prev = $item.prev(".gallery-image-item");
      const $next = $item.next(".gallery-image-item");

      if (direction === "up" && $prev.length) {
        $item.insertBefore($prev);
      } else if (direction === "down" && $next.length) {
        $item.insertAfter($next);
      }

      this.updateOrderNumbers();
      this.handleReorder();
    }

    updateOrderNumbers() {
      this.imagesGrid.find(".gallery-image-item").each((index, item) => {
        $(item)
          .find(".order-number")
          .text(index + 1);
      });
    }

    handleReorder() {
      const imageOrder = [];
      this.imagesGrid.find(".gallery-image-item").each((index, item) => {
        imageOrder.push($(item).data("image-id"));
      });

      $.ajax({
        url: flexpressGallery.ajaxUrl,
        type: "POST",
        data: {
          action: "flexpress_reorder_gallery_images",
          nonce: flexpressGallery.reorderNonce,
          post_id: flexpressGallery.postId,
          image_order: imageOrder,
        },
        success: (response) => {
          if (response.success) {
            console.log("Reorder successful");
          } else {
            console.error("Reorder failed:", response.data);
          }
        },
        error: () => {
          console.error("Reorder failed. Please try again.");
        },
      });
    }
  }

  // Initialize when document is ready
  $(document).ready(() => {
    if ($(".flexpress-gallery-manager").length) {
      new FlexPressGalleryAdmin();
    }
  });
})(jQuery);
