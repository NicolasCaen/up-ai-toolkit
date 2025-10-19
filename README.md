# UP AI Toolkit

**Version:** 1.0.0  
**Author:** GEHIN Nicolas  
**License:** GPL v2 or later

Multi-AI provider toolkit for content analysis, translation, summarization and text modification in the WordPress Gutenberg editor.

## Features

### ✨ Multi-AI Provider Support
Configure and manage multiple AI providers from a single interface:
- **OpenAI (ChatGPT)** - GPT-4o, GPT-4o-mini, GPT-4-turbo, GPT-3.5-turbo
- **Google Gemini** - Gemini 2.0 Flash, Gemini 1.5 Pro, Gemini 1.5 Flash
- **Mistral AI** - Mistral Large, Medium, Small
- **Anthropic Claude** - Claude 3.5 Sonnet, Claude 3.5 Haiku, Claude 3 Opus
- **OpenRouter** - Access to many models through a single API

### 📝 Content Analysis & Modification
- **Generate Excerpt**: Automatically create SEO-optimized excerpts for your posts
- **Translate**: Translate block content to 12+ languages
- **Modify Text**: Use custom prompts to rewrite, improve, or transform text

### 🧪 Testing Framework
Built-in test suite to validate:
- Gutenberg block parsing
- Text extraction from blocks
- Text injection into blocks
- AI provider connectivity

### 🔌 REST API
Full REST API for integration with external tools:
- `/upai/v1/excerpt` - Generate excerpts
- `/upai/v1/translate` - Translate text
- `/upai/v1/modify` - Modify text with custom prompts
- `/upai/v1/extract-block` - Extract text from blocks (testing)
- `/upai/v1/inject-text` - Inject text into blocks (testing)

## Installation

1. Upload the `up-ai-toolkit` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin panel
3. Go to **UP AI Toolkit** > **Settings** to configure your AI providers

## Configuration

### Adding an AI Provider

1. Navigate to **UP AI Toolkit** > **Settings**
2. Click **+ Add Provider**
3. Configure the provider:
   - **Provider Name**: Choose a descriptive name (e.g., "My OpenAI")
   - **Provider Type**: Select from OpenAI, Gemini, Mistral, Claude, or OpenRouter
   - **API Key**: Enter your API key from the provider
   - **Model**: Select or enter the model to use
   - **Set as Default**: Check to make this the default provider
4. Click **Test Connection** to verify the configuration
5. Click **Save Changes**

### Getting API Keys

- **OpenAI**: https://platform.openai.com/api-keys
- **Google Gemini**: https://makersuite.google.com/app/apikey
- **Mistral AI**: https://console.mistral.ai/api-keys/
- **Anthropic**: https://console.anthropic.com/settings/keys
- **OpenRouter**: https://openrouter.ai/keys

## Usage

### In the Gutenberg Editor

1. Open any post or page in the Gutenberg editor
2. Click the **three dots** (⋮) in the top right corner
3. Select **UP AI Toolkit** from the menu

A sidebar will appear with three sections:

#### Generate Excerpt
- Click **Generate Excerpt** to create an SEO-optimized summary
- The excerpt will be automatically added to your post

#### Translate
1. Select a block with text content
2. Choose the **Target Language** from the dropdown
3. Click **Translate**
4. The block will be updated with the translated text

#### Modify Text
1. Select a block with text content
2. Enter a custom **Prompt** (e.g., "Make this more concise")
3. Click **Modify Text**
4. The block will be updated with the AI-generated text

### Supported Languages

- English (en)
- Français (fr)
- Español (es)
- Deutsch (de)
- Italiano (it)
- Português (pt)
- Nederlands (nl)
- Polski (pl)
- Русский (ru)
- 日本語 (ja)
- 中文 (zh)
- العربية (ar)

## Testing

If `WP_DEBUG` is enabled, you'll see a **Tests** submenu:

1. Go to **UP AI Toolkit** > **Tests**
2. Click **Run All Tests** to validate:
   - Block parsing functionality
   - Text extraction from blocks
   - Text injection into blocks
   - Provider connectivity

### Manual API Tests

The Tests page also provides manual testing tools:
- **Extract Block Text**: Test text extraction from specific blocks
- **Inject Text**: Test text injection into blocks
- **AI Text Modification**: Test AI modification with custom prompts

## REST API Usage

### Authentication

All API endpoints require WordPress REST API authentication via nonce or application passwords.

### Example: Translate Text

