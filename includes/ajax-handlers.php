<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'openai-helper.php';
require_once plugin_dir_path(__FILE__) . 'generator.php';
require_once plugin_dir_path(__FILE__) . 'image-generator.php';

add_action('wp_ajax_aicg_generate_title_tagline', 'aicg_generate_title_tagline');
function aicg_generate_title_tagline() {
    check_ajax_referer('aicg_generate_title_tagline');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $url_parts = wp_parse_url(home_url());
    $domain = isset($url_parts['host']) ? $url_parts['host'] : '';  

    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        wp_send_json_error('OpenAI API key is not set.');
    }

    // Get language from plugin settings
    $language_code = get_option('ang_language', 'en');
    $language = get_language_name_from_code($language_code);

    $prompt = "Come up with a creative website title and a short tagline for a website with the domain \"$domain\". The answer must be in $language. Respond in the format: Title - Tagline";

    $response = aicg_openai_chat_request($api_key, $prompt);
    if (!$response) {
        wp_send_json_error('OpenAI request failed.');
    }

    $parts = explode(' - ', $response, 2);
    $title = trim($parts[0] ?? '');
    $tagline = trim($parts[1] ?? '');

    if ($title) update_option('aicg_site_name', $title);
    if ($tagline) update_option('blogdescription', $tagline);

    wp_send_json_success(esc_html($title . ' - ' . $tagline) . '<br><i>Saved to site settings!</i>');
}
add_action('wp_ajax_aicg_generate_logo', 'aicg_generate_logo');
function aicg_generate_logo() {
    check_ajax_referer('aicg_generate_logo');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        wp_send_json_error('OpenAI API key is not set.');
    }
    $tagline = get_option('blogdescription', '');
    
    // Get site name from plugin settings, fallback to WordPress blogname
    $title = get_option('aicg_site_name', '');
    if (empty($title)) {
        $title = get_option('blogname', '');
    }
    
    $prompt = "Create a modern, minimalistic logo for a website called \"$title\" with the tagline \"$tagline\". The logo should be on a transparent background, PNG format, and suitable for website use.";

    $image_url = aicg_generate_image_from_prompt($api_key, $prompt, 512, 512);
    if (!$image_url) {
        wp_send_json_error('Logo generation failed.');
    }
    // Save as site logo (WordPress custom_logo)
    $attachment_id = aicg_media_handle_sideload($image_url, 0, $title . ' Logo');
    if ($attachment_id && !is_wp_error($attachment_id)) {
        set_theme_mod('custom_logo', $attachment_id);
    }
    wp_send_json_success(['url' => $image_url]);
}

add_action('wp_ajax_aicg_generate_favicon', 'aicg_generate_favicon');
function aicg_generate_favicon() {
    check_ajax_referer('aicg_generate_favicon');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        wp_send_json_error('OpenAI API key is not set.');
    }
    $tagline = get_option('blogdescription', '');
    
    // Get site name from plugin settings, fallback to WordPress blogname
    $title = get_option('aicg_site_name', '');
    if (empty($title)) {
        $title = get_option('blogname', '');
    }
    
    $prompt = "Create a simple, recognizable favicon for a website called \"$title\" with the tagline \"$tagline\". The favicon should be 64x64 PNG, transparent background, and suitable for browser tabs.";

    $image_url = aicg_generate_image_from_prompt($api_key, $prompt, 64, 64);
    if (!$image_url) {
        wp_send_json_error('Favicon generation failed.');
    }
    // Save as site icon (WordPress site_icon)
    $attachment_id = aicg_media_handle_sideload($image_url, 0, $title . ' Favicon');
    if ($attachment_id && !is_wp_error($attachment_id)) {
        update_option('site_icon', $attachment_id);
    }
    wp_send_json_success(['url' => $image_url]);
}
add_action('wp_ajax_aicg_use_logo_as_favicon', 'aicg_use_logo_as_favicon');
function aicg_use_logo_as_favicon() {
    check_ajax_referer('aicg_use_logo_as_favicon');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $logo_id = get_theme_mod('custom_logo');
    if (!$logo_id) {
        wp_send_json_error('No logo set.');
    }
    update_option('site_icon', $logo_id);
    wp_send_json_success('Logo is now used as favicon!');
}
add_action('wp_ajax_aicg_generate_contact_about', 'aicg_generate_contact_about');
function aicg_generate_contact_about() {
    check_ajax_referer('aicg_generate_contact_about');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $result = aicg_generate_contact_and_about_pages();
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_aicg_generate_articles', 'aicg_generate_articles_ajax');
function aicg_generate_articles_ajax() {
    check_ajax_referer('aicg_generate_articles');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $result = aicg_generate_authors_categories_articles();
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    wp_send_json_success($result);
}
add_action('wp_ajax_aicg_generate_images', 'aicg_generate_images_ajax');
function aicg_generate_images_ajax() {
    check_ajax_referer('aicg_generate_images');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $result = aicg_generate_images_for_articles();
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    wp_send_json_success($result);
}

add_action('wp_ajax_aicg_generate_homepage', 'aicg_generate_homepage_ajax');
function aicg_generate_homepage_ajax() {
    check_ajax_referer('aicg_generate_homepage');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $result = aicg_generate_homepage();
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    wp_send_json_success($result);
}

add_action('wp_ajax_aicg_generate_css', 'aicg_generate_css_ajax');
function aicg_generate_css_ajax() {
    check_ajax_referer('aicg_generate_css');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $result = aicg_generate_base_css();
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    wp_send_json_success($result);
}

add_action('wp_ajax_aicg_generate_policies', 'aicg_generate_policies_ajax');
function aicg_generate_policies_ajax() {
    check_ajax_referer('aicg_generate_policies');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $result = aicg_generate_policy_pages();
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    wp_send_json_success($result);
}

add_action('wp_ajax_aicg_generate_theme', 'aicg_generate_theme_ajax');
function aicg_generate_theme_ajax() {
    check_ajax_referer('aicg_generate_theme');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions.');
    }
    
    $theme_name = isset($_POST['theme_name']) ? sanitize_text_field($_POST['theme_name']) : 'AI Generated Theme';
    $theme_description = isset($_POST['theme_description']) ? sanitize_text_field($_POST['theme_description']) : 'A theme generated by AI WP GEN';
    $custom_prompt = isset($_POST['custom_prompt']) ? sanitize_textarea_field($_POST['custom_prompt']) : '';
    
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        wp_send_json_error('OpenAI API key is not set.');
    }
    
    $result = aicg_generate_wordpress_theme($api_key, $theme_name, $theme_description, $custom_prompt);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    wp_send_json_success($result);
}
