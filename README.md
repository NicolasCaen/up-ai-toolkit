# UP AI Toolkit

**Version :** 1.2.0  
**Auteur :** GEHIN Nicolas  
**Licence :** GPL v2 ou ultérieure

Boîte à outils multi-fournisseurs d’IA pour l’analyse de contenu, la traduction, le résumé et la modification de texte dans l’éditeur Gutenberg de WordPress.

## Nouveautés (1.2.0)

- Metabox « Traduire toute la page »: choisissez la langue cible et traduisez tous les blocs texte de l’article.
- Boutons de génération SEO (icône seule) placés à droite des champs Title/Description (prévisualisation).
- Correction de l’URL d’icône (suppression du `?ver=...` avant remplacement) pour éviter les 403.
- Alignement des champs et boutons SEO sur `flex-end` pour une meilleure cohérence visuelle.

## Nouveautés (1.1.0)

- Bouton d’accès rapide près de “Prévisualiser” pour ouvrir la barre latérale du plugin (icône chargée depuis `assets/images/icon.svg`).
- Boutons séparés pour la génération SEO (Title, Description, Both) avec champs de prévisualisation modifiables avant enregistrement.
- Cases à cocher pour appliquer le Title/Description à Yoast uniquement à l’enregistrement (aucune mise à jour automatique).
- Le toggle Contexte global (tone of voice + instruction du site) est déplacé dans le panneau Provider et s’applique à tous les prompts.
- Les endpoints REST acceptent `use_context` pour inclure/exclure le contexte global.

## Nouveautés (1.0.1)

- **[Auth]** Correction des appels REST dans l’éditeur Gutenberg: utilisation de `wp.apiFetch` + fallback avec nonce et `credentials: 'same-origin'`.
- **[Admin]** Correction de la validation du formulaire Provider (exclut le template caché, trim des champs).
- **[OpenRouter]** Normalisation du modèle `auto` en `openrouter/auto`, entêtes `Referer` et `X-Title`, messages d’erreur plus clairs si modèle invalide.
- **[Admin REST]** Le bouton "Test Connection" n’utilise plus `wpApiSettings`, mais des variables localisées (`restUrl`, `restNonce`).

## Fonctionnalités

### ✨ Support multi-fournisseurs d’IA
Configurez et gérez plusieurs fournisseurs d’IA depuis une interface unique :
- **OpenAI (ChatGPT)** – GPT-4o, GPT-4o-mini, GPT-4-turbo, GPT-3.5-turbo
- **Google Gemini** – Gemini 2.0 Flash, Gemini 1.5 Pro, Gemini 1.5 Flash
- **Mistral AI** – Mistral Large, Medium, Small
- **Anthropic Claude** – Claude 3.5 Sonnet, Claude 3.5 Haiku, Claude 3 Opus
- **OpenRouter** – Accès à de nombreux modèles via une API unique

### 📝 Analyse et modification de contenu
- **Generate Excerpt** : génère automatiquement des extraits optimisés SEO
- **Translate** : traduit le contenu des blocs en 12+ langues
- **Modify Text** : utilise des prompts personnalisés pour réécrire/optimiser le texte

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

## Utilisation

### Dans l’éditeur Gutenberg

1. Ouvrez un article/une page dans l’éditeur Gutenberg.
2. Cliquez sur les **trois points** (⋮) en haut à droite ou utilisez le bouton d’accès rapide.
3. Sélectionnez **UP AI Toolkit**.

Une barre latérale s’ouvre avec plusieurs sections :

#### Generate Excerpt
- Cliquez sur **Generate Excerpt** pour créer un résumé optimisé SEO.
- L’extrait est ajouté automatiquement à l’article.

#### Translate
1. Sélectionnez un bloc contenant du texte.
2. Choisissez la **langue cible**.
3. Cliquez sur **Translate**.
4. Le texte du bloc est remplacé par la traduction.

#### Modify Text
1. Sélectionnez un bloc contenant du texte.
2. Saisissez un **Prompt** personnalisé (ex. « Rendre ce texte plus concis »).
3. Cliquez sur **Modify Text**.
4. Le texte du bloc est mis à jour avec la version générée par l’IA.

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

## Tests

Si `WP_DEBUG` est activé, un sous-menu **Tests** apparaît :

1. Allez dans **UP AI Toolkit** > **Tests**
2. Cliquez sur **Run All Tests** pour vérifier :
   - Le parsing des blocs
   - L’extraction de texte depuis les blocs
   - L’injection de texte dans les blocs
   - La connectivité aux fournisseurs

### Tests API manuels

La page Tests propose aussi des outils de test manuel :
- **Extract Block Text** : tester l’extraction de texte d’un bloc
- **Inject Text** : tester l’injection de texte dans un bloc
- **AI Text Modification** : tester la modification de texte avec prompt

## Utilisation de l’API REST

### Authentification

Tous les endpoints nécessitent l’authentification REST WordPress (nonce ou application passwords).

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

### Filtres

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

### Structure des fichiers

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

### Rôles des classes

- **UPAI_Core** : gestion des réglages, parsing de contenu
- **UPAI_Admin** : interface d’admin, enqueue des scripts
- **UPAI_AI_Providers** : communication API multi-fournisseurs
- **UPAI_Content_Analyzer** : logique d’analyse et de modification de texte
- **UPAI_REST_API** : endpoints REST
- **UPAI_Tests** : tests automatisés

## Prérequis

- WordPress 5.8+
- PHP 7.4+
- Éditeur Gutenberg activé
- Au moins un fournisseur d’IA configuré

## Sécurité

- Les clés API sont stockées dans la base WordPress
- Tous les endpoints REST nécessitent une authentification
- Assainissement des entrées utilisateurs
- Vérification de nonce pour les actions admin

## Fonctionnalités futures (Feuille de route)

- ✅ Support multi-fournisseurs d’IA
- ✅ Fonctions d’analyse de contenu
- ✅ Intégration Gutenberg
- ✅ API REST
- ✅ Cadre de tests
- 🔄 Génération de contenu en masse via REST
- 🔄 Génération de pages depuis des templates
- 🔄 Planification de contenu
- 🔄 Analytics & suivi d’usage

## Dépannage

### Erreur « No AI providers configured »
- Allez dans les réglages et ajoutez au moins un fournisseur avec une clé valide

### La sidebar Gutenberg n’apparaît pas
- Assurez-vous que le plugin est activé
- Rafraîchissez la page de l’éditeur
- Consultez la console du navigateur pour des erreurs JS

### L’appel API échoue
- Vérifiez votre clé API
- Vérifiez votre crédit/quota chez le fournisseur
- Activez « Log Requests » dans les réglages et consultez `debug.log`

### La traduction ne fonctionne pas
- Sélectionnez un bloc contenant du texte
- Vérifiez que la langue cible est supportée
- Vérifiez que le modèle choisi la supporte

## Support

Pour les problèmes, questions ou demandes de fonctionnalités :
- GitHub : [github.com/nicolas-gehin/up-ai-toolkit](https://github.com/nicolas-gehin/up-ai-toolkit)
- Email : contact@nicolas-gehin.com

## Licence

GPL v2 ou ultérieure

## Crédits

Développé par **GEHIN Nicolas**

Inspiré des bonnes pratiques et de l’architecture du plugin AI Engine.
