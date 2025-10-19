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
