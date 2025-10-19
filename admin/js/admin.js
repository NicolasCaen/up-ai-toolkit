/**
 * Admin JavaScript for UP AI Toolkit
 *
 * @package UP_AI_Toolkit
 */

(function($) {
    'use strict';
    
    var providerCounter = 0;
    
    $(document).ready(function() {
        
        // Toggle provider details
        $(document).on('click', '.upai-toggle-provider', function(e) {
            e.preventDefault();
            var $providerItem = $(this).closest('.upai-provider-item');
            var $body = $providerItem.find('.upai-provider-body');
            
            $body.slideToggle(200);
            $(this).text($body.is(':visible') ? upaiAdmin.strings.hide || 'Hide' : upaiAdmin.strings.edit || 'Edit');
        });
        
        // Add new provider
        $('#upai-add-provider').on('click', function(e) {
            e.preventDefault();
            
            var $template = $('#upai-provider-template .upai-provider-item').clone();
            var newId = 'provider_' + Date.now();
            
            // Replace NEW_ID with actual ID
            var templateHtml = $template.prop('outerHTML');
            templateHtml = templateHtml.replace(/NEW_ID/g, newId);
            
            var $newProvider = $(templateHtml);
            $newProvider.attr('data-provider-id', newId);
            $newProvider.find('.upai-provider-body').show();
            
            $('#upai-providers-list').append($newProvider);
            $('.upai-no-providers').remove();
            
            // Scroll to new provider
            $('html, body').animate({
                scrollTop: $newProvider.offset().top - 100
            }, 300);
        });
        
        // Delete provider
        $(document).on('click', '.upai-delete-provider', function(e) {
            e.preventDefault();
            
            if (!confirm(upaiAdmin.strings.confirmDelete || 'Are you sure?')) {
                return;
            }
            
            var $providerItem = $(this).closest('.upai-provider-item');
            $providerItem.fadeOut(300, function() {
                $(this).remove();
                
                // Show "no providers" message if list is empty
                if ($('#upai-providers-list .upai-provider-item').length === 0) {
                    $('#upai-providers-list').append(
                        '<p class="upai-no-providers">No AI providers configured yet. Click "Add Provider" to get started.</p>'
                    );
                }
            });
        });
        
        // Test provider connection
        $(document).on('click', '.upai-test-provider', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var providerId = $button.data('provider-id');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('Testing...');
            
            $.ajax({
                url: wpApiSettings.root + 'upai/v1/test-provider',
                type: 'POST',
                headers: {
                    'X-WP-Nonce': wpApiSettings.nonce
                },
                data: JSON.stringify({
                    provider_id: providerId
                }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        alert(upaiAdmin.strings.testSuccess || 'Test successful!\n\n' + response.data.message + '\n\nResponse: ' + response.data.response);
                    } else {
                        alert((upaiAdmin.strings.testFailed || 'Test failed:') + ' ' + response.error);
                    }
                },
                error: function(xhr) {
                    var message = 'Connection error';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        message = xhr.responseJSON.error;
                    }
                    alert((upaiAdmin.strings.testFailed || 'Test failed:') + ' ' + message);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Form validation
        $('#upai-settings-form').on('submit', function(e) {
            var hasProvider = $('#upai-providers-list .upai-provider-item').length > 0;
            
            if (!hasProvider) {
                e.preventDefault();
                alert('Please add at least one AI provider before saving.');
                return false;
            }
            
            // Check that all providers have required fields
            var valid = true;
            // IMPORTANT: Only validate providers within the visible list (exclude hidden template)
            $('#upai-providers-list .upai-provider-item').each(function() {
                var $provider = $(this);
                var name = ($provider.find('input[name*="[name]"]').val() || '').trim();
                var apiKey = ($provider.find('input[name*="[api_key]"]').val() || '').trim();
                
                if (!name || !apiKey) {
                    valid = false;
                    $provider.find('.upai-provider-body').show();
                    if (!name) $provider.find('input[name*="[name]"]').css('border', '1px solid red');
                    if (!apiKey) $provider.find('input[name*="[api_key]"]').css('border', '1px solid red');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields (Provider Name and API Key) for all providers.');
                return false;
            }
        });
        
        // Clear error borders on input
        $(document).on('input', 'input.regular-text', function() {
            $(this).css('border', '');
        });
    });
    
})(jQuery);
