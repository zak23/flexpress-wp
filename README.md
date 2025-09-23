# FlexPress WordPress Project

A modern WordPress website running in Docker containers with MySQL database and phpMyAdmin for database management.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed
- Ports 8085 and 8086 available

### Installation

1. **Clone and navigate to the project:**
   ```bash
   cd /home/zak/projects/flexpress
   ```

2. **Start the containers:**
   ```bash
   docker-compose up -d
   ```

3. **Access your WordPress site:**
   - WordPress: https://zakspov.com
   - phpMyAdmin: http://localhost:8086

## 📁 Project Structure

```
flexpress/
├── docker-compose.yml    # Docker services configuration
├── Dockerfile           # Custom WordPress image
├── apache-config.conf   # Apache virtual host config
├── .env                 # Environment variables
├── .env.example         # Environment template
├── wp-content/          # WordPress themes, plugins, uploads
└── README.md            # This file
```

## 🐳 Docker Services

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| WordPress | flexpress_wordpress | 8085 | Main WordPress application |
| MySQL | flexpress_mysql | 3306 (internal) | Database server |
| phpMyAdmin | flexpress_phpmyadmin | 8086 | Database administration |

## 📧 Email Configuration

### Google SMTP Integration
FlexPress includes Google SMTP integration for reliable email delivery, especially for internal emails:

- **Admin Interface**: Configure Google SMTP settings under `FlexPress → Google SMTP`
- **Smart Routing**: Automatically uses Google SMTP for emails to your own domain (e.g., contact@zakspov.com)
- **App Password Support**: Secure authentication using Google App Passwords
- **Internal Email Focus**: Perfect solution for contact forms and internal communications
- **No SES Bounce Issues**: Avoids Amazon SES delivery problems to your own domain
- **Email Testing**: Built-in test email functionality

#### Quick Setup
1. Enable 2-Factor Authentication on your Google account
2. Generate an App Password in Google Account Settings → Security → App passwords
3. Configure settings in `FlexPress → Google SMTP`:
   - SMTP Host: `smtp.gmail.com`
   - SMTP Port: `587`
   - Encryption: `TLS`
   - Username: Your Google Workspace email (e.g., `noreply@zakspov.com`)
   - Password: Your 16-character App Password
   - From Email: `noreply@zakspov.com`
   - Enable "Use for Internal Emails Only" (recommended)
4. Test email delivery using the built-in test function

### Amazon SES Integration
FlexPress includes comprehensive Amazon SES integration for reliable email delivery:

- **Admin Interface**: Configure SES settings under `FlexPress → Amazon SES`
- **SMTP Support**: Full SMTP configuration with TLS/SSL encryption
- **Environment Variables**: Optional secure credential storage via environment variables
- **Email Testing**: Built-in test email functionality
- **Monitoring**: Email delivery statistics and logging
- **Security**: Support for both database and environment variable credential storage

#### Quick Setup
1. Set up Amazon SES in AWS console
2. Verify your domain and create SMTP credentials
3. Configure settings in `FlexPress → Amazon SES`
4. Test email delivery using the built-in test function

For detailed setup instructions, see [Amazon SES Setup Guide](docs/AMAZON_SES_SETUP_GUIDE.md).

### Email Routing Strategy
FlexPress uses intelligent email routing:
- **Internal Emails** (to your domain): Uses Google SMTP for reliable delivery
- **External Emails** (to other domains): Uses Amazon SES or default mail system
- **Contact Forms**: Automatically routed through Google SMTP when sending to your domain
- **Newsletters**: Uses Plunk integration for marketing emails

## 🔧 Recent Fixes

### Flowguard Subscription Extend Webhook Fix (January 2025)
- Fixed critical issue where extending cancelled users incorrectly changed their status to 'active'
- Changes:
  - Modified `flexpress_flowguard_handle_subscription_extend()` to preserve current user status
  - Only updates relevant dates (nextChargeOn for recurring, expiresOn for one-time) without changing status
  - Enhanced logging to show preserved status in activity logs and error logs
- Impact: Cancelled users remain cancelled when extended, only their access dates are updated appropriately.

### Newsletter Modal Turnstile Fix (September 2025)
- Resolved console error: `Uncaught TurnstileError: [Cloudflare Turnstile] Could not find widget.` when subscribing from the newsletter modal
- Changes:
  - Turnstile widget now uses required `cf-turnstile` class and fixed id `newsletter-turnstile`
  - Widget is explicitly rendered on modal open to ensure presence before token retrieval
  - JS targets the rendered widget id for `turnstile.getResponse(widgetId)` and `turnstile.reset(widgetId)`
  - Defensive guards added when `window.turnstile` is not yet ready
- Impact: Newsletter subscribe flow works reliably with Turnstile protection.

### Newsletter Modal Dismissal Persistence (September 2025)
- Added localStorage persistence to prevent the newsletter modal from reappearing after the user closes it
- Uses key `flexpress_newsletter_modal_dismissed` set to `true` on modal hide
- Auto-show is gated by this flag; users won’t be spammed with repeated prompts

### Admin Menu Consolidation (September 2025)
- Consolidated all settings under the single top-level menu: `FlexPress`
- Removed duplicate/standalone menus:
  - Turnstile: now only under `FlexPress → Turnstile`
  - Plunk: now only under `FlexPress → Plunk`
  - Flowguard: removed standalone top-level; now only `FlexPress → Flowguard`
  - Discord: removed standalone top-level; now only `FlexPress → Discord`
- Updated admin enqueue hooks to match correct page hooks (`flexpress-settings_page_*`)

New menu structure:

```
FlexPress
  ├─ General
  ├─ Pages & Menus
  ├─ Auto-Setup
  ├─ Discord
  ├─ Turnstile
  ├─ Plunk
  ├─ Flowguard
  ├─ Video Settings
  ├─ Membership
  ├─ Pricing
  ├─ Affiliate
  └─ Contact
```

### Site URL Configuration (September 2025)
- **URL Standardization**: Standardized WordPress site URL to lowercase `https://zakspov.com` following best practices
- **Database Update**: Modified `wp_options` table to set both `siteurl` and `home` options to standardized URL
- **Documentation Update**: Updated README.md and .cursorrules to reflect correct URL structure
- **Configuration**: 
  - Live Site: `https://zakspov.com` (production site)
  - Test Site: `https://zakspov.com` (development data only)
- **Impact**: All WordPress-generated URLs now use the standardized lowercase domain structure

