<?php
if (!defined('ABSPATH')) {
    exit;
}

class OTP_Auth_Ajax {
    public function __construct() {
        add_action('wp_ajax_send_otp', array($this, 'ajax_send_otp'));
        add_action('wp_ajax_nopriv_send_otp', array($this, 'ajax_send_otp'));
        add_action('wp_ajax_verify_otp', array($this, 'ajax_verify_otp'));
        add_action('wp_ajax_nopriv_verify_otp', array($this, 'ajax_verify_otp'));
    }
    
    public function ajax_send_otp() {
        check_ajax_referer('otp_auth_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $action_type = sanitize_text_field($_POST['action_type']);
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => esc_html__('Please enter a valid email address.', 'otp-auth-plugin')));
        }
        
        if ($this->verify_captcha()) {
            $otp_handler = new OTP_Auth_OTP_Handler();
            $result = $otp_handler->send_otp($email, $action_type);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } else {
            wp_send_json_error(array('message' => esc_html__('CAPTCHA verification failed.', 'otp-auth-plugin')));
        }
    }
    
    public function ajax_verify_otp() {
        check_ajax_referer('otp_auth_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $otp = sanitize_text_field($_POST['otp']);
        $action_type = sanitize_text_field($_POST['action_type']);
        $redirect_url = esc_url_raw($_POST['redirect_url']);
        
        $otp_handler = new OTP_Auth_OTP_Handler();
        $result = $otp_handler->verify_otp($email, $otp, $action_type, $redirect_url);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    private function verify_captcha() {
        $settings = get_option('otp_auth_settings', array());
        if (empty($settings['captcha_enabled'])) return true;
        
        $captcha_type = isset($settings['captcha_type']) ? sanitize_text_field($settings['captcha_type']) : 'recaptcha';
        $response = isset($_POST['captcha_response']) ? sanitize_text_field($_POST['captcha_response']) : '';
        if (empty($response)) return false;
        
        $args = array(
            'body' => array(
                'response' => $response,
                'remoteip' => sanitize_text_field($_SERVER['REMOTE_ADDR'])
            )
        );

        if ($captcha_type === 'recaptcha') {
            $args['body']['secret'] = sanitize_text_field($settings['recaptcha_secret']);
            $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', $args);
        } elseif ($captcha_type === 'hcaptcha') {
            $args['body']['secret'] = sanitize_text_field($settings['hcaptcha_secret']);
            $verify = wp_remote_post('https://hcaptcha.com/siteverify', $args);
        } else {
            return false;
        }

        if (is_wp_error($verify)) return false;
        $body = json_decode(wp_remote_retrieve_body($verify));
        return !empty($body->success);
    }
}
