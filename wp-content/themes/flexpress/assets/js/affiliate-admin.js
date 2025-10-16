/**
 * FlexPress Affiliate Admin Dashboard
 */
jQuery(document).ready(function ($) {
  "use strict";

  // Wrap everything in try-catch to prevent script errors
  try {
    // Tab switching functionality
    $(".nav-tab").on("click", function (e) {
      e.preventDefault();

      const targetTab = $(this).attr("href").substring(1); // Remove the #

      // Remove active class from all tabs and content
      $(".nav-tab").removeClass("nav-tab-active");
      $(".tab-content").removeClass("active");

      // Add active class to clicked tab
      $(this).addClass("nav-tab-active");

      // Show target content
      $("#" + targetTab).addClass("active");

      // Update URL hash without triggering page reload
      if (history.pushState) {
        history.pushState(null, null, "#" + targetTab);
      }
    });

    // Handle initial tab from URL hash
    setTimeout(function () {
      const hash = window.location.hash.substring(1);
      if (hash && $("#" + hash).length) {
        $('.nav-tab[href="#' + hash + '"]').click();
      }
    }, 100);

    try {
      // Only initialize if we're on the affiliate settings page
      if (!$(".nav-tab").length) {
        console.log("Affiliate admin script loaded but no tabs found");
        return;
      }
    } catch (error) {
      console.error("Error in affiliate admin script initialization:", error);
      return;
    }

    // Add New Affiliate button click handler
    $("#add-new-affiliate").on("click", function () {
      $("#add-affiliate-modal").fadeIn(300);
      $("#add-affiliate-name").focus();
    });

    // Create button click handler
    $("#create-promo-code").on("click", function () {
      $("#promo-code-modal").fadeIn(300);
      $("#new-promo-code").focus();
    });

    // Close modal
    $(".modal-close").on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      closeModal();
    });

    // Close modal on outside click
    $(document).on("click", ".affiliate-modal", function (e) {
      if (e.target === this) {
        closeModal();
      }
    });

    // Helper function to close modal
    function closeModal() {
      $(".affiliate-modal").fadeOut(300, function () {
        $("#promo-code-form").trigger("reset");
        $("#add-affiliate-form").trigger("reset");
        clearErrors();
      });
    }

    // Helper function to show form errors
    function showFormError(field, message) {
      const $field = $(field);
      const $error = $(
        '<div class="form-error" style="color: #dc3232; font-size: 12px; margin-top: 5px;"></div>'
      );
      $error.text(message);
      $field.closest(".form-field").append($error);
      $field.addClass("error");
    }

    // Helper function to clear form errors
    function clearErrors() {
      $(".form-error").remove();
      $(".error").removeClass("error");
    }

    // Helper function to validate promo code format
    function validatePromoCode(code) {
      // Only allow letters, numbers, and hyphens, 3-20 characters
      return /^[a-zA-Z0-9-]{3,20}$/.test(code);
    }

    // Helper function to show notices
    function showNotice(message, type = "error") {
      const $notice = $(
        '<div class="notice notice-' +
          type +
          ' is-dismissible"><p>' +
          message +
          "</p></div>"
      );
      const $dismiss = $(
        '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'
      );

      $notice.append($dismiss);
      $(".wrap > h1").after($notice);

      $dismiss.on("click", function () {
        $notice.fadeOut(300, function () {
          $(this).remove();
        });
      });

      // Auto dismiss after 5 seconds
      setTimeout(function () {
        $notice.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    }

    // Form submission - only bind if form exists
    if ($("#promo-code-form").length) {
      $("#promo-code-form").on("submit", function (e) {
        e.preventDefault();

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $targetPlans = $("#target-plans");
        const $promoCode = $("#new-promo-code");
        const $affiliateName = $("#affiliate-name");
        const $commissionRate = $("#commission-rate");

        // Clear previous errors
        clearErrors();

        // Validate promo code format
        if (!$promoCode.length) return; // Not on this screen

        if (!validatePromoCode($promoCode.val().trim())) {
          showFormError(
            $promoCode,
            "Promo code must be 3-20 characters and contain only letters, numbers, and hyphens."
          );
          return;
        }

        // Validate affiliate name
        if ($affiliateName.val().trim().length < 2) {
          showFormError(
            $affiliateName,
            "Affiliate name must be at least 2 characters."
          );
          return;
        }

        // Validate target plans
        if (!$targetPlans.val() || $targetPlans.val().length === 0) {
          showFormError(
            $targetPlans,
            "Please select at least one target plan."
          );
          return;
        }

        // Validate commission rate
        const commissionRate = parseFloat($commissionRate.val());
        if (
          isNaN(commissionRate) ||
          commissionRate < 0 ||
          commissionRate > 100
        ) {
          showFormError(
            $commissionRate,
            "Commission rate must be between 0 and 100."
          );
          return;
        }

        // Disable form while submitting
        $form.find("input, select, button").prop("disabled", true);

        const formData = {
          action: "create_affiliate_code",
          nonce: flexpressAffiliate.nonce,
          code: $promoCode.val().trim(),
          affiliate_name: $affiliateName.val().trim(),
          target_plans: $targetPlans.val(),
          commission_rate: commissionRate,
        };

        // Show loading state
        $submitButton.html(
          '<span class="spinner is-active"></span> Creating...'
        );

        $.ajax({
          url: flexpressAffiliate.ajaxurl,
          type: "POST",
          data: formData,
          success: function (response) {
            if (response.success) {
              showNotice(
                response.data.message || flexpressAffiliate.i18n.success,
                "success"
              );
              closeModal();
              // Reload after a short delay to allow the notice to be seen
              setTimeout(function () {
                window.location.reload();
              }, 1000);
            } else {
              showFormError(
                $promoCode,
                response.data.message || flexpressAffiliate.i18n.error
              );
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            showNotice(
              flexpressAffiliate.i18n.error + (error ? ": " + error : "")
            );
          },
          complete: function () {
            // Re-enable form
            $form.find("input, select, button").prop("disabled", false);
            $submitButton.html("Create Promo Code");
          },
        });
      });
    }

    // Add Affiliate form submission
    $("#add-affiliate-form").on("submit", function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitButton = $form.find('button[type="submit"]');
      const $affiliateName = $("#add-affiliate-name");
      const $affiliateEmail = $("#affiliate-email");
      const $payoutMethod = $("#payout-method");
      const $payoutDetails = $("#payout-details");

      // Clear previous errors
      clearErrors();

      // Validate affiliate name
      if ($affiliateName.val().trim().length < 2) {
        showFormError(
          $affiliateName,
          "Affiliate name must be at least 2 characters."
        );
        return;
      }

      // Validate email
      if (
        !$affiliateEmail.val().trim() ||
        !isValidEmail($affiliateEmail.val().trim())
      ) {
        showFormError($affiliateEmail, "Please enter a valid email address.");
        return;
      }

      // Validate payout method
      if (!$payoutMethod.val()) {
        showFormError($payoutMethod, "Please select a payout method.");
        return;
      }

      // Validate payout details
      if (!$payoutDetails.val().trim()) {
        showFormError($payoutDetails, "Please enter payout details.");
        return;
      }

      // Disable form while submitting
      $form.find("input, select, textarea, button").prop("disabled", true);

      const formData = {
        action: "add_affiliate",
        nonce: flexpressAffiliate.nonce,
        display_name: $affiliateName.val().trim(),
        email: $affiliateEmail.val().trim(),
        website: $("#affiliate-website").val().trim(),
        payout_method: $payoutMethod.val(),
        payout_details: $payoutDetails.val().trim(),
        commission_initial: parseFloat($("#commission-initial").val()) || 25,
        commission_rebill: parseFloat($("#commission-rebill").val()) || 10,
        commission_unlock: parseFloat($("#commission-unlock").val()) || 15,
        payout_threshold: parseFloat($("#payout-threshold").val()) || 100,
        status: $("#affiliate-status").val(),
        notes: $("#affiliate-notes").val().trim(),
      };

      // Show loading state
      $submitButton.html('<span class="spinner is-active"></span> Adding...');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            showNotice(
              response.data.message || "Affiliate added successfully!",
              "success"
            );
            closeModal();
            // Reload after a short delay to allow the notice to be seen
            setTimeout(function () {
              window.location.reload();
            }, 1000);
          } else {
            showNotice(
              response.data.message || "Error adding affiliate",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          showNotice("Error adding affiliate: " + error);
        },
        complete: function () {
          // Re-enable form
          $form.find("input, select, textarea, button").prop("disabled", false);
          $submitButton.html("Add Affiliate");
        },
      });
    });

    // Helper function to validate email
    function isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }

    // View affiliate details
    $(document).on("click", ".view-affiliate", function () {
      const affiliateId = $(this).data("id");
      const $button = $(this);

      $button
        .prop("disabled", true)
        .html('<span class="spinner is-active"></span>');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: {
          action: "get_affiliate_details",
          nonce: flexpressAffiliate.nonce,
          affiliate_id: affiliateId,
        },
        success: function (response) {
          if (response.success) {
            showAffiliateDetailsModal(response.data);
          } else {
            showNotice(
              response.data.message || "Error loading affiliate details",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          showNotice("Error loading affiliate details: " + error);
        },
        complete: function () {
          $button.prop("disabled", false).html("View");
        },
      });
    });

    // Edit affiliate
    $(document).on("click", ".edit-affiliate", function () {
      const affiliateId = $(this).data("id");
      const $button = $(this);

      $button
        .prop("disabled", true)
        .html('<span class="spinner is-active"></span>');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: {
          action: "get_affiliate_details",
          nonce: flexpressAffiliate.nonce,
          affiliate_id: affiliateId,
        },
        success: function (response) {
          if (response.success) {
            showEditAffiliateModal(response.data);
          } else {
            showNotice(
              response.data.message || "Error loading affiliate details",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          showNotice("Error loading affiliate details: " + error);
        },
        complete: function () {
          $button.prop("disabled", false).html("Edit");
        },
      });
    });

    // Helper function to show affiliate details modal
    function showAffiliateDetailsModal(data) {
      const modalHtml = `
            <div id="affiliate-details-modal" class="affiliate-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Affiliate Details: ${data.display_name}</h2>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="affiliate-details-grid">
                            <div class="detail-section">
                                <h3>Basic Information</h3>
                                <p><strong>Name:</strong> ${
                                  data.display_name
                                }</p>
                                <p><strong>Email:</strong> ${data.email}</p>
                                <p><strong>Website:</strong> ${
                                  data.website || "Not provided"
                                }</p>
                                <p><strong>Status:</strong> <span class="status status-${
                                  data.status
                                }">${
        data.status.charAt(0).toUpperCase() + data.status.slice(1)
      }</span></p>
                                <p><strong>Affiliate Code:</strong> <code>${
                                  data.affiliate_code
                                }</code></p>
                                <p><strong>Referral URL:</strong> <a href="${
                                  data.referral_url
                                }" target="_blank">${data.referral_url}</a></p>
                            </div>
                            <div class="detail-section">
                                <h3>Commission Settings</h3>
                                <p><strong>Initial Commission:</strong> ${
                                  data.commission_initial
                                }%</p>
                                <p><strong>Rebill Commission:</strong> ${
                                  data.commission_rebill
                                }%</p>
                                <p><strong>Unlock Commission:</strong> ${
                                  data.commission_unlock
                                }%</p>
                                <p><strong>Payout Threshold:</strong> $${
                                  data.payout_threshold
                                }</p>
                            </div>
                            <div class="detail-section">
                                <h3>Payout Information</h3>
                                <p><strong>Method:</strong> ${
                                  data.payout_method.charAt(0).toUpperCase() +
                                  data.payout_method.slice(1).replace("_", " ")
                                }</p>
                                <p><strong>Details:</strong> ${
                                  data.payout_details
                                }</p>
                            </div>
                            <div class="detail-section">
                                <h3>Performance Statistics</h3>
                                <p><strong>Total Clicks:</strong> ${
                                  data.total_clicks
                                }</p>
                                <p><strong>Total Signups:</strong> ${
                                  data.total_signups
                                }</p>
                                <p><strong>Total Revenue:</strong> $${
                                  data.total_revenue
                                }</p>
                                <p><strong>Total Commission:</strong> $${
                                  data.total_commission
                                }</p>
                                <p><strong>Pending Commission:</strong> $${
                                  data.pending_commission
                                }</p>
                                <p><strong>Approved Commission:</strong> $${
                                  data.approved_commission
                                }</p>
                                <p><strong>Paid Commission:</strong> $${
                                  data.paid_commission
                                }</p>
                            </div>
                            ${
                              data.notes
                                ? `<div class="detail-section"><h3>Notes</h3><p>${data.notes}</p></div>`
                                : ""
                            }
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="button button-primary edit-affiliate-from-details" data-id="${
                              data.id
                            }">
                                Edit Affiliate
                            </button>
                            <button type="button" class="button modal-close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

      // Remove any existing modals
      $("#affiliate-details-modal").remove();

      // Add new modal
      const $modal = $(modalHtml).appendTo("body");
      $modal.fadeIn(300);

      // Bind close events
      $modal.find(".modal-close").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeAffiliateDetailsModal();
      });

      // Bind outside click to close
      $modal.on("click", function (e) {
        if (e.target === this) {
          closeAffiliateDetailsModal();
        }
      });

      // Bind edit button
      $modal.find(".edit-affiliate-from-details").on("click", function () {
        const affiliateId = $(this).data("id");
        closeAffiliateDetailsModal();
        showEditAffiliateModal(data);
      });
    }

    // Helper function to show edit affiliate modal
    function showEditAffiliateModal(data) {
      const modalHtml = `
            <div id="edit-affiliate-modal" class="affiliate-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Edit Affiliate: ${data.display_name}</h2>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="edit-affiliate-form">
                            <input type="hidden" name="affiliate_id" value="${
                              data.id
                            }">
                            <div class="form-field">
                                <label for="edit-affiliate-name">Affiliate Name *</label>
                                <input type="text" id="edit-affiliate-name" name="display_name" value="${
                                  data.display_name
                                }" required>
                            </div>
                            <div class="form-field">
                                <label for="edit-affiliate-email">Email Address *</label>
                                <input type="email" id="edit-affiliate-email" name="email" value="${
                                  data.email
                                }" required>
                            </div>
                            <div class="form-field">
                                <label for="edit-affiliate-website">Website/Social Media</label>
                                <input type="url" id="edit-affiliate-website" name="website" value="${
                                  data.website || ""
                                }" placeholder="https://example.com">
                            </div>
                            <div class="form-field">
                                <label for="edit-payout-method">Payout Method *</label>
                                <select id="edit-payout-method" name="payout_method" required>
                                    <option value="">Select payout method</option>
                                    <option value="paypal" ${
                                      data.payout_method === "paypal"
                                        ? "selected"
                                        : ""
                                    }>PayPal (Free)</option>
                                    <option value="crypto" ${
                                      data.payout_method === "crypto"
                                        ? "selected"
                                        : ""
                                    }>Cryptocurrency (Free)</option>
                                    <option value="aus_bank_transfer" ${
                                      data.payout_method === "aus_bank_transfer"
                                        ? "selected"
                                        : ""
                                    }>Australian Bank Transfer (Free)</option>
                                    <option value="yoursafe" ${
                                      data.payout_method === "yoursafe"
                                        ? "selected"
                                        : ""
                                    }>Yoursafe (Free)</option>
                                    <option value="ach" ${
                                      data.payout_method === "ach"
                                        ? "selected"
                                        : ""
                                    }>ACH - US Only ($10 USD Fee)</option>
                                    <option value="swift" ${
                                      data.payout_method === "swift"
                                        ? "selected"
                                        : ""
                                    }>Swift International ($30 USD Fee)</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="edit-payout-details">Payout Details *</label>
                                <input type="text" id="edit-payout-details" name="payout_details" value="${
                                  data.payout_details
                                }" required placeholder="PayPal email, bank account, etc.">
                            </div>
                            <div class="form-field">
                                <label for="edit-commission-initial">Initial Commission Rate (%)</label>
                                <input type="number" id="edit-commission-initial" name="commission_initial" min="0" max="100" step="0.1" value="${
                                  data.commission_initial
                                }">
                            </div>
                            <div class="form-field">
                                <label for="edit-commission-rebill">Rebill Commission Rate (%)</label>
                                <input type="number" id="edit-commission-rebill" name="commission_rebill" min="0" max="100" step="0.1" value="${
                                  data.commission_rebill
                                }">
                            </div>
                            <div class="form-field">
                                <label for="edit-commission-unlock">Unlock Commission Rate (%)</label>
                                <input type="number" id="edit-commission-unlock" name="commission_unlock" min="0" max="100" step="0.1" value="${
                                  data.commission_unlock
                                }">
                            </div>
                            <div class="form-field">
                                <label for="edit-payout-threshold">Payout Threshold ($)</label>
                                <input type="number" id="edit-payout-threshold" name="payout_threshold" min="0" step="0.01" value="${
                                  data.payout_threshold
                                }">
                            </div>
                            <div class="form-field">
                                <label for="edit-affiliate-status">Status</label>
                                <select id="edit-affiliate-status" name="status">
                                    <option value="pending" ${
                                      data.status === "pending"
                                        ? "selected"
                                        : ""
                                    }>Pending</option>
                                    <option value="active" ${
                                      data.status === "active" ? "selected" : ""
                                    }>Active</option>
                                    <option value="suspended" ${
                                      data.status === "suspended"
                                        ? "selected"
                                        : ""
                                    }>Suspended</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="edit-affiliate-notes">Notes</label>
                                <textarea id="edit-affiliate-notes" name="notes" rows="3" placeholder="Additional notes about this affiliate...">${
                                  data.notes || ""
                                }</textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="button button-primary">Update Affiliate</button>
                                <button type="button" class="button modal-close">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

      // Remove any existing modals
      $("#edit-affiliate-modal").remove();

      // Add new modal
      const $modal = $(modalHtml).appendTo("body");
      $modal.fadeIn(300);

      // Populate dynamic payout fields
      const $editContainer = $modal.find(".payout-details-container");
      if ($editContainer.length) {
        populatePayoutFields(
          $editContainer,
          data.payout_details,
          data.payout_method
        );
      }

      // Bind close events
      $modal.find(".modal-close").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeEditAffiliateModal();
      });

      // Bind outside click to close
      $modal.on("click", function (e) {
        if (e.target === this) {
          closeEditAffiliateModal();
        }
      });

      // Bind form submission
      $modal.find("#edit-affiliate-form").on("submit", function (e) {
        e.preventDefault();

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const affiliateId = $form.find('input[name="affiliate_id"]').val();

        // Clear previous errors
        clearErrors();

        // Validate form
        const $affiliateName = $("#edit-affiliate-name");
        const $affiliateEmail = $("#edit-affiliate-email");
        const $payoutMethod = $("#edit-payout-method");
        const $payoutDetails = $("#edit-payout-details");

        if ($affiliateName.val().trim().length < 2) {
          showFormError(
            $affiliateName,
            "Affiliate name must be at least 2 characters."
          );
          return;
        }

        if (
          !$affiliateEmail.val().trim() ||
          !isValidEmail($affiliateEmail.val().trim())
        ) {
          showFormError($affiliateEmail, "Please enter a valid email address.");
          return;
        }

        if (!$payoutMethod.val()) {
          showFormError($payoutMethod, "Please select a payout method.");
          return;
        }

        if (!$payoutDetails.val().trim()) {
          showFormError($payoutDetails, "Please enter payout details.");
          return;
        }

        // Disable form while submitting
        $form.find("input, select, textarea, button").prop("disabled", true);

        const formData = {
          action: "update_affiliate",
          nonce: flexpressAffiliate.nonce,
          affiliate_id: affiliateId,
          display_name: $affiliateName.val().trim(),
          email: $affiliateEmail.val().trim(),
          website: $("#edit-affiliate-website").val().trim(),
          payout_method: $payoutMethod.val(),
          payout_details: $payoutDetails.val().trim(),
          commission_initial:
            parseFloat($("#edit-commission-initial").val()) || 25,
          commission_rebill:
            parseFloat($("#edit-commission-rebill").val()) || 10,
          commission_unlock:
            parseFloat($("#edit-commission-unlock").val()) || 15,
          payout_threshold:
            parseFloat($("#edit-payout-threshold").val()) || 100,
          status: $("#edit-affiliate-status").val(),
          notes: $("#edit-affiliate-notes").val().trim(),
        };

        // Show loading state
        $submitButton.html(
          '<span class="spinner is-active"></span> Updating...'
        );

        $.ajax({
          url: flexpressAffiliate.ajaxurl,
          type: "POST",
          data: formData,
          success: function (response) {
            if (response.success) {
              showNotice(
                response.data.message || "Affiliate updated successfully!",
                "success"
              );
              closeEditAffiliateModal();
              // Reload after a short delay to allow the notice to be seen
              setTimeout(function () {
                window.location.reload();
              }, 1000);
            } else {
              showNotice(
                response.data.message || "Error updating affiliate",
                "error"
              );
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            showNotice("Error updating affiliate: " + error);
          },
          complete: function () {
            // Re-enable form
            $form
              .find("input, select, textarea, button")
              .prop("disabled", false);
            $submitButton.html("Update Affiliate");
          },
        });
      });
    }

    // Helper function to close affiliate details modal
    function closeAffiliateDetailsModal() {
      $("#affiliate-details-modal").fadeOut(300, function () {
        $(this).remove();
      });
    }

    // Helper function to close edit affiliate modal
    function closeEditAffiliateModal() {
      $("#edit-affiliate-modal").fadeOut(300, function () {
        $(this).remove();
      });
    }

    // Delete code
    $(document).on("click", ".delete-code", function () {
      const code = $(this).data("code");
      const $row = $(this).closest("tr");

      if (!confirm(flexpressAffiliate.i18n.confirmDelete)) {
        return;
      }

      const $button = $(this);
      $button
        .prop("disabled", true)
        .html('<span class="spinner is-active"></span>');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: {
          action: "delete_affiliate_code",
          nonce: flexpressAffiliate.nonce,
          code: code,
        },
        success: function (response) {
          if (response.success) {
            showNotice(
              response.data.message || flexpressAffiliate.i18n.success,
              "success"
            );
            $row.fadeOut(300, function () {
              $(this).remove();
              // If no more rows, show empty message
              if ($("#promo-codes-list tr").length === 0) {
                $("#promo-codes-list").html(
                  '<tr><td colspan="7">' +
                    flexpressAffiliate.i18n.noPromoCodesFound +
                    "</td></tr>"
                );
              }
            });
          } else {
            showNotice(
              response.data.message || flexpressAffiliate.i18n.error,
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          showNotice(
            flexpressAffiliate.i18n.error + (error ? ": " + error : "")
          );
        },
        complete: function () {
          $button.prop("disabled", false).html("Delete");
        },
      });
    });

    // View code details
    $(document).on("click", ".view-details", function () {
      const code = $(this).data("code");
      const $button = $(this);

      $button
        .prop("disabled", true)
        .html('<span class="spinner is-active"></span>');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: {
          action: "get_affiliate_stats",
          nonce: flexpressAffiliate.nonce,
          code: code,
        },
        success: function (response) {
          if (response.success) {
            showStatsModal(response.data);
          } else {
            showNotice(
              response.data.message || flexpressAffiliate.i18n.error,
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          showNotice(
            flexpressAffiliate.i18n.error + (error ? ": " + error : "")
          );
        },
        complete: function () {
          $button.prop("disabled", false).html("Details");
        },
      });
    });

    // Helper function to show stats modal
    function showStatsModal(data) {
      const modalHtml = `
            <div id="code-details-modal" class="affiliate-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Code Details: ${data.code}</h2>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="stats-grid">
                            <div class="stat-card">
                                <h3>Total Uses</h3>
                                <div class="stat-number">${data.total_uses}</div>
                            </div>
                            <div class="stat-card">
                                <h3>Revenue Generated</h3>
                                <div class="stat-number">$${data.revenue}</div>
                            </div>
                            <div class="stat-card">
                                <h3>Conversion Rate</h3>
                                <div class="stat-number">${data.conversion_rate}%</div>
                            </div>
                            <div class="stat-card">
                                <h3>Last 30 Days</h3>
                                <div class="stat-number">${data.recent_uses}</div>
                            </div>
                        </div>
                        <div class="usage-timeline">
                            <h3>Recent Usage</h3>
                            <canvas id="usage-timeline-chart"></canvas>
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="button button-primary edit-promo-code" data-code="${data.code}">
                                Edit Code
                            </button>
                            <button type="button" class="button modal-close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

      // Remove any existing modals
      $("#code-details-modal").remove();

      // Add new modal
      const $modal = $(modalHtml).appendTo("body");
      $modal.fadeIn(300);

      // Bind close events to the new modal
      $modal.find(".modal-close").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeStatsModal();
      });

      // Bind outside click to close
      $modal.on("click", function (e) {
        if (e.target === this) {
          closeStatsModal();
        }
      });

      // Bind edit button
      $modal.find(".edit-promo-code").on("click", function () {
        const code = $(this).data("code");
        closeStatsModal();
        showEditModal(code, data);
      });

      // Initialize chart if we have timeline data
      if (
        data.timeline &&
        data.timeline.length &&
        typeof Chart !== "undefined"
      ) {
        const chartElement = document.getElementById("usage-timeline-chart");
        if (!chartElement) {
          console.warn("Chart element not found");
          return;
        }

        const ctx = chartElement.getContext("2d");
        if (!ctx) {
          console.warn("Could not get chart context");
          return;
        }

        new Chart(ctx, {
          type: "line",
          data: {
            labels: data.timeline.map((item) => item.date),
            datasets: [
              {
                label: "Uses",
                data: data.timeline.map((item) => item.count),
                borderColor: "#007cba",
                backgroundColor: "rgba(0, 124, 186, 0.1)",
                tension: 0.4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1,
                },
              },
            },
          },
        });
      }
    }

    // Helper function to close stats modal
    function closeStatsModal() {
      $("#code-details-modal").fadeOut(300, function () {
        $(this).remove();
      });
    }

    // Helper function to show edit modal
    function showEditModal(code, data) {
      const modalHtml = `
            <div id="edit-promo-modal" class="affiliate-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Edit Promotional Code: ${code}</h2>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <form id="edit-promo-form">
                            <div class="form-field">
                                <label for="edit-affiliate-name">Affiliate Name</label>
                                <input type="text" id="edit-affiliate-name" name="affiliate_name" value="${
                                  data.affiliate_name || ""
                                }" required>
                            </div>
                            <div class="form-field">
                                <label for="edit-target-plans">Target Plans</label>
                                <select id="edit-target-plans" name="target_plans[]" multiple required>
                                    ${
                                      data.target_plans
                                        ? data.target_plans
                                            .map(
                                              (plan) =>
                                                `<option value="${plan}" selected>${plan}</option>`
                                            )
                                            .join("")
                                        : ""
                                    }
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="edit-commission-rate">Commission Rate (%)</label>
                                <input type="number" id="edit-commission-rate" name="commission_rate" min="0" max="100" step="0.1" value="${
                                  data.commission_rate || 10
                                }" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="button button-primary">Update Code</button>
                                <button type="button" class="button modal-close">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

      // Remove any existing edit modals
      $("#edit-promo-modal").remove();

      // Add new modal
      const $modal = $(modalHtml).appendTo("body");
      $modal.fadeIn(300);

      // Bind close events
      $modal.find(".modal-close").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        closeEditModal();
      });

      // Bind outside click to close
      $modal.on("click", function (e) {
        if (e.target === this) {
          closeEditModal();
        }
      });

      // Load plan options
      loadPlanOptions("#edit-target-plans");

      // Bind form submission
      $modal.find("#edit-promo-form").on("submit", function (e) {
        e.preventDefault();

        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $targetPlans = $("#edit-target-plans");
        const $affiliateName = $("#edit-affiliate-name");
        const $commissionRate = $("#edit-commission-rate");

        // Clear previous errors
        clearErrors();

        // Validate affiliate name
        if ($affiliateName.val().trim().length < 2) {
          showFormError(
            $affiliateName,
            "Affiliate name must be at least 2 characters."
          );
          return;
        }

        // Validate target plans
        if (!$targetPlans.val() || $targetPlans.val().length === 0) {
          showFormError(
            $targetPlans,
            "Please select at least one target plan."
          );
          return;
        }

        // Validate commission rate
        const commissionRate = parseFloat($commissionRate.val());
        if (
          isNaN(commissionRate) ||
          commissionRate < 0 ||
          commissionRate > 100
        ) {
          showFormError(
            $commissionRate,
            "Commission rate must be between 0 and 100."
          );
          return;
        }

        // Disable form while submitting
        $form.find("input, select, button").prop("disabled", true);

        const formData = {
          action: "update_affiliate_code",
          nonce: flexpressAffiliate.nonce,
          code: code,
          affiliate_name: $affiliateName.val().trim(),
          target_plans: $targetPlans.val(),
          commission_rate: commissionRate,
        };

        // Show loading state
        $submitButton.html(
          '<span class="spinner is-active"></span> Updating...'
        );

        $.ajax({
          url: flexpressAffiliate.ajaxurl,
          type: "POST",
          data: formData,
          success: function (response) {
            if (response.success) {
              showNotice(
                response.data.message || flexpressAffiliate.i18n.success,
                "success"
              );
              closeEditModal();
              // Reload after a short delay to allow the notice to be seen
              setTimeout(function () {
                window.location.reload();
              }, 1000);
            } else {
              showFormError(
                $affiliateName,
                response.data.message || flexpressAffiliate.i18n.error
              );
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            showNotice(
              flexpressAffiliate.i18n.error + (error ? ": " + error : "")
            );
          },
          complete: function () {
            // Re-enable form
            $form.find("input, select, button").prop("disabled", false);
            $submitButton.html("Update Code");
          },
        });
      });
    }

    // Helper function to close edit modal
    function closeEditModal() {
      $("#edit-promo-modal").fadeOut(300, function () {
        $(this).remove();
      });
    }

    // Helper function to load plan options
    function loadPlanOptions(selector) {
      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: {
          action: "get_pricing_plans",
          nonce: flexpressAffiliate.nonce,
        },
        success: function (response) {
          if (response.success && response.data) {
            const $select = $(selector);
            $select.empty();
            response.data.forEach((plan) => {
              $select.append(
                `<option value="${plan.name}">${plan.name}</option>`
              );
            });
          }
        },
      });
    }

    // Payout system functionality
    let currentPayoutPage = 1;
    let currentPayoutStatus = "";

    // Load payouts on page load
    loadPayouts();

    // Create payout button
    $("#create-payout").on("click", function () {
      loadEligibleAffiliates();
      $("#create-payout-modal").fadeIn(300);
    });

    // Refresh payouts button
    $("#refresh-payouts").on("click", function () {
      loadPayouts();
    });

    // Payout status filter
    $("#payout-status-filter").on("change", function () {
      currentPayoutStatus = $(this).val();
      currentPayoutPage = 1;
      loadPayouts();
    });

    // Pagination
    $("#prev-page").on("click", function () {
      if (currentPayoutPage > 1) {
        currentPayoutPage--;
        loadPayouts();
      }
    });

    $("#next-page").on("click", function () {
      currentPayoutPage++;
      loadPayouts();
    });

    // Create payout form submission
    $("#create-payout-form").on("submit", function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitButton = $form.find('button[type="submit"]');

      // Clear previous errors
      clearErrors();

      // Validate form
      const $affiliate = $("#payout-affiliate");
      const $periodStart = $("#payout-period-start");
      const $periodEnd = $("#payout-period-end");

      if (!$affiliate.val()) {
        showFormError($affiliate, "Please select an affiliate.");
        return;
      }

      if (!$periodStart.val()) {
        showFormError($periodStart, "Please select a start date.");
        return;
      }

      if (!$periodEnd.val()) {
        showFormError($periodEnd, "Please select an end date.");
        return;
      }

      if (new Date($periodStart.val()) >= new Date($periodEnd.val())) {
        showFormError($periodEnd, "End date must be after start date.");
        return;
      }

      // Disable form while submitting
      $form.find("input, select, textarea, button").prop("disabled", true);

      const formData = {
        action: "create_payout",
        nonce: flexpressAffiliate.nonce,
        affiliate_id: $affiliate.val(),
        period_start: $periodStart.val(),
        period_end: $periodEnd.val(),
        notes: $("#payout-notes").val().trim(),
      };

      // Show loading state
      $submitButton.html('<span class="spinner is-active"></span> Creating...');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            showNotice(
              response.data.message || "Payout created successfully!",
              "success"
            );
            closeModal();
            loadPayouts();
          } else {
            showNotice(
              response.data.message || "Error creating payout",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          showNotice("Error creating payout: " + error);
        },
        complete: function () {
          // Re-enable form
          $form.find("input, select, textarea, button").prop("disabled", false);
          $submitButton.html("Create Payout");
        },
      });
    });

    // Update payout status form submission
    $("#update-payout-form").on("submit", function (e) {
      e.preventDefault();

      const $form = $(this);
      const $submitButton = $form.find('button[type="submit"]');

      // Clear previous errors
      clearErrors();

      // Validate form
      const $status = $("#update-payout-status");

      if (!$status.val()) {
        showFormError($status, "Please select a status.");
        return;
      }

      // Disable form while submitting
      $form.find("input, select, textarea, button").prop("disabled", true);

      const formData = {
        action: "update_payout_status",
        nonce: flexpressAffiliate.nonce,
        payout_id: $("#update-payout-id").val(),
        status: $status.val(),
        reference_id: $("#update-payout-reference").val().trim(),
        notes: $("#update-payout-notes").val().trim(),
      };

      // Show loading state
      $submitButton.html('<span class="spinner is-active"></span> Updating...');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            showNotice(
              response.data.message || "Payout status updated successfully!",
              "success"
            );
            closeModal();
            loadPayouts();
          } else {
            showNotice(
              response.data.message || "Error updating payout status",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          showNotice("Error updating payout status: " + error);
        },
        complete: function () {
          // Re-enable form
          $form.find("input, select, textarea, button").prop("disabled", false);
          $submitButton.html("Update Status");
        },
      });
    });

    // View payout details
    $(document).on("click", ".view-payout", function () {
      const payoutId = $(this).data("id");
      const $button = $(this);

      $button
        .prop("disabled", true)
        .html('<span class="spinner is-active"></span>');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: {
          action: "get_payout_details",
          nonce: flexpressAffiliate.nonce,
          payout_id: payoutId,
        },
        success: function (response) {
          if (response.success) {
            showPayoutDetailsModal(response.data);
          } else {
            showNotice(
              response.data.message || "Error loading payout details",
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          showNotice("Error loading payout details: " + error);
        },
        complete: function () {
          $button.prop("disabled", false).html("View");
        },
      });
    });

    // Update payout status
    $(document).on("click", ".update-payout-status", function () {
      const payoutId = $(this).data("id");
      const currentStatus = $(this).data("status");

      $("#update-payout-id").val(payoutId);
      $("#update-payout-status").val(currentStatus);
      $("#update-payout-modal").fadeIn(300);
    });

    // Helper function to load payouts
    function loadPayouts() {
      const $tbody = $("#payouts-list");
      $tbody.html('<tr><td colspan="7">Loading payouts...</td></tr>');

      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: {
          action: "get_payouts_list",
          nonce: flexpressAffiliate.nonce,
          page: currentPayoutPage,
          per_page: 20,
          status: currentPayoutStatus,
        },
        success: function (response) {
          if (response.success) {
            renderPayoutsTable(response.data.payouts);
            updatePagination(response.data);
          } else {
            $tbody.html(
              '<tr><td colspan="7">Error loading payouts: ' +
                (response.data.message || "Unknown error") +
                "</td></tr>"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
          $tbody.html(
            '<tr><td colspan="7">Error loading payouts: ' + error + "</td></tr>"
          );
        },
      });
    }

    // Helper function to render payouts table
    function renderPayoutsTable(payouts) {
      const $tbody = $("#payouts-list");

      if (payouts.length === 0) {
        $tbody.html('<tr><td colspan="7">No payouts found.</td></tr>');
        return;
      }

      let html = "";
      payouts.forEach(function (payout) {
        const statusClass = "status-" + payout.status;
        const statusLabel =
          payout.status.charAt(0).toUpperCase() + payout.status.slice(1);
        const period = payout.period_start + " to " + payout.period_end;
        const createdDate = new Date(payout.created_at).toLocaleDateString();

        html += "<tr>";
        html +=
          "<td><strong>" +
          escapeHtml(payout.affiliate_name) +
          "</strong><br><small>" +
          escapeHtml(payout.affiliate_email) +
          "</small></td>";
        html += "<td>" + escapeHtml(period) + "</td>";
        html += "<td>$" + parseFloat(payout.payout_amount).toFixed(2) + "</td>";
        html +=
          "<td>" +
          escapeHtml(
            payout.payout_method.charAt(0).toUpperCase() +
              payout.payout_method.slice(1).replace("_", " ")
          ) +
          "</td>";
        html +=
          '<td><span class="status ' +
          statusClass +
          '">' +
          statusLabel +
          "</span></td>";
        html += "<td>" + createdDate + "</td>";
        html += "<td>";
        html +=
          '<button type="button" class="button button-small view-payout" data-id="' +
          payout.id +
          '">View</button> ';
        html +=
          '<button type="button" class="button button-small update-payout-status" data-id="' +
          payout.id +
          '" data-status="' +
          payout.status +
          '">Update</button>';
        html += "</td>";
        html += "</tr>";
      });

      $tbody.html(html);
    }

    // Helper function to update pagination
    function updatePagination(data) {
      const $prevBtn = $("#prev-page");
      const $nextBtn = $("#next-page");
      const $pageInfo = $("#page-info");

      $prevBtn.prop("disabled", data.page <= 1);
      $nextBtn.prop("disabled", data.page >= data.total_pages);

      $pageInfo.text("Page " + data.page + " of " + data.total_pages);
    }

    // Helper function to load eligible affiliates
    function loadEligibleAffiliates() {
      $.ajax({
        url: flexpressAffiliate.ajaxurl,
        type: "POST",
        data: {
          action: "get_eligible_affiliates",
          nonce: flexpressAffiliate.nonce,
        },
        success: function (response) {
          if (response.success) {
            const $select = $("#payout-affiliate");
            $select.empty();
            $select.append('<option value="">Select affiliate</option>');

            response.data.forEach(function (affiliate) {
              $select.append(
                '<option value="' +
                  affiliate.id +
                  '">' +
                  escapeHtml(affiliate.display_name) +
                  " ($" +
                  parseFloat(affiliate.pending_amount).toFixed(2) +
                  " pending)</option>"
              );
            });
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", status, error);
        },
      });
    }

    // Helper function to show payout details modal
    function showPayoutDetailsModal(data) {
      const modalHtml = `
            <div id="payout-details-modal" class="affiliate-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Payout Details: ${data.affiliate_name}</h2>
                        <span class="modal-close">&times;</span>
                    </div>
                    <div class="modal-body">
                        <div class="affiliate-details-grid">
                            <div class="detail-section">
                                <h3>Payout Information</h3>
                                <p><strong>Affiliate:</strong> ${
                                  data.affiliate_name
                                }</p>
                                <p><strong>Email:</strong> ${
                                  data.affiliate_email
                                }</p>
                                <p><strong>Period:</strong> ${
                                  data.period_start
                                } to ${data.period_end}</p>
                                <p><strong>Amount:</strong> $${parseFloat(
                                  data.payout_amount
                                ).toFixed(2)}</p>
                                <p><strong>Status:</strong> <span class="status status-${
                                  data.status
                                }">${
        data.status.charAt(0).toUpperCase() + data.status.slice(1)
      }</span></p>
                            </div>
                            <div class="detail-section">
                                <h3>Payment Details</h3>
                                <p><strong>Method:</strong> ${
                                  data.payout_method.charAt(0).toUpperCase() +
                                  data.payout_method.slice(1).replace("_", " ")
                                }</p>
                                <p><strong>Details:</strong> ${
                                  data.payout_details
                                }</p>
                                ${
                                  data.reference_id
                                    ? "<p><strong>Reference ID:</strong> " +
                                      data.reference_id +
                                      "</p>"
                                    : ""
                                }
                                <p><strong>Created:</strong> ${new Date(
                                  data.created_at
                                ).toLocaleString()}</p>
                                ${
                                  data.processed_at
                                    ? "<p><strong>Processed:</strong> " +
                                      new Date(
                                        data.processed_at
                                      ).toLocaleString() +
                                      "</p>"
                                    : ""
                                }
                            </div>
                        </div>
                        ${
                          data.transactions.length > 0
                            ? `
                        <div class="detail-section">
                            <h3>Related Transactions (${
                              data.transactions.length
                            })</h3>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Revenue</th>
                                        <th>Commission</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.transactions
                                      .map(
                                        (t) => `
                                        <tr>
                                            <td>${new Date(
                                              t.created_at
                                            ).toLocaleDateString()}</td>
                                            <td>${
                                              t.transaction_type
                                                .charAt(0)
                                                .toUpperCase() +
                                              t.transaction_type.slice(1)
                                            }</td>
                                            <td>$${parseFloat(
                                              t.revenue_amount
                                            ).toFixed(2)}</td>
                                            <td>$${parseFloat(
                                              t.commission_amount
                                            ).toFixed(2)}</td>
                                            <td><span class="status status-${
                                              t.status
                                            }">${
                                          t.status.charAt(0).toUpperCase() +
                                          t.status.slice(1)
                                        }</span></td>
                                        </tr>
                                    `
                                      )
                                      .join("")}
                                </tbody>
                            </table>
                        </div>
                        `
                            : ""
                        }
                        ${
                          data.notes
                            ? `<div class="detail-section"><h3>Notes</h3><p>${data.notes}</p></div>`
                            : ""
                        }
                        <div class="modal-actions">
                            <button type="button" class="button button-primary update-payout-status" data-id="${
                              data.id
                            }" data-status="${data.status}">
                                Update Status
                            </button>
                            <button type="button" class="button modal-close">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

      // Remove any existing modals
      $("#payout-details-modal").remove();

      // Add new modal
      const $modal = $(modalHtml).appendTo("body");
      $modal.fadeIn(300);

      // Bind close events
      $modal.find(".modal-close").on("click", function (e) {
        e.preventDefault();
        e.stopPropagation();
        closePayoutDetailsModal();
      });

      // Bind outside click to close
      $modal.on("click", function (e) {
        if (e.target === this) {
          closePayoutDetailsModal();
        }
      });

      // Bind update button
      $modal.find(".update-payout-status").on("click", function () {
        const payoutId = $(this).data("id");
        const currentStatus = $(this).data("status");
        closePayoutDetailsModal();
        $("#update-payout-id").val(payoutId);
        $("#update-payout-status").val(currentStatus);
        $("#update-payout-modal").fadeIn(300);
      });
    }

    // Helper function to close payout details modal
    function closePayoutDetailsModal() {
      $("#payout-details-modal").fadeOut(300, function () {
        $(this).remove();
      });
    }

    // Affiliate status management
    $(document).on(
      "click",
      ".approve-affiliate, .reject-affiliate, .suspend-affiliate, .reactivate-affiliate",
      function () {
        const $button = $(this);
        const affiliateId = $button.data("id");
        const action = $button.hasClass("approve-affiliate")
          ? "active"
          : $button.hasClass("reject-affiliate")
          ? "rejected"
          : $button.hasClass("suspend-affiliate")
          ? "suspended"
          : $button.hasClass("reactivate-affiliate")
          ? "active"
          : "pending";

        const actionText = $button.hasClass("approve-affiliate")
          ? "approve"
          : $button.hasClass("reject-affiliate")
          ? "reject"
          : $button.hasClass("suspend-affiliate")
          ? "suspend"
          : $button.hasClass("reactivate-affiliate")
          ? "reactivate"
          : "update";

        // Show confirmation dialog
        const confirmMessage = `Are you sure you want to ${actionText} this affiliate?`;
        if (!confirm(confirmMessage)) {
          return;
        }

        // Show notes dialog for reject/suspend actions
        let notes = "";
        if (action === "rejected" || action === "suspended") {
          notes = prompt(
            `Please provide a reason for ${actionText}ing this affiliate:`
          );
          if (notes === null) {
            return; // User cancelled
          }
        }

        $button
          .prop("disabled", true)
          .html('<span class="spinner is-active"></span>');

        $.ajax({
          url: flexpressAffiliate.ajaxurl,
          type: "POST",
          data: {
            action: "toggle_affiliate_status",
            nonce: flexpressAffiliate.nonce,
            affiliate_id: affiliateId,
            status: action,
            notes: notes || "",
          },
          success: function (response) {
            if (response.success) {
              showNotice(
                response.data.message ||
                  `Affiliate ${actionText}d successfully!`,
                "success"
              );
              // Reload the page to update the status
              setTimeout(function () {
                window.location.reload();
              }, 1000);
            } else {
              showNotice(
                response.data.message || `Error ${actionText}ing affiliate`,
                "error"
              );
            }
          },
          error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            showNotice(`Error ${actionText}ing affiliate: ` + error);
          },
          complete: function () {
            $button
              .prop("disabled", false)
              .html($button.text().replace("ing...", ""));
          },
        });
      }
    );

    // Dynamic Payout Fields Management
    function initPayoutFields() {
      // Handle payout method changes
      $(document).on("change", 'select[name="payout_method"]', function () {
        const selectedMethod = $(this).val();
        const $container = $(this)
          .closest("form")
          .find(".payout-details-container");

        // Hide all payout fields
        $container.find(".payout-fields").removeClass("active").hide();

        // Show selected method fields
        if (selectedMethod) {
          $container
            .find("." + selectedMethod + "-fields")
            .addClass("active")
            .show();

          // Handle crypto type selection
          if (selectedMethod === "crypto") {
            $container
              .find('select[name="crypto_type"]')
              .on("change", function () {
                const cryptoType = $(this).val();
                const $otherField = $container.find(
                  'input[name="crypto_other"]'
                );

                if (cryptoType === "other") {
                  $otherField.show().attr("required", true);
                } else {
                  $otherField.hide().removeAttr("required");
                }
              });
          }
        }

        // Update consolidated payout details
        updateConsolidatedPayoutDetails($container);
      });

      // Handle field changes to update consolidated details
      $(document).on("input change", ".payout-detail-field", function () {
        const $container = $(this).closest(".payout-details-container");
        updateConsolidatedPayoutDetails($container);
      });
    }

    function updateConsolidatedPayoutDetails($container) {
      const $activeFields = $container.find(".payout-fields.active");
      const $hiddenField = $container.find(
        'input[name="payout_details"], textarea[name="payout_details"]'
      );

      if ($activeFields.length === 0) {
        $hiddenField.val("");
        return;
      }

      const payoutData = {};

      $activeFields.find(".payout-detail-field").each(function () {
        const $field = $(this);
        const fieldName = $field.attr("name");
        const fieldValue = $field.val().trim();

        if (fieldValue) {
          payoutData[fieldName] = fieldValue;
        }
      });

      // Convert to JSON for storage
      $hiddenField.val(JSON.stringify(payoutData));
    }

    function parsePayoutDetails(payoutDetails, payoutMethod) {
      if (!payoutDetails) return {};

      try {
        return JSON.parse(payoutDetails);
      } catch (e) {
        // Handle legacy string format
        const data = {};
        if (payoutMethod === "paypal") {
          data.paypal_email = payoutDetails;
        } else {
          data.legacy_details = payoutDetails;
        }
        return data;
      }
    }

    function populatePayoutFields($container, payoutDetails, payoutMethod) {
      const data = parsePayoutDetails(payoutDetails, payoutMethod);

      // Show appropriate fields
      $container.find(".payout-fields").removeClass("active").hide();
      if (payoutMethod) {
        $container
          .find("." + payoutMethod + "-fields")
          .addClass("active")
          .show();
      }

      // Populate fields with data
      Object.keys(data).forEach(function (key) {
        const $field = $container.find('[name="' + key + '"]');
        if ($field.length) {
          $field.val(data[key]);

          // Trigger change events for dependent fields
          if (key === "crypto_type") {
            $field.trigger("change");
          }
        }
      });

      // Handle legacy data
      if (data.legacy_details) {
        $container
          .find(".payout-detail-field")
          .first()
          .val(data.legacy_details);
      }
    }

    // Initialize payout fields when document is ready
    initPayoutFields();

    // Format payout method name with fees
    function formatPayoutMethodName(method) {
      const methods = {
        paypal: "PayPal (Free)",
        crypto: "Cryptocurrency (Free)",
        aus_bank_transfer: "Australian Bank Transfer (Free)",
        yoursafe: "Yoursafe (Free)",
        ach: "ACH - US Only ($10 USD Fee)",
        swift: "Swift International ($30 USD Fee)",
      };

      return (
        methods[method] ||
        method.charAt(0).toUpperCase() + method.slice(1).replace("_", " ")
      );
    }

    // Format payout details for display
    function formatPayoutDetails(method, detailsJson) {
      if (!detailsJson) {
        return "<em>No details provided</em>";
      }

      let details;
      try {
        details = JSON.parse(detailsJson);
      } catch (e) {
        return (
          '<div class="payout-detail-legacy">' +
          escapeHtml(detailsJson) +
          "</div>"
        );
      }

      if (!details) {
        return "<em>No details provided</em>";
      }

      let output = '<div class="payout-details-formatted">';

      switch (method) {
        case "paypal":
          output +=
            "<strong>PayPal Email:</strong> " +
            escapeHtml(details.paypal_email || "");
          break;

        case "crypto":
          const cryptoType =
            details.crypto_type === "other"
              ? details.crypto_other || "Unknown"
              : details.crypto_type;
          output +=
            "<strong>Cryptocurrency:</strong> " +
            escapeHtml(cryptoType) +
            "<br>";
          output +=
            "<strong>Wallet Address:</strong> <code>" +
            escapeHtml(details.crypto_address || "") +
            "</code>";
          break;

        case "aus_bank_transfer":
          output +=
            "<strong>Bank:</strong> " +
            escapeHtml(details.aus_bank_name || "") +
            "<br>";
          output +=
            "<strong>BSB:</strong> " +
            escapeHtml(details.aus_bsb || "") +
            "<br>";
          output +=
            "<strong>Account:</strong> " +
            escapeHtml(details.aus_account_number || "") +
            "<br>";
          output +=
            "<strong>Account Holder:</strong> " +
            escapeHtml(details.aus_account_holder || "");
          break;

        case "yoursafe":
          output +=
            "<strong>Yoursafe IBAN:</strong> " +
            escapeHtml(details.yoursafe_iban || "");
          break;

        case "ach":
          output +=
            "<strong>Bank:</strong> " +
            escapeHtml(details.ach_bank_name || "") +
            "<br>";
          output +=
            "<strong>Account:</strong> " +
            escapeHtml(details.ach_account_number || "") +
            "<br>";
          output +=
            "<strong>ABA Routing:</strong> " +
            escapeHtml(details.ach_aba || "") +
            "<br>";
          output +=
            "<strong>Account Holder:</strong> " +
            escapeHtml(details.ach_account_holder || "");
          break;

        case "swift":
          output +=
            "<strong>Bank:</strong> " +
            escapeHtml(details.swift_bank_name || "") +
            "<br>";
          output +=
            "<strong>SWIFT/BIC:</strong> " +
            escapeHtml(details.swift_code || "") +
            "<br>";
          output +=
            "<strong>IBAN/Account:</strong> " +
            escapeHtml(details.swift_iban_account || "") +
            "<br>";
          output +=
            "<strong>Account Holder:</strong> " +
            escapeHtml(details.swift_account_holder || "") +
            "<br>";
          output +=
            "<strong>Bank Address:</strong> " +
            escapeHtml(details.swift_bank_address || "") +
            "<br>";
          output +=
            "<strong>Beneficiary Address:</strong> " +
            escapeHtml(details.swift_beneficiary_address || "");

          if (
            details.swift_intermediary_swift ||
            details.swift_intermediary_iban
          ) {
            output += "<br><em>Intermediary Details:</em><br>";
            if (details.swift_intermediary_swift) {
              output +=
                "<strong>Intermediary SWIFT:</strong> " +
                escapeHtml(details.swift_intermediary_swift) +
                "<br>";
            }
            if (details.swift_intermediary_iban) {
              output +=
                "<strong>Intermediary IBAN:</strong> " +
                escapeHtml(details.swift_intermediary_iban);
            }
          }
          break;

        default:
          output += "<em>Unknown payout method</em>";
          break;
      }

      output += "</div>";
      return output;
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
      const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      };
      return text.replace(/[&<>"']/g, function (m) {
        return map[m];
      });
    }

    // Export promo report CSV
    $("#export-promo-report").on("click", function (e) {
      e.preventDefault();

      // Create a form and submit it to trigger CSV download
      const form = $("<form>", {
        method: "POST",
        action: ajaxurl,
      });

      form.append(
        $("<input>", {
          type: "hidden",
          name: "action",
          value: "export_promo_report_csv",
        })
      );

      form.append(
        $("<input>", {
          type: "hidden",
          name: "nonce",
          value: flexpress_affiliate_admin.nonce,
        })
      );

      $("body").append(form);
      form.submit();
      form.remove();
    });
  } catch (error) {
    console.error("Error in affiliate admin script:", error);
  }
});
