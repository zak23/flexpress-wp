// Join Page Script (migrated from join.php inline)
// Depends on: jquery

(function($) {
  $(function() {
    // Localized data
    const promoData = window.flexpressPromo || {};
    const joinFormData = window.flexpressJoinForm || {};

    let appliedPromo = null;
    const urlPromoCode = joinFormData.urlPromoCode || '';
    if (urlPromoCode) {
      appliedPromo = { code: urlPromoCode };
    }

    let selectedPlan = null;
    let currentPlanType = 'recurring';

    function updateLegalText($plan) {
      const planType = $plan.data('plan-type');
      const planCurrency = $plan.data('plan-currency');
      const planDuration = Number($plan.data('duration')) || 0;
      const planDurationUnit = String($plan.data('duration-unit') || 'days');
      let planPrice = Number($plan.data('plan-price')) || 0;
      const $billingText = $('#billing-text');

      const originalPrice = $plan.find('.price-amount').data('original-price');
      const hasPromoDiscount = originalPrice && Number(originalPrice) > planPrice;

      const trialEnabled = String($plan.data('trial-enabled')) === '1';
      const trialPrice = Number($plan.data('trial-price')) || 0;
      const trialDuration = Number($plan.data('trial-duration')) || 0;
      const trialDurationUnit = String($plan.data('trial-duration-unit') || 'days');

      function pluralize(n, unit) {
        if (n === 1) return unit;
        if (unit === 'day') return 'days';
        if (unit === 'week') return 'weeks';
        if (unit === 'month') return 'months';
        if (unit === 'year') return 'years';
        return unit;
      }

      const durationText = planDuration + ' ' + pluralize(planDuration, planDurationUnit);
      const trialDurationText = trialDuration > 0
        ? (trialDuration + ' ' + pluralize(trialDuration, trialDurationUnit))
        : '';

      let priceText = planCurrency + planPrice.toFixed(2);
      if (hasPromoDiscount) priceText += ' (promo discount applied)';

      let billingText = '';
      if (planType === 'recurring') {
        if (trialEnabled && trialPrice === 0 && trialDuration > 0) {
          billingText = "You're signing up for a " + trialDurationText + " free trial. After the trial, your access will be terminated and you will be prompted to purchase access";
        } else if (trialEnabled && trialPrice > 0 && trialDuration > 0) {
          billingText = 'Your subscription starts with a ' + trialDurationText + ' trial for ' + planCurrency + trialPrice.toFixed(2) + ', then automatically renews at ' + priceText + ' every ' + durationText + ' unless cancelled. You may cancel at any time';
        } else {
          billingText = 'Your subscription will automatically renew at ' + priceText + ' every ' + durationText + ' unless cancelled. You may cancel at any time';
        }
      } else if (planType === 'one_time') {
        billingText = 'This is a one-time payment of ' + priceText + '. No recurring charges will be applied.';
      } else if (planType === 'lifetime') {
        billingText = 'This is a one-time payment of ' + priceText + ' for lifetime access. No recurring charges will be applied.';
      } else {
        if (trialEnabled && trialPrice === 0 && trialDuration > 0) {
          billingText = "You're signing up for a " + trialDurationText + " free trial. After the trial, your access will be terminated and you will be prompted to purchase access";
        } else if (trialEnabled && trialPrice > 0 && trialDuration > 0) {
          billingText = 'Your subscription starts with a ' + trialDurationText + ' trial for ' + planCurrency + trialPrice.toFixed(2) + ', then automatically renews at ' + priceText + ' every ' + durationText + ' unless cancelled. You may cancel at any time';
        } else {
          billingText = 'Your subscription will automatically renew at ' + priceText + ' every ' + durationText + ' unless cancelled. You may cancel at any time';
        }
      }

      $billingText.text(billingText);

      const $dashboardLink = $('#dashboard-link-section');
      if (planType === 'recurring') $dashboardLink.show(); else $dashboardLink.hide();

      updateTrialDisclaimer(trialEnabled, trialPrice, trialDuration, trialDurationUnit,
        planCurrency, planPrice, planDuration, planDurationUnit);
    }

    function updateTrialDisclaimer(trialEnabled, trialPrice, trialDuration, trialDurationUnit,
      planCurrency, planPrice, planDuration, planDurationUnit) {
      const $trialDisclaimer = $('#trial-disclaimer');
      const $trialText = $('#trial-text-content');

      function pluralize(n, unit) {
        if (n === 1) return unit;
        if (unit === 'day') return 'days';
        if (unit === 'week') return 'weeks';
        if (unit === 'month') return 'months';
        if (unit === 'year') return 'years';
        return unit;
      }

      if (trialEnabled && trialPrice > 0 && trialDuration > 0) {
        const trialDurationText = trialDuration + ' ' + pluralize(trialDuration, trialDurationUnit);
        const billingDurationText = planDuration + ' ' + pluralize(planDuration, planDurationUnit);
        const trialText = '* Limited Access ' + trialDurationText + ' trial automatically rebilling at ' + planCurrency + Number(planPrice).toFixed(2) + ' every ' + billingDurationText + ' until cancelled';
        $trialText.text(trialText);
        $trialDisclaimer.show();
      } else {
        $trialDisclaimer.hide();
      }
    }

    // Toggle between recurring / one-time
    $('.toggle-btn[data-plan-type]').on('click', function() {
      const planType = $(this).data('plan-type');
      currentPlanType = planType;
      $('.toggle-btn[data-plan-type]').removeClass('active');
      $(this).addClass('active');
      $('.membership-plan-item').hide();
      $('.membership-plan-item[data-plan-type="' + planType + '"]').show();
      $('.membership-plan-item').removeClass('selected');
      $('.membership-plan-item.popular-plan').removeClass('no-highlight');
      selectedPlan = null;
    });

    // Plan selection
    $('.membership-plan-item').on('click', function() {
      $('.membership-plan-item').removeClass('selected');
      $('.membership-plan-item.popular-plan').removeClass('no-highlight');
      $(this).addClass('selected');
      $('.membership-plan-item.popular-plan:not(.selected)').addClass('no-highlight');
      selectedPlan = $(this);
      updateLegalText(selectedPlan);
    });

    // Continue button
    $('#membership-continue-btn').on('click', function(e) {
      e.preventDefault();
      const isTrialLink = !!joinFormData.isTrialLink;
      if (!isTrialLink && !selectedPlan) { alert('Please select a membership plan first.'); return; }
      const planId = isTrialLink ? null : selectedPlan.data('plan-id');
      const isLoggedIn = !!joinFormData.isLoggedIn;
      if (isLoggedIn) {
        if (isTrialLink) return;
        let paymentUrl = (joinFormData.paymentUrlBase || '/payment') + '?plan=' + encodeURIComponent(planId);
        if (appliedPromo && appliedPromo.code) paymentUrl += '&promo=' + encodeURIComponent(appliedPromo.code);
        window.location.href = paymentUrl;
      } else {
        const email = $('#reg-email').val();
        const password = $('#reg-password').val();
        const confirmPassword = $('#reg-confirm-password').val();
        const termsAccepted = $('#reg-terms').is(':checked');
        if (!email || !password || !confirmPassword) { alert('Please fill in all fields.'); return; }
        if (password !== confirmPassword) { alert('Passwords do not match.'); return; }
        if (!termsAccepted) { alert('Please accept the terms and conditions.'); return; }
        const ajaxData = {
          action: 'flexpress_process_registration_and_payment',
          nonce: joinFormData.nonce || '',
          email, password,
          selected_plan: isTrialLink ? '' : planId,
          applied_promo_code: appliedPromo ? appliedPromo.code : '',
          trial_token: joinFormData.trialToken || ''
        };
        $.ajax({ url: joinFormData.ajaxurl, type: 'POST', data: ajaxData })
          .done(function(response) {
            if (response && response.success) {
              if (response.data && response.data.payment_url) {
                window.location.href = response.data.payment_url;
              } else {
                window.location.href = (joinFormData.dashboardUrl || '/dashboard/');
              }
            } else {
              alert('Registration failed: ' + ((response && response.data && response.data.message) || 'Unknown error'));
            }
          })
          .fail(function() { alert('An error occurred during registration. Please try again.'); });
      }
    });

    // Init state (hide non-recurring by default when not trial)
    if (!joinFormData.isTrialLink) {
      $('.membership-plan-item').hide();
      $('.membership-plan-item[data-plan-type="recurring"]').show();
      const $popular = $('.membership-plan-item.popular-plan:visible').first();
      if ($popular.length) {
        $('.membership-plan-item').removeClass('selected');
        $('.membership-plan-item.popular-plan').removeClass('no-highlight');
        $popular.addClass('selected');
        $('.membership-plan-item.popular-plan:not(.selected)').addClass('no-highlight');
        selectedPlan = $popular;
        updateLegalText($popular);
      }
    }

    // Promo handlers
    $('#membership-promo-code').on('keypress', function(e) {
      if (e.which === 13) { e.preventDefault(); $('#apply-membership-promo').click(); }
    });
    $('.promo-code-label').on('click', function() { $('.promo-code-input').toggleClass('show'); });
    $('#apply-membership-promo').on('click', function() {
      const code = ($('#membership-promo-code').val() || '').trim();
      if (!code) { showPromoMessage('Please enter a promo code', 'error'); return; }
      $.ajax({ url: promoData.ajaxurl, type: 'POST', data: { action: 'apply_promo_code', code: code, nonce: promoData.nonce } })
        .done(function(response) {
          if (response && response.success) {
            appliedPromo = response.data;
            showPromoMessage('Promo code "' + response.data.code + '" applied! You saved ' + response.data.discount_value + '% on your subscription.', 'success');
            $('#membership-promo-code').val('');
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('promo', response.data.code);
            window.location.href = currentUrl.toString();
          } else {
            showPromoMessage((response && response.data && response.data.message) || 'Invalid promo code', 'error');
          }
        })
        .fail(function() { showPromoMessage('An error occurred. Please try again.', 'error'); });
    });

    function showPromoMessage(message, type) {
      const $msg = $('#membership-promo-message');
      $msg.removeClass('success error info').addClass(type);
      $msg.html('<div class="promo-applied-message text-' + type + '"><i class="fas fa-check-circle me-1"></i>' + message + '</div>').show();
      setTimeout(function() { $msg.hide(); }, 5000);
    }

    // Store original prices for discount indicator
    $('.membership-plan-item .price-amount').each(function() {
      const originalPrice = parseFloat($(this).text().replace(/[^0-9.]/g, ''));
      $(this).data('original-price', originalPrice);
    });
  });
})(jQuery);


