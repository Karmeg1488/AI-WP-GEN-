<?php
/*
Plugin Name: AI WP GEN
Description: Automatic generation of authors, categories, articles, images, and WordPress themes via OpenAI.
Version: 1.6.10
Author: Stanislav Perepelytsia
License: GPLv2 or later
*/
if (!defined('ABSPATH')) exit; // Prevent direct access

// Debug logging function - MUST BE DEFINED FIRST before any includes
function aicg_debug_log($message) {
    // Direct error_log for immediate debugging
    error_log('[AI WP GEN] ' . $message);
    
    // Also save to WordPress option for visibility
    if (function_exists('get_option') && function_exists('update_option')) {
        $logs = get_option('aicg_debug_logs', []);
        if (!is_array($logs)) {
            $logs = [];
        }
        
        $logs[] = [
            'time' => function_exists('current_time') ? current_time('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
            'message' => $message
        ];
        
        // Keep only last 100 logs
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('aicg_debug_logs', $logs);
    }
}

// Log plugin loading
aicg_debug_log('AI WP GEN plugin loaded');

// Include functional files with error handling
$plugin_dir = plugin_dir_path(__FILE__);
$required_files = [
    'includes/admin-page.php',
    'includes/generator.php',
    'includes/image-generator.php',
    'includes/openai-helper.php',
    'includes/ajax-handlers.php',
    'includes/theme-generator.php',
];

foreach ($required_files as $file) {
    $file_path = $plugin_dir . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log("AI WP GEN: Required file missing: $file");
    }
}

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
        $upload_dir = wp_upload_dir();
        $css_file = $upload_dir['basedir'] . '/aicg-styles/base.css';
        $version = file_exists($css_file) ? filemtime($css_file) : '1.0';
        wp_enqueue_style('aicg-base-css', $css_url, [], $version);
    }
});

// Plugin activation hook
register_activation_hook(__FILE__, function() {
    // Create upload directory for styles if it doesn't exist
    $upload_dir = wp_upload_dir();
    $styles_dir = $upload_dir['basedir'] . '/aicg-styles';
    if (!file_exists($styles_dir)) {
        wp_mkdir_p($styles_dir);
    }
    aicg_debug_log('AI WP GEN plugin activated successfully');
});

// Plugin deactivation hook
register_deactivation_hook(__FILE__, function() {
    aicg_debug_log('AI WP GEN plugin deactivated');
});


