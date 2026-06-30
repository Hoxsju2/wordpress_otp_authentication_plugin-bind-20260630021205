<?php
class OTP_Auth_Controller {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('template_redirect', array($this, 'handle_template_redirect'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    public function init() {
        // Add rewrite rules for clean URLs
        add_rewrite_rule('^otp-login/?$', 'index.php?otp_auth_page=login', 'top');
        add_rewrite_rule('^otp-signup/?$', 'index.php?otp_auth_page=signup', 'top');
        add_rewrite_rule('^otp-auth/?$', 'index.php?otp_auth_page=auth', 'top');
    }
    
    public function add_query_vars($vars) {
        $vars[] = 'otp_auth_page';
        return $vars;
    }
    
    public function handle_template_redirect() {
        $otp_page = get_query_var('otp_auth_page');
        
        if ($otp_page) {
            // Handle virtual OTP auth pages
            $this->display_otp_auth_page($otp_page);
            exit;
        }
    }
    
    private function display_otp_auth_page($page_type) {
        // Set the action based on page type
        $default_action = ($page_type === 'signup') ? 'signup' : 'signin';
        
        // Check for action parameter in URL
        if (isset($_GET['action']) && $_GET['action'] === 'signup') {
            $default_action = 'signup';
        }
        
        // Store the referrer for redirect purposes
        $this->store_referrer_for_redirect();
        
        // Get header
        get_header();
        
        echo '<div class="otp-auth-page-wrapper">';
        
        // Display the auth form
        $frontend = new OTP_Auth_Frontend();
        echo $frontend->get_auth_page_content($default_action);
        
        echo '</div>';
        
        // Get footer
        get_footer();
    }
    
    private function store_referrer_for_redirect() {
        if (!session_id()) {
            session_start();
        }
        
        // Only store referrer if we don't already have a redirect URL stored
        if (isset($_SESSION['otp_auth_redirect'])) {
            return; // Already have a redirect URL stored
        }
        
        // Get the referrer
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        if (!empty($referrer)) {
            $site_url = home_url();
            
            // Only store same-domain referrers that aren't auth pages
            if (strpos($referrer, $site_url) === 0 && 
                !$this->is_auth_page_url($referrer) &&
                strpos($referrer, '/wp-admin') === false &&
                strpos($referrer, 'wp-login.php') === false) {
                $_SESSION['otp_auth_redirect'] = $referrer;
            }
        }
    }
    
    private function is_auth_page_url($url) {
        // Check if URL contains OTP auth indicators
        if (strpos($url, 'otp-auth') !== false || 
            strpos($url, 'otp-login') !== false || 
            strpos($url, 'otp-signup') !== false) {
            return true;
        }
        
        // Check if URL is a page with OTP shortcode
        global $wpdb;
        $page = $wpdb->get_row(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_content LIKE '%[otp_auth_page]%' 
             AND post_status = 'publish' 
             AND post_type = 'page' 
             LIMIT 1"
        );
        
        if ($page) {
            $page_url = get_permalink($page->ID);
            if (strpos($url, $page_url) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
