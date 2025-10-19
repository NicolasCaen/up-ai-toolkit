<?php
/**
 * REST API endpoints
 *
 * @package UP_AI_Toolkit
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UPAI_REST_API {
    
    /**
     * Core instance
     */
    private $core;
    
    /**
     * Content analyzer instance
     */
    private $content_analyzer;
    
    /**
     * API namespace
     */
    private $namespace = 'upai/v1';
    
    /**
     * Constructor
     */
    public function __construct( $core, $content_analyzer ) {
        $this->core = $core;
        $this->content_analyzer = $content_analyzer;
        
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Generate excerpt
        register_rest_route( $this->namespace, '/excerpt', array(
            'methods' => 'POST',
            'callback' => array( $this, 'generate_excerpt' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'provider_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'use_context' => array(
                    'required' => false,
                    'type' => 'boolean',
                ),
            ),
        ) );
        
        // Translate text
        register_rest_route( $this->namespace, '/translate', array(
            'methods' => 'POST',
            'callback' => array( $this, 'translate_text' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args' => array(
                'text' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'target_language' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'provider_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'use_context' => array(
                    'required' => false,
                    'type' => 'boolean',
                ),
            ),
        ) );
        
        // Modify text
        register_rest_route( $this->namespace, '/modify', array(
            'methods' => 'POST',
            'callback' => array( $this, 'modify_text' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args' => array(
                'text' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'prompt' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'provider_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'use_context' => array(
                    'required' => false,
                    'type' => 'boolean',
                ),
            ),
        ) );

        // Modify whole post coherently
        register_rest_route( $this->namespace, '/modify-post', array(
            'methods' => 'POST',
            'callback' => array( $this, 'modify_post' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'prompt' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'provider_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'use_context' => array(
                    'required' => false,
                    'type' => 'boolean',
                ),
            ),
        ) );
        
        // Extract block text (for testing)
        register_rest_route( $this->namespace, '/extract-block', array(
            'methods' => 'POST',
            'callback' => array( $this, 'extract_block_text' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'block_index' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
            ),
        ) );
        
        // Inject text into block (for testing)
        register_rest_route( $this->namespace, '/inject-text', array(
            'methods' => 'POST',
            'callback' => array( $this, 'inject_text' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'block_index' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'text' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ) );
        
        // Test provider connection
        register_rest_route( $this->namespace, '/test-provider', array(
            'methods' => 'POST',
            'callback' => array( $this, 'test_provider' ),
            'permission_callback' => array( $this, 'check_admin_permission' ),
            'args' => array(
                'provider_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );
        
        // Get available providers
        register_rest_route( $this->namespace, '/providers', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_providers' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // Generate SEO title and meta description
        register_rest_route( $this->namespace, '/seo-generate', array(
            'methods' => 'POST',
            'callback' => array( $this, 'generate_seo' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'sanitize_callback' => 'absint',
                ),
                'provider_id' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'what' => array(
                    'required' => false,
                    'type' => 'string', // 'both' | 'title' | 'description'
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'use_context' => array(
                    'required' => false,
                    'type' => 'boolean',
                ),
            ),
        ) );
    }
    
    /**
     * Check permission for REST API access
     */
    public function check_permission() {
        return $this->core->user_can_use_ai();
    }
    
    /**
     * Check admin permission for REST API access
     */
    public function check_admin_permission() {
        return $this->core->user_can_manage_settings();
    }
    
    /**
     * Generate excerpt endpoint
     */
    public function generate_excerpt( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $provider_id = $request->get_param( 'provider_id' );
        $use_context = $request->get_param( 'use_context' );
        $use_context = is_null( $use_context ) ? true : (bool) $use_context;
        
        $result = $this->content_analyzer->generate_excerpt( $post_id, $provider_id, array( 'use_context' => $use_context ) );
        
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => $result->get_error_message(),
            ), 400 );
        }
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $result,
        ), 200 );
    }

    /**
     * Modify whole post coherently endpoint
     */
    public function modify_post( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $prompt = $request->get_param( 'prompt' );
        $provider_id = $request->get_param( 'provider_id' );
        $use_context = $request->get_param( 'use_context' );
        $use_context = is_null( $use_context ) ? true : (bool) $use_context;

        $result = $this->content_analyzer->modify_post_coherently( $post_id, $prompt, $provider_id, array( 'use_context' => $use_context ) );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => $result->get_error_message(),
            ), 400 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data' => $result,
        ), 200 );
    }
    
    /**
     * Translate text endpoint
     */
    public function translate_text( $request ) {
        $text = $request->get_param( 'text' );
        $target_language = $request->get_param( 'target_language' );
        $provider_id = $request->get_param( 'provider_id' );
        $use_context = $request->get_param( 'use_context' );
        $use_context = is_null( $use_context ) ? true : (bool) $use_context;
        
        $result = $this->content_analyzer->translate_text( $text, $target_language, $provider_id, array( 'use_context' => $use_context ) );
        
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => $result->get_error_message(),
            ), 400 );
        }
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $result,
        ), 200 );
    }
    
    /**
     * Modify text endpoint
     */
    public function modify_text( $request ) {
        $text = $request->get_param( 'text' );
        $prompt = $request->get_param( 'prompt' );
        $provider_id = $request->get_param( 'provider_id' );
        $use_context = $request->get_param( 'use_context' );
        $use_context = is_null( $use_context ) ? true : (bool) $use_context;
        
        $result = $this->content_analyzer->modify_text( $text, $prompt, $provider_id, array( 'use_context' => $use_context ) );
        
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => $result->get_error_message(),
            ), 400 );
        }
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $result,
        ), 200 );
    }
    
    /**
     * Extract block text endpoint (for testing)
     */
    public function extract_block_text( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $block_index = $request->get_param( 'block_index' );
        
        $blocks = $this->core->parse_blocks( $post_id );
        
        if ( ! isset( $blocks[ $block_index ] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => __( 'Invalid block index', 'up-ai-toolkit' ),
            ), 400 );
        }
        
        $text = $this->content_analyzer->extract_block_text( $blocks[ $block_index ] );
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => array(
                'text' => $text,
                'block' => $blocks[ $block_index ],
            ),
        ), 200 );
    }
    
    /**
     * Inject text endpoint (for testing)
     */
    public function inject_text( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $block_index = $request->get_param( 'block_index' );
        $text = $request->get_param( 'text' );
        
        $result = $this->content_analyzer->inject_text_into_post( $post_id, $block_index, $text );
        
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => $result->get_error_message(),
            ), 400 );
        }
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $result,
        ), 200 );
    }
    
    /**
     * Test provider endpoint
     */
    public function test_provider( $request ) {
        $provider_id = $request->get_param( 'provider_id' );
        $provider_config = $this->core->get_provider( $provider_id );
        
        if ( ! $provider_config ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => __( 'Provider not found', 'up-ai-toolkit' ),
            ), 404 );
        }
        
        // Send a simple test request
        $test_prompt = "Say 'Hello from UP AI Toolkit!' in one short sentence.";
        $result = UPAI_AI_Providers::send_request( $provider_config, $test_prompt );
        
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error' => $result->get_error_message(),
            ), 400 );
        }
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => array(
                'message' => __( 'Provider connection successful!', 'up-ai-toolkit' ),
                'response' => $result['content'],
                'model' => $result['model'],
            ),
        ), 200 );
    }
    
    /**
     * Get providers endpoint
     */
    public function get_providers( $request ) {
        $providers = $this->core->get_providers();
        $default_provider = $this->core->get_default_provider();
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => array(
                'providers' => $providers,
                'default_provider' => $default_provider,
            ),
        ), 200 );
    }

    /**
     * Generate SEO title and/or meta description, then save to appropriate meta keys
     */
    public function generate_seo( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $provider_id = $request->get_param( 'provider_id' );
        $what = $request->get_param( 'what' );
        if ( ! $what ) { $what = 'both'; }
        $use_context = $request->get_param( 'use_context' );
        $use_context = is_null( $use_context ) ? true : (bool) $use_context;

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => __( 'Invalid post ID', 'up-ai-toolkit' ) ), 400 );
        }

        $data = array();
        $errors = array();

        if ( $what === 'both' || $what === 'title' ) {
            $title_res = $this->content_analyzer->generate_seo_title( $post_id, $provider_id, array( 'use_context' => $use_context ) );
            if ( is_wp_error( $title_res ) ) {
                $errors['title'] = $title_res->get_error_message();
            } else {
                $saved_key = $this->content_analyzer->set_seo_title_for_post( $post_id, $title_res['title'] );
                $data['title'] = array( 'value' => $title_res['title'], 'meta_key' => $saved_key );
            }
        }

        if ( $what === 'both' || $what === 'description' ) {
            $desc_res = $this->content_analyzer->generate_meta_description( $post_id, $provider_id, array( 'use_context' => $use_context ) );
            if ( is_wp_error( $desc_res ) ) {
                $errors['description'] = $desc_res->get_error_message();
            } else {
                $saved_key = $this->content_analyzer->set_meta_description_for_post( $post_id, $desc_res['description'] );
                $data['description'] = array( 'value' => $desc_res['description'], 'meta_key' => $saved_key );
            }
        }

        if ( ! empty( $errors ) && empty( $data ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $errors ), 400 );
        }

        return new WP_REST_Response( array( 'success' => true, 'data' => $data ), 200 );
    }
}
