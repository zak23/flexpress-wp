# FlexPress WordPress Theme

**A premium WordPress theme for content creators with integrated payment processing, membership management, and secure video delivery.**

FlexPress is designed specifically for content websites (primarily adult content) but can be adapted for any premium content platform. It includes comprehensive built-in integrations and management tools, eliminating the need for multiple plugins.

**Test Site:** ZaksPOV.com (development data only)

---

## 🌟 Core Features

### 💳 Payment Processing
- **Flowguard Integration**: Modern payment processing with embedded forms
- **Remember Card Feature**: Secure card storage for faster future payments
- **Comprehensive Validation System**: Real-time field validation with user-friendly error messages
- **Error Handling & Recovery**: Automatic retry mechanisms and graceful error recovery
- **Verotel FlexPay Integration**: Legacy payment processing system (being phased out)
- **Subscription Management**: Recurring billing and membership tiers
- **Pay-Per-View (PPV)**: Individual episode purchases with unlock buttons
- **Member Discounts**: Flexible pricing for subscribers
- **Webhook Processing**: Real-time payment confirmations
- **Affiliate System**: Complete commission tracking and management system
- **Affiliate Application Management**: Comprehensive admin interface for managing affiliate applications and accounts
- **Bulk Operations**: Approve, reject, suspend, or reactivate multiple affiliates at once
- **Status Notifications**: Automatic email notifications to affiliates when status changes
- **Performance Tracking**: Real-time statistics and revenue tracking for affiliate accounts
- **Promo Codes System**: Dedicated promotional code management separate from affiliate codes
- **Flexible Discount Types**: Percentage, fixed amount, and free trial discounts
- **Usage Tracking**: Comprehensive analytics and usage limits
- **Payment Integration**: Seamless integration with Flowguard payment processing

### 🎥 Video Management
- **BunnyCDN Stream Integration**: Secure video hosting and streaming
- **Thumbnail Generation**: Automatic video previews
- **Access Control**: Free, PPV, membership, and mixed access types
- **Token-Based Security**: Secure video URLs with expiration
- **Multiple Video Types**: Full episodes, trailers, and previews

### 🖼️ Gallery System
- **BunnyCDN Storage Integration**: Image hosting and optimization
- **Automatic Thumbnail Generation**: Center-cropped square thumbnails
- **Episode Galleries**: Attach image galleries to video content
- **Drag & Drop Upload**: Admin interface for easy image management
- **Lightbox Viewer**: Professional gallery display
- **Responsive Grid**: Mobile-optimized image layouts
- **Thumbnail Optimization**: Configurable thumbnail sizes (100-800px)

### 👥 Membership System
- **Custom Registration**: Advanced signup forms with validation
- **User Dashboard**: Member portal with purchase history
- **Activity Logging**: Comprehensive user interaction tracking
- **Access Management**: Grant/revoke episode access
- **Profile Management**: User information and preferences

### 🎨 Modern Interface
- **Dark Theme Design**: Vixen.com-inspired professional aesthetics
- **Responsive Layout**: Mobile-first responsive design
- **Interactive Elements**: Hover effects and smooth animations
- **Video Cards**: Clean episode presentation with play buttons
- **Model Profiles**: Performer showcase with relationship linking
- **Clean User Experience**: Admin toolbar disabled for non-admin users

---

## 🔧 Technical Integrations

### Flowguard Payment System
- **Modern Payment Processing**: Embedded payment forms with no redirects
- **Remember Card Feature**: Secure card storage for faster future payments
- **JWT Authentication**: Secure API communication with signature validation
- **Subscription Management**: Recurring and one-time subscription support
- **PPV Purchases**: Individual episode unlock with member discounts
- **Webhook Processing**: Real-time payment notifications and status updates
- **Admin Interface**: Complete payment management and diagnostics
- **Security**: PCI DSS compliance with 3D Secure support

### Verotel FlexPay (Legacy)
- **Merchant Integration**: Complete payment gateway setup
- **Webhook Handling**: Real-time payment processing
- **Security**: Signature validation and secure transactions
- **Multiple Brands**: Support for various Verotel payment brands
- **Debug Tools**: Admin interface for webhook monitoring

### BunnyCDN Services
- **Stream API**: Video hosting and delivery
- **Storage API**: Image and file management
- **Thumbnail API**: Automatic video preview generation
- **Token Authentication**: Secure content delivery
- **CDN Optimization**: Global content distribution

### Promo Codes System
- **Dedicated Management**: Separate from affiliate codes with its own admin interface
- **Multiple Discount Types**: Percentage discounts, fixed amount discounts, and free trial periods
- **Usage Controls**: Total usage limits and per-user usage limits
- **Time-based Validity**: Start and end date controls for promotional campaigns
- **Plan Restrictions**: Apply codes to specific subscription plans or PPV content
- **Real-time Validation**: Instant promo code validation with detailed error messages
- **Usage Analytics**: Comprehensive tracking of promo code usage and effectiveness
- **Payment Integration**: Seamless integration with Flowguard payment processing
- **Frontend Shortcodes**: Display active promo codes and application forms
- **Admin Dashboard**: Complete management interface with bulk operations

### WordPress Integration
- **Custom Post Types**: Episodes and Models
- **Advanced Custom Fields (ACF)**: Content management
- **User Roles**: Member and admin capabilities
- **Menu Systems**: Automated navigation setup with Main, Support, Legal, and Friends menus
- **Template Hierarchy**: Custom page and archive templates

---

## 📂 Theme Structure

```
wp-content/themes/flexpress/
├── assets/                    # Theme assets (CSS, JS, images)
│   ├── css/                  # Stylesheets and design files
│   ├── js/                   # JavaScript functionality
│   └── images/               # Theme images and icons
├── includes/                  # Core functionality and integrations
│   ├── admin/                # Admin panel classes and interfaces
│   ├── verotel/              # Payment processing library
│   ├── acf/                  # Custom field configurations
│   ├── bunnycdn.php          # Video and storage integration
│   ├── verotel-integration.php # Payment webhook handling
│   ├── gallery-system.php    # Image gallery management
│   ├── pricing-helpers.php   # Episode pricing logic
│   ├── affiliate-helpers.php # Commission tracking
│   ├── promo-codes-integration.php # Promo codes system
│   ├── promo-codes-shortcodes.php # Frontend promo code display
│   └── contact-helpers.php   # Contact form integration
├── page-templates/           # Custom page templates
├── template-parts/           # Reusable template components
├── functions.php             # Theme setup and core functions
├── style.css                 # Theme stylesheet and metadata
├── index.php                 # Main template file
├── header.php                # Header template
├── footer.php                # Footer template
├── archive-episode.php       # Episode archive with filtering
├── archive-model.php         # Model archive display
├── single-episode.php        # Individual episode pages
├── single-model.php          # Individual model profiles
└── README.md                 # This documentation
```

---

## 🚀 Installation & Setup

### Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **BunnyCDN Account**: Stream and Storage services
- **Verotel Account**: FlexPay merchant setup

### Installation Steps
1. **Upload Theme**: Place in `wp-content/themes/flexpress/`
2. **Activate Theme**: Enable in WordPress admin
3. **Configure Settings**: Complete integration setup
4. **Create Content**: Add episodes and models
5. **Test Payments**: Verify Verotel integration

### Configuration Checklist
- [ ] **BunnyCDN Stream**: Library ID, API Key, Token Key
- [ ] **BunnyCDN Storage**: Storage zone configuration
- [ ] **Verotel FlexPay**: Merchant ID, Shop ID, Signature Key
- [ ] **Custom Logo**: Upload brand logo
- [ ] **Menu Structure**: Configure navigation menus
- [ ] **Legal Pages**: Create compliance pages
- [ ] **Payment Testing**: Verify webhook processing
- [ ] **Promo Codes**: Create and configure promotional campaigns

---

## 🎟️ Promo Codes System

### Admin Management
Access the promo codes management interface at **FlexPress Settings → Promo Codes** in the WordPress admin.

#### Creating Promo Codes
1. **Code**: Unique identifier (e.g., "SAVE20", "WELCOME10")
2. **Name**: Display name for the promotion
3. **Description**: Detailed description of the offer
4. **Discount Type**: 
   - **Percentage**: Percentage discount (e.g., 20% off)
   - **Fixed**: Fixed amount discount (e.g., $10 off)
   - **Free Trial**: Free trial period (e.g., 7 days free)
5. **Usage Limits**: Total usage limit and per-user limit
6. **Validity Period**: Start and end dates
7. **Plan Restrictions**: Apply to specific subscription plans

#### Usage Tracking
- Real-time usage statistics
- User-specific usage tracking
- Revenue impact analysis
- Expiration monitoring

### Frontend Integration

#### Payment Form Integration
Promo codes are automatically integrated into payment forms with:
- Real-time validation
- Instant discount calculation
- Error handling and user feedback
- Session-based storage

#### Shortcodes

**Display Active Promo Codes**
```
[flexpress_promo_codes limit="5" show_expiry="true" style="cards"]
```
- `limit`: Number of codes to display
- `show_expiry`: Show expiration dates
- `show_usage`: Show usage statistics
- `style`: Display style (cards, list, table)

