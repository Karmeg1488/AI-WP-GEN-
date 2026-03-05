<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'openai-helper.php';

/**
 * Generate images for posts having meta 'aicg_image_needed' = 1
 */
function aicg_generate_images_for_articles() {
    $api_key = get_option('aicg_api_key');
    if (!$api_key) {
        return 'OpenAI API key is not set.';
    }

    
    // First try posts marked as needing images
    $args = [
        'post_type' => 'post',
        'posts_per_page' => 10,
        'meta_key' => 'aicg_image_needed',
        'meta_value' => '1',
        'post_status' => 'publish',
    ];

    $query = new WP_Query($args);

    // Fallback: if none found, process latest posts without thumbnails
    if (!$query->have_posts()) {
        $args = [
            'post_type' => 'post',
            'posts_per_page' => 10,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];
        $query = new WP_Query($args);
    }

    $count = 0;

    if (!$query->have_posts()) {
        return 'No posts require image generation.';
    }

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        $title = get_the_title();

        // Generate image via OpenAI DALL·E
        $image_url = aicg_generate_image_from_title($api_key, $title);

        if ($image_url) {
            // Download and add to media library
            $attachment_id = aicg_media_handle_sideload($image_url, $post_id);

            if ($attachment_id && !is_wp_error($attachment_id)) {
                set_post_thumbnail($post_id, $attachment_id);
                // Remove meta flag to avoid duplicate generation
                delete_post_meta($post_id, 'aicg_image_needed');
                $count++;
            }
        }
    }

    wp_reset_postdata();

    return "Thumbnails generated and added: {$count}.";
}

/**
 * Request OpenAI DALL·E to generate image based on post title
 */
function aicg_generate_image_from_title($api_key, $title) {
    $endpoint = 'https://api.openai.com/v1/images/generations';

    $styles = [
        "A professional, high-quality photojournalistic image depicting {$title}. Style: realistic, modern professional photography, natural lighting, contemporary 2020s setting, editorial quality, high-detail, sharp focus, clear composition. No logos, text, or watermarks. Cinematic widescreen composition",
        "A modern, cinematic press photograph about {$title}. Style: professional news photography, documentary style, realistic lighting, contemporary setting, clear focused composition, clean professional background. No text or logos. High quality editorial standard",
        "A vivid, engaging professional photograph illustrating {$title}. Style: professional journalistic photography, natural light setup, contemporary aesthetics, compelling visual composition, sharp and detailed. No watermarks, logos, or text. News-worthy quality",
        "A sophisticated current news photograph featuring {$title}. Style: documentary realism, professional quality, modern natural lighting, clear subject focus, clean composition, suitable for publication. High resolution, no graphics. Engaging professional visual",
        "A striking professional editorial image about {$title}. Style: high-quality editorial photography, realistic rendering, professional lighting, contemporary styling, subject-focused composition. No logos or text. Cinematic quality, sharp details"
    ];
    $style = $styles[array_rand($styles)];

    $data = [
        'prompt' => $style,
        'n' => 1,
        'size' => '512x512',
        'response_format' => 'url',
    ];

    $args = [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'timeout' => 60,
    ];

    $response = wp_remote_post($endpoint, $args);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);

    if (!empty($json['data'][0]['url'])) {
        return esc_url_raw($json['data'][0]['url']);
    }

    return false;
}

/**
 * Download image from URL and add it to the WordPress media library
 */
function aicg_media_handle_sideload($image_url, $post_id = 0, $desc = '') {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Create a temporary file from URL
    $tmp = download_url($image_url);
    if (is_wp_error($tmp)) {
        return false;
    }

    // File name from URL
    $file_array = [];
    $url_parts = wp_parse_url($image_url);
    $file_array['name'] = isset($url_parts['path']) ? basename($url_parts['path']) : '';
    $file_array['tmp_name'] = $tmp;

    // Add file to media library
    $attachment_id = media_handle_sideload($file_array, $post_id, $desc);

    // On error, delete temp file
    if (is_wp_error($attachment_id)) {
        wp_delete_file($tmp);
        return false;
    }

    return $attachment_id;
}