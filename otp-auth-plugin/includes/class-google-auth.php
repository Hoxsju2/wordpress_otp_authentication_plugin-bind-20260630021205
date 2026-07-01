<?php
if (!defined('ABSPATH')) {
    exit;
}

class OTP_Auth_Google_Auth {
    
    public function __construct() {
        // AJAX endpoints to get Google Login URL
        add_action('wp_ajax_get_google_auth_url', array($this, 'ajax_get_auth_url'));
        add_action('wp_ajax_nopriv_get_google_auth_url', array($this, 'ajax_get_auth_url'));
        
        // AJAX endpoint for admin testing
        add_action('wp_ajax_test_google_auth_config', array($this, 'ajax_test_config'));
        
        // Callback handler endpoint (acting as the Authorized Redirect URI)
        add_action('wp_ajax_otp_google_callback', array($this, 'handle_callback'));
        add_action('wp_ajax_nopriv_otp_google_callback', array($this, 'handle_callback'));
    }

    /**
     * Get Google Configuration safely.
     */
    private function get_config() {
        $settings = get_option('otp_auth_settings', array());
        return array(
            'enabled' => !empty($settings['google_login_enabled']),
            'client_id' => !empty($settings['google_client_id']) ? sanitize_text_field($settings['google_client_id']) : '',
            'client_secret' => !empty($settings['google_client_secret']) ? sanitize_text_field($settings['google_client_secret']) : '',
            'redirect_uri' => admin_url('admin-ajax.php?action=otp_google_callback')
        );
    }

    /**
     * Generate the OAuth 2.0 URL
     */
    private function generate_login_url($state = 'frontend') {
        $config = $this->get_config();
        
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            return false;
        }

        $params = array(
            'client_id'     => $config['client_id'],
            'redirect_uri'  => $config['redirect_uri'],
            'response_type' => 'code',
            'scope'         => 'email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account'
        );

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Return Google URL for frontend buttons
     */
    public function ajax_get_auth_url() {
        check_ajax_referer('otp_auth_nonce', 'nonce');
        
        $url = $this->generate_login_url('frontend');
        if ($url) {
            wp_send_json_success(array('url' => $url));
        } else {
            wp_send_json_error(array('message' => __('Google Sign-In is not configured properly.', 'otp-auth-plugin')));
        }
    }

