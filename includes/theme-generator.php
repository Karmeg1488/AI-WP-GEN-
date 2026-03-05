<?php
if (!defined('ABSPATH')) exit;

/**
 * Fix common PHP/HTML template generation errors
 */
function aicg_fix_template_syntax($content) {
    if (empty($content)) {
        return $content;
    }

    // Fix: <?php followed directly by HTML without closing ?>
    // Match pattern: <?php [spaces/newlines] < (start of HTML tag)
    $content = preg_replace('/<\?php\s*(?=<[^?])/m', "<?php\n?>\n<", $content);
    
    // Fix: Multiple <?php tags - remove duplicates
    $content = preg_replace('/<\?php\s*<\?php/m', "<?php", $content);
    
    // Fix: Remove empty PHP blocks
    $content = preg_replace('/<\?php\s*\?>/m', '', $content);
    
    // Ensure file starts with <?php on fresh line if it has PHP
    if (strpos($content, '<?php') === 0) {
        $content = "<?php\n" . ltrim(substr($content, 5));
    }
    
    return trim($content);
}

/**
 * Generate a complete WordPress theme with custom styles and layouts
 */
function aicg_generate_wordpress_theme($api_key, $theme_name, $theme_description, $custom_prompt) {
    if (!$api_key) {
        return new WP_Error('no_api_key', 'OpenAI API key is not set.');
    }

    $language_code = get_option('ang_language', 'en');
    $language = get_language_name_from_code($language_code);
    $site_name = get_option('aicg_site_name', '') ?: get_option('blogname', 'My Website');
    
    // Sanitize theme name for directory
    $safe_theme_name = sanitize_file_name(str_replace(' ', '-', strtolower($theme_name)));
    $theme_path = WP_CONTENT_DIR . '/themes/' . $safe_theme_name;
    
    // Check if theme directory already exists
    if (is_dir($theme_path)) {
        return new WP_Error('theme_exists', 'Theme directory already exists. Please choose a different name or delete existing theme.');
    }

    // Create theme directories
    if (!wp_mkdir_p($theme_path . '/assets/css')) {
        return new WP_Error('dir_error', 'Failed to create theme directories.');
    }
    if (!wp_mkdir_p($theme_path . '/assets/images')) {
        return new WP_Error('dir_error', 'Failed to create images directory.');
    }
    if (!wp_mkdir_p($theme_path . '/assets/js')) {
        return new WP_Error('dir_error', 'Failed to create JS directory.');
    }

    // Generate theme style.css header
    $style_header = aicg_generate_theme_style_header($theme_name, $theme_description, $site_name);
    
    // Generate main CSS
    $main_css = aicg_generate_theme_css($api_key, $theme_name, $language, $custom_prompt);
    if (is_wp_error($main_css) || empty($main_css)) {
        return new WP_Error('css_gen_failed', 'Failed to generate CSS styles.');
    }

    // Generate functions.php
    $functions_php = aicg_generate_theme_functions($theme_name, $safe_theme_name);

    // Generate header.php
    $header_php = aicg_generate_theme_header($api_key, $theme_name, $language, $custom_prompt);
    if (is_wp_error($header_php) || empty($header_php)) {
        return new WP_Error('header_gen_failed', 'Failed to generate header template.');
    }

    // Generate footer.php
    $footer_php = aicg_generate_theme_footer($api_key, $theme_name, $language);
    if (is_wp_error($footer_php) || empty($footer_php)) {
        return new WP_Error('footer_gen_failed', 'Failed to generate footer template.');
    }

    // Generate index.php
    $index_php = aicg_generate_theme_index($api_key, $theme_name, $language);

    // Generate single.php
    $single_php = aicg_generate_theme_single($api_key, $theme_name, $language);

    // Generate page.php
    $page_php = aicg_generate_theme_page($api_key, $theme_name, $language);

    // Generate archive.php
    $archive_php = aicg_generate_theme_archive($api_key, $theme_name, $language);

    // Generate sidebar.php
    $sidebar_php = aicg_generate_theme_sidebar($api_key, $theme_name, $language);

    // Generate screenshot.png for theme preview
    $screenshot_url = aicg_generate_theme_screenshot($api_key, $theme_name);

    // Write files
    $files = [
        'style.css' => $style_header . "\n" . $main_css,
        'functions.php' => aicg_fix_template_syntax($functions_php),
        'header.php' => aicg_fix_template_syntax($header_php),
        'footer.php' => aicg_fix_template_syntax($footer_php),
        'index.php' => aicg_fix_template_syntax($index_php),
        'single.php' => aicg_fix_template_syntax($single_php),
        'page.php' => aicg_fix_template_syntax($page_php),
        'archive.php' => aicg_fix_template_syntax($archive_php),
        'sidebar.php' => aicg_fix_template_syntax($sidebar_php),
    ];

    foreach ($files as $filename => $content) {
        $file_path = $theme_path . '/' . $filename;
        if (file_put_contents($file_path, $content) === false) {
            return new WP_Error('write_error', "Failed to write theme file: $filename");
        }
    }

    // Download and save theme screenshot
    if (!empty($screenshot_url)) {
        $screenshot_path = $theme_path . '/screenshot.png';
        aicg_download_image($screenshot_url, $screenshot_path);
    }

    // Generate and save additional images for homepage
    $image_count = 3;
    aicg_generate_theme_images($api_key, $theme_name, $theme_path, $image_count, $language);

    // Generate custom CSS file
    $custom_css = aicg_generate_custom_css($api_key, $theme_name, $language, $custom_prompt);
    if (!empty($custom_css)) {
        file_put_contents($theme_path . '/assets/css/custom.css', $custom_css);
    }

    return [
        'success' => true,
        'theme_name' => $theme_name,
        'safe_name' => $safe_theme_name,
        'theme_path' => $theme_path,
        'message' => sprintf('Theme "%s" successfully created! Activate it from Appearance > Themes.', $theme_name)
    ];
}

