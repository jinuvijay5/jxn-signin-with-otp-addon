=== Sign in with OTP Addon ===
Contributors: jineshpv
Donate link: https://jineshpv.com/
Tags: otp login, two factor, authentication, security, admin login, sms, email otp, wp-admin
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: Apache License 2.0
License URI: http://www.apache.org/licenses/LICENSE-2.0

Sign in with OTP Addon allows WordPress administrators to log in securely using a One-Time Password (OTP) instead of a traditional password. Supports email-based or SMS-based OTP depending on your configuration.

== Description ==

**Sign in with OTP Addon** enhances your WordPress admin login security by allowing users to authenticate using a One-Time Password (OTP).  
Instead of entering a username and password, the user simply enters their registered email/phone and receives a secure OTP to log in.

This plugin is lightweight, secure, and specifically designed for **WordPress admin login (wp-login.php)**.

### 🔐 Key Features

- Login using **One-Time Password (OTP)**
- Option to send OTP via **email** or integrate SMS gateways
- Removes dependency on passwords for admin authentication
- Works with default WordPress login page
- Compatible with WordPress 6.x
- Secure OTP generation & validation
- Lightweight and developer-friendly
- Customizable flow and extendable hooks

### 📌 Use Cases

- Passwordless login for administrators
- Improved security for high-risk websites
- Client-friendly login method
- Prevent brute-force password attacks
- Organizations requiring simplified login workflows

---

== Installation ==

1. Download the plugin ZIP and upload it to `/wp-content/plugins/`
2. Extract the ZIP file
3. Activate **Sign in with OTP Addon** in **Plugins → Installed Plugins**
4. Configure your OTP settings (SMS API or Email OTP)

The plugin now automatically modifies your admin login flow.

---

== Frequently Asked Questions ==

= Does this replace the default WordPress login? =
Yes, the login page will prompt for OTP instead of a password.

= Does this work for frontend login forms? =
This version is designed exclusively for **admin login (wp-login.php)**.

= How is the OTP sent? =
OTP can be sent via:
- Default WordPress email  
- Custom SMS API (developer integration required)

= Can I customize the login UI? =
Yes, developers can override templates and use plugin hooks.

---

== Screenshots ==

1. Login page with OTP field  
2. OTP sent notification screen  
3. Successful login flow  

---

== Changelog ==

= 1.0.0 =
* Initial stable release  
* OTP authentication added  
* Email/SMS integration ready  
* Secure validation flow  

---

== Upgrade Notice ==

= 1.0.0 =
Initial release of passwordless OTP login for wp-admin.

---

== License ==

Apache License 2.0  
https://www.apache.org/licenses/LICENSE-2.0

© 2019–2026 Jinesh P.V  
https://jineshpv.com/
