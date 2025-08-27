<?php
/**
 * Plugin Name: Spam Comment Cleaner
 * Description: Remove spam comments containing specific URLs like shorturl.fm
 * Version: 1.0.2
 * Author: Ivan Eguiguren
 * Text Domain: spam-comment-cleaner
 * GitHub Plugin URI: ieguiguren/spam-comment-cleaner
 * GitHub Branch: main
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCC_PLUGIN_FILE', __FILE__);
define('SCC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCC_PLUGIN_VERSION', '1.0.1');
define('SCC_GITHUB_REPO', 'donosor00/spam-comment-cleaner');

class SpamCommentCleaner {
    
    private $default_patterns = [
        'shorturl.fm',
        'bit.ly',
        'tinyurl.com'
    ];
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_scc_scan_comments', [$this, 'ajax_scan_comments']);
        add_action('wp_ajax_scc_delete_comments', [$this, 'ajax_delete_comments']);
        
        // Initialize auto-updater
        new SpamCommentCleanerUpdater();
    }
    
    public function add_admin_menu() {
        add_management_page(
            'Spam Comment Cleaner',
            'Spam Comment Cleaner', 
            'manage_options',
            'spam-comment-cleaner',
            [$this, 'admin_page']
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'tools_page_spam-comment-cleaner') {
            return;
        }
        
        wp_enqueue_script('jquery');
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Spam Comment Cleaner <small>v<?php echo SCC_PLUGIN_VERSION; ?></small></h1>
            
            <div class="notice notice-warning">
                <p><strong>Warning:</strong> This tool will permanently delete comments. Make sure you have a backup of your database.</p>
            </div>
            
            <div class="card">
                <h2>URL Patterns to Search</h2>
                <p>Enter URL patterns (one per line) that you want to remove from comments:</p>
                
                <textarea id="spam-patterns" rows="10" cols="50" style="width: 100%; max-width: 500px;"><?php 
                echo esc_textarea(implode("\n", $this->default_patterns)); 
                ?></textarea>
                
                <p>
                    <button id="scan-btn" class="button button-primary">Scan for Spam Comments</button>
                    <button id="delete-btn" class="button button-secondary" disabled>Delete Spam Comments</button>
                </p>
            </div>
            
            <div id="results-container" style="display: none;">
                <div class="card">
                    <h2>Scan Results</h2>
                    <div id="scan-results"></div>
                </div>
            </div>
            
            <div id="progress-container" style="display: none;">
                <div class="card">
                    <h2>Progress</h2>
                    <div id="progress-info"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let spamComments = [];
            
            $('#scan-btn').click(function() {
                const patterns = $('#spam-patterns').val().split('\n').filter(p => p.trim() !== '');
                
                if (patterns.length === 0) {
                    alert('Please enter at least one URL pattern.');
                    return;
                }
                
                $(this).prop('disabled', true).text('Scanning...');
                $('#delete-btn').prop('disabled', true);
                $('#results-container').hide();
                
                $.post(ajaxurl, {
                    action: 'scc_scan_comments',
                    patterns: patterns,
                    nonce: '<?php echo wp_create_nonce('scc_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        spamComments = response.data.comments;
                        displayResults(response.data);
                        $('#delete-btn').prop('disabled', spamComments.length === 0);
                    } else {
                        alert('Error: ' + response.data);
                    }
                    
                    $('#scan-btn').prop('disabled', false).text('Scan for Spam Comments');
                });
            });
            
            $('#delete-btn').click(function() {
                if (!confirm('Are you sure you want to delete ' + spamComments.length + ' spam comments? This action cannot be undone.')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('Deleting...');
                $('#scan-btn').prop('disabled', true);
                $('#progress-container').show();
                
                const commentIds = spamComments.map(c => c.comment_ID);
                
                $.post(ajaxurl, {
                    action: 'scc_delete_comments',
                    comment_ids: commentIds,
                    nonce: '<?php echo wp_create_nonce('scc_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#progress-info').html('<div class="notice notice-success"><p>Successfully deleted ' + response.data.deleted + ' spam comments!</p></div>');
                        spamComments = [];
                        $('#results-container').hide();
                    } else {
                        $('#progress-info').html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                    
                    $('#delete-btn').prop('disabled', true).text('Delete Spam Comments');
                    $('#scan-btn').prop('disabled', false);
                });
            });
            
            function displayResults(data) {
                let html = '<p><strong>Found ' + data.total + ' spam comments</strong></p>';
                
                if (data.total > 0) {
                    html += '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">';
                    data.comments.forEach(function(comment) {
                        html += '<div style="margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-left: 4px solid #dc3232;">';
                        html += '<strong>ID:</strong> ' + comment.comment_ID + ' | ';
                        html += '<strong>Author:</strong> ' + $('<div>').text(comment.comment_author).html() + ' | ';
                        html += '<strong>Date:</strong> ' + comment.comment_date + '<br>';
                        html += '<strong>Content:</strong> ' + $('<div>').text(comment.comment_content.substring(0, 200) + '...').html();
                        html += '</div>';
                    });
                    html += '</div>';
                } else {
                    html += '<p style="color: green;">No spam comments found with the specified patterns.</p>';
                }
                
                $('#scan-results').html(html);
                $('#results-container').show();
            }
        });
        </script>
        
        <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        </style>
        <?php
    }
    
    public function ajax_scan_comments() {
        check_ajax_referer('scc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $patterns = isset($_POST['patterns']) ? $_POST['patterns'] : [];
        
        if (empty($patterns)) {
            wp_send_json_error('No patterns provided');
        }
        
        global $wpdb;
        
        $conditions = [];
        $values = [];
        
        foreach ($patterns as $pattern) {
            $pattern = sanitize_text_field(trim($pattern));
            if (!empty($pattern)) {
                $conditions[] = "comment_content LIKE %s";
                $values[] = '%' . $wpdb->esc_like($pattern) . '%';
            }
        }
        
        if (empty($conditions)) {
            wp_send_json_error('No valid patterns provided');
        }
        
        $where_clause = implode(' OR ', $conditions);
        
        $query = "SELECT comment_ID, comment_author, comment_content, comment_date, comment_approved 
                  FROM {$wpdb->comments} 
                  WHERE ({$where_clause})
                  ORDER BY comment_date DESC 
                  LIMIT 500";
        
        $spam_comments = $wpdb->get_results($wpdb->prepare($query, $values));
        
        wp_send_json_success([
            'total' => count($spam_comments),
            'comments' => $spam_comments
        ]);
    }
    
    public function ajax_delete_comments() {
        check_ajax_referer('scc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $comment_ids = isset($_POST['comment_ids']) ? $_POST['comment_ids'] : [];
        
        if (empty($comment_ids)) {
            wp_send_json_error('No comment IDs provided');
        }
        
        $deleted_count = 0;
        
        foreach ($comment_ids as $comment_id) {
            $comment_id = intval($comment_id);
            if ($comment_id > 0 && wp_delete_comment($comment_id, true)) {
                $deleted_count++;
            }
        }
        
        // Clean orphaned comment meta
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE comment_id NOT IN (SELECT comment_id FROM {$wpdb->comments})");
        
        wp_send_json_success([
            'deleted' => $deleted_count,
            'total' => count($comment_ids)
        ]);
    }
}

/**
 * Auto-updater class for GitHub releases
 */