/**
 * Generate theme style.css header
 */
function aicg_generate_theme_style_header($theme_name, $description, $site_name) {
    return <<<CSS
/*
Theme Name: {$theme_name}
Description: {$description}
Author: AI WP GEN
Version: 1.0.0
License: GPLv2 or later
Text Domain: {$theme_name}
Domain Path: /languages

*/

/* Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    height: 100%;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    line-height: 1.6;
    color: #333;
    background-color: #fafafa;
}

/* WordPress Core Styles */
.wp-block-image img {
    max-width: 100%;
    height: auto;
}

CSS;
}

/**
 * Generate main CSS styles from OpenAI
 */
function aicg_generate_theme_css($api_key, $theme_name, $language, $custom_prompt = '') {
    $prompt = "Generate comprehensive, modern CSS styles for a WordPress theme called \"{$theme_name}\" in {$language}. " .
        ($custom_prompt ? "Based on this concept: {$custom_prompt}. " : "") .
        "Include detailed, unique styles for: " .
        "1) Header (navigation bar, logo area, responsive menu) - min 300 lines " .
        "2) Footer (multi-column layout, links, social media) - min 200 lines " .
        "3) Post/Article cards (featured image, title, excerpt, metadata) - min 200 lines " .
        "4) Page layout (content area, sidebar, responsive grid) - min 200 lines " .
        "5) Buttons and CTAs (hover effects, transitions, colors) - min 100 lines " .
        "6) Forms (input styling, validation, focus states) - min 150 lines " .
        "7) Mobile responsiveness (all screen sizes, touch-friendly) - min 150 lines " .
        "8) Typography (headings h1-h6, paragraphs, links) - min 100 lines " .
        "9) Colors and gradients (unique color scheme, smooth transitions) - min 150 lines " .
        "10) Animations and effects (smooth transitions, hover states) - min 100 lines " .
        "Total: 1400+ lines of professional, modern CSS. " .
        "Include custom properties (CSS variables) for colors, spacing, fonts. " .
        "Make it unique, visually appealing, and production-ready. " .
        "Output: ONLY valid CSS code, no markdown or explanations.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    if (empty($response)) {
        error_log('AI WP GEN - Failed to generate theme CSS');
        return '';
    }

    // Clean up CSS response
    $response = trim($response);
    $response = str_replace('```css', '', $response);
    $response = str_replace('```', '', $response);
    return $response;
}

/**
 * Generate theme functions.php
 */
