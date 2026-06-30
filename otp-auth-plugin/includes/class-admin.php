<?php
class OTP_Auth_Admin {
    
    private $available_icons;
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // Initialize available icons
        $this->init_available_icons();
    }
    
    private function init_available_icons() {
        $this->available_icons = array(
            'login' => array(
                'login_default' => array(
                    'name' => 'Default Login (Arrow Right)',
                    'svg' => '<path d="M15 3h6v18h-6"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>'
                ),
                'user' => array(
                    'name' => 'User Icon',
                    'svg' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'
                ),
                'lock' => array(
                    'name' => 'Lock Icon',
                    'svg' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><circle cx="12" cy="16" r="1"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'
                ),
                'key' => array(
                    'name' => 'Key Icon',
                    'svg' => '<circle cx="8" cy="8" r="6"/><path d="m13 13 5 5"/><path d="m13 13 2-5"/><path d="m13 13-5-2"/>'
                ),
                'shield' => array(
                    'name' => 'Shield Icon',
                    'svg' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>'
                ),
                'log_in' => array(
                    'name' => 'Log In Icon',
                    'svg' => '<path d="M15 3h6v18h-6M10 17l5-5-5-5M15 12H3"/>'
                ),
                'user_check' => array(
                    'name' => 'User Check',
                    'svg' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17,11 19,13 23,9"/>'
                ),
                'mail' => array(
                    'name' => 'Mail Icon',
                    'svg' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>'
                )
            ),
            'logout' => array(
                'logout_default' => array(
                    'name' => 'Default Logout (Log Out)',
                    'svg' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/>'
                ),
                'door_open' => array(
                    'name' => 'Door Open',
                    'svg' => '<path d="M13 4h3a2 2 0 0 1 2 2v14"/><path d="M2 20h3"/><path d="M13 20h9"/><line x1="13" y1="4" x2="13" y2="20"/><circle cx="16" cy="12" r="1"/>'
                ),
                'exit' => array(
                    'name' => 'Exit Icon',
                    'svg' => '<path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="10" y2="12"/>'
                ),
                'power' => array(
                    'name' => 'Power Off',
                    'svg' => '<path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/>'
                ),
                'sign_out' => array(
                    'name' => 'Sign Out',
                    'svg' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/>'
                ),
                'x_circle' => array(
                    'name' => 'X Circle',
                    'svg' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
                ),
                'user_x' => array(
                    'name' => 'User X',
                    'svg' => '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="18" y1="8" x2="23" y2="13"/><line x1="23" y1="8" x2="18" y2="13"/>'
                ),
                'log_out' => array(
                    'name' => 'Log Out Alt',
                    'svg' => '<path d="M10 3H6a2 2 0 0 0-2 2v14c0 1.1.9 2 2 2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="10" y2="12"/>'
                )
            )
        );
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('OTP Authentication Settings', 'otp-auth-plugin'),
            __('OTP Auth', 'otp-auth-plugin'),
            'manage_options',
            'otp-auth-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_init() {
        register_setting('otp_auth_settings', 'otp_auth_settings', array($this, 'sanitize_settings'));
        
        // General Settings Section
        add_settings_section(
            'otp_auth_general',
            __('General Settings', 'otp-auth-plugin'),
            array($this, 'general_section_callback'),
            'otp-auth-settings'
        );
        
        add_settings_field('force_otp_auth', __('Force OTP Authentication', 'otp-auth-plugin'), array($this, 'force_otp_auth_callback'), 'otp-auth-settings', 'otp_auth_general');
        add_settings_field('default_user_role', __('Default User Role', 'otp-auth-plugin'), array($this, 'default_user_role_callback'), 'otp-auth-settings', 'otp_auth_general');
        add_settings_field('redirect_after_login', __('Redirect After Login', 'otp-auth-plugin'), array($this, 'redirect_after_login_callback'), 'otp-auth-settings', 'otp_auth_general');
        add_settings_field('custom_redirect_url', __('Custom Redirect URL', 'otp-auth-plugin'), array($this, 'custom_redirect_url_callback'), 'otp-auth-settings', 'otp_auth_general');
        add_settings_field('otp_expiry', __('OTP Expiry (minutes)', 'otp-auth-plugin'), array($this, 'otp_expiry_callback'), 'otp-auth-settings', 'otp_auth_general');
        add_settings_field('protected_post_types', __('Protected Post Types', 'otp-auth-plugin'), array($this, 'protected_post_types_callback'), 'otp-auth-settings', 'otp_auth_general');
        
        // Email Settings Section
        add_settings_section('otp_auth_email', __('Email Settings', 'otp-auth-plugin'), array($this, 'email_section_callback'), 'otp-auth-settings');
        add_settings_field('email_from_name', __('Email From Name', 'otp-auth-plugin'), array($this, 'email_from_name_callback'), 'otp-auth-settings', 'otp_auth_email');
        add_settings_field('email_from_email', __('Email From Address', 'otp-auth-plugin'), array($this, 'email_from_email_callback'), 'otp-auth-settings', 'otp_auth_email');
        
        // Button Styling Section
        add_settings_section('otp_auth_button_styling', __('Login/Logout Button Styling', 'otp-auth-plugin'), array($this, 'button_styling_section_callback'), 'otp-auth-settings');
        add_settings_field('button_display', __('Button Display', 'otp-auth-plugin'), array($this, 'button_display_callback'), 'otp-auth-settings', 'otp_auth_button_styling');
        add_settings_field('login_icon', __('Login/Sign Up Icon', 'otp-auth-plugin'), array($this, 'login_icon_callback'), 'otp-auth-settings', 'otp_auth_button_styling');
        add_settings_field('logout_icon', __('Logout Icon', 'otp-auth-plugin'), array($this, 'logout_icon_callback'), 'otp-auth-settings', 'otp_auth_button_styling');
        add_settings_field('icon_size', __('Icon Size (pixels)', 'otp-auth-plugin'), array($this, 'icon_size_callback'), 'otp-auth-settings', 'otp_auth_button_styling');
        add_settings_field('icon_color', __('Icon Color', 'otp-auth-plugin'), array($this, 'icon_color_callback'), 'otp-auth-settings', 'otp_auth_button_styling');
        add_settings_field('button_bg_color', __('Button Background Color', 'otp-auth-plugin'), array($this, 'button_bg_color_callback'), 'otp-auth-settings', 'otp_auth_button_styling');
        add_settings_field('button_border_color', __('Button Border Color', 'otp-auth-plugin'), array($this, 'button_border_color_callback'), 'otp-auth-settings', 'otp_auth_button_styling');
        
        // CAPTCHA Settings Section
        add_settings_section('otp_auth_captcha', __('CAPTCHA Settings', 'otp-auth-plugin'), array($this, 'captcha_section_callback'), 'otp-auth-settings');
        add_settings_field('captcha_enabled', __('Enable CAPTCHA', 'otp-auth-plugin'), array($this, 'captcha_enabled_callback'), 'otp-auth-settings', 'otp_auth_captcha');
        add_settings_field('captcha_type', __('CAPTCHA Type', 'otp-auth-plugin'), array($this, 'captcha_type_callback'), 'otp-auth-settings', 'otp_auth_captcha');
        add_settings_field('recaptcha_site_key', __('reCAPTCHA Site Key', 'otp-auth-plugin'), array($this, 'recaptcha_site_key_callback'), 'otp-auth-settings', 'otp_auth_captcha');
        add_settings_field('recaptcha_secret', __('reCAPTCHA Secret Key', 'otp-auth-plugin'), array($this, 'recaptcha_secret_callback'), 'otp-auth-settings', 'otp_auth_captcha');
        add_settings_field('hcaptcha_site_key', __('hCaptcha Site Key', 'otp-auth-plugin'), array($this, 'hcaptcha_site_key_callback'), 'otp-auth-settings', 'otp_auth_captcha');
        add_settings_field('hcaptcha_secret', __('hCaptcha Secret Key', 'otp-auth-plugin'), array($this, 'hcaptcha_secret_callback'), 'otp-auth-settings', 'otp_auth_captcha');
    }
    
    public function show_admin_notices() {
        $validation_errors = get_transient('otp_auth_validation_errors');
        if ($validation_errors && is_array($validation_errors)) {
            foreach ($validation_errors as $error) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            }
            delete_transient('otp_auth_validation_errors');
        }
        
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'otp-auth-plugin') . '</p></div>';
        }
    }
    
    public function add_meta_boxes() {
        $settings = get_option('otp_auth_settings', array());
        $protected_post_types = $settings['protected_post_types'] ?? array();
        
        if (!empty($protected_post_types)) {
            foreach ($protected_post_types as $post_type) {
                add_meta_box('otp_auth_settings', __('OTP Authentication', 'otp-auth-plugin'), array($this, 'meta_box_callback'), $post_type, 'side', 'high');
            }
        }
    }
    
    public function meta_box_callback($post) {
        wp_nonce_field('otp_auth_meta_nonce', 'otp_auth_meta_nonce');
        $requires_auth = get_post_meta($post->ID, '_otp_requires_auth', true);
        echo '<label><input type="checkbox" name="_otp_requires_auth" value="1" ' . checked($requires_auth, '1', false) . ' /> ' . __('Require OTP authentication to view this content', 'otp-auth-plugin') . '</label>';
        echo '<p class="description">' . __('When enabled, users must authenticate via OTP to access this content.', 'otp-auth-plugin') . '</p>';
    }
    
    public function save_post_meta($post_id) {
        if (!isset($_POST['otp_auth_meta_nonce']) || !wp_verify_nonce($_POST['otp_auth_meta_nonce'], 'otp_auth_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        $requires_auth = isset($_POST['_otp_requires_auth']) ? '1' : '0';
        update_post_meta($post_id, '_otp_requires_auth', $requires_auth);
    }
    
    public function settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
        ?>
        <div class="wrap">
            <h1><?php _e('OTP Authentication Settings', 'otp-auth-plugin'); ?></h1>
            
            <!-- V1.2.6 TABS IMPLEMENTATION -->
            <h2 class="nav-tab-wrapper">
                <a href="?page=otp-auth-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Plugin Settings', 'otp-auth-plugin'); ?></a>
                <a href="?page=otp-auth-settings&tab=testing" class="nav-tab <?php echo $active_tab == 'testing' ? 'nav-tab-active' : ''; ?>"><?php _e('Testing & Debugging', 'otp-auth-plugin'); ?></a>
            </h2>

            <?php if ($active_tab == 'settings'): ?>
            
            <p><?php _e('Configure your OTP authentication system. This plugin only requires OTP when users try to login, signup, or perform actions requiring authentication - not for general browsing.', 'otp-auth-plugin'); ?></p>
            <div class="otp-auth-admin-container">
                <div class="otp-auth-admin-main">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('otp_auth_settings');
                        do_settings_sections('otp-auth-settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="otp-auth-admin-sidebar">
                    <div class="otp-auth-shortcodes-info">
                        <h3><?php _e('Available Shortcodes', 'otp-auth-plugin'); ?></h3>
                        <div class="shortcode-item">
                            <code>[otp_auth_page]</code>
                            <p><?php _e('Displays the main authentication page with login/signup options.', 'otp-auth-plugin'); ?></p>
                        </div>
                        <div class="shortcode-item">
                            <code>[otp_login_button]</code>
                            <p><?php _e('Shows Login/Logout button with modern icons. Auto-detects user status.', 'otp-auth-plugin'); ?></p>
                        </div>
                    </div>
                    
                    <div class="otp-auth-virtual-pages">
                        <h3><?php _e('Virtual Pages', 'otp-auth-plugin'); ?></h3>
                        <p><?php _e('You can also use these clean URLs:', 'otp-auth-plugin'); ?></p>
                        <ul>
                            <li><code><?php echo home_url('/otp-auth/'); ?></code></li>
                            <li><code><?php echo home_url('/otp-login/'); ?></code></li>
                            <li><code><?php echo home_url('/otp-signup/'); ?></code></li>
                        </ul>
                    </div>
                    
                    <div class="otp-auth-button-preview">
                        <h3><?php _e('Button Preview', 'otp-auth-plugin'); ?></h3>
                        <div id="button-preview-container">
                            <div id="login-button-preview" class="preview-button">
                                <span class="preview-icon" id="login-preview-icon"></span>
                                <span class="preview-text">Login</span>
                            </div>
                            <div id="logout-button-preview" class="preview-button">
                                <span class="preview-icon" id="logout-preview-icon"></span>
                                <span class="preview-text">Logout</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: ?>

            <!-- V1.2.6 TESTING AND DEBUGGING TAB -->
            <div class="otp-auth-testing-tool">
                <p>Welcome to the Testing Tool. Here you can execute the exact identical AJAX requests the frontend uses, but you will see the raw backend server responses. This will expose any hidden caching or blocking errors (like Google Chrome preventing redirects).</p>
                
                <div class="otp-test-layout">
                    <div class="otp-test-controls">
                        <div class="otp-test-card">
                            <h3>1. Request OTP</h3>
                            <table class="form-table">
                                <tr>
                                    <th><label for="test_email">Test Email Address</label></th>
                                    <td><input type="email" id="test_email" class="regular-text" placeholder="you@example.com"></td>
                                </tr>
                                <tr>
                                    <th><label for="test_action">Action Type</label></th>
                                    <td>
                                        <select id="test_action">
                                            <option value="signin">Sign In (Must exist)</option>
                                            <option value="signup">Sign Up (Must be new)</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <td><button type="button" id="btn_test_send" class="button button-primary">Execute: send_otp</button></td>
                                </tr>
                            </table>
                        </div>

                        <div class="otp-test-card" style="margin-top: 20px;">
                            <h3>2. Verify OTP</h3>
                            <table class="form-table">
                                <tr>
                                    <th><label for="test_otp">Enter 6-Digit OTP</label></th>
                                    <td><input type="text" id="test_otp" class="regular-text" maxlength="6" placeholder="123456"></td>
                                </tr>
                                <tr>
                                    <th></th>
                                    <td><button type="button" id="btn_test_verify" class="button button-primary">Execute: verify_otp</button></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="otp-test-console-container">
                        <h3>Server Output Console</h3>
                        <div class="otp-test-console" id="test_log_output">
                            <span style="color: #888;">[System Ready] Waiting for command execution...</span><br>
                        </div>
                        <button type="button" class="button" onclick="document.getElementById('test_log_output').innerHTML='';">Clear Console</button>
                    </div>
                </div>
            </div>
            
            <script>
                // Injecting backend variables securely for the testing tool to consume
                var otpTestAjaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
                var otpTestNonce = "<?php echo wp_create_nonce('otp_auth_nonce'); ?>";
            </script>

            <?php endif; ?>
        </div>
        
        <script type="application/json" id="otp-auth-icons-data">
        <?php echo json_encode($this->available_icons); ?>
        </script>
        <?php
    }
    
    public function sanitize_settings($input) {
        $output = array();
        $errors = array();
        
        $output['force_otp_auth'] = isset($input['force_otp_auth']) ? 1 : 0;
        $output['default_user_role'] = sanitize_text_field($input['default_user_role']);
        $output['redirect_after_login'] = sanitize_text_field($input['redirect_after_login']);
        $output['custom_redirect_url'] = esc_url_raw($input['custom_redirect_url']);
        $output['otp_expiry'] = absint($input['otp_expiry']);
        $output['protected_post_types'] = isset($input['protected_post_types']) && is_array($input['protected_post_types']) ? array_map('sanitize_text_field', $input['protected_post_types']) : array();
        $output['email_from_name'] = sanitize_text_field($input['email_from_name']);
        
        $email_from_address = sanitize_email($input['email_from_email']);
        if (empty($email_from_address) || !is_email($email_from_address)) {
            $errors[] = __('Email From Address must be a valid email address format.', 'otp-auth-plugin');
            $current_settings = get_option('otp_auth_settings', array());
            $output['email_from_email'] = isset($current_settings['email_from_email']) ? $current_settings['email_from_email'] : get_option('admin_email');
        } else {
            $output['email_from_email'] = $email_from_address;
        }
        
        $output['button_display'] = sanitize_text_field($input['button_display']);
        $output['login_icon'] = $this->sanitize_icon_selection($input['login_icon'], 'login', 'login_default');
        $output['logout_icon'] = $this->sanitize_icon_selection($input['logout_icon'], 'logout', 'logout_default');
        
        $icon_size = absint($input['icon_size']);
        $output['icon_size'] = ($icon_size < 12 || $icon_size > 48) ? 16 : $icon_size;
        
        $output['icon_color'] = sanitize_hex_color($input['icon_color']) ?: '#ffffff';
        $output['button_bg_color'] = sanitize_hex_color($input['button_bg_color']) ?: '#3b82f6';
        $output['button_border_color'] = sanitize_hex_color($input['button_border_color']) ?: '#3b82f6';
        
        $output['captcha_enabled'] = isset($input['captcha_enabled']) ? 1 : 0;
        $output['captcha_type'] = sanitize_text_field($input['captcha_type']);
        $output['recaptcha_site_key'] = sanitize_text_field($input['recaptcha_site_key']);
        $output['recaptcha_secret'] = sanitize_text_field($input['recaptcha_secret']);
        $output['hcaptcha_site_key'] = sanitize_text_field($input['hcaptcha_site_key']);
        $output['hcaptcha_secret'] = sanitize_text_field($input['hcaptcha_secret']);
        
        if (!empty($errors)) set_transient('otp_auth_validation_errors', $errors, 30);
        flush_rewrite_rules();
        return $output;
    }
    
    private function sanitize_icon_selection($icon_key, $type, $default) {
        if (isset($this->available_icons[$type][$icon_key])) return sanitize_text_field($icon_key);
        return $default;
    }
    
    // Admin Setting Callbacks Below
    public function general_section_callback() { echo '<p>' . __('Configure general OTP authentication settings.', 'otp-auth-plugin') . '</p>'; }
    public function email_section_callback() { echo '<p>' . __('Configure email settings for OTP delivery.', 'otp-auth-plugin') . '</p>'; }
    public function button_styling_section_callback() { echo '<p>' . __('Customize the appearance of your login/logout buttons.', 'otp-auth-plugin') . '</p>'; }
    
    public function force_otp_auth_callback() {
        $settings = get_option('otp_auth_settings', array());
        $checked = isset($settings['force_otp_auth']) && $settings['force_otp_auth'] ? 'checked' : '';
        echo '<input type="checkbox" name="otp_auth_settings[force_otp_auth]" value="1" ' . $checked . ' />';
    }
    public function default_user_role_callback() {
        $settings = get_option('otp_auth_settings', array());
        $selected_role = isset($settings['default_user_role']) ? $settings['default_user_role'] : 'subscriber';
        if (!function_exists('wp_roles')) require_once ABSPATH . 'wp-includes/capabilities.php';
        echo '<select name="otp_auth_settings[default_user_role]">';
        foreach (wp_roles()->get_names() as $role_key => $role_name) {
            echo '<option value="' . esc_attr($role_key) . '" ' . selected($selected_role, $role_key, false) . '>' . esc_html($role_name) . '</option>';
        }
        echo '</select>';
    }
    public function redirect_after_login_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['redirect_after_login']) ? $settings['redirect_after_login'] : 'previous_page';
        echo '<select name="otp_auth_settings[redirect_after_login]" id="redirect_after_login">';
        echo '<option value="previous_page"' . selected($value, 'previous_page', false) . '>Previous page</option>';
        echo '<option value="homepage"' . selected($value, 'homepage', false) . '>Always homepage</option>';
        echo '<option value="custom"' . selected($value, 'custom', false) . '>Custom URL</option>';
        echo '</select>';
    }
    public function custom_redirect_url_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['custom_redirect_url']) ? $settings['custom_redirect_url'] : '';
        echo '<input type="url" name="otp_auth_settings[custom_redirect_url]" value="' . esc_attr($value) . '" class="regular-text" id="custom_redirect_url" />';
    }
    public function otp_expiry_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['otp_expiry']) ? $settings['otp_expiry'] : 15;
        echo '<input type="number" name="otp_auth_settings[otp_expiry]" value="' . esc_attr($value) . '" min="1" max="60" />';
    }
    public function protected_post_types_callback() {
        $settings = get_option('otp_auth_settings', array());
        $selected_types = $settings['protected_post_types'] ?? array();
        echo '<fieldset>';
        foreach (get_post_types(array('public' => true), 'objects') as $post_type) {
            $checked = in_array($post_type->name, $selected_types) ? 'checked' : '';
            echo '<label><input type="checkbox" name="otp_auth_settings[protected_post_types][]" value="' . esc_attr($post_type->name) . '" ' . $checked . ' /> ' . esc_html($post_type->label) . '</label><br>';
        }
        echo '</fieldset>';
    }
    public function email_from_name_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['email_from_name']) ? $settings['email_from_name'] : get_bloginfo('name');
        echo '<input type="text" name="otp_auth_settings[email_from_name]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    public function email_from_email_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['email_from_email']) ? $settings['email_from_email'] : get_option('admin_email');
        echo '<input type="email" name="otp_auth_settings[email_from_email]" value="' . esc_attr($value) . '" class="regular-text" required />';
    }
    public function button_display_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['button_display']) ? $settings['button_display'] : 'both';
        echo '<select name="otp_auth_settings[button_display]" id="button_display">';
        echo '<option value="both"' . selected($value, 'both', false) . '>Icon + Text</option>';
        echo '<option value="icon_only"' . selected($value, 'icon_only', false) . '>Icon Only</option>';
        echo '<option value="text_only"' . selected($value, 'text_only', false) . '>Text Only</option>';
        echo '</select>';
    }
    public function login_icon_callback() {
        $settings = get_option('otp_auth_settings', array());
        $selected_icon = isset($settings['login_icon']) ? $settings['login_icon'] : 'login_default';
        echo '<select name="otp_auth_settings[login_icon]" id="login_icon" class="icon-select">';
        foreach ($this->available_icons['login'] as $key => $icon) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($selected_icon, $key, false) . ' data-svg="' . esc_attr($icon['svg']) . '">' . esc_html($icon['name']) . '</option>';
        }
        echo '</select><div class="icon-preview" id="login-icon-preview" style="margin-top: 10px;"></div>';
    }
    public function logout_icon_callback() {
        $settings = get_option('otp_auth_settings', array());
        $selected_icon = isset($settings['logout_icon']) ? $settings['logout_icon'] : 'logout_default';
        echo '<select name="otp_auth_settings[logout_icon]" id="logout_icon" class="icon-select">';
        foreach ($this->available_icons['logout'] as $key => $icon) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($selected_icon, $key, false) . ' data-svg="' . esc_attr($icon['svg']) . '">' . esc_html($icon['name']) . '</option>';
        }
        echo '</select><div class="icon-preview" id="logout-icon-preview" style="margin-top: 10px;"></div>';
    }
    public function icon_size_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['icon_size']) ? $settings['icon_size'] : 16;
        echo '<input type="number" name="otp_auth_settings[icon_size]" value="' . esc_attr($value) . '" min="12" max="48" id="icon_size" />';
        echo '<input type="range" id="icon_size_slider" min="12" max="48" value="' . esc_attr($value) . '" style="margin-left: 10px; width: 200px;" />';
        echo '<span id="icon_size_display" style="margin-left: 10px; font-weight: bold;">' . esc_html($value) . 'px</span>';
    }
    public function icon_color_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['icon_color']) ? $settings['icon_color'] : '#ffffff';
        echo '<input type="color" name="otp_auth_settings[icon_color]" value="' . esc_attr($value) . '" id="icon_color" />';
        echo '<input type="text" name="otp_auth_settings[icon_color]" value="' . esc_attr($value) . '" class="color-text-input" id="icon_color_text" placeholder="#ffffff" />';
    }
    public function button_bg_color_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['button_bg_color']) ? $settings['button_bg_color'] : '#3b82f6';
        echo '<input type="color" name="otp_auth_settings[button_bg_color]" value="' . esc_attr($value) . '" id="button_bg_color" />';
        echo '<input type="text" name="otp_auth_settings[button_bg_color]" value="' . esc_attr($value) . '" class="color-text-input" id="button_bg_color_text" placeholder="#3b82f6" />';
    }
    public function button_border_color_callback() {
        $settings = get_option('otp_auth_settings', array());
        $value = isset($settings['button_border_color']) ? $settings['button_border_color'] : '#3b82f6';
        echo '<input type="color" name="otp_auth_settings[button_border_color]" value="' . esc_attr($value) . '" id="button_border_color" />';
        echo '<input type="text" name="otp_auth_settings[button_border_color]" value="' . esc_attr($value) . '" class="color-text-input" id="button_border_color_text" placeholder="#3b82f6" />';
    }
    public function captcha_section_callback() { echo '<p>' . __('Configure CAPTCHA protection to prevent spam.', 'otp-auth-plugin') . '</p>'; }
    public function captcha_enabled_callback() {
        $settings = get_option('otp_auth_settings', array());
        echo '<input type="checkbox" name="otp_auth_settings[captcha_enabled]" value="1" ' . (isset($settings['captcha_enabled']) && $settings['captcha_enabled'] ? 'checked' : '') . ' />';
    }
    public function captcha_type_callback() {
        $settings = get_option('otp_auth_settings', array());
        echo '<select name="otp_auth_settings[captcha_type]">';
        echo '<option value="recaptcha"' . selected(isset($settings['captcha_type']) ? $settings['captcha_type'] : 'recaptcha', 'recaptcha', false) . '>reCAPTCHA v2</option>';
        echo '<option value="hcaptcha"' . selected(isset($settings['captcha_type']) ? $settings['captcha_type'] : 'recaptcha', 'hcaptcha', false) . '>hCaptcha</option>';
        echo '</select>';
    }
    public function recaptcha_site_key_callback() {
        $settings = get_option('otp_auth_settings', array());
        echo '<input type="text" name="otp_auth_settings[recaptcha_site_key]" value="' . esc_attr($settings['recaptcha_site_key'] ?? '') . '" class="regular-text" />';
    }
    public function recaptcha_secret_callback() {
        $settings = get_option('otp_auth_settings', array());
        echo '<input type="password" name="otp_auth_settings[recaptcha_secret]" value="' . esc_attr($settings['recaptcha_secret'] ?? '') . '" class="regular-text" />';
    }
    public function hcaptcha_site_key_callback() {
        $settings = get_option('otp_auth_settings', array());
        echo '<input type="text" name="otp_auth_settings[hcaptcha_site_key]" value="' . esc_attr($settings['hcaptcha_site_key'] ?? '') . '" class="regular-text" />';
    }
    public function hcaptcha_secret_callback() {
        $settings = get_option('otp_auth_settings', array());
        echo '<input type="password" name="otp_auth_settings[hcaptcha_secret]" value="' . esc_attr($settings['hcaptcha_secret'] ?? '') . '" class="regular-text" />';
    }
}
