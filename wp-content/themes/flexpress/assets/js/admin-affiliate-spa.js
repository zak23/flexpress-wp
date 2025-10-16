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
          const modal = document.getElementById("add-affiliate-modal");
          if (modal && modal.style.display !== "none") {
            modal.style.display = "none";
          }
        }
      });

      document.addEventListener("click", (e) => {
        const modal = document.getElementById("add-affiliate-modal");
        if (modal && e.target === modal) {
          modal.style.display = "none";
        }
      });
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
                                    <td>${affiliate.affiliate_id || ""}</td>
                                    <td>${affiliate.display_name || ""}</td>
                                    <td>${affiliate.email || ""}</td>
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
                                    <td>${affiliate.commission_rate || 0}%</td>
                                    <td>$${Number(
                                      affiliate.total_revenue ?? 0
                                    ).toFixed(2)}</td>
                                    <td>
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

    editAffiliate(affiliateId) {
      // Edit affiliate functionality
      alert(`Edit affiliate ${affiliateId} - to be implemented`);
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
        if (e.message && e.message.includes('Failed to execute \'observe\' on \'MutationObserver\'')) {
          console.warn('Third-party MutationObserver error ignored');
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
