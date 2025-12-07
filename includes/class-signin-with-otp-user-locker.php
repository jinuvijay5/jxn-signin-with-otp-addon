<?php
/**
 * SIOTP_User_Locker setup
 *
 * @package SIOTP_User_Locker
 * @version   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class SIOTP_User_Locker
 * 
 * This class represents a user locker in the SIOTP (Smart Internet of Things Platform) system.
 * It encapsulates functionality related to managing user lockers, such as creating, updating, and deleting lockers.
 * Instances of this class can be used to interact with individual user lockers and perform operations on them.
 */
class SIOTP_User_Locker {
	/**
	 * Loader variable.
	 *
	 * @version 1.0.0
	 */
	private SIOTP_Loader $loader;
	
	/**
	 * Previous lock status.
	 *
	 * @var boolean
	 */
	public $prev_lock_status = null;

	/**
	 * locked.
	 *
	 * @var boolean
	 */
	public $locked = false;

	/**
	 * Constructor method for the class.
	 * 
	 * This method is called automatically when an instance of the class is created.
	 * It initializes the object and sets up any necessary configurations or dependencies.
	 */
	public function __construct() {
		$this->loader = new SIOTP_Loader();

		// Check if user is already locked
		$this->loader->add_filter( 'wp_authenticate_user', $this, 'wp_authenticate_user', 1 );

		// Set password check flag
		$this->loader->add_filter( 'check_password', $this, 'check_password' );

		// Increment bad attempt counter and finally lock account
		$this->loader->add_action( 'wp_login_failed', $this, 'wp_login_failed' );

		// Reset account lock on pass reset and valid login
		$this->loader->add_action( 'password_reset', $this, 'password_reset' );
		$this->loader->add_action( 'wp_login', $this, 'wp_login' );

		// Add info about account lock
		$this->loader->add_filter( 'login_errors', $this, 'login_errors' );

		// Edit user profile
		$this->loader->add_action( 'edit_user_profile', $this, 'edit_user_profile' );
		$this->loader->add_action( 'edit_user_profile_update', $this, 'edit_user_profile_update' );

		// Add new column to the user list
		$this->loader->add_filter( 'manage_users_columns', $this, 'manage_users_columns' );
		$this->loader->add_filter( 'manage_users_custom_column', $this, 'manage_users_custom_column', 10, 3 );

		$this->loader->run();
	}

	/**
	 * Check if user is already locked / disabled
	 *
	 * @version   1.0.0.0
	 *
	 * @param    object  $user User object.
	 * @return   object  If success return user object otherwise WP_Error.
	 */
	public function wp_authenticate_user( $user ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Return error if user account is disabled
		$disabled = get_user_meta( $user->ID, 'siotp_user_disabled', true );

		if ( !empty( $disabled ) ) {

			$reason = '';

			if ( SignInWithOtp::get_option( 'userlocker_show_reason' ) ) {

				$reason = ( string ) SignInWithOtp::get_option( 'userlocker_default_disabled_reason' );
			}

			if ( empty( $reason ) ) {
				return new WP_Error( 'siotp_user_disabled', __( '<strong>ERROR</strong>: This user account is disabled.', SIOTP_TEXTDOMAIN ) );
			} else {
				return new WP_Error( 'siotp_user_disabled', sprintf( __( '<strong>ERROR</strong>: This user account is disabled ( reason: %s ).', SIOTP_TEXTDOMAIN ), esc_html( $reason ) ) );
			}
		}

		// Return error if user account is locked
		$locked = get_user_meta( $user->ID, 'siotp_user_locked', true );

		if ( !empty( $locked ) ) {

			$reason = '';

			if ( SignInWithOtp::get_option( 'userlocker_show_reason' ) ) {

				$reason = ( string ) SignInWithOtp::get_option( 'userlocker_default_locked_reason' );
			}

			if ( empty( $reason ) ) {
				return new WP_Error( 'siotp_user_locked', __( '<strong>ERROR</strong>: This user account is locked for security reasons. Please use <b>Lost your password</b> option to unlock it.', SIOTP_TEXTDOMAIN ) );
			} else {
				return new WP_Error( 'siotp_user_locked', sprintf( __( '<strong>ERROR</strong>: This user account is locked ( reason: %s ). Please use <b>Lost your password</b> option to unlock it.', SIOTP_TEXTDOMAIN ), esc_html( $reason ) ) );
			}
		}

		return $user;
	}

