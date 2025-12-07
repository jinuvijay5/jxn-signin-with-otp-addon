<?php
/**
 * SIOTP_Admin_Functions setup
 *
 * @package SIOTP_Admin_Functions
 * @version   1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Represents the SIOTP_Admin_Functions class.
 * This class encapsulates functions related to administration tasks.
 * It provides functionality for managing and performing administrative operations.
 */
class SIOTP_Admin_Functions {
	/**
	 * Mailer instance.
	 *
	 * @var mailer
	 */
	private $mailer;

	/**
	 * Helper instance.
	 *
	 * @var helper
	 */
	private $helper;

	/**
	 * Loader instance.
	 *
	 * @var SIOTP_Loader
	 */
	private SIOTP_Loader $loader;

	/**
	 * SIOTP_Admin_Functions Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		include_once SIOTP_ABSPATH . 'includes/class-signin-with-otp-mailer.php';		
		$this->mailer = new SIOTP_Mailer();
    }

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		$this->loader = new SIOTP_Loader();
		$this->helper = new SIOTP_Form_Helper();

		// For Mailer
		$this->loader->add_action( 'wp_mail_content_type', $this->mailer, 'set_content_type', 100 );
		$this->loader->add_action( 'wp_mail_from_name', $this->mailer, 'set_from_name' );
		$this->loader->add_action( 'wp_mail_from', $this->mailer, 'set_from_email' );
		$this->loader->add_action( 'admin_notices', $this, 'display_admin_notices' );

		// Ajax functions
		$ajax_params = array( 
						'authenticate_user'             => 'authenticate_user',
						'disable_otp_after_validity'    => 'disable_otp_after_validity',
						'cleanup_expired_otp_scheduler' => 'custom_event_cleanup_expired_otp',
						'call_unlock_user_scheduler'    => 'call_unlock_user_scheduler'
					);

		if( $ajax_params ) {
			foreach( $ajax_params as $ajax_slug => $ajax_function ){
				$this->loader->add_action( 'wp_ajax_' . $ajax_slug, $this, $ajax_function );
				$this->loader->add_action( 'wp_ajax_nopriv_' . $ajax_slug, $this, $ajax_function );
			}
		}

		// For Cron Jobs
		$this->loader->add_action( 'signin_with_otp_cleanup_expired_otps', $this, 'cleanup_expired_otps' );
		$this->loader->add_action( 'signin_with_otp_unblock_user_after_locked_time', $this, 'unblock_user_after_locked_time' );

		$this->loader->run();
	}
	
	/**
	 * Defines a function to display a success notice in the WordPress admin area.
	 * 
	 * This function is intended to be used as a callback for WordPress action hooks
	 * to display a success notice in the WordPress admin area. The notice will be
	 * displayed using the WordPress `admin_notices` hook.
	 * 
	 * @return void
	 */
	public function display_admin_notices() {
		$custom_notices = get_transient( 'siotp_custom_notices' );

		if( $custom_notices ) {
		    ?>
		    <div class="notice notice-<?php echo esc_attr( $custom_notices['type'] );?> is-dismissible">
		        <p><?php echo sprintf( __( "%s", SIOTP_TEXTDOMAIN ), $custom_notices['message'] ); ?></p>
		    </div>
		    <?php
		    delete_transient( 'siotp_custom_notices' );
		}
	}

