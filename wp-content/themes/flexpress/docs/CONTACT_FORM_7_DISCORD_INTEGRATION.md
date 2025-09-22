# Contact Form 7 Discord Integration

## Overview

FlexPress now includes comprehensive Discord notifications for all Contact Form 7 form submissions. This integration provides real-time notifications to your Discord server whenever forms are submitted, helping you stay on top of customer inquiries, casting applications, and support requests.

## Features

### 🎯 **Form-Specific Notifications**
- **Contact Forms** - General inquiries with sender details
- **Casting Applications** - Talent applications with age, experience, and social media
- **Support Requests** - Technical support with issue types and priority levels
- **General Forms** - Any other Contact Form 7 forms
- **Form Failures** - Alerts when forms fail to send properly

### 🎨 **Rich Discord Embeds**
- **Color-coded** notifications (Blue for contact, Orange for casting, Red for support)
- **Structured data** with relevant form fields
- **Timestamps** for all submissions
- **Site branding** with logo and site name

### ⚙️ **Flexible Configuration**
- **Separate webhooks** for different notification types
- **Individual toggles** for each form type
- **Failure notifications** for debugging
- **Admin-friendly** settings interface

## Setup Instructions

### 1. Configure Discord Webhooks

1. **Go to your Discord server** → Server Settings → Integrations
2. **Click "Create Webhook"** for each channel you want to use
3. **Recommended channels:**
   - `#contact-forms` - For general contact inquiries
   - `#casting-applications` - For talent applications
   - `#support-requests` - For technical support
   - `#form-failures` - For debugging form issues

### 2. Configure FlexPress Discord Settings

1. **Navigate to** FlexPress → Discord Settings
2. **Set webhook URLs:**
   - **Default Webhook** - Used for all notifications if specific webhooks aren't set
   - **Contact Forms Webhook** - Dedicated channel for contact form submissions
   - **Financial Webhook** - For payment notifications (existing)

3. **Enable notifications:**
   - ✅ **Contact Forms** - General contact inquiries
   - ✅ **Casting Applications** - Talent applications
   - ✅ **Support Requests** - Technical support requests
   - ✅ **General Forms** - Other Contact Form 7 forms
   - ⚠️ **Form Submission Failures** - Debugging (optional)

### 3. Test the Integration

1. **Use the "Test Connection" button** in Discord Settings
2. **Submit test forms** on your contact, casting, and support pages
3. **Check Discord channels** for notifications
4. **Verify formatting** and data accuracy

## Notification Examples

### Contact Form Submission
```
📧 New contact form submission!

**📧 Contact Form Submission**
New contact form submission received from Your Site Name

**Name:** John Doe
**Email:** john@example.com
**Subject:** General Inquiry
**Message:** I have a question about your services...
**Submitted:** 2024-01-15 14:30:25
```

### Casting Application
```
🌟 New casting application received!

**🌟 Casting Application**
New casting form submission received from Your Site Name

**Name:** Jane Smith
**Email:** jane@example.com
**Age:** 25
**Phone:** +1 (555) 123-4567
**Message:** I'm interested in joining your cast...
**Submitted:** 2024-01-15 14:30:25
```

### Support Request
```
🆘 New support request submitted!

**🆘 Support Request**
New support form submission received from Your Site Name

**Name:** Bob Johnson
**Email:** bob@example.com
**Issue Type:** Technical Support
**Priority:** High
**Message:** I'm having trouble accessing my account...
**Submitted:** 2024-01-15 14:30:25
```

## Technical Details

### Integration Points
- **Contact Form 7 Hooks:** `wpcf7_mail_sent` and `wpcf7_mail_failed`
- **FlexPress Discord System:** Uses existing `FlexPress_Discord_Notifications` class
- **Form Detection:** Automatically detects form type based on FlexPress form IDs

### Form Type Detection
The system automatically detects form types by comparing Contact Form 7 form IDs with stored FlexPress form IDs:
- `flexpress_contact_form_id` → Contact form
- `flexpress_casting_form_id` → Casting application
- `flexpress_support_form_id` → Support request
- Other IDs → General form

### Data Handling
- **Field Extraction:** Automatically extracts relevant form fields
- **Message Truncation:** Long messages are truncated to 1000 characters
- **Sanitization:** All data is properly sanitized before sending
- **Error Handling:** Graceful fallbacks for missing data

## Troubleshooting

### Common Issues

1. **No notifications received**
   - Check webhook URLs are correct
   - Verify Discord webhook is active
   - Check notification toggles are enabled
   - Review WordPress debug logs

2. **Incomplete form data**
   - Ensure Contact Form 7 forms have proper field names
   - Check form field mapping in templates
   - Verify form submission is successful

3. **Wrong channel notifications**
   - Check webhook URL configuration
   - Verify channel type mapping
   - Test with different form types

### Debug Information
- **WordPress Debug Log:** Check `wp-content/debug.log` for Discord errors
- **Contact Form 7 Logs:** Check CF7 submission logs
- **Discord Webhook Logs:** Check Discord server audit logs

## Advanced Configuration

### Custom Webhook Channels
You can set up different Discord channels for different notification types:

```php
// In Discord Settings
Default Webhook: https://discord.com/api/webhooks/.../general
Contact Forms Webhook: https://discord.com/api/webhooks/.../contact-forms
Financial Webhook: https://discord.com/api/webhooks/.../financial
```

### Custom Notification Content
The system uses different emojis and colors for each form type:
- **Contact:** 📧 Blue (#0099ff)
- **Casting:** 🌟 Orange (#ff6b35)
- **Support:** 🆘 Red (#ff0000)
- **General:** 📝 Green (#00ff00)

### Role Mentions
You can configure Discord webhooks to mention specific roles:
- Set up `@SupportTeam` mentions for support requests
- Use `@CastingTeam` for casting applications
- Configure `@AdminTeam` for form failures

## Security Considerations

- **Webhook URLs:** Keep Discord webhook URLs secure and private
- **Data Privacy:** Form data is sent to Discord - ensure compliance with privacy policies
- **Access Control:** Limit Discord channel access to authorized team members
- **Logging:** Monitor Discord audit logs for unauthorized access

## Future Enhancements

- **Custom field mapping** for specific form requirements
- **Conditional notifications** based on form field values
- **Slack integration** as alternative to Discord
- **Email fallback** when Discord is unavailable
- **Analytics dashboard** for form submission trends

## Support

For technical support with this integration:
1. Check the troubleshooting section above
2. Review WordPress debug logs
3. Test with the Discord connection test tool
4. Contact FlexPress support if issues persist
