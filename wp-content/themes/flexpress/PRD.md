# FlexPress Theme – Product Requirements Document (PRD)

## 1. Overview

**Theme Name:** FlexPress Premium Dark Theme  
**Description:** Sleek black and white design with minimalist aesthetics and modern video interface. All features, settings, and plugin-like tools are contained within the theme for easy deployment and updates across multiple sites.

---

## 2. Feature List

### 2.1 Theme Structure
- **Version:** 1.0.0
- **Status:** Complete
- **Description:**
  - Required files: `style.css`, `index.php`, `functions.php`, `header.php`, `footer.php`, `screenshot.png`
  - Required directories: `assets`, `includes`, `template-parts`, `page-templates`
  - Naming conventions for templates, parts, archives, singles

### 2.2 Templates System
- **Version:** 1.1.0
- **Status:** In Progress
- **Description:**
  - Specialized layouts for home, archives, single content pages
  - Page templates: home, login, lost/reset password, register, dashboard, membership, episodes, about, contact, coming soon, casting, content removal, 2257, privacy, terms, join
  - Archive templates: episode, model
  - Single templates: episode, model

### 2.3 Custom Logo
- **Version:** 1.0.0
- **Status:** Complete
- **Description:** Custom logo upload with media library integration and preview functionality

### 2.4 Navigation System
- **Version:** 1.0.0
- **Status:** Complete
- **Description:**
  - Menus: primary, footer-1, footer-2, quick-links, legal-menu
  - Automated creation and organization of menu items

### 2.5 Registration System
- **Version:** 1.0.0
- **Status:** In Progress
- **Description:**
  - Custom registration form with shortcode
  - Password strength indicator, real-time validation, AJAX submission, Bootstrap validation, social login, subscription handling
  - Shortcode: `flexpress_register_form`

### 2.6 Authentication System
- **Version:** 1.0.0
- **Status:** In Progress
- **Description:**
  - AJAX-powered login and password reset
  - Custom templates for login, lost/reset password

### 2.7 Forms System
- **Version:** 1.0.0
- **Status:** In Progress
- **Description:**
  - Contact, casting, and content removal forms
  - AJAX handling and template integration

### 2.8 Legal Pages
- **Version:** 1.0.0
- **Status:** Complete
- **Description:**
  - 2257, content removal, privacy, terms
  - Customizable content and Last Updated date

### 2.9 BunnyCDN Integration
- **Version:** 1.3.0
- **Status:** In Progress
- **Description:**
  - Secure video delivery, API-based thumbnails, authenticated URLs
  - Hero video on homepage with thumbnail-to-video transition

### 2.10 FlexPay & Verotel Integration
- **Version:** 1.1.0
- **Status:** In Progress
- **Description:**
  - Payment and membership system
  - Verotel FlexPay gateway integration
  - Role-agnostic access control for premium content

### 2.11 Pricing Plan Management
- **Version:** 1.0.0
- **Status:** In Progress
- **Description:**
  - Dynamic pricing plans, admin interface, trial support, featured plans, Verotel fields, AJAX admin

### 2.12 Model Profiles
- **Version:** 1.0.0
- **Status:** In Progress
- **Description:**
  - Model profile management, social links, episode relationships, featured models

### 2.13 Date Synchronization
- **Version:** 1.0.0
- **Status:** In Progress
- **Description:**
  - Align post dates with ACF release dates, consistent date display

### 2.14 CLI Utilities
- **Version:** 1.0.0
- **Status:** Complete
- **Description:**
  - Docker-based WordPress CLI utilities (`wp-docker.sh`)

### 2.15 Coming Soon Page
- **Version:** 1.0.0
- **Status:** Complete
- **Description:**
  - Minimal coming soon template with showreel video integration

---

## 3. Settings & Configuration

- **Global Theme Options:**
  - Custom logo
  - Navigation menus
  - BunnyCDN settings (API keys, library ID, token key)
  - FlexPay/Verotel settings (merchant ID, shop ID, signature key)
  - Pricing plan settings
- **Per-feature Settings:**
  - Registration: enable/disable social login, password strength
  - Authentication: AJAX login, password reset
  - Forms: enable/disable specific forms
  - Model fields: about, social links, featured status
- **Integration Points:**
  - BunnyCDN API
  - Verotel FlexPay API
  - Social login providers

---

## 4. Plugin-like Tools & Utilities

- **Custom Registration System** (`includes/class-flexpress-registration.php`, `assets/js/registration.js`)
- **Pricing Admin** (`includes/admin/class-flexpress-pricing-settings.php`, `assets/js/pricing-admin.js`)
- **CLI Utilities** (`wp-docker.sh`)
- **Date Synchronization** (`includes/admin/class-flexpress-membership-settings.php`)

---

## 5. Deployment & Update Strategy

- **Theme-only deployment:**
  - Exclude: `node_modules`, `vendor`, `.git`, `.gitignore`, `docker-compose.yml`, `Caddyfile`, `wp-config.php`, logs, SQL, archives
- **Required files:**
  - `style.css` header, `functions.php`, `screenshot.png`, `README.md`
- **Required directories:**
  - `assets`, `includes`, `template-parts`, `page-templates`
- **Update process:**
  - Keep PRD and README in sync with codebase
  - Use versioning for features and settings

---

## 6. Changelog

- **[v1.0.0]** Initial theme structure, navigation, custom logo, legal pages, CLI utilities, coming soon page
- **[v1.1.0]** Advanced templates, registration/authentication, BunnyCDN integration, FlexPay/Verotel, pricing management, model profiles, date sync

---

## 7. References

- See `README.md` for detailed setup and configuration instructions.
- All features and settings are self-contained within the theme for easy deployment across multiple sites. 