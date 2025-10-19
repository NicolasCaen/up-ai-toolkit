<?php
/**
 * Plugin Name: UP AI Toolkit
 * Plugin URI: https://github.com/nicolas-gehin/up-ai-toolkit
 * Description: Multi-AI provider toolkit for content analysis, translation, summarization and text modification in Gutenberg editor.
 * Version: 1.1.0
 * Author: GEHIN Nicolas
 * Author URI: https://nicolas-gehin.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: up-ai-toolkit
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'UPAI_VERSION', '1.1.0' );
define( 'UPAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UPAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'UPAI_PLUGIN_FILE', __FILE__ );

/**
 * Main plugin class initialization
 */
class UP_AI_Toolkit {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Core instance
     */
    public $core;
    
    /**
     * Admin instance
     */
    public $admin;
    
    /**
     * Content analyzer instance
     */
    public $content_analyzer;
    
    /**
     * REST API instance
     */
    public $rest_api;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once UPAI_PLUGIN_DIR . 'includes/class-upai-core.php';
        require_once UPAI_PLUGIN_DIR . 'includes/class-upai-admin.php';
        require_once UPAI_PLUGIN_DIR . 'includes/class-upai-content-analyzer.php';
        require_once UPAI_PLUGIN_DIR . 'includes/class-upai-ai-providers.php';
        require_once UPAI_PLUGIN_DIR . 'includes/class-upai-rest-api.php';
        require_once UPAI_PLUGIN_DIR . 'includes/class-upai-tests.php';
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        
        // Activation/Deactivation hooks
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }
    
    /**
     * Initialize plugin components
     */
    public function init_plugin() {
        $this->core = new UPAI_Core();
        
        if ( is_admin() ) {
            $this->admin = new UPAI_Admin( $this->core );
        }
        
        $this->content_analyzer = new UPAI_Content_Analyzer( $this->core );
        $this->rest_api = new UPAI_REST_API( $this->core, $this->content_analyzer );
        
        // Initialize tests if WP_DEBUG is enabled
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            new UPAI_Tests( $this->core, $this->content_analyzer );
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'up-ai-toolkit', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        $default_options = array(
            'providers' => array(),
            'default_provider' => '',
            'test_mode' => false,
            'log_requests' => false,
        );
        
        if ( ! get_option( 'upai_settings' ) ) {
            add_option( 'upai_settings', $default_options );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize the plugin
 */
function upai_toolkit() {
    return UP_AI_Toolkit::instance();
}

// Start the plugin
upai_toolkit();