**Promo Code Application Form**
```
[flexpress_promo_form plan_id="monthly" amount="29.99" button_text="Apply Code"]
```
- `plan_id`: Target subscription plan
- `amount`: Order amount for validation
- `button_text`: Custom button text
- `placeholder`: Input placeholder text

**Promo Banner**
```
[flexpress_promo_banner code="WELCOME20" title="Welcome Offer" description="Get 20% off your first month"]
```
- `code`: Promo code to display
- `title`: Banner title
- `description`: Offer description
- `background_color`: Custom background color
- `text_color`: Custom text color
- `show_code`: Display the promo code
- `expiry_date`: Expiration date

### API Integration

#### Validation Endpoint
```javascript
// Apply promo code
fetch(ajaxurl, {
    method: 'POST',
    body: new URLSearchParams({
        action: 'apply_promo_code',
        code: 'SAVE20',
        plan_id: 'monthly',
        amount: 29.99
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Discount:', data.data.discount_amount);
        console.log('Final Amount:', data.data.final_amount);
    }
});
```

#### Usage Recording
Promo code usage is automatically recorded when:
- Payment is successfully processed
- User applies a valid promo code
- Transaction is completed

---

## 📋 Content Management

### Episodes
- **Video Content**: Full episodes, trailers, previews
- **Access Types**: Free, PPV-only, membership, mixed pricing
- **Model Relationships**: Link performers to episodes
- **Release Scheduling**: Future episode planning
- **Duration Tracking**: Episode length management
- **Category System**: Tags and filtering

### Models
- **Profile Pages**: Individual performer showcases
- **Image Galleries**: Photo collections
- **Episode Listings**: Related content display
- **Social Links**: External profile connections
- **Biography Management**: Detailed performer information

### Galleries
- **Episode Attachment**: Link image sets to videos
- **Upload Interface**: Admin drag-and-drop tools
- **Image Optimization**: Automatic resizing
- **Alt Text Management**: Accessibility compliance
- **Ordering System**: Custom image arrangement

---

## 🖼️ Gallery Thumbnail Generation

### Overview
The FlexPress theme automatically generates square thumbnails for all gallery images uploaded to BunnyCDN Storage. This ensures consistent, optimized thumbnails for gallery displays while maintaining the original high-resolution images.

### How It Works
1. **Upload Process**: When images are uploaded via the episode gallery interface
2. **Automatic Processing**: Images are automatically center-cropped to square format
3. **Size Optimization**: Thumbnails are resized to the configured dimensions
4. **Dual Upload**: Both original and thumbnail versions are uploaded to BunnyCDN
5. **Folder Structure**: Thumbnails are stored in a `/thumbs/` subfolder

### Configuration
- **Thumbnail Size**: Configurable from 100px to 800px (default: 300px)
- **Gallery Columns**: Configurable grid layout (default: 5 columns)
- **Format**: All thumbnails are saved as JPEG for optimal compression
- **Quality**: High-quality JPEG compression maintains image clarity
- **Crop Method**: Center-crop ensures the most important part of the image is preserved

### File Structure
```
episodes/galleries/[episode_id]/
├── image-abc123-1234567890.jpg          # Original image
└── thumbs/
    └── thumb_image-abc123-1234567890.jpg # Generated thumbnail
```

### Admin Settings
Navigate to **FlexPress Settings → Video** to configure:
- **Gallery Thumbnail Size**: Set the pixel dimensions for square thumbnails
- **BunnyCDN Storage Settings**: Configure storage zone and API credentials

### Benefits
- **Performance**: Faster loading with optimized thumbnail sizes
- **Consistency**: Uniform square thumbnails across all galleries
- **Bandwidth**: Reduced data usage for thumbnail displays
- **User Experience**: Professional, consistent gallery appearance
- **SEO**: Optimized images improve page load speeds
- **Smart Display**: Gallery sections only appear when images are present

### Conditional Display
The gallery section on episode pages uses intelligent display logic:
- **No Images**: Gallery section is completely hidden
- **With Images**: Gallery section displays with proper heading and grid
- **Helper Function**: `flexpress_has_episode_gallery()` available for theme customization
- **Performance**: Avoids unnecessary HTML output when no gallery exists

### Preview Mode for Locked Episodes
When episodes are not unlocked, the gallery implements a preview mode to encourage purchases:
- **Access Control Integration**: Automatically detects episode access status
- **5-Image Preview**: Shows only first 5 images for locked episodes with 6+ images
- **Clickable CTA Overlay**: 5th image displays "+X" overlay that links to join page
- **Interactive Design**: "Click to unlock" hint appears on hover with gold highlighting
- **Direct Conversion**: One-click path from gallery preview to membership signup
- **Smart Logic**: Episodes with ≤5 images show all (no preview mode needed)
- **Responsive CTA**: Hover effects and sizing adapt to all screen sizes

### Template Integration
```php
<?php if (function_exists('flexpress_has_episode_gallery') && flexpress_has_episode_gallery()): ?>
    <div class="episode-gallery-section">
        <h2><?php esc_html_e('Episode Gallery', 'flexpress'); ?></h2>
        <?php 
        // Pass access information for preview mode
        $access_info = flexpress_check_episode_access();
        flexpress_display_episode_gallery(null, null, $access_info['has_access']); 
        ?>
    </div>
<?php endif; ?>
```

#### Preview Mode Integration
The gallery automatically switches to preview mode based on episode access:
```php
// Gallery function signature with access parameter
flexpress_display_episode_gallery($post_id = null, $columns = null, $has_access = null)

// Examples:
flexpress_display_episode_gallery(); // Auto-detects access
flexpress_display_episode_gallery(123, 4, false); // Force preview mode
flexpress_display_episode_gallery(123, 5, true); // Force full access
```

#### Conversion Funnel
The gallery preview creates a seamless conversion path with smart user-state routing:

**Not Logged In Users:**
1. **Content Discovery**: User finds interesting episode
2. **Preview Tease**: First 4 images show content quality  
3. **CTA Trigger**: "+28 LOGIN TO UNLOCK" overlay
4. **Authentication**: Click redirects to login with return URL
5. **Access**: User returns and sees appropriate unlock options

**Logged In Users:**
1. **Content Discovery**: User views episode gallery
2. **Preview Tease**: First 4 images demonstrate quality
3. **Smart CTA**: "+28 CLICK TO UNLOCK" (purchase) or "+28 GET MEMBERSHIP"
4. **Direct Action**: Purchase flow or membership signup
5. **Satisfaction**: Immediate access to all gallery content

**Access Control Integration:**
- Automatically detects user authentication state
- Matches episode access control logic exactly
- Routes to login, purchase, or membership as appropriate
- Maintains redirect URLs for seamless user experience

---

## ⚙️ Admin Features

### Settings Panels
- **General**: Logo, colors, basic configuration
- **Video**: BunnyCDN Stream integration
- **Membership**: User management and access control
- **Verotel**: Payment processing configuration
- **Pricing**: Episode pricing and discount management
- **Affiliate**: Complete affiliate and promo-code management system
- **Contact & Social**: Business information and social media

### Management Tools
- **Member Dashboard**: User activity and purchase tracking
- **Episode Sync**: BunnyCDN content synchronization
- **Webhook Diagnostics**: Payment processing monitoring
- **Access Control**: Grant/revoke episode permissions
- **Activity Logs**: Comprehensive audit trails

### Legal Compliance
- **Dynamic Legal Pages**: Auto-generated compliance content
- **2257 Compliance**: Age verification requirements
- **Privacy Policy**: GDPR-compliant privacy documentation
- **Terms & Conditions**: Service agreement templates
- **Content Removal**: DMCA takedown request forms

---

## 🤝 Affiliate System

### Core Features
- **Module Toggle**: Enable/disable entire affiliate system from admin settings
- **Affiliate Applications**: Public application form with admin approval workflow
- **Commission Tracking**: Automatic commission calculation and tracking
- **Promo Code Management**: Create and manage promotional codes with custom pricing
- **Click Tracking**: 30-day cookie-based attribution system
- **Advanced Payout Management**: Dynamic form system with method-specific field validation and comprehensive payout processing
- **Real-time Analytics**: Live statistics and performance tracking

### Commission Structure
- **Initial Sales**: Configurable percentage (default 25%)
- **Recurring Payments**: Lower rate for rebills (default 10%)
- **Unlock Purchases**: Special rate for PPV content (default 15%)
- **Per-Affiliate Overrides**: Custom rates for individual affiliates
- **Promo Code Pricing**: Custom pricing overrides per promotional code

### Database Schema
- **wp_flexpress_affiliates**: Core affiliate data and statistics
- **wp_flexpress_affiliate_promo_codes**: Promo code management
- **wp_flexpress_affiliate_clicks**: Click tracking and attribution
- **wp_flexpress_affiliate_transactions**: Commission records
- **wp_flexpress_affiliate_payouts**: Payout management

### Integration Points
- **Flowguard Webhooks**: Automatic commission processing on payment events
- **Cookie Tracking**: 30-day attribution window with secure cookie management
- **User Management**: WordPress user system integration
- **Admin Interface**: Complete management dashboard in FlexPress Settings
- **Application Management Page**: Dedicated page template for managing affiliate applications (`page-templates/affiliate-applications.php`)
- **Bulk Operations**: Approve, reject, suspend, or reactivate multiple affiliates simultaneously
- **Status Management**: Individual and bulk status updates with automatic email notifications
- **Search & Filtering**: Advanced search by name, email, affiliate code, or status
- **Real-time Statistics**: Live dashboard with pending applications, active affiliates, and revenue tracking
- **Export Functionality**: Data export capabilities for reporting and analysis
- **Dynamic Payout Forms**: Method-specific field collection with real-time validation
- **Comprehensive Payout Support**: 6 payout methods including international transfers
- **Fee Management**: Transparent fee structure with automatic deduction

