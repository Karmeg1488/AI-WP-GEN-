# AI WP GEN

**Version:** 1.6.10  
**Author:** Stanislav Perepelytsia  
**Stable tag:** 1.6.10  
**Tested up to:** 6.8  
**License:** GPLv2 or later

== Short Description ==

AI WP GEN is a WordPress plugin for automatic generation of authors, categories, articles, images, site title, tagline, logo, favicon, and static pages ("Contact", "About Us") using OpenAI.

---

## Description

AI WP GEN is a WordPress plugin for automatic generation of authors, categories, articles, images, site title, tagline, logo, favicon, and static pages ("Contact", "About Us") using OpenAI.  
All content is generated in the language you select in the plugin settings.

---

## Features

- Generate site title and tagline in your preferred language
- Generate and set site logo (via OpenAI image generation)
- Use logo as favicon or generate favicon via OpenAI
- Generate authors, categories, and articles automatically
- Generate images for posts and assign as featured images
- Generate comprehensive "Contact" and "About Us" pages with rich content
- Generate complete homepages with Hero section, About us, blog posts, and testimonials
- Generate custom base.css stylesheet based on styling preferences
- **🎭 NEW v1.6: Generate complete custom WordPress themes** - Full theme creation with unique styles
  - Complete WordPress theme structure (header, footer, single, page, archive templates)
  - Custom CSS generation (1400+ lines) per theme
  - Automatic theme screenshot and preview images
  - Responsive design included
  - Mobile-friendly templates
  - All themes include functions.php with WordPress hooks and widgets
- All content adapts to your selected language
- **Supported languages:** English, Polish, German, Hungarian, Ukrainian, Turkish, Italian, Czech, French, Dutch

---

## Installation

1. Upload the plugin to your `/wp-content/plugins/` directory.
2. Activate the plugin via the WordPress admin panel.
3. Go to **AI WP GEN** in the admin menu.
4. Enter your OpenAI API key in the plugin settings.

---

## License

This plugin is licensed under the GPLv2 or later.  
You can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation.

---

## Changelog

### 1.6.0 - MAJOR: Theme Generation
* **🎭 NEW FEATURE: Complete WordPress Theme Generation** - Create full custom WordPress themes with unique styles
  * Generate complete WordPress theme structure with all necessary files
  * Custom CSS generation (1400+ lines per theme) based on OpenAI
  * Automatic generation of theme files: header.php, footer.php, index.php, single.php, page.php, archive.php, sidebar.php
  * Responsive design templates included in all generated themes
  * Unique functions.php with WordPress hooks, widgets, and theme support
* **ENHANCEMENT: Theme Screenshot & Images** - Automatically generate preview images
  * Professional theme screenshot for WordPress theme browser
  * Generate multiple high-quality images for theme sections (hero, feature, content)
  * All images optimized for web (1920x1080px)
* **ENHANCEMENT: Custom CSS Assets** - Additional styling capabilities
  * Custom CSS files with advanced animations and effects (800+ lines)
  * Gradient generators and modern color schemes
  * Mobile-responsive styles included
  * CSS variables/custom properties for easy customization
* **UI IMPROVEMENT: New Theme Generation Panel** - User-friendly theme creation
  * Theme name input
  * Optional theme description
  * Custom design prompt (optional) for personalized themes
  * Real-time generation with progress indicators
  * Direct link to activate theme after generation
* **TECHNICAL**: Full theme generation pipeline
  * Automatic directory creation and management
  * File write validation
  * Image download and optimization
  * Error logging for all generation steps
* **SUPPORTED LANGUAGES**: All 10 languages supported for theme-specific content
  * Themes adapt to selected language
  * Localized hero sections and content descriptions

### 1.5.58
* **CRITICAL: Comprehensive Error Logging** - Added detailed logging for all OpenAI API calls
  * All API failures now logged to wp-content/debug.log
  * Image generation errors now tracked and logged
  * Content generation failures now logged with specific details
  * HTTP status codes and API error messages captured
* **ENHANCEMENT: Better Error Handling** - Improved error detection and reporting
  * Text generation (articles, pages) failures now logged
  * Image generation failures now logged with context
  * API timeouts and connection errors now visible in debug logs
  * Prevents silent failures in content generation
