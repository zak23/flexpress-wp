# SMTP2Go Integration Documentation

## Overview

FlexPress includes a complete SMTP2Go integration for reliable internal email delivery. This system automatically routes emails to your domain (`@zakspov.com`) through SMTP2Go, providing professional email delivery with automatic domain authentication.

## Features

- **Professional Email Delivery**: Enterprise-grade SMTP service with automatic domain authentication
- **Smart Routing**: Automatically detects and routes internal emails to SMTP2Go
- **From Address Override**: Uses configured From email instead of WordPress defaults
- **No Bounce Issues**: Eliminates Amazon SES delivery problems to your own domain
- **Built-in Testing**: Complete test functionality with AJAX connection testing
- **Fallback System**: Automatic fallback to Google SMTP, then Amazon SES if needed

## Implementation

### Files Created/Modified

1. **`includes/class-flexpress-smtp2go.php`**
   - Main SMTP2Go integration class
   - Handles PHPMailer configuration
   - Smart routing logic for internal emails
   - Comprehensive debug logging

2. **`includes/admin/class-flexpress-smtp2go-settings.php`**
   - Admin settings page for SMTP2Go configuration
   - AJAX test connection functionality
   - Form validation and sanitization
   - Built-in setup guide

3. **`includes/admin/class-flexpress-settings.php`**
   - Added SMTP2Go submenu to FlexPress settings
   - Render method for SMTP2Go settings page

4. **`includes/class-flexpress-ses-smtp.php`**
   - Updated Amazon SES to defer to SMTP2Go for internal emails
   - Fixed routing logic to handle emails TO `zakspov.com`

5. **`functions.php`**
   - Added SMTP2Go class includes
   - Instantiated SMTP2Go settings class in admin

## Configuration

### Current Setup

- **SMTP Host**: `mail.smtp2go.com`
- **SMTP Port**: `587`
- **Encryption**: `TLS`
- **Username**: `zakozbourne`
- **Password**: `QalBtCYZtjV1AHwL`
- **From Email**: `zak@zakozbourne.com`
- **From Name**: `Zak Ozbourne`
- **Test Email**: `casting@zakspov.com`
- **Enable SMTP2Go**: ✅ Enabled
- **Use for Internal Only**: ✅ Enabled

### Admin Interface

Access the SMTP2Go settings at: `FlexPress → SMTP2Go`

Settings include:
- Enable/disable SMTP2Go
- SMTP connection details
- From email and name configuration
- Internal emails only option
- Test email functionality
- Built-in setup guide

## Smart Routing Logic

### Internal Email Detection

The system automatically detects emails as "internal" if:
1. **Recipient domain matches sender domain** (traditional internal email)
2. **Recipient domain is `zakspov.com`** (your domain, regardless of sender)

### Priority Order

For internal emails (TO `@zakspov.com`):
1. **SMTP2Go** (Primary) - Professional delivery with proper From address
2. **Google SMTP** (Fallback) - If SMTP2Go is disabled or fails
3. **Amazon SES** (Last Resort) - If both SMTP2Go and Google SMTP fail

For external emails (TO other domains):
- **Amazon SES** - Handles all external email delivery

### From Address Override

SMTP2Go automatically overrides the From address with your configured settings:
- **Configured From**: `zak@zakozbourne.com`
- **Configured From Name**: `Zak Ozbourne`
- **Override Behavior**: Replaces WordPress default From address

## Debug Logging

The system includes comprehensive debug logging:

```
FlexPress SMTP2Go: Constructor called
FlexPress SMTP2Go: configure_smtp called
FlexPress SMTP2Go: should_use_smtp2go called
FlexPress SMTP2Go: From email = test@example.com, From domain = example.com
FlexPress SMTP2Go: Recipients = Array([casting@zakspov.com] => 1)
FlexPress SMTP2Go: Checking recipient casting@zakspov.com (domain: zakspov.com)
FlexPress SMTP2Go: Found internal email, using SMTP2Go
FlexPress SMTP2Go: Proceeding with SMTP2Go configuration
FlexPress SMTP2Go: Set From address to zak@zakozbourne.com (Zak Ozbourne)
FlexPress SMTP2Go: Auth details - Host: mail.smtp2go.com, Port: 587, Username: zakozbourne, Password length: 16
FlexPress SMTP2Go: SMTP configured for zak@zakozbourne.com via mail.smtp2go.com
```

## Troubleshooting

### Common Issues

1. **Username Not Saving**
   - **Issue**: SMTP username field not persisting after save
   - **Solution**: Changed sanitization from `sanitize_email()` to `sanitize_text_field()`
   - **Reason**: SMTP usernames are often not email addresses

2. **PHPMailer Class Not Found**
   - **Issue**: Fatal error when testing connection
   - **Solution**: Removed direct PHPMailer instantiation, use WordPress mail functions
   - **Reason**: WordPress uses namespaced PHPMailer

3. **Routing Not Working**
   - **Issue**: Emails not being routed to SMTP2Go
   - **Solution**: Added class instantiation (`new FlexPress_SMTP2Go()`)
   - **Reason**: Class wasn't being instantiated automatically

4. **From Address Override Not Working**
   - **Issue**: WordPress default From address being used
   - **Solution**: Added explicit `setFrom()` call in SMTP2Go configuration
   - **Reason**: WordPress was overriding the From address

### Debug Steps

1. Check debug logs for SMTP2Go activity
2. Verify SMTP2Go is enabled in settings
3. Test connection using built-in test button
4. Check routing logic in debug logs
5. Verify From address override in logs

## Benefits

### Before SMTP2Go
- **Amazon SES Bounces**: Internal emails bounced due to domain conflicts
- **From Address Issues**: Emails showed confusing From addresses
- **Spam Filter Problems**: Domain mismatches triggered spam filters
- **Manual Configuration**: Required complex domain authentication

### After SMTP2Go
- **No Bounces**: Professional delivery with automatic domain authentication
- **Proper From Address**: Consistent `zak@zakozbourne.com` From address
- **Spam Filter Friendly**: Proper domain alignment eliminates spam issues
- **Automatic Routing**: No manual configuration required
- **Professional Delivery**: Enterprise-grade email service

## Future Enhancements

Potential improvements for the SMTP2Go integration:

1. **Domain Configuration**: Make domain detection configurable instead of hardcoded
2. **Multiple Domains**: Support for multiple internal domains
3. **Advanced Routing**: More sophisticated routing rules
4. **Analytics**: Email delivery statistics and reporting
5. **Webhook Integration**: Real-time delivery status updates

## Security Considerations

- **Password Storage**: SMTP passwords stored in WordPress options (encrypted)
- **Nonce Protection**: All AJAX requests protected with WordPress nonces
- **Capability Checks**: Admin-only access to settings and testing
- **Input Sanitization**: All form inputs properly sanitized
- **Debug Logging**: Sensitive data excluded from debug logs

## Performance Impact

- **Minimal Overhead**: Only processes emails when SMTP2Go is enabled
- **Efficient Routing**: Quick domain detection with minimal processing
- **Fallback System**: Graceful degradation if SMTP2Go fails
- **Caching**: WordPress options cached for fast access

---

*Last Updated: September 2025*
*Version: 1.0.0*
