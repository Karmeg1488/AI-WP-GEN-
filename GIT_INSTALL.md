# AI WP GEN - WordPress AI Content & Styling Generator

<div align="center">

[![Version](https://img.shields.io/badge/version-1.5.55-blue?style=flat-square)](https://gitlab.com/stas.karm/ai-news-generator/-/releases)
[![License](https://img.shields.io/badge/license-MIT%2FGPLv2-green?style=flat-square)](LICENSE)
[![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue?style=flat-square)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/php-7.2%2B-purple?style=flat-square)](https://php.net)

**The most powerful WordPress plugin for AI-driven content and website generation powered by OpenAI GPT-4 and DALL-E.**

[Features](#features) • [Installation](#installation) • [Configuration](#configuration) • [Documentation](#documentation) • [Contributing](#contributing)

</div>

---

## 🌟 Overview

AI WP GEN is a comprehensive WordPress plugin that leverages OpenAI's GPT-4 and DALL-E to automatically generate:

- **Complete websites** with homepage, about, contact, and policy pages
- **Professional content** including articles, authors, and categories
- **AI-generated images** for hero sections, about pages, and featured images
- **Custom styling** with AI-generated CSS based on your preferences
- **Legal compliance** with automatically generated privacy policies, terms, and cookie policies
- **Multi-language support** for 10+ languages
- **Professional branding** with logos, favicons, and site titles

## ✨ Features

### 🎨 Content Generation
- ✅ Automatic article generation with AI-written content
- ✅ Author and category auto-creation
- ✅ Multi-language support (English, Polish, German, Hungarian, Ukrainian, Turkish, Italian, Czech, French, Dutch)
- ✅ Custom prompts to guide content creation
- ✅ Intelligent featured image generation

### 🏠 Homepage Creation
- ✅ Complete homepage with Hero section
- ✅ About Us section with company description
- ✅ Latest blog posts display
- ✅ Customer testimonials section
- ✅ AI-generated responsive layout

### 📋 Policy Pages
- ✅ Privacy Policy (12 comprehensive sections)
- ✅ Terms & Conditions (13 comprehensive sections)
- ✅ Cookie Policy (14 comprehensive sections, GDPR compliant)

### 🎯 Site Branding
- ✅ AI-generated logo
- ✅ Favicon creation and management
- ✅ Custom site title and tagline
- ✅ Professional CSS stylesheet generation
- ✅ Theme-aware styling

### 🔒 Security & Compliance
- ✅ OpenAI API key encryption
- ✅ Nonce validation on all AJAX endpoints
- ✅ Permission checks (manage_options capability)
- ✅ Comprehensive input sanitization
- ✅ GDPR-compliant policy generation

## 📦 Installation

### Method 1: Manual Installation
1. Download the plugin from [GitLab releases](https://gitlab.com/stas.karm/ai-news-generator/-/releases)
2. Extract to `/wp-content/plugins/`
3. Activate in WordPress admin panel

### Method 2: From Repository
```bash
cd /path/to/wp-content/plugins/
git clone https://gitlab.com/stas.karm/ai-news-generator.git ai-news-generator
```

## ⚙️ Configuration

### Step 1: Get Your OpenAI API Key
1. Visit [OpenAI Platform](https://platform.openai.com)
2. Create an account or log in
3. Generate an API key
4. Copy your API key

### Step 2: Configure Plugin
1. Go to WordPress admin panel
2. Navigate to **AI WP GEN** menu
3. Enter your **OpenAI API Key**
4. Configure settings:
   - **Site Name**: Your website's main title
   - **Categories**: Comma-separated list (e.g., "Tech,Business,Lifestyle")
   - **Authors**: Number of authors to generate
   - **Articles**: Number of articles per category
   - **Custom Prompt**: Optional guidance for AI (e.g., "Generate tech news articles")
   - **CSS Style Prompt**: Styling preferences (e.g., "Modern dark theme with purple gradients")
   - **Language**: Select from 10+ supported languages

### Step 3: Start Generating
1. Go to **Generation** tab
2. Use buttons to generate:
   - Articles & Authors
   - FeaturedImages
   - Homepage
   - Policy Pages
   - CSS Stylesheet

## 🚀 Usage

### Content Generation Workflow
```
Settings → Configure → Generation Tab → Choose Features → Generate
```

### Example Workflow
1. Set Site Name: "Tech News Daily"
2. Add Categories: "AI, Blockchain, Cybersecurity"
3. Custom Prompt: "Write in-depth technical articles"
4. Select Language: Ukrainian
5. Click "Generate Articles"
6. Click "Generate Images"
7. Click "Generate Homepage"
8. Click "Generate CSS"

## 📊 Plugin Architecture

```
ai-news-generator/
├── ai-news-generator.php         # Main plugin file
├── includes/
│   ├── admin-page.php            # Admin panel & UI
│   ├── generator.php             # Core generation functions
│   ├── openai-helper.php         # OpenAI API integration
│   ├── image-generator.php       # Image generation & processing
│   └── ajax-handlers.php         # AJAX endpoints
├── README.md                      # Documentation
└── [Other files...]
```

## 🌐 Supported Languages

- 🇬🇧 English
- 🇵🇱 Polish
- 🇩🇪 German
- 🇭🇺 Hungarian
- 🇺🇦 Ukrainian
- 🇹🇷 Turkish
- 🇮🇹 Italian
- 🇨🇿 Czech
- 🇫🇷 French
- 🇳🇱 Dutch

## 💾 Database

All plugin data is stored in WordPress options table:
- `aicg_api_key` - OpenAI API key
- `aicg_site_name` - Site branding name
- `ang_site_topic` - Custom content prompt
- `ang_style_prompt` - CSS styling prompt
- `ang_language` - Selected language
- `aicg_css_file_url` - Generated CSS file URL

## 🔐 Security

- **API Key**: Stored securely in WordPress options
- **AJAX Endpoints**: Protected with `check_ajax_referer()` and capability checks
- **Input Sanitization**: All user inputs sanitized with `sanitize_text_field()`
- **Output Escaping**: All outputs escaped with `esc_html()`, `esc_url()`, `wp_kses_post()`

## 📝 API Integration

### OpenAI Models Used
- **Text Generation**: GPT-4o-mini (cost-effective, high-quality)
- **Image Generation**: DALL-E 3 (professional 4K images)

### Rate Limits
- Recommended: 1-2 generations per hour
- Each generation uses OpenAI API credits

## 🐛 Troubleshooting

### Images not generating?
- Check OpenAI API key is valid
- Verify account has image generation credits
- Check server error logs in `/wp-content/debug.log`

### Policies generating empty content?
- Ensure API key has sufficient credits
- Wait 30 seconds between API calls
- Check WordPress `php_uname()` permissions

### Language not applying?
- Verify language setting in plugin settings
- Clear WordPress cache if using caching plugin
- Regenerate content after changing language

## 📚 Documentation

For detailed documentation, see:
- [README.md](README.md) - Feature list
- [CONTRIBUTING.md](CONTRIBUTING.md) - Contribution guidelines
- [LICENSE](LICENSE) - License information

## 🤝 Contributing

Contributions are welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

## 📄 License

This project is dual-licensed:
- **Plugin Components**: GPLv2 or later (WordPress requirement)
- **Separate Components**: MIT License

See [LICENSE](LICENSE) for details.

## 👤 Author

**Stanislav Perepelytsia**

## 🙏 Acknowledgments

- OpenAI for GPT-4 and DALL-E APIs
- WordPress community for excellent plugin ecosystem
- Contributors and testers

## 📧 Support

For issues and questions:
- 📌 [GitLab Issues](https://gitlab.com/stas.karm/ai-news-generator/-/issues)
- 💬 [Discussions](https://gitlab.com/stas.karm/ai-news-generator/-/discussions)

---

<div align="center">

Made with ❤️ by Stanislav Perepelytsia

[⬆ Back to top](#ai-wp-gen---wordpress-ai-content--styling-generator)

</div>