### Frontend Components
- **Application Form**: `[affiliate_application_form]` shortcode
- **Affiliate Dashboard**: `[affiliate_dashboard]` shortcode
- **Stats Widget**: `[affiliate_stats]` shortcode
- **Referral Links**: `[affiliate_referral_link]` shortcode

### Security Features
- **Input Sanitization**: All user input properly sanitized
- **CSRF Protection**: Nonce verification on all forms
- **Access Controls**: Role-based permissions
- **Cookie Security**: Secure, HTTP-only cookies
- **GDPR Compliance**: Cookie notices and data protection

---

## 🎯 Target Use Cases

### Adult Content Platforms
- **Premium Video Sites**: Subscription and PPV content
- **Model Portfolios**: Performer showcase websites
- **Member Communities**: Exclusive content access
- **Pay-Per-View Events**: Individual content purchases

### General Content Creators
- **Educational Platforms**: Course and tutorial sites
- **Entertainment Content**: Premium media delivery
- **Fitness Programs**: Workout and nutrition content
- **Creative Portfolios**: Artist and creator showcases

---

## 🎨 Design Guidelines

### Visual Style
- **Dark Theme**: Black background with white text
- **Minimalist Aesthetic**: Clean, professional design
- **Video-Focused**: Content takes center stage
- **Responsive**: Mobile-first responsive design

### Layout Patterns
- **Grid Systems**: CSS Grid for flexible layouts
- **Card Design**: Consistent card-based components
- **Hover Effects**: Smooth transitions and animations
- **Typography**: Uppercase titles with proper spacing

