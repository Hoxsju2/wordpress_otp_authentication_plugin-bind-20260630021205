jQuery(document).ready(function($) {
    // Get icons data from the hidden script tag
    const iconsData = JSON.parse($('#otp-auth-icons-data').text() || '{}');
    
    // Admin JavaScript functionality
    
    // Toggle CAPTCHA settings visibility
    function toggleCaptchaSettings() {
        const captchaEnabled = $('input[name="otp_auth_settings[captcha_enabled]"]').is(':checked');
        const captchaType = $('select[name="otp_auth_settings[captcha_type]"]').val();
        
        // Show/hide all CAPTCHA related fields
        $('tr').has('input[name*="captcha"], select[name*="captcha"]').not(':first').toggle(captchaEnabled);
        
        if (captchaEnabled) {
            // Show/hide specific CAPTCHA type fields
            $('tr').has('input[name*="recaptcha"]').toggle(captchaType === 'recaptcha');
            $('tr').has('input[name*="hcaptcha"]').toggle(captchaType === 'hcaptcha');
        }
    }
    
    // Toggle custom redirect URL field
    function toggleCustomRedirectUrl() {
        const redirectType = $('#redirect_after_login').val();
        const $customUrlRow = $('#custom_redirect_url').closest('tr');
        
        if (redirectType === 'custom') {
            $customUrlRow.show();
        } else {
            $customUrlRow.hide();
        }
    }
    
    // Update icon size slider and input
    function syncIconSizeControls() {
        const $sizeInput = $('#icon_size');
        const $sizeSlider = $('#icon_size_slider');
        const $sizeDisplay = $('#icon_size_display');
        
        $sizeInput.on('input', function() {
            const value = parseInt($(this).val());
            if (value >= 12 && value <= 48) {
                $sizeSlider.val(value);
                $sizeDisplay.text(value + 'px');
                updateButtonPreview();
            }
        });
        
        $sizeSlider.on('input', function() {
            const value = parseInt($(this).val());
            $sizeInput.val(value);
            $sizeDisplay.text(value + 'px');
            updateButtonPreview();
        });
    }
    
    // Update icon preview when selection changes
    function updateIconPreviews() {
        const loginIcon = $('#login_icon').val();
        const logoutIcon = $('#logout_icon').val();
        const iconColor = $('#icon_color').val();
        const iconSize = parseInt($('#icon_size').val()) || 16;
        
        // Update login icon preview
        if (iconsData.login && iconsData.login[loginIcon]) {
            const loginSvg = `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="${iconColor}" stroke-width="2">${iconsData.login[loginIcon].svg}</svg>`;
            $('#login-icon-preview').html(`<div style="display: flex; align-items: center; gap: 8px; margin-top: 5px;"><strong>Preview:</strong> ${loginSvg}</div>`);
        }
        
        // Update logout icon preview
        if (iconsData.logout && iconsData.logout[logoutIcon]) {
            const logoutSvg = `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="${iconColor}" stroke-width="2">${iconsData.logout[logoutIcon].svg}</svg>`;
            $('#logout-icon-preview').html(`<div style="display: flex; align-items: center; gap: 8px; margin-top: 5px;"><strong>Preview:</strong> ${logoutSvg}</div>`);
        }
    }
    
    // Update button preview
    function updateButtonPreview() {
        const buttonDisplay = $('#button_display').val();
        const iconColor = $('#icon_color').val();
        const buttonBgColor = $('#button_bg_color').val();
        const buttonBorderColor = $('#button_border_color').val();
        const iconSize = parseInt($('#icon_size').val()) || 16;
        const loginIcon = $('#login_icon').val();
        const logoutIcon = $('#logout_icon').val();
        
        // Update preview styles
        $('.preview-button').css({
            'background': buttonBgColor,
            'color': iconColor,
            'border-color': buttonBorderColor
        });
        
        // Update login button preview
        if (iconsData.login && iconsData.login[loginIcon]) {
            const loginSvg = `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${iconsData.login[loginIcon].svg}</svg>`;
            $('#login-preview-icon').html(loginSvg);
        }
        
        // Update logout button preview
        if (iconsData.logout && iconsData.logout[logoutIcon]) {
            const logoutSvg = `<svg width="${iconSize}" height="${iconSize}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${iconsData.logout[logoutIcon].svg}</svg>`;
            $('#logout-preview-icon').html(logoutSvg);
        }
        
        // Handle display options
        if (buttonDisplay === 'icon_only') {
            $('.preview-text').hide();
            $('.preview-icon').show();
        } else if (buttonDisplay === 'text_only') {
            $('.preview-icon').hide();
            $('.preview-text').show();
        } else { // both
            $('.preview-icon').show();
            $('.preview-text').show();
        }
        
        // Sync color inputs
        $('#icon_color_text').val(iconColor);
        $('#button_bg_color_text').val(buttonBgColor);
        $('#button_border_color_text').val(buttonBorderColor);
        
        // Update individual icon previews
        updateIconPreviews();
    }
    
    // Validate hex color
    function isValidHexColor(color) {
        return /^#[0-9A-F]{6}$/i.test(color);
    }
    
    // Validate email format
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Real-time email validation
    function validateEmailField() {
        const $emailField = $('input[name="otp_auth_settings[email_from_email]"]');
        const $emailRow = $emailField.closest('tr');
        
        $emailField.on('input blur', function() {
            const email = $(this).val().trim();
            
            if (email && !isValidEmail(email)) {
                $(this).addClass('invalid-email');
                
                // Remove existing error message
                $emailRow.find('.email-validation-error').remove();
                
                // Add error message
                const $errorMsg = $('<div class="email-validation-error" style="color: #d63638; font-size: 13px; margin-top: 5px;"><strong>Error:</strong> Please enter a valid email address format (e.g., noreply@yourdomain.com).</div>');
                $emailField.parent().append($errorMsg);
            } else {
                $(this).removeClass('invalid-email');
                $emailRow.find('.email-validation-error').remove();
            }
        });
    }
    
    // Sync color picker with text input
    function syncColorInputs() {
        // Icon color sync
        $('#icon_color').on('change', function() {
            $('#icon_color_text').val($(this).val());
            updateButtonPreview();
        });
        
        $('#icon_color_text').on('input', function() {
            const color = $(this).val();
            if (isValidHexColor(color)) {
                $('#icon_color').val(color);
                $(this).removeClass('invalid-color').addClass('valid-color');
                updateButtonPreview();
            } else {
                $(this).removeClass('valid-color').addClass('invalid-color');
            }
        });
        
        // Background color sync
        $('#button_bg_color').on('change', function() {
            $('#button_bg_color_text').val($(this).val());
            updateButtonPreview();
        });
        
        $('#button_bg_color_text').on('input', function() {
            const color = $(this).val();
            if (isValidHexColor(color)) {
                $('#button_bg_color').val(color);
                $(this).removeClass('invalid-color').addClass('valid-color');
                updateButtonPreview();
            } else {
                $(this).removeClass('valid-color').addClass('invalid-color');
            }
        });
        
        // Border color sync
        $('#button_border_color').on('change', function() {
            $('#button_border_color_text').val($(this).val());
            updateButtonPreview();
        });
        
        $('#button_border_color_text').on('input', function() {
            const color = $(this).val();
            if (isValidHexColor(color)) {
                $('#button_border_color').val(color);
                $(this).removeClass('invalid-color').addClass('valid-color');
                updateButtonPreview();
            } else {
                $(this).removeClass('valid-color').addClass('invalid-color');
            }
        });
    }
    
    // Initial setup
    toggleCaptchaSettings();
    toggleCustomRedirectUrl();
    syncIconSizeControls();
    updateButtonPreview();
    syncColorInputs();
    validateEmailField();
    updateIconPreviews();
    
    // Bind events
    $('input[name="otp_auth_settings[captcha_enabled]"]').on('change', toggleCaptchaSettings);
    $('select[name="otp_auth_settings[captcha_type]"]').on('change', toggleCaptchaSettings);
    $('#redirect_after_login').on('change', toggleCustomRedirectUrl);
    $('#button_display').on('change', updateButtonPreview);
    $('#login_icon, #logout_icon').on('change', updateButtonPreview);
    $('#icon_color, #button_bg_color, #button_border_color').on('change', updateButtonPreview);
    
    // Copy shortcode functionality
    $('.shortcode-item code').on('click', function() {
        const text = $(this).text();
        
        // Copy to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showCopyNotification('Shortcode copied to clipboard!');
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showCopyNotification('Shortcode copied to clipboard!');
        }
    });
    
    function showCopyNotification(message) {
        const $notification = $('<div class="notice notice-success is-dismissible" style="position: fixed; top: 32px; right: 20px; z-index: 9999; max-width: 300px;"><p>' + message + '</p></div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Enhanced form validation
    $('form').on('submit', function(e) {
        let isValid = true;
        const errors = [];
        
        // Validate email from address (most important)
        const fromEmail = $('input[name="otp_auth_settings[email_from_email]"]').val().trim();
        if (!fromEmail) {
            errors.push('Email From Address is required.');
            isValid = false;
        } else if (!isValidEmail(fromEmail)) {
            errors.push('Email From Address must be a valid email format (e.g., noreply@yourdomain.com).');
            isValid = false;
        }
        
        // Validate icon size
        const iconSize = parseInt($('#icon_size').val());
        if (iconSize < 12 || iconSize > 48) {
            errors.push('Icon size must be between 12 and 48 pixels.');
            isValid = false;
        }
        
        // Validate color inputs
        $('.color-text-input').each(function() {
            const color = $(this).val().trim();
            if (color && !isValidHexColor(color)) {
                $(this).addClass('invalid-color');
                const fieldName = $(this).attr('id').replace('_text', '').replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                errors.push(fieldName + ' must be a valid hex color code (e.g., #ffffff).');
                isValid = false;
            } else {
                $(this).removeClass('invalid-color');
            }
        });
        
        // Validate OTP expiry
        const otpExpiry = $('input[name="otp_auth_settings[otp_expiry]"]').val();
        if (otpExpiry < 1 || otpExpiry > 60) {
            errors.push('OTP expiry must be between 1 and 60 minutes.');
            isValid = false;
        }
        
        // Validate CAPTCHA settings
        const captchaEnabled = $('input[name="otp_auth_settings[captcha_enabled]"]').is(':checked');
        if (captchaEnabled) {
            const captchaType = $('select[name="otp_auth_settings[captcha_type]"]').val();
            
            if (captchaType === 'recaptcha') {
                const siteKey = $('input[name="otp_auth_settings[recaptcha_site_key]"]').val().trim();
                const secret = $('input[name="otp_auth_settings[recaptcha_secret]"]').val().trim();
                
                if (!siteKey || !secret) {
                    errors.push('Both reCAPTCHA Site Key and Secret Key are required when CAPTCHA is enabled.');
                    isValid = false;
                }
            } else if (captchaType === 'hcaptcha') {
                const siteKey = $('input[name="otp_auth_settings[hcaptcha_site_key]"]').val().trim();
                const secret = $('input[name="otp_auth_settings[hcaptcha_secret]"]').val().trim();
                
                if (!siteKey || !secret) {
                    errors.push('Both hCaptcha Site Key and Secret Key are required when CAPTCHA is enabled.');
                    isValid = false;
                }
            }
        }
        
        // Validate custom redirect URL if selected
        const redirectType = $('#redirect_after_login').val();
        if (redirectType === 'custom') {
            const customUrl = $('#custom_redirect_url').val().trim();
            if (!customUrl || !isValidUrl(customUrl)) {
                errors.push('Custom Redirect URL must be a valid URL when "Custom URL" redirect option is selected.');
                isValid = false;
            }
        }
        
        // Show all errors if validation fails
        if (!isValid) {
            const errorMessage = 'Please fix the following errors before saving:\n\n' + errors.map((error, index) => (index + 1) + '. ' + error).join('\n');
            alert(errorMessage);
            e.preventDefault();
            return false;
        }
    });
    
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (e) {
            return false;
        }
    }
    
    // Add visual indicators for required fields
    $('input[name="otp_auth_settings[email_from_email]"]').attr('required', true);
    
    // Style invalid email field
    $('<style>.invalid-email { border-color: #d63638 !important; box-shadow: 0 0 2px rgba(214, 54, 56, 0.8) !important; }</style>').appendTo('head');

    // --- V1.2.6 Testing & Debugging Console Logic ---
    const $testConsole = $('#test_log_output');

    function logToConsole(message, type = 'info') {
        if ($testConsole.length === 0) return;
        const time = new Date().toLocaleTimeString();
        let colorClass = 'info';
        if (type === 'error') colorClass = 'error';
        if (type === 'success') colorClass = 'success';
        
        $testConsole.append(`<span class="${colorClass}">[${time}] ${message}</span><br>`);
        $testConsole.scrollTop($testConsole[0].scrollHeight);
    }

    $('#btn_test_send').on('click', function(e) {
        e.preventDefault();
        const email = $('#test_email').val().trim();
        const actionType = $('#test_action').val();
        
        if (!email) {
            logToConsole('Error: Test Email Address is required.', 'error');
            return;
        }

        logToConsole(`>>> EXECUTING: send_otp (Email: ${email}, Action: ${actionType})`, 'info');
        
        const data = {
            action: 'send_otp',
            email: email,
            action_type: actionType,
            nonce: typeof otpTestNonce !== 'undefined' ? otpTestNonce : ''
        };

        const startTime = Date.now();

        $.ajax({
            url: typeof otpTestAjaxUrl !== 'undefined' ? otpTestAjaxUrl : ajaxurl,
            type: 'POST',
            data: data,
            timeout: 30000,
            success: function(response) {
                const duration = Date.now() - startTime;
                logToConsole(`<<< RESPONSE (${duration}ms):\n` + JSON.stringify(response, null, 2), response.success ? 'success' : 'error');
            },
            error: function(xhr, status, error) {
                const duration = Date.now() - startTime;
                logToConsole(`!!! AJAX ERROR (${duration}ms): Status: ${xhr.status}, Error: ${error}`, 'error');
                logToConsole(`Raw Response Text:\n${xhr.responseText}`, 'error');
            }
        });
    });

    $('#btn_test_verify').on('click', function(e) {
        e.preventDefault();
        const email = $('#test_email').val().trim();
        const otp = $('#test_otp').val().trim();
        const actionType = $('#test_action').val();

        if (!email || !otp) {
            logToConsole('Error: Both Email and OTP are required for verification.', 'error');
            return;
        }

        logToConsole(`>>> EXECUTING: verify_otp (Email: ${email}, OTP: ${otp}, Action: ${actionType})`, 'info');

        const data = {
            action: 'verify_otp',
            email: email,
            otp: otp,
            action_type: actionType,
            redirect_url: '',
            nonce: typeof otpTestNonce !== 'undefined' ? otpTestNonce : ''
        };

        const startTime = Date.now();

        $.ajax({
            url: typeof otpTestAjaxUrl !== 'undefined' ? otpTestAjaxUrl : ajaxurl,
            type: 'POST',
            data: data,
            timeout: 30000,
            success: function(response) {
                const duration = Date.now() - startTime;
                logToConsole(`<<< RESPONSE (${duration}ms):\n` + JSON.stringify(response, null, 2), response.success ? 'success' : 'error');
            },
            error: function(xhr, status, error) {
                const duration = Date.now() - startTime;
                logToConsole(`!!! AJAX ERROR (${duration}ms): Status: ${xhr.status}, Error: ${error}`, 'error');
                logToConsole(`Raw Response Text:\n${xhr.responseText}`, 'error');
            }
        });
    });
});
