<?php
class OTP_Auth_OTP_Handler {
    
    private $table_name;
    private $email_handler;
    private $settings;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'otp_auth_codes';
        $this->email_handler = new OTP_Auth_Email_Handler();
        $this->settings = get_option('otp_auth_settings', array());
    }
    
    public function send_otp($email, $action_type) {
        // Clean up expired OTPs
        $this->cleanup_expired_otps();
        
        // Check if email exists for signup
        if ($action_type === 'signup' && email_exists($email)) {
            return array(
                'success' => false,
                'message' => __('An account with this email already exists. Please sign in instead.', 'otp-auth-plugin')
            );
        }
        
        // Check if email exists for signin
        if ($action_type === 'signin' && !email_exists($email)) {
            return array(
                'success' => false,
                'message' => __('No account found with this email. Please sign up first.', 'otp-auth-plugin')
            );
        }
        
        // Generate OTP
        $otp_code = $this->generate_otp();
        
        // Store OTP in database
        if ($this->store_otp($email, $otp_code, $action_type)) {
            // Send email
            if ($this->email_handler->send_otp_email($email, $otp_code, $action_type)) {
                return array(
                    'success' => true,
                    'message' => __('Verification code sent successfully. Please check your email (including spam folder).', 'otp-auth-plugin')
                );
            } else {
                return array(
                    'success' => false,
                    'message' => __('Failed to send verification code. Please try again.', 'otp-auth-plugin')
                );
            }
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to generate verification code. Please try again.', 'otp-auth-plugin')
            );
        }
    }
    
    public function verify_otp($email, $otp_code, $action_type, $redirect_url = '') {
        global $wpdb;
        
        // Get valid OTP
        $otp_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE email = %s 
             AND otp_code = %s 
             AND action_type = %s 
             AND is_used = 0 
             AND expires_at > NOW() 
             ORDER BY created_at DESC 
             LIMIT 1",
            $email, $otp_code, $action_type
        ));
        
        if (!$otp_record) {
            return array(
                'success' => false,
                'message' => __('Invalid or expired verification code. Please try again.', 'otp-auth-plugin')
            );
        }
        
        // Mark OTP as used
        $wpdb->update(
            $this->table_name,
            array('is_used' => 1),
            array('id' => $otp_record->id),
            array('%d'),
            array('%d')
        );
        
        // Handle user authentication
        if ($action_type === 'signup') {
            $user_id = $this->create_user($email);
        } else {
            $user = get_user_by('email', $email);
            $user_id = $user ? $user->ID : false;
        }
        
        if ($user_id) {
            // Log in the user
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);
            
            // Determine redirect URL based on settings
            $redirect_url = $this->get_redirect_url($redirect_url);
            
            return array(
                'success' => true,
                'message' => $action_type === 'signup' ? __('Account created successfully!', 'otp-auth-plugin') : __('Signed in successfully!', 'otp-auth-plugin'),
                'redirect_url' => $redirect_url
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to authenticate user. Please try again.', 'otp-auth-plugin')
            );
        }
    }
    
    private function get_redirect_url($fallback_url = '') {
        $settings = get_option('otp_auth_settings', array());
        $redirect_type = isset($settings['redirect_after_login']) ? $settings['redirect_after_login'] : 'previous_page';
        
        // Start session if not already started
        if (!session_id()) {
            session_start();
        }
        
        switch ($redirect_type) {
            case 'homepage':
                return home_url();
                
            case 'custom':
                $custom_url = isset($settings['custom_redirect_url']) ? $settings['custom_redirect_url'] : '';
                if (!empty($custom_url) && filter_var($custom_url, FILTER_VALIDATE_URL)) {
                    return $custom_url;
                }
                // Fall back to homepage if custom URL is invalid
                return home_url();
                
            case 'previous_page':
            default:
                // Priority order for redirect:
                // 1. Session stored redirect URL
                // 2. Referrer (if from same domain and not auth page)
                // 3. Fallback URL (if provided and valid)
                // 4. Homepage
                
                // Check for stored redirect URL in session
                if (isset($_SESSION['otp_auth_redirect'])) {
                    $redirect_url = $_SESSION['otp_auth_redirect'];
                    unset($_SESSION['otp_auth_redirect']);
                    
                    // Validate the URL is from the same domain and not an auth page
                    if ($this->is_valid_redirect_url($redirect_url)) {
                        return $redirect_url;
                    }
                }
                
                // Check referrer
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $referrer = $_SERVER['HTTP_REFERER'];
                    
                    if ($this->is_valid_redirect_url($referrer)) {
                        return $referrer;
                    }
                }
                
                // Check fallback URL
                if (!empty($fallback_url) && $this->is_valid_redirect_url($fallback_url)) {
                    return $fallback_url;
                }
                
                // Default to homepage
                return home_url();
        }
    }
    
    private function is_valid_redirect_url($url) {
        if (empty($url)) {
            return false;
        }
        
        $site_url = home_url();
        
        // Must be from same domain
        if (strpos($url, $site_url) !== 0) {
            return false;
        }
        
        // Must not be an auth page
        if ($this->is_auth_page_url($url)) {
            return false;
        }
        
        // Must not be wp-admin
        if (strpos($url, '/wp-admin') !== false) {
            return false;
        }
        
        // Must not be wp-login.php
        if (strpos($url, 'wp-login.php') !== false) {
            return false;
        }
        
        return true;
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
    
    private function generate_otp() {
        return sprintf('%06d', mt_rand(100000, 999999));
    }
    
    private function store_otp($email, $otp_code, $action_type) {
        global $wpdb;
        
        $expiry_minutes = isset($this->settings['otp_expiry']) ? $this->settings['otp_expiry'] : 15;
        
        return $wpdb->insert(
            $this->table_name,
            array(
                'email' => $email,
                'otp_code' => $otp_code,
                'action_type' => $action_type,
                'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"))
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    private function create_user($email) {
        $username = $this->generate_username_from_email($email);
        $password = wp_generate_password();
        
        // Get default role from settings
        $settings = get_option('otp_auth_settings', array());
        $default_role = isset($settings['default_user_role']) ? $settings['default_user_role'] : 'subscriber';
        
        // Validate role exists
        if (!get_role($default_role)) {
            $default_role = 'subscriber';
        }
        
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'role' => $default_role
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            error_log('OTP Auth: Failed to create user - ' . $user_id->get_error_message());
            return false;
        }
        
        return $user_id;
    }
    
    private function generate_username_from_email($email) {
        $username = sanitize_user(current(explode('@', $email)), true);
        
        // Ensure username is unique
        $counter = 1;
        $original_username = $username;
        
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    private function cleanup_expired_otps() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$this->table_name} 
             WHERE expires_at < NOW() 
             OR (is_used = 1 AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))"
        );
    }
}
