<?php
/**
 * Core functionality for UP AI Toolkit
 *
 * @package UP_AI_Toolkit
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UPAI_Core {
    
    /**
     * Plugin settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option( 'upai_settings', array() );
    }
    
    /**
     * Get plugin settings
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Get specific setting
     */
    public function get_setting( $key, $default = null ) {
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }
    
    /**
     * Update plugin settings
     */
    public function update_settings( $settings ) {
        $this->settings = $settings;
        return update_option( 'upai_settings', $settings );
    }
    
    /**
     * Update specific setting
     */
    public function update_setting( $key, $value ) {
        $this->settings[ $key ] = $value;
        return update_option( 'upai_settings', $this->settings );
    }
    
    /**
     * Get configured AI providers
     */
    public function get_providers() {
        return $this->get_setting( 'providers', array() );
    }
    
    /**
     * Get specific provider configuration
     */
    public function get_provider( $provider_id ) {
        $providers = $this->get_providers();
        return isset( $providers[ $provider_id ] ) ? $providers[ $provider_id ] : null;
    }
    
    /**
     * Add or update AI provider
     */
    public function save_provider( $provider_id, $config ) {
        $providers = $this->get_providers();
        $providers[ $provider_id ] = $config;
        return $this->update_setting( 'providers', $providers );
    }
    
    /**
     * Delete AI provider
     */
    public function delete_provider( $provider_id ) {
        $providers = $this->get_providers();
        if ( isset( $providers[ $provider_id ] ) ) {
            unset( $providers[ $provider_id ] );
            return $this->update_setting( 'providers', $providers );
        }
        return false;
    }
    
    /**
     * Get default provider
     */
    public function get_default_provider() {
        $default = $this->get_setting( 'default_provider' );
        if ( $default && $this->get_provider( $default ) ) {
            return $default;
        }
        
        // Return first available provider
        $providers = $this->get_providers();
        if ( ! empty( $providers ) ) {
            return array_key_first( $providers );
        }
        
        return null;
    }
    
    /**
     * Get post content cleaned for AI processing
     */
    public function get_post_content( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return '';
        }
        
        $content = $post->post_content;
        
        // Apply WordPress content filters
        $content = apply_filters( 'upai_pre_post_content', $content, $post_id );
        
        // Remove shortcodes
        $content = strip_shortcodes( $content );
        
        // Remove Gutenberg block comments
        $content = preg_replace( '/<!--\s*\/?wp:[^\>]+-->/', '', $content );
        
        // Strip HTML tags
        $content = wp_strip_all_tags( $content );
        
        // Normalize whitespace
        $content = preg_replace( '/\s+/', ' ', $content );
        $content = trim( $content );
        
        return apply_filters( 'upai_post_content', $content, $post_id );
    }
    
    /**
     * Parse Gutenberg blocks from post content
     */
    public function parse_blocks( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }
        
        // Use WordPress built-in block parser
        if ( function_exists( 'parse_blocks' ) ) {
            return parse_blocks( $post->post_content );
        }
        
        return array();
    }
    
    /**
     * Get text content from a specific block
     */
    public function get_block_text_content( $block ) {
        $text = '';
        
        // Extract text from common block attributes
        if ( isset( $block['attrs']['content'] ) ) {
            $text .= wp_strip_all_tags( $block['attrs']['content'] ) . ' ';
        }
        
        // Extract from innerHTML
        if ( isset( $block['innerHTML'] ) ) {
            $text .= wp_strip_all_tags( $block['innerHTML'] ) . ' ';
        }
        
        // Recursively process inner blocks
        if ( ! empty( $block['innerBlocks'] ) ) {
            foreach ( $block['innerBlocks'] as $inner_block ) {
                $text .= $this->get_block_text_content( $inner_block ) . ' ';
            }
        }
        
        return trim( $text );
    }
    
    /**
     * Log message if logging is enabled
     */
    public function log( $message, $level = 'info' ) {
        if ( $this->get_setting( 'log_requests', false ) ) {
            error_log( sprintf( '[UP AI Toolkit %s] %s', strtoupper( $level ), $message ) );
        }
    }
    
    /**
     * Check if user has permission to use AI features
     */
    public function user_can_use_ai() {
        return current_user_can( 'edit_posts' );
    }
    
    /**
     * Check if user can manage AI settings
     */
    public function user_can_manage_settings() {
        return current_user_can( 'manage_options' );
    }
}
