<?php
/*
Plugin Name: AI WP GEN
Description: Automatic generation of authors, categories, articles, and images via OpenAI.
Version: 1.5.56
Author: Stanislav Perepelytsia
License: GPLv2 or later
*/
if (!defined('ABSPATH')) exit; // Prevent direct access

// Include functional files
require_once plugin_dir_path(__FILE__) . 'includes/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/image-generator.php';
require_once plugin_dir_path(__FILE__) . 'includes/openai-helper.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handlers.php';

// Hook to add admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'AI WP GEN',       // Page title
        'AI WP GEN',       // Menu title
        'manage_options',             // Capability
        'ai-news-generator',          // Menu slug
        'aicg_admin_page_render',     // Callback function
        'dashicons-admin-generic',    // Icon
        75                            // Menu position
    );
});

// Hook to enqueue generated CSS on frontend
add_action('wp_enqueue_scripts', function() {
    $css_url = get_option('aicg_css_file_url', '');
    if (!empty($css_url)) {
        wp_enqueue_style('aicg-base-css', $css_url, [], filemtime(wp_upload_dir()['basedir'] . '/aicg-styles/base.css'));
    }
});

