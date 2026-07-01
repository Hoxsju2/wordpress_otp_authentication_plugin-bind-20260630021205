<?php
/**
 * Plugin Name: OTP Authentication Plugin
 * Plugin URI: https://yourwebsite.com/
 * Description: A comprehensive OTP-based authentication system with email verification, Google Sign-In, and modern design. Fully compatible with WordPress 7.0.
 * Version: 1.4.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: otp-auth-plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OTP_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OTP_AUTH_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('OTP_AUTH_VERSION', '1.4.0');

class OTPAuthPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load text domain
        load_plugin_textdomain('otp-auth-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        $this->includes();
        $this->init_classes();
    }
    
    private function includes() {
        // Base Files
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-admin.php';
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-email-handler.php';
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-otp-handler.php';
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-shortcodes.php';
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-auth-controller.php';
        
        // Version Modular Files
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-activator.php';
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-core.php';
        
        // V1.3.0 GitHub Auto Updater
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-github-updater.php';

        // V1.4.0 Google Authentication
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-google-auth.php';
    }
    
    private function init_classes() {
        // Base Classes
        new OTP_Auth_Admin();
        new OTP_Auth_Frontend();
        new OTP_Auth_Email_Handler();
        new OTP_Auth_OTP_Handler();
        new OTP_Auth_Shortcodes();
        new OTP_Auth_Controller();
        
        // Version Modular Classes
        new OTP_Auth_Ajax();
        new OTP_Auth_Core();
        
        // V1.3.0 GitHub Auto Updater Class
        new OTP_Auth_GitHub_Updater();

        // V1.4.0 Google Authentication Class
        new OTP_Auth_Google_Auth();
    }
    
    public function activate() {
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-activator.php';
        OTP_Auth_Activator::activate();
    }
    
    public function deactivate() {
        require_once OTP_AUTH_PLUGIN_PATH . 'includes/class-activator.php';
        OTP_Auth_Activator::deactivate();
    }
}

// Initialize the plugin
new OTPAuthPlugin();
