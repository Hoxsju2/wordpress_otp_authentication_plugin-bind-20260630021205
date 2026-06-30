<?php
if (!defined('ABSPATH')) {
    exit;
}

class OTP_Auth_Activator {
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }
    
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    private static function add_rewrite_rules() {
        add_rewrite_rule('^otp-auth/?$', 'index.php?otp_auth_page=1', 'top');
        add_rewrite_tag('%otp_auth_page%', '([^&]+)');
    }
    
    private static function create_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'otp_auth_codes';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            email varchar(100) NOT NULL,
            otp_code varchar(6) NOT NULL,
            action_type varchar(20) NOT NULL,
            expires_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            is_used tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY email (email),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private static function set_default_options() {
        $default_settings = array(
            'otp_expiry' => 15,
            'email_from_name' => get_bloginfo('name'),
            'email_from_email' => get_option('admin_email'),
            'captcha_enabled' => false,
            'captcha_type' => 'recaptcha',
            'recaptcha_site_key' => '',
            'recaptcha_secret' => '',
            'hcaptcha_site_key' => '',
            'hcaptcha_secret' => '',
            'force_otp_auth' => true,
            'protected_post_types' => array(),
            'default_user_role' => 'subscriber',
            'redirect_after_login' => 'previous_page',
            'custom_redirect_url' => '',
            'button_display' => 'both',
            'icon_color' => '#ffffff',
            'button_bg_color' => '#3b82f6',
            'button_border_color' => '#3b82f6',
            'login_icon' => 'login_default',
            'logout_icon' => 'logout_default',
            'icon_size' => 16
        );
        add_option('otp_auth_settings', $default_settings);
    }
}
