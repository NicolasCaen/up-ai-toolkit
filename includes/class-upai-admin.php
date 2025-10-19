<?php
/**
 * Admin functionality
 *
 * @package UP_AI_Toolkit
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UPAI_Admin {
    
    /**
     * Core instance
     */
    private $core;
    
    /**
     * Constructor
     */
    public function __construct( $core ) {
        $this->core = $core;
        
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_scripts' ) );
        add_action('admin_head', array( $this, 'mon_css_icone_svg' ));
    }
    /* on remplace l’icône par notre SVG coloré */

public function mon_css_icone_svg() { 
 // Chemin absolu vers l’icône
    $icon = plugins_url('../assets/images/icon.svg', __FILE__);
    ?>
    <style>
        /* on vise le <li> de notre menu */
        #toplevel_page_up-ai-toolkit .wp-menu-image {
            background-image: url('<?php echo esc_url($icon); ?>') !important;
            background-repeat: no-repeat;
            background-position: center;
            background-size: 20px 20px;
        }
        /* on masque l’icône Dashicons d’origine */
        #toplevel_page_up-ai-toolkit .wp-menu-image::before {
            content: none !important;
        }
    </style>
    <?php
}
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'UP AI Toolkit', 'up-ai-toolkit' ),
            __( 'UP AI Toolkit', 'up-ai-toolkit' ),
            'manage_options',
            'up-ai-toolkit',
            array( $this, 'render_admin_page' ),
            'dashicons-admin-generic',
            30
        );
        
        add_submenu_page(
            'up-ai-toolkit',
            __( 'Settings', 'up-ai-toolkit' ),
            __( 'Settings', 'up-ai-toolkit' ),
            'manage_options',
            'up-ai-toolkit',
            array( $this, 'render_admin_page' )
        );
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            add_submenu_page(
                'up-ai-toolkit',
                __( 'Tests', 'up-ai-toolkit' ),
                __( 'Tests', 'up-ai-toolkit' ),
                'manage_options',
                'up-ai-toolkit-tests',
                array( $this, 'render_tests_page' )
            );
        }
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting( 'upai_settings_group', 'upai_settings', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
        ) );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();
        
        // Sanitize providers
        if ( isset( $input['providers'] ) && is_array( $input['providers'] ) ) {
            $sanitized['providers'] = array();
            foreach ( $input['providers'] as $id => $provider ) {
                $sanitized['providers'][ sanitize_key( $id ) ] = array(
                    'name' => sanitize_text_field( $provider['name'] ),
                    'type' => sanitize_text_field( $provider['type'] ),
                    'api_key' => sanitize_text_field( $provider['api_key'] ),
                    'model' => sanitize_text_field( $provider['model'] ),
                    'enabled' => ! empty( $provider['enabled'] ),
                );
            }
        }
        
        // Sanitize other settings
        $sanitized['default_provider'] = isset( $input['default_provider'] ) ? sanitize_text_field( $input['default_provider'] ) : '';
        $sanitized['test_mode'] = ! empty( $input['test_mode'] );
        $sanitized['log_requests'] = ! empty( $input['log_requests'] );
        
        return $sanitized;
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'up-ai-toolkit' ) === false ) {
            return;
        }
        
        wp_enqueue_style(
            'upai-admin',
            UPAI_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            UPAI_VERSION
        );
        
        wp_enqueue_script(
            'upai-admin',
            UPAI_PLUGIN_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            UPAI_VERSION,
            true
        );
        
        wp_localize_script( 'upai-admin', 'upaiAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'restUrl' => rest_url( 'upai/v1' ),
            'restNonce' => wp_create_nonce( 'wp_rest' ),
            'nonce' => wp_create_nonce( 'upai_admin_nonce' ),
            'strings' => array(
                'confirmDelete' => __( 'Are you sure you want to delete this provider?', 'up-ai-toolkit' ),
                'testSuccess' => __( 'Test successful!', 'up-ai-toolkit' ),
                'testFailed' => __( 'Test failed:', 'up-ai-toolkit' ),
            ),
        ) );
    }
    
    /**
     * Enqueue Gutenberg editor scripts
     */
    public function enqueue_gutenberg_scripts() {
        if ( ! $this->core->user_can_use_ai() ) {
            return;
        }
        
        wp_enqueue_script(
            'upai-gutenberg',
            UPAI_PLUGIN_URL . 'assets/js/gutenberg-integration.js',
            array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose', 'wp-api-fetch' ),
            UPAI_VERSION,
            true
        );
        
        wp_localize_script( 'upai-gutenberg', 'upaiGutenberg', array(
            'apiUrl' => rest_url( 'upai/v1' ),
            'nonce' => wp_create_nonce( 'wp_rest' ),
            'languages' => upai_toolkit()->content_analyzer->get_supported_languages(),
            'providers' => $this->core->get_providers(),
            'defaultProvider' => $this->core->get_default_provider(),
        ) );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'up-ai-toolkit' ) );
        }
        
        $settings = $this->core->get_settings();
        $providers = $this->core->get_providers();
        $supported_providers = UPAI_AI_Providers::get_supported_providers();
        
        include UPAI_PLUGIN_DIR . 'admin/admin-page.php';
    }
    
    /**
     * Render tests page
     */
    public function render_tests_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'up-ai-toolkit' ) );
        }
        
        include UPAI_PLUGIN_DIR . 'admin/tests-page.php';
    }
}