	/**
	 * Check the existence of a user.
	 * 
	 * This function is responsible for verifying whether a user exists in the system.
	 * It can be used to perform user validation or authentication checks.
	 * 
	 * @return bool Returns true if the user exists, false otherwise.
	 */
	public function check_user_existence() {
		$username = isset( $_POST['log'] ) ? wp_unslash( $_POST['log'] ) : null;
		$user     = get_user_by( 'login', $username ); 

		if ( ! $user && ! is_wp_error( $user ) ) {	
		    $message = array( 
				'status'      => 'error', 
				'messageHtml' => sprintf( '<div id="login_error"><strong>Error</strong>: The username <b>%s</b> is not registered on this site. If you are unsure of your username, try your email address instead.</div>', $username ) 
			);
		} else {
			$locked = get_user_meta( $user->ID, 'siotp_user_locked', true );

			if ( ! empty( $locked ) ) {
				$message = array( 
					'status'      => 'error', 
					'messageHtml' => sprintf( '<div id="login_error"><strong>ERROR</strong>: This user account is locked for security reasons. Please use <b>Lost your password</b> option to unlock it.</div>' ),
					'user_locked' => true 
				);
			} else {
			    $message = array( 
					'status'      => 'success', 
					'messageHtml' => sprintf( 'The username <b>%s</b> is registered on this site.', $username )
				);
			}
		}

	    wp_send_json( $message );
		wp_die();
	}

