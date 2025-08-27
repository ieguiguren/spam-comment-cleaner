# WordPress Spam Comment Cleaner

A WordPress plugin to efficiently identify and remove spam comments containing specific URL patterns.

## Features

- **Safe Preview Mode**: Scan and preview spam comments before deletion
- **Customizable Patterns**: Define your own URL patterns to detect spam
- **Batch Processing**: Handle up to 500 comments per operation to prevent timeouts
- **Admin Interface**: Clean, user-friendly dashboard in WordPress admin
- **Database Integrity**: Uses WordPress native functions to maintain data consistency
- **Orphaned Meta Cleanup**: Automatically removes orphaned comment metadata
- **Permission Control**: Restricted to administrators only

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release ZIP file
2. Go to your WordPress admin panel
3. Navigate to `Plugins > Add New`
4. Click `Upload Plugin`
5. Select the ZIP file and install
6. Activate the plugin

### Method 2: Manual Installation

1. Download and extract the plugin files
2. Upload the `spam-comment-cleaner` folder to `/wp-content/plugins/`
3. Activate the plugin through WordPress admin

## Usage

1. Navigate to `Tools > Spam Comment Cleaner` in your WordPress admin
2. Configure URL patterns (one per line) in the textarea
3. Click `Scan for Spam Comments` to preview results
4. Review the found comments carefully
5. Click `Delete Spam Comments` to permanently remove them

## Default Patterns

The plugin comes pre-configured with common spam URL patterns:
- `shorturl.fm`
- `bit.ly`
- `tinyurl.com`

## Configuration

### Adding Custom Patterns

You can add any URL pattern to detect spam comments:

```
example-spam-site.com
malicious-domain.net
suspicious-url.org
```

### Pattern Matching

The plugin uses SQL `LIKE` matching with wildcards, so:
- `shorturl.fm` will match any comment containing this string
- Patterns are case-insensitive
- No regex required - simple string matching

## Security Features

- **CSRF Protection**: All AJAX requests use WordPress nonces
- **Permission Checks**: Only users with `manage_options` capability can access
- **Data Sanitization**: All input is properly sanitized
- **Preview Mode**: Always scan before delete to prevent accidents

## Technical Details

### Requirements

- WordPress 4.0 or higher
- PHP 5.6 or higher
- Administrator privileges

### Database Operations

The plugin:
- Uses `wp_delete_comment()` for safe comment removal
- Maintains WordPress hooks and filters
- Cleans orphaned entries from `wp_commentmeta`
- Processes comments in batches to prevent memory issues

### AJAX Endpoints

- `scc_scan_comments`: Scans for spam comments matching patterns
- `scc_delete_comments`: Deletes specified comment IDs

## Safety Considerations

⚠️ **Important**: Always backup your database before running bulk deletion operations.

The plugin includes several safety measures:
- Preview mode shows exactly what will be deleted
- Confirmation dialog before deletion
- Uses WordPress native deletion functions
- Limits processing to 500 comments per operation

## Screenshots

### Main Interface
The plugin provides a clean admin interface under Tools menu.

### Scan Results
Preview found spam comments with details before deletion.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

1. Clone this repository
2. Set up a local WordPress development environment
3. Install the plugin in development mode
4. Make your changes and test thoroughly

### Coding Standards

- Follow WordPress Coding Standards
- Use proper sanitization and validation
- Include inline documentation
- Test with various WordPress versions

## Changelog

### 1.0.0 (2025-08-27)
- Initial release
- Basic spam comment detection and removal
- Admin interface with preview mode
- Customizable URL patterns
- Batch processing support

## License

This project is licensed under the GPL v2 or later.

## Support

For support, please:
1. Check existing GitHub issues
2. Create a new issue with detailed description
3. Include WordPress version and error messages

## Frequently Asked Questions

### Q: Will this affect legitimate comments?
**A**: The plugin only targets comments containing the specific URL patterns you define. Always use preview mode first.

### Q: Can I recover deleted comments?
**A**: No, deletion is permanent. Always backup your database before using this plugin.

### Q: Why is there a 500 comment limit?
**A**: To prevent PHP timeouts and memory issues on shared hosting environments.

### Q: Can I run this multiple times?
**A**: Yes, you can run scans and deletions as many times as needed.

### Q: Does it work with comment moderation plugins?
**A**: Yes, it works with all standard WordPress comment systems and most plugins.

## Credits

Developed for WordPress administrators dealing with spam comment issues.

---

**⚠️ Disclaimer**: This plugin permanently deletes comments. Use at your own risk and always maintain database backups.
