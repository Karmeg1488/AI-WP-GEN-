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
    // Build a more specific prompt that emphasizes the custom concept
    $concept_emphasis = '';
    if (!empty($custom_prompt)) {
        // Truncate if too long
        $truncated_prompt = strlen($custom_prompt) > 300 ? substr($custom_prompt, 0, 300) . '...' : $custom_prompt;
        $concept_emphasis = "THIS IS YOUR DESIGN DIRECTION - MAKE IT THE PRIMARY FOCUS:\n\"{$truncated_prompt}\"\n\n" .
            "Apply this concept to ALL colors, typography, spacing, animations, and visual elements throughout the theme. " .
            "The user provided this concept, so RESPECT IT and build the entire visual identity around it. ";
    }
    
    $prompt = "Generate comprehensive, production-ready CSS styles for a WordPress theme called \"{$theme_name}\" in {$language}.\n\n" .
        $concept_emphasis .
        "Include detailed styles for ALL of these elements:\n\n" .
        "GLOBAL:\n" .
        "- Root CSS variables (colors from concept, spacing units, typography)\n" .
        "- Body, html, main styling\n" .
        "- All headings (h1-h6) with distinct sizing and styles\n" .
        "- Paragraph, links, strong, em, blockquote styling\n\n" .
        "LAYOUT & CONTAINERS:\n" .
        "- .site-container, .content-wrapper, .page-container\n" .
        "- Header with navigation, logo positioning\n" .
        "- Footer with multi-section layout\n" .
        "- Sidebar styling\n" .
        "- Main content area with proper spacing\n\n" .
        "CONTENT ELEMENTS:\n" .
        "- Post/article cards with featured image, title, excerpt, metadata\n" .
        "- Single post/page styling with breadcrumbs, author box\n" .
        "- Archive/list styling with category labels\n" .
        "- Page headers and sections\n\n" .
        "INTERACTIVE:\n" .
        "- Buttons with multiple states (normal, hover, active, disabled)\n" .
        "- Forms: inputs, textareas, selects with focus states\n" .
        "- Links with hover effects\n" .
        "- Smooth transitions and transforms on interactive elements\n\n" .
        "RESPONSIVE:\n" .
        "- Mobile styles (480px and below)\n" .
        "- Tablet styles (768px)\n" .
        "- Desktop styles (1200px+)\n" .
        "- Touch-friendly spacing for mobile\n\n" .
        "SPECIAL EFFECTS:\n" .
        "- Gradients and subtle animations\n" .
        "- Hover effects on all interactive elements\n" .
        "- Smooth color transitions matching the concept\n" .
        "- Unique visual touches that match your design direction\n\n" .
        "OUTPUT: Only valid, complete CSS code. No explanations or markdown. Minimum 1000 lines of well-organized CSS.";

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
 * Main/Index Template
 */
get_header();
?>
<main class="site-main">
    <div class="posts-container">
        <div class="posts-grid">
            <?php
            if (have_posts()) :
                while (have_posts()) : the_post();
                    ?>
                    <article class="post-card" id="post-<?php the_ID(); ?>">
                        <div class="post-card-inner">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>" class="post-card-image">
                                    <?php the_post_thumbnail('medium_large', ['class' => 'featured-image']); ?>
                                    <span class="post-card-overlay"></span>
                                </a>
                            <?php endif; ?>

                            <div class="post-card-content">
                                <div class="post-categories">
                                    <?php the_category(' '); ?>
                                </div>

                                <h2 class="post-card-title">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h2>

                                <div class="post-card-meta">
                                    <span class="meta-author">
                                        <i class="icon-user"></i>
                                        <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                            <?php the_author(); ?>
                                        </a>
                                    </span>
                                    <span class="meta-date">
                                        <i class="icon-calendar"></i>
                                        <time datetime="<?php echo get_the_date('c'); ?>">
                                            <?php echo get_the_date(); ?>
                                        </time>
                                    </span>
                                    <span class="meta-comments">
                                        <i class="icon-comments"></i>
                                        <a href="<?php comments_link(); ?>">
                                            <?php comments_number(__('No comments', 'textdomain'), __('1 comment', 'textdomain'), __('% comments', 'textdomain')); ?>
                                        </a>
                                    </span>
                                </div>

                                <div class="post-card-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>

                                <a href="<?php the_permalink(); ?>" class="post-card-link">
                                    <?php _e('Read More', 'textdomain'); ?> <span class="arrow">→</span>
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php
                endwhile;
                
                // Pagination
                ?>
                <nav class="pagination-wrapper" aria-label="<?php _e('Post pagination', 'textdomain'); ?>">
                    <div class="pagination">
                        <?php
                        echo paginate_links(array(
                            'type' => 'list',
                            'prev_text' => '← ' . __('Previous', 'textdomain'),
                            'next_text' => __('Next', 'textdomain') . ' →',
                            'before_page_number' => '<span class="page-number">',
                            'after_page_number' => '</span>'
                        ));
                        ?>
                    </div>
                </nav>
                <?php
            else :
                ?>
                <div class="no-posts-found">
                    <h2><?php _e('No Posts Found', 'textdomain'); ?></h2>
                    <p><?php _e('Sorry, no posts matched your criteria.', 'textdomain'); ?></p>
                </div>
                <?php
            endif;
            ?>
        </div>

        <?php
        // Display sidebar if it exists
        if (is_active_sidebar('primary-sidebar')) {
            echo '<aside class="posts-sidebar">';
            dynamic_sidebar('primary-sidebar');
            echo '</aside>';
        }
        ?>
    </div>
