<?php
/**
 * Admin Settings Page Template
 *
 * @package UP_AI_Toolkit
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap upai-admin">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="upai-admin-header">
        <p><?php _e( 'Configure multiple AI providers and manage content analysis settings.', 'up-ai-toolkit' ); ?></p>
    </div>
    
    <form method="post" action="options.php" id="upai-settings-form">
        <?php
        settings_fields( 'upai_settings_group' );
        ?>
        
        <div class="upai-settings-sections">
            
            <!-- AI Providers Section -->
            <div class="upai-section">
                <h2><?php _e( 'AI Providers', 'up-ai-toolkit' ); ?></h2>
                <p class="description"><?php _e( 'Configure your AI providers. You need at least one active provider to use the plugin features.', 'up-ai-toolkit' ); ?></p>
                
                <div id="upai-providers-list">
                    <?php
                    if ( ! empty( $providers ) ) :
                        foreach ( $providers as $provider_id => $provider ) :
                            $provider_info = isset( $supported_providers[ $provider['type'] ] ) ? $supported_providers[ $provider['type'] ] : null;
                            ?>
                            <div class="upai-provider-item" data-provider-id="<?php echo esc_attr( $provider_id ); ?>">
                                <div class="upai-provider-header">
                                    <h3>
                                        <?php echo esc_html( $provider['name'] ); ?>
                                        <?php if ( $provider_info ) : ?>
                                            <span class="upai-provider-type">(<?php echo esc_html( $provider_info['name'] ); ?>)</span>
                                        <?php endif; ?>
                                        <?php if ( $settings['default_provider'] === $provider_id ) : ?>
                                            <span class="upai-badge upai-badge-primary"><?php _e( 'Default', 'up-ai-toolkit' ); ?></span>
                                        <?php endif; ?>
                                    </h3>
                                    <div class="upai-provider-actions">
                                        <button type="button" class="button upai-test-provider" data-provider-id="<?php echo esc_attr( $provider_id ); ?>">
                                            <?php _e( 'Test Connection', 'up-ai-toolkit' ); ?>
                                        </button>
                                        <button type="button" class="button upai-toggle-provider">
                                            <?php _e( 'Edit', 'up-ai-toolkit' ); ?>
                                        </button>
                                        <button type="button" class="button button-link-delete upai-delete-provider" data-provider-id="<?php echo esc_attr( $provider_id ); ?>">
                                            <?php _e( 'Delete', 'up-ai-toolkit' ); ?>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="upai-provider-body" style="display: none;">
                                    <table class="form-table">
                                        <tr>
                                            <th><?php _e( 'Provider Name', 'up-ai-toolkit' ); ?></th>
                                            <td>
                                                <input type="text" 
                                                       name="upai_settings[providers][<?php echo esc_attr( $provider_id ); ?>][name]" 
                                                       value="<?php echo esc_attr( $provider['name'] ); ?>" 
                                                       class="regular-text" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e( 'Provider Type', 'up-ai-toolkit' ); ?></th>
                                            <td>
                                                <select name="upai_settings[providers][<?php echo esc_attr( $provider_id ); ?>][type]" class="regular-text">
                                                    <?php foreach ( $supported_providers as $type_id => $type_info ) : ?>
                                                        <option value="<?php echo esc_attr( $type_id ); ?>" <?php selected( $provider['type'], $type_id ); ?>>
                                                            <?php echo esc_html( $type_info['name'] ); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e( 'API Key', 'up-ai-toolkit' ); ?></th>
                                            <td>
                                                <input type="password" 
                                                       name="upai_settings[providers][<?php echo esc_attr( $provider_id ); ?>][api_key]" 
                                                       value="<?php echo esc_attr( $provider['api_key'] ); ?>" 
                                                       class="regular-text" 
                                                       autocomplete="off" />
                                                <p class="description"><?php _e( 'Your API key will be stored securely.', 'up-ai-toolkit' ); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e( 'Model', 'up-ai-toolkit' ); ?></th>
                                            <td>
                                                <?php if ( $provider_info && ! empty( $provider_info['models'] ) ) : ?>
                                                    <select name="upai_settings[providers][<?php echo esc_attr( $provider_id ); ?>][model]" class="regular-text">
                                                        <?php foreach ( $provider_info['models'] as $model ) : ?>
                                                            <option value="<?php echo esc_attr( $model ); ?>" <?php selected( $provider['model'], $model ); ?>>
                                                                <?php echo esc_html( $model ); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else : ?>
                                                    <input type="text" 
                                                           name="upai_settings[providers][<?php echo esc_attr( $provider_id ); ?>][model]" 
                                                           value="<?php echo esc_attr( $provider['model'] ); ?>" 
                                                           class="regular-text" />
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><?php _e( 'Set as Default', 'up-ai-toolkit' ); ?></th>
                                            <td>
                                                <label>
                                                    <input type="radio" 
                                                           name="upai_settings[default_provider]" 
                                                           value="<?php echo esc_attr( $provider_id ); ?>" 
                                                           <?php checked( $settings['default_provider'], $provider_id ); ?> />
                                                    <?php _e( 'Use this provider by default', 'up-ai-toolkit' ); ?>
                                                </label>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <?php
                        endforeach;
                    else :
                        ?>
                        <p class="upai-no-providers"><?php _e( 'No AI providers configured yet. Click "Add Provider" to get started.', 'up-ai-toolkit' ); ?></p>
                        <?php
                    endif;
                    ?>
                </div>
                
                <p>
                    <button type="button" class="button button-secondary" id="upai-add-provider">
                        <?php _e( '+ Add Provider', 'up-ai-toolkit' ); ?>
                    </button>
                </p>
            </div>
            
            <!-- General Settings Section -->
            <div class="upai-section">
                <h2><?php _e( 'General Settings', 'up-ai-toolkit' ); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e( 'Tone of Voice', 'up-ai-toolkit' ); ?></th>
                        <td>
                            <input type="text"
                                   name="upai_settings[tone_of_voice]"
                                   value="<?php echo isset( $settings['tone_of_voice'] ) ? esc_attr( $settings['tone_of_voice'] ) : ''; ?>"
                                   class="regular-text"
                                   placeholder="<?php esc_attr_e( 'Ex: Professionnel, convivial, expert mais accessible', 'up-ai-toolkit' ); ?>" />
                            <p class="description"><?php _e( 'Définissez le ton global des textes générés (ex: professionnel, convivial, premium, technique).', 'up-ai-toolkit' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Global Instruction (site presentation)', 'up-ai-toolkit' ); ?></th>
                        <td>
                            <textarea name="upai_settings[global_instruction]" rows="4" class="large-text" placeholder="<?php echo esc_attr__( 'Ex: Nous sommes une agence digitale spécialisée en SEO/SEA pour PME en France. Nos services incluent audit SEO, campagnes Google Ads et création de contenu optimisé. Notre proposition de valeur: des résultats mesurables et un accompagnement humain.', 'up-ai-toolkit' ); ?>"><?php echo isset( $settings['global_instruction'] ) ? esc_textarea( $settings['global_instruction'] ) : ''; ?></textarea>
                            <p class="description"><?php _e( 'Présentez brièvement le site/entreprise pour guider les générations et assurer la cohérence.', 'up-ai-toolkit' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Test Mode', 'up-ai-toolkit' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="upai_settings[test_mode]" 
                                       value="1" 
                                       <?php checked( ! empty( $settings['test_mode'] ) ); ?> />
                                <?php _e( 'Enable test mode (responses will be simulated)', 'up-ai-toolkit' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Log Requests', 'up-ai-toolkit' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="upai_settings[log_requests]" 
                                       value="1" 
                                       <?php checked( ! empty( $settings['log_requests'] ) ); ?> />
                                <?php _e( 'Log all API requests to debug log', 'up-ai-toolkit' ); ?>
                            </label>
                            <p class="description"><?php _e( 'Useful for debugging. Check your debug.log file.', 'up-ai-toolkit' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
        </div>
        
        <?php submit_button(); ?>
    </form>
    
    <!-- Add Provider Template (hidden) -->
    <div id="upai-provider-template" style="display: none;">
        <div class="upai-provider-item" data-provider-id="NEW_ID">
            <div class="upai-provider-header">
                <h3><?php _e( 'New Provider', 'up-ai-toolkit' ); ?></h3>
                <div class="upai-provider-actions">
                    <button type="button" class="button upai-toggle-provider"><?php _e( 'Edit', 'up-ai-toolkit' ); ?></button>
                    <button type="button" class="button button-link-delete upai-delete-provider"><?php _e( 'Delete', 'up-ai-toolkit' ); ?></button>
                </div>
            </div>
            <div class="upai-provider-body">
                <table class="form-table">
                    <tr>
                        <th><?php _e( 'Provider Name', 'up-ai-toolkit' ); ?></th>
                        <td><input type="text" name="upai_settings[providers][NEW_ID][name]" value="" class="regular-text" placeholder="My AI Provider" /></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Provider Type', 'up-ai-toolkit' ); ?></th>
                        <td>
                            <select name="upai_settings[providers][NEW_ID][type]" class="regular-text">
                                <?php foreach ( $supported_providers as $type_id => $type_info ) : ?>
                                    <option value="<?php echo esc_attr( $type_id ); ?>"><?php echo esc_html( $type_info['name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e( 'API Key', 'up-ai-toolkit' ); ?></th>
                        <td><input type="password" name="upai_settings[providers][NEW_ID][api_key]" value="" class="regular-text" autocomplete="off" /></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Model', 'up-ai-toolkit' ); ?></th>
                        <td><input type="text" name="upai_settings[providers][NEW_ID][model]" value="" class="regular-text" placeholder="gpt-4o-mini" /></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Set as Default', 'up-ai-toolkit' ); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="upai_settings[default_provider]" value="NEW_ID" />
                                <?php _e( 'Use this provider by default', 'up-ai-toolkit' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