class SpamCommentCleanerUpdater {
    
    private $plugin_file;
    private $plugin_basename;
    private $version;
    private $github_repo;
    
    public function __construct() {
        $this->plugin_file = SCC_PLUGIN_FILE;
        $this->plugin_basename = SCC_PLUGIN_BASENAME;
        $this->version = SCC_PLUGIN_VERSION;
        $this->github_repo = SCC_GITHUB_REPO;
        
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        add_filter('upgrader_pre_download', [$this, 'download_package'], 10, 3);
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $transient->response[$this->plugin_basename] = (object) [
                'slug' => dirname($this->plugin_basename),
                'plugin' => $this->plugin_basename,
                'new_version' => $remote_version,
                'url' => "https://github.com/{$this->github_repo}",
                'package' => $this->get_download_url($remote_version),
                'tested' => get_bloginfo('version')
            ];
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information for update popup
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== dirname($this->plugin_basename)) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        $release_info = $this->get_release_info($remote_version);
        
        return (object) [
            'name' => 'Spam Comment Cleaner',
            'slug' => dirname($this->plugin_basename),
            'version' => $remote_version,
            'author' => 'Ivan Eguiguren',
            'homepage' => "https://github.com/{$this->github_repo}",
            'short_description' => 'Remove spam comments containing specific URLs',
            'sections' => [
                'description' => 'A WordPress plugin to efficiently identify and remove spam comments containing specific URL patterns.',
                'changelog' => $release_info['changelog'] ?? 'Bug fixes and improvements.'
            ],
            'download_link' => $this->get_download_url($remote_version),
            'requires' => '4.0',
            'tested' => get_bloginfo('version'),
            'requires_php' => '5.6'
        ];
    }
    
    /**
     * Download package from GitHub
     */
    public function download_package($reply, $package, $upgrader) {
        if (strpos($package, 'github.com') !== false && strpos($package, $this->github_repo) !== false) {
            $package = $this->get_download_url($this->get_remote_version());
        }
        
        return $reply;
    }
    
    /**
     * Get remote version from GitHub API
     */
    private function get_remote_version() {
        $request = wp_remote_get("https://api.github.com/repos/{$this->github_repo}/releases/latest", [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            ]
        ]);
        
        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) !== 200) {
            return $this->version;
        }
        
        $body = json_decode(wp_remote_retrieve_body($request), true);
        
        if (isset($body['tag_name'])) {
            return ltrim($body['tag_name'], 'v');
        }
        
        return $this->version;
    }
    
    /**
     * Get release information
     */
    private function get_release_info($version) {
        $request = wp_remote_get("https://api.github.com/repos/{$this->github_repo}/releases/tags/v{$version}", [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version')
            ]
        ]);
        
        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) !== 200) {
            return [];
        }
        
        $body = json_decode(wp_remote_retrieve_body($request), true);
        
        return [
            'changelog' => $body['body'] ?? 'Bug fixes and improvements.'
        ];
    }
    
    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        return "https://github.com/{$this->github_repo}/archive/v{$version}.zip";
    }
}

// Initialize the plugin
new SpamCommentCleaner();