```bash
curl -X POST 'https://yoursite.com/wp-json/upai/v1/translate' \
  -H 'Content-Type: application/json' \
  -H 'X-WP-Nonce: YOUR_NONCE' \
  -d '{
    "text": "Hello world",
    "target_language": "fr"
  }'
```

### Example: Generate Excerpt

```bash
curl -X POST 'https://yoursite.com/wp-json/upai/v1/excerpt' \
  -H 'Content-Type: application/json' \
  -H 'X-WP-Nonce: YOUR_NONCE' \
  -d '{
    "post_id": 123
  }'
```

### Example: Modify Text

```bash
curl -X POST 'https://yoursite.com/wp-json/upai/v1/modify' \
  -H 'Content-Type: application/json' \
  -H 'X-WP-Nonce: YOUR_NONCE' \
  -d '{
    "text": "This is a long paragraph...",
    "prompt": "Make this more concise"
  }'
```

## Hooks & Filters

### Filters

#### `upai_supported_languages`
Modify the list of supported translation languages.

```php
add_filter('upai_supported_languages', function($languages) {
    $languages['ko'] = '한국어';
    return $languages;
});
```

#### `upai_excerpt_prompt`
Customize the prompt used for excerpt generation.

```php
add_filter('upai_excerpt_prompt', function($prompt, $post_id, $content) {
    return "Create a short summary in 100 characters:\n\n" . $content;
}, 10, 3);
```

#### `upai_translation_prompt`
Customize the translation prompt.

```php
add_filter('upai_translation_prompt', function($prompt, $text, $target_language) {
    return "Translate to $target_language (formal tone):\n\n$text";
}, 10, 3);
```

#### `upai_modify_prompt`
Customize the text modification prompt.

```php
add_filter('upai_modify_prompt', function($prompt, $text, $custom_prompt) {
    return "$custom_prompt\n\nKeep it under 200 words:\n\n$text";
}, 10, 3);
```

## Architecture

### File Structure

```
up-ai-toolkit/
├── up-ai-toolkit.php          # Main plugin file
├── includes/
│   ├── class-upai-core.php              # Core functionality
│   ├── class-upai-admin.php             # Admin interface
│   ├── class-upai-ai-providers.php      # AI provider integrations
│   ├── class-upai-content-analyzer.php  # Content analysis functions
│   ├── class-upai-rest-api.php          # REST API endpoints
│   └── class-upai-tests.php             # Testing framework
├── admin/
│   ├── admin-page.php         # Settings page template
│   ├── tests-page.php         # Tests page template
│   ├── css/admin.css          # Admin styles
│   └── js/admin.js            # Admin JavaScript
├── assets/
│   └── js/gutenberg-integration.js  # Gutenberg editor integration
└── README.md
```

### Class Responsibilities

- **UPAI_Core**: Settings management, content parsing
- **UPAI_Admin**: Admin interface, script enqueuing
- **UPAI_AI_Providers**: Multi-provider API communication
- **UPAI_Content_Analyzer**: Text analysis and modification logic
- **UPAI_REST_API**: REST API endpoints
- **UPAI_Tests**: Automated testing functions

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Gutenberg editor enabled
- At least one configured AI provider

## Security

- API keys are stored in the WordPress database
- All REST endpoints require authentication
- Input sanitization on all user inputs
- Nonce verification for admin actions

## Future Features (Roadmap)

- ✅ Multi-AI provider support
- ✅ Content analysis functions
- ✅ Gutenberg integration
- ✅ REST API
- ✅ Testing framework
- 🔄 Bulk content generation via REST API
- 🔄 Page generation from templates
- 🔄 Content scheduling
- 🔄 Analytics & usage tracking

## Troubleshooting

### "No AI providers configured" error
- Go to Settings and add at least one AI provider with a valid API key

### Gutenberg sidebar not showing
- Make sure the plugin is activated
- Try refreshing the editor page
- Check browser console for JavaScript errors

### API request fails
- Verify your API key is correct
- Check if you have sufficient credits/quota with the provider
- Enable "Log Requests" in settings and check debug.log

### Translation not working
- Ensure you've selected a block with text content
- Verify the target language is supported
- Check that your AI provider supports the selected model

## Support

For issues, questions, or feature requests:
- GitHub: [github.com/nicolas-gehin/up-ai-toolkit](https://github.com/nicolas-gehin/up-ai-toolkit)
- Email: contact@nicolas-gehin.com

## License

GPL v2 or later

## Credits

Developed by **GEHIN Nicolas**

Inspired by AI Engine plugin architecture and best practices.
