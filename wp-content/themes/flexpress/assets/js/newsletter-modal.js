/* global jQuery, flexpressNewsletter, turnstile */
(function () {
  // Guard until jQuery is ready
  function onReady(fn) {
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      setTimeout(fn, 0);
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  }

  onReady(function () {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    var NEWSLETTER_LS_KEY = 'flexpress_newsletter_modal_dismissed';
    var modalSelector = '#newsletterModal';

    function hasDismissedNewsletterModal() {
      try {
        return window.localStorage && localStorage.getItem(NEWSLETTER_LS_KEY) === 'true';
      } catch (e) {
        return false;
      }
    }

    function markNewsletterModalDismissed() {
      try {
        if (window.localStorage) {
          localStorage.setItem(NEWSLETTER_LS_KEY, 'true');
        }
      } catch (e) {}
    }

    function showNewsletterModal() {
      // Prefer Bootstrap 5 native API to avoid jQuery plugin dependency
      try {
        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
          var modalEl = document.querySelector(modalSelector);
          if (modalEl) {
            var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
            return;
          }
        }
      } catch (e) {
        // fall through to jQuery-based call
      }
      // Fallback to jQuery plugin if available
      if ($(modalSelector).length && typeof $(modalSelector).modal === 'function') {
        $(modalSelector).modal('show');
      }
    }

    // Only proceed if modal exists in DOM
    if (!document.querySelector(modalSelector)) return;

    // Render Turnstile on show (if enabled)
    var flexpressNewsletterWidgetId = null;
    var flexpressTurnstileRendered = false;
    $(modalSelector).on('shown.bs.modal', function () {
      if (!flexpressNewsletter || !flexpressNewsletter.turnstileEnabled) return;
      try {
        if (window.turnstile && !flexpressTurnstileRendered) {
          flexpressNewsletterWidgetId = turnstile.render('#newsletter-turnstile', {
            sitekey: flexpressNewsletter.turnstileSiteKey || '',
            theme: flexpressNewsletter.turnstileTheme || 'auto',
            size: flexpressNewsletter.turnstileSize || 'normal',
            callback: 'flexpressNewsletterTurnstileCallback',
            'expired-callback': 'flexpressNewsletterTurnstileExpired',
            'error-callback': 'flexpressNewsletterTurnstileError'
          });
          flexpressTurnstileRendered = true;
        }
      } catch (e) {
        // eslint-disable-next-line no-console
        console.error('Turnstile render error:', e);
      }
    });

    // Persist dismissal on hide
    $(modalSelector).on('hidden.bs.modal', function () {
      markNewsletterModalDismissed();
    });

    // Schedule show if not dismissed
    if (!hasDismissedNewsletterModal()) {
      var ageVerified = false;
      try {
        ageVerified = localStorage.getItem('flexpress_age_verified') === 'true';
      } catch (e) {
        ageVerified = false;
      }
      var delay = (flexpressNewsletter && flexpressNewsletter.modalDelayMs) || 5000;
      if (ageVerified) {
        setTimeout(showNewsletterModal, delay);
      } else {
        document.addEventListener(
          'flexpress:ageVerified',
          function () {
            setTimeout(showNewsletterModal, delay);
          },
          { once: true }
        );
      }
    }

    // Handle form submit
    $('#newsletterForm').on('submit', function (e) {
      e.preventDefault();
      var $form = $(this);
      var $submitBtn = $form.find('button[type="submit"]');
      var email = $('#newsletterEmail').val();
      if (!email) return;

      // Turnstile checks
      if (flexpressNewsletter && flexpressNewsletter.turnstileEnabled) {
        if (!window.turnstile) {
          alert('Security system not loaded. Please wait a moment and try again.');
          return;
        }
      }
      var token = null;
      if (flexpressNewsletter && flexpressNewsletter.turnstileEnabled) {
        try {
          token = flexpressNewsletterWidgetId ? turnstile.getResponse(flexpressNewsletterWidgetId) : turnstile.getResponse();
        } catch (err) {
          // eslint-disable-next-line no-console
          console.error('Turnstile getResponse error:', err);
        }
        if (!token) {
          alert('Please complete the security verification');
          return;
        }
      }

      $submitBtn.prop('disabled', true).text('Subscribing...');
      var ajaxData = {
        action: 'plunk_newsletter_signup',
        email: email,
        website: $form.find('input[name=\"website\"]').val()
      };
      if (flexpressNewsletter && flexpressNewsletter.turnstileEnabled) {
        ajaxData['cf-turnstile-response'] = token;
      }

      $.ajax({
        url: (flexpressNewsletter && flexpressNewsletter.ajaxurl) || '',
        type: 'POST',
        data: ajaxData,
        success: function (response) {
          if (response && response.success) {
            var successHtml =
              '<div class=\"text-center newsletter-success\">' +
              '<div class=\"bg-white rounded p-4\">' +
              '<h4 class=\"text-pink mb-3\">' +
              (response.data && response.data.message ? response.data.message : 'Subscribed!') +
              '</h4>' +
              '<p class=\"text-dark mb-0\">Please check your email to confirm your subscription.</p>' +
              '</div>' +
              '</div>';
            $form.html(successHtml);
            setTimeout(function () {
              try {
                if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                  var modalEl = document.querySelector(modalSelector);
                  if (modalEl) {
                    var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.hide();
                    return;
                  }
                }
              } catch (e) {}
              if (typeof $(modalSelector).modal === 'function') {
                $(modalSelector).modal('hide');
              }
            }, 3000);
          } else {
            $submitBtn.prop('disabled', false).text('Subscribe Now');
            try {
              if (flexpressNewsletter && flexpressNewsletter.turnstileEnabled && window.turnstile) {
                turnstile.reset(flexpressNewsletterWidgetId || undefined);
              }
            } catch (e) {}
            $form.prepend('<div class=\"alert alert-danger\">' + (response && response.data ? response.data : 'An error occurred.') + '</div>');
          }
        },
        error: function () {
          $submitBtn.prop('disabled', false).text('Subscribe Now');
          try {
            if (flexpressNewsletter && flexpressNewsletter.turnstileEnabled && window.turnstile) {
              turnstile.reset(flexpressNewsletterWidgetId || undefined);
            }
          } catch (e) {}
          $form.prepend('<div class=\"alert alert-danger\">An error occurred. Please try again.</div>');
        }
      });
    });

    // Expose callbacks for Turnstile
    window.flexpressNewsletterTurnstileCallback = function (token) {
      // eslint-disable-next-line no-console
      console.log('Newsletter Turnstile token received:', token);
    };
    window.flexpressNewsletterTurnstileExpired = function () {
      // eslint-disable-next-line no-console
      console.log('Newsletter Turnstile token expired');
    };
    window.flexpressNewsletterTurnstileError = function (error) {
      // eslint-disable-next-line no-console
      console.log('Newsletter Turnstile error:', error);
    };
  });
})();


