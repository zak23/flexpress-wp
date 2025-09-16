# Legal Pages Setup Documentation

## Overview

The FlexPress theme includes a comprehensive legal pages creation system that automatically creates all required legal pages and organizes them in a dedicated Legal menu. This system is essential for compliance with adult content regulations and industry standards.

## Legal Pages Created

The system creates the following 5 legal pages:

1. **Privacy Policy** - Data collection and privacy information
2. **Customer Terms & Conditions** - Service terms and membership conditions
3. **2257 Compliance** - US regulation compliance information
4. **Anti-Slavery and Human Trafficking Policy** - Modern slavery prevention
5. **Content Removal** - Content removal request form and process

## Page Templates Used

Each page uses a specialized template with built-in functionality:

- `page-templates/privacy.php`
- `page-templates/terms.php`
- `page-templates/2257-compliance.php`
- `page-templates/anti-slavery.php`
- `page-templates/content-removal.php`

## How to Use

### Method 1: Admin Interface (Recommended)

1. Go to **WordPress Admin** → **FlexPress Settings** → **General** tab
2. Look for the "Pages & Menus" section
3. Click the **"Create Legal Pages & Menu"** button
4. The system will:
   - Create all 5 legal pages (if they don't exist)
   - Assign proper page templates
   - Create a "Legal" menu
   - Add all pages to the Legal menu
   - Assign the menu to the footer legal menu location

### Method 2: Programmatic Usage

```php
// Create all legal pages and menu
$created_pages = flexpress_create_legal_pages_and_menu();

// Simple helper function
$created_pages = flexpress_setup_legal_pages();
```

### Method 3: Automatic Creation

The system automatically creates legal pages when:
- Theme is activated (if less than 3 legal pages exist)
- `after_setup_theme` hook runs

## Menu Integration

The legal pages are automatically organized in a "Legal" menu that is assigned to:
- `footer-legal-menu` location (used in footer.php)
- `legal-menu` location (for backwards compatibility)

## Features

### ACF Integration

Each legal page supports advanced customization via ACF fields:
- **Contact Form Integration**: Add Contact Form 7 or WPForms
- **Custom Contact Form Title**: Customize form section heading
- **Last Updated Date Control**: Show/hide and customize last updated date
- **Additional Content**: Extra content sections

### Template Features

All legal page templates include:
- Responsive Bootstrap-based layout
- Consistent styling with theme
- Last updated date display
- Contact form integration
- Additional content areas
- Proper semantic markup

### Content Removal Page

The Content Removal page includes:
- Built-in content removal form
- Identity verification fields
- DMCA compliance structure
- Form validation
- Email submission to admin

## Customization

### Editing Page Content

1. Go to **WordPress Admin** → **Pages**
2. Find and edit the legal page you want to modify
3. Update content using the WordPress editor
4. Configure ACF fields in the sidebar for additional options

### Menu Customization

1. Go to **WordPress Admin** → **Appearance** → **Menus**
2. Find the "Legal" menu
3. Add, remove, or reorder menu items as needed
4. Assign to different menu locations if required

### Template Customization

The page templates can be customized by editing the files in `page-templates/`:
- Copy the template to your child theme (if using one)
- Modify HTML structure and styling as needed
- Templates use Bootstrap 5 classes for styling

## Compliance Notes

### Adult Content Industry

The legal pages are specifically designed for adult content websites:
- **2257 Compliance**: Meets US federal requirements
- **Age Verification**: Includes proper disclaimers
- **Content Removal**: DMCA-compliant removal process
- **Privacy Policy**: Covers adult content data handling
- **Terms**: Addresses membership and content access

### International Compliance

For international compliance, consider:
- GDPR compliance (EU) - covered in Privacy Policy template
- Age verification requirements by jurisdiction
- Content licensing and performer consent
- Local advertising and payment regulations

## Troubleshooting

### Pages Not Created

If pages aren't created automatically:
1. Check WordPress error logs
2. Verify file permissions on page-templates directory
3. Ensure ACF plugin is active
4. Try manual creation via admin interface

### Menu Not Appearing

If the legal menu doesn't appear in footer:
1. Check **Appearance** → **Menus** → **Manage Locations**
2. Ensure "Legal" menu is assigned to "Footer Legal Menu"
3. Clear any caching plugins
4. Check footer.php for proper menu location call

### Template Not Applied

If custom templates aren't being used:
1. Verify template files exist in `page-templates/` directory
2. Check page edit screen for "Page Attributes" → "Template" setting
3. Clear any caching that might affect template selection

## Development Notes

### Function Reference

- `flexpress_create_legal_pages_and_menu()` - Main creation function
- `flexpress_setup_legal_pages()` - Simple helper wrapper
- `flexpress_auto_create_legal_pages()` - Automatic creation checker
- `flexpress_display_legal_contact_form()` - Render contact forms
- `flexpress_display_legal_additional_content()` - Render additional content
- `flexpress_get_legal_last_updated_date()` - Get formatted last updated date
- `flexpress_should_show_legal_last_updated()` - Check if date should display

### Hooks Used

- `after_setup_theme` - Auto-creation trigger
- `admin_post_create_legal_pages_menu` - Admin form submission
- `flexpress_settings_sections_general` - Admin interface integration

### Database Changes

The system creates:
- WordPress pages with proper meta data
- Navigation menu and menu items
- Theme customizer settings for menu locations

## Security Considerations

- All form submissions are nonce-protected
- User capability checks for admin functions
- Proper input sanitization and validation
- SQL injection prevention via WordPress APIs

## Support

For issues with the legal pages system:
1. Check WordPress debug logs
2. Verify all dependencies (ACF, contact form plugins)
3. Test with default WordPress theme to isolate theme issues
4. Contact FlexPress theme support with specific error messages

## Dynamic Content Generation

### Overview

The FlexPress theme automatically generates comprehensive content for Privacy Policy, Terms & Conditions, 2257 Compliance, Customer Terms & Conditions, Anti-Slavery & Human Trafficking Policy, and Content Removal using your site's contact information and business details. This ensures your legal pages are always up-to-date with your current settings.

### Dynamic Variables Used

The system automatically pulls information from:

#### Site Information
- **Site Name**: Retrieved from WordPress settings (`get_bloginfo('name')`)
- **Site URL**: Retrieved from WordPress settings (`get_bloginfo('url')`)
- **Site Domain**: Extracted from the site URL

#### Contact Information (from FlexPress Contact & Social Settings)
- **Contact Email**: Primary contact email address
- **Support Email**: Customer support email
- **Billing Email**: Billing and payment inquiries email
- **Fallback Emails**: Auto-generated based on domain if not set (e.g., `privacy@yourdomain.com`)

#### Business Information (from FlexPress Contact & Social Settings)
- **Parent Company**: Business or company name
- **Business Number**: ABN, TIN, EIN, or other business registration number
- **Business Address**: Complete mailing address

### Content Templates

#### Privacy Policy Content Includes:
- Information collection practices
- Data usage and processing
- Third-party sharing policies
- Security measures
- Data retention policies
- User privacy rights
- Cookie and tracking information
- Age restrictions and compliance
- International data transfer information
- Contact information with dynamic email addresses

#### Terms & Conditions Content Includes:
- Age verification requirements
- Account registration and security
- Membership and billing terms
- Content and intellectual property rights
- User conduct guidelines
- Privacy and data protection references
- Service disclaimers and limitations
- Termination policies
- Governing law information
- Contact information with dynamic email addresses

#### 2257 Compliance Content Includes:
- Federal labeling and record-keeping law compliance statement
- Age verification requirements (18 U.S.C. §2257)
- Records custodian information with dynamic contact details
- Effective date with current timestamp
- Professional compliance language for adult content industry

#### Customer Terms & Conditions Content Includes:
- Comprehensive billing and payment terms
- Verotel payment processor integration
- Age verification and access restrictions
- Subscription and recurring billing details
- Cancellation and refund policies
- Content monitoring and compliance procedures
- Chargeback and dispute handling
- Dynamic business registration details

#### Anti-Slavery & Human Trafficking Policy Content Includes:
- Modern slavery prevention commitments
- Zero-tolerance policy statements
- Supply chain compliance requirements
- Risk assessment and due diligence procedures
- Code of conduct enforcement
- Reporting and remediation processes
- Current date for policy updates

#### Content Removal Page Content Includes:
- Professional content removal procedures
- Confidentiality and privacy protection
- Non-consensual content and illegal material definitions
- Response timeframes (24 hours for urgent cases, 7 business days for others)
- Legal compliance team review process
- Dynamic contact emails for copyright and support
- Clear guidelines for different types of violations

### Setting Up Dynamic Content

#### 1. Configure Contact Information
1. Go to **WordPress Admin** → **FlexPress Settings** → **Contact & Social**
2. Fill in your business information:
   - Parent Company
   - Business Number (ABN/TIN/EIN)
   - Business Address
3. Set up contact emails:
   - Support Email
   - General Contact Email
   - Billing Email
4. Save your settings

#### 2. Create or Update Legal Pages
The system will automatically use dynamic content when:
- Creating new Privacy Policy pages
- Creating new Terms & Conditions pages
- Creating new 2257 Compliance pages
- Creating new Customer Terms & Conditions pages
- Creating new Anti-Slavery & Human Trafficking Policy pages
- Creating new Content Removal pages
- Using the "Create Legal Pages & Menu" button in FlexPress Settings

#### 3. Regenerate Existing Content
If you already have legal pages and want to update them with dynamic content:
1. Go to **FlexPress Settings** → **Contact & Social**
2. Click **"Regenerate Legal Pages with Updated Info"**
3. This will overwrite existing content with the new dynamic version

### Functions Reference

#### Content Generation Functions
```php
// Generate privacy policy content
$privacy_content = flexpress_generate_privacy_policy_content();

// Generate terms and conditions content
$terms_content = flexpress_generate_terms_conditions_content();

// Generate 2257 compliance content
$compliance_content = flexpress_generate_2257_compliance_content();

// Generate customer terms content
$customer_terms_content = flexpress_generate_customer_terms_content();

// Generate anti-slavery policy content
$anti_slavery_content = flexpress_generate_anti_slavery_content();

// Generate content removal content
$content_removal_content = flexpress_generate_content_removal_content();

// Regenerate existing pages
flexpress_regenerate_legal_page_content('both'); // or 'privacy', 'terms', '2257', 'customer_terms', 'anti_slavery', 'content_removal'
```

#### Helper Functions Used
```php
// Get contact information
flexpress_get_contact_email('contact'); // or 'support', 'billing'
flexpress_get_parent_company();
flexpress_get_business_number();
flexpress_get_business_address();

// WordPress functions
get_bloginfo('name');
get_bloginfo('url');
```

### Customization

#### Modifying Content Templates
To customize the generated content, edit these functions in `functions.php`:
- `flexpress_generate_privacy_policy_content()`
- `flexpress_generate_terms_conditions_content()`
- `flexpress_generate_2257_compliance_content()`
- `flexpress_generate_customer_terms_content()`
- `flexpress_generate_anti_slavery_content()`
- `flexpress_generate_content_removal_content()`

#### Adding New Variables
To add new dynamic variables:
1. Add the variable to your content generation function
2. Retrieve the value using appropriate helper functions
3. Include the variable in your content template

#### Example: Adding Phone Number
```php
// In the content generation function
$phone_number = get_option('my_phone_setting', '');
if (!empty($phone_number)) {
    $content .= "<li><strong>Phone:</strong> " . esc_html($phone_number) . "</li>";
}
```

### Best Practices

#### Before Regenerating Content
- **Backup existing content** if you've made custom modifications
- **Review generated content** to ensure it meets your needs
- **Test all contact links** to verify they work correctly

#### Content Management
- **Update contact settings first** before regenerating legal pages
- **Use the regeneration feature** whenever you change business information
- **Review legal content regularly** to ensure compliance with current laws

#### Compliance Considerations
- **Verify accuracy** of all generated content
- **Add jurisdiction-specific clauses** if needed
- **Consult legal professionals** for complex requirements
- **Update content** when laws or regulations change

### Troubleshooting

#### Content Not Updating
1. Check that contact information is properly saved in FlexPress Settings
2. Verify page titles match exactly ("Privacy Policy", "Customer Terms & Conditions", "2257 Compliance", "Anti-Slavery and Human Trafficking Policy", "Content Removal")
3. Check WordPress error logs for any generation errors
4. Ensure user has sufficient permissions (`manage_options`)

#### Missing Information
- Empty fields will use sensible defaults (e.g., `support@yourdomain.com`)
- Business information sections are hidden if no data is available
- Contact sections adapt based on available email addresses

#### Customization Lost
- Regeneration overwrites existing content completely
- Save custom content separately before regenerating
- Consider creating child theme overrides for heavily customized content

### Security Notes

- All dynamic content is properly escaped using WordPress functions
- Email addresses are validated before inclusion
- User input is sanitized through WordPress sanitization functions
- Nonce verification protects regeneration actions
- Only administrators can regenerate content 