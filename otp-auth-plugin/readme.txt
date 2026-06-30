=== OTP Authentication Plugin ===
Contributors: yourname
Tags: authentication, login, otp, email, security
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive OTP-based authentication system with email verification, modern design, and CAPTCHA support.

== Description ==

The OTP Authentication Plugin provides a secure, modern way to handle user authentication on your WordPress site. Instead of traditional passwords, users authenticate using One-Time Passwords (OTPs) sent to their email addresses.

= Key Features =

* **Email-based OTP Authentication**: Secure 6-digit verification codes sent via email
* **Unified Login/Signup Process**: Single interface for both new and existing users
* **Modern, Responsive Design**: Beautiful, mobile-friendly interface
* **CAPTCHA Integration**: Support for both reCAPTCHA v2 and hCaptcha
* **Smart Redirects**: Automatic redirection to the original page after authentication
* **Two Shortcodes Included**:
  * `[otp_auth_page]` - Main authentication page
  * `[otp_login_button]` - Smart login/logout button with modern icons
* **Spam Protection**: Built-in warnings about email delivery to spam folders
* **Session Management**: Secure handling of authentication states
* **Admin Configuration**: Easy-to-use settings panel

= How It Works =

1. User visits your site and attempts to access protected content
2. They are directed to the OTP authentication page
3. User enters their email address and selects Login or Signup
4. A 6-digit OTP is sent to their email (with spam folder warning)
5. User enters the OTP to complete authentication
6. They are automatically redirected back to their original destination

= Security Features =

* Time-limited OTP codes (configurable expiration)
* CAPTCHA protection against automated attacks
* Secure email delivery with proper headers
* Database cleanup of expired codes
* Session-based redirect management

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/otp-auth-plugin/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > OTP Auth to configure the plugin
4. Create a page and add the `[otp_auth_page]` shortcode
5. Optionally add `[otp_login_button]` shortcodes to your theme

== Frequently Asked Questions ==

= How do I set up the authentication page? =

Create a new page and add the shortcode `[otp_auth_page]`. This will display the complete authentication interface.

= Can I customize the email templates? =

The current version includes default email templates. Custom templates will be available in future updates.

= What CAPTCHA services are supported? =

The plugin supports both Google reCAPTCHA v2 and hCaptcha. You can enable/disable and configure them in the settings.

= How long are OTP codes valid? =

By default, OTP codes expire after 15 minutes, but this is configurable in the plugin settings.

= Will this work with my theme? =

Yes! The plugin is designed to work with any properly coded WordPress theme. The authentication interface uses modern, responsive CSS that adapts to most theme styles.

== Screenshots ==

1. Modern authentication interface with login/signup tabs
2. OTP verification step with resend functionality
3. Admin settings panel with CAPTCHA configuration
4. Smart login/logout button in action

== Changelog ==

= 1.0.0 =
* Initial release
* Email-based OTP authentication
* Modern responsive design
* CAPTCHA integration (reCAPTCHA v2 & hCaptcha)
* Two shortcodes for flexible integration
* Comprehensive admin settings
* Automatic redirect functionality
* Spam folder warnings
* Session management
* Database optimization

== Upgrade Notice ==

= 1.0.0 =
Initial release of the OTP Authentication Plugin.

== Support ==

For support, feature requests, or bug reports, please visit our support forum or contact us directly.

== Privacy Policy ==

This plugin stores email addresses and temporary OTP codes in your WordPress database. OTP codes are automatically deleted after expiration or use. No data is transmitted to external services except for CAPTCHA verification (if enabled) and email delivery through your configured mail system.
