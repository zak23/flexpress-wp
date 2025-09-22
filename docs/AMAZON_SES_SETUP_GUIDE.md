# Amazon SES Setup Guide for FlexPress

This guide will walk you through setting up Amazon Simple Email Service (SES) as your email deliverer for the FlexPress WordPress theme.

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [AWS SES Setup](#aws-ses-setup)
3. [FlexPress Configuration](#flexpress-configuration)
4. [Environment Variables (Optional)](#environment-variables-optional)
5. [Testing](#testing)
6. [Troubleshooting](#troubleshooting)
7. [Best Practices](#best-practices)

## Prerequisites

- AWS Account with access to Amazon SES
- Domain ownership verification capability
- FlexPress theme installed and activated
- WordPress admin access

## AWS SES Setup

### Step 1: Create AWS Account
1. Sign up for an AWS account at [aws.amazon.com](https://aws.amazon.com)
2. Navigate to the Amazon SES console
3. Select your preferred AWS region (recommended: `us-east-1`)

### Step 2: Verify Your Domain
1. In the SES console, go to **Verified identities**
2. Click **Create identity**
3. Select **Domain** and enter your domain name (e.g., `yourdomain.com`)
4. Choose **Use a TXT record** for verification
5. Add the provided TXT record to your domain's DNS settings
6. Wait for verification (can take up to 72 hours)

### Step 3: Create SMTP Credentials
1. In the SES console, go to **SMTP settings**
2. Click **Create SMTP credentials**
3. Enter an IAM user name (e.g., `flexpress-smtp-user`)
4. Download or copy the SMTP credentials:
   - **SMTP Username**: Your SMTP username
   - **SMTP Password**: Your SMTP password
   - **SMTP Host**: `email-smtp.[region].amazonaws.com`
   - **SMTP Port**: 587 (TLS) or 465 (SSL)

### Step 4: Request Production Access (Optional)
- By default, SES starts in sandbox mode (limited to verified emails)
- To send to any email address, request production access in the SES console
- This process requires approval and may take 24-48 hours

## FlexPress Configuration

### Step 1: Access SES Settings
1. Log into your WordPress admin dashboard
2. Navigate to **FlexPress → Amazon SES**
3. You'll see the SES configuration page

### Step 2: Configure Basic Settings
1. **Enable Amazon SES**: Check this box to activate SES
2. **AWS Region**: Select your SES region (e.g., `us-east-1`)
3. **SMTP Host**: Enter your SMTP host (auto-populated based on region)
4. **SMTP Port**: Choose 587 (TLS) or 465 (SSL)
5. **SMTP Encryption**: Select TLS or SSL
6. **SMTP Username**: Enter your SES SMTP username
7. **SMTP Password**: Enter your SES SMTP password
8. **From Email**: Enter your verified email address
9. **From Name**: Enter your site name or preferred sender name

### Step 3: Test Configuration
1. Enter a test email address in the **Test Email Address** field
2. Click **Send Test Email** to verify your configuration
3. Check the **Status** section for configuration validation

## Environment Variables (Optional)

For enhanced security, you can store SES credentials in environment variables instead of the database.

### Step 1: Update .env File
Add these variables to your `.env` file:

```env
# Amazon SES Configuration
SES_AWS_REGION=us-east-1
SES_SMTP_HOST=email-smtp.us-east-1.amazonaws.com
SES_SMTP_PORT=587
SES_SMTP_USERNAME=your_smtp_username
SES_SMTP_PASSWORD=your_smtp_password
SES_FROM_EMAIL=noreply@yourdomain.com
SES_FROM_NAME=Your Site Name
```

### Step 2: Enable Environment Variables
1. In the FlexPress SES settings, check **Use Environment Variables**
2. Save the settings
3. The system will now use environment variables instead of database values

## Testing

### Test Email Delivery
1. Use the **Send Test Email** button in the admin interface
2. Check your email inbox for the test message
3. Verify the sender information is correct

### Monitor Email Logs
1. The system automatically logs email events
2. Check the **SES Stats** section for delivery statistics
3. Review WordPress debug logs for any SMTP errors

### Test Different Email Types
Test various WordPress email functions:
- User registration emails
- Password reset emails
- Contact form submissions
- Admin notifications

## Troubleshooting

### Common Issues

#### "SMTP connection failed"
- Verify SMTP credentials are correct
- Check if your IP is not blocked by AWS
- Ensure SMTP port is not blocked by firewall
- Verify AWS region matches your SES setup

#### "Email not delivered"
- Check if recipient email is verified (in sandbox mode)
- Verify domain verification is complete
- Check AWS SES sending limits
- Review AWS CloudWatch logs for bounces/complaints

#### "Authentication failed"
- Double-check SMTP username and password
- Ensure credentials are for SMTP, not AWS console
- Verify IAM user has SES sending permissions

#### "From email not verified"
- Verify the from email address in AWS SES console
- Use only verified email addresses as sender
- Check domain verification status

### Debug Mode
Enable WordPress debug logging to see detailed SMTP information:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `/wp-content/debug.log` for FlexPress SES messages.

## Best Practices

### Security
- Use environment variables for production credentials
- Regularly rotate SMTP credentials
- Monitor AWS CloudTrail for unauthorized access
- Use IAM roles with minimal required permissions

### Deliverability
- Maintain good sender reputation
- Use consistent from names and addresses
- Implement proper SPF, DKIM, and DMARC records
- Monitor bounce and complaint rates

### Performance
- Use appropriate AWS regions for your audience
- Monitor SES sending quotas and limits
- Implement email queuing for high-volume sending
- Use SES sending statistics for optimization

### Monitoring
- Set up CloudWatch alarms for bounce rates
- Monitor email delivery statistics
- Track WordPress email logs
- Implement health checks for email functionality

## Advanced Configuration

### Custom SMTP Settings
For advanced users, you can modify the SMTP configuration by editing:
- `/wp-content/themes/flexpress/includes/class-flexpress-ses-smtp.php`

### Email Templates
Customize email templates by modifying:
- Contact Form 7 templates
- WordPress default email templates
- FlexPress custom email functions

### Integration with Other Services
The SES integration works seamlessly with:
- Contact Form 7
- WordPress user registration
- Password reset functionality
- Admin notifications
- Custom email functions

## Support

For additional support:
1. Check the FlexPress documentation
2. Review AWS SES documentation
3. Check WordPress debug logs
4. Contact your hosting provider for SMTP restrictions

## Changelog

### Version 1.0.0
- Initial SES integration
- SMTP configuration
- Environment variable support
- Email testing and monitoring
- Admin interface integration
