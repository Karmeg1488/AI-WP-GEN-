<?php
if (!defined('ABSPATH')) exit;

/**
 * Fix common PHP/HTML template generation errors
 */
function aicg_fix_template_syntax($content) {
    if (empty($content)) {
        return $content;
    }

    // Don't modify by default - let the improved prompts do their job
    // Only do minimal fixes for extreme cases
    
    $content = trim($content);
    
    // Remove markdown code fence if present
    $content = preg_replace('/^```php\s*/i', '', $content);
    $content = preg_replace('/\s*```\s*$/i', '', $content);
    $content = trim($content);
    
    return $content;
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
        'assets/js/theme.js' => "/* Theme JavaScript */\n(function() {\n    'use strict';\n    \n    // Mobile menu toggle\n    const menuToggle = document.querySelector('.mobile-menu-toggle');\n    if (menuToggle) {\n        menuToggle.addEventListener('click', function() {\n            this.classList.toggle('active');\n        });\n    }\n})();\n",
    ];

    foreach ($files as $filename => $content) {
        $file_path = $theme_path . '/' . $filename;
        
        // Create subdirectories if needed
        $dir = dirname($file_path);
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        
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
 * Generate header.php - Use hardcoded template for reliability
 */
function aicg_generate_theme_header($api_key, $theme_name, $language, $custom_prompt = '') {
    return <<<'PHP'
<?php
/**
 * Header Template
 */
?>
<!DOCTYPE html>
<html lang="<?php language_attributes(); ?>">
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php wp_title(); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <header class="site-header">
        <div class="site-branding">
            <h1><a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a></h1>
            <p><?php bloginfo('description'); ?></p>
        </div>
        <nav class="main-navigation">
            <?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'wp_page_menu')); ?>
        </nav>
    </header>
    <main class="site-content">
PHP;
}

/**
 * Generate footer.php - Use hardcoded template for reliability
 */
function aicg_generate_theme_footer($api_key, $theme_name, $language) {
    return <<<'PHP'
<?php
/**
 * Footer Template
 */
?>
    </main>
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-widgets">
                <?php 
                if (is_active_sidebar('primary-sidebar')) {
                    dynamic_sidebar('primary-sidebar');
                }
                ?>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
                <?php wp_footer(); ?>
            </div>
        </div>
    </footer>
</body>
</html>
PHP;
}

/**
 * Generate index.php (main template)
 */
/**
 * Generate index.php - Use hardcoded template for reliability
 */
function aicg_generate_theme_index($api_key, $theme_name, $language) {
    return <<<'PHP'
<?php
/**
 * Main Template
 */
get_header();
?>
<div class="posts-container">
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();
            ?>
            <article class="post">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="post-thumbnail">
                        <?php the_post_thumbnail('medium'); ?>
                    </div>
                <?php endif; ?>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <div class="post-meta">
                    <span class="author">By <?php the_author(); ?></span>
                    <span class="date"><?php echo get_the_date(); ?></span>
                    <span class="category"><?php the_category(', '); ?></span>
                </div>
                <div class="excerpt">
                    <?php the_excerpt(); ?>
                </div>
                <a href="<?php the_permalink(); ?>" class="read-more">Read More</a>
            </article>
            <?php
        endwhile;
        
        // Pagination
        ?>
        <nav class="pagination">
            <?php
            echo paginate_links(array(
                'type' => 'list',
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;'
            ));
            ?>
        </nav>
        <?php
    else :
        ?>
        <p>No posts found.</p>
        <?php
    endif;
    ?>
</div>
<?php get_footer(); ?>
PHP;
}

/**
 * Generate single.php (single post template)
 */
/**
 * Generate single.php - Use hardcoded template for reliability
 */
function aicg_generate_theme_single($api_key, $theme_name, $language) {
    return <<<'PHP'
<?php
/**
 * Single Post Template
 */
get_header();
?>
<div class="post-container">
    <?php
    while (have_posts()) : the_post();
        ?>
        <article class="single-post">
            <?php if (has_post_thumbnail()) : ?>
                <div class="post-featured-image">
                    <?php the_post_thumbnail('large'); ?>
                </div>
            <?php endif; ?>
            <h1><?php the_title(); ?></h1>
            <div class="post-meta">
                <span class="author">By <?php the_author_meta('display_name'); ?></span>
                <span class="date"><?php echo get_the_date(); ?></span>
                <span class="category"><?php the_category(', '); ?></span>
            </div>
            <div class="post-content">
                <?php the_content(); ?>
            </div>
            <div class="post-tags">
                <?php the_tags('<span>', '</span>', ''); ?>
            </div>
            <nav class="post-navigation">
                <div class="prev-post"><?php previous_post_link(); ?></div>
                <div class="next-post"><?php next_post_link(); ?></div>
            </nav>
        </article>
        <?php
        if (comments_open() || get_comments_number()) {
            comments_template();
        }
    endwhile;
    ?>
</div>
<?php get_footer(); ?>
PHP;
}

/**
 * Generate page.php (page template)
 */
/**
 * Generate page.php - Use hardcoded template for reliability
 */
function aicg_generate_theme_page($api_key, $theme_name, $language) {
    return <<<'PHP'
<?php
/**
 * Page Template
 */
get_header();
?>
<div class="page-container">
    <?php
    while (have_posts()) : the_post();
        ?>
        <article class="page">
            <h1><?php the_title(); ?></h1>
            <div class="page-content">
                <?php the_content(); ?>
            </div>
        </article>
        <?php
        if (comments_open() || get_comments_number()) {
            comments_template();
        }
    endwhile;
    ?>
</div>
<?php get_footer(); ?>
PHP;
}

/**
 * Generate archive.php (archive template)
 */
/**
 * Generate archive.php - Use hardcoded template for reliability
 */
function aicg_generate_theme_archive($api_key, $theme_name, $language) {
    return <<<'PHP'
<?php
/**
 * Archive Template
 */
get_header();
?>
<div class="archive-container">
    <h1 class="archive-title"><?php the_archive_title(); ?></h1>
    <div class="archive-description"><?php the_archive_description(); ?></div>
    
    <div class="posts-archive">
        <?php
        if (have_posts()) :
            while (have_posts()) : the_post();
                ?>
                <article class="post">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="post-thumbnail">
                            <?php the_post_thumbnail('medium'); ?>
                        </div>
                    <?php endif; ?>
                    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <div class="post-meta">
                        <span class="date"><?php echo get_the_date(); ?></span>
                    </div>
                    <div class="excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                </article>
                <?php
            endwhile;
            
            // Pagination
            echo paginate_links(array('type' => 'list'));
        else :
            ?>
            <p>No posts found.</p>
            <?php
        endif;
        ?>
    </div>
</div>
<?php get_footer(); ?>
PHP;
}

/**
 * Generate sidebar.php
 */
/**
 * Generate sidebar.php - Use hardcoded template for reliability
 */
function aicg_generate_theme_sidebar($api_key, $theme_name, $language) {
    return <<<'PHP'
<?php
/**
 * Sidebar Template
 */
?>
<aside class="sidebar">
    <?php
    if (is_active_sidebar('primary-sidebar')) {
        dynamic_sidebar('primary-sidebar');
    } else {
        ?>
        <div class="widget">
            <h3>Primary Sidebar</h3>
            <p>This sidebar is not assigned any widgets. Configure it in Appearance > Widgets.</p>
        </div>
        <?php
    }
    ?>
</aside>
PHP;
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
