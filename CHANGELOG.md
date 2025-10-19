## [1.2.0] - 2025-10-19

### Ajouté
- Metabox « Traduire toute la page » dans l’éditeur: sélection de la langue et traduction de tous les blocs texte via `/upai/v1/translate-post`.
- Boutons de génération SEO intégrés à côté des champs (icône seule) pour Title et Description.

### Modifié
- Chargement des icônes via URL propre (suppression du `?ver=...` avant remplacement du chemin) pour éviter les 403.
- Alignement des champs SEO et des boutons sur `flex-end` pour une meilleure cohérence visuelle.

### Corrigé
- Fallback d’ouverture de la sidebar pour garantir l’ouverture du bon panneau même si le menu n’est pas monté.

## [1.1.0] - 2025-10-19

### Added
- Gutenberg quick-toggle button near Preview to open the plugin sidebar. Icon loaded from `assets/images/icon.svg` (replaceable without code changes).
- SEO generation controls now include separate buttons: Generate Title, Generate Description, Generate Both.
- Preview workflow: generated SEO Title/Description fill preview fields, allowing manual edits before saving.
- Checkboxes to apply Title/Description to Yoast on post save (no automatic post update).
- Global context toggle (tone of voice + site instruction) moved to Provider panel and applied to all prompts.

### Changed
- REST endpoints accept `use_context` to include/exclude global context in prompts.
- Sidebar UI reorganized: Provider panel hosts context toggle; SEO panel focuses on generation + preview.

### Fixed
- Improved Yoast UI sync: avoid auto-saving; apply metas only on user save; multiple fallbacks to open the correct plugin sidebar panel.

# Changelog

All notable changes to UP AI Toolkit will be documented in this file.

## [1.0.1] - 2025-10-19

### Fixed
- REST auth errors in Gutenberg sidebar by switching to `wp.apiFetch` and adding a fetch fallback with nonce and credentials.
- Admin provider form validation to ignore hidden template and trim values.

### Improved
- OpenRouter integration: normalize `auto` to `openrouter/auto`, include `Referer` and `X-Title` headers, and provide clearer error messages for invalid models.
- Admin “Test Connection” no longer relies on `wpApiSettings`, now uses localized `restUrl`/`restNonce`.

### Changed
- Bumped plugin version to 1.0.1.

## [1.0.0] - 2025-01-19

### Added
- Multi-AI provider support (OpenAI, Gemini, Mistral, Claude, OpenRouter)
- Settings page for configuring AI providers
- Gutenberg sidebar integration with three main features:
  - Generate excerpt/summary
  - Translate text to 12+ languages
  - Modify text with custom prompts
- REST API with 6 endpoints for external integrations
- Dedicated content analyzer module (`class-upai-content-analyzer.php`)
- Comprehensive testing framework with automated and manual tests
- Test page for validating block text extraction and injection
- Admin interface with provider management
- Support for multiple AI providers with default selection
- Provider connection testing
- Request logging for debugging
- Block text extraction and injection functions
- Filters and hooks for customization

### Technical Details
- PHP 7.4+ required
- WordPress 5.8+ required
- Modular architecture with separate classes for each functionality
- REST API authentication via WordPress nonces
- Gutenberg block manipulation support for paragraph, heading, quote, and list blocks

### Security
- API keys stored securely in WordPress database
- All REST endpoints require authentication
- Input sanitization and validation
- Nonce verification for admin actions
