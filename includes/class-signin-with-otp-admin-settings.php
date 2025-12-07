<?php
/**
 * SIOTP_Admin_Settings setup
 *
 * @package SIOTP_Admin_Settings
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class defines the admin settings for the SIOTP plugin.
 * It handles the configuration and management of settings within the WordPress admin dashboard.
 *
 * @class SIOTP_Admin_Settings
 */
class SIOTP_Admin_Settings {
	/**
	 * Version variable.
	 *
	 * @version 1.0.0
	 */
	protected $version;

	/**
	 * Loader variable.
	 *
	 * @version 1.0.0
	 */
	protected $plugin_slug;
	
	/**
	 * Plugin basename.
	 *
	 * @version 1.0.0
	 *
	 * @var string
	 */
	protected $plugin_basename = null;

	/**
	 * form_helper object.
	 *
	 * @version 1.0.0
	 *
	 * @var object
	 */
	protected $form_helper;

	/**
	 * Options array.
	 *
	 * @version 1.0.0
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Slug of the plugin screen.
	 *
	 * @version 1.0.0
	 *
	 * @var string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Plugin screen slug.
	 *
	 * @version    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hooks_suffix = array();

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
	 * SIOTP_Admin_Settings Constructor.
	 */
	public function __construct() {
		$this->admin_init();
	}

	/**
	 * Initialize admin-specific actions and filters.
	 *
	 * This function is hooked to the 'admin_init' action and is used to perform
	 * various initialization tasks needed for the admin area of the WordPress site.
	 */
	public function admin_init() {
		$this->loader      = new SIOTP_Loader();
		$this->form_helper = new SIOTP_Form_Helper();
		$this->options     = $this->defaults( false );

		// Load admin style sheet and JavaScript.
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts' );
		$this->loader->add_filter( 'woocommerce_screen_ids', $this, 'add_plugin_screen_ids' );
		$this->loader->add_action( 'admin_menu', $this, 'admin_menu' );
		$this->loader->add_action( 'login_head', $this, 'admin_login_styles' );
		$this->loader->run();
	}

	/**
	 * Register custom admin menu items in the WordPress dashboard.
	 *
	 * This function hooks into the 'admin_menu' action to add custom menu items
	 * to the WordPress admin sidebar. It allows developers to create new menu pages,
	 * sub-menu pages, or modify existing menu items to enhance the admin interface.
	 *
	 * @return void
	 */
	public function admin_menu() {
		$admin_menu = array(
			array( SIOTP_PLUGIN_NICE_NAME . '-dashboard', 'dashboard_callback', __( 'Dashboard', SIOTP_TEXTDOMAIN ) ),
			array( SIOTP_PLUGIN_NICE_NAME . '-settings', 'settings_callback', __( 'Settings', SIOTP_TEXTDOMAIN ) ),
			array( SIOTP_PLUGIN_NICE_NAME . '-admin-help', 'admin_help_callback', __( 'Support', SIOTP_TEXTDOMAIN ) ),
		);

		$admin_menu = apply_filters( SIOTP_PLUGIN_NICE_NAME . '_admin_menu', $admin_menu );
		$this->plugin_screen_hook_suffix = add_menu_page(
											__( SIOTP_PLUGIN_NAME . ' Settings', SIOTP_TEXTDOMAIN ), 
											__( SIOTP_PLUGIN_NAME, SIOTP_TEXTDOMAIN ), 
											'manage_options', 
											SIOTP_PLUGIN_NICE_NAME . '-dashboard', 
											array( $this, 'dashboard_callback' ), 
											SIOTP_PLUGIN_URL . '/assets/images/otp_icon.png', 60
										);
		$screen_id = 'sign-in-with-otp_page_';
		$this->plugin_screen_hooks_suffix = array(
								'toplevel_page_' . SIOTP_PLUGIN_NICE_NAME . '-dashboard', 
								$screen_id . SIOTP_PLUGIN_NICE_NAME . '-settings', 
								$screen_id . SIOTP_PLUGIN_NICE_NAME . '-admin-help'
							);

		foreach ( $admin_menu as $key => $value ) {
			add_submenu_page( SIOTP_PLUGIN_NICE_NAME . '-dashboard', $value[2], $value[2], 'manage_options', $value[0], array( $this, $value[1] ) );
		}
	}

