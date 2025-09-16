/**
 * FlexPress Gallery Admin JavaScript
 */

(function($) {
    'use strict';
    
    // Gallery Admin Class
    class FlexPressGalleryAdmin {
        constructor() {
            this.uploadArea = $('#gallery-upload-area');
            this.fileInput = $('#gallery-file-input');
            this.progressBar = $('#upload-progress');
            this.progressFill = $('#progress-fill');
            this.progressText = $('#progress-text');
            this.imagesGrid = $('#gallery-images-grid');
            this.selectButton = $('#select-gallery-images');
            
            this.init();
        }
        
        init() {
            console.log('FlexPress Gallery Admin initialized');
            this.bindEvents();
            this.initSortable();
        }
        
        bindEvents() {
            // File selection button
            this.selectButton.on('click', () => this.openFileSelector());
            
            // Drag and drop events
            this.uploadArea.on('dragover', (e) => this.handleDragOver(e));
            this.uploadArea.on('dragleave', (e) => this.handleDragLeave(e));
            this.uploadArea.on('drop', (e) => this.handleDrop(e));
            
            // File input change
            this.fileInput.on('change', (e) => this.handleFileSelect(e));
            
            // Image management events
            this.imagesGrid.on('click', '.delete-image', (e) => this.deleteImage(e));
            this.imagesGrid.on('click', '.edit-image', (e) => this.editImage(e));
            this.imagesGrid.on('click', '.move-up', (e) => this.moveImage(e, 'up'));
            this.imagesGrid.on('click', '.move-down', (e) => this.moveImage(e, 'down'));
            this.imagesGrid.on('change', '.image-alt, .image-caption', (e) => this.updateImageInfo(e));
            
            // Delete all button
            $(document).on('click', '#delete-all-gallery-images', (e) => {
                console.log('Delete all button clicked');
                this.deleteAllImages(e);
            });
        }
        
        initSortable() {
            this.imagesGrid.sortable({
                handle: '.image-order',
                axis: 'y',
                update: (event, ui) => this.handleReorder()
            });
        }
        
        openFileSelector() {
            this.fileInput.click();
        }
        
        handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            this.uploadArea.addClass('dragover');
        }
        
        handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            this.uploadArea.removeClass('dragover');
        }
        
        handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            this.uploadArea.removeClass('dragover');
            
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
            this.showProgress();
            
            let uploaded = 0;
            const total = files.length;
            
            Array.from(files).forEach((file, index) => {
                this.uploadFile(file, (success) => {
                    uploaded++;
                    this.updateProgress((uploaded / total) * 100);
                    
                    if (uploaded === total) {
                        this.hideProgress();
                        this.refreshGallery();
                    }
                });
            });
        }
        
        uploadFile(file, callback) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                console.error('Invalid file type:', file.type);
                callback(false);
                return;
            }
            
            // Validate file size (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                console.error('File too large:', file.size);
                callback(false);
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'flexpress_upload_gallery_image');
            formData.append('nonce', flexpressGallery.nonce);
            formData.append('post_id', flexpressGallery.postId);
            formData.append('image', file);
            formData.append('title', file.name.replace(/\.[^/.]+$/, ''));
            formData.append('alt', file.name.replace(/\.[^/.]+$/, ''));
            formData.append('caption', '');
            formData.append('description', '');
            
            $.ajax({
                url: flexpressGallery.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        console.log('Upload successful:', response.data);
                        callback(true);
                    } else {
                        console.error('Upload failed:', response.data);
                        callback(false);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Upload error:', error);
                    callback(false);
                }
            });
        }
        
        showProgress() {
            this.progressBar.show();
            this.updateProgress(0);
        }
        
        hideProgress() {
            this.progressBar.hide();
        }
        
        updateProgress(percentage) {
            this.progressFill.css('width', percentage + '%');
            this.progressText.text(Math.round(percentage) + '%');
        }
        
        refreshGallery() {
            // Reload the page to show new images
            location.reload();
        }
        
        deleteImage(e) {
            e.preventDefault();
            
            if (!confirm(flexpressGallery.strings.deleteConfirm)) {
                return;
            }
            
            const $item = $(e.target).closest('.gallery-image-item');
            const imageId = $item.data('image-id');
            
            $.ajax({
                url: flexpressGallery.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flexpress_delete_gallery_image',
                    nonce: flexpressGallery.deleteNonce,
                    post_id: flexpressGallery.postId,
                    image_id: imageId
                },
                success: (response) => {
                    if (response.success) {
                        $item.fadeOut(300, () => {
                            $item.remove();
                            this.updateOrderNumbers();
                        });
                    } else {
                        alert('Delete failed: ' + response.data);
                    }
                },
                error: () => {
                    alert('Delete failed. Please try again.');
                }
            });
        }
        
        deleteAllImages(e) {
            e.preventDefault();
            console.log('deleteAllImages function called');
            
            if (!confirm(flexpressGallery.strings.deleteAllConfirm)) {
                console.log('User cancelled delete all');
                return;
            }
            
            console.log('Sending AJAX request:', {
                action: 'flexpress_delete_all_gallery_images',
                nonce: flexpressGallery.deleteAllNonce,
                post_id: flexpressGallery.postId
            });
            
            $.ajax({
                url: flexpressGallery.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flexpress_delete_all_gallery_images',
                    nonce: flexpressGallery.deleteAllNonce,
                    post_id: flexpressGallery.postId
                },
                success: (response) => {
                    if (response.success) {
                        // Hide the delete all button
                        $('#delete-all-gallery-images').hide();
                        
                        // Remove all gallery images
                        this.imagesGrid.find('.gallery-image-item').fadeOut(300, () => {
                            this.imagesGrid.html('<p class="no-images">No gallery images uploaded yet.</p>');
                        });
                        
                        // Show success message
                        alert(flexpressGallery.strings.deleteAllComplete);
                    } else {
                        alert('Delete all failed: ' + response.data);
                    }
                },
                error: () => {
                    alert('Delete all failed. Please try again.');
                }
            });
        }
        
        editImage(e) {
            e.preventDefault();
            
            const $item = $(e.target).closest('.gallery-image-item');
            const $inputs = $item.find('.image-info input');
            
            // Toggle edit mode
            if ($inputs.prop('readonly')) {
                $inputs.prop('readonly', false).addClass('editing');
                $(e.target).find('.dashicons').removeClass('dashicons-edit').addClass('dashicons-yes');
            } else {
                $inputs.prop('readonly', true).removeClass('editing');
                $(e.target).find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-edit');
                
                // Save changes
                this.saveImageInfo($item);
            }
        }
        
        saveImageInfo($item) {
            const imageId = $item.data('image-id');
            const alt = $item.find('.image-alt').val();
            const caption = $item.find('.image-caption').val();
            
            // Update the image info in the database
            // This would typically be done via AJAX, but for simplicity we'll just update the DOM
            // In a real implementation, you'd want to save this to the database
            
            console.log('Saving image info:', { imageId, alt, caption });
        }
        
        updateImageInfo(e) {
            const $item = $(e.target).closest('.gallery-image-item');
            const $inputs = $item.find('.image-info input');
            
            // Mark as modified
            $inputs.addClass('modified');
        }
        
        moveImage(e, direction) {
            e.preventDefault();
            
            const $item = $(e.target).closest('.gallery-image-item');
            const $prev = $item.prev('.gallery-image-item');
            const $next = $item.next('.gallery-image-item');
            
            if (direction === 'up' && $prev.length) {
                $item.insertBefore($prev);
            } else if (direction === 'down' && $next.length) {
                $item.insertAfter($next);
            }
            
            this.updateOrderNumbers();
            this.handleReorder();
        }
        
        updateOrderNumbers() {
            this.imagesGrid.find('.gallery-image-item').each((index, item) => {
                $(item).find('.order-number').text(index + 1);
            });
        }
        
        handleReorder() {
            const imageOrder = [];
            this.imagesGrid.find('.gallery-image-item').each((index, item) => {
                imageOrder.push($(item).data('image-id'));
            });
            
            $.ajax({
                url: flexpressGallery.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flexpress_reorder_gallery_images',
                    nonce: flexpressGallery.reorderNonce,
                    post_id: flexpressGallery.postId,
                    image_order: imageOrder
                },
                success: (response) => {
                    if (response.success) {
                        console.log('Reorder successful');
                    } else {
                        console.error('Reorder failed:', response.data);
                    }
                },
                error: () => {
                    console.error('Reorder failed. Please try again.');
                }
            });
        }
    }
    
    // Initialize when document is ready
    $(document).ready(() => {
        if ($('.flexpress-gallery-manager').length) {
            new FlexPressGalleryAdmin();
        }
    });
    
})(jQuery);