</main>
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
<main class="site-main">
    <div class="post-container">
        <div class="post-wrapper">
            <?php
            while (have_posts()) : the_post();
                ?>
                <article class="single-post" id="post-<?php the_ID(); ?>">
                    <header class="post-header">
                        <h1 class="post-title"><?php the_title(); ?></h1>
                        
                        <div class="post-meta-header">
                            <div class="post-meta-author">
                                <span class="meta-label"><?php _e('By', 'textdomain'); ?></span>
                                <?php the_author_posts_link(); ?>
                            </div>
                            <div class="post-meta-date">
                                <span class="meta-label"><?php _e('Published', 'textdomain'); ?></span>
                                <time datetime="<?php echo get_the_date('c'); ?>">
                                    <?php echo get_the_date(); ?>
                                </time>
                            </div>
                            <div class="post-meta-categories">
                                <span class="meta-label"><?php _e('Category', 'textdomain'); ?></span>
                                <?php the_category(', '); ?>
                            </div>
                        </div>

                        <?php if (has_post_thumbnail()) : ?>
                            <div class="post-featured-image">
                                <?php the_post_thumbnail('large', ['class' => 'featured-image']); ?>
                            </div>
                        <?php endif; ?>
                    </header>

                    <div class="post-content-wrapper">
                        <div class="post-content">
                            <?php the_content(); ?>
                        </div>

                        <?php
                        // Display sidebar if it exists
                        if (is_active_sidebar('primary-sidebar')) {
                            echo '<aside class="post-sidebar">';
                            dynamic_sidebar('primary-sidebar');
                            echo '</aside>';
                        }
                        ?>
                    </div>

                    <?php
                    $tags = get_the_tags();
                    if ($tags) {
                        echo '<footer class="post-footer">';
                        echo '<div class="post-tags">';
                        echo '<span class="tag-label">' . __('Tags:', 'textdomain') . '</span>';
                        foreach ($tags as $tag) {
                            echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" class="tag">' . esc_html($tag->name) . '</a>';
                        }
                        echo '</div>';
                        echo '</footer>';
                    }
                    ?>

                    <nav class="post-navigation">
                        <div class="nav-previous">
                            <?php previous_post_link('%link', '← ' . __('Previous Post', 'textdomain')); ?>
                        </div>
                        <div class="nav-next">
                            <?php next_post_link('%link', __('Next Post', 'textdomain') . ' →'); ?>
                        </div>
                    </nav>

                    <!-- Author Box -->
                    <div class="author-box">
                        <div class="author-avatar">
                            <?php echo get_avatar(get_the_author_meta('ID'), 80); ?>
                        </div>
                        <div class="author-info">
                            <h4 class="author-name"><?php the_author_meta('display_name'); ?></h4>
                            <p class="author-bio"><?php the_author_meta('description'); ?></p>
                        </div>
                    </div>
                </article>

                <section class="comments-section">
                    <?php
                    if (comments_open() || get_comments_number()) {
                        comments_template();
                    }
                    ?>
                </section>

                <?php
            endwhile;
            ?>

            <!-- Related Posts -->
            <section class="related-posts">
                <h3><?php _e('Related Posts', 'textdomain'); ?></h3>
                <?php
                $related_posts = new WP_Query([
                    'category__in' => wp_get_post_categories(get_the_ID()),
                    'posts_per_page' => 3,
                    'post__not_in' => [get_the_ID()],
                ]);

                if ($related_posts->have_posts()) {
                    echo '<div class="related-posts-grid">';
                    while ($related_posts->have_posts()) {
                        $related_posts->the_post();
                        echo '<article class="related-post-card">';
                        if (has_post_thumbnail()) {
                            echo '<a href="' . esc_url(get_permalink()) . '" class="card-image">';
                            the_post_thumbnail('medium');
                            echo '</a>';
                        }
                        echo '<h4><a href="' . esc_url(get_permalink()) . '">' . get_the_title() . '</a></h4>';
                        echo '<p class="excerpt">' . get_the_excerpt() . '</p>';
                        echo '</article>';
                    }
                    echo '</div>';
                }
                wp_reset_postdata();
                ?>
            </section>
        </div>
    </div>
