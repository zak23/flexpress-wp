# Single Model Page - Enhanced Layout Features

## Overview
The single model page (`single-model.php`) has been enhanced with a modern, multi-section layout inspired by premium adult content sites.

## New Layout Sections

### 1. Hero Landscape Image Section
- **Purpose**: Wide landscape banner image displayed prominently below the navigation
- **ACF Field**: `model_hero_image` (Image field)
- **Fallback**: If no hero image is set, displays a simple header with the model name
- **Design**: Full-width background image with gradient overlay and model name positioned at bottom-left

### 2. Half-Half Image and Text Section
- **Purpose**: Split layout showcasing model profile image and detailed information
- **Left Column**: Profile image (uses `model_profile_image` ACF field, falls back to featured image)
- **Right Column**: 
  - About section (model_about field)
  - Additional content (WordPress content editor)
  - Model details (birthdate, height, measurements)
  - Social media links (Instagram, Twitter, OnlyFans, Website)

### 3. Latest Scene Hero Section
- **Purpose**: Highlights the model's most recent episode in a prominent hero-style layout
- **Features**:
  - Large episode thumbnail with hover effects
  - Episode title, duration, release date
  - Episode excerpt (if available)
  - "Watch Now" button
  - Dark gradient background for visual contrast

### 4. Episodes Grid Section
- **Purpose**: Displays all episodes featuring the model in a responsive grid
- **Features**:
  - Uses the same episode card template as the episodes archive page
  - Responsive grid (4 columns on large screens, adapts down to 1 column on mobile)
  - Shows up to 12 episodes initially
  - Episodes filtered by release date (only shows released episodes)
  - Sorted by release date (newest first)

## ACF Field Organization

The model fields are now organized into **4 logical tabs** for better admin experience:

### 📝 **Basic Information Tab**
Essential model information and biography:
- **About**: Biography text (required, 6 rows)
- **Gender**: Select dropdown (Female, Male, Trans, Non-Binary, Other)
- **Date of Birth**: Date picker
- **Height**: Text field (e.g., 5'8")
- **Measurements**: Text field (e.g., 34-26-36)

### 🖼️ **Images Tab**
Visual content management:
- **Hero Landscape Image**: Wide banner for hero section (recommended: 1920x600px)
- **Profile Image**: Dedicated image for half-half section

### 📱 **Social Media Tab**
All social media and website links:
- **Instagram**: Instagram profile URL
- **Twitter/X**: Twitter/X profile URL
- **TikTok**: TikTok profile URL
- **OnlyFans**: OnlyFans profile URL
- **Personal Website**: Website URL
- **Website Title**: Custom title for website link (conditional, shows when website URL is filled)

### ⚙️ **Display Settings Tab**
Homepage and featured controls:
- **Show on Homepage**: Toggle homepage visibility (default: Yes)
- **Featured Model**: Mark model as featured for special highlighting

## Field Enhancements
- **Helpful Placeholders**: All URL fields include example URLs for guidance
- **Better Sizing**: Responsive field widths for optimal admin layout
- **Improved Instructions**: Clearer field descriptions and recommendations
- **Conditional Logic**: Website title only appears when website URL is provided

## CSS Classes Added

### Hero Section
- `.model-hero-section` - Main hero container
- `.model-hero-image` - Background image wrapper
- `.model-hero-overlay` - Gradient overlay
- `.model-hero-title` - Model name styling
- `.model-header-fallback` - Fallback header when no hero image

### Profile Section
- `.model-profile-section` - Main profile container
- `.model-content` - Content wrapper
- `.model-bio` - Biography text styling
- `.model-details` - Details grid styling

### Latest Scene
- `.model-latest-scene` - Latest scene hero container
- `.latest-scene-video` - Video thumbnail wrapper
- `.latest-scene-overlay` - Hover overlay for play button
- `.latest-scene-info` - Episode information styling
- `.latest-scene-title` - Episode title styling

### Episodes Grid
- `.model-episodes` - Episodes section container
- Uses existing episode card styles from archive page

## Responsive Design
- **Desktop (1200px+)**: Full layout with 4-column episode grid
- **Tablet (768px-1199px)**: Adapted layout with 3-column episode grid
- **Mobile (767px and below)**: Stacked layout with single-column episode grid
- **Hero image heights**: 400px (desktop) → 300px (tablet) → 250px (mobile)

## Integration Notes
- Maintains compatibility with existing model functionality
- Uses established episode filtering and BunnyCDN thumbnail systems
- Follows FlexPress theme color variables and styling patterns
- Responsive grid uses Bootstrap classes for consistency

## Enhanced Admin Experience

### 🎯 **Organized Interface**
The admin interface now features **4 logical tabs** that group related fields:
- **Basic Information**: Core model details and biography
- **Images**: Hero image and profile image management  
- **Social Media**: All social platforms and website links
- **Display Settings**: Homepage and featured model controls

### ✨ **User-Friendly Features**
1. **Tabbed Organization**: Clean, logical grouping reduces clutter
2. **Helpful Placeholders**: Example URLs guide editors (e.g., "https://instagram.com/username")
3. **Responsive Sizing**: Fields intelligently sized for optimal workflow
4. **Clear Instructions**: Enhanced field descriptions with recommendations
5. **Conditional Logic**: Website title field only appears when needed
6. **Visual Guidance**: Image size recommendations (e.g., "1920x600px for hero")

### 🚀 **Streamlined Workflow**
Editors can efficiently:
- Navigate between organized sections
- Upload images with size guidance
- Add social media links with URL examples
- Control display settings in dedicated tab
- See conditional fields only when relevant

## Enhanced Social Media Integration
The social media section now supports:
- **Instagram**: Existing field with Instagram icon
- **Twitter/X**: Updated icon and label for X (formerly Twitter)
- **TikTok**: New field with TikTok icon
- **OnlyFans**: Existing field with heart icon
- **Website**: Enhanced with custom title support (shows website title instead of generic "Website")

## Performance Considerations
- Episode queries are optimized with proper meta_query filters
- Images use WordPress responsive image sizes
- CSS uses existing theme variables for consistency
- Hover effects use CSS transforms for smooth performance 