* **DEBUGGING IMPROVEMENTS**: Easier troubleshooting of OpenAI API issues
  * Users can now view detailed error messages in wp-content/debug.log
  * HTTP status codes and full API responses logged for debugging
  * Helpful error messages for common issues (invalid API key, timeouts, etc.)

### 1.5.57
* **BUGFIX: Testimonials Generation** - Fixed syntax error in testimonials prompt with incorrect quote marks
* **CRITICAL: Plugin Activation** - Fixed fatal error preventing plugin activation
* All testimonials generation functionality now works correctly

### 1.5.56
* **MAJOR: Enhanced Content Generation** - Significantly increased content volume for all generated pages and articles
  * Articles now generate 800-1200 words (previously 200-300 words)
  * Contact pages now generate 600+ words with detailed sections
  * About Us pages now generate 800+ words with 10 comprehensive sections
  * Homepage About sections now 400-600 words with multiple paragraphs
  * CSS stylesheets now generate 500+ lines of comprehensive code
* **ENHANCEMENT: AI Response Size** - Increased max_tokens from 250 to 1500 for richer content
* **CRITICAL: Full Page Title Localization** - All page titles now display in selected language
  * Contact → Kontakt, Contacto, Contatti, etc.
  * About Us → O nas, Über uns, Chi siamo, etc.
  * Home, Privacy Policy, Terms & Conditions, and Cookie Policy titles properly localized
  * Support for all 10 languages: English, Polish, German, Hungarian, Ukrainian, Turkish, Italian, Czech, French, Dutch
* **ENHANCEMENT: Improved Image Generation Prompts** - More detailed and specific prompts for higher quality generated images
  * Professional photography standards in all image generation requests
  * Better composition and detail specifications
  * Editorial and cinematic quality requirements
* **ENHANCEMENT: Better Homepage Structure** - Enhanced hero section descriptions and About section content
  * Hero descriptions now 4-5 detailed sentences (previously 2-3)
  * About sections now comprehensive with specific details
  * Testimonials now 3-4 sentences with concrete benefits

### 1.5.55
* **BUGFIX: Homepage Image Generation**: Improved image generation with better error handling and more specific, detailed prompts
* **ENHANCEMENT: Testimonials Section**: Enhanced testimonials generation with better JSON validation and fallback parsing
* **CRITICAL: Policy Pages Enhancement**: Completely rewritten policy generation with comprehensive, detailed content
* **Privacy Policy now includes**: 12 detailed sections including GDPR compliance, data retention, user rights, security measures
* **Terms & Conditions now includes**: 13 detailed sections including liability limitations, intellectual property, third-party links
* **Cookie Policy now includes**: 14 detailed sections with GDPR compliance, detailed cookie types, consent management
* **All policy pages**: Now generate with 2-4 paragraphs per section minimum for comprehensive coverage
* **Image Enhancement**: Improved prompts for hero section (cinematic, 4K, professional lighting)
* **Image Enhancement**: Improved prompts for about section (team collaboration, modern workplace)
* **Testimonials Fix**: Better JSON extraction with regex fallback for reliable parsing
* **Error Logging**: Added error logging for image generation debugging
* **Fallback Content**: Policy pages now have fallback text if generation fails

### 1.5.54
* **Policy Pages Generation**: NEW! Automatically generate Privacy Policy, Terms & Conditions, and Cookie Policy pages
* Policies are generated based on user's selected language (10+ languages supported)
* Privacy Policy includes: data collection, usage, cookies, third-party services, user rights, data retention
* Terms & Conditions includes: terms of use, liability limitations, intellectual property, user responsibilities
* Cookie Policy includes: cookie types, consent management, GDPR compliance, user rights
* Policies use site name, URL, and admin email in generated content
* All policy pages are GDPR-compliant and professionally written
* Automatic creation/update of policy pages as WordPress pages
* Add "📋 Policy Pages" button in Static Pages section of admin panel