    /**
     * Return Google URL for admin testing tool
     */
    public function ajax_test_config() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized access.'));
        }
        
        $url = $this->generate_login_url('testing');
        if ($url) {
            wp_send_json_success(array('url' => $url));
        } else {
            wp_send_json_error(array('message' => 'Missing Client ID or Secret.'));
        }
    }

    /**
     * The Authorized Redirect URI Handler.
     * Google sends the user here after they approve the login.
     */
    public function handle_callback() {
        $config = $this->get_config();
        
        // Handle explicit Google cancellation/errors
        if (isset($_GET['error'])) {
            $this->output_or_redirect($_GET['state'], false, __('Google authentication was cancelled or failed.', 'otp-auth-plugin') . ' (' . sanitize_text_field($_GET['error']) . ')');
        }
        
        if (!isset($_GET['code']) || !isset($_GET['state'])) {
            $this->output_or_redirect('frontend', false, __('Invalid request from Google.', 'otp-auth-plugin'));
        }

        $code = sanitize_text_field($_GET['code']);
        $state = sanitize_text_field($_GET['state']); // 'testing' or 'frontend'

        // 1. Exchange Auth Code for Access Token
        $token_response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'client_id'     => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri'  => $config['redirect_uri'],
                'grant_type'    => 'authorization_code',
                'code'          => $code
            )
        ));

        if (is_wp_error($token_response) || wp_remote_retrieve_response_code($token_response) !== 200) {
            $this->output_or_redirect($state, false, __('Failed to retrieve access token from Google.', 'otp-auth-plugin'));
        }

        $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
        if (empty($token_data['access_token'])) {
            $this->output_or_redirect($state, false, __('Invalid token response from Google.', 'otp-auth-plugin'));
        }

        // 2. Fetch User Profile
        $profile_response = wp_remote_get('https://www.googleapis.com/oauth2/v2/userinfo', array(
            'headers' => array('Authorization' => 'Bearer ' . $token_data['access_token'])
        ));

        if (is_wp_error($profile_response) || wp_remote_retrieve_response_code($profile_response) !== 200) {
            $this->output_or_redirect($state, false, __('Failed to fetch user profile from Google.', 'otp-auth-plugin'));
        }

        $profile_data = json_decode(wp_remote_retrieve_body($profile_response), true);
        
        if (empty($profile_data['email'])) {
            $this->output_or_redirect($state, false, __('Google did not provide an email address.', 'otp-auth-plugin'));
        }

        $email = sanitize_email($profile_data['email']);

        // 3. Handle Testing State
        if ($state === 'testing') {
            $this->output_or_redirect($state, true, 'Google Integration is Working Perfectly!', $email);
        }

        // 4. Handle Frontend Login/Registration
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // Register New User
            $username = sanitize_user(current(explode('@', $email)), true);
            $counter = 1;
            $orig_username = $username;
            while (username_exists($username)) {
                $username = $orig_username . $counter;
                $counter++;
            }
            
            $settings = get_option('otp_auth_settings', array());
            $role = isset($settings['default_user_role']) ? $settings['default_user_role'] : 'subscriber';
            if (!get_role($role)) $role = 'subscriber';

            $user_id = wp_insert_user(array(
                'user_login' => $username,
                'user_email' => $email,
                'user_pass'  => wp_generate_password(24, true, true),
                'role'       => $role
            ));

            if (is_wp_error($user_id)) {
                $this->output_or_redirect($state, false, __('Failed to create account.', 'otp-auth-plugin'));
            }
            $user = get_user_by('id', $user_id);
        }

        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        do_action('wp_login', $user->user_login, $user);

        // Redirect safely
        $redirect_url = $this->get_redirect_url();
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Display a clean testing diagnostic screen or redirect with errors
     */
    private function output_or_redirect($state, $success, $message, $email = '') {
        if ($state === 'testing') {
            $color = $success ? '#10b981' : '#ef4444';
            $icon = $success ? '✅' : '❌';
            
            $html = "<div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif; max-width:600px; margin:50px auto; padding:30px; border-radius:12px; background:#fff; box-shadow:0 10px 25px rgba(0,0,0,0.1); text-align:center;'>";
            $html .= "<h1 style='color: {$color};'>{$icon} " . ($success ? 'Success!' : 'Error') . "</h1>";
            $html .= "<p style='font-size:16px; color:#374151;'>" . esc_html($message) . "</p>";
            
            if ($success && $email) {
                $html .= "<div style='background:#f3f4f6; padding:15px; border-radius:8px; margin:20px 0;'>";
                $html .= "<p style='margin:0; font-weight:600; color:#111827;'>Retrieved Email Account:</p>";
                $html .= "<p style='margin:5px 0 0 0; color:#3b82f6; font-family:monospace; font-size:16px;'>" . esc_html($email) . "</p>";
                $html .= "</div>";
                $html .= "<p style='color:#6b7280; font-size:14px;'>The OAuth handshake is fully functional. You can safely enable Google Login for your users.</p>";
            }
            
            $html .= "<button onclick='window.close()' style='margin-top:20px; background:#111827; color:#fff; border:none; padding:12px 24px; border-radius:6px; cursor:pointer; font-weight:600;'>Close Window</button>";
            $html .= "</div>";
            
            wp_die($html, 'Google Auth Test', array('response' => $success ? 200 : 400));
        } else {
            // If it fails on the frontend, redirect to home with error parameter
            wp_safe_redirect(add_query_arg('otp_error', urlencode($message), home_url()));
            exit;
        }
    }

    /**
     * Resolve Redirect URL safely (Same as OTP login logic)
     */
    private function get_redirect_url() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $settings = get_option('otp_auth_settings', array());
        $redirect_type = isset($settings['redirect_after_login']) ? $settings['redirect_after_login'] : 'previous_page';

        if ($redirect_type === 'homepage') return home_url();
        if ($redirect_type === 'custom') {
            $custom = isset($settings['custom_redirect_url']) ? $settings['custom_redirect_url'] : '';
            return (!empty($custom) && filter_var($custom, FILTER_VALIDATE_URL)) ? $custom : home_url();
        }

        if (isset($_SESSION['otp_auth_redirect'])) {
            $url = $_SESSION['otp_auth_redirect'];
            unset($_SESSION['otp_auth_redirect']);
            return $url;
        }

        return home_url();
    }
}