### Color System
- **Primary**: Black (#000000) and White (#ffffff)
- **Accent**: Customizable accent color (default: #ff6b35)
- **Grays**: Various shades for hierarchy and contrast
- **Interactive**: Hover states and focus indicators

---

## 🔧 Development

### Code Standards
- **PHP**: 4 spaces, 120 character limit, single quotes
- **CSS**: 2 spaces, 80 character limit, double quotes
- **JavaScript**: 2 spaces, 100 character limit, single quotes

### File Organization
- **Modular Structure**: Separate files for each feature
- **Class-Based**: Object-oriented PHP development
- **Template Hierarchy**: WordPress template standards
- **Asset Management**: Organized CSS and JavaScript

### Best Practices
- **No Inline Styles**: CSS classes and external stylesheets
- **Organized Imports**: Proper dependency management
- **Documentation**: Comprehensive code comments
- **Feature Validation**: Input sanitization and validation
- **Security**: Nonce verification and capability checks

---

## 🛠️ Maintenance

### Regular Updates
- **WordPress Core**: Keep WordPress updated
- **Dependencies**: Update ACF and other plugins
- **Security**: Monitor and patch vulnerabilities
- **Performance**: Optimize database and assets

### Monitoring
- **Webhook Status**: Monitor payment processing
- **Video Delivery**: Check BunnyCDN performance
- **User Activity**: Review activity logs
- **Error Logs**: Monitor for PHP and JavaScript errors

### Backup Strategy
- **Database**: Regular database backups
- **Media Files**: BunnyCDN redundancy
- **Theme Files**: Version control with Git
- **Settings**: Export configuration settings

---

## 📞 Support & Documentation

### Additional Documentation
- **GALLERY_SYSTEM_IMPLEMENTATION.md**: Detailed gallery system guide
- **LEGAL_PAGES_SETUP.md**: Legal page configuration
- **SINGLE_MODEL_FEATURES.md**: Model profile functionality
- **FOOTER_SETUP.md**: Footer customization guide
- **LINTING_SETUP.md**: Code quality tools

### Development Resources
- **PRD.md**: Product requirements document
- **ACF Fields**: Custom field configurations
- **Webhook Testing**: Payment integration guides
- **API Documentation**: BunnyCDN and Verotel APIs

---

## 📄 License

**Proprietary License** - This theme is proprietary software designed for content creators. Unauthorized distribution or modification is prohibited.

---

**FlexPress Theme** - Empowering content creators with professional tools and seamless integrations.
- commission_signup, commission_rebill, commission_type
- status, total_signups, total_rebills, total_commissions
- created_at, updated_at

-- Detailed commission tracking  
wp_flexpress_affiliate_commissions:
- affiliate_id, user_id, transaction_type
- revenue_amount, commission_amount, commission_rate
- transaction_id, status, created_at

-- Payout management
wp_flexpress_affiliate_payouts:
- affiliate_id, period_start, period_end
- total_amount, payout_method, status
- processed_at, reference_id
```

#### Core Functions
- `flexpress_track_affiliate_commission()` - Comprehensive commission tracking
- `flexpress_register_affiliate()` - New affiliate registration with validation
- `flexpress_get_affiliate_by_code()` - Affiliate lookup and validation
- `flexpress_get_affiliate_dashboard_data()` - Performance metrics aggregation
- `flexpress_generate_affiliate_code()` - Unique code generation
- `flexpress_update_affiliate_rates()` - Commission rate management

#### Frontend Templates
- **Affiliate Signup** (`page-templates/affiliate-signup.php`)
  - Professional registration form with real-time validation
  - Auto-suggested affiliate codes with availability checking
  - Terms and conditions agreement integration
  - Responsive Bootstrap-based design
- **Affiliate Dashboard** (`page-templates/affiliate-dashboard.php`)
  - Performance statistics and KPIs
  - Commission history with transaction details
  - Monthly performance charts using Chart.js
  - Promotional link generators
  - Payout status and history

#### Admin Integration
- **Enhanced Settings** (`class-flexpress-affiliate-settings.php`)
  - Separate signup and rebill commission rate configuration
  - Default commission type settings (percentage/flat)
  - Affiliate management and oversight tools
  - Commission rate bulk updates
- **Verotel Webhook Integration**
  - Automatic commission tracking on initial sales
  - Rebill commission processing on recurring payments
  - Backward compatibility with existing promo code system
  - Dual tracking for legacy and new affiliate systems

#### Commission Types
1. **Signup Commissions:**
   - Tracked on initial membership purchases
   - Applied to first-time user payments
   - Configurable percentage or flat rate
   - Real-time calculation and recording

2. **Rebill Commissions:**
   - Tracked on recurring subscription payments
   - Applied to all subsequent billing cycles
   - Separate rate structure from signup commissions
   - Ongoing passive income for affiliates

3. **Pay-Per-View Commissions:**
   - Tracked on individual episode purchases
   - Applied to PPV transaction amounts
   - Immediate commission calculation
   - Episode-specific tracking and reporting

#### Security Features
- **Code Validation:**
  - Unique affiliate code generation and verification
  - Alphanumeric code format with length restrictions
  - Duplicate prevention and availability checking
- **Data Protection:**
  - Secure commission calculation and storage
  - Transaction reference validation
  - Affiliate authentication and authorization
- **Fraud Prevention:**
  - Transaction verification before commission award
  - Status-based commission management (pending/confirmed/paid)
  - Admin override capabilities for disputed transactions

#### Analytics & Reporting
- **Real-time Metrics:**
  - Total signups and conversion rates
  - Monthly and lifetime commission totals
  - Recent transaction history
  - Performance trend analysis
- **Visual Dashboard:**
  - Chart.js integration for monthly performance graphs
  - Responsive data visualization
  - Interactive filtering and date range selection
  - Export capabilities for tax and accounting purposes

#### Integration Points
- **Payment Processing:** Direct integration with Verotel webhook system
- **User Management:** Seamless WordPress user account integration
- **Membership System:** Automatic tracking of membership signups and renewals
- **Legacy Support:** Maintains compatibility with existing promo code system

#### Configuration
- **Admin Settings:**
  - FlexPress Settings → Affiliate Settings
  - Default commission rates (signup/rebill)
  - Commission type preferences
  - Affiliate approval workflow settings
- **Template Integration:**
  - Create pages using affiliate-signup and affiliate-dashboard templates
  - Assign to `/affiliate-signup` and `/affiliate-dashboard` URLs
  - Configure menu links and promotional placements

#### Dependencies
- WordPress 5.0+
- Advanced Custom Fields (ACF) - for admin interface
- Bootstrap 5.0+ (included in theme)
- Chart.js (included in dashboard template)
- Verotel FlexPay integration
- FlexPress membership system

#### Developer Overview

**🏗️ Architecture Pattern:**
The affiliate system follows a modular, event-driven architecture with clear separation of concerns:

```php
// Core Architecture Flow
Verotel Webhook → Commission Tracker → Database Logger → Analytics Aggregator
     ↓                    ↓                  ↓                    ↓
Payment Event    → Calculate Commission → Store Transaction → Update Metrics
```

**📁 File Structure:**
```
includes/
├── affiliate-helpers.php           # Core affiliate functions
├── admin/
│   └── class-flexpress-affiliate-settings.php  # Admin interface
├── verotel-integration.php         # Webhook integration
page-templates/
├── affiliate-signup.php            # Public registration
└── affiliate-dashboard.php         # Affiliate portal
assets/
├── js/affiliate-signup.js          # Form validation
├── js/affiliate-dashboard.js       # Dashboard interactions
└── css/affiliate-styles.css        # Affiliate-specific styling
```

**🔧 Key Implementation Details:**

1. **Commission Calculation Engine:**
```php
function flexpress_calculate_commission($amount, $rate, $type = 'percentage') {
    if ($type === 'percentage') {
        return ($amount * $rate) / 100;
    }
    return min($rate, $amount); // Flat rate, capped at transaction amount
}
```

2. **Webhook Integration Pattern:**
```php
// Dual tracking system for legacy compatibility
add_action('flexpress_verotel_initial_payment', 'flexpress_track_promo_usage', 10, 4);
add_action('flexpress_verotel_initial_payment', 'flexpress_track_affiliate_commission', 20, 4);
add_action('flexpress_verotel_rebill_payment', 'flexpress_track_affiliate_rebill', 10, 4);
```

3. **Database Optimization:**
```sql
-- Indexes for performance
CREATE INDEX idx_affiliate_commissions_affiliate_id ON wp_flexpress_affiliate_commissions(affiliate_id);
CREATE INDEX idx_affiliate_commissions_created_at ON wp_flexpress_affiliate_commissions(created_at);
CREATE INDEX idx_affiliate_commissions_status ON wp_flexpress_affiliate_commissions(status);
```

4. **Security Implementation:**
```php
// Multi-layer validation
$affiliate_code = sanitize_text_field($_POST['affiliate_code']);
$affiliate = flexpress_get_affiliate_by_code($affiliate_code);
if (!$affiliate || $affiliate->status !== 'active') {
    return new WP_Error('invalid_affiliate', 'Invalid or inactive affiliate code');
}
```

5. **Dynamic Payout System Implementation:**

**File Structure:**
```
includes/
├── payout-display-helpers.php          # Payout formatting functions
├── class-flexpress-affiliate-manager.php # Validation and processing
└── admin/
    └── class-flexpress-affiliate-settings.php # Admin interface

assets/
├── css/affiliate-system.css            # Dynamic form styling
└── js/affiliate-admin.js               # Real-time field management
```

**Key Functions:**
```php
// Format payout details for display
function flexpress_format_payout_details($payout_method, $payout_details) {
    $details = json_decode($payout_details, true);
    // Method-specific formatting logic
}

// Validate payout details per method
private function validate_payout_details($method, $details_json) {
    switch ($method) {
        case 'aus_bank_transfer':
            if (!preg_match('/^[0-9]{6}$/', $details['aus_bsb'])) {
                return ['valid' => false, 'message' => 'BSB must be exactly 6 digits'];
            }
            break;
        case 'ach':
            if (!preg_match('/^[0-9]{9}$/', $details['ach_aba'])) {
                return ['valid' => false, 'message' => 'ABA routing must be exactly 9 digits'];
            }
            break;
    }
}
```

**JavaScript Dynamic Fields:**
```javascript
function initPayoutFields() {
    $(document).on('change', 'select[name="payout_method"]', function() {
        const selectedMethod = $(this).val();
        const $container = $(this).closest('form').find('.payout-details-container');
        
        // Hide all fields, show selected method fields
        $container.find('.payout-fields').removeClass('active').hide();
        $container.find('.' + selectedMethod + '-fields').addClass('active').show();
        
        // Update consolidated JSON data
        updateConsolidatedPayoutDetails($container);
    });
}
```

**Data Flow:**
1. **Selection** → User selects payout method → Dynamic fields appear
2. **Collection** → Form data collected → JSON consolidation → Server validation  
3. **Storage** → Database storage → Admin display formatting → Payout processing
4. **Display** → Structured formatting in admin interface with edit capabilities

**🚀 Performance Considerations:**
- **Caching Strategy:** WordPress transients for dashboard data (15-minute cache)
- **Database Optimization:** Indexed queries for commission lookups
- **AJAX Endpoints:** Non-blocking commission tracking
- **Bulk Operations:** Batch processing for large commission calculations

**📊 Analytics Framework:**
```php
// Real-time metrics calculation
function flexpress_get_affiliate_metrics($affiliate_id, $period = '30_days') {
    $cache_key = "affiliate_metrics_{$affiliate_id}_{$period}";
    $metrics = wp_cache_get($cache_key);
    
    if (false === $metrics) {
        $metrics = [
            'total_signups' => $this->count_signups($affiliate_id, $period),
            'total_rebills' => $this->count_rebills($affiliate_id, $period),
            'total_commissions' => $this->sum_commissions($affiliate_id, $period),
            'conversion_rate' => $this->calculate_conversion_rate($affiliate_id, $period)
        ];
        wp_cache_set($cache_key, $metrics, '', 900); // 15 minutes
    }
    
    return $metrics;
}
```

#### Future Roadmap & Enhancement Plans

**🎯 Phase 2: Advanced Commission Features (Q2 2024)**

1. **Tiered Commission Structure:**
```php
// Multi-tier commission rates based on performance
$commission_tiers = [
    'bronze' => ['signup' => 25, 'rebill' => 10, 'threshold' => 0],
    'silver' => ['signup' => 30, 'rebill' => 15, 'threshold' => 50],
    'gold'   => ['signup' => 35, 'rebill' => 20, 'threshold' => 100],
    'diamond'=> ['signup' => 40, 'rebill' => 25, 'threshold' => 200]
];
```

2. **Bonus Commission Events:**
   - Weekend signup bonuses (150% commission rate)
   - Monthly challenges with flat bonus payments
   - Holiday promotional periods with enhanced rates
   - Volume-based milestone rewards

3. **Advanced Attribution Tracking:**
   - Multi-touch attribution (first-click, last-click, linear)
   - Cross-device tracking using fingerprinting
   - UTM parameter integration for campaign tracking
   - Referral chain tracking (sub-affiliates)

**💰 Phase 3: Comprehensive Payout System (Q3 2024)**

1. **Multiple Payout Methods:**
```php
// Dynamic Payout System with Method-Specific Fields
$payout_methods = [
    'paypal' => [
        'name' => 'PayPal (Free)',
        'fee' => 0,
        'fields' => ['paypal_email'],
        'validation' => 'Email format validation'
    ],
    'crypto' => [
        'name' => 'Cryptocurrency (Free)',
        'fee' => 0,
        'fields' => ['crypto_type', 'crypto_address', 'crypto_other'],
        'validation' => 'Cryptocurrency type and wallet address required'
    ],
    'aus_bank_transfer' => [
        'name' => 'Australian Bank Transfer (Free)',
        'fee' => 0,
        'fields' => ['aus_bank_name', 'aus_bsb', 'aus_account_number', 'aus_account_holder'],
        'validation' => 'BSB must be exactly 6 digits'
    ],
    'yoursafe' => [
        'name' => 'Yoursafe (Free)',
        'fee' => 0,
        'fields' => ['yoursafe_iban'],
        'validation' => 'IBAN format validation'
    ],
    'ach' => [
        'name' => 'ACH - US Only ($10 USD Fee)',
        'fee' => 10,
        'fields' => ['ach_account_number', 'ach_aba', 'ach_account_holder', 'ach_bank_name'],
        'validation' => 'ABA routing number must be exactly 9 digits'
    ],
    'swift' => [
        'name' => 'Swift International ($30 USD Fee)',
        'fee' => 30,
        'fields' => [
            'swift_bank_name', 'swift_code', 'swift_iban_account', 
            'swift_account_holder', 'swift_bank_address', 'swift_beneficiary_address',
            'swift_intermediary_swift', 'swift_intermediary_iban' // Optional
        ],
        'validation' => 'All required fields must be provided'
    ]
];
```

2. **Dynamic Payout Form System:**
   - **Method-Specific Fields**: Forms dynamically show/hide fields based on selected payout method
   - **Real-time Validation**: Client-side and server-side validation for each method
   - **JSON Data Storage**: Structured storage with legacy compatibility
   - **Professional UI**: Clean interface with focus states and error handling
   - **Admin Integration**: Formatted display in admin interface with edit capabilities

3. **Payout Method Details:**

   **🇦🇺 Australian Bank Transfer (Free)**
   - Bank Name, BSB Number (6 digits), Account Number, Account Holder Name
   - Pattern validation ensures BSB is exactly 6 digits

   **💳 Yoursafe (Free)**
   - Yoursafe IBAN field with format validation

   **🏦 ACH - US Only ($10 USD Fee)**
   - Account Number, ABA Routing (9 digits), Account Holder, Bank Name
   - Pattern validation ensures ABA is exactly 9 digits

   **🌍 Swift International ($30 USD Fee)**
   - Bank Name, SWIFT/BIC Code, IBAN/Account, Account Holder
   - Bank Address, Beneficiary Address
   - Optional: Secondary/Intermediary SWIFT Code, Intermediary IBAN
   - Most comprehensive method with full international transfer support

   **💸 PayPal (Free)**
   - Email address with format validation

   **₿ Cryptocurrency (Free)**
   - Cryptocurrency type selection (Bitcoin, Ethereum, Litecoin, Other)
   - Wallet address field with custom type specification

4. **Technical Implementation:**
   ```javascript
   // Dynamic field management
   function initPayoutFields() {
       // Show/hide fields based on selection
       // Update consolidated JSON data
       // Handle validation and dependencies
   }
   ```

5. **Automated Payout Scheduling:**
   - Weekly, bi-weekly, monthly payout cycles
   - Minimum payout thresholds per method
   - Fee deduction from payout amounts for paid methods
   - Automatic payout processing with approval workflows
   - Tax document generation (1099, international forms)

3. **Payout Management Dashboard:**
   - Real-time payout status tracking
   - Payout method management and verification
   - Historical payout reports and tax summaries
   - Dispute resolution system

**🔔 Phase 4: Advanced Notifications & Communication (Q4 2024)**

1. **Discord Integration:**
```php
// Discord webhook notifications
class FlexpressDiscordNotifications {
    private $webhook_url;
    
    public function send_commission_alert($affiliate_code, $amount, $type) {
        $embed = [
            'title' => '💰 New Commission Earned!',
            'description' => "Affiliate: **{$affiliate_code}**\nAmount: **\${$amount}**\nType: **{$type}**",
            'color' => 0x00ff00,
            'timestamp' => date('c'),
            'footer' => ['text' => 'FlexPress Affiliate System']
        ];
        
        $this->send_webhook(['embeds' => [$embed]]);
    }
    
    public function send_milestone_notification($affiliate_code, $milestone) {
        // Milestone achievement notifications
    }
    
    public function send_payout_processed($affiliate_code, $amount, $method) {
        // Payout confirmation notifications
    }
}
```

2. **Email Automation System:**
   - Welcome series for new affiliates
   - Weekly performance summaries
   - Commission milestone celebrations
   - Payout confirmations and receipts
   - Re-engagement campaigns for inactive affiliates

3. **Real-time Dashboard Notifications:**
   - Browser push notifications for new commissions
   - In-dashboard notification center
   - Mobile-responsive notification alerts
   - Customizable notification preferences

**📱 Phase 5: Mobile App & Advanced Analytics (Q1 2025)**

1. **Progressive Web App (PWA):**
   - Native app experience for affiliate dashboard
   - Offline capability for viewing reports
   - Push notifications for real-time updates
   - Quick commission sharing to social media

2. **Advanced Analytics & AI:**
```php
// AI-powered affiliate insights
class FlexpressAffiliateAI {
    public function predict_conversion_rates($affiliate_id, $traffic_data) {
        // Machine learning predictions for optimization
    }
    
    public function suggest_promotional_strategies($affiliate_performance) {
        // AI-generated marketing recommendations
    }
    
    public function detect_fraud_patterns($commission_data) {
        // Anomaly detection for fraudulent activity
    }
}
```

3. **Enhanced Reporting:**
   - Custom date range analytics
   - Conversion funnel analysis
   - A/B testing for promotional materials
   - Competitor performance benchmarking
   - ROI calculation tools

**🔗 Phase 6: Third-Party Integrations (Q2 2025)**

1. **Marketing Platform Integration:**
   - Mailchimp/Klaviyo for email marketing
   - Facebook Pixel for conversion tracking
   - Google Analytics enhanced ecommerce
   - TikTok/Instagram affiliate tools

2. **CRM Integration:**
   - HubSpot contact syncing
   - Salesforce lead management
   - Customer lifecycle tracking
   - Automated follow-up sequences

3. **Financial Tools:**
   - QuickBooks integration for tax reporting
   - Stripe Connect for direct payouts
   - TaxJar for automated tax calculations
   - Currency conversion for international affiliates

**⚙️ Technical Debt & Optimization Roadmap**

1. **Performance Enhancements:**
   - Redis caching for high-volume commission tracking
   - Database sharding for large affiliate networks
   - CDN integration for dashboard assets
   - GraphQL API for mobile app integration

2. **Security Improvements:**
   - Two-factor authentication for affiliate accounts
   - Rate limiting for API endpoints
   - Advanced fraud detection algorithms
   - GDPR compliance tools for data management

3. **Developer Experience:**
   - REST API for third-party integrations
   - Webhook system for external notifications
   - SDK for custom affiliate implementations
   - Comprehensive API documentation

**📊 Success Metrics & KPIs to Track:**
- Affiliate signup conversion rate
- Average commission per affiliate
- Affiliate retention rate (90-day, 1-year)
- Time to first commission
- Payout completion rate
- Support ticket volume and resolution time
- Mobile dashboard usage statistics
- API adoption and integration success

**🛡️ Compliance & Legal Considerations:**
- FTC affiliate disclosure requirements
- International tax reporting obligations
- GDPR/CCPA data privacy compliance
- Anti-fraud monitoring and prevention
- Terms of service and affiliate agreements
- Dispute resolution procedures

### Episode Access Control
- Flexible access types (free/PPV/membership/mixed)
- Smart pricing calculations
- Member discount application
- Visual access indicators
- Admin confirmation controls

### Activity Logging
- Comprehensive user activity tracking
- PPV purchase logging
- Payment confirmation tracking
- Admin dashboard integration
- Detailed audit trails

### Episodes Archive System

Modern, responsive episodes archive page with advanced filtering and layout options inspired by premium adult content sites.

#### Version
2.0.0

#### Status
Enabled

#### Recent Updates
- **v2.0.0:** Complete redesign with Vixen.com-inspired layout, episode card layout overhaul (duration moved to info section), dynamic filtering system, and responsive grid options
- **v1.5.0:** Added toggle filter functionality with 2-column/3-column layout switching
- **v1.4.0:** Implemented dropdown-based filter switching between categories and models
- **v1.3.0:** Enhanced dark theme styling and visual consistency

#### Core Features
- **Responsive Grid Layout:**
  - 8/4 column split (videos/filters) when filters visible
  - 12 column full-width when filters hidden
  - 2 videos per row (filters visible) or 3 videos per row (filters hidden)
  - Mobile-responsive single column on small screens
- **Dynamic Filter Toggle:**
  - Show/Hide filters button with smooth animations
  - Instant layout switching between 2-column and 3-column video grids
  - Persistent filter state during session
  - Professional hover effects and transitions
- **Advanced Filtering System:**
  - Dropdown-based filter type selection (Category/Models)
  - Dynamic content display based on selected filter type
  - Smart category filtering by post tags with episode counts
  - Model-based filtering using ACF relationship fields
  - Alphabetical filtering (A-Z) for episodes by title
- **Sorting Options:**
  - Compact button-based sort controls
  - Newest/Oldest episode sorting
  - Release date-based ordering using ACF fields
  - Maintains sort state across filter changes

#### Technical Implementation
- **Template:** `archive-episode.php`
- **Styling:** Enhanced CSS in `main.css` with dark theme integration
- **JavaScript:** Vanilla JS for filter toggling and dropdown interactions
- **Query Integration:** 
  - WordPress WP_Query with custom meta queries
  - ACF field integration for release dates and model relationships
  - Post tag taxonomy filtering
  - SQL-based alphabetical filtering

#### Filter Types
1. **Category Filtering:**
   - Uses WordPress `post_tag` taxonomy
   - Displays tag name with episode count (e.g., "Big Cock (5)")
   - Active filter highlighting
   - Direct URL-based filtering for bookmarkable results

2. **Model Filtering:**
   - Integrates with custom `model` post type
   - Uses ACF `featured_models` relationship field
   - Alphabetical model listing
   - Model-specific episode filtering

3. **Alphabetical Filtering:**
   - A-Z grid layout (6 columns desktop, responsive)
   - SQL LIKE query for title-based filtering
   - Letter-based episode grouping
   - Clean alphabet navigation

#### Layout System
- **Filters Visible Mode:**
  - 8-column video area, 4-column filter sidebar
  - 2 videos per row in responsive grid
  - Sticky sidebar positioning
  - Professional card-based filter interface

- **Filters Hidden Mode:**
  - 12-column full-width video area
  - 3 videos per row for optimal browsing
  - More content visible per page
  - Clean, distraction-free viewing

#### Pagination
- **Vixen.com-Style Pagination:**
  - "First | Back | 1 2 3 4 5 | Next | Last" format
  - Page count indicators
  - Clean, professional styling
  - 16 episodes per page (configurable)

#### Responsive Design
- **Desktop (>992px):** Full filter/toggle functionality
- **Tablet (768px-991px):** Stacked layout with responsive filters
- **Mobile (<768px):** Single column, simplified interface
- **All Breakpoints:** Optimized touch targets and spacing

#### Dark Theme Integration
- **Consistent Styling:**
  - Black background (#000000)
  - White text (#ffffff) 
  - Dark gray borders (#222222)
  - Smooth hover transitions (#1a1a1a)
- **Interactive Elements:**
  - White active states with black text
  - Subtle hover effects
  - Professional button styling
  - Filter highlight indicators

#### Performance Features
- **Efficient Queries:** Optimized WP_Query parameters
- **Lazy Loading:** Future-ready for image optimization
- **Minimal JavaScript:** Vanilla JS for maximum performance
- **CSS Transitions:** Smooth 0.3s animations
- **Responsive Images:** BunnyCDN integration ready

#### User Experience
- **Intuitive Navigation:** Clear filter categories and sorting
- **Visual Feedback:** Active states and hover effects
- **Smooth Transitions:** Animated layout changes
- **Professional Design:** Premium adult site aesthetics
- **Accessibility:** Keyboard navigation and screen reader support

#### Episode Card Layout v2.0.0

**Clean Information Design:**
- **No Thumbnail Overlays:** Duration and text removed from video thumbnails for clean viewing
- **Play Button Only:** Centered play button appears on hover over thumbnail
- **Two-Row Information Section:** Structured content display below thumbnail

**Layout Structure:**
```html
<div class="episode-card">
  <a href="episode-url" class="episode-link">
    <div class="card-img-top">
      <!-- Clean thumbnail with play button overlay only -->
      <div class="episode-overlay">
        <div class="episode-play-button">
          <i class="fa-solid fa-play"></i>
        </div>
      </div>
    </div>
  </a>
  
  <!-- Episode Information Below Thumbnail -->
  <div class="episode-info">
    <div class="episode-info-row">
      <!-- Row 1: Title | Duration -->
      <h5 class="episode-title">
        <a href="episode-url">Episode Title</a>
      </h5>
      <span class="episode-duration">17:34</span>
    </div>
    
    <div class="episode-info-row">
      <!-- Row 2: Models | Date -->
      <div class="episode-performers">
        <a href="model-url">Model Name</a>, <a href="model-url">Model Name</a>
      </div>
      <span class="episode-date">MARCH 26, 2025</span>
    </div>
  </div>
</div>
```

**Content Layout:**
- **Row 1:** Episode Title (left-aligned) | Duration (right-aligned)
- **Row 2:** Featured Models (left-aligned) | Release Date (right-aligned)
- **Flexbox Alignment:** `justify-content: space-between` for proper spacing

**Interactive Elements:**
- **Clickable Episode Title:** Links to single episode page with hover effects
- **Clickable Model Names:** Individual links to model profile pages
- **Model Link Styling:** Gray (#aaaaaa) to white (#ffffff) with underline on hover
- **Play Button Animation:** Scales and appears on thumbnail hover

**Duration Implementation:**
- **Location:** Moved from thumbnail overlay to info section for cleaner design
- **Format:** MM:SS display (e.g., "17:34")
- **Source:** ACF `episode_duration` field with BunnyCDN API fallback
- **Styling:** Gray color (#888888), right-aligned, 0.85rem font size

**Date Handling:**
- **Format Support:** Smart parsing for UK format (dd/mm/yyyy) and standard formats
- **Display:** Uppercase format (e.g., "DECEMBER 04, 2018")
- **Fallback:** Uses WordPress post date if ACF field is empty or invalid
- **Color:** Dark gray (#888888) for visual hierarchy

**Typography & Colors:**
- **Episode Title:** White (#ffffff), 1.1rem, font-weight 600
- **Duration:** Gray (#888888), 0.85rem, right-aligned
- **Model Names:** Gray (#aaaaaa), hover to white with underline transition
- **Release Date:** Dark gray (#888888), 0.85rem, uppercase format

#### Dependencies
- WordPress 5.0+
- Advanced Custom Fields (ACF)
- Bootstrap 5.0+ (included in theme)
- BunnyCDN integration (for video thumbnails)
- Custom episode post type
- Model post type integration

### Registration System

Advanced registration system with form validation, password strength checking, and subscription integration.

#### Version
1.0.0

#### Status
Enabled

#### Features
- Custom registration form with shortcode
- Password strength indicator
- Real-time password match validation
- AJAX form submission
- Bootstrap form validation
- Social login integration
- Subscription handling

#### Dependencies
- WordPress 5.0+
- PHP 7.4+
- Bootstrap 5.0+
- jQuery 3.6+
- Verotel FlexPay

### Episode Access Control System

Comprehensive episode access management with flexible pricing models and member benefits.

#### Version
1.3.0

#### Status
Enabled

#### Recent Updates
- **v1.3.0:** Added comprehensive purchased episodes management interface with bulk operations and detailed user profile controls
- **v1.2.0:** Fixed Verotel integration to properly separate one-time purchases (PPV episodes) from recurring subscriptions (memberships)

#### Features
- **Access Types:**
  - Free for Everyone
  - Pay-Per-View Only (No Membership Access)
  - Membership Access + PPV Option
  - Members Get Discount + PPV for Non-Members
- **Smart Access Logic:**
  - Automatic video selection (full/trailer/preview)
  - User-specific pricing calculations
  - Membership status integration
  - Purchase history tracking
- **Visual Indicators:**
  - Color-coded access type badges
  - Dynamic pricing display
  - Member discount highlights
  - Access status notifications
- **Admin Controls:**
  - ACF field-based configuration
  - Confirmation popup for free episodes
  - Contextual help and validation
  - Price field warnings
- **Episode Management Interface:**
  - Integrated episode access management within member details pages
  - Grant/revoke episode access directly from user profiles
  - Purchase statistics and access tracking per user
  - Add/remove episode access with real-time updates
  - Comprehensive purchase history and activity logging

#### Technical Implementation
- **Purchase Types:**
  - PPV Episodes: Uses `get_purchase_URL()` for one-time payments
  - Memberships: Uses `get_subscription_URL()` for recurring billing
- **Payment Flow:**
  - One-time purchases include `referenceID` for tracking
  - Webhook handling for both purchase types
  - Proper parameter mapping for Verotel API

#### Dependencies
- Advanced Custom Fields (ACF)
- Verotel FlexPay integration
- BunnyCDN Stream
- FlexPress Activity Logger

### PPV Purchase Activity Logging

Comprehensive activity logging system for tracking Pay-Per-View episode purchases and user interactions.

#### Version
1.0.0

#### Status
Enabled

#### Features
- **Activity Types:**
  - `ppv_purchase` - Initial purchase confirmation
  - `ppv_purchase_confirmed` - Webhook payment confirmation
- **Logged Information:**
  - Episode ID and title
  - Access type (free/ppv_only/membership/mixed)
  - Original price vs final price
  - Member discount applied (if any)
  - User's membership status
  - Transaction reference and ID
  - Currency and payment method
  - Complete webhook data
- **Smart Descriptions:**
  - "Purchased episode: Episode Title - $19.99"
  - "Purchased episode: Episode Title (Member discount: 30%) - $13.99"
  - "PPV Purchase confirmed: Episode Title - USD 13.99"
- **Admin Dashboard:**
  - User activity log in admin profiles
  - Color-coded activity badges
  - Detailed purchase information
  - Chronological purchase history
- **Helper Functions:**
  - `FlexPress_Activity_Logger::log_ppv_purchase()`
  - Consistent logging across payment flows
  - Automatic discount calculation tracking

#### Integration Points
- Payment return handler (`flexpress_handle_ppv_payment_return`)
- Webhook confirmation (`flexpress_handle_ppv_webhook`)
- Admin user profile pages
- Activity reporting system

#### Database Storage
- Custom table: `wp_flexpress_user_activity`
- Indexed by user ID, event type, and date
- JSON-encoded event data for flexibility
- IP address and user agent tracking

#### Dependencies
- FlexPress Activity Logger class
- Verotel FlexPay integration
- WordPress user meta system
- Advanced Custom Fields (ACF)

### Flowguard Unlock Button Integration

Modern PPV (Pay-Per-View) purchase system using Flowguard's embedded payment forms for seamless episode unlocking.

#### Version
1.0.0

#### Status
Enabled

#### Features
- **Embedded Payment Forms**: No redirects, seamless user experience
- **Member Discount Support**: Automatic discount application for active members
- **Secure Transaction Processing**: JWT-based API communication
- **Real-time Webhook Processing**: Instant episode access upon payment approval
- **Flexible Pricing**: Support for minimum Flowguard pricing ($2.95 USD)
- **Transaction Tracking**: Complete purchase history and audit trails

#### Technical Implementation
- **API Client**: `FlexPress_Flowguard_API` class with JWT token generation
- **Purchase Function**: `flexpress_flowguard_create_ppv_purchase()` with member discount handling (uses `episode_price` ACF field)
- **Webhook Handler**: `flexpress_flowguard_webhook_handler()` for payment processing
- **Reference Format**: `ppv_episodeId_userId_timestamp` for unique transaction tracking
- **Payment Pages**: Dedicated templates for payment form and success handling

#### Unlock Button Flow
1. **User clicks unlock button** on episode page
2. **AJAX request** creates Flowguard purchase session
3. **Payment form** loads with embedded Flowguard SDK
4. **User completes payment** without leaving the site
5. **Webhook processes** payment approval
6. **Episode access granted** and user redirected to success page

#### Configuration
- **FlexPress Settings → Flowguard**: Shop ID, Signature Key, Environment
- **Webhook URL**: `/wp-admin/admin-ajax.php?action=flowguard_webhook`
- **Payment Pages**: `/payment` and `/payment-success` templates
- **Minimum Price**: $2.95 USD (Flowguard requirement)

#### Dependencies
- Flowguard API client
- Advanced Custom Fields (ACF)
- WordPress AJAX system
- FlexPress Activity Logger

### Verotel Webhook Integration

Advanced Verotel FlexPay webhook processing system with comprehensive signature validation and error handling.

#### Version
1.4.0

#### Status
Enabled

#### Recent Updates
- **v1.4.0:** Complete webhook overhaul with advanced signature validation methods and auto-bypass system
- **v1.3.0:** Enhanced webhook debugging with multiple signature calculation methods
- **v1.2.0:** Added comprehensive error logging and webhook request analysis

#### Features
- **Advanced Signature Validation:**
  - Multiple signature calculation methods (standard, no-empty-filter, URL-encoded, string concatenation)
  - Official Verotel FlexPay client integration
  - Alternative signature key testing
  - Comprehensive signature debugging with detailed logging
- **Auto-Bypass System:**
  - Automatically enables signature bypass when validation fails consistently
  - Prevents payment processing downtime during signature debugging
  - Admin notification system for bypass activation
  - Configurable bypass settings in FlexPress admin
- **Streamlined Webhook Processing:**
  - GET and POST request handling
  - Enhanced user identification via multiple parameters (custom1, custom2, custom3)
  - Event-specific processing (initial, rebill, cancel, chargeback)
  - shopID validation for security
- **Simplified Authentication:**
  - Removed complex signature validation that was incompatible with Verotel's implementation
  - Uses shopID verification and Verotel's secure delivery for authentication
  - Minimal logging focused on essential webhook processing
- **Webhook Monitoring:**
  - Success/failure tracking
  - Orphaned webhook detection for debugging
  - Clean status reporting

#### Technical Implementation
- **Webhook Endpoint:** `wp-admin/admin-ajax.php?action=verotel_webhook`
- **AJAX Actions:**
  - `verotel_webhook` - Main webhook handler
  - `flexpress_verotel_webhook` - Alternative endpoint
- **Security Approach:**
  - shopID validation against configured value (133772)
  - Verotel's secure webhook delivery
  - Required field validation (shopID, saleID)
- **Performance Optimized:**
  - Removed complex signature calculation attempts
  - Minimal debug logging
  - Faster webhook processing

#### Webhook Events Supported
- **initial** - New subscription signup
- **rebill** - Recurring subscription charge
- **cancel** - Subscription cancellation
- **chargeback** - Payment dispute/chargeback
- **credit** - Subscription termination credit

#### User Identification Methods
1. **custom1** - Primary user ID field
2. **custom2** - Secondary identifier
3. **custom3** - Tertiary identifier or combined data
4. **saleID** - Verotel sale identifier

#### Security Features
- **IP Validation:** Cloudflare-aware IP detection
- **User Agent Verification:** Verotel-specific user agent validation
- **Signature Verification:** Multiple cryptographic validation methods
- **Request Logging:** Complete webhook request audit trail
- **Rate Limiting:** Built-in protection against webhook flooding

#### Admin Interface
- **FlexPress Settings → Verotel Settings:**
  - Webhook URL configuration
  - Signature bypass controls
  - Debug mode toggles
  - Webhook testing tools
- **Diagnostic Tools:**
  - Real-time webhook monitoring
  - Signature validation testing
  - Webhook history and analysis
  - Error reporting dashboard

#### Troubleshooting
- **Signature Validation Issues:**
  - Enable debug logging in FlexPress settings
  - Check webhook logs in `wp-content/debug.log`
  - Use signature bypass for emergency payment processing
  - Verify Verotel signature key configuration
- **User Identification Problems:**
  - Ensure custom1 field contains valid WordPress user ID
  - Check user exists in WordPress database
  - Verify webhook parameter mapping in Verotel dashboard
- **Webhook Processing Failures:**
  - Check AJAX action registration in functions.php
  - Verify webhook URL accessibility
  - Review error logs for specific failure points

### Membership Management Access Issues

**Issue:** "Sorry, you are not allowed to access this page" error when trying to edit/manage members in FlexPress admin.

**Root Cause:** Two critical misconfigurations:
1. **Permission Mismatch:** Admin menu pages required `manage_options` capability (administrator-only) while the actual membership management functions used `edit_users` capability
2. **URL Routing Mismatch:** Submenu pages were created with slugs `flexpress-manage-members` and `flexpress-tools` but internal URLs pointed to `flexpress-membership-settings`

**Solution Applied:**
1. **Capability Fixes:**
   - Changed "Manage Members" page capability from `manage_options` to `edit_users`
   - Changed "Tools" page capability from `manage_options` to `edit_users`
   - Updated episode sync tool capability from `manage_options` to `edit_posts`

2. **URL Routing Fixes:**
   - Updated filter form URLs to use `flexpress-manage-members`
   - Fixed edit user links to use correct page slug
   - Updated "Back to Members List" link
   - Fixed script enqueue hooks to match new page names
   - Fixed episode sync redirect URL

**Files Modified:**
- `wp-content/themes/flexpress/includes/admin/class-flexpress-membership-settings.php`

**Resolution Date:** June 18, 2025

**Prevention:** This issue occurred due to inconsistency between menu registration and internal URL references. Future development should ensure page slugs match throughout all internal links and capability requirements align with actual function permissions.

#### Dependencies
- Verotel FlexPay PHP Client library
- WordPress AJAX system
- FlexPress Settings framework
- Advanced Custom Fields (ACF)
- WordPress user management system

#### File Structure
```
includes/
├── verotel-integration.php      # Main webhook handler
├── verotel/                     # Verotel FlexPay library
│   └── src/Verotel/FlexPay/     # Official client classes
├── admin/
│   ├── class-flexpress-verotel-settings.php    # Admin interface
│   └── class-flexpress-verotel-diagnostics.php # Debug tools
└── webhook-test-files/          # Development testing files
```

#### Integration Points
- User registration and membership activation
- Subscription management and billing
- Episode access control system
- Activity logging and audit trails
- Admin dashboard notifications

### Join Page

Modern membership signup page with pricing options and user registration form.

#### Version
1.0.0

#### Status
Enabled

#### Features
- Monthly and annual membership options
- Responsive design with modern UI
- AJAX form submission
- Smooth scroll to form on plan selection
- Terms and privacy policy integration

#### Dependencies
- WordPress 5.0+
- PHP 7.4+
- Bootstrap 5.0+
- jQuery 3.6+
- Verotel FlexPay

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- MySQL 5.7 or higher
- BunnyCDN Stream account
- Verotel FlexPay merchant account

## Setup

1. Install the theme in `wp-content/themes/flexpress/`
2. Activate the theme in WordPress admin
3. Configure required settings:
   - BunnyCDN Stream credentials
   - Verotel FlexPay merchant details
   - Custom logo
   - Menu structure

## Configuration

### BunnyCDN Stream
- Library ID
- API Key
- Token Key
- Stream URL

### Verotel FlexPay
- Merchant ID
- Shop ID
- Signature Key

### Theme Options
- Logo upload
- Menu configuration
- Featured content
- Social media links

## Development

### Code Style
- PHP: 4 spaces, 120 char limit, single quotes
- CSS: 2 spaces, 80 char limit, double quotes
- JS: 2 spaces, 100 char limit, single quotes

### Best Practices
- No inline styles
- Organized imports
- Proper documentation
- Feature validation
- Automated testing

## Styling Guidelines & Design Patterns

### Design Philosophy
- **Vixen.com Inspired**: Clean, professional aesthetic with minimalist design
- **Dark Theme Focus**: Black backgrounds with white text for premium feel
- **Modern Video Interface**: Emphasis on video content with sophisticated hover effects
- **Consistent Interactions**: Uniform behavior across all video/model cards

### Layout Systems

#### CSS Grid Patterns
```css
/* Standard Video Grid - 4→3→2→1 columns responsive */
.video-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

@media (max-width: 1200px) {
  .video-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
  .video-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 480px) {
  .video-grid { grid-template-columns: 1fr; }
}

/* Model Grid - 6→4→3→2→1 columns responsive */
.models-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 15px;
}
```

#### Responsive Breakpoints
- **1200px+**: Full desktop layout
- **768px-1199px**: Tablet layout (reduced columns)
- **480px-767px**: Mobile landscape (2 columns max)
- **<480px**: Mobile portrait (single column)

### Card Design Patterns

#### Episode Cards
```css
/* Standard Episode Card Structure */
.episode-card {
  position: relative;
  overflow: hidden;
  border-radius: 8px;
  background: #000;
  transition: transform 0.3s ease;
}

.episode-card:hover {
  transform: translateY(-2px);
}
```

**Hover Behavior:**
- **Default State**: Text visible at bottom, play button hidden
- **Hover State**: Text fades out (`opacity: 0`), play button scales in from center
- **Play Button**: Circular, centered, white border, glass effect with backdrop-filter
- **Transition**: 0.3s ease for smooth animations

#### Model Cards
```css
/* Model Card with Center + Bottom Overlays */
.model-card {
  position: relative;
  overflow: hidden;
}

/* Center overlay for magnifying glass */
.model-center-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  opacity: 0;
}

