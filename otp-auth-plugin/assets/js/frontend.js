jQuery(document).ready(function($) {
    const OTPAuth = {
        currentStep: 'email',
        currentAction: 'signin',
        isRedirecting: false,
        timerInterval: null,
        
        init: function() {
            this.ensureBodyAppend();
            this.bindEvents();
            this.initializeForm();
            
            // If the wrapper has data-auto-open (e.g. on the dedicated shortcode page), force it open immediately
            if ($('#otp-auth-container').data('auto-open') === true) {
                $('#otp-auth-container').addClass('is-active').show();
                $('body').addClass('otp-auth-overlay-active');
            }
            
            console.log('OTPAuth initialized (v1.2.6) - UI State & Chrome Redirect Fixes Active');
        },
        
        ensureBodyAppend: function() {
            const $container = $('#otp-auth-container');
            if ($container.length > 0) {
                if ($container.parent().prop('tagName') !== 'BODY') {
                    const $detached = $container.detach();
                    $('body').append($detached);
                }
                $container.css({
                    'z-index': '2147483647'
                });
            }
        },
        
        bindEvents: function() {
            // Triggering the overlay from the Global Header Button
            $(document).on('click', '.otp-login-trigger', function(e) {
                e.preventDefault();
                $('#otp-auth-container').hide().addClass('is-active').fadeIn(300);
                $('body').addClass('otp-auth-overlay-active');
            });

            // Close button functionality for overlay
            $(document).on('click', '#otp-auth-close', function(e) {
                e.preventDefault();
                $('#otp-auth-container').fadeOut(300, function() {
                    $(this).removeClass('is-active');
                    $('body').removeClass('otp-auth-overlay-active');
                });
            });

            // Tab switching
            $(document).on('click', '.otp-auth-tab', this.handleTabSwitch.bind(this));
            
            // Explicit button clicks
            $(document).on('click', '#send-otp-btn', this.handleSendClick.bind(this));
            $(document).on('click', '#verify-otp-btn', this.handleVerifyClick.bind(this));
            
            // Prevent actual form submission strictly
            $(document).on('submit', '#otp-auth-form', function(e) {
                e.preventDefault();
                return false;
            });

            // Handle Mobile Keyboard "Go" / "Enter" Button
            $(document).on('keypress', '#otp_email', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#send-otp-btn').trigger('click');
                }
            });

            $(document).on('keypress', '#otp_code', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#verify-otp-btn').trigger('click');
                }
            });
            
            // Back button
            $(document).on('click', '#back-to-email', this.goToEmailStep.bind(this));
            
            // Resend OTP
            $(document).on('click', '#resend-otp', this.resendOTP.bind(this));
            
            // OTP input formatting (Bulletproof Pasting)
            $(document).on('input', '#otp_code', this.formatOTPInput.bind(this));
        },
        
        initializeForm: function() {
            this.currentAction = $('#current_action').val() || 'signin';
            this.showStep('email');
            this.clearMessages();
            this.updateHeaderText();
            
            $('.otp-auth-tab').removeClass('active');
            $('.otp-auth-tab[data-tab=\"' + this.currentAction + '\"]').addClass('active');
        },
        
        updateHeaderText: function() {
            if (this.currentAction === 'signup') {
                $('#otp-dynamic-title').text('Create Account');
                $('#otp-dynamic-subtitle').text('Create a new account to continue');
            } else {
                $('#otp-dynamic-title').text('Welcome Back');
                $('#otp-dynamic-subtitle').text('Sign in to your account to continue');
            }
        },
        
        handleTabSwitch: function(e) {
            e.preventDefault();
            const $tab = $(e.currentTarget);
            const action = $tab.data('tab');
            
            if (action === this.currentAction) return;
            
            $('.otp-auth-tab').removeClass('active');
            $tab.addClass('active');
            
            this.currentAction = action;
            $('#current_action').val(action);
            
            this.updateHeaderText();
            this.goToEmailStep();
            this.clearMessages();
        },
        
        handleSendClick: function(e) {
            e.preventDefault();
            if (this.isProcessing()) return false;
            this.sendOTP();
        },
        
        handleVerifyClick: function(e) {
            e.preventDefault();
            if (this.isProcessing()) return false;
            this.verifyOTP();
        },
        
        sendOTP: function() {
            this.isRedirecting = false;
            const email = $('#otp_email').val().trim();
            
            if (!email || !this.isValidEmail(email)) {
                this.showMessage('[ERR_INVALID_EMAIL] Please enter a valid email address.', 'error');
                return;
            }
            
            if (typeof otpAuth === 'undefined') {
                this.showMessage('[ERR_MISSING_CONFIG] Configuration error. Please refresh the page.', 'error');
                return;
            }
            
            // FIX: Lock exactly the Send button, ignore steps
            this.setButtonLoading('#send-otp-btn', true);
            this.clearMessages();
            
            const data = {
                action: 'send_otp',
                email: email,
                action_type: this.currentAction,
                nonce: $('input[name=\"nonce\"]').val() || otpAuth.nonce
            };
            
            const captchaResponse = this.getCaptchaResponse();
            if (captchaResponse) {
                data.captcha_response = captchaResponse;
            }
            
            $.ajax({
                url: otpAuth.ajaxurl,
                type: 'POST',
                data: data,
                timeout: 30000,
                success: this.handleSendOTPSuccess.bind(this),
                error: this.handleAjaxError.bind(this),
                complete: function() {
                    // FIX: Unlock exactly the Send button, prevent race condition freezing Step 2
                    this.setButtonLoading('#send-otp-btn', false);
                }.bind(this)
            });
        },
        
        handleSendOTPSuccess: function(response) {
            if (typeof response === 'string') {
                try {
                    const jsonMatch = response.match(/\{.*\}/s);
                    response = jsonMatch ? JSON.parse(jsonMatch[0]) : JSON.parse(response);
                } catch (e) {
                    this.showMessage('[ERR_PARSE_ERROR] Server returned an invalid response. Please try again.', 'error');
                    this.resetCaptcha();
                    return;
                }
            }
            
            if (response && response.success) {
                const msg = (response.data && response.data.message) ? response.data.message : 'Success!';
                this.showMessage(msg, 'success');
                this.goToOTPStep();
                this.startResendTimer(); // Start 30s countdown
            } else {
                const msg = (response && response.data && response.data.message) ? response.data.message : '[ERR_SEND_FAIL] Error sending code.';
                this.showMessage(msg, 'error');
                this.resetCaptcha();
            }
        },
        
        startResendTimer: function() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            
            const $resendWrapper = $('.otp-auth-resend');
            $resendWrapper.html(`<p class=\"otp-timer-text\">Resend code in <span class=\"otp-timer-count\">30s</span></p>`);
            
            let timeLeft = 30;
            this.timerInterval = setInterval(() => {
                timeLeft--;
                $('.otp-timer-count').text(timeLeft + 's');
                
                if (timeLeft <= 0) {
                    clearInterval(this.timerInterval);
                    $resendWrapper.html(`<p>Didn't receive the code? <button type=\"button\" id=\"resend-otp\" class=\"otp-auth-link\">Resend Code</button></p>`);
                }
            }, 1000);
        },
        
        verifyOTP: function() {
            this.isRedirecting = false; 
            const email = $('#otp_email').val().trim();
            const otp = $('#otp_code').val().trim();
            
            if (!otp || otp.length !== 6) {
                this.showMessage('[ERR_INVALID_CODE] Please enter a valid 6-digit verification code.', 'error');
                return;
            }
            
            // FIX: Lock exactly the Verify button
            this.setButtonLoading('#verify-otp-btn', true);
            this.clearMessages();
            
            const data = {
                action: 'verify_otp',
                email: email,
                otp: otp,
                action_type: this.currentAction,
                redirect_url: window.location.href,
                nonce: $('input[name=\"nonce\"]').val() || otpAuth.nonce
            };
            
            $.ajax({
                url: otpAuth.ajaxurl,
                type: 'POST',
                data: data,
                timeout: 30000,
                success: this.handleVerifyOTPSuccess.bind(this),
                error: this.handleAjaxError.bind(this),
                complete: function() {
                    // FIX: Unlock exactly the Verify button, but ONLY if we aren't redirecting
                    if (!this.isRedirecting) {
                        this.setButtonLoading('#verify-otp-btn', false);
                    }
                }.bind(this)
            });
        },
        
        handleVerifyOTPSuccess: function(response) {
            if (typeof response === 'string') {
                try {
                    const jsonMatch = response.match(/\{.*\}/s);
                    response = jsonMatch ? JSON.parse(jsonMatch[0]) : JSON.parse(response);
                } catch (e) {
                    this.showMessage('[ERR_PARSE_ERROR] Server error during verification. Please try again.', 'error');
                    return;
                }
            }
            
            if (response && response.success) {
                const msg = (response.data && response.data.message) ? response.data.message : 'Success!';
                this.showMessage(msg, 'success');
                
                this.isRedirecting = true; 
                this.setButtonLoading('#verify-otp-btn', true); // Keep the UI button locked
                
                const redirectUrl = (response.data && response.data.redirect_url) ? response.data.redirect_url : window.location.href;
                
                // V1.2.6 AGGRESSIVE REDIRECT FIX FOR CHROME
                setTimeout(function() {
                    try {
                        window.location.assign(redirectUrl);
                    } catch(e) {
                        window.location.href = redirectUrl;
                    }
                    setTimeout(function() {
                        window.location.replace(redirectUrl);
                    }, 500);
                }, 400);
                
            } else {
                this.isRedirecting = false;
                const msg = (response && response.data && response.data.message) ? response.data.message : '[ERR_VERIFY_FAIL] Verification failed.';
                this.showMessage(msg, 'error');
            }
        },
        
        resendOTP: function(e) {
            e.preventDefault();
            this.sendOTP();
        },
        
        goToEmailStep: function() {
            this.currentStep = 'email';
            $('#step-email').show();
            $('#step-otp').hide();
            $('#otp_code').val('');
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.clearMessages();
        },
        
        goToOTPStep: function() {
            this.currentStep = 'otp';
            $('#step-email').hide();
            $('#step-otp').show();
            $('#otp_code').focus();
        },
        
        showStep: function(step) {
            $('.otp-auth-step').hide();
            $('#step-' + step).show();
            this.currentStep = step;
        },
        
        setButtonLoading: function(btnSelector, loading) {
            const $btn = $(btnSelector);
            if (loading) {
                $btn.prop('disabled', true).addClass('loading');
            } else {
                $btn.prop('disabled', false).removeClass('loading');
            }
        },
        
        isProcessing: function() {
            return $('#send-otp-btn').hasClass('loading') || $('#verify-otp-btn').hasClass('loading');
        },
        
        showMessage: function(message, type) {
            const $container = $('#otp-auth-messages');
            const $message = $('<div class=\"otp-auth-message ' + type + '\">' + message + '</div>');
            
            $container.empty().append($message);
            
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut();
                }, 5000);
            }
        },
        
        clearMessages: function() {
            $('#otp-auth-messages').empty();
        },
        
        formatOTPInput: function(e) {
            // V1.2.6 Mobile Paste Fix: Strip absolutely all non-numeric characters (including invisible iOS spaces)
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value.length > 6) {
                value = value.substring(0, 6);
            }
            e.target.value = value;
        },
        
        isValidEmail: function(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        getCaptchaResponse: function() {
            if (typeof grecaptcha !== 'undefined' && $('.g-recaptcha').length > 0) {
                return grecaptcha.getResponse();
            }
            if (typeof hcaptcha !== 'undefined' && $('.h-captcha').length > 0) {
                return hcaptcha.getResponse();
            }
            return null;
        },
        
        resetCaptcha: function() {
            if (typeof grecaptcha !== 'undefined' && $('.g-recaptcha').length > 0) {
                grecaptcha.reset();
            }
            if (typeof hcaptcha !== 'undefined' && $('.h-captcha').length > 0) {
                hcaptcha.reset();
            }
        },
        
        handleAjaxError: function(xhr, status, error) {
            if (status === 'abort') return; 
            
            this.isRedirecting = false;
            
            let errorCode = 'ERR_UNKNOWN';
            let errorMessage = 'An error occurred. Please try again.';
            
            if (xhr.status === 0) {
                errorCode = 'ERR_NETWORK';
                errorMessage = 'Network error or request blocked. Please check your internet connection.';
            } else if (xhr.responseText === '-1' || xhr.responseText === '0') {
                errorCode = 'ERR_TOKEN_EXPIRED';
                errorMessage = 'Security token expired. Please refresh the page and try again.';
            } else if (xhr.status >= 500) {
                errorCode = 'ERR_SERVER_500';
                errorMessage = 'Server error. Please try again later.';
            } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                errorCode = 'ERR_API';
                errorMessage = xhr.responseJSON.data.message;
            } else if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.data && response.data.message) {
                        errorCode = 'ERR_API';
                        errorMessage = response.data.message;
                    }
                } catch (e) {
                    errorCode = 'ERR_PARSE';
                }
            }
            
            this.showMessage(`[${errorCode}] ${errorMessage}`, 'error');
            this.resetCaptcha();
        }
    };
    
    if ($('#otp-auth-container').length > 0) {
        OTPAuth.init();
    }
    
    setTimeout(function() {
        if ($('#otp-auth-container').length > 0) {
            OTPAuth.ensureBodyAppend();
            if (typeof window.OTPAuth === 'undefined') {
                OTPAuth.init();
            }
        }
    }, 800);
    
    window.OTPAuth = OTPAuth;
});