**Files Modified:**
- Database: `wp_options` table (`siteurl` and `home` options)
- `README.md` - Updated all URL references
- `.cursorrules` - Updated project configuration

### Age Verification Modal (September 2025)
- **SEO-Compliant Implementation**: Modal positioned in footer to avoid H3 interference with main content H1s
- **localStorage Persistence**: Users only see the modal once per browser session
- **Dynamic Styling**: Uses theme accent colors with automatic text color contrast
- **Accessibility Features**: Keyboard navigation support (ESC to exit, Enter to confirm)
- **Responsive Design**: Mobile-optimized layout with proper touch targets
- **Custom Logo Integration**: Automatically displays site logo or falls back to site title
- **Configurable Exit URL**: Admin can set custom exit URL in FlexPress Settings → General
- **Legal Compliance**: Includes required age verification text for adult content sites

**Files Added:**
- `assets/css/age-verification.css` - Modal styling with accent color theming
- `assets/js/age-verification.js` - Modal functionality and localStorage management
- Modal HTML integrated into `footer.php` for SEO compliance

**Usage:**
- Modal appears automatically on first visit
- Users can reset verification status via browser console: `flexpressAgeVerification.reset()`
- Check verification status: `flexpressAgeVerification.status()`
- Configure exit URL in WordPress Admin → FlexPress Settings → General → "Age Verification Exit URL"

### Join Page Continue Button (September 2025)
- **Issue**: Continue button on join page was not responding to clicks
- **Root Cause**: JavaScript selector mismatch (`join-continue-btn` vs `membership-continue-btn`)
- **Solution**: 
  - Fixed JavaScript selector to match button ID
  - Implemented registration functionality using existing `flexpress_process_registration_and_payment` AJAX handler
  - Implemented login functionality using existing `flexpress_ajax_login` AJAX handler
  - Added proper error handling and user feedback
  - Updated WordPress site URL to localhost:8085 for development testing (reverted back to zakspov.com for production)
- **Result**: Continue button now properly processes user registration/login and redirects to payment page

### Payment Success Login Flow (September 2025)
- **Issue**: Users redirected to `/payment-success/` after payment were not logged in, causing redirect to login page
- **Root Cause**: Payment-success page lacked login check and auto-login capability
- **Solution**: 
  - Added login check to payment-success page with auto-login for valid user_id parameters
  - Updated JavaScript redirects in flowguard.js and payment.php to include user_id
  - Payment-success page now handles both logged-in and non-logged-in scenarios gracefully
  - Users with valid user_id are automatically logged in, others redirected to login with return URL
- **Result**: Smooth payment flow without jarring redirects to login page after successful payment

### Enhanced 404 Page (January 2025)
- **Dark Theme Integration**: Fully styled to match FlexPress dark aesthetic with CSS variables
- **Animated Error Display**: Large "404" text with pulsing glow animation and decorative underline
- **Comprehensive Navigation**: Multiple action cards for episodes, models, join, contact, and homepage
- **Search Functionality**: Integrated search form with enhanced styling and hover effects
- **Recent Episodes Preview**: Shows 3 most recent episodes with thumbnails and hover overlays
- **Responsive Design**: Mobile-optimized with proper scaling for all screen sizes
- **Accessibility Features**: Proper focus states, contrast ratios, and keyboard navigation
- **Theme Consistency**: Uses FlexPress CSS variables, accent colors, and design patterns

**Implementation:**
- Enhanced `page-templates/404.php` with comprehensive layout and functionality
- Added 404 page styles to `assets/css/main.css` (following theme convention)
- Integrated with existing FlexPress design system and color variables
- Includes recent episodes query with proper date formatting and timezone handling

**Features:**
- Animated 404 number with glow effects
- Search form with accent color theming
- 5 navigation cards (Episodes, Join, Models, Help, Home)
- Recent episodes section with video thumbnails
- Fully responsive design (desktop → tablet → mobile)
- Dark theme optimized with proper contrast

## 📤 File Upload Configuration

The WordPress site is configured to handle large file uploads:

- **Upload Max Filesize:** 64MB
- **Post Max Size:** 64MB  
- **Memory Limit:** 512MB
- **Max Execution Time:** 300 seconds

These limits are configured in:
- `Dockerfile` - PHP configuration via `/usr/local/etc/php/conf.d/uploads.ini`
- `wp-content/themes/flexpress/functions.php` - WordPress-specific limits

To modify upload limits, update both the Dockerfile and rebuild the container:
```bash
docker-compose down
docker-compose up --build -d
```

## 💳 Payment Integration

### Flowguard Payment System

FlexPress now uses **Flowguard** as the primary payment processing system, replacing Verotel FlexPay. Flowguard provides:

- **Embedded Payment Forms**: No redirects, seamless user experience
- **PCI DSS Compliance**: Secure payment processing with hosted iframes
- **3D Secure Support**: Enhanced security for card transactions
- **Webhook Integration**: Real-time payment notifications
- **Enhanced Reference System**: Meaningful payment references with user data (affiliate, promo, signup source)
- **Admin Dashboard**: Complete payment management interface

#### Flowguard Configuration

1. **Access Settings**: Go to `FlexPress Settings → Flowguard`
2. **Configure API**: Enter your Shop ID and Signature Key from ControlCenter
3. **Set Environment**: Choose between Sandbox (testing) or Production (live)
4. **Test Integration**: Use the built-in testing tools to verify setup

#### Payment Pages

- **Registration**: `/register-flowguard` - Cheeky user registration form
- **Join Page**: `/join-flowguard` - Modern membership signup with Flowguard integration
- **Payment Form**: `/flowguard-payment` - Embedded payment processing
- **Success Page**: `/payment-success` - Payment completion confirmation
- **Declined Page**: `/payment-declined` - Payment failure handling

#### Webhook Endpoint

Flowguard webhooks are automatically handled at:
```
/wp-admin/admin-ajax.php?action=flowguard_webhook
```

#### Enhanced Reference System

FlexPress now includes an enhanced reference system that stores meaningful user information in Flowguard payment references:

**Reference Formats**:

**Subscription References**: `uid123_affAFF12345_promoWELCOME_srcgoogle_planpremium_monthly_reg12345678`
- `uid123` - User ID
- `affAFF12345` - Affiliate code (truncated to 8 chars, or `affnone` if no affiliate)
- `promoWELCOME` - Promo code (truncated to 8 chars, or `promonone` if no promo)
- `srcgoogle` - Signup source (google, facebook, twitter, etc., or `srcnone` if no source)
- `planpremium_monthly` - Plan ID
- `reg12345678` - Registration timestamp (last 8 digits)

