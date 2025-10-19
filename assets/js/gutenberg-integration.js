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
    var ToggleControl = wp.components.ToggleControl;
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
        var _useStateSeo = useState(false),
            seoLoading = _useStateSeo[0],
            setSeoLoading = _useStateSeo[1];
        
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

        // Use global context (tone + site instruction)
        var _useState7 = useState(true),
            useContext = _useState7[0],
            setUseContext = _useState7[1];

        // SEO previews (do not auto-apply)
        var _useState8 = useState(''),
            seoTitlePreview = _useState8[0],
            setSeoTitlePreview = _useState8[1];
        var _useState9 = useState(''),
            seoDescPreview = _useState9[0],
            setSeoDescPreview = _useState9[1];
        var _useState10 = useState(true),
            applyTitleOnSave = _useState10[0],
            setApplyTitleOnSave = _useState10[1];
        var _useState11 = useState(true),
            applyDescOnSave = _useState11[0],
            setApplyDescOnSave = _useState11[1];
        
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
         * Generate SEO title and meta description and save to SEO plugin meta
         */
        var handleGenerateSEO = function() {
            if (!postId) {
                setError('No post ID found. Please save the post first.');
                return;
            }
            setSeoLoading(true);
            makeRequest('seo-generate', { post_id: postId, what: 'both' })
                .then(function(data) {
                    setSeoLoading(false);
                    // Only preview; do not auto-apply to post
                    if (data && data.title && data.title.value) setSeoTitlePreview(data.title.value);
                    if (data && data.description && data.description.value) setSeoDescPreview(data.description.value);
                    var msg = 'SEO generated.';
                    if (data && data.title && data.description) {
                        msg = 'SEO generated: title + description';
                    } else if (data && data.title) {
                        msg = 'SEO generated: title';
                    } else if (data && data.description) {
                        msg = 'SEO generated: description';
                    }
                    alert(msg);
                })
                .catch(function(err) {
                    setSeoLoading(false);
                    console.error('Error generating SEO:', err);
                });
        };

        // Separate generators
        var handleGenerateSEOTitle = function() {
            if (!postId) {
                setError('No post ID found. Please save the post first.');
                return;
            }
            setSeoLoading(true);
            makeRequest('seo-generate', { post_id: postId, what: 'title' })
                .then(function(data) {
                    setSeoLoading(false);
                    if (data && data.title && data.title.value) setSeoTitlePreview(data.title.value);
                    alert('SEO Title generated');
                })
                .catch(function(err) {
                    setSeoLoading(false);
                    console.error('Error generating SEO title:', err);
                });
        };

        var handleGenerateSEODescription = function() {
            if (!postId) {
                setError('No post ID found. Please save the post first.');
                return;
            }
            setSeoLoading(true);
            makeRequest('seo-generate', { post_id: postId, what: 'description' })
                .then(function(data) {
                    setSeoLoading(false);
                    if (data && data.description && data.description.value) setSeoDescPreview(data.description.value);
                    alert('Meta Description generated');
                })
                .catch(function(err) {
                    setSeoLoading(false);
                    console.error('Error generating Meta Description:', err);
                });
        };

        // Apply preview to meta right before user saves the post (no auto-save triggered here)
        useEffect(function() {
            var unsubscribe = wp.data.subscribe(function() {
                try {
                    var isSaving = wp.data.select('core/editor').isSavingPost();
                    var isAutosaving = wp.data.select('core/editor').isAutosavingPost();
                    var meta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};
                    if (isSaving && !isAutosaving) {
                        var updates = {};
                        if (applyTitleOnSave && seoTitlePreview) {
                            updates['_yoast_wpseo_title'] = seoTitlePreview;
                        }
                        if (applyDescOnSave && seoDescPreview) {
                            updates['_yoast_wpseo_metadesc'] = seoDescPreview;
                        }
                        if (Object.keys(updates).length > 0) {
                            wp.data.dispatch('core/editor').editPost({ meta: Object.assign({}, meta, updates) });
                        }
                    }
                } catch (e) {}
            });
            return function() { try { unsubscribe && unsubscribe(); } catch(e) {} };
        }, [applyTitleOnSave, applyDescOnSave, seoTitlePreview, seoDescPreview]);

        // Add a small icon button next to the Preview dropdown to toggle our sidebar
        useEffect(function() {
            var addedBtn = null;
            var addedStyle = null;
            var interval = setInterval(function() {
                try {
                    var previewBtn = document.querySelector('button.editor-preview-dropdown__toggle');
                    if (!previewBtn || addedBtn) return;
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'components-button is-compact has-icon upai-quick-toggle';
                    btn.setAttribute('aria-label', 'UP AI Toolkit');
                    btn.style.marginLeft = '6px';
                    // Resolve icon.svg URL based on this script tag src
                    (function() {
                        try {
                            var scripts = document.getElementsByTagName('script');
                            var scriptSrc = '';
                            for (var i = 0; i < scripts.length; i++) {
                                var s = scripts[i];
                                if (!s.src) continue;
                                if (s.src.indexOf('/wp-content/plugins/up-ai-toolkit/assets/js/gutenberg-integration.js') !== -1) {
                                    scriptSrc = s.src;
                                    break;
                                }
                            }
                            if (scriptSrc) {
                                var iconUrl = scriptSrc.replace('/assets/js/gutenberg-integration.js', '/assets/images/icon.svg');
                                fetch(iconUrl, { credentials: 'same-origin' })
                                    .then(function(r){ return r.text(); })
                                    .then(function(svg){ btn.innerHTML = svg; })
                                    .catch(function(){
                                        btn.innerHTML = '\n<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">\n  <path d="M12 2l2.09 6.26H20l-5.17 3.76L16.18 18 12 14.9 7.82 18l1.35-5.98L4 8.26h5.91L12 2z" fill="currentColor"/>\n</svg>';
                                    });
                            } else {
                                btn.innerHTML = '\n<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">\n  <path d="M12 2l2.09 6.26H20l-5.17 3.76L16.18 18 12 14.9 7.82 18l1.35-5.98L4 8.26h5.91L12 2z" fill="currentColor"/>\n</svg>';
                            }
                        } catch (e) {
                            btn.innerHTML = '\n<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">\n  <path d="M12 2l2.09 6.26H20l-5.17 3.76L16.18 18 12 14.9 7.82 18l1.35-5.98L4 8.26h5.91L12 2z" fill="currentColor"/>\n</svg>';
                        }
                    })();
                    btn.addEventListener('click', function() {
                        try {
                            // Prefer to simulate the exact same action as clicking the menu item
                            var menuBtn = document.querySelector('button[aria-controls="up-ai-toolkit:up-ai-toolkit-sidebar"]');
                            if (menuBtn) {
                                menuBtn.click();
                                return;
                            }
                        } catch (e) {}
                        try {
                            // Fallbacks using edit-post store
                            if (wp && wp.data && wp.data.dispatch) {
                                var editPost = wp.data.dispatch('core/edit-post');
                                // Try general sidebar with composed name (plugin/slug)
                                if (editPost && editPost.openGeneralSidebar) {
                                    editPost.openGeneralSidebar('up-ai-toolkit/up-ai-toolkit-sidebar');
                                }
                                // Last resort: openPluginSidebar if available
                                if (editPost && editPost.openPluginSidebar) {
                                    editPost.openPluginSidebar('up-ai-toolkit-sidebar');
                                }
                            }
                        } catch (e) {}
                    });
                    previewBtn.insertAdjacentElement('afterend', btn);
                    addedBtn = btn;

                    // Inject minimal CSS for predictable icon sizing
                    try {
                        var css = '.upai-quick-toggle svg{width:16px;height:16px;display:block}.upai-quick-toggle{padding:2px;line-height:1}';
                        var style = document.createElement('style');
                        style.type = 'text/css';
                        style.appendChild(document.createTextNode(css));
                        document.head.appendChild(style);
                        addedStyle = style;
                    } catch(e) {}
                } catch (e) {}
            }, 400);
            return function() {
                try { clearInterval(interval); } catch (e) {}
                try { if (addedBtn && addedBtn.parentNode) addedBtn.parentNode.removeChild(addedBtn); } catch (e) {}
                try { if (addedStyle && addedStyle.parentNode) addedStyle.parentNode.removeChild(addedStyle); } catch (e) {}
            };
        }, []);

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
            var payload = Object.assign({}, data, { use_context: !!useContext });
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
                    ),
                    el(PanelRow, {},
                        el(ToggleControl, {
                            label: 'Use Global Context (tone & site instruction)',
                            checked: useContext,
                            onChange: setUseContext
                        })
                    ),
                    el(PanelRow, {},
                        el('p', { style: { fontSize: '12px', color: useContext ? '#116611' : '#666' } },
                            useContext ? 'Global context WILL be applied to prompts.' : 'Global context will NOT be applied to prompts.'
                        )
                    )
                ),

                // Generate SEO (Title & Meta Description)
                el(PanelBody, {
                    title: 'Generate SEO (Title & Description)',
                    initialOpen: true
                },
                    el(PanelRow, {},
                        el('p', { style: { fontSize: '13px', color: '#666' } },
                            'Generate SEO title and meta description based on content, tone of voice and site instruction. They will be saved to the active SEO plugin if detected.'
                        )
                    ),
                    el(PanelRow, {},
                        el(Button, {
                            isSecondary: true,
                            isBusy: seoLoading || loading,
                            disabled: seoLoading || loading,
                            onClick: handleGenerateSEOTitle
                        }, seoLoading ? 'Generating...' : 'Generate Title'),
                        el(Button, {
                            style: { marginLeft: '8px' },
                            isSecondary: true,
                            isBusy: seoLoading || loading,
                            disabled: seoLoading || loading,
                            onClick: handleGenerateSEODescription
                        }, seoLoading ? 'Generating...' : 'Generate Description'),
                        el(Button, {
                            style: { marginLeft: '8px' },
                            isPrimary: true,
                            isBusy: seoLoading || loading,
                            disabled: seoLoading || loading,
                            onClick: handleGenerateSEO
                        }, seoLoading ? 'Generating...' : 'Generate Both')
                    )
                ),

                // Edit SEO (Preview) Section
                el(PanelBody, {
                    title: 'Edit SEO (Preview)',
                    initialOpen: true
                },
                    el(PanelRow, {},
                        el(TextControl, {
                            label: 'SEO Title (preview)',
                            value: seoTitlePreview,
                            onChange: setSeoTitlePreview
                        })
                    ),
                    el(PanelRow, {},
                        el(TextareaControl, {
                            label: 'Meta Description (preview)',
                            value: seoDescPreview,
                            onChange: setSeoDescPreview,
                            rows: 3
                        })
                    ),
                    el(PanelRow, {},
                        el(ToggleControl, {
                            label: 'Apply Title to Yoast on Save',
                            checked: applyTitleOnSave,
                            onChange: setApplyTitleOnSave
                        })
                    ),
                    el(PanelRow, {},
                        el(ToggleControl, {
                            label: 'Apply Description to Yoast on Save',
                            checked: applyDescOnSave,
                            onChange: setApplyDescOnSave
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
