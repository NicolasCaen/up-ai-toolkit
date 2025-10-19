/**
 * Gutenberg Integration for UP AI Toolkit
 *
 * Adds AI content modification tools to the Gutenberg editor
 *
 * @package UP_AI_Toolkit
 */

(function(wp) {
    'use strict';
    
    if (!wp || !wp.plugins || !wp.editPost || !wp.element || !wp.components) {
        console.error('UP AI Toolkit: Required WordPress packages not found');
        return;
    }
    
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var PluginSidebar = wp.editPost.PluginSidebar;
    var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
    var registerPlugin = wp.plugins.registerPlugin;
    var useSelect = wp.data.useSelect;
    var useDispatch = wp.data.useDispatch;
    var Button = wp.components.Button;
    var PanelBody = wp.components.PanelBody;
    var PanelRow = wp.components.PanelRow;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var SelectControl = wp.components.SelectControl;
    var Spinner = wp.components.Spinner;
    var Notice = wp.components.Notice;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    
    /**
     * UP AI Toolkit Sidebar Component
     */
    var UPAISidebar = function() {
        var postId = useSelect(function(select) {
            return select('core/editor').getCurrentPostId();
        });
        
        var selectedBlock = useSelect(function(select) {
            var selectedBlockId = select('core/block-editor').getSelectedBlockClientId();
            if (!selectedBlockId) return null;
            return select('core/block-editor').getBlock(selectedBlockId);
        });
        
        var updateBlockAttributes = useDispatch('core/block-editor').updateBlockAttributes;
        
        var _useState = useState(false),
            loading = _useState[0],
            setLoading = _useState[1];
        
        var _useState2 = useState(null),
            error = _useState2[0],
            setError = _useState2[1];
        
        var _useState3 = useState(''),
            customPrompt = _useState3[0],
            setCustomPrompt = _useState3[1];
        
        var _useState4 = useState(''),
            targetLanguage = _useState4[0],
            setTargetLanguage = _useState4[1];

        // Provider selection (global for this sidebar session)
        var _useState6 = useState((upaiGutenberg && upaiGutenberg.defaultProvider) || ''),
            providerId = _useState6[0],
            setProviderId = _useState6[1];

        // Whole post modification prompt
        var _useState5 = useState(''),
            postPrompt = _useState5[0],
            setPostPrompt = _useState5[1];
        
        /**
         * Extract text from selected block
         */
        var extractBlockText = function() {
            if (!selectedBlock) return '';
            
            var text = '';
            
            // Handle different block types
            if (selectedBlock.name === 'core/paragraph') {
                text = selectedBlock.attributes.content || '';
            } else if (selectedBlock.name === 'core/heading') {
                text = selectedBlock.attributes.content || '';
            } else if (selectedBlock.name === 'core/quote') {
                text = selectedBlock.attributes.value || '';
            } else if (selectedBlock.name === 'core/list') {
                text = selectedBlock.attributes.values || '';
            } else {
                // Generic fallback
                text = selectedBlock.attributes.content || '';
            }
            
            // Strip HTML tags
            var tmp = document.createElement('div');
            tmp.innerHTML = text;
            return tmp.textContent || tmp.innerText || '';
        };

        /**
         * Modify the whole post coherently
         */
        var handleModifyPost = function() {
            if (!postId) {
                setError('No post ID found. Please save the post first.');
                return;
            }
            if (!postPrompt) {
                setError('Please enter a prompt');
                return;
            }

            makeRequest('modify-post', {
                post_id: postId,
                prompt: postPrompt
            })
            .then(function(data) {
                // After applying changes server-side, refresh blocks in editor
                wp.data.dispatch('core/editor').refreshPost();
                alert('Post modified successfully (' + (data.updated || 0) + ' blocks updated).');
            })
            .catch(function(err) {
                console.error('Error modifying whole post:', err);
            });
        };
        
        /**
         * Update block with new text
         */
        var updateBlockText = function(newText) {
            if (!selectedBlock) return;
            
            var blockId = selectedBlock.clientId;
            var newAttributes = {};
            
            // Handle different block types
            if (selectedBlock.name === 'core/paragraph' || selectedBlock.name === 'core/heading') {
                newAttributes.content = newText;
            } else if (selectedBlock.name === 'core/quote') {
                newAttributes.value = newText;
            } else if (selectedBlock.name === 'core/list') {
                newAttributes.values = newText;
            } else {
                newAttributes.content = newText;
            }
            
            updateBlockAttributes(blockId, newAttributes);
        };
        
        /**
         * Make API request
         */
        var makeRequest = function(endpoint, data) {
            setLoading(true);
            setError(null);
            // Attach provider_id if selected
            var payload = Object.assign({}, data);
            if (providerId) {
                payload.provider_id = providerId;
            }

            // Prefer wp.apiFetch (handles nonce & cookies)
            if (wp.apiFetch) {
                return wp.apiFetch({
                    path: '/upai/v1/' + endpoint,
                    method: 'POST',
                    data: payload
                })
                .then(function(result) {
                    setLoading(false);
                    if (!result || result.success === false) {
                        var msg = (result && (result.error || result.message)) || 'API request failed';
                        throw new Error(msg);
                    }
                    return result.data || result; // our API returns { success, data }
                })
                .catch(function(err) {
                    setLoading(false);
                    var msg = err && (err.message || err.code) ? (err.message || err.code) : 'Request failed';
                    setError(msg);
                    throw err;
                });
            }

            // Fallback to fetch with nonce + same-origin credentials
            return fetch(upaiGutenberg.apiUrl + '/' + endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': upaiGutenberg.nonce
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
            .then(function(response) {
                return response.json().catch(function(){ return { success: false, error: 'Invalid JSON response' }; });
            })
            .then(function(result) {
                setLoading(false);
                if (!result.success) {
                    throw new Error(result.error || result.message || 'API request failed');
                }
                return result.data;
            })
            .catch(function(err) {
                setLoading(false);
                setError(err.message || 'Request failed');
                throw err;
            });
        };
        
        /**
         * Generate excerpt
         */
        var handleGenerateExcerpt = function() {
            makeRequest('excerpt', { post_id: postId })
                .then(function(data) {
                    if (data.excerpt) {
                        // Update post excerpt
                        wp.data.dispatch('core/editor').editPost({ excerpt: data.excerpt });
                        alert('Excerpt generated successfully!');
                    }
                })
                .catch(function(err) {
                    console.error('Error generating excerpt:', err);
                });
        };
        
        /**
         * Translate text
         */
        var handleTranslate = function() {
            var text = extractBlockText();
            if (!text) {
                setError('Please select a block with text content');
                return;
            }
            
            if (!targetLanguage) {
                setError('Please select a target language');
                return;
            }
            
            makeRequest('translate', {
                text: text,
                target_language: targetLanguage
            })
            .then(function(data) {
                if (data.translated_text) {
                    updateBlockText(data.translated_text);
                }
            })
            .catch(function(err) {
                console.error('Error translating text:', err);
            });
        };
        
        /**
         * Modify text with custom prompt
         */
        var handleModify = function() {
            var text = extractBlockText();
            if (!text) {
                setError('Please select a block with text content');
                return;
            }
            
            if (!customPrompt) {
                setError('Please enter a prompt');
                return;
            }
            
            makeRequest('modify', {
                text: text,
                prompt: customPrompt
            })
            .then(function(data) {
                if (data.modified_text) {
                    updateBlockText(data.modified_text);
                }
            })
            .catch(function(err) {
                console.error('Error modifying text:', err);
            });
        };
        
        /**
         * Prepare language options
         */
        var languageOptions = Object.keys(upaiGutenberg.languages || {}).map(function(code) {
            return {
                label: upaiGutenberg.languages[code],
                value: code
            };
        });
        
        languageOptions.unshift({ label: 'Select language...', value: '' });

        // Prepare provider options
        var providerOptions = [{ label: 'Default provider', value: '' }];
        try {
            var providers = upaiGutenberg.providers || {};
            Object.keys(providers).forEach(function(id) {
                var p = providers[id] || {};
                var name = p.name || id;
                providerOptions.push({ label: name + ' (' + (p.type || 'custom') + ')', value: id });
            });
        } catch (e) {}
        
        return el(Fragment, {},
            el(PluginSidebarMoreMenuItem, {
                target: 'up-ai-toolkit-sidebar',
                icon: 'admin-generic'
            }, 'UP AI Toolkit'),
            
            el(PluginSidebar, {
                name: 'up-ai-toolkit-sidebar',
                title: 'UP AI Toolkit',
                icon: 'admin-generic'
            },
                error && el(Notice, {
                    status: 'error',
                    isDismissible: true,
                    onRemove: function() { setError(null); }
                }, error),
                
                // Provider selection
                el(PanelBody, {
                    title: 'Provider',
                    initialOpen: true
                },
                    el(PanelRow, {},
                        el(SelectControl, {
                            label: 'Choose provider',
                            value: providerId,
                            options: providerOptions,
                            onChange: setProviderId
                        })
                    )
                ),
                
                // Generate Excerpt Section
                el(PanelBody, {
                    title: 'Generate Excerpt',
                    initialOpen: false
                },
                    el(PanelRow, {},
                        el('p', { style: { fontSize: '13px', color: '#666' } },
                            'Generate an SEO-optimized excerpt for this post.'
                        )
                    ),
                    el(PanelRow, {},
                        el(Button, {
                            isPrimary: true,
                            isBusy: loading,
                            disabled: loading,
                            onClick: handleGenerateExcerpt
                        }, loading ? 'Generating...' : 'Generate Excerpt')
                    )
                ),
                
                // Translate Section
                el(PanelBody, {
                    title: 'Translate',
                    initialOpen: false
                },
                    el(PanelRow, {},
                        el('p', { style: { fontSize: '13px', color: '#666' } },
                            'Select a block and translate it to another language.'
                        )
                    ),
                    !selectedBlock && el(PanelRow, {},
                        el(Notice, {
                            status: 'warning',
                            isDismissible: false
                        }, 'Please select a block to translate')
                    ),
                    el(PanelRow, {},
                        el(SelectControl, {
                            label: 'Target Language',
                            value: targetLanguage,
                            options: languageOptions,
                            onChange: setTargetLanguage,
                            disabled: !selectedBlock
                        })
                    ),
                    el(PanelRow, {},
                        el(Button, {
                            isPrimary: true,
                            isBusy: loading,
                            disabled: loading || !selectedBlock || !targetLanguage,
                            onClick: handleTranslate
                        }, loading ? 'Translating...' : 'Translate')
                    )
                ),
                
                // Modify Text Section
                el(PanelBody, {
                    title: 'Modify Text',
                    initialOpen: true
                },
                    el(PanelRow, {},
                        el('p', { style: { fontSize: '13px', color: '#666' } },
                            'Select a block and modify it with a custom prompt.'
                        )
                    ),
                    !selectedBlock && el(PanelRow, {},
                        el(Notice, {
                            status: 'warning',
                            isDismissible: false
                        }, 'Please select a block to modify')
                    ),
                    selectedBlock && el(PanelRow, {},
                        el('div', { style: { width: '100%' } },
                            el('strong', {}, 'Selected block: '),
                            el('span', { style: { fontSize: '12px', color: '#666' } },
                                selectedBlock.name.replace('core/', '')
                            )
                        )
                    ),
                    el(PanelRow, {},
                        el(TextareaControl, {
                            label: 'Prompt',
                            help: 'e.g., "Make this text more concise" or "Rewrite in a professional tone"',
                            value: customPrompt,
                            onChange: setCustomPrompt,
                            rows: 4,
                            disabled: !selectedBlock
                        })
                    ),
                    el(PanelRow, {},
                        el(Button, {
                            isPrimary: true,
                            isBusy: loading,
                            disabled: loading || !selectedBlock || !customPrompt,
                            onClick: handleModify
                        }, loading ? 'Processing...' : 'Modify Text')
                    )
                )

                ,
                // Modify Whole Post Section
                el(PanelBody, {
                    title: 'Modify Whole Post (Coherent)',
                    initialOpen: false
                },
                    el(PanelRow, {},
                        el('p', { style: { fontSize: '13px', color: '#666' } },
                            'Apply a single prompt to the whole article. The AI will return coherent updates across all text blocks.'
                        )
                    ),
                    el(PanelRow, {},
                        el(TextareaControl, {
                            label: 'Prompt for the whole post',
                            help: 'e.g., "Unify tone to professional and concise. Improve flow and clarity."',
                            value: postPrompt,
                            onChange: setPostPrompt,
                            rows: 4
                        })
                    ),
                    el(PanelRow, {},
                        el(Button, {
                            isPrimary: true,
                            isBusy: loading,
                            disabled: loading || !postPrompt,
                            onClick: handleModifyPost
                        }, loading ? 'Processing...' : 'Modify Whole Post')
                    )
                )
            )
        );
    };
    
    /**
     * Register the plugin
     */
    registerPlugin('up-ai-toolkit', {
        render: UPAISidebar,
        icon: 'admin-generic'
    });
    
})(window.wp);
