<?php
/**
 * Testing functions for text extraction and injection in Gutenberg blocks
 *
 * @package UP_AI_Toolkit
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UPAI_Tests {
    
    /**
     * Core instance
     */
    private $core;
    
    /**
     * Content analyzer instance
     */
    private $content_analyzer;
    
    /**
     * Constructor
     */
    public function __construct( $core, $content_analyzer ) {
        $this->core = $core;
        $this->content_analyzer = $content_analyzer;
        
        add_action( 'wp_ajax_upai_run_tests', array( $this, 'run_tests_ajax' ) );
    }
    
    /**
     * Run all tests via AJAX
     */
    public function run_tests_ajax() {
        check_ajax_referer( 'upai_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'up-ai-toolkit' ) ) );
        }
        
        $results = $this->run_all_tests();
        
        wp_send_json_success( array( 'results' => $results ) );
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $results = array();
        
        $results['parse_blocks'] = $this->test_parse_blocks();
        $results['extract_text'] = $this->test_extract_text();
        $results['inject_text'] = $this->test_inject_text();
        $results['provider_connection'] = $this->test_provider_connection();
        
        return $results;
    }
    
    /**
     * Test: Parse Gutenberg blocks
     */
    public function test_parse_blocks() {
        $test = array(
            'name' => 'Parse Gutenberg Blocks',
            'status' => 'pending',
            'message' => '',
            'details' => array(),
        );
        
        try {
            // Create a test post with Gutenberg blocks
            $test_content = '<!-- wp:paragraph -->
<p>This is a test paragraph.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>Test Heading</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Another paragraph with some content.</p>
<!-- /wp:paragraph -->';
            
            $post_id = wp_insert_post( array(
                'post_title' => 'UPAI Test Post - ' . time(),
                'post_content' => $test_content,
                'post_status' => 'draft',
                'post_type' => 'post',
            ) );
            
            if ( is_wp_error( $post_id ) ) {
                throw new Exception( $post_id->get_error_message() );
            }
            
            // Parse blocks
            $blocks = $this->core->parse_blocks( $post_id );
            
            $test['details']['post_id'] = $post_id;
            $test['details']['blocks_count'] = count( $blocks );
            $test['details']['block_types'] = array_map( function( $block ) {
                return $block['blockName'];
            }, $blocks );
            
            if ( count( $blocks ) === 3 ) {
                $test['status'] = 'success';
                $test['message'] = sprintf( __( 'Successfully parsed %d blocks', 'up-ai-toolkit' ), count( $blocks ) );
            } else {
                $test['status'] = 'warning';
                $test['message'] = sprintf( __( 'Expected 3 blocks, found %d', 'up-ai-toolkit' ), count( $blocks ) );
            }
            
            // Clean up
            wp_delete_post( $post_id, true );
            
        } catch ( Exception $e ) {
            $test['status'] = 'error';
            $test['message'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Test: Extract text from blocks
     */
    public function test_extract_text() {
        $test = array(
            'name' => 'Extract Text from Blocks',
            'status' => 'pending',
            'message' => '',
            'details' => array(),
        );
        
        try {
            // Create a test post
            $test_content = '<!-- wp:paragraph -->
<p>This is a test paragraph with some content.</p>
<!-- /wp:paragraph -->';
            
            $post_id = wp_insert_post( array(
                'post_title' => 'UPAI Extract Test - ' . time(),
                'post_content' => $test_content,
                'post_status' => 'draft',
                'post_type' => 'post',
            ) );
            
            if ( is_wp_error( $post_id ) ) {
                throw new Exception( $post_id->get_error_message() );
            }
            
            // Parse blocks and extract text
            $blocks = $this->core->parse_blocks( $post_id );
            
            if ( empty( $blocks ) ) {
                throw new Exception( __( 'No blocks found', 'up-ai-toolkit' ) );
            }
            
            $extracted_text = $this->content_analyzer->extract_block_text( $blocks[0] );
            
            $test['details']['post_id'] = $post_id;
            $test['details']['extracted_text'] = $extracted_text;
            $test['details']['text_length'] = strlen( $extracted_text );
            
            if ( ! empty( $extracted_text ) && strpos( $extracted_text, 'test paragraph' ) !== false ) {
                $test['status'] = 'success';
                $test['message'] = __( 'Text extraction successful', 'up-ai-toolkit' );
            } else {
                $test['status'] = 'error';
                $test['message'] = __( 'Text extraction failed or incomplete', 'up-ai-toolkit' );
            }
            
            // Clean up
            wp_delete_post( $post_id, true );
            
        } catch ( Exception $e ) {
            $test['status'] = 'error';
            $test['message'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Test: Inject text into blocks
     */
    public function test_inject_text() {
        $test = array(
            'name' => 'Inject Text into Blocks',
            'status' => 'pending',
            'message' => '',
            'details' => array(),
        );
        
        try {
            // Create a test post
            $test_content = '<!-- wp:paragraph -->
<p>Original content</p>
<!-- /wp:paragraph -->';
            
            $post_id = wp_insert_post( array(
                'post_title' => 'UPAI Inject Test - ' . time(),
                'post_content' => $test_content,
                'post_status' => 'draft',
                'post_type' => 'post',
            ) );
            
            if ( is_wp_error( $post_id ) ) {
                throw new Exception( $post_id->get_error_message() );
            }
            
            $new_text = 'Modified content by UP AI Toolkit';
            
            // Inject text
            $result = $this->content_analyzer->inject_text_into_post( $post_id, 0, $new_text );
            
            if ( is_wp_error( $result ) ) {
                throw new Exception( $result->get_error_message() );
            }
            
            // Verify injection
            $post = get_post( $post_id );
            $blocks = $this->core->parse_blocks( $post_id );
            $injected_text = $this->content_analyzer->extract_block_text( $blocks[0] );
            
            $test['details']['post_id'] = $post_id;
            $test['details']['original_text'] = 'Original content';
            $test['details']['new_text'] = $new_text;
            $test['details']['injected_text'] = $injected_text;
            
            if ( strpos( $injected_text, 'Modified content' ) !== false ) {
                $test['status'] = 'success';
                $test['message'] = __( 'Text injection successful', 'up-ai-toolkit' );
            } else {
                $test['status'] = 'error';
                $test['message'] = __( 'Text injection failed', 'up-ai-toolkit' );
            }
            
            // Clean up
            wp_delete_post( $post_id, true );
            
        } catch ( Exception $e ) {
            $test['status'] = 'error';
            $test['message'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Test: Provider connection
     */
    public function test_provider_connection() {
        $test = array(
            'name' => 'Provider Connection',
            'status' => 'pending',
            'message' => '',
            'details' => array(),
        );
        
        try {
            $default_provider_id = $this->core->get_default_provider();
            
            if ( ! $default_provider_id ) {
                $test['status'] = 'warning';
                $test['message'] = __( 'No default provider configured', 'up-ai-toolkit' );
                return $test;
            }
            
            $provider_config = $this->core->get_provider( $default_provider_id );
            
            // Send a simple test request
            $result = UPAI_AI_Providers::send_request( 
                $provider_config, 
                'Respond with: "Connection test successful"' 
            );
            
            if ( is_wp_error( $result ) ) {
                throw new Exception( $result->get_error_message() );
            }
            
            $test['details']['provider'] = $provider_config['name'];
            $test['details']['model'] = $result['model'];
            $test['details']['response'] = $result['content'];
            
            $test['status'] = 'success';
            $test['message'] = __( 'Provider connection successful', 'up-ai-toolkit' );
            
        } catch ( Exception $e ) {
            $test['status'] = 'error';
            $test['message'] = $e->getMessage();
        }
        
        return $test;
    }
    
    /**
     * Get formatted test results as HTML
     */
    public function format_test_results( $results ) {
        $html = '<div class="upai-test-results">';
        
        foreach ( $results as $test ) {
            $status_class = 'upai-test-' . $test['status'];
            $status_icon = $this->get_status_icon( $test['status'] );
            
            $html .= sprintf(
                '<div class="upai-test-result %s">
                    <h4>%s %s</h4>
                    <p>%s</p>',
                $status_class,
                $status_icon,
                esc_html( $test['name'] ),
                esc_html( $test['message'] )
            );
            
            if ( ! empty( $test['details'] ) ) {
                $html .= '<div class="upai-test-details"><pre>' . esc_html( print_r( $test['details'], true ) ) . '</pre></div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get status icon
     */
    private function get_status_icon( $status ) {
        $icons = array(
            'success' => '✓',
            'error' => '✗',
            'warning' => '⚠',
            'pending' => '○',
        );
        
        return isset( $icons[ $status ] ) ? $icons[ $status ] : '○';
    }
}