function aicg_generate_theme_functions($theme_name, $theme_slug) {
    // Convert slug with dashes to underscore version for PHP function names
    $php_slug = str_replace('-', '_', $theme_slug);
    $theme_slug_quoted = "'{$theme_slug}'";
    
    return <<<PHP
<?php
/**
 * {$theme_name} Theme Functions
 */

// Add theme support
add_theme_support('title-tag');
add_theme_support('post-thumbnails');
add_theme_support('custom-logo');
add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
add_theme_support('responsive-embeds');

// Register menus
register_nav_menus(array(
    'primary' => __('Primary Menu', '{$php_slug}'),
    'footer' => __('Footer Menu', '{$php_slug}')
));

// Register sidebars
register_sidebar(array(
    'name' => __('Primary Sidebar', '{$php_slug}'),
    'id' => '{$php_slug}_primary',
    'description' => __('Main sidebar', '{$php_slug}'),
    'before_widget' => '<div id="%1\\$s" class="widget %2\\$s">',
    'after_widget' => '</div>',
    'before_title' => '<h3 class="widget-title">',
    'after_title' => '</h3>'
));

// Enqueue styles and scripts
function {$php_slug}_enqueue_assets() {
    wp_enqueue_style('{$theme_slug}-style', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('{$theme_slug}-script', get_template_directory_uri() . '/assets/js/theme.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', '{$php_slug}_enqueue_assets');

// Custom excerpt length
function {$php_slug}_excerpt_length(\$length) {
    return 20;
}
add_filter('excerpt_length', '{$php_slug}_excerpt_length');

// Custom excerpt more
function {$php_slug}_excerpt_more(\$more) {
    return '...';
}
add_filter('excerpt_more', '{$php_slug}_excerpt_more');

// Add custom body classes
function {$php_slug}_body_classes(\$classes) {
    if (is_front_page()) {
        \$classes[] = 'front-page';
    }
    if (is_single()) {
        \$classes[] = 'single-post';
    }
    return \$classes;
}
add_filter('body_class', '{$php_slug}_body_classes');
?>
PHP;
}

/**
 * Generate header.php
 */
function aicg_generate_theme_header($api_key, $theme_name, $language, $custom_prompt = '') {
    $prompt = "Generate a complete, valid header.php WordPress template for theme \"{$theme_name}\" in {$language}. " .
        "CRITICAL REQUIREMENTS:\n" .
        "1) Start with <?php tag, then immediately include logical code\n" .
        "2) IMPORTANT: Close the opening <?php tag with ?> BEFORE any HTML doctype\n" .
        "3) The file structure MUST be: <?php [php code here] ?> [HTML here] \n" .
        "4) Include these functions: wp_head(), bloginfo('charset'), body_class(), wp_nav_menu(), get_template_directory_uri()\n" .
        "5) Include: DOCTYPE html, head with meta tags (charset, viewport), navigation, logo/branding, search form, social icons\n" .
        "6) Responsive mobile menu toggle button\n" .
        "7) Use semantic HTML5 elements\n" .
        "8) Do NOT use markdown, output must be raw PHP/HTML code only\n" .
        "EXAMPLE STRUCTURE:\n" .
        "<?php\n" .
        "// PHP logic here\n" .
        "?>\n" .
        "<!DOCTYPE html>\n" .
        "<html>\n" .
        "...[rest of HTML]";

    $response = aicg_openai_chat_request($api_key, $prompt);
    if (empty($response)) {
        error_log('AI WP GEN - Failed to generate theme header');
        return '';
    }

    return $response;
}

/**
 * Generate footer.php
 */
function aicg_generate_theme_footer($api_key, $theme_name, $language) {
    $prompt = "Generate a complete WordPress footer.php template for theme \"{$theme_name}\" in {$language}. " .
        "CRITICAL REQUIREMENTS:\n" .
        "1) Start with <?php tag\n" .
        "2) IMPORTANT: Close PHP with ?> BEFORE HTML content\n" .
        "3) Include wp_footer() function (REQUIRED for WordPress)\n" .
        "4) Include: multi-column layout (3-4 columns), widgets area, copyright notice, footer menu with wp_nav_menu()\n" .
        "5) Include: social media links, back to top button, closing body and html tags\n" .
        "6) Responsive design with flexbox\n" .
        "7) Clean semantic HTML5\n" .
        "8) Do NOT use markdown, only raw PHP/HTML\n" .
        "MUST include <?php wp_footer(); ?> before closing body tag.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    if (empty($response)) {
        error_log('AI WP GEN - Failed to generate theme footer');
        return '';
    }

    return $response;
}

/**
 * Generate index.php (main template)
 */
function aicg_generate_theme_index($api_key, $theme_name, $language) {
    $prompt = "Generate WordPress index.php template for theme \"{$theme_name}\" showing blog posts/articles in {$language}. " .
        "CRITICAL: Start with <?php, then close with ?> before HTML.\n" .
        "Include: get_header(), have_posts() loop with posts, post title/excerpt/date/author/categories, thumbnails, read more link, pagination, sidebar with dynamic_sidebar(), get_footer().\n" .
        "Responsive, semantic HTML5. Output ONLY raw PHP/HTML code - no markdown.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    if (empty($response)) {
        error_log('AI WP GEN - Failed to generate theme index');
        return '';
    }

    return $response;
}

/**
 * Generate single.php (single post template)
 */
function aicg_generate_theme_single($api_key, $theme_name, $language) {
    $prompt = "Generate WordPress single.php for displaying single posts in theme \"{$theme_name}\" in {$language}. " .
        "CRITICAL: Start with <?php, close with ?> before HTML.\n" .
        "Include: get_header(), featured image, post title, author info with get_the_author_meta(), publication date, categories, full post_content, tags, related posts query, comments_template(), get_footer().\n" .
        "Responsive, semantic HTML5. Output ONLY raw PHP/HTML - no markdown.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    return !empty($response) ? $response : '';
}