**PPV/Unlock References**: `ppv_ep123_uid456_affAFF12345_promoWELCOME_srcgoogle_ts12345678`
- `ppv` - PPV identifier
- `ep123` - Episode ID
- `uid456` - User ID
- `affAFF12345` - Affiliate code (truncated to 8 chars, or `affnone` if no affiliate)
- `promoWELCOME` - Promo code (truncated to 8 chars, or `promonone` if no promo)
- `srcgoogle` - Signup source (google, facebook, twitter, etc., or `srcnone` if no source)
- `ts12345678` - Purchase timestamp (last 8 digits)

**Empty Field Handling**: When fields are empty, the system uses placeholder values (`none`) to maintain consistent reference structure and enable proper parsing.

**Benefits**:
- **User Tracking**: Easily identify users from payment references
- **Affiliate Attribution**: Track which affiliates referred users
- **Promo Analytics**: Monitor promo code usage and effectiveness
- **Source Attribution**: Understand where users are coming from
- **Admin Interface**: View and manage reference data via `FlexPress → Flowguard References`

**Backward Compatibility**: Legacy references (`user_123_plan_456`) continue to work seamlessly.

#### Database Tables

The integration creates three database tables:
- `wp_flexpress_flowguard_webhooks` - Webhook event logging
- `wp_flexpress_flowguard_transactions` - Transaction records
- `wp_flexpress_flowguard_sessions` - Payment session tracking

### Discord Notifications System

FlexPress includes a comprehensive Discord notification system that provides real-time alerts for all critical payment events and activities with **multi-channel webhook support**.

#### Discord Integration Features

- **Multi-Channel Webhooks**: Separate webhooks for different notification types
- **Real-Time Notifications**: Instant Discord alerts for all payment events
- **Rich Embeds**: Beautiful, detailed notifications with color coding
- **Comprehensive Form Data**: Complete casting application details in Discord
- **Data Validation**: Bulletproof against Discord 400 errors
- **Customizable Events**: Choose which events trigger notifications
- **Team Collaboration**: Keep your team informed of all activities
- **Easy Setup**: Simple webhook configuration with test functionality

#### Multi-Channel Webhook System

FlexPress supports **3 webhook categories** for organized notifications:

1. **🔔 Default Webhook** - Fallback for all notifications
2. **💰 Financial Notifications** - All payment/subscription related events
3. **📝 Contact Forms** - Talent applications and contact form submissions

#### Supported Notification Events

**Financial Notifications (💰 Channel):**
- **🎉 New Member Signups** - When someone subscribes to your site
- **💰 Subscription Rebills** - Successful recurring payments
- **❌ Subscription Cancellations** - When members cancel
- **⏰ Subscription Expirations** - When memberships expire
- **🔄 Subscription Extensions** - When memberships are extended
- **🎬 PPV Purchases** - Pay-per-view episode purchases
- **⚠️ Refunds & Chargebacks** - Payment issues and disputes

**Contact Forms (📝 Channel):**
- **🌟 Talent Applications** - Complete casting application details
- **📧 Contact Form Submissions** - General inquiries
- **🆘 Support Requests** - Customer support tickets

#### Discord Setup Instructions

1. **Create Discord Webhooks**:
   - Go to your Discord server → Server Settings → Integrations
   - Click "Create Webhook" for each channel you want to use
   - Recommended channels: `#financial-alerts`, `#contact-forms`
   - Copy the webhook URLs

2. **Configure FlexPress**:
   - Go to `FlexPress Settings → Discord`
   - **Default Discord Webhook URL** - Fallback for all notifications
   - **Financial Notifications Webhook** - For payment/subscription events
   - **Contact Forms Webhook** - For talent applications and contact forms
   - Test the connection to verify setup

3. **Customize Notifications**:
   - Enable/disable specific event types
   - All notifications include rich embeds with detailed information
   - Color-coded notifications for easy identification

#### Standardized Notification Format

All Discord notifications follow a consistent data structure for easy tracking and management:

**Standard Field Order:**
1. **Username** - User's display name
2. **User ID** - WordPress user ID (for tracking)
3. **Email** - User's email address (subscription events only)
4. **Amount** - Payment amount with currency
5. **Transaction ID** - Flowguard transaction identifier
6. **Sale ID** - Flowguard sale identifier
7. **Additional Fields** - Event-specific information

#### Notification Examples

**🎉 New Member Signup:**
```
🎉 New Member Signup!
Username: John Doe
User ID: 123
Email: john@example.com
Amount: USD 29.95
Subscription Type: Recurring
Transaction ID: 123456789
Sale ID: 987654321
Next Charge: Jan 15, 2025
```

**💰 Subscription Rebill:**
```
💰 Subscription Rebill Success
Username: John Doe
User ID: 123
Amount: USD 29.95
Transaction ID: 123456790
Sale ID: 987654321
Next Charge: Feb 15, 2025
```

**🎬 PPV Purchase:**
```
🎬 PPV Purchase Approved
Username: John Doe
User ID: 123
Amount: USD 9.95
Transaction ID: 123456791
Episode: "Hot Summer Nights"
Episode Link: View Episode
```

**❌ Subscription Cancelled:**
```
❌ Subscription Cancelled
Username: John Doe
User ID: 123
Cancelled By: User
Sale ID: 987654321
```

**⚠️ Refund/Chargeback:**
```
⚠️ Chargeback Processed
Username: John Doe
User ID: 123
Amount: USD 29.95
Transaction ID: 123456792
Order Type: Subscription
```

**⏰ Subscription Expired:**
```
⏰ Subscription Expired
Username: John Doe
User ID: 123
Sale ID: 987654321
Subscription Type: Recurring
```

**🔄 Subscription Extended:**
```
🔄 Subscription Extended
Username: John Doe
User ID: 123
Amount: USD 29.95
Subscription Type: Recurring
Transaction ID: 123456793
Sale ID: 987654321
Next Charge: Mar 15, 2025
```

#### Pro Tips

- **Separate Channels**: Create different Discord channels for different types of notifications
- **Role Mentions**: Use @mentions in webhook settings to ping specific team members
- **Regular Testing**: Test notifications regularly to ensure they're working properly
- **Team Coordination**: Set up role-based notifications for different team members

#### Troubleshooting Discord Issues

**Common Issues Fixed During Implementation:**