/* Bottom overlay for text */
.model-text-overlay {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
}
```

**Hover Behavior:**
- **Default State**: Name visible at bottom, magnifying glass hidden
- **Hover State**: Name disappears, magnifying glass appears in center
- **No Border Radius**: Remove rounded corners on overlays to match image edges

### Typography & Text Styling

#### Universal Text Rules
```css
/* All Episode/Model Titles */
.episode-title,
.hero-episode-title,
.model-card .card-title {
  text-transform: uppercase;
  letter-spacing: 0.5px;
  text-align: center;
  font-weight: 700;
}

/* Model/Performer Names */
.hero-model-name,
.episode-performers {
  color: var(--color-primary);
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
}
```

#### Responsive Typography
```css
/* Hero Section Text Scaling */
@media (max-width: 991.98px) {
  .hero-model-name { font-size: 18px; }
  .hero-episode-title { font-size: 24px; }
}

@media (max-width: 767.98px) {
  .hero-model-name { font-size: 16px; }
  .hero-episode-title { font-size: 20px; }
}

@media (max-width: 480px) {
  .hero-model-name { font-size: 14px; }
  .hero-episode-title { font-size: 18px; }
}
```

### Hero Section Patterns

#### Structure
```html
<div class="hero-section">
  <a href="episode-url" class="hero-link">
    <div class="hero-video-container"><!-- Video/Thumbnail --></div>
    <div class="hero-play-button"><!-- Centered Play Button --></div>
    <div class="hero-content-overlay"><!-- Bottom Text Overlay --></div>
  </a>