/**
 * Generate page.php (page template)
 */
function aicg_generate_theme_page($api_key, $theme_name, $language) {
    $prompt = "Generate WordPress page.php for static pages in theme \"{$theme_name}\" in {$language}. " .
        "CRITICAL: Start with <?php, close with ?> before HTML.\n" .
        "Include: get_header(), page title using the_title(), full page content with the_content(), comments_template(), sidebar with dynamic_sidebar(), get_footer().\n" .
        "Responsive, semantic HTML5. Output ONLY raw PHP/HTML - no markdown.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    return !empty($response) ? $response : '';
}

/**
 * Generate archive.php (archive template)
 */
function aicg_generate_theme_archive($api_key, $theme_name, $language) {
    $prompt = "Generate WordPress archive.php for category/date archives in theme \"{$theme_name}\" in {$language}. " .
        "CRITICAL: Start with <?php, close with ?> before HTML.\n" .
        "Include: get_header(), archive title using the_archive_title(), archive description with the_archive_description(), posts loop with have_posts(), post summaries, pagination, get_footer().\n" .
        "Responsive, semantic HTML5. Output ONLY raw PHP/HTML - no markdown.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    return !empty($response) ? $response : '';
}

/**
 * Generate sidebar.php
 */
function aicg_generate_theme_sidebar($api_key, $theme_name, $language) {
    $prompt = "Generate WordPress sidebar.php for theme \"{$theme_name}\" in {$language}. " .
        "CRITICAL: Start with <?php, close with ?> before HTML.\n" .
        "Include: PHP check if(is_active_sidebar()), dynamic_sidebar() function call, styled widget containers with proper HTML.\n" .
        "Responsive CSS-friendly markup. Output ONLY raw PHP/HTML - no markdown.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    return !empty($response) ? $response : '';
}

/**
 * Generate custom CSS file
 */
function aicg_generate_custom_css($api_key, $theme_name, $language, $custom_prompt = '') {
    $prompt = "Generate advanced, unique CSS for theme \"{$theme_name}\" in {$language}. " .
        ($custom_prompt ? "Design concept: {$custom_prompt}. " : "") .
        "Create: advanced animations, gradients, modern color schemes, hover effects, 800+ lines. " .
        "Output: ONLY CSS code.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    return $response ?: '';
}

/**
 * Generate theme screenshot preview image
 */
function aicg_generate_theme_screenshot($api_key, $theme_name) {
    $site_name = get_option('aicg_site_name', '') ?: get_option('blogname', '');
    
    $prompt = "Generate a professional, modern WordPress website design preview image for theme named \"{$theme_name}\". " .
        "Style: contemporary web design, clean layout, professional company website appearance. " .
        "Show: responsive design appearing on desktop, modern header, content sections, professional color scheme. " .
        "High quality, 4K ready, professional marketing image. Size: 1200x900px.";

    $image_url = aicg_generate_image_from_prompt($api_key, $prompt, 1200, 900);
    return $image_url ?: '';
}

/**
 * Generate and save theme images
 */
function aicg_generate_theme_images($api_key, $theme_name, $theme_path, $count = 3, $language = 'English') {
    $image_types = [
        'hero' => 'Generate a stunning hero banner image for website theme. Modern, professional, high-quality photography.',
        'feature' => 'Generate a professional feature/service hero image. Modern design, corporate style, 1920x1080px.',
        'content' => 'Generate a professional content block background image. Subtle, modern, suitable for reading text overlay.'
    ];

    $counter = 0;
    foreach ($image_types as $type => $extra_prompt) {
        if ($counter >= $count) break;
        
        $prompt = "Generate a professional, high-quality image for WordPress website in {$language}. " .
            "{$extra_prompt} " .
            "Style: modern, clean, professional. Resolution 1920x1080px, suitable for web.";

        $image_url = aicg_generate_image_from_prompt($api_key, $prompt, 1920, 1080);
        
        if (!empty($image_url)) {
            $filename = $theme_path . '/assets/images/' . $type . '-image.jpg';
            aicg_download_image($image_url, $filename);
        }
        
        $counter++;
    }
}

/**
 * Download and save image from URL
 */
function aicg_download_image($image_url, $save_path) {
    if (empty($image_url)) return false;

    $response = wp_remote_get($image_url, array('timeout' => 30));
    if (is_wp_error($response)) {
        error_log('AI WP GEN - Failed to download image: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    if (file_put_contents($save_path, $body) === false) {
        error_log('AI WP GEN - Failed to save image: ' . $save_path);
        return false;
    }

    return true;
}

?>
