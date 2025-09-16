/**
 * Join Carousel Admin JavaScript
 * Handles adding/removing slides and media uploads
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        var $slideContainer = $('#carousel-slides');
        var $slideTemplate = $('#slide-template').html();
        var slideIndex = $('.carousel-slide').length;
        var isAddingSlide = false; // Flag to prevent multiple rapid additions
        
        // Carousel interval preview
        var $intervalField = $('#carousel_interval');
        if ($intervalField.length) {
            // Add preview text that updates when value changes
            var $previewText = $('<span class="interval-preview" style="margin-left: 10px; font-style: italic;"></span>');
            $intervalField.after($previewText);
            
            // Update preview text immediately and on change
            function updateIntervalPreview() {
                var seconds = parseInt($intervalField.val(), 10) || 5;
                var slides = $('.carousel-slide').length || 3;
                var totalTime = seconds * slides;
                
                $previewText.text('Each slide: ' + seconds + 's, Full rotation: ' + totalTime + 's');
            }
            
            $intervalField.on('input change', updateIntervalPreview);
            updateIntervalPreview(); // Initialize
        }

        // Add new slide
        $('#add-slide-btn').on('click', function() {
            // Prevent multiple rapid clicks
            if (isAddingSlide) {
                return;
            }
            
            isAddingSlide = true;
            
            var newSlide = $slideTemplate.replace(/INDEX/g, slideIndex);
            $slideContainer.append(newSlide);
            
            // Update slide title
            $slideContainer.find('.carousel-slide').last().find('h4').text('Slide ' + (slideIndex + 1));
            
            // Increment index for next slide
            slideIndex++;
            
            // Rebind events for the new slide
            bindSlideEvents();
            
            // Update interval preview
            if ($intervalField.length) {
                updateIntervalPreview();
            }
            
            // Focus on the image field to encourage immediate filling
            $slideContainer.find('.carousel-slide').last().find('.slide-image').focus();
            
            // Reset the flag after a short delay
            setTimeout(function() {
                isAddingSlide = false;
            }, 300);
        });

        // Bind events to slide elements
        function bindSlideEvents() {
            // Remove slide
            $('.remove-slide-btn').off('click').on('click', function() {
                var $slide = $(this).closest('.carousel-slide');
                
                // Confirm removal
                if (confirm('Are you sure you want to remove this slide?')) {
                    $slide.fadeOut(300, function() {
                        $(this).remove();
                        // Re-index remaining slides
                        updateSlideIndices();
                        
                        // Update interval preview
                        if ($intervalField.length) {
                            updateIntervalPreview();
                        }
                    });
                }
            });
            
            // Image upload
            $('.upload-image-btn').off('click').on('click', function() {
                var $button = $(this);
                var $imageField = $button.closest('p').find('.slide-image');
                var $previewContainer = $button.closest('.carousel-slide').find('.slide-preview');
                
                // Create media frame
                var frame = wp.media({
                    title: 'Select or Upload Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
                
                // When image selected
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $imageField.val(attachment.url);
                    
                    // Update preview
                    $previewContainer.html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 100px; display: block; margin-bottom: 10px;" />');
                });
                
                // Open media library
                frame.open();
            });
        }
        
        // Update indices of all slides
        function updateSlideIndices() {
            $('.carousel-slide').each(function(idx) {
                var $slide = $(this);
                
                // Update slide title
                $slide.find('h4').text('Slide ' + (idx + 1));
                
                // Update input names
                $slide.find('input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        name = name.replace(/carousel_slides\[\d+\]/, 'carousel_slides[' + idx + ']');
                        $(this).attr('name', name);
                    }
                });
                
                // Update data attribute
                $slide.attr('data-index', idx);
            });
            
            // Update slideIndex to match the current count
            slideIndex = $('.carousel-slide').length;
        }
        
        // Initialize events
        bindSlideEvents();
    });
})(jQuery); 