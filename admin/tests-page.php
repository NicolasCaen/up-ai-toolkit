<?php
/**
 * Tests Page Template
 *
 * @package UP_AI_Toolkit
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tests = new UPAI_Tests( upai_toolkit()->core, upai_toolkit()->content_analyzer );
?>

<div class="wrap upai-tests">
    <h1><?php _e( 'UP AI Toolkit - Tests', 'up-ai-toolkit' ); ?></h1>
    
    <div class="upai-tests-header">
        <p><?php _e( 'Run tests to validate text extraction and injection functions in Gutenberg blocks.', 'up-ai-toolkit' ); ?></p>
        <p>
            <button type="button" class="button button-primary" id="upai-run-tests">
                <?php _e( 'Run All Tests', 'up-ai-toolkit' ); ?>
            </button>
        </p>
    </div>
    
    <div id="upai-tests-results">
        <p class="description"><?php _e( 'Click "Run All Tests" to begin testing.', 'up-ai-toolkit' ); ?></p>
    </div>
    
    <!-- Manual Test Section -->
    <div class="upai-section upai-manual-tests">
        <h2><?php _e( 'Manual API Tests', 'up-ai-toolkit' ); ?></h2>
        
        <div class="upai-manual-test">
            <h3><?php _e( 'Test Extract Block Text', 'up-ai-toolkit' ); ?></h3>
            <p class="description"><?php _e( 'Extract text from a specific block in a post.', 'up-ai-toolkit' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><?php _e( 'Post ID', 'up-ai-toolkit' ); ?></th>
                    <td><input type="number" id="test-extract-post-id" class="regular-text" placeholder="1" /></td>
                </tr>
                <tr>
                    <th><?php _e( 'Block Index', 'up-ai-toolkit' ); ?></th>
                    <td><input type="number" id="test-extract-block-index" class="regular-text" placeholder="0" /></td>
                </tr>
            </table>
            
            <p>
                <button type="button" class="button" id="test-extract-text">
                    <?php _e( 'Extract Text', 'up-ai-toolkit' ); ?>
                </button>
            </p>
            
            <div id="test-extract-result" class="upai-test-result-box"></div>
        </div>
        
        <div class="upai-manual-test">
            <h3><?php _e( 'Test Inject Text into Block', 'up-ai-toolkit' ); ?></h3>
            <p class="description"><?php _e( 'Inject new text into a specific block in a post.', 'up-ai-toolkit' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><?php _e( 'Post ID', 'up-ai-toolkit' ); ?></th>
                    <td><input type="number" id="test-inject-post-id" class="regular-text" placeholder="1" /></td>
                </tr>
                <tr>
                    <th><?php _e( 'Block Index', 'up-ai-toolkit' ); ?></th>
                    <td><input type="number" id="test-inject-block-index" class="regular-text" placeholder="0" /></td>
                </tr>
                <tr>
                    <th><?php _e( 'New Text', 'up-ai-toolkit' ); ?></th>
                    <td><textarea id="test-inject-text" class="large-text" rows="4" placeholder="Enter text to inject..."></textarea></td>
                </tr>
            </table>
            
            <p>
                <button type="button" class="button button-primary" id="test-inject-text-btn">
                    <?php _e( 'Inject Text', 'up-ai-toolkit' ); ?>
                </button>
            </p>
            
            <div id="test-inject-result" class="upai-test-result-box"></div>
        </div>
        
        <div class="upai-manual-test">
            <h3><?php _e( 'Test AI Text Modification', 'up-ai-toolkit' ); ?></h3>
            <p class="description"><?php _e( 'Test AI text modification with a custom prompt.', 'up-ai-toolkit' ); ?></p>
            
            <table class="form-table">
                <tr>
                    <th><?php _e( 'Original Text', 'up-ai-toolkit' ); ?></th>
                    <td><textarea id="test-ai-text" class="large-text" rows="4" placeholder="Enter text to modify..."></textarea></td>
                </tr>
                <tr>
                    <th><?php _e( 'Prompt', 'up-ai-toolkit' ); ?></th>
                    <td><input type="text" id="test-ai-prompt" class="large-text" placeholder="Make this text more concise" /></td>
                </tr>
            </table>
            
            <p>
                <button type="button" class="button button-primary" id="test-ai-modify">
                    <?php _e( 'Modify Text', 'up-ai-toolkit' ); ?>
                </button>
            </p>
            
            <div id="test-ai-result" class="upai-test-result-box"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Run all tests
    $('#upai-run-tests').on('click', function() {
        var $button = $(this);
        var $results = $('#upai-tests-results');
        
        $button.prop('disabled', true).text('<?php esc_js( _e( 'Running Tests...', 'up-ai-toolkit' ) ); ?>');
        $results.html('<p><?php esc_js( _e( 'Running tests, please wait...', 'up-ai-toolkit' ) ); ?></p>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'upai_run_tests',
                nonce: '<?php echo wp_create_nonce( 'upai_admin_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var html = '<div class="upai-test-results">';
                    $.each(response.data.results, function(key, test) {
                        var statusClass = 'upai-test-' + test.status;
                        var statusIcon = test.status === 'success' ? '✓' : test.status === 'error' ? '✗' : '⚠';
                        
                        html += '<div class="upai-test-result ' + statusClass + '">';
                        html += '<h4>' + statusIcon + ' ' + test.name + '</h4>';
                        html += '<p>' + test.message + '</p>';
                        
                        if (test.details && Object.keys(test.details).length > 0) {
                            html += '<div class="upai-test-details"><pre>' + JSON.stringify(test.details, null, 2) + '</pre></div>';
                        }
                        
                        html += '</div>';
                    });
                    html += '</div>';
                    
                    $results.html(html);
                } else {
                    $results.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $results.html('<div class="notice notice-error"><p><?php esc_js( _e( 'An error occurred while running tests.', 'up-ai-toolkit' ) ); ?></p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_js( _e( 'Run All Tests', 'up-ai-toolkit' ) ); ?>');
            }
        });
    });
    
    // Test extract text
    $('#test-extract-text').on('click', function() {
        var postId = $('#test-extract-post-id').val();
        var blockIndex = $('#test-extract-block-index').val();
        var $result = $('#test-extract-result');
        
        if (!postId || !blockIndex) {
            $result.html('<div class="notice notice-error"><p><?php esc_js( _e( 'Please enter post ID and block index.', 'up-ai-toolkit' ) ); ?></p></div>');
            return;
        }
        
        $result.html('<p><?php esc_js( _e( 'Extracting text...', 'up-ai-toolkit' ) ); ?></p>');
        
        $.ajax({
            url: '<?php echo rest_url( 'upai/v1/extract-block' ); ?>',
            type: 'POST',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
            },
            data: JSON.stringify({
                post_id: parseInt(postId),
                block_index: parseInt(blockIndex)
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p><strong><?php esc_js( _e( 'Success!', 'up-ai-toolkit' ) ); ?></strong></p><pre>' + response.data.text + '</pre></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.error + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p><?php esc_js( _e( 'An error occurred.', 'up-ai-toolkit' ) ); ?></p></div>');
            }
        });
    });
    
    // Test inject text
    $('#test-inject-text-btn').on('click', function() {
        var postId = $('#test-inject-post-id').val();
        var blockIndex = $('#test-inject-block-index').val();
        var text = $('#test-inject-text').val();
        var $result = $('#test-inject-result');
        
        if (!postId || !blockIndex || !text) {
            $result.html('<div class="notice notice-error"><p><?php esc_js( _e( 'Please fill in all fields.', 'up-ai-toolkit' ) ); ?></p></div>');
            return;
        }
        
        $result.html('<p><?php esc_js( _e( 'Injecting text...', 'up-ai-toolkit' ) ); ?></p>');
        
        $.ajax({
            url: '<?php echo rest_url( 'upai/v1/inject-text' ); ?>',
            type: 'POST',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
            },
            data: JSON.stringify({
                post_id: parseInt(postId),
                block_index: parseInt(blockIndex),
                text: text
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p><?php esc_js( _e( 'Text injected successfully!', 'up-ai-toolkit' ) ); ?></p></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.error + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p><?php esc_js( _e( 'An error occurred.', 'up-ai-toolkit' ) ); ?></p></div>');
            }
        });
    });
    
    // Test AI modification
    $('#test-ai-modify').on('click', function() {
        var text = $('#test-ai-text').val();
        var prompt = $('#test-ai-prompt').val();
        var $result = $('#test-ai-result');
        
        if (!text || !prompt) {
            $result.html('<div class="notice notice-error"><p><?php esc_js( _e( 'Please fill in all fields.', 'up-ai-toolkit' ) ); ?></p></div>');
            return;
        }
        
        $result.html('<p><?php esc_js( _e( 'Processing with AI...', 'up-ai-toolkit' ) ); ?></p>');
        
        $.ajax({
            url: '<?php echo rest_url( 'upai/v1/modify' ); ?>',
            type: 'POST',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>'
            },
            data: JSON.stringify({
                text: text,
                prompt: prompt
            }),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p><strong><?php esc_js( _e( 'Modified Text:', 'up-ai-toolkit' ) ); ?></strong></p><pre>' + response.data.modified_text + '</pre></div>');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.error + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p><?php esc_js( _e( 'An error occurred.', 'up-ai-toolkit' ) ); ?></p></div>');
            }
        });
    });
});
</script>
