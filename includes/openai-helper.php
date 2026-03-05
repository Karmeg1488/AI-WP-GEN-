<?php
if (!defined('ABSPATH')) exit;

function aicg_openai_chat_request($api_key, $prompt) {
    $endpoint = 'https://api.openai.com/v1/chat/completions';

    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 1500,
        'temperature' => 0.7,
    ];

    $args = [
        'body' => json_encode($data),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'timeout' => 30,
    ];

    $response = wp_remote_post($endpoint, $args);

    if (is_wp_error($response)) {
        error_log('AI WP GEN - API Error: ' . $response->get_error_message());
        return false;
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);

    // Log non-200 status codes
    if ($status !== 200) {
        error_log('AI WP GEN - API Status ' . $status . ': ' . print_r($json, true));
    }

    // Check for API errors in response
    if (!empty($json['error'])) {
        error_log('AI WP GEN - API Error Response: ' . print_r($json['error'], true));
        return false;
    }

    if (!empty($json['choices'][0]['message']['content'])) {
        return trim($json['choices'][0]['message']['content']);
    }

    error_log('AI WP GEN - No content in API response: ' . print_r($json, true));
    return false;
}

/**
 * Generate a list of real names via OpenAI
 */
function aicg_openai_generate_names($api_key, $count) {
    $prompt = "Generate {$count} unique full names (first and last) in English, formatted as a JSON array of strings, for example: [\"John Smith\", \"Anna Brown\"]";

    $response = aicg_openai_chat_request($api_key, $prompt);
    if (!$response) {
        error_log('AI WP GEN - Failed to generate names');
        return [];
    }

    $names = json_decode($response, true);
    if (!is_array($names)) {
        $lines = explode("\n", $response);
        $names = array_filter(array_map('trim', $lines));
    }

    return $names;
}

// Deprecated: Use aicg_openai_chat_request() instead
// This function is kept for backward compatibility but should not be used
function aicg_openai_completion_request($prompt, $n = 1, $max_tokens = 60) {
    _deprecated_function(__FUNCTION__, '1.5.43', 'aicg_openai_chat_request');
    return aicg_openai_chat_request(get_option('aicg_api_key'), $prompt);
}

function aicg_openai_request($api_key, $prompt) {
    // Use chat model by default
    $response = aicg_openai_chat_request($api_key, $prompt);
    return $response ?: false;
}
function aicg_generate_image_from_prompt($api_key, $prompt, $width = 512, $height = 512) {
    $endpoint = 'https://api.openai.com/v1/images/generations';
    $data = [
        'prompt' => $prompt,
        'n' => 1,
        'size' => "{$width}x{$height}",
        'response_format' => 'url'
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
        error_log('AI WP GEN - Image Generation Error: ' . $response->get_error_message());
        return false;
    }
    
    $status = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($status !== 200) {
        error_log('AI WP GEN - Image API Status ' . $status . ': ' . print_r($body, true));
    }
    
    if (!empty($body['error'])) {
        error_log('AI WP GEN - Image API Error: ' . print_r($body['error'], true));
        return false;
    }
    
    if (!empty($body['data'][0]['url'])) {
        return $body['data'][0]['url'];
    }
    
    error_log('AI WP GEN - No image URL in response: ' . print_r($body, true));
    return false;
}