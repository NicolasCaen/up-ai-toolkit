<?php
/**
 * Content Analyzer
 * Dedicated file for content analysis and text replacement functions
 *
 * @package UP_AI_Toolkit
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class UPAI_Content_Analyzer {
    
    /**
     * Core instance
     */
    private $core;
    
    /**
     * Supported languages for translation
     */
    private $supported_languages = array(
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'ru' => 'Русский',
        'ja' => '日本語',
        'zh' => '中文',
        'ar' => 'العربية',
    );
    
    /**
     * Constructor
     */
    public function __construct( $core ) {
        $this->core = $core;
        // No automatic SEO generation by default; actions are exposed via REST and editor UI button
    }

    /**
     * Get all text-bearing blocks (recursively) with their paths and plain text
     */
    public function get_text_blocks( $post_id ) {
        $blocks = $this->core->parse_blocks( $post_id );
        $text_blocks = array();
        foreach ( $blocks as $i => $block ) {
            $this->collect_text_blocks( $block, array( $i ), $text_blocks, $post_id );
        }
        return $text_blocks;
    }

    /**
     * Recursively collect text blocks into accumulator
     */
    private function collect_text_blocks( $block, $path, &$acc, $post_id ) {
        $name = isset( $block['blockName'] ) ? $block['blockName'] : '';
        $is_text = in_array( $name, array( 'core/paragraph', 'core/heading', 'core/quote', 'core/list', 'core/freeform' ), true );
        if ( ! $is_text ) {
            $is_text = apply_filters( 'upai_is_text_block', false, $block, $path, $post_id );
        }
        if ( $is_text ) {
            $text = $this->extract_block_text( $block );
            if ( strlen( trim( $text ) ) > 0 ) {
                $acc[] = array(
                    'path' => $path,
                    'index' => isset( $path[0] ) ? $path[0] : 0, // backward compatibility
                    'type' => $name,
                    'text' => $text,
                );
            }
        }
        // Recurse into innerBlocks if present
        if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
            foreach ( $block['innerBlocks'] as $j => $inner ) {
                $this->collect_text_blocks( $inner, array_merge( $path, array( 'inner', $j ) ), $acc, $post_id );
            }
        }
    }

    /**
     * Build a coherent modification prompt across blocks
     */
    private function build_coherent_prompt( $text_blocks, $user_prompt, $use_context = true ) {
        // Limit to avoid extreme token usage
        $max_blocks = 50;
        if ( count( $text_blocks ) > $max_blocks ) {
            $text_blocks = array_slice( $text_blocks, 0, $max_blocks );
        }

        // Global context from settings (optional)
        $tone_line = '';
        $global_line = '';
        if ( $use_context ) {
            $settings = $this->core->get_settings();
            $tone = isset( $settings['tone_of_voice'] ) ? trim( $settings['tone_of_voice'] ) : '';
            $global = isset( $settings['global_instruction'] ) ? trim( wp_strip_all_tags( $settings['global_instruction'] ) ) : '';
            $tone_line = $tone ? "Tone of voice: {$tone}\n" : '';
            $global_line = $global ? "Site context: {$global}\n" : '';
        }

        $instructions = "You are rewriting the following article composed of Gutenberg blocks (including nested blocks). Maintain global coherence (tone, terminology, flow) across blocks.\n";
        $instructions .= $tone_line . $global_line;
        $instructions .= "Apply these editing instructions to the whole article: \n" . $user_prompt . "\n\n";
        $instructions .= "Return ONLY valid JSON with an array named 'updates'. Each item must be either {\"path\": [numbers and 'inner'], \"text\": string} or {\"index\": number, \"text\": string}. Prefer using 'path' for nested blocks. Do not include any extra commentary.\n\n";

        $blocks_snippet = array();
        foreach ( $text_blocks as $b ) {
            // Truncate very long blocks (reduce token usage)
            $snippet = mb_substr( $b['text'], 0, 800 );
            $blocks_snippet[] = sprintf( "- path: %s, type: %s, text: \n%s", json_encode( $b['path'] ), $b['type'], $snippet );
        }
        return $instructions . "Blocks:\n" . implode( "\n\n", $blocks_snippet ) . "\n\nJSON schema example: {\"updates\":[{\"path\":[0,\"inner\",1],\"text\":\"New text...\"}]}";
    }

    /**
     * Apply bulk modifications to blocks given a mapping of index=>text
     */
    public function apply_bulk_modifications( $post_id, $updates ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'invalid_post', __( 'Invalid post ID', 'up-ai-toolkit' ) );
        }

        $blocks = $this->core->parse_blocks( $post_id );
        if ( empty( $blocks ) ) {
            return new WP_Error( 'no_blocks', __( 'No blocks found in post', 'up-ai-toolkit' ) );
        }

        foreach ( $updates as $update ) {
            if ( isset( $update['path'] ) && is_array( $update['path'] ) && array_key_exists( 'text', $update ) ) {
                $this->apply_update_by_path( $blocks, $update['path'], $update['text'] );
                continue;
            }
            if ( isset( $update['index'] ) && array_key_exists( 'text', $update ) ) {
                $i = intval( $update['index'] );
                if ( isset( $blocks[ $i ] ) ) {
                    $blocks[ $i ] = $this->prepare_block_replacement( $blocks[ $i ], $update['text'] );
                }
            }
        }

        $new_content = serialize_blocks( $blocks );
        $result = wp_update_post( array( 'ID' => $post_id, 'post_content' => $new_content ) );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return array( 'success' => true );
    }

    /**
     * Apply an update to a block located by path (e.g., [0, 'inner', 2, 'inner', 0])
     */
    private function apply_update_by_path( &$blocks, $path, $new_text ) {
        $ref =& $blocks;
        $parentRefs = array();
        $lastKey = null;
        // Traverse to target block
        foreach ( $path as $k ) {
            if ( $k === 'inner' ) {
                $lastKey = 'innerBlocks';
                if ( ! isset( $ref[ $lastKey ] ) || ! is_array( $ref[ $lastKey ] ) ) {
                    // Create structure if missing
                    $ref[ $lastKey ] = array();
                }
                $ref =& $ref[ $lastKey ];
            }
            else {
                // numeric index
                if ( is_array( $ref ) && isset( $ref[ $k ] ) ) {
                    $ref =& $ref[ $k ];
                }
                else {
                    return; // invalid path
                }
            }
        }
        // $ref should now be the target block array
        if ( is_array( $ref ) ) {
            $ref = $this->prepare_block_replacement( $ref, $new_text );
        }
    }

    /**
     * Modify whole post coherently
     */
    public function modify_post_coherently( $post_id, $user_prompt, $provider_id = null, $options = array() ) {
        $this->core->log( "Coherent modify for post {$post_id}" );

        if ( ! $provider_id ) {
            $provider_id = $this->core->get_default_provider();
        }
        $provider_config = $this->core->get_provider( $provider_id );
        if ( ! $provider_config ) {
            return new WP_Error( 'no_provider', __( 'No AI provider configured', 'up-ai-toolkit' ) );
        }

        $text_blocks = $this->get_text_blocks( $post_id );
        if ( empty( $text_blocks ) ) {
            return new WP_Error( 'no_text_blocks', __( 'No text blocks found to modify', 'up-ai-toolkit' ) );
        }

        $use_context = ! isset( $options['use_context'] ) || (bool) $options['use_context'];
        $prompt = $this->build_coherent_prompt( $text_blocks, $user_prompt, $use_context );
        $result = UPAI_AI_Providers::send_request( $provider_config, $prompt, array_merge( array( 'max_tokens' => 5000, 'timeout' => 120 ), $options ) );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Parse JSON
        $json = trim( $result['content'] );
        // Strip fenced code blocks if present
        if ( preg_match( '/^```(?:json)?\s*([\s\S]*?)```$/m', $json, $mFence ) ) {
            $json = trim( $mFence[1] );
        }
        $decoded = json_decode( $json, true );
        if ( ! $decoded || ! isset( $decoded['updates'] ) || ! is_array( $decoded['updates'] ) ) {
            // Try to recover JSON fenced in code blocks
            if ( preg_match( '/\{[\s\S]*\}/', $json, $m ) ) {
                $decoded = json_decode( $m[0], true );
            }
        }
        // Accept array root like [{"path":..., "text":...}]
        if ( ! $decoded && preg_match( '/\[[\s\S]*\]/', $json, $mArr ) ) {
            $arr = json_decode( $mArr[0], true );
            if ( is_array( $arr ) ) {
                $decoded = array( 'updates' => $arr );
            }
        }
        if ( ! $decoded || ! isset( $decoded['updates'] ) || ! is_array( $decoded['updates'] ) ) {
            return new WP_Error( 'invalid_ai_response', __( 'AI did not return a valid JSON updates structure', 'up-ai-toolkit' ) );
        }

        $apply = $this->apply_bulk_modifications( $post_id, $decoded['updates'] );
        if ( is_wp_error( $apply ) ) {
            return $apply;
        }

        return array(
            'updated' => count( $decoded['updates'] ),
            'usage'   => isset( $result['usage'] ) ? $result['usage'] : array(),
            'model'   => isset( $result['model'] ) ? $result['model'] : '',
        );
    }
    
    /**
     * Get supported languages
     */
    public function get_supported_languages() {
        return apply_filters( 'upai_supported_languages', $this->supported_languages );
    }

    /**
     * Build common SEO prompt context
     */
    private function build_seo_context( $post_id ) {
        $settings = $this->core->get_settings();
        $site_name = get_bloginfo( 'name' );
        $tone = isset( $settings['tone_of_voice'] ) ? trim( $settings['tone_of_voice'] ) : '';
        $global = isset( $settings['global_instruction'] ) ? trim( wp_strip_all_tags( $settings['global_instruction'] ) ) : '';
        $post = get_post( $post_id );
        $post_title = $post ? get_the_title( $post ) : '';
        $content = $this->core->get_post_content( $post_id );
        return compact( 'settings', 'site_name', 'tone', 'global', 'post_title', 'content' );
    }

    /**
     * Generate SEO title (~50-60 chars)
     */
    public function generate_seo_title( $post_id, $provider_id = null, $options = array() ) {
        $this->core->log( "Generating SEO title for post {$post_id}" );
        $ctx = $this->build_seo_context( $post_id );
        if ( empty( $ctx['content'] ) && empty( $ctx['post_title'] ) ) {
            return new WP_Error( 'empty_content', __( 'Nothing to generate SEO title from', 'up-ai-toolkit' ) );
        }
        if ( ! $provider_id ) {
            $provider_id = $this->core->get_default_provider();
        }
        $provider_config = $this->core->get_provider( $provider_id );
        if ( ! $provider_config ) {
            return new WP_Error( 'no_provider', __( 'No AI provider configured', 'up-ai-toolkit' ) );
        }
        $use_context = ! isset( $options['use_context'] ) || (bool) $options['use_context'];
        $tone_line = '';
        $global_line = '';
        if ( $use_context ) {
            $tone_line = $ctx['tone'] ? "Tone of voice: {$ctx['tone']}\n" : '';
            $global_line = $ctx['global'] ? "Site context: {$ctx['global']}\n" : '';
        }
        $prompt = sprintf(
            "You are an SEO expert. Create a concise, compelling, click-worthy SEO title for a web page.\n%s%sWebsite: %s\nConstraints: 50-60 characters, include main keyword naturally, avoid brand unless helpful, no ALL CAPS, no quotes. Output only the title, no extra text.\n\nPage title: %s\nExcerpt: %s",
            $tone_line,
            $global_line,
            $ctx['site_name'],
            mb_substr( $ctx['post_title'], 0, 120 ),
            mb_substr( $ctx['content'], 0, 1200 )
        );
        $result = UPAI_AI_Providers::send_request( $provider_config, $prompt, array_merge( array( 'max_tokens' => 80 ), $options ) );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $title = trim( wp_strip_all_tags( $result['content'] ) );
        // Post-process length
        if ( mb_strlen( $title ) > 65 ) {
            $title = mb_substr( $title, 0, 65 );
        }
        return array( 'title' => $title, 'usage' => $result['usage'], 'model' => $result['model'] );
    }

    /**
     * Generate SEO meta description (140-160 chars)
     */
    public function generate_meta_description( $post_id, $provider_id = null, $options = array() ) {
        $this->core->log( "Generating meta description for post {$post_id}" );
        $ctx = $this->build_seo_context( $post_id );
        if ( empty( $ctx['content'] ) && empty( $ctx['post_title'] ) ) {
            return new WP_Error( 'empty_content', __( 'Nothing to generate meta description from', 'up-ai-toolkit' ) );
        }
        if ( ! $provider_id ) {
            $provider_id = $this->core->get_default_provider();
        }
        $provider_config = $this->core->get_provider( $provider_id );
        if ( ! $provider_config ) {
            return new WP_Error( 'no_provider', __( 'No AI provider configured', 'up-ai-toolkit' ) );
        }
        $use_context = ! isset( $options['use_context'] ) || (bool) $options['use_context'];
        $tone_line = '';
        $global_line = '';
        if ( $use_context ) {
            $tone_line = $ctx['tone'] ? "Tone of voice: {$ctx['tone']}\n" : '';
            $global_line = $ctx['global'] ? "Site context: {$ctx['global']}\n" : '';
        }
        $prompt = sprintf(
            "Write a persuasive, SEO-friendly meta description for the page below.\n%s%sWebsite: %s\nConstraints: 140-160 characters, single sentence, include a value proposition and a soft call-to-action, no quotes. Output only the description.\n\nPage title: %s\nContent: %s",
            $tone_line,
            $global_line,
            $ctx['site_name'],
            mb_substr( $ctx['post_title'], 0, 160 ),
            mb_substr( $ctx['content'], 0, 2000 )
        );
        $result = UPAI_AI_Providers::send_request( $provider_config, $prompt, array_merge( array( 'max_tokens' => 120 ), $options ) );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        $desc = trim( wp_strip_all_tags( $result['content'] ) );
        if ( mb_strlen( $desc ) > 170 ) {
            $desc = mb_substr( $desc, 0, 170 );
        }
        return array( 'description' => $desc, 'usage' => $result['usage'], 'model' => $result['model'] );
    }

    /**
     * Detect active SEO plugin and return a slug
     */
    private function detect_seo_plugin() {
        if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Frontend' ) ) {
            return 'yoast';
        }
        if ( defined( 'RANK_MATH_VERSION' ) || class_exists( '\\RankMath' ) ) {
            return 'rankmath';
        }
        if ( defined( 'SEOPRESS_VERSION' ) || function_exists( 'seopress_init' ) ) {
            return 'seopress';
        }
        return null;
    }

    /**
     * Save SEO title to the appropriate meta key depending on the SEO plugin
     */
    public function set_seo_title_for_post( $post_id, $title ) {
        $plugin = $this->detect_seo_plugin();
        $saved_key = '';
        if ( $plugin === 'yoast' ) {
            $saved_key = '_yoast_wpseo_title';
        } elseif ( $plugin === 'rankmath' ) {
            $saved_key = 'rank_math_title';
        } elseif ( $plugin === 'seopress' ) {
            $saved_key = '_seopress_titles_title';
        } else {
            $saved_key = '_upai_seo_title';
        }
        update_post_meta( $post_id, $saved_key, wp_kses_post( $title ) );
        return $saved_key;
    }

    /**
     * Save meta description to the appropriate meta key depending on the SEO plugin
     */
    public function set_meta_description_for_post( $post_id, $description ) {
        $plugin = $this->detect_seo_plugin();
        $saved_key = '';
        if ( $plugin === 'yoast' ) {
            $saved_key = '_yoast_wpseo_metadesc';
        } elseif ( $plugin === 'rankmath' ) {
            $saved_key = 'rank_math_description';
        } elseif ( $plugin === 'seopress' ) {
            $saved_key = '_seopress_titles_desc';
        } else {
            $saved_key = '_upai_meta_description';
        }
        update_post_meta( $post_id, $saved_key, wp_kses_post( $description ) );
        return $saved_key;
    }
    
    /**
     * Generate excerpt/summary for a post
     */
    public function generate_excerpt( $post_id, $provider_id = null, $options = array() ) {
        $this->core->log( "Generating excerpt for post {$post_id}" );
        
        // Get post content
        $content = $this->core->get_post_content( $post_id );
        if ( empty( $content ) ) {
            return new WP_Error( 'empty_content', __( 'Post content is empty', 'up-ai-toolkit' ) );
        }
        
        // Get provider
        if ( ! $provider_id ) {
            $provider_id = $this->core->get_default_provider();
        }
        
        $provider_config = $this->core->get_provider( $provider_id );
        if ( ! $provider_config ) {
            return new WP_Error( 'no_provider', __( 'No AI provider configured', 'up-ai-toolkit' ) );
        }
        
        // Build prompt
        $max_length = isset( $options['max_length'] ) ? intval( $options['max_length'] ) : 160;
        $use_context = ! isset( $options['use_context'] ) || (bool) $options['use_context'];
        $tone_line = '';
        $global_line = '';
        if ( $use_context ) {
            $settings = $this->core->get_settings();
            $tone = isset( $settings['tone_of_voice'] ) ? trim( $settings['tone_of_voice'] ) : '';
            $global = isset( $settings['global_instruction'] ) ? trim( wp_strip_all_tags( $settings['global_instruction'] ) ) : '';
            $tone_line = $tone ? "Tone of voice: {$tone}\n" : '';
            $global_line = $global ? "Site context: {$global}\n" : '';
        }
        $prompt = sprintf(
            "Create a clear, SEO-optimized summary for the following text. The summary should be between 120 and %d characters. Focus on the main topic and make it engaging. Do not include URLs.\n%s%s\nText:\n%s",
            $max_length,
            $tone_line,
            $global_line,
            substr( $content, 0, 3000 ) // Limit content to avoid token limits
        );
        
        $prompt = apply_filters( 'upai_excerpt_prompt', $prompt, $post_id, $content );
        
        // Send request to AI
        $result = UPAI_AI_Providers::send_request( $provider_config, $prompt, $options );
        
        if ( is_wp_error( $result ) ) {
            $this->core->log( "Error generating excerpt: " . $result->get_error_message(), 'error' );
            return $result;
        }
        
        $excerpt = $result['content'];
        
        // Apply filters
        $excerpt = apply_filters( 'upai_generated_excerpt', $excerpt, $post_id );
        
        $this->core->log( "Excerpt generated successfully for post {$post_id}" );
        
        return array(
            'excerpt' => $excerpt,
            'usage' => $result['usage'],
            'model' => $result['model'],
        );
    }
    
    /**
     * Translate text to target language
     */
    public function translate_text( $text, $target_language, $provider_id = null, $options = array() ) {
        $this->core->log( "Translating text to {$target_language}" );
        
        if ( empty( $text ) ) {
            return new WP_Error( 'empty_text', __( 'Text is empty', 'up-ai-toolkit' ) );
        }
        
        // Validate language
        $languages = $this->get_supported_languages();
        if ( ! isset( $languages[ $target_language ] ) ) {
            return new WP_Error( 'invalid_language', __( 'Invalid target language', 'up-ai-toolkit' ) );
        }
        
        // Get provider
        if ( ! $provider_id ) {
            $provider_id = $this->core->get_default_provider();
        }
        
        $provider_config = $this->core->get_provider( $provider_id );
        if ( ! $provider_config ) {
            return new WP_Error( 'no_provider', __( 'No AI provider configured', 'up-ai-toolkit' ) );
        }
        
        // Build prompt
        $language_name = $languages[ $target_language ];
        $use_context = ! isset( $options['use_context'] ) || (bool) $options['use_context'];
        $tone_line = '';
        $global_line = '';
        if ( $use_context ) {
            $settings = $this->core->get_settings();
            $tone = isset( $settings['tone_of_voice'] ) ? trim( $settings['tone_of_voice'] ) : '';
            $global = isset( $settings['global_instruction'] ) ? trim( wp_strip_all_tags( $settings['global_instruction'] ) ) : '';
            $tone_line = $tone ? "Preferred tone of voice: {$tone}\n" : '';
            $global_line = $global ? "Site context: {$global}\n" : '';
        }
        $prompt = sprintf(
            "Translate the following text to %s (%s). Maintain tone, style and intent. If a preferred tone is provided, adapt accordingly. Provide only the translated text.\n%s%s\nText to translate:\n%s",
            $language_name,
            $target_language,
            $tone_line,
            $global_line,
            $text
        );
        
        $prompt = apply_filters( 'upai_translation_prompt', $prompt, $text, $target_language );
        
        // Send request to AI
        $result = UPAI_AI_Providers::send_request( $provider_config, $prompt, $options );
        
        if ( is_wp_error( $result ) ) {
            $this->core->log( "Error translating text: " . $result->get_error_message(), 'error' );
            return $result;
        }
        
        $translated_text = $result['content'];
        
        // Apply filters
        $translated_text = apply_filters( 'upai_translated_text', $translated_text, $text, $target_language );
        
        $this->core->log( "Text translated successfully to {$target_language}" );
        
        return array(
            'translated_text' => $translated_text,
            'source_language' => 'auto',
            'target_language' => $target_language,
            'usage' => $result['usage'],
            'model' => $result['model'],
        );
    }
    
    /**
     * Modify text with custom prompt
     */
    public function modify_text( $text, $custom_prompt, $provider_id = null, $options = array() ) {
        $this->core->log( "Modifying text with custom prompt" );
        
        if ( empty( $text ) ) {
            return new WP_Error( 'empty_text', __( 'Text is empty', 'up-ai-toolkit' ) );
        }
        
        if ( empty( $custom_prompt ) ) {
            return new WP_Error( 'empty_prompt', __( 'Custom prompt is empty', 'up-ai-toolkit' ) );
        }
        
        // Get provider
        if ( ! $provider_id ) {
            $provider_id = $this->core->get_default_provider();
        }
        
        $provider_config = $this->core->get_provider( $provider_id );
        if ( ! $provider_config ) {
            return new WP_Error( 'no_provider', __( 'No AI provider configured', 'up-ai-toolkit' ) );
        }
        
        // Build prompt
        $use_context = ! isset( $options['use_context'] ) || (bool) $options['use_context'];
        $tone_line = '';
        $global_line = '';
        if ( $use_context ) {
            $settings = $this->core->get_settings();
            $tone = isset( $settings['tone_of_voice'] ) ? trim( $settings['tone_of_voice'] ) : '';
            $global = isset( $settings['global_instruction'] ) ? trim( wp_strip_all_tags( $settings['global_instruction'] ) ) : '';
            $tone_line = $tone ? "Tone of voice: {$tone}\n" : '';
            $global_line = $global ? "Site context: {$global}\n" : '';
        }
        $prompt = sprintf(
            "%s\n%s%s\nOriginal text:\n%s\n\nProvide only the modified text without any additional explanation.",
            $custom_prompt,
            $tone_line,
            $global_line,
            $text
        );
        
        $prompt = apply_filters( 'upai_modify_prompt', $prompt, $text, $custom_prompt );
        
        // Send request to AI
        $result = UPAI_AI_Providers::send_request( $provider_config, $prompt, $options );
        
        if ( is_wp_error( $result ) ) {
            $this->core->log( "Error modifying text: " . $result->get_error_message(), 'error' );
            return $result;
        }
        
        $modified_text = $result['content'];
        
        // Apply filters
        $modified_text = apply_filters( 'upai_modified_text', $modified_text, $text, $custom_prompt );
        
        $this->core->log( "Text modified successfully" );
        
        return array(
            'modified_text' => $modified_text,
            'usage' => $result['usage'],
            'model' => $result['model'],
        );
    }
    
    /**
     * Extract text from Gutenberg block
     */
    public function extract_block_text( $block ) {
        return $this->core->get_block_text_content( $block );
    }
    
    /**
     * Replace text in Gutenberg block
     * This function prepares the block structure for text replacement
     */
    public function prepare_block_replacement( $block, $new_text ) {
        // For paragraph blocks
        if ( $block['blockName'] === 'core/paragraph' ) {
            return array(
                'blockName' => $block['blockName'],
                'attrs' => $block['attrs'],
                'innerHTML' => '<p>' . esc_html( $new_text ) . '</p>',
                'innerContent' => array( '<p>' . esc_html( $new_text ) . '</p>' ),
            );
        }
        
        // For heading blocks
        if ( $block['blockName'] === 'core/heading' ) {
            $level = isset( $block['attrs']['level'] ) ? intval( $block['attrs']['level'] ) : 2;
            $tag = 'h' . $level;
            return array(
                'blockName' => $block['blockName'],
                'attrs' => $block['attrs'],
                'innerHTML' => '<' . $tag . '>' . esc_html( $new_text ) . '</' . $tag . '>',
                'innerContent' => array( '<' . $tag . '>' . esc_html( $new_text ) . '</' . $tag . '>' ),
            );
        }
        
        // For other blocks, return as-is with a filter hook
        return apply_filters( 'upai_prepare_block_replacement', $block, $new_text );
    }
    
    /**
     * Inject modified text back into post content
     */
    public function inject_text_into_post( $post_id, $block_index, $new_text ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'invalid_post', __( 'Invalid post ID', 'up-ai-toolkit' ) );
        }
        
        // Parse blocks
        $blocks = $this->core->parse_blocks( $post_id );
        
        if ( ! isset( $blocks[ $block_index ] ) ) {
            return new WP_Error( 'invalid_block', __( 'Invalid block index', 'up-ai-toolkit' ) );
        }
        
        // Replace block content
        $blocks[ $block_index ] = $this->prepare_block_replacement( $blocks[ $block_index ], $new_text );
        
        // Serialize blocks back to content
        $new_content = serialize_blocks( $blocks );
        
        // Update post
        $result = wp_update_post( array(
            'ID' => $post_id,
            'post_content' => $new_content,
        ) );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        $this->core->log( "Text injected successfully into post {$post_id}, block {$block_index}" );
        
        return array(
            'success' => true,
            'post_id' => $post_id,
            'block_index' => $block_index,
        );
    }
}
