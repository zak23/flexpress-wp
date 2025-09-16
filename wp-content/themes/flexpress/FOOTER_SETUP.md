# 🚀 FlexPress Auto-Setup System

## Overview

FlexPress is designed as a **complete turnkey solution** for adult content paysites using Verotel payment processing and BunnyCDN video delivery. When you activate the theme, it automatically creates all required pages, menus, and configurations.

## 🎯 What Gets Created Automatically

### 1. Main Navigation Menu (`footer-menu`)
- **Home** - Landing page with home template
- **Episodes** - Episode archive with custom template  
- **Models** - Model profiles and listings
- **Extras** - Additional content section
- **Livestream** - Live streaming content
- **About** - About page with custom template
- **Casting** - Model application form with custom template
- **Contact** - Contact form with custom template

### 2. Support Menu (`footer-support-menu`)
- **Join** - Membership signup with pricing plans
- **Login** - Custom login page with AJAX functionality
- **My Account** - User dashboard with subscription management
- **Reset Password** - Password reset functionality
- **Cancel** - Subscription cancellation
- **Affiliates** - Partner/webmaster information
- **Log Out** - Automatic logout link (generated dynamically)

### 3. Legal Menu (`footer-legal-menu`)
- **Privacy Policy** - Privacy terms with ACF integration
- **Terms & Conditions** - Customer terms and conditions
- **2257 Compliance** - Age verification compliance
- **Anti-Slavery Policy** - Anti-trafficking policy
- **Content Removal** - DMCA/content removal form

### 4. Friends Menu (`footer-friends-menu`)
- **Exclusv.Life** - Premium Adult Content Platform
- **Adult Site Broker** - Adult Website Brokerage Services
- **Zak Ozbourne** - Adult Web Developer

### 5. WordPress Configuration
- Search engine discouragement enabled
- Comments disabled by default
- Trackbacks/pingbacks disabled
- Permalink structure set to `/%postname%/`
- Default timezone configuration
- Date format optimization

## ⚡ Auto-Setup Triggers

The auto-setup runs automatically when:

1. **Theme Activation** - First time theme is activated
2. **Theme Switch** - When switching back to FlexPress
3. **Fresh Installation** - When less than 10 pages exist
4. **Manual Trigger** - Via admin interface

## 🛡️ Safety Features

- **Smart Detection** - Only runs on fresh installations or when explicitly triggered
- **Duplicate Prevention** - Won't overwrite existing pages
- **Logging** - Comprehensive error logging for troubleshooting
- **Status Tracking** - Setup completion status stored in database
- **Manual Override** - Force re-run option in admin

## 📊 Admin Interface

### Auto-Setup Status Dashboard
Located in **FlexPress → General Settings**

**Displays:**
- ✅ Setup completion status
- 📅 Setup completion date
- 📊 Number of pages created
- 🔄 Manual re-run options

### Manual Controls
- **Run Auto-Setup Now** - For fresh installations
- **Force Re-Run Auto-Setup** - Recreates everything
- **Individual Page Group Creation** - Legacy manual options

## 🔧 Technical Implementation

### Database Options
- `flexpress_auto_setup_completed` - Boolean flag
- `flexpress_auto_setup_date` - Timestamp of completion
- `flexpress_auto_setup_results` - Array of creation results

### WordPress Hooks
- `after_switch_theme` - Theme activation trigger
- `after_setup_theme` - Secondary trigger
- `flexpress_complete_auto_setup_hook` - Scheduled execution

### Page Templates
Each page is assigned the appropriate template:
- `page-templates/page-home.php`
- `page-templates/episodes.php`
- `page-templates/about.php`
- `page-templates/casting.php`
- `page-templates/contact.php`
- `page-templates/join.php`
- `page-templates/login.php`
- `page-templates/dashboard.php`
- `page-templates/reset-password.php`
- `page-templates/privacy.php`
- `page-templates/terms.php`
- `page-templates/2257-compliance.php`
- `page-templates/anti-slavery.php`
- `page-templates/content-removal.php`

## 🎨 Footer Menu Structure

The footer displays three distinct menu sections:

```html
<!-- Main Navigation -->
<div class="col-md-4">
    <h4>Menu</h4>
    <!-- footer-menu items -->
</div>

<!-- Support Links -->
<div class="col-md-4">
    <h4>Support</h4>
    <!-- footer-support-menu items -->
    <!-- Log Out (dynamic) -->
</div>

<!-- Legal Pages -->
<div class="col-md-4">
    <h4>Legal</h4>
    <!-- footer-legal-menu items -->
</div>
```

## 🚨 Troubleshooting

### Auto-Setup Not Running
1. Check **FlexPress → General Settings** for status
2. Click **Run Auto-Setup Now** if needed
3. Review error logs in WordPress debug.log

### Pages Not Created
- Verify user has `manage_options` capability
- Check for PHP memory limits (increased to 512M)
- Review WordPress error logs

### Menu Not Displaying
1. Verify menu locations are registered in theme
2. Check footer.php for proper `wp_nav_menu()` calls
3. Assign menus in **Appearance → Menus**

## 🔗 Integration Points

### Verotel FlexPay
- Join page integrates with pricing plans
- Payment processing for subscriptions and PPV
- Webhook handling for payment confirmations

### BunnyCDN Stream
- Episode templates configured for video delivery
- Thumbnail generation and caching
- Secure token-based video URLs

### ACF (Advanced Custom Fields)
- Legal page settings and content
- Episode metadata and video fields
- Model profile information

## 📋 Next Steps After Auto-Setup

1. **Configure Verotel** - Add merchant credentials
2. **Set Up BunnyCDN** - Configure stream settings  
3. **Create Pricing Plans** - Set subscription options
4. **Upload Logo** - Brand customization
5. **Add Content** - Episodes, models, and pages
6. **Test Payment Flow** - Verify complete functionality

## 💡 Benefits

- **Zero Configuration** - Works out of the box
- **Complete Solution** - All pages and functionality included
- **Professional Structure** - Proper menu organization
- **SEO Optimized** - WordPress best practices
- **Adult Industry Compliant** - Legal pages and compliance features
- **Payment Ready** - Verotel integration built-in
- **Video Optimized** - BunnyCDN streaming configured

This auto-setup system transforms FlexPress from a theme into a **complete business solution** for adult content creators and paysite operators. 