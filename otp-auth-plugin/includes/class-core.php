<?php
if (!defined('ABSPATH')) {
    exit;
}

class OTP_Auth_Core {
    public function __construct() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Add custom styles for login/logout button
        add_action('wp_head', array($this, 'add_custom_button_styles'));
        
        // V1.2.5 GLOBAL INJECTION: Silently inject the overlay HTML into the footer on every page so header buttons can trigger it
        add_action('wp_footer', array($this, 'inject_global_auth_overlay'));
        
        // Intercept login attempts (WP 7.0 Safe Redirects)
        add_action('login_init', array($this, 'intercept_wp_login'));
        add_action('wp_login_form', array($this, 'redirect_login_to_otp'));
        add_action('register_form', array($this, 'redirect_register_to_otp'));
        add_action('pre_comment_on_post', array($this, 'intercept_comment_submission'));
        
        // URL Replacements
        add_filter('wp_nav_menu_items', array($this, 'replace_login_links'), 10, 2);
        add_filter('login_url', array($this, 'replace_login_url'), 10, 3);
        add_filter('register_url', array($this, 'replace_register_url'));
        
        // V1.2.8: Smart Logout Redirect Logic
        add_filter('logout_redirect', array($this, 'smart_logout_redirect'), 10, 3);
    }
    
    public function enqueue_frontend_scripts() {
        // DYNAMIC CACHE BUSTING: Forces browsers to download the latest files
        $css_file = OTP_AUTH_PLUGIN_PATH . 'assets/css/frontend.css';
        $js_file = OTP_AUTH_PLUGIN_PATH . 'assets/js/frontend.js';
        
        $css_version = file_exists($css_file) ? filemtime($css_file) : OTP_AUTH_VERSION;
        $js_version = file_exists($js_file) ? filemtime($js_file) : OTP_AUTH_VERSION;

        wp_enqueue_style('otp-auth-style', OTP_AUTH_PLUGIN_URL . 'assets/css/frontend.css', array(), $css_version);
        wp_enqueue_script('otp-auth-script', OTP_AUTH_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), $js_version, true);
        
        wp_localize_script('otp-auth-script', 'otpAuth', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('otp_auth_nonce'),
            'messages' => array(
                'sending' => esc_html__('Sending OTP...', 'otp-auth-plugin'),
                'verifying' => esc_html__('Verifying OTP...', 'otp-auth-plugin'),
                'error' => esc_html__('[ERR_FALLBACK] An error occurred. Please try again.', 'otp-auth-plugin')
            )
        ));
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_otp-auth-settings') return;
        wp_enqueue_style('otp-auth-admin-style', OTP_AUTH_PLUGIN_URL . 'assets/css/admin.css', array(), OTP_AUTH_VERSION);
        wp_enqueue_script('otp-auth-admin-script', OTP_AUTH_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), OTP_AUTH_VERSION, true);
    }
    
    public function add_custom_button_styles() {
        $settings = get_option('otp_auth_settings', array());
        
        $button_display = isset($settings['button_display']) ? sanitize_text_field($settings['button_display']) : 'both';
        $icon_color = isset($settings['icon_color']) ? sanitize_hex_color($settings['icon_color']) : '#ffffff';
        $button_bg_color = isset($settings['button_bg_color']) ? sanitize_hex_color($settings['button_bg_color']) : '#3b82f6';
        $button_border_color = isset($settings['button_border_color']) ? sanitize_hex_color($settings['button_border_color']) : '#3b82f6';
        $icon_size = isset($settings['icon_size']) ? absint($settings['icon_size']) : 16;
        
        $button_bg_hover = $this->darken_color($button_bg_color, 0.1);
        $button_border_hover = $this->darken_color($button_border_color, 0.1);
        
        echo '<style id="otp-auth-custom-button-styles">';
        echo '.otp-login-btn, .otp-logout-btn { background: ' . esc_attr($button_bg_color) . ' !important; color: ' . esc_attr($icon_color) . ' !important; border: 2px solid ' . esc_attr($button_border_color) . ' !important; }';
        echo '.otp-login-btn:hover, .otp-logout-btn:hover { background: ' . esc_attr($button_bg_hover) . ' !important; border-color: ' . esc_attr($button_border_hover) . ' !important; color: ' . esc_attr($icon_color) . ' !important; }';
        echo '.otp-login-btn .otp-btn-icon, .otp-logout-btn .otp-btn-icon { width: ' . esc_attr($icon_size) . 'px !important; height: ' . esc_attr($icon_size) . 'px !important; }';
        
        if ($button_display === 'icon_only') {
            echo '.otp-login-btn .button-text, .otp-logout-btn .button-text { display: none !important; }';
        } elseif ($button_display === 'text_only') {
            echo '.otp-login-btn .otp-btn-icon, .otp-logout-btn .otp-btn-icon { display: none !important; }';
        }
        echo '</style>';
    }
    
    private function darken_color($color, $factor) {
        $color = ltrim($color, '#');
        if (strlen($color) !== 6) return '#000000';
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        $r = max(0, min(255, $r * (1 - $factor)));
        $g = max(0, min(255, $g * (1 - $factor)));
        $b = max(0, min(255, $b * (1 - $factor)));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    public function inject_global_auth_overlay() {
        // Only inject if the user is a guest and not in the admin dashboard
        if (!is_user_logged_in() && !is_admin()) {
            $frontend = new OTP_Auth_Frontend();
            // Passing false so it starts hidden. Clicking the login shortcode button will trigger it via JS.
            echo $frontend->get_auth_page_content('signin', false);
        }
    }
    
    public function intercept_wp_login() {
        if ($this->is_admin_area() || (isset($_REQUEST['action']) && $_REQUEST['action'] === 'logout')) return;
        $auth_page_url = $this->get_otp_auth_page_url();
        if ($auth_page_url) {
            wp_safe_redirect($auth_page_url);
            exit;
        }
    }
    
    public function redirect_login_to_otp() {
        if ($this->is_admin_area()) return;
        $auth_page_url = $this->get_otp_auth_page_url();
        if ($auth_page_url) {
            wp_safe_redirect($auth_page_url);
            exit;
        }
    }
    
    public function redirect_register_to_otp() {
        $auth_page_url = $this->get_otp_auth_page_url();
        if ($auth_page_url) {
            wp_safe_redirect(add_query_arg('action', 'signup', $auth_page_url));
            exit;
        }
    }
    
    public function intercept_comment_submission($post_id) {
        if (!is_user_logged_in()) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['pending_comment'] = array('post_id' => $post_id, 'comment_data' => $_POST);
            $_SESSION['otp_auth_redirect'] = get_permalink($post_id) . '#comments';
            $auth_page_url = $this->get_otp_auth_page_url();
            if ($auth_page_url) {
                wp_safe_redirect($auth_page_url);
                exit;
            }
        }
    }
    
    public function replace_login_links($items, $args) {
        if (is_user_logged_in()) return $items;
        $auth_page_url = $this->get_otp_auth_page_url();
        if (!$auth_page_url) return $items;
        
        $login_urls = array(wp_login_url(), site_url('/wp-login.php'), home_url('/wp-login.php'), wp_registration_url());
        foreach ($login_urls as $login_url) {
            $items = str_replace($login_url, $auth_page_url, $items);
        }
        return $items;
    }
    
    public function replace_login_url($login_url, $redirect, $force_reauth) {
        if ($this->is_admin_area()) return $login_url;
        $auth_page_url = $this->get_otp_auth_page_url();
        return $auth_page_url ? $auth_page_url : $login_url;
    }
    
    public function replace_register_url($register_url) {
        $auth_page_url = $this->get_otp_auth_page_url();
        return $auth_page_url ? add_query_arg('action', 'signup', $auth_page_url) : $register_url;
    }
    
    private function is_admin_area() {
        if (is_admin() || current_user_can('manage_options')) return true;
        if (isset($_SERVER['REQUEST_URI']) && strpos(sanitize_text_field($_SERVER['REQUEST_URI']), '/wp-admin') !== false) return true;
        return false;
    }
    
    private function get_otp_auth_page_url() {
        global $wpdb;
        $page = $wpdb->get_row(
            "SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[otp_auth_page]%' AND post_status = 'publish' AND post_type = 'page' LIMIT 1"
        );
        return $page ? get_permalink($page->ID) : home_url('/otp-auth/');
    }

    /**
     * V1.2.8: Smart Logout Redirect Method
     * Ensure users stay on their current page after logging out,
     * EXCEPT if they are on a sensitive profile/dashboard page (redirect them to home).
     */
    public function smart_logout_redirect($redirect_to, $requested_redirect_to, $user) {
        // If there's no specific redirect requested, try to use the referer
        if (empty($requested_redirect_to)) {
            $referer = wp_get_referer();
            if ($referer) {
                $redirect_to = $referer;
            } else {
                $redirect_to = home_url();
            }
        }
        
        // Parse the target redirect URL
        $parsed_url = wp_parse_url($redirect_to);
        $path = isset($parsed_url['path']) ? strtolower($parsed_url['path']) : '';
        
        // Define paths that should force a redirect to the homepage
        $restricted_paths = array(
            '/wp-admin',
            '/dashboard',
            '/my-account',
            '/profile',
            '/account',
            '/author'
        );
        
        // Check if the redirect target contains any of the restricted paths
        foreach ($restricted_paths as $restricted_path) {
            if (strpos($path, $restricted_path) !== false) {
                return home_url();
            }
        }
        
        // If not restricted, stay on the requested page
        return $redirect_to;
    }
}
