<?php
class OTP_Auth_Frontend {
    
    public function __construct() {
        // Initialization for frontend
    }
    
    public function get_auth_page_content($default_action = 'signin', $auto_open = false) {
        // Prevent duplicate rendering if multiple buttons/shortcodes exist on the same page
        if (defined('OTP_AUTH_POPUP_RENDERED')) {
            if ($auto_open) {
                return '<script>document.addEventListener("DOMContentLoaded", function() { setTimeout(function(){ jQuery("#otp-auth-container").data("auto-open", true).addClass("is-active").show(); jQuery("body").addClass("otp-auth-overlay-active"); }, 100); });</script>';
            }
            return ''; 
        }
        define('OTP_AUTH_POPUP_RENDERED', true);
        
        ob_start();
        
        $auto_open_attr = $auto_open ? 'data-auto-open="true"' : 'data-auto-open="false"';
        
        ?>
        <div id="otp-auth-container" class="otp-auth-wrapper" <?php echo $auto_open_attr; ?> style="z-index: 2147483647 !important;">
            <div class="otp-auth-form-container">
                
                <!-- Close Button -->
                <button type="button" id="otp-auth-close" class="otp-auth-close-btn" aria-label="<?php esc_attr_e('Close', 'otp-auth-plugin'); ?>">
                    <svg class="otp-auth-close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>

                <div class="otp-auth-header">
                    <h2 class="otp-auth-title" id="otp-dynamic-title"><?php _e('Welcome', 'otp-auth-plugin'); ?></h2>
                    <p class="otp-auth-subtitle" id="otp-dynamic-subtitle"><?php _e('Sign in to your account to continue', 'otp-auth-plugin'); ?></p>
                </div>
                
                <div class="otp-auth-toggle">
                    <button type="button" class="otp-auth-tab <?php echo $default_action === 'signin' ? 'active' : ''; ?>" data-tab="signin">
                        <svg class="otp-auth-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v18h-6M10 17l5-5-5-5M15 12H3"/></svg>
                        <?php _e('Sign In', 'otp-auth-plugin'); ?>
                    </button>
                    <button type="button" class="otp-auth-tab <?php echo $default_action === 'signup' ? 'active' : ''; ?>" data-tab="signup">
                        <svg class="otp-auth-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        <?php _e('Sign Up', 'otp-auth-plugin'); ?>
                    </button>
                </div>
                
                <form id="otp-auth-form" class="otp-auth-form" onsubmit="return false;">
                    <input type="hidden" id="current_action" value="<?php echo esc_attr($default_action); ?>">
                    
                    <!-- Step 1: Email Input -->
                    <div id="step-email" class="otp-auth-step">
                        <div class="otp-auth-field">
                            <label for="otp_email"><?php _e('Email Address', 'otp-auth-plugin'); ?></label>
                            <input type="email" id="otp_email" name="otp_email" required placeholder="you@example.com" autocomplete="email">
                        </div>
                        
                        <?php $this->render_captcha(); ?>
                        
                        <button type="button" id="send-otp-btn" class="otp-auth-btn otp-auth-btn-primary">
                            <span class="btn-text"><?php _e('Continue with Email', 'otp-auth-plugin'); ?></span>
                            <div class="btn-loader">
                                <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></path>
                                </svg>
                            </div>
                        </button>
                        
                        <div class="otp-auth-info">
                            <svg class="otp-auth-info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <p><?php _e('A secure verification code will be sent to your email to verify your identity. Don\'t forget to check your spam folder.', 'otp-auth-plugin'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Step 2: OTP Verification -->
                    <div id="step-otp" class="otp-auth-step" style="display: none;">
                        <div class="otp-auth-field">
                            <label for="otp_code"><?php _e('6-Digit Verification Code', 'otp-auth-plugin'); ?></label>
                            <input type="text" id="otp_code" name="otp_code" pattern="[0-9]{6}" maxlength="6" placeholder="123456" autocomplete="one-time-code">
                        </div>
                        
                        <button type="button" id="verify-otp-btn" class="otp-auth-btn otp-auth-btn-primary">
                            <span class="btn-text"><?php _e('Verify & Continue', 'otp-auth-plugin'); ?></span>
                            <div class="btn-loader">
                                <svg class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="1s" repeatCount="indefinite"/></path>
                                </svg>
                            </div>
                        </button>
                        
                        <div class="otp-auth-resend">
                            <p><?php _e('Didn\'t receive the code?', 'otp-auth-plugin'); ?> <button type="button" id="resend-otp" class="otp-auth-link"><?php _e('Resend Code', 'otp-auth-plugin'); ?></button></p>
                        </div>
                        
                        <button type="button" id="back-to-email" class="otp-auth-btn otp-auth-btn-secondary">
                            <?php _e('Use a different email', 'otp-auth-plugin'); ?>
                        </button>
                    </div>
                    
                    <div id="otp-auth-messages" class="otp-auth-messages"></div>
                </form>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    private function render_captcha() {
        $settings = get_option('otp_auth_settings', array());
        
        if (empty($settings['captcha_enabled'])) {
            return;
        }
        
        $captcha_type = isset($settings['captcha_type']) ? $settings['captcha_type'] : 'recaptcha';
        
        echo '<div class="otp-auth-captcha">';
        
        if ($captcha_type === 'recaptcha' && !empty($settings['recaptcha_site_key'])) {
            wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true);
            echo '<div class="g-recaptcha" data-sitekey="' . esc_attr($settings['recaptcha_site_key']) . '"></div>';
        } elseif ($captcha_type === 'hcaptcha' && !empty($settings['hcaptcha_site_key'])) {
            wp_enqueue_script('hcaptcha', 'https://js.hcaptcha.com/1/api.js', array(), null, true);
            echo '<div class="h-captcha" data-sitekey="' . esc_attr($settings['hcaptcha_site_key']) . '"></div>';
        }
        
        echo '</div>';
    }
}
