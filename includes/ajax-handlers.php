<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'openai-helper.php';
require_once plugin_dir_path(__FILE__) . 'generator.php';
require_once plugin_dir_path(__FILE__) . 'image-generator.php';

add_action('wp_ajax_aicg_generate_title_tagline', 'aicg_generate_title_tagline');
function aicg_generate_title_tagline() {
    check_ajax_referer('aicg_generate_title_tagline');
    
    aicg_debug_log('Starting title and tagline generation...');
    
    if (!current_user_can('manage_options')) {
        aicg_debug_log('Title/tagline generation failed: Insufficient permissions');
        wp_send_json_error('Insufficient permissions.');
    }
    
    $url_parts = wp_parse_url(home_url());
    $domain = isset($url_parts['host']) ? $url_parts['host'] : '';
    aicg_debug_log('Domain: ' . $domain);

    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        aicg_debug_log('Title/tagline generation failed: OpenAI API key is not set');
        wp_send_json_error('OpenAI API key is not set.');
    }

    // Get language from plugin settings
    $language_code = get_option('ang_language', 'en');
    $language = get_language_name_from_code($language_code);
    aicg_debug_log('Language: ' . $language);

    $prompt = "Come up with a creative website title and a short tagline for a website with the domain \"$domain\". The answer must be in $language. Respond in the format: Title - Tagline";

    $response = aicg_openai_chat_request($api_key, $prompt);
    if (!$response) {
        aicg_debug_log('Title/tagline generation failed: OpenAI request failed');
        wp_send_json_error('OpenAI request failed.');
    }
    
    aicg_debug_log('API responded: ' . $response);

    $parts = explode(' - ', $response, 2);
    $title = trim($parts[0] ?? '');
    $tagline = trim($parts[1] ?? '');
    
    aicg_debug_log('Generated title: ' . $title);
    aicg_debug_log('Generated tagline: ' . $tagline);

    if ($title) update_option('aicg_site_name', $title);
    if ($tagline) update_option('blogdescription', $tagline);
    
    aicg_debug_log('Title and tagline saved to settings');

    wp_send_json_success(esc_html($title . ' - ' . $tagline) . '<br><i>Saved to site settings!</i>');
}
add_action('wp_ajax_aicg_generate_logo', 'aicg_generate_logo');
function aicg_generate_logo() {
    // Log with simple error_log first
    error_log('=== LOGO GENERATION STARTED ===');
    
    check_ajax_referer('aicg_generate_logo');
    
    error_log('✓ Nonce verified');
    
    if (!current_user_can('manage_options')) {
        error_log('✗ Insufficient permissions');
        aicg_debug_log('Logo generation failed: Insufficient permissions');
        wp_send_json_error('Insufficient permissions.');
    }
    
    error_log('✓ User has manage_options');
    
    $api_key = get_option('aicg_api_key');
    error_log('API Key length: ' . strlen($api_key));
    
    if (!$api_key) {
        error_log('✗ No API key found');
        aicg_debug_log('Logo generation failed: OpenAI API key is not set');
        wp_send_json_error('OpenAI API key is not set.');
    }
    
    error_log('✓ API key found');
    aicg_debug_log('Starting logo generation...');
    
    $tagline = get_option('blogdescription', '');
    error_log('Tagline: ' . $tagline);
    aicg_debug_log('Tagline: ' . $tagline);
    
    // Get site name from plugin settings, fallback to WordPress blogname
    $title = get_option('aicg_site_name', '');
    if (empty($title)) {
        $title = get_option('blogname', '');
    }
    error_log('Site title: ' . $title);
    aicg_debug_log('Site title: ' . $title);
    
    // Get main content/design prompt for more customized logo
    $custom_prompt = get_option('ang_site_topic', '');
    error_log('Custom prompt length: ' . strlen($custom_prompt));
    
    if (!empty($custom_prompt)) {
        // Truncate custom prompt to prevent exceeding DALL-E's 1000 char limit
        // Keep room for the rest of the prompt (base text + title + tagline = ~150 chars)
        $max_custom_length = 600;
        if (strlen($custom_prompt) > $max_custom_length) {
            $custom_prompt = substr($custom_prompt, 0, $max_custom_length) . '...';
            error_log('Truncated custom prompt to ' . $max_custom_length . ' chars');
            aicg_debug_log('Truncated custom prompt to ' . $max_custom_length . ' chars');
        }
        
        // Use custom prompt to guide logo design
        $prompt = "Create a modern, professional logo for a website called \"$title\" based on this context: \"$custom_prompt\". The tagline is \"$tagline\". The logo should be on a transparent background, PNG format, and suitable for website use.";
        error_log('Using custom prompt for logo');
        aicg_debug_log('Using custom prompt for logo');
    } else {
        // Default logo prompt
        $prompt = "Create a modern, minimalistic logo for a website called \"$title\" with the tagline \"$tagline\". The logo should be on a transparent background, PNG format, and suitable for website use.";
        error_log('Using default prompt for logo');
        aicg_debug_log('Using default prompt for logo');
    }

    error_log('Prompt length: ' . strlen($prompt));
    aicg_debug_log('Logo prompt: ' . substr($prompt, 0, 100) . '...');
    aicg_debug_log('Calling image generation API...');
    
    error_log('Calling aicg_generate_image_from_prompt...');
    $image_url = aicg_generate_image_from_prompt($api_key, $prompt, 512, 512);
    
    error_log('Image URL returned: ' . ($image_url ? 'Yes (' . strlen($image_url) . ' chars)' : 'No'));
    aicg_debug_log('Image URL result: ' . ($image_url ?: 'EMPTY'));
    
    if (!$image_url) {
        error_log('✗ Image generation failed - no URL returned');
        aicg_debug_log('Logo generation failed: Image URL is empty. Check API response.');
        wp_send_json_error('Logo generation failed. Check logs for details.');
    }
    
    error_log('✓ Image generated: ' . $image_url);
    aicg_debug_log('Image generated successfully: ' . $image_url);
    
    // Save as site logo (WordPress custom_logo)
    aicg_debug_log('Attempting to save logo to media library...');
    error_log('Saving to media library...');
    
    $attachment_id = aicg_media_handle_sideload($image_url, 0, $title . ' Logo');
    
    error_log('Attachment ID: ' . ($attachment_id ?: 'Failed'));
    
    if ($attachment_id && !is_wp_error($attachment_id)) {
        set_theme_mod('custom_logo', $attachment_id);
        error_log('✓ Logo saved with ID: ' . $attachment_id);
        aicg_debug_log('Logo saved with attachment ID: ' . $attachment_id);
    } else {
        $error_msg = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'Unknown error';
        error_log('✗ Failed to save: ' . $error_msg);
        aicg_debug_log('Failed to save logo to media library: ' . $error_msg);
    }
    
    error_log('=== LOGO GENERATION COMPLETED ===');
    wp_send_json_success(['url' => $image_url]);
}

