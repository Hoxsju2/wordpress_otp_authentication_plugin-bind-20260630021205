<?php
class OTP_Auth_Shortcodes {
    
    public function __construct() {
        add_shortcode('otp_auth_page', array($this, 'auth_page_shortcode'));
        add_shortcode('otp_login_button', array($this, 'login_button_shortcode'));
    }
    
    public function auth_page_shortcode($atts) {
        $atts = shortcode_atts(array(
            'default_action' => 'signin'
        ), $atts, 'otp_auth_page');
        
        $frontend = new OTP_Auth_Frontend();
        // Force auto_open = true for the dedicated shortcode page so it opens instantly
        return $frontend->get_auth_page_content($atts['default_action'], true);
    }
    
    public function login_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text_login' => 'Login',
            'text_logout' => 'Logout',
            'class' => ''
        ), $atts, 'otp_login_button');
        
        $settings = get_option('otp_auth_settings', array());
        $button_display = isset($settings['button_display']) ? $settings['button_display'] : 'both';
        $login_icon = isset($settings['login_icon']) ? $settings['login_icon'] : 'login_default';
        $logout_icon = isset($settings['logout_icon']) ? $settings['logout_icon'] : 'logout_default';
        
        if (is_user_logged_in()) {
            // Show logout button
            $logout_url = wp_logout_url();
            
            $icon_html = '';
            $text_html = '';
            
            if ($button_display === 'both' || $button_display === 'icon_only') {
                $icon_svg = $this->get_icon_svg($logout_icon, 'logout');
                $icon_html = '<svg class="otp-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . 
                           $icon_svg . '</svg>';
            }
            
            if ($button_display === 'both' || $button_display === 'text_only') {
                $text_html = '<span class="button-text">' . esc_html($atts['text_logout']) . '</span>';
            }
            
            return '<a href="' . esc_url($logout_url) . '" class="otp-logout-btn ' . esc_attr($atts['class']) . '">' . 
                   $icon_html . $text_html . '</a>';
                   
        } else {
            // Show login button - V1.2.5 FIX: Using # and .otp-login-trigger class to trigger JS overlay instead of redirecting!
            $icon_html = '';
            $text_html = '';
            
            if ($button_display === 'both' || $button_display === 'icon_only') {
                $icon_svg = $this->get_icon_svg($login_icon, 'login');
                $icon_html = '<svg class="otp-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' . 
                           $icon_svg . '</svg>';
            }
            
            if ($button_display === 'both' || $button_display === 'text_only') {
                $text_html = '<span class="button-text">' . esc_html($atts['text_login']) . '</span>';
            }
            
            return '<a href="#" class="otp-login-btn otp-login-trigger ' . esc_attr($atts['class']) . '">' . 
                   $icon_html . $text_html . '</a>';
        }
    }
    
    private function get_icon_svg($icon_key, $type) {
        $available_icons = array(
            'login' => array(
                'login_default' => '<path d="M15 3h6v18h-6"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
                'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
                'lock' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><circle cx="12" cy="16" r="1"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
                'key' => '<circle cx="8" cy="8" r="6"/><path d="m13 13 5 5"/><path d="m13 13 2-5"/><path d="m13 13-5-2"/>',
                'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
                'log_in' => '<path d="M15 3h6v18h-6M10 17l5-5-5-5M15 12H3"/>',
                'user_check' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17,11 19,13 23,9"/>',
                'mail' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'
            ),
            'logout' => array(
                'logout_default' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/>',
                'door_open' => '<path d="M13 4h3a2 2 0 0 1 2 2v14"/><path d="M2 20h3"/><path d="M13 20h9"/><line x1="13" y1="4" x2="13" y2="20"/><circle cx="16" cy="12" r="1"/>',
                'exit' => '<path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="10" y2="12"/>',
                'power' => '<path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/>',
                'sign_out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/>',
                'x_circle' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',
                'user_x' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/>',
                'log_out' => '<path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="10" y2="12"/>'
            )
        );
        
        if (isset($available_icons[$type][$icon_key])) {
            return $available_icons[$type][$icon_key];
        }
        
        return $type === 'login' ? $available_icons['login']['login_default'] : $available_icons['logout']['logout_default'];
    }
}
