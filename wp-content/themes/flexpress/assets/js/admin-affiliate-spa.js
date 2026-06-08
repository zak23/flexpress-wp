/**
 * FlexPress Affiliate Admin SPA
 * React-based admin interface for affiliate management
 */

(function () {
  "use strict";

  // Simple React-like component system for admin interface
  const AdminAffiliateApp = {
    state: {
      currentTab: "affiliates",
      affiliates: [],
      transactions: [],
      payouts: [],
      loading: false,
      error: null,
    },

    init() {
      this.bindEvents();
      this.loadInitialData();
    },

    bindEvents() {
      // Tab switching
      document.addEventListener("click", (e) => {
        if (e.target.classList.contains("nav-tab")) {
          e.preventDefault();
          const tab = e.target.getAttribute("href").substring(1);
          this.switchTab(tab);
        }
      });

      // Add affiliate button
      const addBtn = document.getElementById("add-new-affiliate");
      if (addBtn) {
        addBtn.addEventListener("click", () => this.showAddAffiliateModal());
      }

      // Create payout button
      const payoutBtn = document.getElementById("create-payout");
      if (payoutBtn) {
        payoutBtn.addEventListener("click", () => this.showCreatePayoutModal());
      }

      // Modal close handlers
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
          this.closeModal("add-affiliate-modal", true);
          this.closeModal("affiliate-details-modal");
          this.closeModal("edit-affiliate-modal");
        }
      });

      document.addEventListener("click", (e) => {
        const addModal = document.getElementById("add-affiliate-modal");
        if (addModal && e.target === addModal) {
          addModal.style.display = "none";
        }

        if (e.target.classList.contains("modal-close")) {
          const modal = e.target.closest(".affiliate-modal");
          if (modal) {
            if (modal.id === "add-affiliate-modal") {
              modal.style.display = "none";
            } else {
              modal.remove();
            }
          }
        }

        if (
          e.target.classList.contains("affiliate-modal") &&
          e.target.id !== "add-affiliate-modal"
        ) {
          e.target.remove();
        }
      });
    },

    escapeHtml(value) {
      const div = document.createElement("div");
      div.textContent = value == null ? "" : String(value);
      return div.innerHTML;
    },

    formatPayoutMethod(method) {
      const labels = {
        paypal: "PayPal",
        crypto: "Cryptocurrency",
        aus_bank_transfer: "Australian Bank Transfer",
        yoursafe: "Yoursafe",
        ach: "ACH",
        swift: "Swift International",
      };
      return labels[method] || method || "Not set";
    },

    normalizeAffiliateDetails(data) {
      if (!data) {
        return null;
      }
      if (data.affiliate) {
        return {
          ...data.affiliate,
          promo_codes: data.promo_codes || [],
          transactions: data.transactions || [],
          clicks: data.clicks || [],
        };
      }
      return data;
    },

    async fetchAffiliateDetails(affiliateId) {
      const formData = new FormData();
      formData.append("action", "get_affiliate_details");
      formData.append("nonce", flexpress_admin.affiliate_nonce);
      formData.append("affiliate_id", affiliateId);

      const response = await fetch(flexpress_admin.ajaxurl, {
        method: "POST",
        body: formData,
      });

      const result = await response.json();
      if (!result.success) {
        throw new Error(
          result.data?.message || "Failed to load affiliate details"
        );
      }

      return this.normalizeAffiliateDetails(result.data);
    },

    closeModal(modalId, hideOnly = false) {
      const modal = document.getElementById(modalId);
      if (!modal) {
        return;
      }
      if (hideOnly) {
        modal.style.display = "none";
      } else {
        modal.remove();
      }
    },

    switchTab(tab) {
      // Update nav tabs
      document
        .querySelectorAll(".nav-tab")
        .forEach((t) => t.classList.remove("nav-tab-active"));
      document
        .querySelector(`[href="#${tab}"]`)
        .classList.add("nav-tab-active");

      // Update content
      document
        .querySelectorAll(".tab-content")
        .forEach((t) => t.classList.remove("active"));
      document.getElementById(tab).classList.add("active");

      this.state.currentTab = tab;
      this.loadTabData(tab);
    },

    async loadInitialData() {
      this.state.loading = true;
      try {
        await Promise.all([
          this.loadAffiliates(),
          this.loadTransactions(),
          this.loadPayouts(),
        ]);
      } catch (error) {
        this.state.error = error.message;
      } finally {
        this.state.loading = false;
      }
    },

    async loadTabData(tab) {
      switch (tab) {
        case "affiliates":
          await this.loadAffiliates();
          break;
        case "transactions":
          await this.loadTransactions();
          break;
        case "payouts":
          await this.loadPayouts();
          break;
      }
    },

    async loadAffiliates() {
      try {
        const response = await fetch(
          `${flexpress_admin.rest_url}flexpress/v1/admin/affiliates`,
          {
            headers: {
              "X-WP-Nonce": flexpress_admin.nonce,
            },
          }
        );

        if (!response.ok) throw new Error("Failed to load affiliates");

        const data = await response.json();
        this.state.affiliates = data;
        this.renderAffiliates();
      } catch (error) {
        console.error("Error loading affiliates:", error);
        this.renderError("affiliate-admin-app", "Failed to load affiliates");
      }
    },

    async loadTransactions() {
      try {
        const response = await fetch(
          `${flexpress_admin.rest_url}flexpress/v1/admin/transactions`,
          {
            headers: {
              "X-WP-Nonce": flexpress_admin.nonce,
            },
          }
        );

        if (!response.ok) throw new Error("Failed to load transactions");

        const data = await response.json();
        this.state.transactions = data;
        this.renderTransactions();
      } catch (error) {
        console.error("Error loading transactions:", error);
        this.renderError(
          "transactions-admin-app",
          "Failed to load transactions"
        );
      }
    },

    async loadPayouts() {
      try {
        const response = await fetch(
          `${flexpress_admin.rest_url}flexpress/v1/admin/payouts`,
          {
            headers: {
              "X-WP-Nonce": flexpress_admin.nonce,
            },
          }
        );

        if (!response.ok) throw new Error("Failed to load payouts");

        const data = await response.json();
        this.state.payouts = data;
        this.renderPayouts();
      } catch (error) {
        console.error("Error loading payouts:", error);
        this.renderError("payouts-admin-app", "Failed to load payouts");
      }
    },

    renderAffiliates() {
      const container = document.getElementById("affiliate-admin-app");
      if (!container) return;

      if (this.state.affiliates.length === 0) {
        container.innerHTML = '<p class="no-data">No affiliates found.</p>';
        return;
      }

      const html = `
                <div class="affiliates-admin-interface">
                    <div class="affiliates-header">
                        <h3>Affiliate Management</h3>
                        <button class="button button-primary" onclick="AdminAffiliateApp.showAddAffiliateModal()">
                            Add New Affiliate
                        </button>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Affiliate ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Commission Rate</th>
                                <th>Total Revenue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${this.state.affiliates
                              .map(
                                (affiliate) => `
                                <tr>
                                    <td>${affiliate.affiliate_code || ""}</td>
                                    <td>${this.escapeHtml(affiliate.display_name || "")}</td>
                                    <td>${this.escapeHtml(affiliate.email || "")}</td>
                                    <td>
                                        <span class="status-badge status-${
                                          affiliate.status || "pending"
                                        }">
                                            ${
                                              (affiliate.status || "pending")
                                                .charAt(0)
                                                .toUpperCase() +
                                              (
                                                affiliate.status || "pending"
                                              ).slice(1)
                                            }
                                        </span>
                                    </td>
                                    <td>${affiliate.commission_initial ?? 0}% / ${affiliate.commission_rebill ?? 0}% / ${affiliate.commission_unlock ?? 0}%</td>
                                    <td>$${Number(
                                      affiliate.total_revenue ?? 0
                                    ).toFixed(2)}</td>
                                    <td>
                                        <button class="button button-small" onclick="AdminAffiliateApp.viewAffiliate(${
                                          affiliate.id
                                        })">
                                            View
                                        </button>
                                        <button class="button button-small" onclick="AdminAffiliateApp.editAffiliate(${
                                          affiliate.id
                                        })">
                                            Edit
                                        </button>
                                        <button class="button button-small" onclick="AdminAffiliateApp.toggleAffiliateStatus(${
                                          affiliate.id
                                        }, '${affiliate.status}')">
                                            ${
                                              affiliate.status === "active"
                                                ? "Suspend"
                                                : "Activate"
                                            }
                                        </button>
                                    </td>
                                </tr>
                            `
                              )
                              .join("")}
                        </tbody>
                    </table>
                </div>
            `;

      container.innerHTML = html;
    },

    renderTransactions() {
      const container = document.getElementById("transactions-admin-app");
      if (!container) return;

      if (this.state.transactions.length === 0) {
        container.innerHTML = '<p class="no-data">No transactions found.</p>';
        return;
      }

      const html = `
                <div class="transactions-admin-interface">
                    <h3>Transaction History</h3>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Affiliate</th>
                                <th>Type</th>
                                <th>Revenue</th>
                                <th>Commission</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${this.state.transactions
                              .map(
                                (transaction) => `
                                <tr>
                                    <td>${transaction.created_at || ""}</td>
                                    <td>${transaction.affiliate_id || ""}</td>
                                    <td>${
                                      transaction.transaction_type || ""
                                    }</td>
                                    <td>$${Number(
                                      transaction.revenue_amount ?? 0
                                    ).toFixed(2)}</td>
                                    <td>$${Number(
                                      transaction.commission_amount ?? 0
                                    ).toFixed(2)}</td>
                                    <td>
                                        <span class="status-badge status-${
                                          transaction.status || "pending"
                                        }">
                                            ${
                                              (transaction.status || "pending")
                                                .charAt(0)
                                                .toUpperCase() +
                                              (
                                                transaction.status || "pending"
                                              ).slice(1)
                                            }
                                        </span>
                                    </td>
                                </tr>
                            `
                              )
                              .join("")}
                        </tbody>
                    </table>
                </div>
            `;

      container.innerHTML = html;
    },

    renderPayouts() {
      const container = document.getElementById("payouts-admin-app");
      if (!container) return;

      if (this.state.payouts.length === 0) {
        container.innerHTML = '<p class="no-data">No payouts found.</p>';
        return;
      }

      const html = `
                <div class="payouts-admin-interface">
                    <div class="payouts-header">
                        <h3>Payout Management</h3>
                        <div class="payout-actions">
                            <button class="button button-primary" onclick="AdminAffiliateApp.showCreatePayoutModal()">
                                Create Payout
                            </button>
                            <select id="export-method" class="button">
                                <option value="">Export All</option>
                                <option value="paypal">PayPal</option>
                                <option value="crypto">Crypto</option>
                                <option value="aus_bank_transfer">AUS Bank Transfer</option>
                                <option value="yoursafe">Yoursafe</option>
                                <option value="ach">ACH</option>
                                <option value="swift">SWIFT</option>
                            </select>
                            <button class="button" onclick="AdminAffiliateApp.exportPayouts()">
                                Export CSV
                            </button>
                        </div>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Affiliate</th>
                                <th>Period</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${this.state.payouts
                              .map(
                                (payout) => `
                                <tr>
                                    <td>${payout.affiliate_id || ""}</td>
                                    <td>${payout.period_start || ""} → ${
                                  payout.period_end || ""
                                }</td>
                                    <td>$${Number(
                                      payout.payout_amount ?? 0
                                    ).toFixed(2)}</td>
                                    <td>${payout.payout_method || ""}</td>
                                    <td>
                                        <span class="status-badge status-${
                                          payout.status || "pending"
                                        }">
                                            ${
                                              (payout.status || "pending")
                                                .charAt(0)
                                                .toUpperCase() +
                                              (
                                                payout.status || "pending"
                                              ).slice(1)
                                            }
                                        </span>
                                    </td>
                                    <td>
                                        ${
                                          payout.status === "pending"
                                            ? `
                                            <button class="button button-small button-primary" onclick="AdminAffiliateApp.approvePayout(${payout.id})">
                                                Approve
                                            </button>
                                            <button class="button button-small" onclick="AdminAffiliateApp.denyPayout(${payout.id})">
                                                Deny
                                            </button>
                                        `
                                            : ""
                                        }
                                        ${
                                          payout.status === "approved"
                                            ? `
                                            <button class="button button-small button-primary" onclick="AdminAffiliateApp.completePayout(${payout.id})">
                                                Mark Complete
                                            </button>
                                        `
                                            : ""
                                        }
                                    </td>
                                </tr>
                            `
                              )
                              .join("")}
                        </tbody>
                    </table>
                </div>
            `;

      container.innerHTML = html;
    },

    renderError(containerId, message) {
      const container = document.getElementById(containerId);
      if (container) {
        container.innerHTML = `<div class="error-message"><p>${message}</p></div>`;
      }
    },

    async approvePayout(payoutId) {
      try {
        const response = await fetch(
          `${flexpress_admin.rest_url}flexpress/v1/admin/payouts/${payoutId}/approve`,
          {
            method: "POST",
            headers: {
              "X-WP-Nonce": flexpress_admin.nonce,
            },
          }
        );

        if (!response.ok) throw new Error("Failed to approve payout");

        await this.loadPayouts();
        this.showNotice("Payout approved successfully", "success");
      } catch (error) {
        console.error("Error approving payout:", error);
        this.showNotice("Failed to approve payout", "error");
      }
    },

    async denyPayout(payoutId) {
      try {
        const response = await fetch(
          `${flexpress_admin.rest_url}flexpress/v1/admin/payouts/${payoutId}/deny`,
          {
            method: "POST",
            headers: {
              "X-WP-Nonce": flexpress_admin.nonce,
            },
          }
        );

        if (!response.ok) throw new Error("Failed to deny payout");

        await this.loadPayouts();
        this.showNotice("Payout denied", "success");
      } catch (error) {
        console.error("Error denying payout:", error);
        this.showNotice("Failed to deny payout", "error");
      }
    },

    async completePayout(payoutId) {
      try {
        const response = await fetch(
          `${flexpress_admin.rest_url}flexpress/v1/admin/payouts/${payoutId}/complete`,
          {
            method: "POST",
            headers: {
              "X-WP-Nonce": flexpress_admin.nonce,
            },
          }
        );

        if (!response.ok) throw new Error("Failed to complete payout");

        await this.loadPayouts();
        this.showNotice("Payout marked as complete", "success");
      } catch (error) {
        console.error("Error completing payout:", error);
        this.showNotice("Failed to complete payout", "error");
      }
    },

    showAddAffiliateModal() {
      // Use existing modal or create new one
      const modal = document.getElementById("add-affiliate-modal");
      if (modal) {
        modal.style.display = "block";
      }
    },

    showCreatePayoutModal() {
      // Create payout modal
      alert("Create payout functionality - to be implemented");
    },

    async exportPayouts() {
      try {
        const method = document.getElementById("export-method").value;
        const url = `${
          flexpress_admin.rest_url
        }flexpress/v1/admin/payouts/export${method ? `?method=${method}` : ""}`;

        const response = await fetch(url, {
          headers: {
            "X-WP-Nonce": flexpress_admin.nonce,
          },
        });

        if (!response.ok) throw new Error("Failed to export payouts");

        const data = await response.json();

        // Create and download CSV file
        const blob = new Blob([data.csv], { type: "text/csv" });
        const url_blob = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url_blob;
        a.download = `payouts-export-${data.method}-${
          new Date().toISOString().split("T")[0]
        }.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url_blob);

        this.showNotice(
          `Payouts exported successfully (${data.method})`,
          "success"
        );
      } catch (error) {
        console.error("Error exporting payouts:", error);
        this.showNotice("Failed to export payouts", "error");
      }
    },

    async viewAffiliate(affiliateId) {
      try {
        const data = await this.fetchAffiliateDetails(affiliateId);
        this.showViewAffiliateModal(data);
      } catch (error) {
        console.error("Error loading affiliate details:", error);
        this.showNotice(error.message || "Failed to load affiliate details", "error");
      }
    },

    async editAffiliate(affiliateId) {
      try {
        const data = await this.fetchAffiliateDetails(affiliateId);
        this.showEditAffiliateModal(data);
      } catch (error) {
        console.error("Error loading affiliate details:", error);
        this.showNotice(error.message || "Failed to load affiliate details", "error");
      }
    },

    showViewAffiliateModal(data) {
      this.closeModal("affiliate-details-modal");
      this.closeModal("edit-affiliate-modal");

      const status = data.status || "pending";
      const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
      const modal = document.createElement("div");
      modal.id = "affiliate-details-modal";
      modal.className = "affiliate-modal";
      modal.innerHTML = `
        <div class="modal-content">
          <div class="modal-header">
            <h2>Affiliate Details: ${this.escapeHtml(data.display_name || "")}</h2>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
          </div>
          <div class="modal-body">
            <div class="affiliate-details-grid">
              <div class="detail-section">
                <h3>Basic Information</h3>
                <p><strong>Name:</strong> ${this.escapeHtml(data.display_name || "")}</p>
                <p><strong>Email:</strong> ${this.escapeHtml(data.email || "")}</p>
                <p><strong>Website:</strong> ${this.escapeHtml(data.website || "Not provided")}</p>
                <p><strong>Status:</strong> <span class="status-badge status-${this.escapeHtml(status)}">${this.escapeHtml(statusLabel)}</span></p>
                <p><strong>Affiliate Code:</strong> <code>${this.escapeHtml(data.affiliate_code || "")}</code></p>
                <p><strong>Referral URL:</strong> ${
                  data.referral_url
                    ? `<a href="${this.escapeHtml(data.referral_url)}" target="_blank" rel="noopener noreferrer">${this.escapeHtml(data.referral_url)}</a>`
                    : "Not set"
                }</p>
              </div>
              <div class="detail-section">
                <h3>Commission Settings</h3>
                <p><strong>Initial Commission:</strong> ${data.commission_initial ?? 0}%</p>
                <p><strong>Rebill Commission:</strong> ${data.commission_rebill ?? 0}%</p>
                <p><strong>Unlock Commission:</strong> ${data.commission_unlock ?? 0}%</p>
                <p><strong>Payout Threshold:</strong> $${Number(data.payout_threshold ?? 0).toFixed(2)}</p>
              </div>
              <div class="detail-section">
                <h3>Payout Information</h3>
                <p><strong>Method:</strong> ${this.escapeHtml(this.formatPayoutMethod(data.payout_method))}</p>
                <p><strong>Details:</strong> ${this.escapeHtml(data.payout_details || "Not provided")}</p>
              </div>
              <div class="detail-section">
                <h3>Performance Statistics</h3>
                <p><strong>Total Clicks:</strong> ${data.total_clicks ?? 0}</p>
                <p><strong>Total Signups:</strong> ${data.total_signups ?? 0}</p>
                <p><strong>Total Revenue:</strong> $${Number(data.total_revenue ?? 0).toFixed(2)}</p>
                <p><strong>Total Commission:</strong> $${Number(data.total_commission ?? 0).toFixed(2)}</p>
                <p><strong>Pending Commission:</strong> $${Number(data.pending_commission ?? 0).toFixed(2)}</p>
                <p><strong>Approved Commission:</strong> $${Number(data.approved_commission ?? 0).toFixed(2)}</p>
                <p><strong>Paid Commission:</strong> $${Number(data.paid_commission ?? 0).toFixed(2)}</p>
              </div>
              ${
                data.notes
                  ? `<div class="detail-section"><h3>Notes</h3><p>${this.escapeHtml(data.notes)}</p></div>`
                  : ""
              }
            </div>
            <div class="form-actions">
              <button type="button" class="button button-primary" id="view-to-edit-affiliate" data-id="${data.id}">
                Edit Affiliate
              </button>
              <button type="button" class="button modal-close">Close</button>
            </div>
          </div>
        </div>
      `;

      document.body.appendChild(modal);

      modal.querySelector("#view-to-edit-affiliate").addEventListener("click", () => {
        this.closeModal("affiliate-details-modal");
        this.showEditAffiliateModal(data);
      });
    },

    showEditAffiliateModal(data) {
      this.closeModal("affiliate-details-modal");
      this.closeModal("edit-affiliate-modal");

      const modal = document.createElement("div");
      modal.id = "edit-affiliate-modal";
      modal.className = "affiliate-modal";
      modal.innerHTML = `
        <div class="modal-content">
          <div class="modal-header">
            <h2>Edit Affiliate: ${this.escapeHtml(data.display_name || "")}</h2>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
          </div>
          <div class="modal-body">
            <form id="edit-affiliate-form">
              <input type="hidden" name="affiliate_id" value="${data.id}">
              <div class="form-field">
                <label for="edit-affiliate-name">Affiliate Name *</label>
                <input type="text" id="edit-affiliate-name" name="display_name" value="${this.escapeHtml(data.display_name || "")}" required>
              </div>
              <div class="form-field">
                <label for="edit-affiliate-email">Email Address *</label>
                <input type="email" id="edit-affiliate-email" name="email" value="${this.escapeHtml(data.email || "")}" required>
              </div>
              <div class="form-field">
                <label for="edit-affiliate-website">Website/Social Media</label>
                <input type="url" id="edit-affiliate-website" name="website" value="${this.escapeHtml(data.website || "")}" placeholder="https://example.com">
              </div>
              <div class="form-field">
                <label for="edit-payout-method">Payout Method *</label>
                <select id="edit-payout-method" name="payout_method" required>
                  <option value="">Select payout method</option>
                  <option value="paypal" ${data.payout_method === "paypal" ? "selected" : ""}>PayPal (Free)</option>
                  <option value="crypto" ${data.payout_method === "crypto" ? "selected" : ""}>Cryptocurrency (Free)</option>
                  <option value="aus_bank_transfer" ${data.payout_method === "aus_bank_transfer" ? "selected" : ""}>Australian Bank Transfer (Free)</option>
                  <option value="yoursafe" ${data.payout_method === "yoursafe" ? "selected" : ""}>Yoursafe (Free)</option>
                  <option value="ach" ${data.payout_method === "ach" ? "selected" : ""}>ACH - US Only ($10 USD Fee)</option>
                  <option value="swift" ${data.payout_method === "swift" ? "selected" : ""}>Swift International ($30 USD Fee)</option>
                </select>
              </div>
              <div class="form-field">
                <label for="edit-payout-details">Payout Details *</label>
                <input type="text" id="edit-payout-details" name="payout_details" value="${this.escapeHtml(data.payout_details || "")}" required placeholder="PayPal email, bank account, etc.">
              </div>
              <div class="form-field">
                <label for="edit-commission-initial">Initial Commission Rate (%)</label>
                <input type="number" id="edit-commission-initial" name="commission_initial" min="0" max="100" step="0.1" value="${data.commission_initial ?? 25}">
              </div>
              <div class="form-field">
                <label for="edit-commission-rebill">Rebill Commission Rate (%)</label>
                <input type="number" id="edit-commission-rebill" name="commission_rebill" min="0" max="100" step="0.1" value="${data.commission_rebill ?? 10}">
              </div>
              <div class="form-field">
                <label for="edit-commission-unlock">Unlock Commission Rate (%)</label>
                <input type="number" id="edit-commission-unlock" name="commission_unlock" min="0" max="100" step="0.1" value="${data.commission_unlock ?? 15}">
              </div>
              <div class="form-field">
                <label for="edit-payout-threshold">Payout Threshold ($)</label>
                <input type="number" id="edit-payout-threshold" name="payout_threshold" min="0" step="0.01" value="${data.payout_threshold ?? 100}">
              </div>
              <div class="form-field">
                <label for="edit-affiliate-status">Status</label>
                <select id="edit-affiliate-status" name="status">
                  <option value="pending" ${data.status === "pending" ? "selected" : ""}>Pending</option>
                  <option value="active" ${data.status === "active" ? "selected" : ""}>Active</option>
                  <option value="suspended" ${data.status === "suspended" ? "selected" : ""}>Suspended</option>
                  <option value="rejected" ${data.status === "rejected" ? "selected" : ""}>Rejected</option>
                </select>
              </div>
              <div class="form-field">
                <label for="edit-affiliate-notes">Notes</label>
                <textarea id="edit-affiliate-notes" name="notes" rows="3" placeholder="Additional notes about this affiliate...">${this.escapeHtml(data.notes || "")}</textarea>
              </div>
              <div class="form-actions">
                <button type="submit" class="button button-primary">Update Affiliate</button>
                <button type="button" class="button modal-close">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      `;

      document.body.appendChild(modal);

      modal.querySelector("#edit-affiliate-form").addEventListener("submit", (e) => {
        e.preventDefault();
        this.saveAffiliate(modal);
      });
    },

    async saveAffiliate(modal) {
      const form = modal.querySelector("#edit-affiliate-form");
      const submitButton = form.querySelector('button[type="submit"]');
      const displayName = form.querySelector("#edit-affiliate-name").value.trim();
      const email = form.querySelector("#edit-affiliate-email").value.trim();
      const payoutMethod = form.querySelector("#edit-payout-method").value;
      const payoutDetails = form.querySelector("#edit-payout-details").value.trim();

      if (displayName.length < 2) {
        this.showNotice("Affiliate name must be at least 2 characters.", "error");
        return;
      }

      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        this.showNotice("Please enter a valid email address.", "error");
        return;
      }

      if (!payoutMethod) {
        this.showNotice("Please select a payout method.", "error");
        return;
      }

      if (!payoutDetails) {
        this.showNotice("Please enter payout details.", "error");
        return;
      }

      const formData = new FormData();
      formData.append("action", "update_affiliate");
      formData.append("nonce", flexpress_admin.affiliate_nonce);
      formData.append("affiliate_id", form.querySelector('[name="affiliate_id"]').value);
      formData.append("display_name", displayName);
      formData.append("email", email);
      formData.append("website", form.querySelector("#edit-affiliate-website").value.trim());
      formData.append("payout_method", payoutMethod);
      formData.append("payout_details", payoutDetails);
      formData.append("commission_initial", form.querySelector("#edit-commission-initial").value || "25");
      formData.append("commission_rebill", form.querySelector("#edit-commission-rebill").value || "10");
      formData.append("commission_unlock", form.querySelector("#edit-commission-unlock").value || "15");
      formData.append("payout_threshold", form.querySelector("#edit-payout-threshold").value || "100");
      formData.append("status", form.querySelector("#edit-affiliate-status").value);
      formData.append("notes", form.querySelector("#edit-affiliate-notes").value.trim());

      submitButton.disabled = true;
      submitButton.textContent = "Updating...";

      try {
        const response = await fetch(flexpress_admin.ajaxurl, {
          method: "POST",
          body: formData,
        });
        const result = await response.json();

        if (!result.success) {
          throw new Error(result.data?.message || "Failed to update affiliate");
        }

        this.closeModal("edit-affiliate-modal");
        await this.loadAffiliates();
        this.showNotice(
          result.data?.message || "Affiliate updated successfully",
          "success"
        );
      } catch (error) {
        console.error("Error updating affiliate:", error);
        this.showNotice(error.message || "Failed to update affiliate", "error");
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Update Affiliate";
      }
    },

    async toggleAffiliateStatus(affiliateId, currentStatus) {
      const newStatus = currentStatus === "active" ? "suspended" : "active";
      try {
        const response = await fetch(
          `${flexpress_admin.rest_url}flexpress/v1/admin/affiliates/${affiliateId}`,
          {
            method: "PATCH",
            headers: {
              "X-WP-Nonce": flexpress_admin.nonce,
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ status: newStatus }),
          }
        );

        if (!response.ok) throw new Error("Failed to update affiliate status");

        await this.loadAffiliates();
        this.showNotice(`Affiliate ${newStatus} successfully`, "success");
      } catch (error) {
        console.error("Error updating affiliate status:", error);
        this.showNotice("Failed to update affiliate status", "error");
      }
    },

    showNotice(message, type = "info") {
      const notice = document.createElement("div");
      notice.className = `notice notice-${type} is-dismissible`;
      notice.innerHTML = `<p>${message}</p>`;

      const wrap = document.querySelector(".wrap");
      if (wrap) {
        wrap.insertBefore(notice, wrap.firstChild);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
          if (notice.parentNode) {
            notice.parentNode.removeChild(notice);
          }
        }, 5000);
      }
    },
  };

  // Initialize when DOM is ready and target containers exist
  function initializeWhenReady() {
    const hasTargets = document.querySelector(
      "#affiliate-admin-app, #transactions-admin-app, #payouts-admin-app"
    );
    if (hasTargets) {
      try {
        AdminAffiliateApp.init();
      } catch (e) {
        // Ignore third-party MutationObserver errors
        if (
          e.message &&
          e.message.includes(
            "Failed to execute 'observe' on 'MutationObserver'"
          )
        ) {
          console.warn("Third-party MutationObserver error ignored");
        } else {
          throw e;
        }
      }
    } else {
      setTimeout(initializeWhenReady, 100);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeWhenReady);
  } else {
    initializeWhenReady();
  }

  // Make it globally available
  window.AdminAffiliateApp = AdminAffiliateApp;
})();
