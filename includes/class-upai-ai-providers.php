<?php
/**
 * AI Providers Manager
 * Handles communication with multiple AI providers (OpenAI, Gemini, Mistral, etc.)
 *
 * @package UP_AI_Toolkit
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UPAI_AI_Providers {
    
    /**
     * Supported AI providers configuration
     */
    private static $supported_providers = array(
        'openai' => array(
            'name' => 'OpenAI (ChatGPT)',
            'api_url' => 'https://api.openai.com/v1/chat/completions',
            'models' => array( 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo' ),
            'default_model' => 'gpt-4o-mini',
            'requires' => array( 'api_key' ),
        ),
        'gemini' => array(
            'name' => 'Google Gemini',
            'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
            'models' => array( 'gemini-2.0-flash-exp', 'gemini-1.5-pro', 'gemini-1.5-flash' ),
            'default_model' => 'gemini-2.0-flash-exp',
            'requires' => array( 'api_key' ),
        ),
        'mistral' => array(
            'name' => 'Mistral AI',
            'api_url' => 'https://api.mistral.ai/v1/chat/completions',
            'models' => array( 'mistral-large-latest', 'mistral-medium-latest', 'mistral-small-latest' ),
            'default_model' => 'mistral-small-latest',
            'requires' => array( 'api_key' ),
        ),
        'anthropic' => array(
            'name' => 'Anthropic Claude',
            'api_url' => 'https://api.anthropic.com/v1/messages',
            'models' => array( 'claude-3-5-sonnet-20241022', 'claude-3-5-haiku-20241022', 'claude-3-opus-20240229' ),
            'default_model' => 'claude-3-5-haiku-20241022',
            'requires' => array( 'api_key' ),
        ),
        'openrouter' => array(
            'name' => 'OpenRouter',
            'api_url' => 'https://openrouter.ai/api/v1/chat/completions',
            'models' => array( 'openrouter/auto' ), // Meta-model; use full IDs for specifics
            'default_model' => 'openrouter/auto',
            'requires' => array( 'api_key' ),
        ),
    );
    
    /**
     * Get list of supported providers
     */
    public static function get_supported_providers() {
        return self::$supported_providers;
    }
    
    /**
     * Get provider configuration
     */
    public static function get_provider_info( $provider_id ) {
        return isset( self::$supported_providers[ $provider_id ] ) ? self::$supported_providers[ $provider_id ] : null;
    }
    
    /**
     * Send request to AI provider
     */
    public static function send_request( $provider_config, $prompt, $options = array() ) {
        $provider_type = $provider_config['type'];
        $provider_info = self::get_provider_info( $provider_type );
        
        if ( ! $provider_info ) {
            return new WP_Error( 'invalid_provider', __( 'Invalid AI provider', 'up-ai-toolkit' ) );
        }
        
        // Build request based on provider type
        switch ( $provider_type ) {
            case 'openai':
                return self::send_openai_request( $provider_config, $prompt, $options );
            
            case 'gemini':
                return self::send_gemini_request( $provider_config, $prompt, $options );
            
            case 'mistral':
                return self::send_mistral_request( $provider_config, $prompt, $options );
            
            case 'anthropic':
                return self::send_anthropic_request( $provider_config, $prompt, $options );
            
            case 'openrouter':
                return self::send_openrouter_request( $provider_config, $prompt, $options );
            
            default:
                return new WP_Error( 'unsupported_provider', __( 'Unsupported AI provider', 'up-ai-toolkit' ) );
        }
    }
    
    /**
     * Send request to OpenAI (ChatGPT)
     */
    private static function send_openai_request( $provider_config, $prompt, $options ) {
        $api_key = $provider_config['api_key'];
        $model = isset( $options['model'] ) ? $options['model'] : $provider_config['model'];
        $temperature = isset( $options['temperature'] ) ? $options['temperature'] : 0.7;
        $max_tokens = isset( $options['max_tokens'] ) ? $options['max_tokens'] : 2000;
        $timeout = isset( $options['timeout'] ) ? intval( $options['timeout'] ) : 60;
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        );
        
        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $body ),
            'timeout' => $timeout,
        ) );
        
        return self::process_openai_response( $response );
    }
    
    /**
     * Send request to Google Gemini
     */
    private static function send_gemini_request( $provider_config, $prompt, $options ) {
        $api_key = $provider_config['api_key'];
        $model = isset( $options['model'] ) ? $options['model'] : $provider_config['model'];
        $timeout = isset( $options['timeout'] ) ? intval( $options['timeout'] ) : 60;
        
        $api_url = str_replace( '{model}', $model, 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent' );
        $api_url .= '?key=' . $api_key;
        
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt ),
                    ),
                ),
            ),
        );
        
        $response = wp_remote_post( $api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $body ),
            'timeout' => $timeout,
        ) );
        
        return self::process_gemini_response( $response );
    }
    
    /**
     * Send request to Mistral AI
     */
    private static function send_mistral_request( $provider_config, $prompt, $options ) {
        $api_key = $provider_config['api_key'];
        $model = isset( $options['model'] ) ? $options['model'] : $provider_config['model'];
        $temperature = isset( $options['temperature'] ) ? $options['temperature'] : 0.7;
        $max_tokens = isset( $options['max_tokens'] ) ? $options['max_tokens'] : 2000;
        $timeout = isset( $options['timeout'] ) ? intval( $options['timeout'] ) : 60;
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        );
        
        $response = wp_remote_post( 'https://api.mistral.ai/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $body ),
            'timeout' => $timeout,
        ) );
        
        return self::process_openai_response( $response ); // Mistral uses OpenAI-compatible format
    }
    
    /**
     * Send request to Anthropic Claude
     */
    private static function send_anthropic_request( $provider_config, $prompt, $options ) {
        $api_key = $provider_config['api_key'];
        $model = isset( $options['model'] ) ? $options['model'] : $provider_config['model'];
        $max_tokens = isset( $options['max_tokens'] ) ? $options['max_tokens'] : 2000;
        $timeout = isset( $options['timeout'] ) ? intval( $options['timeout'] ) : 60;
        
        $body = array(
            'model' => $model,
            'max_tokens' => $max_tokens,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
        );
        
        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode( $body ),
            'timeout' => $timeout,
        ) );
        
        return self::process_anthropic_response( $response );
    }
    
    /**
     * Send request to OpenRouter
     */
    private static function send_openrouter_request( $provider_config, $prompt, $options ) {
        $api_key = $provider_config['api_key'];
        $model = isset( $options['model'] ) ? $options['model'] : $provider_config['model'];
        // Normalize 'auto' to the valid OpenRouter meta-model
        if ( strtolower( trim( $model ) ) === 'auto' ) {
            $model = 'openrouter/auto';
        }
        $temperature = isset( $options['temperature'] ) ? $options['temperature'] : 0.7;
        $max_tokens = isset( $options['max_tokens'] ) ? $options['max_tokens'] : 2000;
        $timeout = isset( $options['timeout'] ) ? intval( $options['timeout'] ) : 60;
        
        $body = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
        );
        
        $response = wp_remote_post( 'https://openrouter.ai/api/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Referer' => home_url(),
                'X-Title' => get_bloginfo( 'name', 'display' ),
            ),
            'body' => wp_json_encode( $body ),
            'timeout' => $timeout,
        ) );
        
        // OpenRouter uses OpenAI-compatible format, but return clearer error if invalid model
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $body_raw = wp_remote_retrieve_body( $response );
        $data = json_decode( $body_raw, true );
        if ( isset( $data['error'] ) ) {
            $message = $data['error']['message'] ?? 'API error';
            if ( stripos( $message, 'model' ) !== false && stripos( $message, 'not' ) !== false ) {
                $message .= " — Tip: use a full model ID like 'openrouter/auto' or 'deepseek/deepseek-chat'.";
            }
            return new WP_Error( 'api_error', $message );
        }
        // Fallback to generic OpenAI-compatible handler
        return self::process_openai_response( $response );
    }
    
    /**
     * Process OpenAI-compatible response
     */
    private static function process_openai_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['error'] ) ) {
            return new WP_Error( 'api_error', $data['error']['message'] );
        }
        
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            return array(
                'success' => true,
                'content' => trim( $data['choices'][0]['message']['content'] ),
                'model' => isset( $data['model'] ) ? $data['model'] : '',
                'usage' => isset( $data['usage'] ) ? $data['usage'] : array(),
            );
        }
        
        return new WP_Error( 'invalid_response', __( 'Invalid API response', 'up-ai-toolkit' ) );
    }
    
    /**
     * Process Gemini response
     */
    private static function process_gemini_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['error'] ) ) {
            return new WP_Error( 'api_error', $data['error']['message'] );
        }
        
        if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return array(
                'success' => true,
                'content' => trim( $data['candidates'][0]['content']['parts'][0]['text'] ),
                'model' => isset( $data['modelVersion'] ) ? $data['modelVersion'] : '',
                'usage' => isset( $data['usageMetadata'] ) ? $data['usageMetadata'] : array(),
            );
        }
        
        return new WP_Error( 'invalid_response', __( 'Invalid API response', 'up-ai-toolkit' ) );
    }
    
    /**
     * Process Anthropic response
     */
    private static function process_anthropic_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( isset( $data['error'] ) ) {
            return new WP_Error( 'api_error', $data['error']['message'] );
        }
        
        if ( isset( $data['content'][0]['text'] ) ) {
            return array(
                'success' => true,
                'content' => trim( $data['content'][0]['text'] ),
                'model' => isset( $data['model'] ) ? $data['model'] : '',
                'usage' => isset( $data['usage'] ) ? $data['usage'] : array(),
            );
        }
        
        return new WP_Error( 'invalid_response', __( 'Invalid API response', 'up-ai-toolkit' ) );
    }
}
