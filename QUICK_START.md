# UP AI Toolkit - Quick Start Guide

## 🚀 Installation en 3 étapes

### 1. Activer le plugin
Le plugin est déjà installé dans `/wp-content/plugins/up-ai-toolkit/`

1. Allez dans **Extensions** > **Extensions installées**
2. Trouvez **UP AI Toolkit**
3. Cliquez sur **Activer**

### 2. Configurer un provider AI
1. Allez dans **UP AI Toolkit** > **Settings**
2. Cliquez sur **+ Add Provider**
3. Remplissez les champs :
   - **Nom** : Mon OpenAI (ou autre nom)
   - **Type** : Choisir OpenAI, Gemini, Mistral, Claude ou OpenRouter
   - **Clé API** : Votre clé API du provider
   - **Modèle** : Sélectionner le modèle (ex: gpt-4o-mini)
   - Cocher **Set as Default** pour le premier provider
4. Cliquez sur **Test Connection** pour vérifier
5. Cliquez sur **Enregistrer les modifications**

### 3. Utiliser dans Gutenberg
1. Ouvrez un article ou une page
2. Cliquez sur les **trois points** (⋮) en haut à droite
3. Sélectionnez **UP AI Toolkit**
4. La sidebar s'ouvre avec 3 fonctions :
   - **Generate Excerpt** : Créer un résumé automatique
   - **Translate** : Traduire un bloc sélectionné
   - **Modify Text** : Modifier avec un prompt personnalisé

## 📋 Obtenir des clés API

### OpenAI (ChatGPT)
1. Créez un compte sur https://platform.openai.com
2. Allez dans https://platform.openai.com/api-keys
3. Cliquez sur **Create new secret key**
4. Copiez la clé (elle commence par `sk-...`)

**Modèles recommandés :** `gpt-4o-mini` (économique) ou `gpt-4o` (puissant)

### Google Gemini
1. Créez un compte sur https://makersuite.google.com
2. Allez dans https://makersuite.google.com/app/apikey
3. Cliquez sur **Create API key**
4. Copiez la clé

**Modèles recommandés :** `gemini-2.0-flash-exp` (rapide) ou `gemini-1.5-pro` (puissant)

### Mistral AI
1. Créez un compte sur https://console.mistral.ai
2. Allez dans https://console.mistral.ai/api-keys/
3. Cliquez sur **Create new key**
4. Copiez la clé

**Modèles recommandés :** `mistral-small-latest` (économique) ou `mistral-large-latest` (puissant)

### Anthropic Claude
1. Créez un compte sur https://console.anthropic.com
2. Allez dans https://console.anthropic.com/settings/keys
3. Cliquez sur **Create Key**
4. Copiez la clé

**Modèles recommandés :** `claude-3-5-haiku-20241022` (rapide) ou `claude-3-5-sonnet-20241022` (puissant)

### OpenRouter
1. Créez un compte sur https://openrouter.ai
2. Allez dans https://openrouter.ai/keys
3. Cliquez sur **Create Key**
4. Copiez la clé

**Modèle :** `auto` (OpenRouter choisit automatiquement)

## 🎯 Exemples d'utilisation

### Générer un résumé
1. Ouvrez un article
2. Ouvrez la sidebar **UP AI Toolkit**
3. Section **Generate Excerpt** > Cliquez sur **Generate Excerpt**
4. L'extrait est généré et ajouté automatiquement

### Traduire un bloc
1. Sélectionnez un bloc de texte (paragraphe, titre, etc.)
2. Section **Translate** > Choisir la langue cible (ex: Français)
3. Cliquez sur **Translate**
4. Le bloc est traduit automatiquement

### Modifier un texte avec un prompt
1. Sélectionnez un bloc de texte
2. Section **Modify Text** > Entrez un prompt comme :
   - "Rends ce texte plus concis"
   - "Réécris dans un ton professionnel"
   - "Améliore la grammaire et l'orthographe"
   - "Transforme en liste à puces"
3. Cliquez sur **Modify Text**
4. Le bloc est modifié selon votre demande

## 🧪 Tester le plugin

Si `WP_DEBUG` est activé dans `wp-config.php` :

1. Allez dans **UP AI Toolkit** > **Tests**
2. Cliquez sur **Run All Tests**
3. Vérifiez que tous les tests passent (✓)

Les tests vérifient :
- Le parsing des blocs Gutenberg
- L'extraction de texte des blocs
- L'injection de texte dans les blocs
- La connexion aux providers AI

## 🔧 API REST

Le plugin expose une API REST pour l'intégration externe :

```bash
# Générer un extrait
POST /wp-json/upai/v1/excerpt
{
  "post_id": 123
}

# Traduire du texte
POST /wp-json/upai/v1/translate
{
  "text": "Hello world",
  "target_language": "fr"
}

# Modifier du texte
POST /wp-json/upai/v1/modify
{
  "text": "Long text...",
  "prompt": "Make it shorter"
}
```

Authentification requise via `X-WP-Nonce` header.

## ⚙️ Configuration avancée

### Activer les logs
1. **Settings** > Cocher **Log Requests**
2. Les requêtes API sont enregistrées dans `wp-content/debug.log`
3. Utile pour le débogage

### Mode test
1. **Settings** > Cocher **Test Mode**
2. Les réponses AI sont simulées (pas de consommation de crédits)
3. Utile pour le développement

### Plusieurs providers
Vous pouvez configurer plusieurs providers :
- Un provider OpenAI pour les textes courts
- Un provider Gemini pour les traductions
- Un provider Claude pour les modifications complexes

Choisissez le provider par défaut qui sera utilisé automatiquement.

## 🐛 Résolution de problèmes

### La sidebar ne s'affiche pas
- Actualisez la page de l'éditeur
- Vérifiez que le plugin est activé
- Regardez la console JavaScript (F12) pour les erreurs

### Erreur "No AI provider configured"
- Ajoutez au moins un provider dans **Settings**
- Vérifiez que la clé API est correcte

### La traduction ne fonctionne pas
- Sélectionnez d'abord un bloc avec du texte
- Choisissez une langue cible
- Vérifiez que votre provider supporte la traduction

### Test de connexion échoue
- Vérifiez votre clé API
- Vérifiez que vous avez des crédits disponibles
- Testez avec un autre modèle

## 📞 Support

Pour toute question :
- Consultez le `README.md` complet
- Vérifiez les logs dans **Tests** > Manuel
- Email : contact@nicolas-gehin.com

## 🎉 Prêt à utiliser !

Vous pouvez maintenant :
1. ✅ Générer des extraits automatiquement
2. ✅ Traduire vos contenus en 12+ langues
3. ✅ Modifier vos textes avec l'IA
4. ✅ Intégrer via l'API REST

Bon développement ! 🚀
