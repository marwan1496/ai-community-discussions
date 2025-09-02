# AI Community Discussions

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange.svg)](https://github.com/marwan1496/ai-community-discussions)

A powerful WordPress plugin that enables AI-powered community discussions with intelligent content summarization using Google's Gemini API. Create engaging discussion posts and automatically generate concise summaries to enhance user experience and content discoverability.

## 🚀 Features

### Core Functionality
- **Custom Post Type**: Dedicated "Community Discussions" post type with full WordPress integration
- **AI-Powered Summaries**: Automatic content summarization using Google's Gemini API
- **Flexible Summary Length**: Configurable word count (10-300 words) for generated summaries
- **Real-time Generation**: AJAX-powered summary generation without page reloads
- **Frontend Display**: Automatic summary display on single discussion pages
- **REST API Support**: Built-in REST API endpoints for modern integrations

### Admin Features
- **Intuitive Meta Box**: Easy-to-use AI Summary interface in post editor
- **Settings Page**: Comprehensive configuration options for API and summary settings
- **Security**: Nonce verification and capability checks for secure operations
- **Responsive Design**: Mobile-friendly admin interface

### Technical Features
- **WordPress Standards**: Follows WordPress coding standards and best practices
- **Internationalization**: Full translation support with text domain
- **Error Handling**: Robust error handling and user feedback
- **Performance Optimized**: Efficient database queries and minimal resource usage

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Google Gemini API**: API key and endpoint URL (optional but recommended)

## 🛠️ Installation

### Method 1: Manual Installation

1. **Download the Plugin**
   ```bash
   git clone https://github.com/marwan1496/ai-community-discussions.git
   ```

2. **Upload to WordPress**
   - Upload the `ai-community-driven` folder to `/wp-content/plugins/`
   - Or compress the folder and upload via WordPress admin

3. **Activate the Plugin**
   - Go to `Plugins > Installed Plugins` in WordPress admin
   - Find "AI Community Discussions" and click "Activate"

### Method 2: WordPress Admin Upload

1. Download the plugin ZIP file
2. Go to `Plugins > Add New > Upload Plugin`
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin

## ⚙️ Configuration

### Initial Setup

1. **Access Settings**
   - Navigate to `Settings > AI Discussion Summary` in WordPress admin

2. **Configure Summary Settings**
   - Set your preferred summary length (default: 40 words)
   - Choose between 10-300 words for optimal readability

3. **Setup Gemini API (Optional)**
   - Enter your Google Gemini API key
   - Provide the Gemini API endpoint URL
   - Without API configuration, the plugin will use WordPress's built-in text trimming

### API Configuration

#### Getting Gemini API Credentials

1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create a new API key
3. Copy the API key and endpoint URL
4. Enter both in the plugin settings

#### API Endpoint Format
```
https://generativelanguage.googleapis.com/v1beta/models/gemini-pro
```

## 📖 Usage

### Creating Community Discussions

1. **Add New Discussion**
   - Go to `Community Discussions > Add New`
   - Enter your discussion title and content
   - Use the rich text editor for formatting

2. **Generate AI Summary**
   - In the post editor sidebar, find the "AI Summary" meta box
   - Click "Generate Summary" to create an AI-powered summary
   - The summary will automatically populate in the textarea
   - Save your post to store the summary

3. **View on Frontend**
   - Published discussions will show the AI summary at the bottom
   - Summaries are styled with a subtle border and clear typography

### Managing Settings

#### Summary Length Configuration
- Navigate to `Settings > AI Discussion Summary`
- Adjust the "Summary length (words)" field
- Save changes to apply new settings

#### API Settings
- Enter your Gemini API credentials
- Test the connection by generating a summary
- Monitor API usage through Google's dashboard

## 🎨 Customization

### Styling the Summary Display

The plugin adds a CSS class `cdai-summary` to the summary container. Customize the appearance:

```css
.cdai-summary {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 20px;
    margin: 24px 0;
}

.cdai-summary h3 {
    color: #333;
    margin-bottom: 12px;
    font-size: 18px;
}
```

### Hooks and Filters

The plugin provides several WordPress hooks for customization:

```php
// Modify summary before display
add_filter('aicd_summary_content', function($summary, $post_id) {
    return 'Custom prefix: ' . $summary;
}, 10, 2);

// Customize summary generation
add_action('aicd_before_summary_generation', function($post_id, $content) {
    // Custom logic before summary generation
}, 10, 2);
```

### REST API Integration

Access discussions via REST API:

```javascript
// Get all discussions
fetch('/wp-json/wp/v2/ai-community-discussions-api')

// Get specific discussion
fetch('/wp-json/wp/v2/ai-community-discussions-api/123')
```

## 🔧 Development

### File Structure

```
ai-community-driven/
├── ai-community-discussions.php    # Main plugin file
├── assets/
│   └── admin-summary.js           # Admin JavaScript functionality
└── README.md                      # This documentation
```

### Key Classes and Methods

#### `AICommunityDiscussions` Class

**Core Methods:**
- `registerPostType()` - Registers the custom post type
- `addSummaryMetaBox()` - Adds AI summary meta box to editor
- `generateSummary()` - Handles AJAX summary generation
- `addSummaryToContent()` - Displays summary on frontend
- `renderSettingsPage()` - Creates admin settings interface

**Constants:**
- `postType` - Custom post type identifier
- `metaKeySummary` - Meta key for storing summaries
- `optionSettingsKey` - Settings option key
- `nonceAction` - Security nonce action

### Database Schema

The plugin uses WordPress's built-in meta system:

```sql
-- Post meta table stores summaries
wp_postmeta:
- meta_key: '_ai_community_discussions_summary'
- meta_value: Generated summary text

-- Options table stores settings
wp_options:
- option_name: 'ai_community_discussions_settings'
- option_value: Serialized settings array
```

### Security Features

- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Capability Checks**: User permission validation
- **Data Sanitization**: Input sanitization and output escaping
- **SQL Injection Prevention**: Uses WordPress's prepared statements

## 🐛 Troubleshooting

### Common Issues

#### Summary Not Generating
1. **Check API Credentials**
   - Verify Gemini API key is correct
   - Ensure API endpoint URL is properly formatted
   - Check API quota and billing status

2. **JavaScript Errors**
   - Check browser console for errors
   - Ensure jQuery is loaded
   - Verify AJAX URL is accessible

#### Summary Not Displaying
1. **Check Post Type**
   - Ensure you're viewing a "Community Discussion" post
   - Verify the post has a generated summary
   - Check if summary meta exists in database

2. **Theme Compatibility**
   - Test with default WordPress theme
   - Check for theme conflicts
   - Verify `the_content` filter isn't disabled

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Log Files

Check WordPress debug log for plugin-specific errors:
```
/wp-content/debug.log
```

## 📊 Performance Considerations

### Optimization Tips

1. **API Usage**
   - Monitor Gemini API usage to avoid rate limits
   - Consider caching summaries for frequently accessed posts
   - Implement summary regeneration only when content changes

2. **Database Optimization**
   - Regular database cleanup of unused meta data
   - Consider indexing for large numbers of discussions

3. **Frontend Performance**
   - Summaries are generated server-side to reduce client load
   - Minimal JavaScript footprint for admin functionality

## 🔄 Updates and Maintenance

### Version History

- **v1.0.0** - Initial release with core functionality
  - Custom post type implementation
  - AI summary generation
  - Admin settings interface
  - Frontend display integration

### Future Roadmap

- [ ] Multiple AI provider support (OpenAI, Claude, etc.)
- [ ] Bulk summary generation
- [ ] Summary templates and customization
- [ ] Analytics and usage statistics
- [ ] Multi-language support enhancements
- [ ] Gutenberg block integration

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

1. **Fork the Repository**
2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Follow Coding Standards**
   - Use WordPress coding standards
   - Include proper documentation
   - Add unit tests where applicable
4. **Submit a Pull Request**

### Development Setup

1. Clone the repository
2. Set up a local WordPress development environment
3. Install the plugin in development mode
4. Configure your preferred IDE with WordPress standards

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**Marwan Shokry**
- GitHub: [@marwan1496](https://github.com/marwan1496)
- Email: marwanshokry14@gmail.com

## 🙏 Acknowledgments

- WordPress community for the excellent platform
- Google for providing the Gemini API
- Contributors and testers who helped improve this plugin

## 📞 Support

### Getting Help

1. **Documentation**: Check this README for common solutions
2. **Issues**: Report bugs via GitHub Issues
3. **Email**: Contact the author for direct support

### Reporting Issues

When reporting issues, please include:
- WordPress version
- PHP version
- Plugin version
- Error messages (if any)
- Steps to reproduce the problem

---

**Made with ❤️ for the WordPress community**

*Transform your community discussions with the power of AI-driven content summarization.*