1. **Discord 400 "Bad Request" Errors**:
   - **Cause**: Data exceeding Discord's character limits or invalid formatting
   - **Solution**: Implemented comprehensive data validation and sanitization
   - **Features**: Automatic field truncation, markdown removal, array handling

2. **PHP Fatal Error: strlen() on Array**:
   - **Cause**: Contact Form 7 sending array data (checkboxes, multi-selects)
   - **Solution**: Added array detection and implode() conversion to strings
   - **Location**: `contact-form-7-discord-integration.php` sanitization function

3. **Missing Webhook Fields in Admin**:
   - **Cause**: Settings registered in wrong class file
   - **Solution**: Updated `class-flexpress-settings.php` to register all webhook fields
   - **Result**: Now shows Default, Financial, and Contact webhook fields

4. **Incomplete Form Data in Discord**:
   - **Cause**: Limited field mapping in casting form integration
   - **Solution**: Added all casting form fields (gender_identity, stage_age, social media, etc.)
   - **Result**: Complete casting application details now sent to Discord

**Data Validation Features:**
- Field values truncated to 1024 characters (Discord limit)
- Markdown characters removed to prevent formatting issues
- Arrays converted to comma-separated strings
- Empty values replaced with "Not provided"
- Maximum 25 fields per embed (Discord limit)
- Enhanced error logging with response body and payload details

#### Troubleshooting

**Common Issues Fixed During Implementation:**

1. **API URL**: Use `https://flowguard.yoursafe.com/api/merchant` (not `api.yoursafe.com`)
2. **Minimum Amount**: Flowguard requires minimum $2.95 USD for transactions
3. **Minimum Period**: Subscriptions require minimum 2 days (`P2D`)
4. **Environment**: Sandbox and production use the same API URL
5. **Credentials**: Shop ID `134837` and Signature Key from ControlCenter

### Cloudflare Turnstile Protection

FlexPress includes comprehensive Cloudflare Turnstile integration for advanced bot protection across all forms. Turnstile provides invisible bot protection without requiring users to solve CAPTCHAs, making it privacy-focused and GDPR compliant.

#### Turnstile Features

- **🛡️ Invisible Protection**: Most users won't see the widget unless suspicious activity is detected
- **🔒 Privacy-First**: No personal data is collected or stored
- **📱 GDPR Compliant**: No cookies or tracking required
- **📱 Mobile Friendly**: Works seamlessly on all devices
- **⚡ Performance Optimized**: Minimal impact on page load times
- **🎨 Theme Integration**: Automatically matches your site's theme

#### Protected Forms

Turnstile protects the following form types:

- **📧 Contact Forms**: Contact Form 7, WPForms, and other contact form plugins
- **💬 Comment Forms**: WordPress native comment system
- **👤 Registration Forms**: User registration and signup forms
- **🔑 Login Forms**: User authentication and login forms

#### Turnstile Configuration