	/**
	 * SIOTP screen ids.
	 *
	 * @param array $screen_ids SIOTP screen ids.
	 *
	 * @return array $screen_ids SIOTP screen ids.
	 */
	public function add_plugin_screen_ids( $screen_ids ) {
	    $screen_ids[] = 'sign-in-with-otp_page_siotp-settings';
	    return $screen_ids;
	}

	/**
	 * Enqueues custom styles for the WordPress admin login page.
	 *
	 * This function is hooked into the 'login_enqueue_scripts' action
	 * and is used to add custom CSS styles to the admin login page.
	 * Customize the CSS file path to point to your custom styles.
	 *
	 * @return void
	 */
	public function admin_login_styles() {
		$login_primary_color     = SignInWithOtp::get_option( 'login_primary_color', '#ffffff' );
		$login_secondary_color   = SignInWithOtp::get_option( 'login_secondary_color', '#e3867d' );
		$login_button_color      = SignInWithOtp::get_option( 'login_button_color', '#e3867d' );
		$login_button_text_color = SignInWithOtp::get_option( 'login_button_text_color', '#ffffff' );
		$otp_button_color        = SignInWithOtp::get_option( 'otp_button_color', '#144955' );
		$otp_button_text_color   = SignInWithOtp::get_option( 'otp_button_text_color', '#ffffff' );
		echo '<style type="text/css">
			:root {
			  --login-primary: ' . $login_primary_color . ';
			  --login-secondary: ' . $login_secondary_color . ';
			  --login-button-color: ' . $login_button_color . ';
			  --login-button-text-color: ' . $login_button_text_color . ';
			  --otp-button-color: ' . $otp_button_color . ';
			  --otp-button-text-color: ' . $otp_button_text_color . ';
			}
		</style>';
	}

	/**
	 * Callback function for handling settings.
	 *
	 * @version 1.0.0
	 */
	public function settings_callback() {
		if ( isset( $_GET['tab'] ) ) {
			$current_tab = wp_unslash( $_GET['tab'] );
		} else {
			$current_tab = '';
		}

		if( 'dashboard' === $current_tab && isset( $_GET['page'] ) && 'siotp-settings' === $_GET['page'] ) {
			$current_tab = 'general-settings';
		}

		$status_url = add_query_arg( array( 
			'page'   => 'siotp-settings', 
			'status' => 'settings-updated', 
			'tab'    => $current_tab,
		), admin_url( 'admin.php' ) );
		?>
		<div class="wrap siotp_settings">
			<form method="post" action="<?php echo esc_url( $status_url ); ?>">
				<?php do_action( 'siotp_settings_save', $this->options, $current_tab, '' );?>
				<?php $this->get_sidebar( $current_tab ); ?>
				<div class="right-column">
					<h1 class="<?php echo esc_attr( $current_tab ); ?>"><?php echo esc_html( self::format_display_name( $current_tab ) ); ?></h1>
					<div class="content-wrapper">
		            	<?php $this->form_helper->create_form( $this->options );?>
		            </div>
	            	<?php submit_button( __( 'Save Settings', SIOTP_TEXTDOMAIN ), 'primary', 'siotp-save-settings-btn' );?>
	            	<?php wp_nonce_field( 'siotp_save_settings', 'siotp-save-settings' );?>
            	</div>
            </form>
		</div>
		<?php
	}