</div>
```

#### Video Container
- **Aspect Ratio**: 16:9 using `padding-top: 56.25%`
- **Background**: Black (#000) for loading states
- **Pointer Events**: Disabled to prevent video interaction
- **User Select**: Disabled for clean UX

#### Play Button Specifications
```css
.hero-play-button {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.15);
  border: 3px solid rgba(255, 255, 255, 0.8);
  border-radius: 50%;
  backdrop-filter: blur(10px);
  font-size: 28px;
  color: white;
}
```

### Interactive Elements

#### Hover Animation Rules
- **NO translateY Effects**: Avoid jumping/lifting animations (user preference)
- **Opacity Transitions**: 0.3s ease for fade in/out
- **Scale Transitions**: 0.3s ease for play button appearance
- **Text Behavior**: Visible by default, hidden on hover

#### Button Styling
```css
/* Play Buttons */
.episode-play-button,
.hero-play-button {
  background: rgba(255, 255, 255, 0.15);
  border: 3px solid rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

/* Magnifying Glass Buttons */
.magnifying-button {
  background: rgba(255, 255, 255, 0.9);
  color: #000;
  border-radius: 50%;
}
```

### ACF Field Integration

#### Episode Fields Usage
- **featured_models**: Relationship field linking to model posts
- **preview_video**: BunnyCDN video ID for thumbnails/previews
- **episode_duration**: Display format (MM:SS)
- **release_date**: Controls visibility and ordering

#### Model Names Display
```php
// Convert relationship field to comma-separated names
$featured_models = get_field('featured_models');
if ($featured_models && !empty($featured_models)) {
    $model_names = array();
    foreach ($featured_models as $model) {
        $model_names[] = $model->post_title;
    }
    $performers = implode(', ', $model_names);
}
```

### Color System
- **Primary Color**: `var(--color-primary)` for white elements
- **Accent Color**: `var(--color-accent)` for buttons, links, and interactive elements (default: #ff6b35)
- **Accent Hover**: `var(--color-accent-hover)` for hover states
- **Accent Light**: `var(--color-accent-light)` for backgrounds and shadows
- **Accent Dark**: `var(--color-accent-dark)` for gradients and borders
- **Text Color**: `var(--color-text)` for main content (white)
- **Background**: `var(--color-background)` for containers (black)
- **Overlays**: `rgba(0, 0, 0, 0.8)` for text backgrounds
- **Glass Effects**: `rgba(255, 255, 255, 0.15)` with `backdrop-filter: blur(10px)`

#### Accent Color Features
- **Customizable**: Set via FlexPress Settings → General → Color Settings
- **Dynamic Variants**: Automatically generates hover, light, and dark variants
- **Strategic Application**: Used for CTAs, form focus states, navigation, and important UI elements
- **CSS Classes**: `.btn-accent`, `.accent-link`, `.cta-primary`, `.featured-badge`

### Performance Considerations
- **CSS Grid**: Preferred over Bootstrap columns for better control
- **Transitions**: Limited to opacity, transform, and background changes
- **Image Optimization**: Proper lazy loading and responsive image delivery
- **Video Controls**: Hidden via CSS and pointer-events disabled
- **Gallery Images**: Optimized sizes and CDN delivery via BunnyCDN Storage

### Testing Checklist
- [ ] Responsive behavior across all breakpoints
- [ ] Hover effects work consistently on all cards
- [ ] Text scales appropriately on mobile devices
- [ ] Play buttons appear correctly centered
- [ ] ACF relationship fields display model names properly
- [ ] Video thumbnails load from BunnyCDN API
- [ ] All links are clickable and functional
- [ ] Gallery upload interface works correctly
- [ ] Gallery images display in responsive grid
- [ ] Lightbox gallery viewer functions properly
- [ ] BunnyCDN Storage integration for galleries

## Deployment

The theme is designed to be deployed as a standalone package. Only the contents of the `wp-content/themes/flexpress/` directory are required for deployment.

### Excluded Files
- Development files
- Configuration files
- Build artifacts
- Documentation

### Required Checks
- Theme metadata
- Core functionality
- Screenshot
- Documentation

## 🛡️ Payment Form Validation System

### Comprehensive Error Handling
The FlexPress theme includes a sophisticated validation system for Flowguard payment forms that provides:

#### Real-Time Field Validation
- **Card Number**: Luhn algorithm validation, card type detection, length verification
- **Expiry Date**: Format validation (MM/YY), expiration checking, month validation
- **CVV**: Length validation (3-4 digits), numeric format checking
- **Cardholder Name**: Character validation, length limits, special character handling

#### Error Recovery Mechanisms
- **Automatic Retry**: Configurable retry attempts for network errors
- **Graceful Degradation**: Fallback error handling when validation system fails
- **User-Friendly Messages**: Clear, actionable error messages
- **Visual Feedback**: Color-coded field states (error, warning, success)

#### Validation Features
- **Cross-Origin Handling**: Works with Flowguard's iframe-based fields
- **Network Status Monitoring**: Detects online/offline states
- **Form State Management**: Tracks validation state across form interactions
- **Help System**: Contextual help messages for each field
- **Accessibility**: Screen reader friendly error announcements

#### Error Types Handled
- **Field Validation Errors**: Invalid card numbers, expired dates, etc.
- **Network Errors**: Connection issues, timeouts
- **Payment Errors**: Declined cards, insufficient funds, fraud detection
- **System Errors**: Server errors, session expiration
- **3D Secure Errors**: Authentication failures, cancellations

#### Testing & Debugging
- **Test Page**: `/test-validation.php` for comprehensive validation testing
- **Mock System**: Simulated validation for development and testing
- **Error Simulation**: Test various error scenarios
- **Status Monitoring**: Real-time validation state display

### Key Validation Files
- `assets/js/flowguard-validation.js` - Core validation system
- `assets/css/flowguard-validation.css` - Validation styling
- `assets/css/validation-test.css` - Test page styling
- `page-templates/payment.php` - Payment form with integrated validation
- `test-validation.php` - Validation testing and debugging page

### Validation Configuration
```javascript
// Initialize validation system
const validationSystem = new FlowguardValidation(paymentForm, {
    showFieldErrors: true,      // Display field-level errors
    showGlobalErrors: true,     // Display global error messages
    autoRetry: true,           // Enable automatic retry for recoverable errors
    maxRetries: 3,            // Maximum retry attempts
    retryDelay: 2000,          // Delay between retry attempts (ms)
    enableHelp: true           // Show contextual help messages
});
```

### Error Message Customization
The validation system includes comprehensive error messages for all common scenarios:
- Card validation errors (invalid format, expired, unsupported)
- Network errors (connection issues, timeouts)
- Payment processing errors (declined, insufficient funds, fraud)
- 3D Secure authentication errors
- System errors (session expiration, server issues)

### Integration with Flowguard SDK
The validation system seamlessly integrates with Flowguard's embedded payment forms:
- Monitors iframe-based field changes
- Handles cross-origin restrictions gracefully
- Provides visual feedback for field states
- Manages form submission validation
- Implements comprehensive error recovery

## 💳 Remember Card Feature

### Overview
The Remember Card feature allows users to securely save their payment card information for faster future transactions. This enhances user experience by eliminating the need to re-enter card details for each purchase.

### Key Features
- **🔒 Secure Storage**: Card details stored in browser local storage in masked format
- **🎨 Theme Integration**: Styled to match FlexPress dark theme aesthetic
- **⚡ Fast Checkout**: Automatic card prefilling for returning users
- **👤 User Control**: Users can remove saved cards anytime
- **📱 Responsive**: Works seamlessly across all device sizes

### Security
- **Browser Local Storage**: No server-side storage of sensitive card data
- **Masked Format**: Sensitive data is masked and never exposed
- **PCI DSS Compliant**: Follows industry security standards
- **User Consent**: Explicit user permission required for card storage

### Implementation
- **Frontend**: Integrated into payment form with custom styling
- **Validation**: Optional field validation (always valid)
- **User Experience**: Clear information about card storage benefits
- **Customization**: Fully customizable styling and messaging

### Files Modified
- `page-templates/payment.php` - Payment form integration
- `assets/css/flowguard-validation.css` - Remember card styling
- `assets/js/flowguard-validation.js` - Validation system updates
- `FLOWGUARD_REMEMBER_CARD_IMPLEMENTATION.md` - Complete documentation

### Browser Compatibility
- Chrome 60+, Firefox 55+, Safari 12+, Edge 79+
- Requires modern JavaScript and Local Storage API support