1. **Access Settings**: Go to `FlexPress Settings → Turnstile`
2. **Get Keys**: 
   - Visit [Cloudflare Dashboard](https://dash.cloudflare.com/) → Turnstile → Add Site
   - Enter your domain and choose widget mode
   - Copy your Site Key and Secret Key
3. **Configure Settings**:
   - Paste your Site Key and Secret Key
   - Choose widget theme (Auto/Light/Dark)
   - Select widget size (Normal/Compact)
   - Choose which forms to protect
4. **Test Connection**: Use the built-in test tool to verify your configuration

#### Turnstile Test Tool Behavior

- The admin Test Connection button hits Cloudflare's `siteverify` endpoint using your Secret Key. Because no user token is sent during a test, Cloudflare will typically respond with validation error codes (e.g., `missing-input-response`).
- This still confirms connectivity. The tool reports success when the API is reachable and returns a JSON response, even if validation errors are present due to the missing token.
- If the API cannot be reached (network/DNS/SSL issues), the tool reports an error with the underlying reason.

Troubleshooting:

- Ensure both Site Key and Secret Key are saved in `FlexPress Settings → Turnstile`.
- If you see a generic “undefined” message, refresh and try again. The tool now defaults to clear messages when `response.data` is missing.
- Check `wp-content/debug.log` for any “Turnstile validation error” entries when validating actual form submissions.

#### Widget Customization

**Theme Options:**
- **Auto**: Automatically matches your site's theme
- **Light**: Light theme for light backgrounds
- **Dark**: Dark theme for dark backgrounds

**Size Options:**
- **Normal**: Standard size widget
- **Compact**: Smaller, less intrusive widget

#### Form Protection Settings

You can enable/disable Turnstile protection for each form type:

- ✅ **Contact Forms**: Protect all contact forms (Contact Form 7, WPForms, etc.)
- ✅ **Comment Forms**: Protect WordPress comment forms
- ✅ **Registration Forms**: Protect user registration forms
- ✅ **Login Forms**: Protect user login forms

#### Technical Implementation

**Frontend Integration:**
- Automatically loads Cloudflare Turnstile script when enabled
- Adds Turnstile widget to protected forms
- Includes callback functions for token handling

**Server-Side Validation:**
- Validates Turnstile responses against Cloudflare API
- Prevents form submission if validation fails
- Logs validation errors for debugging

**Helper Functions:**
- `flexpress_is_turnstile_enabled()` - Check if Turnstile is configured
- `flexpress_should_protect_contact_forms()` - Check contact form protection
- `flexpress_should_protect_comment_forms()` - Check comment form protection
- `flexpress_should_protect_registration_forms()` - Check registration protection
- `flexpress_should_protect_login_forms()` - Check login form protection
- `flexpress_validate_turnstile_response()` - Validate Turnstile tokens

#### Troubleshooting

**Common Issues:**

1. **Widget Not Appearing**: Check that Site Key is correctly entered
2. **Validation Failing**: Verify Secret Key is correct and matches Site Key
3. **Forms Not Protected**: Ensure the specific form type is enabled in settings
4. **Theme Issues**: Try switching between Auto/Light/Dark themes

**Debug Information:**
- Turnstile validation errors are logged to WordPress error logs
- Use browser console to check for JavaScript errors
- Test connection tool validates both keys simultaneously

#### Pro Tips

- **Invisible Mode**: Most legitimate users won't see the widget
- **Performance**: Turnstile has minimal impact on page load times
- **Security**: Provides protection against automated attacks and spam
- **User Experience**: Seamless integration without interrupting user flow
- **Compliance**: GDPR compliant with no cookies or tracking

### Plunk Email Marketing Integration

FlexPress includes comprehensive Plunk email marketing integration for automated email campaigns, user segmentation, and subscription management. Plunk provides powerful email marketing automation with advanced features for content creators.

#### Plunk Features

- **📧 Automated User Registration**: New users are automatically added to Plunk with appropriate tags
- **📱 Newsletter Subscription Management**: Frontend and backend subscription controls
- **🛡️ Security Integration**: Cloudflare Turnstile and honeypot protection for all forms
- **👥 User Segmentation**: Automatic tagging based on user behavior and membership status
- **📊 Event Tracking**: Comprehensive activity tracking for user engagement
- **⚙️ Admin Management**: WordPress admin interface for contact management
- **🎯 Newsletter Modal**: Beautiful newsletter signup modal with customizable timing

#### Plunk Configuration

1. **Access Settings**: Go to `FlexPress Settings → Plunk`
2. **Get Credentials**: 
   - Sign up at [Plunk.com](https://plunk.com)
   - Copy your **Public API Key (pk_...)**, **Secret API Key (sk_...)**, and **Install URL**
3. **Configure Settings**:
   - Paste your Public API Key, Secret API Key, and Install URL
   - Enable auto-subscribe for new users
   - Configure newsletter modal settings
   - Set modal delay timing
4. **Test Connection**: Use the built-in test tool to verify your setup

#### Plunk Testing & Diagnostics

- Click "Test Plunk Connection" in `FlexPress Settings → Plunk`.
- Detailed logs are written to `wp-content/debug.log` with the prefix `[FlexPress][Plunk]`:
  - `[Test] Starting connection test / Success / Error`
  - `[Request]` url, method, timeout, has_body, masked keys
  - `[Response]` HTTP status, duration_ms, and a short body snippet
- Keys are masked in logs; the secret key is never printed in full.

#### Newsletter Modal Features

**Automatic Display:**
- Shows after configurable delay (1-60 seconds)
- Beautiful gradient design matching your brand
- Mobile-responsive layout
- Turnstile protection integration

**Customization Options:**
- **Modal Delay**: Control when the modal appears (default: 5 seconds)
- **Auto Subscribe**: Automatically subscribe new users to newsletter
- **Security Protection**: Turnstile and honeypot protection enabled

#### User Segmentation

The system automatically segments users based on their behavior:

**Registration Segmentation:**
- **Source**: "Membership Registration"
- **User Type**: "member"
- **Membership Status**: "active"
- **Signup Date**: Automatic timestamp

**Newsletter Segmentation:**
- **Source**: "Newsletter Modal"
- **User Type**: "newsletter_subscriber"
- **Signup Date**: Automatic timestamp

#### Event Tracking

Track comprehensive user behavior:

- **🎬 Video Views**: Track which videos users watch
- **💰 Purchases**: Track payment events and amounts
- **📄 Page Views**: Track user navigation patterns
- **📧 Newsletter Signups**: Track subscription events
- **👤 User Registration**: Track new member signups

#### Technical Implementation

**API Integration:**
- Complete Plunk API wrapper with error handling
- Automatic retry logic for failed requests
- Comprehensive contact management
- Event tracking and analytics

**WordPress Integration:**
- Automatic user registration hooks
- User deletion cleanup
- AJAX handlers for frontend interactions
- Shortcode support for newsletter management

**Security Features:**
- Turnstile integration for bot protection
- Honeypot fields for spam prevention
- Input sanitization and validation
- Nonce verification for AJAX requests

#### Helper Functions

**Core Functions:**
- `flexpress_is_plunk_enabled()` - Check if Plunk is configured
- `flexpress_should_show_newsletter_modal()` - Check modal display settings
- `flexpress_track_plunk_event()` - Track custom events
- `flexpress_track_video_view()` - Track video engagement
- `flexpress_track_purchase()` - Track purchase events

**Newsletter Management:**
- `flexpress_render_newsletter_status()` - Display subscription status
- `flexpress_render_newsletter_modal()` - Render newsletter modal
- `[newsletter_status]` - Shortcode for subscription management

#### Admin Management

**Settings Page Features:**
- API credential configuration
- Newsletter modal settings
- Auto-subscribe options
- Connection testing tools
- User sync functionality

**User Sync Tools:**
- Bulk sync existing WordPress users
- Automatic contact ID storage
- Error handling and reporting
- Progress tracking

#### Newsletter Shortcode

Display subscription management for logged-in users:

```
[newsletter_status]
```

**Features:**
- Toggle switch for subscription status
- Real-time status updates
- AJAX-powered interactions
- Responsive design

#### Troubleshooting

**Common Issues:**

1. **API Connection Failed**: Verify Public API Key, Secret API Key, and Install URL are correct
2. **Modal Not Showing**: Check if newsletter modal is enabled in settings
3. **Users Not Syncing**: Ensure auto-subscribe is enabled
4. **Events Not Tracking**: Verify API credentials and user contact IDs

**Debug Information:**
- Plunk API errors are logged to WordPress error logs
- Use browser console to check for JavaScript errors
- Test connection tool validates API credentials
- User sync tool provides detailed results

#### Pro Tips

- **Segmentation Strategy**: Use automatic tagging for targeted campaigns
- **Event Tracking**: Track user engagement to improve content strategy
- **Modal Timing**: Adjust delay based on your audience behavior
- **Security**: Always enable Turnstile protection for forms
- **Analytics**: Use event tracking data for business insights

### Casting Section

FlexPress includes a professional casting section that appears above the footer on all pages, designed to attract new talent to join the Dolls Down Under family.

#### Casting Section Features

- **Professional Presentation**: Large image with compelling benefits list
- **Comprehensive Benefits**: 10 key benefits including professional production, competitive rates, flexible scheduling
- **Call-to-Action**: Prominent "Apply Now" button linking to `/casting` page
- **Responsive Design**: Mobile-friendly layout with Bootstrap grid system
- **Theme Integration**: Uses theme's accent colors and styling for consistency

#### Benefits Highlighted

- Professional production environment
- Competitive rates
- Flexible scheduling
- Safe and respectful workplace
- Professional photography included
- Hair and makeup provided
- Flexible content agreements
- Award-winning production team
- Secure, private filming locations
- Industry-standard contracts

#### Technical Implementation

- **Template Part**: `template-parts/casting-section.php`
- **Integration**: Automatically included above footer via `footer.php`
- **Styling**: Uses theme's existing CSS classes (`btn-accent`, `text-white`, etc.)
- **Responsive**: Bootstrap classes for mobile optimization
- **Accessibility**: Proper alt text and semantic HTML structure

### Awards and Nominations Section

FlexPress includes a professional awards and nominations section that showcases industry recognition and achievements, displayed prominently on the homepage.

#### Awards Section Features

- **Subtle Design**: Minimal, understated presentation with grayscale logos
- **Simple Layout**: Horizontal layout with small subtitle and logo
- **Hover Effects**: Logos become colorful on hover with smooth transitions
- **External Links**: Direct links to award websites for verification
- **Responsive Layout**: Mobile-optimized with adaptive sizing
- **Theme Integration**: Transparent background with subtle borders

#### Awards Management

- **Multiple Awards Support**: Add unlimited awards and recognitions
- **Individual Logos**: Each award can have its own logo/badge
- **Custom Links**: Optional external links for each award
- **Flexible Titles**: Custom titles for each award
- **Alt Text**: Proper accessibility with custom alt text

#### Technical Implementation

- **Template Part**: `template-parts/awards-nominations.php`
- **Integration**: Included in homepage template above the footer
- **Styling**: Comprehensive CSS in `main.css` (lines 6123-6184)
- **Admin Settings**: Managed through FlexPress Settings → General → Awards & Recognition
- **Helper Functions**: `includes/awards-helpers.php` for settings management
- **Conditional Display**: Only shows when enabled and logo is uploaded

#### CSS Classes

- `.awards-nominations-section`: Main container with subtle borders
- `.awards-subtitle`: Small subtitle text with muted color
- `.award-link-subtle`: Award link with opacity transitions
- `.award-image-subtle`: Award logo with grayscale filter
- `.awards-logos`: Flex container for logo alignment

#### Design Features

- **Transparent Background**: Clean, minimal appearance
- **Subtle Borders**: Top and bottom borders for section definition
- **Grayscale Effect**: Logos start grayscale and become colorful on hover
- **Small Typography**: Understated subtitle with muted colors
- **Horizontal Layout**: Simple left-right alignment
- **Responsive Design**: Adapts to mobile with centered alignment

#### Admin Management

The Awards section can be fully managed through the WordPress admin:

- **Enable/Disable**: Toggle the section on/off via checkbox
- **Custom Title**: Set a custom title (default: "Awards & Recognition")
- **Multiple Awards**: Add unlimited awards with individual settings
- **Individual Logos**: Upload custom logo for each award
- **Custom Links**: Set unique URL for each award (optional)
- **Alt Text**: Set accessibility text for each award logo
- **Conditional Display**: Section only appears when enabled AND at least one award has a logo

**Admin Location**: FlexPress Settings → General → Awards & Recognition

**Helper Functions Available**:
- `flexpress_is_awards_section_enabled()` - Check if section is enabled
- `flexpress_get_awards_title()` - Get section title
- `flexpress_get_awards_list()` - Get array of all awards
- `flexpress_get_awards_count()` - Get number of awards
- `flexpress_should_display_awards_section()` - Check if section should display
- `flexpress_get_awards_data()` - Get all awards data as array

**Data Sanitization**: All awards data is properly sanitized through `flexpress_sanitize_general_settings()` function including awards_enabled, awards_title, and awards_list array with individual award fields.

### Featured On Section

FlexPress includes a professional "Featured On" section that showcases media outlets and publications that have featured the site, displayed prominently on the homepage with an interactive slider.

#### Featured On Section Features

- **Interactive Slider**: Slick carousel displaying multiple media outlets
- **Professional Design**: Grayscale logos that become colorful on hover
- **External Links**: Direct links to media outlet websites
- **Responsive Layout**: Mobile-optimized with adaptive slide counts
- **Theme Integration**: Matches FlexPress dark theme with subtle borders
- **Auto-rotation**: Automatic slider rotation with manual controls and dots

#### Current Media Outlets Displayed

- **Aus Adult News** - Australian adult industry news and reviews
- **Adult Industry News** - Industry publication (placeholder)
- **Industry Insider** - Professional industry coverage (placeholder)
- **Media Spotlight** - Featured content showcase (placeholder)

#### Technical Implementation

- **Template Part**: `template-parts/featured-on.php`
- **Integration**: Included in homepage template above footer
- **Styling**: Comprehensive CSS in `main.css` (lines 6341-6511)
- **Slider**: Slick carousel with custom styling and responsive breakpoints
- **Scripts**: Slick slider CSS/JS loaded only on homepage for performance
- **Admin Settings**: Managed through FlexPress Settings → General → Featured On Section

#### CSS Classes

- `.featured-on-section`: Main container with border styling
- `.media-slider-wrapper`: Slider container with padding
- `.media-slide`: Individual slide container
- `.media-link`: Media outlet link with hover effects
- `.media-logo`: Logo images with grayscale filter
- `.media-name`: Media outlet name with typography styling
- `.slick-dots`: Custom styled navigation dots

#### Design Features

- **Subtle Borders**: Top and bottom borders for section definition
- **Hover Effects**: Lift animation and color transitions
- **Grayscale Filter**: Logos start grayscale and become colorful on hover
- **Card Design**: Each media outlet in a subtle card with borders
- **Responsive Breakpoints**: 4 slides on desktop, 3 on tablet, 2 on mobile, 1 on small mobile

#### Admin Settings

The Featured On section can be managed through **FlexPress Settings → General → Featured On Section**:

- **Enable/Disable**: Toggle the section on/off with a checkbox
- **Media Outlets Management**: Add, edit, or remove media outlets with:
  - **Name**: Display name of the media outlet
  - **URL**: Link to the media outlet's website
  - **Logo**: Upload logo via WordPress media library OR enter external URL
  - **Alt Text**: Accessibility text for the logo
- **Dynamic Management**: Add unlimited media outlets with "Add Media Outlet" button
- **Remove Functionality**: Each outlet can be individually removed
- **Default Content**: Includes Aus Adult News as default when no outlets are configured

#### Image Management Features

- **WordPress Media Library Integration**: Upload logos directly through WordPress media library
- **Image Preview**: See uploaded logos in admin interface before saving
- **Fallback Support**: Option to use external URLs for logos not in media library
- **Automatic Optimization**: WordPress handles image optimization and responsive sizing
- **Security**: All uploaded images are validated and sanitized
- **Performance**: Locally hosted images load faster than external hotlinks

#### Helper Functions

- `flexpress_is_featured_on_enabled()`: Check if the section is enabled
- `flexpress_get_featured_on_media()`: Get configured media outlets array
- Automatic fallback to default content when no outlets are configured

## 🎬 Upcoming Episode System

### Overview
The FlexPress theme includes an automatic upcoming episode system that displays the next scheduled episode with countdown timers, teaser videos, and hero-style design.

### Features
- **Automatic Detection**: Automatically finds the next scheduled episode
- **Countdown Timer**: Real-time countdown to episode release
- **Teaser Video Support**: Optional video preview with autoplay
- **Hero-Style Design**: Matches your site's aesthetic
- **Responsive Design**: Mobile-optimized layout with adaptive timers
- **Smart Display**: Only shows if there's a scheduled episode

### How It Works
The system automatically:
1. **Queries for scheduled episodes** (`post_status => 'future'`)
2. **Gets the next upcoming episode** (ordered by date ASC)
3. **Displays countdown timer** to the release date
4. **Shows teaser video** if available
5. **Prevents clicking** until episode is released

### Technical Implementation

#### Integration
- **Homepage**: Automatically displays in `page-templates/page-home.php`
- **Styling**: All CSS in `main.css` (lines 5680-6120)
- **JavaScript**: Inline countdown timer and video autoplay

#### Key Features
- **Automatic Post Query**: Uses `WP_Query` with `post_status => 'future'`
- **Video Autoplay**: Teaser videos autoplay after 3 seconds with intersection observer
- **Responsive Timers**: Timer layout adapts to screen size
- **BunnyCDN Integration**: Uses existing video settings for teaser videos
- **Performance**: Lazy loading for videos and optimized CSS

#### CSS Classes
- `.upcoming-episode-section`: Main container
- `.hero-section`: Hero-style wrapper
- `.countdown-timer`: Countdown timer container
- `.countdown-unit`: Individual timer elements (days, hours, minutes, seconds)
- `.hero-content-overlay`: Content overlay with gradient background

### Usage

#### Automatic Display
Simply **schedule an episode** in WordPress admin:
1. Create a new episode post
2. Set the **publish date** to a future date/time
3. Add **preview video** and **featured models** if desired
4. The upcoming episode will automatically appear on your homepage

#### Manual Display
You can also include the upcoming episode anywhere using the template part:

```php
<?php get_template_part('template-parts/upcoming-episode'); ?>
```

**Use Cases:**
- **Homepage**: Already included automatically
- **Episodes Page**: Add to `page-templates/episodes.php`
- **Custom Pages**: Include in any template
- **Sidebar Widget**: Create a custom widget
- **Shortcode**: Wrap in a shortcode function

**Example Usage:**
```php
// In any template file
<?php get_template_part('template-parts/upcoming-episode'); ?>

// In a custom widget
public function widget($args, $instance) {
    echo $args['before_widget'];
    get_template_part('template-parts/upcoming-episode');
    echo $args['after_widget'];
}

// In a shortcode
function upcoming_episode_shortcode($atts) {
    ob_start();
    get_template_part('template-parts/upcoming-episode');
    return ob_get_clean();
}
add_shortcode('upcoming_episode', 'upcoming_episode_shortcode');
```

### Styling Customization
The upcoming episode system uses CSS custom properties and can be customized by overriding the styles in your child theme or via the WordPress customizer.

## 🔒 Hidden Episode System

### Overview
The FlexPress theme includes a comprehensive episode visibility system that allows content creators to hide episodes from public view, requiring user registration to access previews and content.

### Features
- **ACF Integration**: Simple checkbox field in episode editor
- **Automatic Filtering**: All episode queries automatically exclude hidden episodes for non-logged-in users
- **Search Protection**: Hidden episodes are excluded from search results for public users
- **Individual Page Protection**: Direct access to hidden episodes redirects non-logged-in users to login
- **Helper Functions**: Comprehensive utility functions for visibility checking

### How It Works
The system automatically:
1. **Checks user login status** on all episode displays
2. **Filters episode queries** to exclude hidden episodes for public users
3. **Protects individual episodes** with redirect to login page
4. **Excludes from search results** for non-logged-in users
5. **Shows all episodes** to logged-in users regardless of visibility setting

### Technical Implementation

#### ACF Field Configuration
- **Field Name**: `hidden_from_public`
- **Field Type**: True/False (checkbox)
- **Default Value**: `false` (public)
- **Location**: Episode Videos field group
- **Instructions**: "Check this box to hide this episode from non-logged-in users. Only registered users will be able to see previews and access this content."

#### Helper Functions
Located in `includes/episode-visibility-helpers.php`:

```php
// Check if episode is hidden from public
flexpress_is_episode_hidden_from_public($episode_id)

// Check if current user can view episode
flexpress_can_user_view_episode($episode_id)

// Get meta query for visibility filtering
flexpress_get_episode_visibility_meta_query()

// Apply visibility filtering to query args
flexpress_add_episode_visibility_to_query($args)

// Check if episode should be displayed
flexpress_should_display_episode($episode_id)

// Get count of visible episodes for current user
flexpress_get_visible_episodes_count($additional_args)

// Display visibility notice for non-logged-in users
flexpress_display_episode_visibility_notice($context)
```

#### Query Filtering
All episode queries automatically apply visibility filtering:

**Homepage Queries:**
- Hero episode section
- Featured episodes grid
- Recent episodes grid

**Archive Pages:**
- Episode archive (`archive-episode.php`)
- Episodes page (`page-templates/episodes.php`)
- Episode grid template (`template-parts/episode-grid.php`)

**Search Results:**
- Main search query filtering via `pre_get_posts` hook
- Search page template filtering

**Individual Episodes:**
- Single episode template (`single-episode.php`) with redirect protection

### Usage

#### Setting Episode Visibility
1. **Edit an episode** in WordPress admin
2. **Scroll to Episode Videos section**
3. **Check "Hidden from Public"** checkbox
4. **Save the episode**

#### For Developers
Use helper functions to check visibility:

```php
// Check if user can view specific episode
if (flexpress_can_user_view_episode($episode_id)) {
    // Display episode content
    get_template_part('template-parts/content', 'episode-card');
}

// Apply visibility filtering to custom queries
$args = array(
    'post_type' => 'episode',
    'posts_per_page' => 10
);
$args = flexpress_add_episode_visibility_to_query($args);
$query = new WP_Query($args);

// Get count of visible episodes
$visible_count = flexpress_get_visible_episodes_count();
```

#### Display Visibility Notice
Show a notice to non-logged-in users about hidden content:

```php
// In any template
flexpress_display_episode_visibility_notice('homepage');
```

### Security Features
- **Query-Level Protection**: Hidden episodes never appear in public queries
- **Direct Access Protection**: Attempting to access hidden episodes redirects to login
- **Search Protection**: Hidden episodes excluded from search results
- **Template-Level Checks**: Additional validation in template files

### User Experience
- **Seamless for Logged-in Users**: All episodes visible regardless of setting
- **Clear Messaging**: Non-logged-in users see registration prompts
- **No Broken Links**: Hidden episodes don't appear in navigation or search
- **Consistent Behavior**: Same visibility rules apply across all site areas

## ⚙️ Configuration

### Environment Variables
Edit `.env` file to customize:
- Database credentials
- WordPress debug settings
- Port configurations

### WordPress Customization
- Themes: `wp-content/themes/`
- Plugins: `wp-content/plugins/`
- Uploads: `wp-content/uploads/`

## 🛠️ Development Commands

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down

# Rebuild containers
docker-compose up --build -d

# Access WordPress container
docker exec -it flexpress_wordpress bash

# Access MySQL container
docker exec -it flexpress_mysql mysql -u root -p
```

## 🔧 WP-CLI Integration

FlexPress includes WP-CLI for powerful WordPress management directly from the command line.

### Using WP-CLI

```bash
# Use the convenient wrapper script
./wp-cli.sh [command]

# Examples:
./wp-cli.sh plugin list
./wp-cli.sh db export backup.sql
./wp-cli.sh user list
./wp-cli.sh theme status
./wp-cli.sh core version

# Or run directly in the container
docker exec -it flexpress_wordpress wp [command]
```

### Common WP-CLI Commands

```bash
# Plugin management
./wp-cli.sh plugin list
./wp-cli.sh plugin install contact-form-7
./wp-cli.sh plugin activate contact-form-7

# Database operations
./wp-cli.sh db export backup.sql
./wp-cli.sh db import backup.sql
./wp-cli.sh db search-replace 'old-domain.com' 'new-domain.com'

# User management
./wp-cli.sh user list
./wp-cli.sh user create admin admin@example.com --role=administrator

# Theme management
./wp-cli.sh theme list
./wp-cli.sh theme activate flexpress

# Core WordPress
./wp-cli.sh core version
./wp-cli.sh core update
./wp-cli.sh core download --force
```

## 🔧 Troubleshooting

### Port Conflicts
- If port 8085 is occupied, change `SERVER_PORT` in `.env`
- Update docker-compose.yml ports mapping accordingly

### Database Issues
- Check container logs: `docker-compose logs db`
- Access phpMyAdmin at http://localhost:8086
- Default credentials in `.env` file

### WordPress Issues
- Enable debug mode in `.env`: `WORDPRESS_DEBUG=1`
- Check logs: `docker-compose logs wordpress`
- Access container: `docker exec -it flexpress_wordpress bash`

### Clearing debug.log
- Go to WordPress Admin → `FlexPress → Tools`
- Use the "Clear debug.log" button to truncate `wp-content/debug.log`
- The tool is nonce-protected and requires `manage_options` capability
- Shows current file size and last modified time before clearing

## 🔒 Security Notes

- Change default passwords before production
- Use environment variables for sensitive data
- Enable SSL/HTTPS in production
- Regular security updates required
- Keep WordPress core and plugins updated

## 📝 Development Guidelines

- Use IP addresses instead of localhost for server configuration
- Avoid port 3000 (reserved for MCP tools)
- All customizations in `wp-content/` directory
- Follow WordPress coding standards
- Test changes in development before production

## 🔧 Recent Updates

### January 2025
- **Enhanced Model Archive Filtering System**: Added comprehensive Vixen.com-style filtering to model archive pages
  - **Sidebar Filters**: Toggle-able sidebar with sorting and filtering options
  - **Sorting Options**: Newest, Oldest, and Alphabetical (A-Z) sorting
  - **Category Filtering**: Filter models by post tags with count display
  - **Alphabetical Filtering**: A-Z grid for quick model name filtering
  - **Dynamic Layout**: 8/4 column layout with filters, 12 column layout without filters
  - **Responsive Grid**: 2 models per row with filters, 3 models per row without filters
  - **Vixen-Style Pagination**: First/Back/Next/Last navigation with page info
  - **Active Filter Display**: Shows current filters with clear functionality
  - **Enhanced UX**: Consistent styling and behavior matching episode archive page
  - **JavaScript Toggle**: Smooth show/hide filter functionality
  - **Clear Filters**: Easy reset to remove all active filters
- **Fixed One-Time Payment Pricing Logic**: Resolved critical bug where "One-Time Payment" plans were incorrectly configured as lifetime access
  - Separated 'one_time' and 'lifetime' plan types properly in admin interface
  - One-time payments now allow configurable durations (30 days, 90 days, etc.) instead of forcing 999 years
  - Updated JavaScript logic in pricing admin to handle plan types correctly
  - Fixed default pricing plans to use correct plan types ('lifetime' for actual lifetime access)
  - Updated admin form behavior to enable duration fields for one-time payments
  - Clarified admin interface descriptions to distinguish between plan types

### September 2025
- **Enhanced Color Contrast System**: Fixed readability issues with light accent colors by implementing automatic text color detection
  - Added `flexpress_get_contrast_text_color()` function that calculates luminance to determine optimal text color
  - Updated admin color picker with real-time preview that automatically adjusts text color (black for light backgrounds, white for dark backgrounds)
  - Enhanced CSS generation to include `--color-accent-text` variable for consistent contrast across the theme
  - Updated button styles to automatically use appropriate text color based on accent color luminance
  - Solves the issue where light colors like yellow made white text unreadable on buttons
  - Uses industry-standard luminance formula (0.299*R + 0.587*G + 0.114*B) for accurate contrast calculation

- **Fixed Daily Pricing Calculations**: Corrected membership page to show accurate daily rates instead of full plan prices
  - Added `flexpress_calculate_daily_rate()` function to properly convert plan prices to daily rates
  - Added `flexpress_get_daily_rate_display()` helper for formatted display
  - Fixed pricing display where $29.95/30 days was incorrectly showing as $29.95/Per Day
  - Now correctly displays: $1.00/day for 30-day plans, $0.67/day for 90-day plans, $0.56/day for 180-day plans
  - Handles different duration units (days, weeks, months, years) with proper conversion
  - Includes trial price calculation when applicable

## 🆘 Support

For issues or questions:
1. Check Docker container logs
2. Verify port availability
3. Check environment configuration
4. Review this documentation