</main>
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
<main class="site-main">
    <div class="page-container">
        <div class="page-wrapper">
            <?php
            while (have_posts()) : the_post();
                ?>
                <article class="page-article" id="post-<?php the_ID(); ?>">
                    <header class="page-header">
                        <h1 class="page-title"><?php the_title(); ?></h1>
                        <?php
                        if (has_post_thumbnail()) {
                            echo '<div class="page-featured-image">';
                            the_post_thumbnail('large', ['class' => 'featured-image']);
                            echo '</div>';
                        }
                        ?>
                    </header>

                    <div class="page-content-wrapper">
                        <div class="page-content">
                            <?php the_content(); ?>
                        </div>
                        
                        <?php
                        // Display sidebar if it exists
                        if (is_active_sidebar('primary-sidebar')) {
                            echo '<aside class="page-sidebar">';
                            dynamic_sidebar('primary-sidebar');
                            echo '</aside>';
                        }
                        ?>
                    </div>

                    <footer class="page-footer">
                        <div class="page-meta">
                            <span class="page-date">
                                <i class="icon-calendar"></i>
                                <?php echo get_the_date(); ?>
                            </span>
                            <span class="page-author">
                                <i class="icon-user"></i>
                                <?php the_author_posts_link(); ?>
                            </span>
                        </div>
                    </footer>
                </article>

                <nav class="page-navigation">
                    <div class="nav-previous">
                        <?php previous_post_link('%link', '← ' . __('Previous Page', 'textdomain')); ?>
                    </div>
                    <div class="nav-next">
                        <?php next_post_link('%link', __('Next Page', 'textdomain') . ' →'); ?>
                    </div>
                </nav>

                <?php
                if (comments_open() || get_comments_number()) {
                    echo '<div class="page-comments-section">';
                    comments_template();
                    echo '</div>';
                }
                ?>
                <?php
            endwhile;
            ?>
        </div>
    </div>
</main>
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
<main class="site-main">
    <div class="archive-container">
        <header class="archive-header">
            <h1 class="archive-title"><?php the_archive_title(); ?></h1>
            <div class="archive-description">
                <?php the_archive_description(); ?>
            </div>
        </header>
        
        <div class="archive-posts-wrapper">
            <div class="archive-posts">
                <?php
                if (have_posts()) :
                    while (have_posts()) : the_post();
                        ?>
                        <article class="post-card archive-post-card" id="post-<?php the_ID(); ?>">
                            <div class="post-card-inner">
                                <?php if (has_post_thumbnail()) : ?>
                                    <a href="<?php the_permalink(); ?>" class="post-card-image">
                                        <?php the_post_thumbnail('medium_large', ['class' => 'featured-image']); ?>
                                        <span class="post-card-overlay"></span>
                                    </a>
                                <?php endif; ?>

                                <div class="post-card-content">
                                    <div class="post-categories">
                                        <?php the_category(' '); ?>
                                    </div>

                                    <h2 class="post-card-title">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_title(); ?>
                                        </a>
                                    </h2>

                                    <div class="post-card-meta">
                                        <span class="meta-date">
                                            <i class="icon-calendar"></i>
                                            <time datetime="<?php echo get_the_date('c'); ?>">
                                                <?php echo get_the_date(); ?>
                                            </time>
                                        </span>
                                        <span class="meta-author">
                                            <i class="icon-user"></i>
                                            <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>">
                                                <?php the_author(); ?>
                                            </a>
                                        </span>
                                        <span class="meta-comments">
                                            <i class="icon-comments"></i>
                                            <a href="<?php comments_link(); ?>">
                                                <?php comments_number(__('No comments', 'textdomain'), __('1 comment', 'textdomain'), __('% comments', 'textdomain')); ?>
                                            </a>
                                        </span>
                                    </div>

                                    <div class="post-card-excerpt">
                                        <?php the_excerpt(); ?>
                                    </div>

                                    <a href="<?php the_permalink(); ?>" class="post-card-link">
                                        <?php _e('Read More', 'textdomain'); ?> <span class="arrow">→</span>
                                    </a>
                                </div>
                            </div>
                        </article>
                        <?php
                    endwhile;
                    
                    // Pagination
                    ?>
                    <nav class="pagination-wrapper" aria-label="<?php _e('Archive pagination', 'textdomain'); ?>">
                        <div class="pagination">
                            <?php
                            echo paginate_links(array(
                                'type' => 'list',
                                'prev_text' => '← ' . __('Previous', 'textdomain'),
                                'next_text' => __('Next', 'textdomain') . ' →'
                            ));
                            ?>
                        </div>
                    </nav>
                    <?php
                else :
                    ?>
                    <div class="no-posts-found">
                        <h2><?php _e('No Posts Found', 'textdomain'); ?></h2>
                        <p><?php _e('Sorry, no posts matched your criteria.', 'textdomain'); ?></p>
                    </div>
                    <?php
                endif;
                ?>
            </div>

            <?php
            // Display sidebar if it exists
            if (is_active_sidebar('primary-sidebar')) {
                echo '<aside class="archive-sidebar">';
                dynamic_sidebar('primary-sidebar');
                echo '</aside>';
            }
            ?>
        </div>
    </div>
</main>
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
