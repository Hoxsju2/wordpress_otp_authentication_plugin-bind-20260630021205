<?php
class OTP_Auth_Email_Handler {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('otp_auth_settings', array());
        add_action('phpmailer_init', array($this, 'configure_phpmailer'));
    }
    
    public function send_otp_email($email, $otp_code, $action_type) {
        $subject = $this->get_email_subject($action_type);
        $message = $this->get_email_message($otp_code, $action_type);
        $headers = $this->get_email_headers();
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    private function get_email_subject($action_type) {
        $site_name = get_bloginfo('name');
        
        if ($action_type === 'signup') {
            return sprintf(__('Welcome to %s - Verify Your Email', 'otp-auth-plugin'), $site_name);
        } else {
            return sprintf(__('Sign in to %s - Verification Code', 'otp-auth-plugin'), $site_name);
        }
    }
    
    private function get_email_message($otp_code, $action_type) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $expiry_minutes = isset($this->settings['otp_expiry']) ? $this->settings['otp_expiry'] : 15;
        
        if ($action_type === 'signup') {
            $greeting = __('Welcome to %s!', 'otp-auth-plugin');
            $intro = __('Thank you for creating an account with us. To complete your registration, please verify your email address using the code below:', 'otp-auth-plugin');
        } else {
            $greeting = __('Sign in to %s', 'otp-auth-plugin');
            $intro = __('You requested to sign in to your account. Please use the verification code below to complete the process:', 'otp-auth-plugin');
        }
        
        $message = sprintf($greeting, $site_name) . "\n\n";
        $message .= sprintf($intro) . "\n\n";
        $message .= __('Verification Code:', 'otp-auth-plugin') . "\n";
        $message .= $otp_code . "\n\n";
        $message .= sprintf(__('This code will expire in %d minutes.', 'otp-auth-plugin'), $expiry_minutes) . "\n\n";
        $message .= __('If you didn\'t request this code, please ignore this email.', 'otp-auth-plugin') . "\n\n";
        $message .= __('Best regards,', 'otp-auth-plugin') . "\n";
        $message .= sprintf(__('The %s Team', 'otp-auth-plugin'), $site_name) . "\n";
        $message .= $site_url;
        
        return $message;
    }
    
    private function get_email_headers() {
        $from_name = isset($this->settings['email_from_name']) ? $this->settings['email_from_name'] : get_bloginfo('name');
        $from_email = isset($this->settings['email_from_email']) ? $this->settings['email_from_email'] : get_option('admin_email');
        
        $headers = array();
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = sprintf('From: %s <%s>', $from_name, $from_email);
        
        return $headers;
    }
    
    public function configure_phpmailer($phpmailer) {
        // Additional PHPMailer configuration if needed
        $phpmailer->CharSet = 'UTF-8';
    }
}
