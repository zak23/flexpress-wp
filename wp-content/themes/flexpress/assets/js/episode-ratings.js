/**
 * Episode Rating System JavaScript
 * Handles interactive rating functionality
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Rating form functionality
    $('.episode-rating-form').each(function() {
        const $form = $(this);
        const episodeId = $form.data('episode-id');
        const $stars = $form.find('.rating-stars-interactive .star');
        const $ratingText = $form.find('.rating-text');
        const $comment = $form.find('textarea');
        const $removeBtn = $form.find('.remove-rating');
        
        let selectedRating = 0;
        
        // Star hover effects
        $stars.on('mouseenter', function() {
            const rating = $(this).data('rating');
            highlightStars($stars, rating);
        });
        
        $stars.on('mouseleave', function() {
            highlightStars($stars, selectedRating);
        });
        
        // Star click - 1-click rating system
        $stars.on('click', function() {
            selectedRating = $(this).data('rating');
            highlightStars($stars, selectedRating);
            updateRatingText();
            
            // Immediately submit the rating
            submitRating(episodeId, selectedRating, $comment.val());
        });
        
        // Remove rating
        $removeBtn.on('click', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to remove your rating?')) {
                removeRating(episodeId);
            }
        });
        
        function highlightStars($stars, rating) {
            $stars.removeClass('filled half-filled');
            $stars.each(function(index) {
                const starRating = index + 1;
                if (starRating <= rating) {
                    $(this).addClass('filled');
                } else if (starRating - 0.5 <= rating) {
                    $(this).addClass('half-filled');
                }
            });
        }
        
        function updateRatingText() {
            if (selectedRating > 0) {
                const text = selectedRating === 1 ? 'Your rating: 1 star' : `Your rating: ${selectedRating} stars`;
                $ratingText.text(text);
            } else {
                $ratingText.text('Click a star to rate');
            }
        }
        
        // Initialize with current rating if exists
        const currentRating = $form.find('.rating-stars-interactive .star.filled').length;
        if (currentRating > 0) {
            selectedRating = currentRating;
            highlightStars($stars, selectedRating);
            updateRatingText();
        }
    });
    
    // Load more ratings
    $('.load-more-ratings').on('click', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const episodeId = $btn.data('episode-id');
        const currentPage = parseInt($btn.data('current-page')) || 1;
        const nextPage = currentPage + 1;
        
        $btn.prop('disabled', true).text('Loading...');
        
        loadRatings(episodeId, nextPage, function(ratings, hasMore) {
            // Append new ratings to the list
            const $ratingsList = $('.episode-ratings-list');
            ratings.forEach(function(rating) {
                const ratingHtml = createRatingHtml(rating);
                $ratingsList.append(ratingHtml);
            });
            
            // Update button state
            if (hasMore) {
                $btn.data('current-page', nextPage).prop('disabled', false).text('Load More Ratings');
            } else {
                $btn.hide();
            }
        });
    });
    
    function submitRating(episodeId, rating, comment) {
        const $form = $('.episode-rating-form[data-episode-id="' + episodeId + '"]');
        const $stars = $form.find('.rating-stars-interactive .star');
        
        // Disable stars during submission to prevent multiple clicks
        $stars.addClass('disabled').css('pointer-events', 'none');
        
        $.ajax({
            url: flexpress_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_episode_rating',
                episode_id: episodeId,
                rating: rating,
                comment: comment,
                nonce: flexpress_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    
                    // Update rating stats
                    updateRatingStats(episodeId, response.data.stats);
                    
                    // Show remove button if it was hidden
                    $form.find('.remove-rating').show();
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred while submitting your rating.', 'error');
            },
            complete: function() {
                // Re-enable stars after submission
                $stars.removeClass('disabled').css('pointer-events', 'auto');
            }
        });
    }
    
    function removeRating(episodeId) {
        const $form = $('.episode-rating-form[data-episode-id="' + episodeId + '"]');
        const $removeBtn = $form.find('.remove-rating');
        
        $removeBtn.prop('disabled', true).text('Removing...');
        
        $.ajax({
            url: flexpress_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'remove_episode_rating',
                episode_id: episodeId,
                nonce: flexpress_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Rating removed successfully.', 'success');
                    
                    // Reset form
                    $form.find('.rating-stars-interactive .star').removeClass('filled half-filled');
                    $form.find('.rating-text').text('Click a star to rate');
                    $form.find('textarea').val('');
                    $form.find('.remove-rating').hide();
                    
                    // Update rating stats
                    updateRatingStats(episodeId, response.data.stats);
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred while removing your rating.', 'error');
            },
            complete: function() {
                $removeBtn.prop('disabled', false).text('Remove Rating');
            }
        });
    }
    
    function loadRatings(episodeId, page, callback) {
        $.ajax({
            url: flexpress_ajax.ajax_url,
            type: 'GET',
            data: {
                action: 'get_episode_ratings',
                episode_id: episodeId,
                page: page,
                per_page: 10
            },
            success: function(response) {
                if (response.success) {
                    const ratings = response.data.ratings;
                    const hasMore = ratings.length === 10; // Assuming 10 per page
                    callback(ratings, hasMore);
                } else {
                    showMessage('Failed to load ratings.', 'error');
                }
            },
            error: function() {
                showMessage('An error occurred while loading ratings.', 'error');
            }
        });
    }
    
    function updateRatingStats(episodeId, stats) {
        const $statsContainer = $('.episode-rating-stats');
        
        if (stats.total_ratings === 0) {
            $statsContainer.html('<p class="text-muted">No ratings yet.</p>');
            return;
        }
        
        // Update average rating display
        $statsContainer.find('.rating-number').text(stats.average_rating);
        $statsContainer.find('.rating-count').text(`(${stats.total_ratings} ${stats.total_ratings === 1 ? 'rating' : 'ratings'})`);
        
        // Update star display
        const $stars = $statsContainer.find('.rating-stars .star');
        $stars.removeClass('filled half-filled');
        $stars.each(function(index) {
            const starRating = index + 1;
            if (starRating <= stats.average_rating) {
                $(this).addClass('filled');
            } else if (starRating - 0.5 <= stats.average_rating) {
                $(this).addClass('half-filled');
            }
        });
        
        // Update distribution bars
        Object.keys(stats.rating_distribution).forEach(function(rating) {
            const count = stats.rating_distribution[rating];
            const percentage = (count / stats.total_ratings) * 100;
            const $bar = $statsContainer.find(`.rating-bar:nth-child(${6 - rating}) .progress-bar`);
            $bar.css('width', percentage + '%');
            $statsContainer.find(`.rating-bar:nth-child(${6 - rating}) .rating-count`).text(count);
        });
    }
    
    function createRatingHtml(rating) {
        const stars = createStarsHtml(rating.rating);
        const date = new Date(rating.created_at).toLocaleDateString();
        
        return `
            <div class="rating-item border-bottom pb-3 mb-3">
                <div class="rating-header d-flex justify-content-between align-items-center mb-2">
                    <div class="rating-stars">${stars}</div>
                    <small class="text-muted">${date}</small>
                </div>
                <div class="rating-user mb-2">
                    <strong>${rating.user_name}</strong>
                </div>
                ${rating.comment ? `<div class="rating-comment">${rating.comment}</div>` : ''}
            </div>
        `;
    }
    
    function createStarsHtml(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            const filled = i <= rating ? 'filled' : '';
            stars += `<span class="star ${filled}"></span>`;
        }
        return stars;
    }
    
    function showMessage(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert
        $('.episode-rating-form').before(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