	/**
	 * Outputs the sidebar navigation for the admin settings pages.
	 *
	 * Displays the plugin logo and a list of navigation tabs for different settings sections.
	 * Highlights the current tab based on the query parameters.
	 *
	 * @return void
	 */
	public function get_sidebar( $current_tab ) : void {
		$tabs = array (
			'dashboard'          => 'Dashboard',
			'general-settings'   => 'General',
			'label-settings'     => 'Labels',
			'otp-settings'       => 'OTP',
			'recaptcha-settings' => 'reCaptcha',
			'emails-settings'    => 'Emails',
			'support'            => 'Support'
		);
	
		?>
		<div class="left-column">
			<div class="logo-frame">
				<img src="<?php echo esc_url( plugins_url( 'assets/images/siwo.png', dirname( __FILE__ ) ) ); ?>" alt="" />
			</div>
			<ul class="siotp-nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) {
					if ( 'dashboard' === $slug ) {
						$page_slug = 'dashboard';
					} elseif( 'support' === $slug ) {
						$page_slug = 'admin-help';
					} else {
						$page_slug = 'settings';
					}

					$menu_url = add_query_arg( array( 
						'page'   => 'siotp-' . esc_attr( $page_slug ), 
						'tab'    => esc_attr( $slug ),
					), admin_url( 'admin.php' ) );

					echo '<li class="' . esc_attr( $slug ) . '"><a href="' . esc_url( $menu_url ) . '" class="siotp-nav ' . ( $current_tab === $slug ? 'siotp-nav-active' : '' ) . '">' . esc_html( $label ) . '</a></li>';
				} ?>
			</ul>
		</div>
		<?php
	}
	
	/**
	 * Converts a slug (e.g., 'general-settings') into a human-readable title (e.g., 'General Settings').
	 *
	 * @param string $slug The slug to convert.
	 * @return string The formatted display name.
	 */
	public static function format_display_name( $slug ) {    
		// Replace dashes and underscores with spaces
	    $name = str_replace( [ '-', '_' ], ' ', $slug );
	    $name = ucwords( strtolower( $name ) );

	    return $name;
	}

	/**
	 * This function handles the callback for the dashboard.
	 * It is responsible for processing any incoming requests related to the dashboard.
	 * 
	 * @version 1.0.0
	 */
	public function dashboard_callback() { 
		require_once SIOTP_ABSPATH . '/includes/class-signin-with-otp-dashboard-list-table.php';
		$tabs = array (
			'dashboard'          => 'Dashboard',
			'general-settings'   => 'General',
			'label-settings'     => 'Labels',
			'otp-settings'       => 'OTP',
			'recaptcha-settings' => 'reCaptcha',
			'emails-settings'    => 'Emails',
			'support'            => 'Support'
		);

		if ( isset( $_GET['tab'] ) ) {
			$current_tab = wp_unslash( $_GET['tab'] );
		} else {
			$current_tab = '';
		}

		$table = new SIOTP_Dashboard_List_Table();
		$table->prepare_items();
		?>
		<div class="wrap siotp_settings siotp-dashboard">
			<form method="post" action="">
				<?php $this->get_sidebar( $current_tab ); ?>
				<div class="right-column">
					<h1><?php esc_html_e( 'OTP Status', SIOTP_TEXTDOMAIN ); ?></h1>
					<div class="content-wrapper">
						<?php 
						$table->views();
						$table->display();
						?>
						<input type="hidden" name="page" value="siotp-dashboard" />				
		            </div>
            	</div>
            </form>
		</div>
		<?php
	}

	/**
	 * Callback function for handling the admin help functionality.
	 * This function is invoked when the admin help page is accessed.
	 * It contains the logic for rendering help content or performing other actions related to admin help.
	 * 
	 * @version 1.0.0
	 */
	public function admin_help_callback() {
		if ( isset( $_GET['tab'] ) ) {
			$current_tab = wp_unslash( $_GET['tab'] );
		} else {
			$current_tab = key( $tabs );
		}
		?>
		<div class="wrap siotp_settings">
			<form>
				<?php $this->get_sidebar( $current_tab ); ?>
				<div class="right-column">
					<h1><?php echo __( SIOTP_PLUGIN_NAME . ' Support', SIOTP_TEXTDOMAIN ); ?></h1>
					<div class="content-wrapper">
						<p style="font-size: 16px;">
							<?php echo sprintf( __( '<b>Support Form</b> - <a target="_blank" href="%s">%s</a>', SIOTP_TEXTDOMAIN ), SIOTP_SUPPORT, SIOTP_SUPPORT ); ?>
						</p>
						<p style="font-size: 15px;"><?php echo __( 'Thank you for using ' . SIOTP_PLUGIN_NAME . ' Addon.', SIOTP_TEXTDOMAIN ); ?></p>
		            </div>
            	</div>
            </form>
		</div>
		<?php
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @version 1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {
		if ( ! isset( $this->plugin_screen_hooks_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( in_array( $screen->id, $this->plugin_screen_hooks_suffix ) ) {
			wp_enqueue_style( 'admin-signin-with-otp', plugins_url( 'assets/css/admin.css', dirname( __FILE__ ) ), array(), time() );
		}
	}

	/**
	 * Enqueues the admin scripts for the plugin or theme.
	 *
	 * @version 1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {
		if ( ! isset( $this->plugin_screen_hooks_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( in_array( $screen->id, $this->plugin_screen_hooks_suffix ) ) {		
			$params = array(
				'signinText'                 => SignInWithOtp::get_option( 'signin_text', 'Sign in with OTP' ),
				'notReceivedText'            => SignInWithOtp::get_option( 'not_received_text', 'Not Received OTP?' ),
				'regenerateText'             => SignInWithOtp::get_option( 'regenerate_text', 'Re-Generate OTP' ),
				'otpValidityTime'            => SignInWithOtp::get_option( 'otp_validity_time', '5' ),
				'maximumNoOfRequest'         => SignInWithOtp::get_option( 'maximum_no_of_request', '5' ),
				'maximumFailedLoginAttempts' => SignInWithOtp::get_option( 'maximum_failed_login_attempts', '2' ),
				'blockTime'                  => SignInWithOtp::get_option( 'block_time', '5' ),
				'ajaxUrl'                    => SIOTP_AJAX_URL,
				'greCaptcha'                 => array(
					'siteKey'   => SignInWithOtp::get_option( 'recaptcha_site_key', '' ),
					'secretKey' => SignInWithOtp::get_option( 'recaptcha_secret_key', '' )
				),
				'isAdminSettings'            => $screen && ( 'sign-in-with-otp_page_siotp-settings' === $screen->id ) ? 'yes' : 'no'
			);

			if ( ! wp_script_is( 'admin-signin-with-otp', 'enqueued' ) ) {	
				wp_enqueue_media();		
				wp_enqueue_script( SIOTP_PLUGIN_BASENAME . '-admin-tiptip', plugins_url( 'assets/js/jquery.tipTip.min.js', dirname( __FILE__ ) ), array( 'jquery' ), '1.3.1' );
				wp_enqueue_script( 'admin-signin-with-otp', plugins_url( 'assets/js/admin.js', dirname( __FILE__ ) ), array( 'jquery', 'wp-util', 'iris' ), time() );
				wp_localize_script( 'admin-signin-with-otp', 'signInWithOtp', $params );
			}
		}
	}

	/**
	 * This function sets the default values for the specified parameters or properties.
	 * It is responsible for initializing the state or configuration of the system 
	 * when certain parameters or properties are not provided or explicitly set.
	 * 
	 * @return array $options Options array.
	 */
	public function defaults( $all = false ) {
		$current_tab = isset( $_GET['tab'] ) ? wp_unslash( $_GET['tab'] ) : 'general-settings';
		
		$options['general-settings'] = array(
			array(
				'title' => __( 'Global Configuration', SIOTP_TEXTDOMAIN ),
				'type'  => 'title',
				'desc'  => 'This section allows you to enable or disable core functionalities of the "Sign In with OTP" plugin. By default, all features are disabled upon installation, but you can use these toggles to tailor the plugin\'s behavior to your specific needs. Disabling an option here will effectively turn off the associated feature across your WordPress site, preventing it from being used for user authentication or registration.',
				'id'    => 'general_settings',
			),

			array(
				'title'    => __( 'Enable/Disable', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'Enable', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_enabled',
				'type'     => 'checkbox',
				'default'  => '0',
				'desc_tip' => true,
				'autoload' => false,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'general_settings',
			),

			array(
				'title' => __( 'Visual Configuration', SIOTP_TEXTDOMAIN ),
				'type'  => 'title',
				'desc'  => 'The "Visual Configuration" section of the Sign In with OTP WordPress plugin empowers you to customize the visual appearance of the OTP login form. Here, you can define colors, fonts, and button styles to seamlessly integrate the login experience with your website\'s existing design. This ensures a consistent and branded user interface for a professional and cohesive look.',
				'id'    => 'style_settings',
			),		

			array(
				'title'    => __( 'Primary color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The primary color for WP admin screen. Default %s.', SIOTP_TEXTDOMAIN ), '#ffffff' ),
				'id'       => 'siotp_login_primary_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#ffffff',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Secondary color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The secondary color for WP admin screen. Default %s.', SIOTP_TEXTDOMAIN ), '#e3867d' ),
				'id'       => 'siotp_login_secondary_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#e3867d',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Login button color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The login button color for WP admin screen. Default %s.', SIOTP_TEXTDOMAIN ), '#e3867d' ),
				'id'       => 'siotp_login_button_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#e3867d',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Login button text color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The login button text color for WP admin screen. Default %s.', SIOTP_TEXTDOMAIN ), '#ffffff' ),
				'id'       => 'siotp_login_button_text_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#ffffff',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Sign in with OTP button color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The sign in with OTP button color for WP admin screen. Default %s.', SIOTP_TEXTDOMAIN ), '#144955' ),
				'id'       => 'siotp_otp_button_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#144955',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Sign in with OTP button text color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The sign in with OTP button text color for WP admin screen. Default %s.', SIOTP_TEXTDOMAIN ), '#ffffff' ),
				'id'       => 'siotp_otp_button_text_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#ffffff',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'style_settings',
			)
		);

		$options['label-settings'] = array(

			array(
				'title' => __( 'Label Configuration', SIOTP_TEXTDOMAIN ),
				'type'  => 'title',
				'desc'  => 'The "Label Configuration" section in the Sign In with OTP WordPress plugin allows administrators to customize the text displayed on various elements of the login and registration forms. This includes fields like "Email/Phone Label," "OTP Label," "Login Button Text," and "Register Button Text." By modifying these labels, site owners can tailor the user experience to better fit their website\'s branding and language, ensuring clarity and consistency for their users.',
				'id'    => 'label_settings',
			),

			array(
				'title'    => __( 'Sign in text', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The text that appears on the login screen.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_signin_text',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => 'Sign in with OTP',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Not Received text', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The text that appears on the \'Enter the OTP\' screen.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_not_received_text',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => 'Not Received OTP?',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Re-Generate text', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The text that appears on the \'Enter the OTP\' screen.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_regenerate_text',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => 'Re-Generate OTP',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Captcha form text', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The text that appears on the Captcha form screen.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_captcha_text',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => 'Enter Captcha to send OTP',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Back button text', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The back button text that appears on the login form screen.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_back_button_text',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => 'Back',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'OTP text', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The text that appears on the Captcha form screen.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_otp_text',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => 'Enter One Time Password (OTP)',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'       => __( 'Icon in the Login screen', SIOTP_TEXTDOMAIN ),
				'desc'        => __( 'The logo icon that appears on the WP login screen.', SIOTP_TEXTDOMAIN ),
				'id'          => 'siotp_login_icon',
				'type'        => 'upload',
				'css'         => 'min-width:600px;',
				'placeholder' => __( 'N/A', SIOTP_TEXTDOMAIN ),
				'default'     => '',
				'btn_class'	  => 'button button-secondary',	
				'autoload'    => false,
				'desc_tip'    => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'label_settings',
			)
		);	

		$options['otp-settings'] = array(
			array(
				'title' => __( 'One-Time Password Configuration', SIOTP_TEXTDOMAIN ),
				'type'  => 'title',
				'desc'  => 'OTP Configuration is where administrators define the settings for how One-Time Passwords will be generated and delivered within the WordPress plugin. This section typically allows customization of parameters such as the OTP length, the validity period of the OTP, and the preferred method of delivery (e.g., email or SMS). Proper configuration ensures a secure and user-friendly experience for users signing in with an OTP.',
				'id'    => 'otp_settings',
			),

			array(
				'title'    => __( 'OTP validity time', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'OTP validity time in minutes.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_otp_validity_time',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => '5',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Maximum no of OTP request', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The maximum number of OTPs received per user. After that user is blocked for the time set in the Block Time below.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_maximum_no_of_request',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => '5',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Maximum failed login attempts', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The maximum number of allowed failed OTPs. Afer that user is blocked for the time set in the Block Time below.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_maximum_failed_login_attempts',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => '2',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Block Time', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'Block time in minutes.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_block_time',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => '5',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'otp_settings',
			)
		);

		$options['recaptcha-settings'] = array(
			array(
				'title' => __( 'Configure reCAPTCHA v2', SIOTP_TEXTDOMAIN ),
				'type'  => 'title',
				'desc'  => 'This section allows you to integrate reCAPTCHA v2 with your Sign In with OTP WordPress plugin, adding a layer of security to your login and registration forms. By enabling reCAPTCHA v2, you can protect your website from spam, bots, and brute-force attacks, ensuring only legitimate users can proceed. You will need to obtain your unique Site Key and Secret Key from Google reCAPTCHA to configure this feature effectively.',
				'id'    => 'recaptcha_settings',
			),

			array(
				'title'    => __( 'Enable/Disable', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'Enable', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_recaptcha_enabled',
				'type'     => 'checkbox',
				'css'      => 'min-width:600px;',
				'default'  => '0',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Site Key', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The Site Key that appears on the Google reCaptcha admin console.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_recaptcha_site_key',
				'type'     => 'key',
				'css'      => 'min-width:600px;',
				'default'  => '',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Secret Key', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The Secret Key that appears on the Google reCaptcha admin console.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_recaptcha_secret_key',
				'type'     => 'key',
				'css'      => 'min-width:600px;',
				'default'  => '',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'recaptcha_settings',
			)
		);

		$default_message = '<p style="font-family: \'Helvetica Neue\',sans-serif;font-size: 18px;line-height: 1.5;text-align: left;color:#000000;">We provide this convenient login option to streamline access without requiring a complex password.</p>
			<div style="margin: 30px 0;padding: 0;">
				<p style="font-family: \'Helvetica Neue\',sans-serif;font-size: 18px;line-height: 1.5;">
					<label style="font-weight: 600">Here is your login verification code:</label>
				</p>
				<p style="font-family: \'Helvetica Neue\',sans-serif;font-size: 30px;line-height: 30px;background: #f2f3f7;padding: 15px;margin: 0;">
					<label style="font-weight: 600">%% JXN_OTP %%</label>
				</p>
				<p style="font-family: \'Helvetica Neue\',sans-serif;font-size: 15px;line-height: 1.5;margin: 40px 0 0; color: #000000;">
					<label>Please make sure you never share this code with anyone.</label>
				</p>
				<p style="font-family: \'Helvetica Neue\',sans-serif;font-size: 15px;line-height: 1.5;margin: 0;color: #000000;">
					<label>Note: The code will expire in ' . SignInWithOtp::get_option( 'otp_validity_time', '5' ) . ' minutes.</label>
				</p>
			</div>
			<p style="font-family: \'Helvetica Neue\',sans-serif;font-size: 18px;line-height: 24px;font-weight: 600;text-align: left;color: #000000;">Have questions or trouble logging in?</p>
			<p style="font-family: \'Helvetica Neue\',sans-serif;font-size: 15px;line-height: 1.5;text-align: left;color: #000000;">Kindly contact the <a style="color:%s;" href="mailto:' . get_option( 'admin_email' ) . '">' . get_bloginfo( 'name' ) . '</a> administrator</p>';
		
		$options['emails-settings'] = array(
			array(
				'title' => __( 'Email Configuration Settings', SIOTP_TEXTDOMAIN ),
				'type'  => 'title',
				'desc'  => 'The "Sender Configuration Settings" section in the Sign In with OTP WordPress plugin allows you to define how one-time passwords are sent to your users. Here, you\'ll specify the method for delivering the OTP, such as email or SMS, and provide the necessary credentials or API keys for your chosen service. This ensures that your website can reliably and securely send OTPs to facilitate user login and verification processes.',
				'id'    => 'email_options',
			),

			array(
				'title'    => __( '"From" name', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'How the sender name appears in outgoing emails.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_email_from_name',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => esc_attr(get_bloginfo('name', 'display')),
				'autoload' => false,
				'desc_tip' => true,
			),
			

			array(
				'title'             => __( '"From" address', SIOTP_TEXTDOMAIN ),
				'desc'              => __( 'How the sender email appears in outgoing emails.', SIOTP_TEXTDOMAIN ),
				'id'                => 'siotp_email_from_address',
				'type'              => 'email',
				'custom_attributes' => array(
					'multiple' => 'multiple',
				),
				'css'               => 'min-width:600px;',
				'default'           => get_option('admin_email'),
				'autoload'          => false,
				'desc_tip'          => true,
			),

			array(
				'title'    => __( 'Subject', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'The subject line for the email containing the One-Time Password sent to users for authentication.', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_email_subject',
				'type'     => 'text',
				'css'      => 'min-width:600px;',
				'default'  => 'Your login verification code - ' . esc_attr( get_bloginfo( 'name', 'display' ) ),
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Message', SIOTP_TEXTDOMAIN ),
				'desc'     => __( 'Customize the content and body of the email that delivers the One-Time Password to your users for login', SIOTP_TEXTDOMAIN ),
				'id'       => 'siotp_email_message',
				'type'     => 'wp_editor',
				'default'  => $default_message,
				'rows'     => 20,
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'email_options',
			),

			array(
				'title' => __( 'Email Template', SIOTP_TEXTDOMAIN ),
				'type'  => 'title',
				/* translators: %s: Nonced email preview link */
				'desc'  => __( 'This section allows you to customize the email template sent to users for one-time password (OTP) verification. You can easily modify the subject, body, and sender information to match your website\'s branding and tone. This ensures a consistent and professional experience for your users during the sign-in process.', SIOTP_TEXTDOMAIN ),
				'id'    => 'email_template_options',
			),

			array(
				'title'       => __( 'Header image', SIOTP_TEXTDOMAIN ),
				'desc'        => __( 'URL to an image you want to show in the email header.', SIOTP_TEXTDOMAIN ),
				'id'          => 'siotp_email_header_image',
				'type'        => 'upload',
				'css'         => 'min-width:600px;',
				'placeholder' => __( 'N/A', SIOTP_TEXTDOMAIN ),
				'default'     => '',
				'btn_class'	  => 'button button-secondary',	
				'autoload'    => false,
				'desc_tip'    => true,
			),

			array(
				'title'    => __( 'Footer text', SIOTP_TEXTDOMAIN ),
				/* translators: %s: Available placeholders for use */
				'desc'     => __( 'The text to appear in the footer of all emails.', SIOTP_TEXTDOMAIN ) . ' ' . sprintf( __( 'Available placeholder: %s', SIOTP_TEXTDOMAIN ), '{site_title}' ),
				'id'       => 'siotp_email_footer_text',
				'css'      => 'width:600px; height: 75px;',
				'type'     => 'textarea',
				'default'  => '&copy; ' . date('Y') . ' {site_title}, All rights reserved',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Base color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The base color for email templates. Default %s.', SIOTP_TEXTDOMAIN ), '#ed8787' ),
				'id'       => 'siotp_email_base_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#ed8787',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Background color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The background color for email templates. Default %s.', SIOTP_TEXTDOMAIN ), '#f7f7f7' ),
				'id'       => 'siotp_email_background_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#f7f7f7',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Body background color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The main body background color. Default %s.', SIOTP_TEXTDOMAIN ), '#ffffff' ),
				'id'       => 'siotp_email_body_background_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#ffffff',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Body text color', SIOTP_TEXTDOMAIN ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The main body text color. Default %s.', SIOTP_TEXTDOMAIN ), '#3c3c3c' ),
				'id'       => 'siotp_email_text_color',
				'type'     => 'color',
				'css'      => 'width:7em; margin: 0;',
				'default'  => '#3c3c3c',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'type' => 'sectionend',
				'id'   => 'email_template_options',
			),
		);

		if ( $all === true ) {
			return $options;
		} else {
			return $options[ $current_tab ];
		}
	}
}

return new SIOTP_Admin_Settings();