	/**
	 * Create email otp if the user exists.
	 *
	 * @param null.
	 * @param string|bool $value Constant value.
	 */
	public function generate_email_otp_if_user_exists() {
		global $wpdb, $table_prefix;

		$username = isset( $_POST['user'] ) ? wp_unslash( $_POST['user'] ) : null;
		$user     = get_user_by( 'login', $username );
		$table    = $table_prefix . 'otp';

		if ( $user && ! is_wp_error( $user ) ) {
			$maximum_no_of_request = SignInWithOtp::get_option( 'maximum_no_of_request' );
		    $user_id               = $user->ID;
		    $user_no_of_request    = $wpdb->get_var( $wpdb->prepare( 'SELECT count(*) FROM ' . $table . ' WHERE user_id=%d', $user_id ) );
		    
		    if( $user_no_of_request < $maximum_no_of_request ){
			    $random_otp = mt_rand( 100000,999999 );
			    $otp        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user_id ) );

			    if( $otp ) {
			    	$data = array(
						'otp_pass' 			=> $random_otp,
						'created_at' 		=> wp_date( 'Y-m-d h:i:s' ),
						'otp_tries'			=> 0,
						'status' 			=> 1,
						'user_status'		=> 0
					);

			    	$where['user_id'] 	= $user_id;
					$format 			= array( '%s', '%s', '%d', '%d', '%d' );
					$where_format 		= array( '%d' );
					$wpdb->update( $table, $data, $where, $format, $where_format );
			    } else {
				    $data = array(
						'user_id' 			=> $user_id,
						'otp_pass' 			=> $random_otp,
						'created_at' 		=> wp_date( 'Y-m-d h:i:s' ),
						'otp_tries'			=> 0,
						'status' 			=> 1,
						'user_status'		=> 0
					);

				    $format = array( '%d', '%s', '%s', '%d', '%d', '%d' );
					$wpdb->insert( $table, $data, $format );
				}

				$no_of_otp_request = absint( get_user_meta( $user_id, 'no_of_otp_request', true ) );
				$no_of_otp_request = $no_of_otp_request ? $no_of_otp_request : 0;

				update_user_meta( $user_id, 'no_of_otp_request', ( $no_of_otp_request + 1 ) );
				update_user_meta( $user_id, 'failed_login_attempts', 0 );
				$this->create_email_otp( $user );
				$user_email = $this->helper->obfuscate_string( $user->user_email, 'X' );

				$message = array( 
					'status'      => 'success', 
					'messageHtml' => sprintf( '<div class="success">Your One Time Password(OTP) has been generated and sent to %s. Please enter the OTP below.</div>', $user_email )
				);
				wp_send_json( $message );
			} else {
				$message = array( 
					'status'      => 'error', 
					'messageHtml' => '<div id="login_error"><strong>Error</strong>: You have exceeded the maximum no of One Time Password(OTP) attemps.</div>' 
				);
				wp_send_json( $message );
			}
		} else {
			$message = array( 
				'status'      => 'error', 
				'messageHtml' => sprintf( '<div id="login_error"><strong>Error</strong>: The username <b>%s</b> is not registered on this site. If you are unsure of your username, try your email address instead.</div>', $username ) 
			);	
			wp_send_json( $message );
		}

		wp_die();
	}

	/**
	 * Create an email OTP to the WP users.
	 *
	 * @param object $user User object.
	 * @version 1.0.0
	 */
	public function create_email_otp( $user ) {		
		$this->mailer->send_email( $user );
	}

	/**
	 * Retrieves the email OTP for a specific user.
	 *
	 * @param int $user_id The ID of the user for whom to retrieve the email OTP.
	 * @return string|null The email OTP if found, or null if not found.
	 */
	public function get_users_email_otp( $user_id ) {
		global $wpdb, $table_prefix;
		$table = $table_prefix . 'otp';
		$otp   = $wpdb->get_row( $wpdb->prepare( "SELECT otp_pass FROM {$table} WHERE user_id = %d AND status = 1 ORDER BY otp_id DESC LIMIT 0,1", $user_id ) );

		return absint( $otp->otp_pass ); 
	}

	/**
	 * Authenticate the user.
	 * 
	 * This function handles user authentication by verifying the provided credentials.
	 * It typically checks the username and password against stored values in the database.
	 * If the credentials are valid, it may start a session, set authentication tokens, or
	 * perform other necessary actions to log the user in.
	 * 
	 * @return void
	 */
	public function authenticate_user() {
		$username 	= isset( $_POST['username'] ) ? wp_unslash( $_POST['username'] ) : null;
		$otp_pass 	= isset( $_POST['otp_pass'] ) ? wp_unslash( $_POST['otp_pass'] ) : null;
		$user 		= get_user_by( 'login', $username );

		if ( $user && ! is_wp_error( $user ) ) {		   
		    global $wpdb, $table_prefix;

			$table       = $table_prefix . 'otp';
		    $user_id     = $user->ID;
		    $invalid_otp = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d AND otp_pass = %s", $user_id, $otp_pass ) );

		    if ( empty( $invalid_otp ) ) {	    	
		    	$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d AND status = 1", $user_id ) );

		    	if( $result && $result->status == 1 ){			    	
			    	$maximum_failed_login = SignInWithOtp::get_option( 'maximum_failed_login_attempts' );

			    	if( $result->otp_tries < $maximum_failed_login ){
				    	if( ! is_wp_error( $result ) && $result ){
					    	$otp_tries 			= absint($result->otp_tries);
					    	$where['user_id'] 	= $user_id;
							$data['otp_tries'] 	= $otp_tries + 1;
							$format 			= array('%d');
							$where_format 		= array('%d');
							$wpdb->update($table, $data, $where, $format, $where_format);
						}

					    $message = array( 
							'status' => 'error', 
							'messageHtml' => sprintf( '<div id="login_error"><strong>Error</strong>: The OTP entered is incorrect. Please enter correct OTP.</div>') 
						);
					    wp_send_json( $message );
					} else {
						$reason = sprintf( 'This user account has been locked for %s minutes due to several incorrect attempts.', SignInWithOtp::get_option( 'block_time' ) );
						update_user_meta( $user_id, 'siotp_user_locked', true );
						update_user_meta( $user_id, 'siotp_user_locked_reason', $reason );
						update_user_meta( $user_id, 'siotp_user_locked_at', wp_date('Y-m-d h:i:s' ) );

						$where['user_id'] 		= $user_id;
						$data['created_at'] 	= wp_date( 'Y-m-d h:i:s' );
						$data['user_status']	= 0;
						$format 				= array( '%s', '%d' );
						$where_format 			= array( '%d' );
						$wpdb->update( $table, $data, $where, $format, $where_format );

						$message = array( 
							'status' => 'error', 
							'messageHtml' => sprintf('<div id="login_error"><strong>Error</strong>: This user account has been locked for %s minutes due to several incorrect attempts. Please try after %s minutes.</div>', SignInWithOtp::get_option('block_time'), SignInWithOtp::get_option('block_time')),
							'user_locked' => true
						);
					    wp_send_json($message);
					}
				} else {
					$message = array( 
						'status' => 'error', 
						'messageHtml' => sprintf('<div id="login_error"><strong>Error</strong>: OTP entered is expired. Please generate a new OTP and try again.</div>') 
					);
				    wp_send_json($message);
				}
			} else {
				if( $invalid_otp->status == 1 ){
					$locked = get_user_meta( $user->ID, 'user_locked', true );

					if ( ! empty( $locked ) ) {
						$message = array( 
							'status'      => 'error', 
							'messageHtml' => sprintf('<div id="login_error"><strong>Error</strong>: This user account has been locked for %s minutes due to several incorrect attempts. Please try after %s minutes.</div>', SignInWithOtp::get_option('block_time'), SignInWithOtp::get_option('block_time')) 
						);
					    wp_send_json( $message );
					} else {
						$credentials = array();

						if ( ! empty( $username ) ) {
							$credentials['user_login'] = wp_unslash( $username );
						}

						if ( ! empty( $user->user_pass ) ) {
							$credentials['user_password'] = $user->user_pass;
						}

						$credentials['remember'] = false;

						if ( '' === $secure_cookie ) {
							$secure_cookie = is_ssl();
						}

						$secure_cookie = apply_filters( 'secure_signon_cookie', $secure_cookie, $credentials );

						global $auth_secure_cookie; // XXX ugly hack to pass this to wp_authenticate_cookie().
						$auth_secure_cookie = $secure_cookie;

						$login_result = wp_signon();
						wp_set_auth_cookie( $user->ID, $credentials['remember'], $secure_cookie );
						do_action( 'wp_login', $user->user_login, $user );
						
						$where['user_id'] 	= $user_id;
						$data['status'] 	= 0;
						$format 			= array( '%d' );
						$where_format 		= array('%d');
						$wpdb->update( $table, $data, $where, $format, $where_format );

						$message = array( 
							'status' => 'success', 
							'messageHtml' => sprintf( '<div class="success">You have successfully logged in to %s</div>',get_bloginfo( 'name' ) ),
							'adminUrl' => admin_url( '/' ) 
						);
					    wp_send_json( $message );
					}
				} else {
					$message = array( 
						'status'      => 'error', 
						'messageHtml' => sprintf( '<div id="login_error"><strong>Error</strong>: OTP entered is expired. Please generate a new OTP and try again.</div>' ) 
					);
				    wp_send_json( $message );
				}
			}
		} else {
		    $message = array( 
				'status'      => 'error', 
				'messageHtml' => sprintf( '<div id="login_error"><strong>Error</strong>: The username <b>%s</b> is not registered on this site. If you are unsure of your username, try your email address instead.</div>', $username ) 
			);	
			wp_send_json($message);					
		}
		
		wp_die();
	}

	/**
	 * Disables the OTP (One-Time Password) after its validity period has expired.
	 *
	 * This function is responsible for checking if the validity period of an OTP
	 * has passed, and if so, it disables the OTP to prevent further use. This ensures
	 * that expired OTPs cannot be used for authentication, enhancing security.
	 *
	 * @return void
	 */
	public function disable_otp_after_validity() {
		$username 	= isset( $_POST['user'] ) ? wp_unslash( $_POST['user'] ) : null;
		$user 		= get_user_by( 'login', $username );

		if ( $user && ! is_wp_error( $user ) ) {
			global $wpdb, $table_prefix;

			$user_id = $user->ID;
			$table   = $table_prefix . 'otp';
			$otp     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d AND status = 1 ORDER BY otp_id DESC LIMIT 0,1", $user_id ) );
			
			if( ! is_wp_error( $otp ) && $otp ){
				$current_time    = current_time( 'timestamp' );
				$validity_time   = SignInWithOtp::get_option( 'otp_validity_time' ) * 60;
				$otp_created_at  = strtotime( $otp->created_at );
				$otp_expiry_time = (int) abs( $validity_time + $otp_created_at ); 
				$locked          = get_user_meta( $otp->user_id, 'user_locked', true );

				if( ( $current_time > $otp_expiry_time ) && ! $locked ) {	
					$this->log('OTP expired', 'OTP Pass-' . $otp->otp_pass);
			    	$where['user_id'] 	= $user_id;
			    	$where['otp_pass'] 	= $otp->otp_pass;
					$data['status'] 	= 0;
					$format 			= array('%d');
					$where_format 		= array('%d', '%s');
					$updated 			= $wpdb->update( $table, $data, $where, $format, $where_format );
				}
			
				if ( $updated ) {
				    $message = array( 
						'status'      => 'error', 
						'messageHtml' => sprintf('<div id="login_error"><strong>Error</strong>: OTP entered is expired. Please generate a new OTP and try again.</div>') 
					);
				    wp_send_json($message);
				}
		    }
		}

		wp_die();
	}

	/**
	 * Cleans up expired OTP (One-Time Password) entries from the database.
	 * This function identifies and removes OTP records that are no longer valid,
	 * helping to maintain the integrity and performance of the database.
	 * 
	 * @return void
	 */
	public function cleanup_expired_otps(){
		global $wpdb, $table_prefix;

		$table = $table_prefix . 'otp';
		$opts  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 1" ) );

		if( ! is_wp_error( $opts ) && $opts ){
			foreach( $opts as $opt ) {
				$current_time    = current_time( 'timestamp' );
				$validity_time   = SignInWithOtp::get_option( 'otp_validity_time' ) * 60;
				$otp_created_at  = strtotime( $opt->created_at);
				$otp_expiry_time = (int) abs( $validity_time + $otp_created_at + 10 );
				$locked          = get_user_meta( $opt->user_id, 'siotp_user_locked', true );
				$this->log('OTP cleanning section', $current_time . '===' . $otp_expiry_time );

				if( ( $current_time < $otp_expiry_time ) && !$locked ) {
					$this->log('Expiry date', wp_date( 'Y-m-d h:i:s', $otp_expiry_time ) );
					$where['otp_id'] 	= $opt->otp_id;
					$data['status'] 	= 0;
					$format 			= array('%d');
					$where_format 		= array('%d');
					$updated 			= $wpdb->update( $table, $data, $where, $format, $where_format );
				}
			}
		}
	}

	/**
	 * Schedules the unlocking of a user account.
	 *
	 * This function is intended to be called to initiate a scheduled task
	 * that unlocks a user account after a specified period. The actual 
	 * implementation of the scheduling mechanism and the conditions under
	 * which the user account is unlocked should be handled within this 
	 * function or in the code that this function invokes.
	 *
	 * Example usage:
	 * $this->call_unlock_user_scheduler();
	 *
	 * @return void
	 */
	public function call_unlock_user_scheduler() {
		$username 	= isset( $_POST['user'] ) ? wp_unslash( $_POST['user'] ) : null;
		$user 		= get_user_by('login', $username);

		if( $user && ! is_wp_error( $user ) ) {
			wp_schedule_single_event( time() + abs( SignInWithOtp::get_option( 'block_time') * 60 ), SIOTP_SCHEDULER_UNBLOCK_USER, array( $user->ID ) );
		}
	}

	/**
	 * Unblocks a user after their account has been locked for a specified duration.
	 *
	 * This function is designed to be used to automatically unblock a user 
	 * whose account has been locked after a certain period of time. It takes 
	 * the user's ID as an argument and performs the necessary operations to 
	 * remove the lock status from the user's account.
	 *
	 * @param int $user_id The ID of the user to be unblocked.
	 */
	public function unblock_user_after_locked_time( $user_id ) {		

		global $wpdb, $table_prefix;

		$table = $table_prefix . 'otp';
		$this->log('User unblocking section', 'Action fired');
		
		if ( $user_id ) {
			$locked        = get_user_meta( $user_id, 'siotp_user_locked', true );
			$locked_at     = get_user_meta( $user_id, 'siotp_user_locked_at', true );
			$current_time  = current_time( 'timestamp' );
			$validity_time = SignInWithOtp::get_option( 'otp_validity_time' ) * 60;
			$expiry_time   = (int) abs( $validity_time + $locked_at );

			if ( ! empty( $locked ) && $current_time > $expiry_time ) {
				$this->log('User locked time ended', 'Unlocked');
				delete_user_meta( $user_id, 'siotp_user_locked' );
				delete_user_meta( $user_id, 'siotp_user_locked_reason' );
				delete_user_meta( $user_id, 'siotp_user_locked_at' );

				$where['user_id'] 		= $user_id;
				$data['created_at'] 	= wp_date( 'Y-m-d h:i:s' );
				$data['otp_tries'] 		= 0;
				$data['user_status']	= 1;
				$format 				= array( '%s', '%d', '%d' );
				$where_format 			= array( '%d' );
				$wpdb->update( $table, $data, $where, $format, $where_format );
			}
		} else {
			return;
		}

		wp_die();
	}

	/**
	 * Custom event handler to clean up expired OTP (One-Time Password) entries.
	 * 
	 * This function is designed to be triggered as a scheduled event (cron job) 
	 * to periodically remove expired OTP entries from the database. 
	 * It ensures that the database remains clean and efficient by removing 
	 * unnecessary records that are no longer valid.
	 */
	public function custom_event_cleanup_expired_otp(){		
		$hook = wp_unslash( SIOTP_SCHEDULER_USER_OTP );
		$ran = $this->cron_run( $hook );

		if ( is_wp_error( $ran ) ) {
			$set = set_message( $ran->get_error_message() );

			// If we can't store the error message in a transient, just display it.
			if ( ! $set ) {
				$message = array( 
					'status' => 'error', 
					'messageHtml' => $ran->get_error_message() 
				);
			    wp_send_json( $message );
			}
		}

		wp_die();
	}

	/**
	 * Executes a cron event immediately.
	 *
	 * Executes an event by scheduling a new single event with the same arguments.
	 *
	 * @param string $hookname The hook name of the cron event to run.
	 * @return true|WP_Error True if the execution was successful, WP_Error if not.
	 */
	public function cron_run( $hookname ) {
		$cron_tasks = _get_cron_array();

		if ( empty( $cron_tasks ) ) {
			return;
		}

		foreach ( $crons as $time => $cron ) {
			if ( isset( $cron[ $hookname ][ $sig ] ) ) {
				$event              = $cron[ $hookname ][ $sig ];
				$event['hook']      = $hookname;
				$event['timestamp'] = $time;
				$event              = (object) $event;

				delete_transient( 'doing_cron' );
				$scheduled = force_schedule_single_event( $hookname, $event->args ); // UTC

				if ( is_wp_error( $scheduled ) ) {
					return $scheduled;
				}

				spawn_cron();
				sleep( 1 );

				/**
				 * Fires after a cron event is scheduled to run manually.
				 *
				 * @param stdClass $event {
				 *     An object containing the event's data.
				 *
				 *     @type string       $hook      Action hook to execute when the event is run.
				 *     @type int          $timestamp Unix timestamp (UTC) for when to next run the event.
				 *     @type string|false $schedule  How often the event should subsequently recur.
				 *     @type array        $args      Array containing each separate argument to pass to the hook's callback function.
				 *     @type int          $interval  The interval time in seconds for the schedule. Only present for recurring events.
				 * }
				 */

				do_action( 'crontrol/ran_event', $event );

				return true;
			}
		}
	}

	/**
	 * Log function
	 *
	 * @param string $text
	 * @param string $message  
	 * @return true
	 */
	public function log( $text, $message ) {
		$filename = SIOTP_PLUGIN_PATH . "/siotp_log.log";

		if ( file_exists( $filename ) ) {
			if ( ! is_writable( $filename ) ) {
	            @chmod( $filename, 0644 );
	        }

			$file = fopen( $filename, "a" );
		    fwrite( $file, wp_date( 'Y-m-d h:i:s' ) . " :: " . $text . ":- " . $message . "\n" ); 
		    fclose( $file ); 
		}
	}
}