	/**
	 * This function checks the validity of a password.
	 * 
	 * @param string $check The password string to be checked.
	 * @return bool Returns true if the password is valid, false otherwise.
	 */
	public function check_password( $check ) {
		if ( !is_null( $this->prev_lock_status ) ) {
			update_user_meta( $this->prev_lock_status['id'], 'siotp_user_locked', $this->prev_lock_status['status'] );
			update_user_meta( $this->prev_lock_status['id'], 'siotp_bad_attempts', $this->prev_lock_status['count'] );
			update_user_meta( $this->prev_lock_status['id'], 'siotp_user_locked_reason', $this->prev_lock_status['reason'] );

			$this->prev_lock_status = null;
		}

		return $check;
	}

	/**
	 * Callback function to handle WordPress login failures.
	 * 
	 * This function is called when a login attempt fails in WordPress.
	 * It accepts the username of the failed login attempt as a parameter.
	 * 
	 * @param string $username The username of the failed login attempt.
	 */
	public function wp_login_failed( $username ) {
		if ( filter_var( $username, FILTER_VALIDATE_EMAIL ) ) {
			$user = get_user_by( 'email', $username );
		} else {
			$user = get_user_by( 'login', $username );
		}

		if ( !$user || ( $user->user_login != $username ) ) {
			// Invalid username
			return;
		}

		// Older WP versions called this function few times, and only last one should count.
		// Therefore save old data now and restore it in check_password hook if needed
		$this->prev_lock_status = array( 
			'id'     => $user->ID,
			'status' => get_user_meta( $user->ID, 'siotp_user_locked', true ),
			'count'  => get_user_meta( $user->ID, 'siotp_bad_attempts', true ),
			'reason' => get_user_meta( 'siotp_user_locked_reason', true ),
		 );

		$disabled = get_user_meta( $user->ID, 'siotp_user_disabled', true );
		$locked = get_user_meta( $user->ID, 'siotp_user_locked', true );

		if ( ! $disabled && ! $locked ) {
			$cnt = get_user_meta( $user->ID, 'siotp_bad_attempts', true );

			if ( $cnt === false ) {
				$cnt = 1;
			} else {
				++$cnt;
			}

			update_user_meta( $user->ID, 'siotp_bad_attempts', $cnt );

			if ( $cnt >= SignInWithOtp::get_option( 'userlocker_max_attempts' ) ) {
				$this->locked = true;
				$this->lock_user( $user->ID, SignInWithOtp::get_option( 'userlocker_default_locked_reason' ) );
			}
		}
	}

	/**
	 * Initiates the password reset process for a user.
	 *
	 * This function is responsible for initiating the password reset process for a given user.
	 * It takes a user object as a parameter and triggers the necessary actions to send a password reset link
	 * to the user's registered email address or mobile number.
	 *
	 * @param WP_User $user The user object for whom the password reset process is initiated.
	 */
	public function password_reset( $user ) {
		$this->unlock_user( $user->ID );
	}

	/**
	 * Function to perform WordPress login for a given username.
	 * 
	 * This function takes a username as input and performs a login action
	 * using the provided username. It is typically used in custom login
	 * functionalities or when programmatically logging in a user.
	 * 
	 * @param string $username The username for which the login action is performed.
	 * 
	 * @return void
	 */
	public function wp_login( $username ) {
		$user = get_userdatabylogin( $username );
		$this->unlock_user( $user->ID );
	}

