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

## 🔧 Recent Fixes

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

#### Database Tables

The integration creates three database tables:
- `wp_flexpress_flowguard_webhooks` - Webhook event logging
- `wp_flexpress_flowguard_transactions` - Transaction records
- `wp_flexpress_flowguard_sessions` - Payment session tracking

### Discord Notifications System

FlexPress includes a comprehensive Discord notification system that provides real-time alerts for all critical payment events and activities.

#### Discord Integration Features

- **Real-Time Notifications**: Instant Discord alerts for all payment events
- **Rich Embeds**: Beautiful, detailed notifications with color coding
- **Customizable Events**: Choose which events trigger notifications
- **Team Collaboration**: Keep your team informed of all activities
- **Easy Setup**: Simple webhook configuration with test functionality

#### Supported Notification Events

- **🎉 New Member Signups** - When someone subscribes to your site
- **💰 Subscription Rebills** - Successful recurring payments
- **❌ Subscription Cancellations** - When members cancel
- **⏰ Subscription Expirations** - When memberships expire
- **🎬 PPV Purchases** - Pay-per-view episode purchases
- **⚠️ Refunds & Chargebacks** - Payment issues and disputes
- **🌟 Talent Applications** - New performer applications

#### Discord Setup Instructions

1. **Create Discord Webhook**:
   - Go to your Discord server → Server Settings → Integrations
   - Click "Create Webhook" in the Webhooks section
   - Choose a channel for notifications (e.g., #payments, #notifications)
   - Copy the webhook URL

2. **Configure FlexPress**:
   - Go to `FlexPress Settings → Discord`
   - Paste your Discord webhook URL
   - Choose which events to notify about
   - Test the connection to verify setup

3. **Customize Notifications**:
   - Enable/disable specific event types
   - All notifications include rich embeds with detailed information
   - Color-coded notifications for easy identification

#### Notification Examples

**New Member Signup:**
```
🎉 New Member Signup!
Member: John Doe
Email: john@example.com
Amount: USD 29.95
Type: Recurring
Next Charge: Jan 15, 2025
```

**PPV Purchase:**
```
🎬 PPV Purchase Approved
Member: Jane Smith
Amount: USD 9.95
Episode: "Hot Summer Nights"
Transaction ID: TXN_12345
```

#### Pro Tips

- **Separate Channels**: Create different Discord channels for different types of notifications
- **Role Mentions**: Use @mentions in webhook settings to ping specific team members
- **Regular Testing**: Test notifications regularly to ensure they're working properly
- **Team Coordination**: Set up role-based notifications for different team members

#### Troubleshooting

**Common Issues Fixed During Implementation:**

1. **API URL**: Use `https://flowguard.yoursafe.com/api/merchant` (not `api.yoursafe.com`)
2. **Minimum Amount**: Flowguard requires minimum $2.95 USD for transactions
3. **Minimum Period**: Subscriptions require minimum 2 days (`P2D`)
4. **Environment**: Sandbox and production use the same API URL
5. **Credentials**: Shop ID `134837` and Signature Key from ControlCenter

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
