<?php
/**
 * SignInWithOtp setup
 *
 * @package SignInWithOtp
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main SignInWithOtp Class.
 *
 * @class SignInWithOtp
 */
final class SignInWithOtp {
    /**
	 * SignInWithOtp version.
	 *
	 * @var string
	 */
	public string $version = '1.0.0';

	/**
	 * Identity variable.
	 *
	 * @var string
	 */
	public string $id = 'signin-with-otp-addon';

    /**
	 * The single instance of the class.
	 *
	 * @var SignInWithOtp
	 * @since 1.0.0
	 */
	protected static ?SignInWithOtp $_instance = null;

	/**
	 * Loader instance.
	 *
	 * @var SIOTP_Loader
	 */
	private SIOTP_Loader $loader;

	/**
	 * Admin handler instance.
	 *
	 * @var SIOTP_Admin_Functions
	 */
	private SIOTP_Admin_Functions $admin;

    /**
	 * Main SignInWithOtp Instance.
	 *
	 * Ensures only one instance of SignInWithOtp is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see SIOTP()
	 * @return SignInWithOtp - Main instance.
	 */
	public static function instance(): SignInWithOtp {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( );
		}
		return self::$_instance;
	}

    /**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', SIOTP_TEXTDOMAIN ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', SIOTP_TEXTDOMAIN ), '1.0.0' );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'signin', 'otp' ), true ) ) {
			return $this->$key();
		}
		return null;
	}

    /**
	 * Constructor for the class SignInWithOtp.
	 *
	 * This method is automatically called when an instance of the class is created.
	 * It can be used to initialize properties, set up dependencies, or perform any
	 * other setup tasks required by the class.
	 *
	 * @return void
	 */
	public function __construct() {
        $this->define_constants();
		$this->includes();
		$this->init_hooks();
		self::create_tables();

		do_action( $this->id . '_admin_loaded' );
	}   

    /**
	 * Defines the constants used throughout the application.
	 *
	 * This function is responsible for defining all necessary constants
	 * that will be used globally within the application. Constants help
	 * maintain consistency and manage configuration values effectively.
	 *
	 * Example:
	 * define( 'CONSTANT_NAME', 'value' );
	 *
	 * @return void
	 */
	private function define_constants(): void {
		$this->define( 'SIOTP_ABSPATH', dirname( SIOTP_PLUGIN_FILE ) . '/' );
		$this->define( 'SIOTP_PLUGIN_BASENAME', plugin_basename( SIOTP_PLUGIN_FILE ) );
		$this->define( 'SIOTP_VERSION', $this->version );
		$this->define( 'SIOTP_PLUGIN_ID', $this->id );
		$this->define( 'SIOTP_PLUGIN_NICE_NAME', 'siotp' );
		$this->define( 'SIOTP_PLUGIN_NAME', 'Sign in with OTP' );
		$this->define( 'SIOTP_PLUGIN_PATH', $this->plugin_path() );
		$this->define( 'SIOTP_PLUGIN_URL', $this->plugin_url() );
		$this->define( 'SIOTP_AJAX_URL', $this->ajax_url() );
		$this->define( 'SIOTP_NOTICE_MIN_PHP_VERSION', '7.4' );
		$this->define( 'SIOTP_NOTICE_MIN_WP_VERSION', '6.0' );
		$this->define( 'SIOTP_TEXTDOMAIN', $this->id );
		$this->define( 'SIOTP_DOC_URL', 'https://jineshpv.com/extensions/jxn-signin-with-otp-addon/' );
		$this->define( 'SIOTP_SUPPORT', 'https://jineshpv.com/support/' );
		$this->define( 'SIOTP_SCHEDULER_EXPIRED_OTPS', 'signin_with_otp_cleanup_expired_otps' );
		$this->define( 'SIOTP_SCHEDULER_UNBLOCK_USER', 'signin_with_otp_unblock_user_after_locked_time' );
	}

    /**
	 * Includes necessary files or dependencies for the functionality.
	 *
	 * This method is responsible for including or requiring all the necessary files,
	 * classes, or libraries needed by the application. It ensures that all dependencies
	 * are loaded and available for use within the application.
	 *
	 * @return void
	 */
	public function includes(): void {
		include_once SIOTP_ABSPATH . 'includes/class-signin-with-otp-loader.php';
		include_once SIOTP_ABSPATH . 'includes/class-signin-with-otp-form-helper.php';
		include_once SIOTP_ABSPATH . 'includes/class-signin-with-otp-admin-settings.php';
		include_once SIOTP_ABSPATH . 'includes/class-signin-with-otp-admin-functions.php';
		include_once SIOTP_ABSPATH . 'includes/class-signin-with-otp-user-locker.php';

		$this->loader = new SIOTP_Loader();
		$this->admin  = new SIOTP_Admin_Functions();
    }

    /**
	 * Initialize all the necessary WordPress hooks.
	 * 
	 * This private function sets up all the action and filter hooks required
	 * for the plugin to function properly. It ensures that the appropriate
	 * callbacks are hooked into WordPress at the correct times, facilitating
	 * the desired plugin behavior.
	 *
	 * Example hooks that might be initialized include:
	 * - Actions for registering custom post types or taxonomies
	 * - Filters for modifying content or settings
	 * - Shortcodes for rendering custom content
	 *
	 * Since this function is private, it is intended for internal use within 
	 * the class and should not be called directly from outside the class.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		$this->loader->add_action( 'plugins_loaded', $this, 'on_plugins_loaded', -1 );
		$this->loader->add_action( 'init', $this, 'init', 0 );
        $this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );

        if( 'yes' === self::get_option( 'enabled', '' ) ) {
	        $this->loader->add_action( 'login_enqueue_scripts', $this, 'enqueue_login_styles' );
	        $this->loader->add_action( 'login_enqueue_scripts', $this, 'enqueue_login_scripts', 99 );
        }

		$this->loader->add_action( 'activated_plugin', $this, 'activated_plugin' );
		$this->loader->add_action( 'deactivated_plugin', $this, 'deactivated_plugin' );
		$this->loader->add_action( 'admin_notices', $this, 'admin_notices' );

		// Plugin helps
		$this->loader->add_filter( 'plugin_action_links_' . SIOTP_PLUGIN_BASENAME, $this, 'plugin_action_links' );
		$this->loader->add_filter( 'plugin_row_meta', $this, 'plugin_row_meta', 10, 2 );
		$this->loader->add_filter( 'script_loader_tag', $this, 'add_async_defer_to_scripts', 10, 2 );
		$this->loader->add_filter( 'cron_schedules', $this, 'add_custom_cron_intervals' );

		// Ajax functions for OTP
		$this->loader->add_action( 'wp_ajax_generate_email_otp', $this, 'generate_email_otp' );
		$this->loader->add_action( 'wp_ajax_check_user_existence', $this, 'check_user_existence' );
		$this->loader->add_action( 'wp_ajax_nopriv_generate_email_otp', $this, 'generate_email_otp' );
		$this->loader->add_action( 'wp_ajax_nopriv_check_user_existence', $this, 'check_user_existence' );

		$this->loader->run();
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * This private static function is responsible for setting up the required tables
	 * in the database when the plugin or application is installed or activated.
	 * It typically includes the SQL statements needed to create each table with
	 * the appropriate columns, data types, and constraints.
	 *
	 * The function should handle any potential errors during the table creation
	 * process and ensure that tables are not created if they already exist.
	 * It is usually called during the plugin activation hook or setup routine.
	 *
	 * @return void
	 */
	private function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
		$table_name      = $wpdb->prefix . 'otp';
		$sql             = "
			CREATE TABLE IF NOT EXISTS {$table_name} (
				otp_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT(20) NOT NULL,
				otp_pass VARCHAR(255) NOT NULL,
				otp_tries TINYINT(1) NOT NULL DEFAULT 0,
				created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				status TINYINT(1) NOT NULL DEFAULT 1,
				user_status TINYINT(1) NOT NULL DEFAULT 1,
				PRIMARY KEY (otp_id),
				KEY user_id (user_id)
			) {$charset_collate};
		";

		dbDelta( $sql );
	}

    /**
	 * When WP has loaded all plugins, trigger the `signin_with_otp_addon_loaded` hook.
	 *
	 * This ensures `signin_with_otp_addon_loaded` is called only after all other plugins
	 * are loaded, to avoid issues caused by plugin directory naming changing
	 * the load order.
	 *
	 * @since 1.0.0
	 */
	public function on_plugins_loaded(): void {
		do_action( 'signin_with_otp_addon_loaded' );
	}

	/**
	 * Retrieves the URL of the plugin's directory.
	 *
	 * This static function returns the URL of the directory where the plugin is located.
	 * It is useful for referencing assets like scripts, stylesheets, images, etc., 
	 * that are stored within the plugin's directory.
	 *
	 * @return string The URL of the plugin directory.
	 */
	public static function plugin_url(): string {
		return untrailingslashit( plugins_url( '/', SIOTP_PLUGIN_FILE ) );
	}

	/**
	 * Retrieves the absolute path to the plugin directory.
	 *
	 * This static function returns the absolute filesystem path to the root directory of the plugin.
	 * It is useful for including files from within the plugin, referencing assets, or performing
	 * file operations. The path returned does not include a trailing slash.
	 *
	 * @return string The absolute path to the plugin directory.
	 */
	public static function plugin_path(): string {
		return untrailingslashit( plugin_dir_path( SIOTP_PLUGIN_FILE  ) );
	}

	/**
	 * Retrieve the path to the template file.
	 *
	 * This function returns the full path to the template file used by the theme.
	 * It is typically used to locate template files in a WordPress theme or plugin.
	 *
	 * @return string The path to the template file.
	 */

	public function template_path(): string {
		return apply_filters( 'siotp_template_path', 'siotp/' );
	}

	/**
	 * Generates and returns the URL for handling AJAX requests.
	 *
	 * This function constructs the AJAX URL that can be used in client-side scripts
	 * to send AJAX requests to the server. The URL is generated using WordPress's 
	 * built-in admin-ajax.php file, which handles all AJAX requests in WordPress.
	 *
	 * @return string The URL for AJAX requests.
	 */
	public function ajax_url(): string {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Retrieve the unique name for the plugin.
	 *
	 * This static method returns a unique identifier for the plugin, which can be 
	 * used for various purposes such as setting plugin-specific options, 
	 * registering hooks, or differentiating the plugin from others in the system.
	 *
	 * @return string The unique name of the plugin.
	 */

	public static function plugin_unique_name(): string {
		return 'siotp';
	}

	/**
	 * Adds custom action links to the plugin's entry in the plugins list.
	 *
	 * This function modifies the default action links (such as "Deactivate" and "Edit") 
	 * that appear below the plugin's name in the plugins list. It allows the addition 
	 * of custom links such as settings pages or documentation links.
	 *
	 * @param array $links An array of the default action links for the plugin.
	 * @return array The modified array of action links, including any custom links.
	 */
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=siotp-settings' ) . '" aria-label="' . esc_attr__( 'View settings', SIOTP_TEXTDOMAIN ) . '">' . esc_html__( 'Settings', SIOTP_TEXTDOMAIN ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Adds custom row meta links to the plugin list table on the Plugins page.
	 *
	 * This function is a callback for the 'plugin_row_meta' filter hook. It allows
	 * developers to add custom links to the plugin's row in the Plugins page.
	 *
	 * @param array  $links An array of existing plugin row meta links.
	 * @param string $file  The plugin file path relative to the plugins directory.
	 * @return array An array of modified plugin row meta links.
	 */

	public static function plugin_row_meta( $links, $file ) { 
		if ( SIOTP_PLUGIN_BASENAME === $file ) {
			$row_meta = array(
				'docs' => '<a href="' . esc_url( apply_filters( SIOTP_TEXTDOMAIN . '_docs_url', SIOTP_DOC_URL ) ) . '" aria-label="' . esc_attr__( 'View documentation', SIOTP_TEXTDOMAIN ) . '">' . esc_html__( 'Docs', SIOTP_TEXTDOMAIN ) . '</a>',
				'settings' => '<a href="' . admin_url( 'admin.php?page=siotp-settings' ) . '" aria-label="' . esc_attr__( 'View settings', SIOTP_TEXTDOMAIN ) . '">' . esc_html__( 'Settings', SIOTP_TEXTDOMAIN ) . '</a>',
			);

			return array_merge($links, $row_meta);
		}

		return (array) $links;
	}

    /**
	 * Init SignInWithOtp when WordPress Initialises.
	 */
	public function init(): void {
		do_action( 'before_signin_with_otp_addon_init' );
		self::create_cron_jobs();
		do_action( 'signin_with_otp_addon_init' );
	}

    /**
     * Check if a plugin is activated.
     * 
     * @param string $filename The filename of the plugin to check.
     * @return bool Whether the plugin is activated or not.
     */
	public function activated_plugin( $filename ) {
		$plugin_already_activated = get_transient( 'signin_with_otp_addon_plugin_activated' );
		if ( ! isset( $plugin_already_activated ) && 'jxn-signin-with-otp-addon/init.php' !== $plugin_already_activated ) {
			set_transient( 'signin_with_otp_addon_plugin_activated', $filename );
		
			$defaults = self::defaults();

			if( $defaults ) {
				foreach( $defaults as $option => $value ) {
					update_option( $option, $value );
				}
			}
		}
	}

    /**
	 * Deactivates the plugin identified by the given filename.
	 *
	 * @since 1.0.0
	 * @param string $filename The filename of the deactivated plugin.
	 */
	public function deactivated_plugin( $filename ) {
		$plugin_already_activated = get_transient( 'signin_with_otp_addon_plugin_activated' );
		if ( isset( $plugin_already_activated ) && 'jxn-signin-with-otp-addon/init.php' === $plugin_already_activated ) {
			global $wpdb;
			$wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE '%siotp%'" ); 
		}
	}

	/**
	 * Displays admin notices.
	 * 
	 * This function is called to display notices in the WordPress admin area.
	 * Notices can be informational, warning, or error messages displayed to users with appropriate permissions.
	 *
	 * @return void
	 */
	public static function admin_notices(): void {
        $recaptcha_site_key   = self::get_option( 'recaptcha_site_key', '' );
        $recaptcha_secret_key = self::get_option( 'recaptcha_secret_key', '' );

        if( empty( $recaptcha_site_key ) && empty( $recaptcha_secret_key ) && 'yes' === self::get_option( 'recaptcha_enabled', '' ) ) {
            echo '<div class="notice notice-error is-dismissible">'."\n";
            echo '    <p>'."\n";
            echo sprintf( __( 'reCaptcha has not been properly configured. <a href="%s">Click here</a> to configure.', SIOTP_TEXTDOMAIN ), 'admin.php?page=siotp-settings' );
            echo '    </p>'."\n";
            echo '</div>'."\n";
        }

        if ( ( isset( $_GET['status'] ) && 'settings-updated' === $_GET['status'] ) && ( isset( $_GET['page'] ) && 'siotp-settings' === $_GET['page'] ) ) {
        	$message = isset( $_GET['tab'] ) ? $_GET['tab'] : 'Settings';
	        $class   = 'notice notice-success settings-error is-dismissible';
			printf( '<div class="%1$s"><p>%2$s have been successfully saved.</p></div>', esc_attr( $class ), esc_html( SIOTP_Admin_Settings::format_display_name( $message ) ) );
		}
    }

    /**
	*
	* Enqueue the admin styles
	*
	* @return null
	**/
	public function enqueue_admin_styles(): void {
		$current_screen = get_current_screen();
		if( in_array( $current_screen->id, ['toplevel_page_siotp-dashboard', 'sign-in-with-otp_page_siotp-settings', 'sign-in-with-otp_page_siotp-admin-help'] ) ) {
        	wp_enqueue_style( 'admin-signin-with-otp', plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ), array(), time() );
        }
    }
    
    /**
	*
	* Enqueue the admin login styles
	*
	* @return null
	**/
	public function enqueue_login_styles(): void {
        wp_enqueue_style( 'admin-signin-with-otp', plugins_url( 'assets/css/admin-login.css', dirname( __FILE__ ) ), array(), time() );
    }

    /**
	*
	* Enqueue the admin script
	*
	* @return null
	**/
	public function enqueue_login_scripts(): void {
		$current_screen = get_current_screen();
		$params = array(
			'signinText'                 => self::get_option( 'signin_text', 'Sign in with OTP' ),
			'notReceivedText'            => self::get_option( 'not_received_text', 'Not Received OTP?' ),
			'regenerateText'             => self::get_option( 'regenerate_text', 'Re-Generate OTP' ),
			'captchaText'                => self::get_option( 'captcha_text', 'Enter Captcha to send OTP' ),
			'otpText'                    => self::get_option( 'otp_text', 'Enter One Time Password (OTP)' ),
			'backText'                   => self::get_option( 'back_button_text', 'Back' ),
			'otpValidityTime'            => self::get_option( 'otp_validity_time', '5' ),
			'maximumNoOfRequest'         => self::get_option( 'maximum_no_of_request', '5' ),
			'maximumFailedLoginAttempts' => self::get_option( 'maximum_failed_login_attempts', '2' ),
			'blockTime'                  => self::get_option( 'block_time', '5' ),
			'ajaxUrl'                    => $this->ajax_url(),
			'siteName'                   => get_bloginfo( 'name' ),
			'siteLogo'                   => self::get_option( 'login_icon', plugins_url( 'assets/images/siwo.png', dirname( __FILE__ ) ) ),
			'greCaptcha'                 => array(
				'enabled'   => self::get_option( 'recaptcha_enabled', '' ),
				'siteKey'   => self::get_option( 'recaptcha_site_key', '' ),
				'secretKey' => self::get_option( 'recaptcha_secret_key', '' )
			),
			'isAdminSettings'            => $current_screen && ( 'sign-in-with-otp_page_siotp-settings' === $current_screen->id ) ? 'yes' : 'no'
		);	

		if ( ! wp_script_is( 'admin-signin-with-otp-recaptcha-async', 'enqueued' ) && 'yes' === self::get_option( 'recaptcha_enabled', '' ) ) {
			wp_enqueue_script( 'admin-signin-with-otp-recaptcha-async', 'https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit', array(), null );
		}

		if ( ! wp_script_is( 'admin-signin-with-otp', 'enqueued' ) ) {
			wp_enqueue_script( 'admin-signin-with-otp', plugins_url( 'assets/js/admin.js', dirname( __FILE__ ) ), array( 'jquery' ), time(), true );
			wp_localize_script( 'admin-signin-with-otp', 'signInWithOtp', $params );
		}
    }

    /**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( string $name, $value ): void {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Return option value from DB.
	 *
	 * @param string $name  Option name.
	 * @param string $default Default value.
	 *
	 * @return string $default Option value.
	 */
	public static function get_option( string $name, $default = null ) {
		return get_option( 'siotp_' . $name ) ? get_option( 'siotp_' . $name ) : $default;
	}

	/**
	 * Default values of plugin
	 *
	 * @return array
	 * @version 1.0
	 * @return array
	 */

	public static function opts() {
		$opts     = array();
		$defaults = self::defaults();
		$name     = self::plugin_unique_name();

		if ( ! empty( self::get_option( 'email_message', '' ) ) ) {
			$opts[$name . '_email_message'] = self::get_option( 'email_message', '' );
		}

		return apply_filters( 'siotp_opts', wp_parse_args( $opts, $defaults ) );
	}

	/**
	 * Returns the default settings or configurations.
	 *
	 * This function returns the default settings or configurations 
	 * for the application. It is static and can be accessed without 
	 * creating an instance of the class.
	 *
	 * @return array Default settings or configurations.
	 */
	public static function defaults() {
		$name = self::plugin_unique_name();

		return apply_filters( 'siotp_defaults_opts', 
			array(
				$name . '_enabled'                 => 'no',
				$name . '_login_primary_color'     => '#ffffff',
				$name . '_login_secondary_color'   => '#e3867d',
				$name . '_login_button_color'      => '#e3867d',
				$name . '_login_button_text_color' => '#ffffff',
				$name . '_otp_button_color'        => '#144955',
				$name . '_otp_button_text_color'   => '#ffffff',

				$name . '_signin_text'       => 'Sign in with OTP',
				$name . '_not_received_text' => 'Not Received OTP?',
				$name . '_regenerate_text'   => 'Re-Generate OTP',
				$name . '_captcha_text'      => 'Enter Captcha to send OTP',
				$name . '_back_button_text'  => 'Back',
				$name . '_otp_text'          => 'Enter One Time Password (OTP)',
				$name . '_login_icon'        => plugins_url( 'assets/images/siwo.png', dirname( __FILE__ ) ),

				$name . '_otp_validity_time'             => '5',
				$name . '_maximum_no_of_request'         => '5',
				$name . '_maximum_failed_login_attempts' => '2',
				$name . '_block_time'                    => '5',

				$name . '_recaptcha_enabled'    => 'no',
				$name . '_recaptcha_site_key'   => '',
				$name . '_recaptcha_secret_key' => '',

				$name . '_email_from_name'             => get_bloginfo( 'name' ),
				$name . '_email_from_address'          => get_bloginfo( 'admin_email' ),
				$name . '_email_header_image'          => plugins_url( 'assets/images/siwo.png', dirname( __FILE__ ) ),
				$name . '_email_footer_text'           => '&copy;' . date( 'Y' ) . ' ' . get_bloginfo( 'name' ) . ', All rights reserved.',
				$name . '_email_base_color'            => '#ed8787',
				$name . '_email_background_color'      => '#f7f7f7',
				$name . '_email_body_background_color' => '#ffffff',
				$name . '_email_text_color'            => '#3c3c3c',
			)
		);
	}

	/**
	 * Checks the existence of a user.
	 *
	 * This function is responsible for verifying whether a user exists.
	 * It performs a check against the user database or external authentication system.
	 * 
	 * @return boolean True if the user exists, false otherwise.
	 */

	public function check_user_existence(): void {
		$this->admin->check_user_existence();
	}

	/**
	 * Function to generate an email OTP (One-Time Password).
	 * This function is used to generate a unique OTP that can be sent via email for verification purposes.
	 *
	 * @return string The generated OTP..
	 */
	public function generate_email_otp(): void {
		$this->admin->generate_email_otp_if_user_exists();
	}

	/**
	* Add async or defer attributes to script enqueues
	* @author JWC Extensions
	* @param  String  $tag     The original enqueued <script src="...> tag
	* @param  String  $handle  The registered unique name of the script
	* @return String  $tag     The modified <script async|defer src="...> tag
	*/
	public function add_async_defer_to_scripts( string $tag, string $handle ): string {
		if ( str_contains( $handle, 'async' ) ) {
			return str_replace( '<script ', '<script async ', $tag );
		}
		if ( str_contains( $handle, 'defer' ) ) {
			return str_replace( '<script ', '<script defer ', $tag );
		}
		return $tag;
	} 

	/**
	* Adds a custom cron interval to the list of schedules.
	* 
	* This function is used to define custom intervals for scheduling cron jobs.
	* 
	* @author JWC Extensions
	* @param array $schedules An array of existing cron schedules.
	* @return array An updated array of cron schedules including the custom interval.
	*/
	public function add_custom_cron_intervals( array $schedules ): array {
	    $schedules['every_minute'] = array(
	        'interval' => 60,
	        'display'  => esc_html__( 'Every Minutes' ), 
	    );

	    $schedules['every_' . get_option( 'siotp_block_time' ) . '_minute'] = array(
	        'interval' => absint( get_option( 'siotp_block_time' ) ) * 60,
	        'display'  => sprintf( 'Every %s Minutes', get_option( 'siotp_block_time' ) ), 
	    );

	    return $schedules;
	}

	/**
	 * Creates cron jobs.
	 * 
	 * This function is responsible for creating cron jobs. It may include
	 * scheduling recurring tasks, such as automated data backups, database maintenance,
	 * or any other periodic tasks required by the application.
	 * 
	 * @access private
	 */
	private static function create_cron_jobs(): void {
		wp_clear_scheduled_hook( SIOTP_SCHEDULER_EXPIRED_OTPS );
		wp_schedule_event( time(), 'every_minute', SIOTP_SCHEDULER_EXPIRED_OTPS );
	}
	/**
	 * Checks if the current device is a mobile device.
	 *
	 * @return bool True if the device is mobile, false otherwise.
	 */
	public static function is_mobile_device(): bool {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$user_agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );

		$mobile_agents = array(
			'android', 'webos', 'iphone', 'ipad', 'ipod', 'blackberry', 'iemobile', 'opera mini', 'windows phone', 'mobile', 'tablet'
		);

		foreach ( $mobile_agents as $device ) {
			if ( strpos( $user_agent, $device ) !== false ) {
				return true;
			}
		}

		return false;
	}
}