add_action('wp_ajax_aicg_generate_favicon', 'aicg_generate_favicon');
function aicg_generate_favicon() {
    check_ajax_referer('aicg_generate_favicon');
    
    aicg_debug_log('Starting favicon generation...');
    
    if (!current_user_can('manage_options')) {
        aicg_debug_log('Favicon generation failed: Insufficient permissions');
        wp_send_json_error('Insufficient permissions.');
    }
    
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        aicg_debug_log('Favicon generation failed: OpenAI API key is not set');
        wp_send_json_error('OpenAI API key is not set.');
    }
    
    $tagline = get_option('blogdescription', '');
    aicg_debug_log('Favicon tagline: ' . $tagline);
    
    // Get site name from plugin settings, fallback to WordPress blogname
    $title = get_option('aicg_site_name', '');
    if (empty($title)) {
        $title = get_option('blogname', '');
    }
    aicg_debug_log('Favicon site title: ' . $title);
    
    $prompt = "Create a simple, recognizable favicon for a website called \"$title\" with the tagline \"$tagline\". The favicon should be 64x64 PNG, transparent background, and suitable for browser tabs.";

    $image_url = aicg_generate_image_from_prompt($api_key, $prompt, 64, 64);
    if (!$image_url) {
        aicg_debug_log('Favicon generation failed: Image URL is empty');
        wp_send_json_error('Favicon generation failed. Check logs for details.');
    }
    
    aicg_debug_log('Favicon generated: ' . $image_url);
    
    // Save as site icon (WordPress site_icon)
    $attachment_id = aicg_media_handle_sideload($image_url, 0, $title . ' Favicon');
    if ($attachment_id && !is_wp_error($attachment_id)) {
        update_option('site_icon', $attachment_id);
        aicg_debug_log('Favicon saved with attachment ID: ' . $attachment_id);
    } else {
        $error_msg = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'Unknown error';
        aicg_debug_log('Failed to save favicon: ' . $error_msg);
    }
    
    wp_send_json_success(['url' => $image_url]);
}
add_action('wp_ajax_aicg_use_logo_as_favicon', 'aicg_use_logo_as_favicon');
function aicg_use_logo_as_favicon() {
    check_ajax_referer('aicg_use_logo_as_favicon');
    
    aicg_debug_log('Converting logo to favicon...');
    
    if (!current_user_can('manage_options')) {
        aicg_debug_log('Logo to favicon conversion failed: Insufficient permissions');
        wp_send_json_error('Insufficient permissions.');
    }
    
    $logo_id = get_theme_mod('custom_logo');
    aicg_debug_log('Logo ID: ' . ($logo_id ? $logo_id : 'None found'));
    
    if (!$logo_id) {
        aicg_debug_log('Logo to favicon conversion failed: No logo set');
        wp_send_json_error('No logo set.');
    }
    
    update_option('site_icon', $logo_id);
    aicg_debug_log('Logo successfully set as favicon. Icon ID: ' . $logo_id);
    
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
    
    // Get main content prompt from Settings - used for all generation (articles, pages, logos, theme)
    $custom_prompt = get_option('ang_site_topic', '');
    
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