	/**
	 * Locks a user account.
	 *
	 * This function locks a user account based on the provided user ID.
	 * Optionally, a reason for the lock can be specified.
	 *
	 * @param int    $user_id The ID of the user to lock.
	 * @param string $reason  (Optional) The reason for locking the user account.
	 * @return void
	 */
	public function lock_user( $user_id, $reason = '' ) {
		// Do not touch 'ul_bad_attempts' - it needs to be updated separately
		$old_status = $this->is_user_locked(  $user_id  );

		// Update status
		if ( ! empty( $old_status ) ) {
			update_user_meta( $user_id, 'siotp_user_locked', true );
		}
		// Update reason
		if ( $reason !== false ) {
			update_user_meta( $user_id, 'siotp_user_locked_reason', $reason );
		}
		// Call hooks
		if ( ! $old_status ) {
			do_action( 'siotp_lock_user', $user_id );
		}
	}

	/**
	 * Unlock a user account.
	 *
	 * This function unlocks a user account based on the provided user ID.
	 *
	 * @param int    $user_id The ID of the user account to unlock.
	 * @param string $reason  (Optional) The reason for unlocking the user account. Default is false.
	 */
	public function unlock_user( $user_id, $reason = false ) {
		$old_status = $this->is_user_locked(  $user_id );

		// Update status
		if (  $old_status ) {
			update_user_meta( $user_id, 'siotp_bad_attempts', 0 );
			update_user_meta( $user_id, 'siotp_user_locked', false );
		}

		// Update reason
		if ( SignInWithOtp::get_option( 'userlocker_default_disabled_reason' ) ) {
			if ( function_exists( 'delete_user_meta' ) ) {
				// WP3.0+
				delete_user_meta( $user_id, 'siotp_user_locked_reason' );
			} else {
				update_user_meta( $user_id, 'siotp_user_locked_reason', '' );
			}
		} elseif ( $reason !== false ) {
			update_user_meta( $user_id, 'siotp_user_locked_reason', $reason );
		}
		// Call hooks
		if ( $old_status ) {
			do_action( 'siotp_unlock_user', $user_id );
		}
	}

	/**
	 * Disable user function.
	 *
	 * This function disables a user account identified by the provided user ID.
	 *
	 * @param int    $user_id The ID of the user to be disabled.
	 * @param string $reason  (Optional) The reason for disabling the user account.
	 * @return void
	 */
	public function disable_user( $user_id, $reason = '' ) {
		$old_status = $this->is_user_disabled( $user_id );

		// Update status
		if ( !$old_status ) {
			update_user_meta( $user_id, 'siotp_user_disabled', true );
		}
		// Update reason
		if ( $reason !== false ) {
			update_user_meta( $user_id, 'siotp_user_disabled_reason', $reason );
		}
		// Call hooks
		if ( !$old_status ) {
			do_action( 'siotp_disable_user', $user_id );
		}
	}

	/**
	 * Enable a user account.
	 *
	 * @param int $user_id The ID of the user to enable.
	 * @param mixed $reason Optional. The reason for enabling the user. Defaults to false.
	 */
	public function enable_user( $user_id, $reason = false ) {
		$old_status = $this->is_user_disabled( $user_id );

		// Update status
		if ( $old_status ) {
			update_user_meta( $user_id, 'siotp_user_disabled', false );
		}

		// Update reason
		if ( SignInWithOtp::get_option( 'userlocker_default_disabled_reason' ) ) {
			if ( function_exists( 'delete_user_meta' ) ) {
				// WP3.0+
				delete_user_meta( $user_id, 'siotp_user_disabled_reason' );
			} else {
				update_user_meta( $user_id, 'siotp_user_disabled_reason', '' );
			}
		} elseif ( $reason !== false ) {
			update_user_meta( $user_id, 'siotp_user_disabled_reason', $reason );
		}

		// Call hooks
		if ( $old_status ) {
			do_action( 'siotp_enable_user', $user_id );
		}
	}

	/**
	 * Checks if the user account associated with the given user ID is locked.
	 *
	 * This function takes a user ID as input and determines whether the corresponding user account is locked.
	 * The user account may be locked due to multiple failed login attempts, administrative action, or other reasons.
	 *
	 * @param int $user_id The ID of the user whose account status needs to be checked.
	 * @return bool True if the user account is locked, false otherwise.
	 */
	public function is_user_locked( $user_id ) {
		return get_user_meta( $user_id, 'siotp_user_locked', true );
	}

