<?php
if (!defined('ABSPATH')) {
    exit;
}

class OTP_Auth_GitHub_Updater {
    
    private $slug;
    private $plugin_data;
    private $username;
    private $repository;
    private $token;
    private $github_api_url;

    public function __construct() {
        // Plugin base slug (e.g., otp-auth-plugin/otp-auth-plugin.php)
        $this->slug = plugin_basename(dirname(__DIR__) . '/otp-auth-plugin.php');
        
        // Hooks into the WordPress Plugin Update mechanism
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
        add_filter('plugins_api', array($this, 'plugin_info_popup'), 10, 3);
        add_filter('upgrader_source_selection', array($this, 'rename_github_extracted_folder'), 10, 4);
        add_action('upgrader_process_complete', array($this, 'purge_update_transient'), 10, 2);
    }

    private function load_repository_config() {
        $settings = get_option('otp_auth_settings', array());
        
        $github_repo = isset($settings['github_repo']) ? sanitize_text_field($settings['github_repo']) : '';
        $this->token = isset($settings['github_token']) ? sanitize_text_field($settings['github_token']) : '';
        
        if (empty($github_repo)) {
            return false;
        }

        $parts = explode('/', trim($github_repo, '/'));
        if (count($parts) !== 2) {
            return false;
        }

        $this->username = $parts[0];
        $this->repository = $parts[1];
        $this->github_api_url = "https://api.github.com/repos/{$this->username}/{$this->repository}/releases/latest";
        
        return true;
    }

    private function get_github_release_info() {
        // Cache the GitHub API response for 6 hours to avoid rate limiting
        $cache_key = 'otp_auth_github_release_info';
        $release_info = get_transient($cache_key);

        if ($release_info !== false) {
            return $release_info;
        }

        if (!$this->load_repository_config()) {
            return false;
        }

        $args = array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress-OTP-Auth-Updater'
            ),
            'timeout' => 15
        );

        if (!empty($this->token)) {
            $args['headers']['Authorization'] = 'token ' . $this->token;
        }

        $response = wp_remote_get($this->github_api_url, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $release_info = json_decode(wp_remote_retrieve_body($response));
        
        // Cache for 6 hours
        set_transient($cache_key, $release_info, 6 * HOUR_IN_SECONDS);

        return $release_info;
    }

    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release_info = $this->get_github_release_info();
        if (!$release_info) {
            return $transient;
        }

        // Clean version tag (e.g., 'v1.3.1' becomes '1.3.1')
        $github_version = ltrim($release_info->tag_name, 'v');
        $current_version = OTP_AUTH_VERSION;

        if (version_compare($github_version, $current_version, '>')) {
            $plugin_obj = new stdClass();
            $plugin_obj->id = $this->slug;
            $plugin_obj->slug = dirname($this->slug);
            $plugin_obj->plugin = $this->slug;
            $plugin_obj->new_version = $github_version;
            $plugin_obj->url = $release_info->html_url;
            $plugin_obj->package = $release_info->zipball_url;
            
            if (!empty($this->token)) {
                $plugin_obj->package = add_query_arg('access_token', $this->token, $plugin_obj->package);
            }

            $transient->response[$this->slug] = $plugin_obj;
        }

        return $transient;
    }

    public function plugin_info_popup($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        if (!isset($args->slug) || $args->slug !== dirname($this->slug)) {
            return $result;
        }

        $release_info = $this->get_github_release_info();
        if (!$release_info) {
            return $result;
        }

        $plugin_info = new stdClass();
        $plugin_info->name = 'OTP Authentication Plugin';
        $plugin_info->slug = dirname($this->slug);
        $plugin_info->version = ltrim($release_info->tag_name, 'v');
        $plugin_info->author = '<a href="https://github.com/' . esc_attr($this->username) . '">GitHub Auto Updater</a>';
        $plugin_info->homepage = $release_info->html_url;
        $plugin_info->requires = '5.0';
        $plugin_info->tested = '6.4';
        $plugin_info->downloaded = 0;
        $plugin_info->last_updated = $release_info->published_at;
        $plugin_info->sections = array(
            'description' => 'A comprehensive OTP-based authentication system with GitHub Auto-Updater.',
            'changelog' => wpautop(esc_html($release_info->body))
        );
        $plugin_info->download_link = $release_info->zipball_url;

        return $plugin_info;
    }

    // GitHub extracts zip files as 'Username-Repo-CommitHash'. We need to rename it to 'otp-auth-plugin'.
    public function rename_github_extracted_folder($source, $remote_source, $upgrader, $hook_extra = null) {
        global $wp_filesystem;

        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->slug) {
            $expected_destination = trailingslashit($remote_source) . dirname($this->slug);

            if ($source !== $expected_destination) {
                $wp_filesystem->move($source, $expected_destination);
                return $expected_destination;
            }
        }

        return $source;
    }

    public function purge_update_transient($upgrader_object, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            delete_transient('otp_auth_github_release_info');
        }
    }
}
