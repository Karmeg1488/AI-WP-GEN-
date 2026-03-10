<?php
if (!defined('ABSPATH')) exit;

function aicg_openai_chat_request($api_key, $prompt) {
    $endpoint = 'https://api.openai.com/v1/chat/completions';

    $data = [
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 2500,
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
                aicg_debug_log('API Error (after ' . $max_retries . ' retries): ' . $response->get_error_message());
                return false;
            }
            aicg_debug_log('API Error (retry ' . $retry_count . '): ' . $response->get_error_message());
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
                aicg_debug_log('API Status ' . $status . ' (after ' . $max_retries . ' retries): ' . print_r($json, true));
                return false;
            }
            aicg_debug_log('API Status ' . $status . ' (retry ' . $retry_count . '), waiting 3 seconds...');
            sleep(3); // Wait 3 seconds before retry on rate limit
            continue;
        }

        // Non-retryable error
        if ($status !== 200) {
            aicg_debug_log('API Status ' . $status . ': ' . print_r($json, true));
            return false;
        }

        // Check for API errors in response
        if (!empty($json['error'])) {
            aicg_debug_log('API Error Response: ' . print_r($json['error'], true));
            return false;
        }

        // Check if we got valid content
        if (!empty($json['choices'][0]['message']['content'])) {
            $content = trim($json['choices'][0]['message']['content']);
            
            // Validate that we got meaningful content (not just a few words)
            if (strlen($content) > 50) {  // At least 50 characters
                return $content;
            } else {
                aicg_debug_log('Content too short (' . strlen($content) . ' chars): ' . $content);
            }
        }

        // Empty or too short content response - try again
        $retry_count++;
        if ($retry_count >= $max_retries) {
            aicg_debug_log('No valid content in API response (after ' . $max_retries . ' attempts): ' . print_r($json, true));
            return false;
        }
        aicg_debug_log('Empty/invalid content response (retry ' . $retry_count . '), waiting 2 seconds...');
        sleep(2);
    }

    return false;
}

/**
 * Generate a list of real names via OpenAI
 */
function aicg_openai_generate_names($api_key, $count) {
    $prompt = "Generate exactly {$count} unique full names (first and last) in English, formatted as a JSON array of strings, for example: [\"John Smith\", \"Anna Brown\"]. Return ONLY the JSON array, nothing else.";

    $response = aicg_openai_chat_request($api_key, $prompt);
    aicg_debug_log('Raw OpenAI response for names: ' . substr($response, 0, 200));
    
    if (!$response) {
        aicg_debug_log('Failed to generate names - empty response');
        return [];
    }

    // Try to extract JSON from response
    $names = [];
    
    // First, try direct JSON decode
    $decoded = json_decode($response, true);
    if (is_array($decoded)) {
        $names = array_filter(array_map(function($name) {
            return is_string($name) ? trim($name) : null;
        }, $decoded));
        aicg_debug_log('Successfully decoded JSON, got ' . count($names) . ' names');
    } else {
        // Try to extract JSON array from response
        if (preg_match('/\[.*\]/s', $response, $matches)) {
            $json_str = $matches[0];
            $decoded = json_decode($json_str, true);
            if (is_array($decoded)) {
                $names = array_filter(array_map(function($name) {
                    return is_string($name) ? trim($name) : null;
                }, $decoded));
                aicg_debug_log('Extracted JSON array, got ' . count($names) . ' names');
            }
        }
        
        // If still no names, try splitting by newlines
        if (empty($names)) {
            $lines = explode("\n", $response);
            $names = array_filter(array_map('trim', $lines));
            aicg_debug_log('Split by newlines, got ' . count($names) . ' names');
        }
    }

    if (empty($names)) {
        aicg_debug_log('WARNING: No names extracted from response: ' . $response);
        return [];
    }

    aicg_debug_log('Generated names: ' . json_encode($names));
    return array_values($names); // Re-index array
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
    
    // Safety check: DALL-E API has a 1000 character limit for prompts
    $max_prompt_length = 1000;
    if (strlen($prompt) > $max_prompt_length) {
        error_log('🖼️ IMAGE API: Prompt too long (' . strlen($prompt) . ' chars), truncating to ' . $max_prompt_length);
        aicg_debug_log('Prompt exceeded 1000 char limit (' . strlen($prompt) . ' chars), truncating');
        $prompt = substr($prompt, 0, $max_prompt_length);
    }
    
    error_log('🖼️ IMAGE API: Starting image generation');
    error_log('🖼️ IMAGE API: Size=' . $width . 'x' . $height);
    error_log('🖼️ IMAGE API: Prompt length=' . strlen($prompt));
    aicg_debug_log('Generating image with size: ' . $width . 'x' . $height);
    aicg_debug_log('Image prompt: ' . substr($prompt, 0, 100) . '...');
    
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
    
    // Retry logic
    $max_retries = 2;
    $retry_count = 0;
    
    while ($retry_count < $max_retries) {
        error_log('🖼️ IMAGE API: Attempt ' . ($retry_count + 1) . '/' . $max_retries);
        
        $response = wp_remote_post($endpoint, $args);
        
        if (is_wp_error($response)) {
            $retry_count++;
            error_log('🖼️ IMAGE API: Connection error: ' . $response->get_error_message());
            if ($retry_count >= $max_retries) {
                aicg_debug_log('Image Generation Error (after ' . $max_retries . ' retries): ' . $response->get_error_message());
                return false;
            }
            aicg_debug_log('Image Generation Error (retry ' . $retry_count . '): ' . $response->get_error_message());
            sleep(2);
            continue;
        }
        
        $status = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        $body = json_decode($body_raw, true);
        
        error_log('🖼️ IMAGE API: Response status=' . $status);
        error_log('🖼️ IMAGE API: Response body length=' . strlen($body_raw));
        
        aicg_debug_log('Image API response status: ' . $status);
        
        // Handle rate limiting
        if ($status === 429) {
            $retry_count++;
            error_log('🖼️ IMAGE API: Rate limited (429)');
            if ($retry_count >= $max_retries) {
                aicg_debug_log('Image API Rate Limited (after retries)');
                return false;
            }
            aicg_debug_log('Image API Rate Limited, waiting 5 seconds...');
            sleep(5);
            continue;
        }
        
        if ($status !== 200) {
            error_log('🖼️ IMAGE API: Error status=' . $status . ', body=' . substr($body_raw, 0, 200));
            aicg_debug_log('Image API Status ' . $status . ': ' . print_r($body, true));
            return false;
        }
        
        if (!empty($body['error'])) {
            error_log('🖼️ IMAGE API: Error in response: ' . json_encode($body['error']));
            aicg_debug_log('Image API Error: ' . print_r($body['error'], true));
            return false;
        }
        
        if (!empty($body['data'][0]['url'])) {
            $image_url = $body['data'][0]['url'];
            error_log('🖼️ IMAGE API: ✓ SUCCESS - URL=' . $image_url);
            aicg_debug_log('Image generated successfully: ' . $image_url);
            return $image_url;
        }
        
        error_log('🖼️ IMAGE API: No URL in response. Body=' . substr($body_raw, 0, 200));
        aicg_debug_log('No image URL in response: ' . print_r($body, true));
        return false;
    }
    
    error_log('🖼️ IMAGE API: Max retries exceeded');
    aicg_debug_log('Image generation failed: Max retries exceeded');
    return false;
}