	/**
	 * Checks if a user is disabled.
	 *
	 * This function takes a user ID as input and checks whether the user is disabled.
	 * It returns true if the user is disabled and false otherwise.
	 *
	 * @param int $user_id The ID of the user to check.
	 * @return bool True if the user is disabled, false otherwise.
	 */
	public function is_user_disabled(  $user_id ) {
		return get_user_meta( $user_id, 'siotp_user_disabled', true );
	}

	/**
	 * Function to handle login errors.
	 *
	 * @param WP_Error $errors The error object containing login errors.
	 * @return WP_Error The modified error object.
	 */
	public function login_errors( $errors ) {
		if ( $this->locked ) {
			$errors .= __( '<br /><strong>ERROR</strong>: This user account has been locked for security reasons. Please use <b>Lost your password</b> option to unlock it.', SIOTP_TEXTDOMAIN ) . "<br />\n";
		}
		return $errors;
	}

	/**
	 * Add user locked or disabled info about account lock.
	 * This function is responsible for editing the user profile.
	 *
	 * @version   1.0.0
	 *
	 * @param    array  $errors
	 * @return   array $errors
	 */
	public function edit_user_profile() {
		if ( !current_user_can( 'edit_users' ) ) {
			return;
		}

		global $user_id;

		// User cannot disable itself
		$current_user = wp_get_current_user();
		$current_user_id = $current_user->ID;
		?>
		<h3><?php _e( 'User Locking', SIOTP_TEXTDOMAIN )?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'User account locked', SIOTP_TEXTDOMAIN );?></th>
				<td><label for="siotp_user_locked"><input name="siotp_user_locked" type="checkbox" id="siotp_user_locked" value="false" <?php checked( true, get_user_meta( $user_id, 'siotp_user_locked', true ) );?> /> <?php _e( 'User account is locked for security reasons', SIOTP_TEXTDOMAIN );?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="siotp_user_locked_reason"><?php _e( 'Lock reason', SIOTP_TEXTDOMAIN );?></label></th>
				<td><input type="text" maxlength="500" size="80" name="siotp_user_locked_reason" id="ul_lock_reason" value="<?php echo esc_attr( get_user_meta( $user_id, 'siotp_user_locked_reason', true ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'User account disabled', SIOTP_TEXTDOMAIN );?></th>
				<td><label for="siotp_user_disabled"><input name="siotp_user_disabled" type="checkbox" id="siotp_user_disabled" value="false" <?php checked( true, get_user_meta( $user_id, 'siotp_user_disabled', true ) );?> /> <?php _e( 'User account is disabled', SIOTP_TEXTDOMAIN );?></label></td>
			</tr>
			<tr>
				<th scope="row"><label for="siotp_user_disabled_reason"><?php _e( 'Disable reason', SIOTP_TEXTDOMAIN );?></label></th>
				<td><input type="text" maxlength="500" size="80" name="siotp_user_disabled_reason" id="siotp_user_disabled_reason" value="<?php echo esc_attr( get_user_meta( $user_id, 'siotp_user_disabled_reason', true ) ); ?>" /></td>
			</tr>
		</table>
		<?php 
	}

	/**
	 * Handles the update of a user's profile.
	 * This function is called when the user submits the form to edit their profile.
	 */
	public function edit_user_profile_update() {
		global $user_id;

		if ( !current_user_can( 'edit_users' ) ) {
			return;
		}

		// User cannot disable itself
		$current_user    = wp_get_current_user();
		$current_user_id = $current_user->ID;
		if ( $current_user_id == $user_id ) {
			return;
		}

		// Lock/unlock user
		$new_status = isset( $_POST['siotp_user_locked'] ) ? sanitize_text_field( wp_unslash( $_POST['siotp_user_locked'] ) ) : '';
		$new_reason = isset( $_POST['siotp_user_locked_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['siotp_user_locked_reason'] ) ) : '';

		if ( $new_status ) {
			$this->lock_user( $user_id, $new_reason );
		} else {
			$this->unlock_user( $user_id, $new_reason );
		}

		// Disable/enable user
		$new_status = isset( $_POST['siotp_user_disabled'] ) ? sanitize_text_field( wp_unslash( $_POST['siotp_user_disabled'] ) ) : '';
		$new_reason = isset( $_POST['siotp_user_disabled_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['siotp_user_disabled_reason'] ) ) : '';
		if ( $new_status ) {
			$this->disable_user( $user_id, $new_reason );
		} else {
			$this->enable_user( $user_id, $new_reason );
		}
	}

	// Add new column to the user list page
	public function manage_users_columns( $columns ) {
		$columns['siotp_user_locked']   = __( 'Locked', SIOTP_TEXTDOMAIN );
		$columns['siotp_user_disabled'] = __( 'Disabled', SIOTP_TEXTDOMAIN );

		return $columns;
	}

	/**
	 * Callback function to manage custom columns in the users list table.
	 *
	 * This function is hooked into the 'manage_users_custom_column' action hook
	 * and is called whenever a custom column needs to be displayed for a user in the
	 * users list table in the WordPress admin area.
	 *
	 * @param string $value       The value to be displayed in the custom column.
	 * @param string $column_name The name of the custom column being displayed.
	 * @param int    $user_id     The ID of the user for which the custom column is being displayed.
	 * @return string             The modified value to be displayed in the custom column.
	 */
	public function manage_users_custom_column( $value, $column_name, $user_id ) {
		if ( $column_name == 'siotp_user_locked' ) {

			if ( get_user_meta( $user_id, 'siotp_user_locked', true ) ) {

				$ret = '<b>' . __( 'Yes', SIOTP_TEXTDOMAIN ) . '</b>';
				$reason = get_user_meta( $user_id, 'siotp_user_locked_reason', true );

				if ( !empty( $reason ) ) {
					$ret .= ' (' . esc_html( $reason ) . ')';
				}
			} else {
				$ret = __( 'No', SIOTP_TEXTDOMAIN );
			}
			return $ret;
		}

		if ( $column_name == 'siotp_user_disabled' ) {

			if ( get_user_meta( $user_id, 'siotp_user_disabled', true ) ) {

				$ret = '<b>' . __( 'Yes', SIOTP_TEXTDOMAIN ) . '</b>';
				$reason = get_user_meta( $user_id, 'siotp_user_disabled_reason', true );

				if ( !empty( $reason ) ) {
					$ret .= ' (' . esc_html($reason ) . ')';
				}
			} else {
				$ret = __( 'No', SIOTP_TEXTDOMAIN );
			}
			return $ret;
		}

		return $value;
	}
}

$wp_user_locker = new SIOTP_User_Locker();

/**
 * Lock user account (user may unlock it by requesting new password)
 *
 * @since 1.0.0
 * @uses apply_filters() Calls 'siotp_lock_user' on user id.
 *
 * @param $user_id int User ID
 * @param $reason bool|string New lock reason (may be empty string) or False to do not update lock reason. Default empty string
 */
function siotp_lock_user( $user_id, $reason = '' ) {
	global $wp_user_locker;
	$wp_user_locker->lock_user( $user_id, $reason );
}

/**
 * Unlock user account
 *
 * @since 1.0.0
 * @uses apply_filters() Calls 'siotp_unlock_user' on user id.
 *
 * @param $user_id int User ID
 * @param $reason bool|string New lock reason (may be empty string) or False to do not update lock reason. Default false
 */
function siotp_unlock_user( $user_id, $reason = false ) {
	global $wp_user_locker;
	$wp_user_locker->unlock_user( $user_id, $reason );
}

/**
 * Disable user account (user cannot enable it, only admin can do this)
 *
 * @since 1.0.0
 * @uses apply_filters() Calls 'siotp_disable_user' on user id.
 *
 * @param $user_id int User ID
 * @param $reason bool|string New disable reason (may be empty string) or False to do not update disable reason. Default empty string
 */
function siotp_disable_user( $user_id, $reason = '' ) {
	global $wp_user_locker;
	$wp_user_locker->disable_user( $user_id, $reason );
}
?>
