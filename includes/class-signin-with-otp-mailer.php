<?php

/**
 * All mail functions will go in here
 *
 * @version    1.0.0.
 *
 * @author     JWC Extensions
 */
class SIOTP_Mailer {
	/**
	 * Option array.
	 *
	 * @version 1.0.0
	 *
	 * @var array
	 */
	private $opts;

	/**
	 * The ID of this plugin.
	 *
	 * @version 1.0.0
	 * @access private
	 * @var string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @version 1.0.0
	 * @access private
	 * @var string $version The current version of this plugin.
	 */
	private $version;

	/**
	 *
	 * @var string $template Cached version of the template
	 */
	private $template = false;

	/**
	 * Constructor method for the class.
	 * 
	 * This method is called automatically when an instance of the class is created.
	 * It initializes the object and sets up any necessary configurations or dependencies.
	 */
	public function __construct() {

		$this->plugin_name = SIOTP_PLUGIN_NAME;
		$this->version     = SIOTP_VERSION;
		$this->opts        = SignInWithOtp::opts();
	}

	/**
	 * Send html emails instead of text plain
	 * @version 1.0.0
	 * @return string
	 */
	public function set_content_type() {
		return $content_type = 'text/html';
	}

	/**
	 * Send a test email to admin email
	 * 
	 * @version 1.0.0
	 * @param      object    $user       User object.
	 */
	public function send_email( $user ) {
		ob_start();
		$email_subject = SignInWithOtp::get_option( 'email_subject' );
		$template_file = SIOTP_PLUGIN_PATH . '/templates/default.php';
		$headers       = "From: " . SignInWithOtp::get_option( 'email_from_name' ) . "<" . SignInWithOtp::get_option( 'email_from_address' ) . ">\r\n";
		$headers       .= "Reply-To: " . SignInWithOtp::get_option( 'email_from_address' ) . "\r\n";
		$subject       = isset( $email_subject ) ? $email_subject : sprintf( __( 'Your login verification code - %s', SIOTP_TEXTDOMAIN ), get_bloginfo( 'name' ) );
		
		$this->include( $template_file, array( 'user_info' => $user ) );

		$message = ob_get_contents();
		$message = str_replace( '{site_title}', get_bloginfo( 'name' ), $message );
		ob_end_clean();

		wp_mail( $user->user_email, $subject, $message, $headers );
	}

	/**
	 * Add template to plain mail
	 * @param $email string Mail to be send
	 * @version 1.0.0
	 * @return string
	 */
	private function add_template( $email ) {
		if ( $this->template ) {
			return str_replace( '%%MAILCONTENT%%', $email, $this->template );
		}

		do_action( 'siotp_add_template', $email, $this );

		$template_file = apply_filters( 'siotp_customizer_template', SIOTP_PLUGIN_PATH . '/templates/default.php' );
		ob_start();
		$this->include( $template_file, array( 'user_info' => $user ) );
		$this->template = ob_get_contents();
		ob_end_clean();
		return apply_filters( 'siotp_return_template', str_replace( '%%MAILCONTENT%%', $email, $this->template ) );
	}

	/**
	 * Include a custom template with variables
	 * @param $filepath 	string		Template name
	 * @param $variables 	array		Variable array
	 * @param $print 		boolean		True/False
	 * @version 1.0.0
	 * @return string
	 */
	public function include( $filepath, $variables = array() ){
	    $output = NULL;

	    if( file_exists( $filepath ) ){
	        extract( $variables );
	        ob_start();
	        include_once $filepath;
	        $output = ob_get_contents();
	    }

	    return $output;
	}

	/**
	 * Replace placeholders
	 *
	 * @param $email string Mail to be send
	 *
	 * @param $user_email string Get destination email
	 * Passed to the filters in case users needs something
	 *
	 * @return string
	 */
	private function replace_placeholders( $email, $user_email = '' ) {
		$to_replace = apply_filters( 'siotp_placeholders', array(
			'%%BLOG_URL%%'         => get_option( 'siteurl' ),
			'%%HOME_URL%%'         => get_option( 'home' ),
			'%%BLOG_NAME%%'        => get_option( 'blogname' ),
			'%%BLOG_DESCRIPTION%%' => get_option( 'blogdescription' ),
			'%%ADMIN_EMAIL%%'      => get_option( 'admin_email' ),
			'%%DATE%%'             => date_i18n( get_option( 'date_format' ) ),
			'%%TIME%%'             => date_i18n( get_option( 'time_format' ) ),
			'%%USER_EMAIL%%'       => $user_email,
		), $user_email );

		if( $to_replace ) {
			foreach ( $to_replace as $placeholder => $var ) {
				if ( is_array( $var ) ) {
					do {
						$var = reset( $var );
					} while ( is_array( $var ) );
				}
				
				$email = str_replace( $placeholder, $var, $email );
			}
		}

		return $email;
	}

	/**
	 * Set the "From" email address.
	 *
	 * This function sets the email address from which emails will be sent.
	 *
	 * @param string $email The email address to set as the "From" address.
	 * @return void
	 */
	public function set_from_email( $email ) {
		if ( empty( $this->opts['siotp_email_from_address'] ) ) {
			return $email;
		}

		return $this->opts['siotp_email_from_address'];
	}

	/**
	 * Sets the "From" name for the object.
	 * 
	 * This method sets the "From" name attribute for the object to the specified value.
	 *
	 * @param string $name The name to set as the "From" name.
	 */
	public function set_from_name( $name ) {
		if ( empty( $this->opts['siotp_email_from_name'] ) ) {
			return $name;
		}

		return $this->opts['siotp_email_from_name'];
	}

	/**
	 * Cleans the retrieve password message before sending.
	 *
	 * This function takes a message string as input and performs any necessary
	 * cleaning or sanitization operations before the message is sent out as part
	 * of the retrieve password process. It allows for modifications to the message
	 * content to ensure compliance with security policies or formatting standards.
	 *
	 * @param string $message The message to be cleaned.
	 * @return string The cleaned message.
	 */
	public function clean_retrieve_password( $message ) {
		return make_clickable( preg_replace( '@<(http[^> ]+)>@', '$1', $message ) );
	}
}
