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
        'timeout' => 60,  // Increased from 30 to 60 seconds
    ];

    // Retry logic for failed requests
    $max_retries = 3;
    $retry_count = 0;
    
    while ($retry_count < $max_retries) {
        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            $retry_count++;
            if ($retry_count >= $max_retries) {
                error_log('AI WP GEN - API Error (after ' . $max_retries . ' retries): ' . $response->get_error_message());
                return false;
            }
            error_log('AI WP GEN - API Error (retry ' . $retry_count . '): ' . $response->get_error_message());
            sleep(2); // Wait 2 seconds before retry
            continue;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);

        // Check for rate limiting (429) or server errors (5xx)
        if ($status === 429 || ($status >= 500 && $status < 600)) {
            $retry_count++;
            if ($retry_count >= $max_retries) {
                error_log('AI WP GEN - API Status ' . $status . ' (after ' . $max_retries . ' retries): ' . print_r($json, true));
                return false;
            }
            error_log('AI WP GEN - API Status ' . $status . ' (retry ' . $retry_count . '), waiting 3 seconds...');
            sleep(3); // Wait 3 seconds before retry on rate limit
            continue;
        }

        // Non-retryable error
        if ($status !== 200) {
            error_log('AI WP GEN - API Status ' . $status . ': ' . print_r($json, true));
            return false;
        }

        // Check for API errors in response
        if (!empty($json['error'])) {
            error_log('AI WP GEN - API Error Response: ' . print_r($json['error'], true));
            return false;
        }

        // Check if we got valid content
        if (!empty($json['choices'][0]['message']['content'])) {
            return trim($json['choices'][0]['message']['content']);
        }

        // Empty content response - try again
        $retry_count++;
        if ($retry_count >= $max_retries) {
            error_log('AI WP GEN - No content in API response (after ' . $max_retries . ' attempts): ' . print_r($json, true));
            return false;
        }
        error_log('AI WP GEN - Empty content response (retry ' . $retry_count . '), waiting 2 seconds...');
        sleep(2);
    }

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
        'timeout' => 60,  // Increased from previous
    ];
    
    // Retry logic
    $max_retries = 2;
    $retry_count = 0;
    
    while ($retry_count < $max_retries) {
        $response = wp_remote_post($endpoint, $args);
        
        if (is_wp_error($response)) {
            $retry_count++;
            if ($retry_count >= $max_retries) {
                error_log('AI WP GEN - Image Generation Error (after ' . $max_retries . ' retries): ' . $response->get_error_message());
                return false;
            }
            error_log('AI WP GEN - Image Generation Error (retry ' . $retry_count . '): ' . $response->get_error_message());
            sleep(2);
            continue;
        }
        
        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Handle rate limiting
        if ($status === 429) {
            $retry_count++;
            if ($retry_count >= $max_retries) {
                error_log('AI WP GEN - Image API Rate Limited (after retries)');
                return false;
            }
            error_log('AI WP GEN - Image API Rate Limited, waiting 5 seconds...');
            sleep(5);
            continue;
        }
        
        if ($status !== 200) {
            error_log('AI WP GEN - Image API Status ' . $status . ': ' . print_r($body, true));
            return false;
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
    
    return false;
}