### 1.5.53
* **Enhanced Homepage - Custom Prompt Integration**: Hero section now uses custom site prompt for content generation
* **Improved Hero Section Layout**: Hero section now displays with text on the left and image on the right (side-by-side)
* **Smart Image Generation**: Both hero and about section images are now generated based on custom prompt context
* **Enhanced Hero Content**: Hero section now includes headline, subheading, detailed description, and CTA all generated from custom prompt
* **Improved About Section**: Enhanced typography and spacing with 40px heading font size and 2x line-height for better readability
* **Professional Blog Section**: Updated blog cards with improved shadows, spacing, and typography
* **Enhanced Testimonials**: Improved testimonial cards with better glassmorphism effect and typography
* **Responsive Side-by-Side Layout**: Hero and About sections now use responsive grid for perfect image-text balance
* All homepage images now have consistent square dimensions (500x500px) for better visual harmony

### 1.5.52
* **Custom CSS Generation**: NEW! Generate a custom base.css file based on styling preferences
* Add "CSS Style Prompt" field in settings for describing website styling (colors, tones, fonts, layout, theme)
* AI-generated CSS includes CSS variables, responsive design, and professional styling
* Generated base.css is automatically saved to uploads/aicg-styles/ directory
* CSS file is automatically enqueued and loaded on the frontend
* Supports dark/light themes, modern/classic designs, and custom color schemes
* Professional stylesheet generation respects all styling preferences

### 1.5.51
* **Homepage Generation**: NEW! Generate complete homepages with multiple sections
* Hero section generation with compelling headline, subheading, and CTA button
* Hero section includes AI-generated professional background image
* About us section with company description and professional image
* Automatic display of latest blog posts (3 posts with featured images)
* Generation of 3 customer testimonials with star ratings and names
* Professional HTML/CSS styling with gradient backgrounds and responsive grid layouts
* Homepage respects user's language selection and site name
* Automatically creates/updates home page and sets it as front page
* All homepage content generated using OpenAI with DALL-E for images

### 1.5.50
* Site Name now syncs with WordPress site title (blogname) when saved
* Site Name is displayed in browser tabs and as the main website title
* Site Name appears in page headers and throughout WordPress
* Added informative description explaining Site Name's role in the admin panel
* Site Name is now the central branding element for the entire website
* Improved consistency between plugin settings and WordPress settings

### 1.5.49
* Added French and Dutch language support
* Significantly expanded Contact page generation with comprehensive content sections
* Significantly expanded About Us page generation with detailed company information
* Contact pages now include multiple contact methods, social media, FAQ section
* About pages now include mission, vision, values, team, achievements, and future goals
* All page content is now more detailed and professionally structured
* Support for 10 languages now available (added French and Dutch)

### 1.5.48
* Added "Site Name" field in plugin settings for custom site branding
* Site Name is now used throughout content generation (titles, logos, pages)
* Site Name field substitutes WordPress blogname when generating content
* Custom site name applies to article generation, logo creation, and page generation
* All content and branding now respects user's site name settings

### 1.5.47
* Changed Site topic input to textarea for entering longer custom prompts
* Added functional custom AI prompt support - now influences article generation
* Custom prompt is used as context when generating article titles and content
* Default prompts are used if custom prompt field is empty
* Improved UI with description and proper textarea styling
* Articles now respect user's site topic settings for more relevant content

### 1.5.46
* Fixed critical language selection bug - plugin now respects user's selected language in settings
* Added support for Polish, Hungarian, and Ukrainian languages
* Refactored language handling: uses saved plugin language instead of WordPress admin locale
* Created centralized get_language_name_from_code() function for consistent language mapping
* Articles, pages, and branding now generate in the selected language

### 1.5.45
* Plugin renamed to AI WP GEN for improved branding
* Updated admin menu and page titles
* Maintained all security improvements and functionality

### 1.5.44
* Added user permission checks (manage_options) to all AJAX handlers for improved security
* Improved category sanitization with sanitize_text_field() before database insertion
* Deprecated aicg_openai_completion_request in favor of aicg_openai_chat_request
* Enhanced security posture with additional capability checks

### 1.5.43
* Fixed version synchronization across files
* Fixed critical API key retrieval bug in openai-helper.php
* Removed deprecated aicg_openai_completion_request function
* Added user permission checks (manage_options) to all AJAX handlers
* Improved category sanitization before WordPress insertion
* Security and stability improvements

### 1.5.42
* Initial public release.