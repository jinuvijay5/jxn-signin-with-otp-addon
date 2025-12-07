<?php
/**
 * SIOTP_Dashboard_List_Table setup
 *
 * @package SIOTP_Dashboard_List_Table
 * @version   1.0.0
 */

defined('ABSPATH') || exit;
require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Represents a custom WordPress list table for displaying data related to SIOTP Dashboard.
 * Extends the WP_List_Table class to inherit its functionality.
 */
class SIOTP_Dashboard_List_Table extends WP_List_Table {
	/**
	 * Constructor method for the class.
	 * 
	 * Initializes a new instance of the class.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'signin-with-otp',
			'plural'   => 'signin-with-otps',
			'ajax'     => false,
			'screen'   => 'signin-with-otp',
		) );
	}

	/**
	 * Prepares the items for display in the list table.
	 *
	 * This method is responsible for preparing the data that will be displayed
	 * in the list table. It typically retrieves data from the database or other
	 * sources, applies any necessary filters or transformations, and sets up
	 * the data structure that will be used by the list table to render the items.
	 */
	public function prepare_items() {
		global $wpdb, $table_prefix;

		$data     = array();
		$per_page = 20;
		$table    = $table_prefix . 'otp';
		$columns  = $this->get_columns();
        $hidden   = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        $this->process_bulk_action();

		$opts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table}" ) );

		if( $opts ) {
			foreach( $opts as $opt ) { 

				$user_status  = '';
				$status       = ( $opt->status == 1 ) ? '<span class="green">Active</span>' : '<span class="red">Expired</span>';
				$reason       = get_user_meta( $opt->user_id, 'siotp_user_locked_reason', true );
				$reason       = $reason ? ' (' . $reason . ')' : '';
				$user_status  = ( $opt->user_status == 0 ) ? '<span class="green">No</span>' : '<span class="red">Yes</span>' . $reason;
				$user         = get_user_by( 'id', $opt->user_id );
				$user_email   = $user->user_email;
				$display_name = $user->display_name ? $user->display_name : $user->first_name . ' ' . $user->last_name;

				$data[] = array(
                    'otp_id'          => $opt->otp_id,
                    'user_id'         => $opt->user_id,
                    'user_name'       => $display_name,
                    'user_email'      => $user_email,
                    'otp_pass'        => $opt->otp_pass,
                    'otp_tries'       => $opt->otp_tries,
                    'created_at'      => wp_date( 'j F, Y h:i A', strtotime( $opt->created_at ) ),
                    'status'          => $status,
                    'user_status'     => $user_status,
                    'user_status_num' => $opt->user_status
                );
			}
		}

		$total_items           = count( $data );
		$current_page          = $this->get_pagenum();
        $this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = array_slice( $data,( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Returns an array of column names for the table.
	 *
	 * @return array<string,string> Array of column names keyed by their ID.
	 */
	public function get_columns() {
		return array(
			'cb'            => '<input type="checkbox" />',
			'user_name'     => __( 'Name', SIOTP_TEXTDOMAIN ),
			'user_email'    => __( 'Email address', SIOTP_TEXTDOMAIN ),
			'otp_pass' 		=> __( 'OTP', SIOTP_TEXTDOMAIN ),
			'otp_tries'  	=> __( 'No. of tries', SIOTP_TEXTDOMAIN ),
			'created_at'  	=> __( 'Created at', SIOTP_TEXTDOMAIN ),
			'status'  		=> __( 'OTP Status', SIOTP_TEXTDOMAIN ),
			'user_status'  	=> __( 'Locked', SIOTP_TEXTDOMAIN ),
		);
	}

	 /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns() {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns() {
        return array( 'user_id' => array( 'user_id', false ), 'created_at' => array( 'created_at', false ) );
    }

	/**
	 * Returns an array of CSS class names for the table.
	 *
	 * @return array<int,string> Array of class names.
	 */
	protected function get_table_classes() {
		return array( 'widefat', 'fixed', 'striped', 'table-view-list', $this->_args['plural'] );
	}

	/**
     * Define what data to show on each column of the table
     *
     * @param Array $item Data
     * @param String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'user_name':
            case 'user_email':
            case 'otp_pass':
            case 'otp_tries':
            case 'created_at':
            case 'status':
            case 'user_status':
                return $item[ $column_name ];

            default:
                return print_r( $item, true );
        }
    }	

	/**
	 * Checkbox for Each Row.
	 *
	 * @param Array $item Data
     *
     * @return Mixed
	 */
	public function column_cb($item) {
	    return sprintf(
	          '<input type="checkbox" name="otp[]" value="%s" />',
	          $item['otp_id']
	    );
	}

	/**
	 * Generates and displays row action links for the table.
	 *
	 * @phpstan-param array{
	 *   interval: int,
	 *   display: string,
	 *   name: string,
	 *   is_too_frequent: bool,
	 * } $schedule
	 * @param mixed[] $schedule    The schedule for the current row.
	 * @param string  $column_name Current column name.
	 * @param string  $primary     Primary column name.
	 * @return string The row actions HTML.
	 */
	protected function handle_row_actions( $schedule, $column_name, $primary ) {

		if ( $primary !== $column_name ) {
			return '';
		}

		$links       = array();
		$delete_link = add_query_arg( array(
			'page'      => 'siotp-dashboard',
			'action' 	=> 'delete-otp',
			'otp_id'    => rawurlencode( $schedule['otp_id'] ),
		), admin_url( 'admin.php' ) );
		$delete_link = wp_nonce_url( $delete_link, 'otp-delete-' . $schedule['otp_id'] );
		$status      = ($schedule['user_status_num'] == 0) ? 'lock-user' : 'unlock-user';
		$locked_link = add_query_arg( array(
			'page'      => 'siotp-dashboard',
			'action' 	=> $status,
			'otp_id'    => rawurlencode( $schedule['otp_id'] ),
		), admin_url( 'admin.php' ) );

		$locked_link = wp_nonce_url( $locked_link, $status . '-' . $schedule['otp_id'] );
		$links[]     = "<span class='delete'><a href='" . esc_url( $delete_link ) . "'>" . esc_html__( 'Delete', SIOTP_TEXTDOMAIN ) . '</a></span>';
		$links[]     = "<span class='" . $status . "'><a href='" . esc_url( $locked_link ) . "'>" . esc_html__( ucfirst(str_replace( '-', ' ',$status ) ), SIOTP_TEXTDOMAIN ) . '</a></span>';

		return $this->row_actions( $links );
	}

	/**
	 * Retrieves the bulk actions available for the current list table.
	 *
	 * This function is used to define the bulk actions that can be performed
	 * on items displayed in a list table, such as a WordPress admin page.
	 * It returns an associative array where the keys represent the action
	 * names and the values represent the action labels.
	 *
	 * @return array An associative array of bulk actions, where the keys are
	 *               the action names and the values are the action labels.
	 */
	public function get_bulk_actions() {
		return array(
			'bulk-delete'     => __( 'Delete', SIOTP_TEXTDOMAIN ),
			'lock-user'       => __( 'Lock user', SIOTP_TEXTDOMAIN ),
			'unlock-user'     => __( 'Unlock user', SIOTP_TEXTDOMAIN ),
			'activate-otp'   => __( 'Activate OTP', SIOTP_TEXTDOMAIN ),
			'deactivate-otp' => __( 'Deactivate OTP', SIOTP_TEXTDOMAIN )
		);
	}

	/**
	 * This method processes bulk actions
	 * it can be outside of class
	 * it can not use wp_redirect coz there is output already
	 * in this example we are processing delete action
	 * message about successful deletion will be shown on page in next part
	 */
	public function process_bulk_action() {
		$messages = array();
	    // Delete OTP when a 'delete-otp' action is being triggered...
	    if ( 'delete-otp' === $this->current_action() ) {
	        $nonce = esc_attr( $_GET['_wpnonce'] );

	        if ( ! wp_verify_nonce( $nonce, 'otp-delete-' . $_GET['otp_id'] ) ) {
	            die( 'Go get a life script kiddies' );
	        } else {
	            $this->delete_otp( absint( $_GET['otp_id'] ) );
                $messages = array(
                	'type'    => 'success',
                	'message' => 'The OTP has been successfully deleted.'
                );
                set_transient( 'siotp_custom_notices', $messages, 60 );
                wp_redirect( esc_url_raw( admin_url( 'admin.php?page=siotp-dashboard' ) ) );
	            exit;
	        }
	    }

	    // Lock a user when 'lock-user' action is being triggered...
	    if ( ( 'lock-user' === $this->current_action() || 'unlock-user' === $this->current_action() ) && isset( $_POST['action2'] ) ) {
	        $method = ( 'lock-user' === $this->current_action() ) ? 'lock-user' : 'unlock-user';
	        check_admin_referer( 'bulk-' . $this->_args['plural'] );
			$otp_ids = esc_sql( $_POST['otp'] );

			foreach ( $otp_ids as $otp_id ) {
				$this->update_user_lock_status( absint( $otp_id ), $method );
			}
			
			$action   = ( 'lock-user' === $this->current_action() ) ? 'locked' : 'unlocked';
			$messages = array(
				'type'    => 'success',
				'message' => 'The user\'s account has been successfully ' . $action . '.'
			);
			set_transient( 'siotp_custom_notices', $messages, 60 );
			wp_redirect( esc_url_raw( admin_url( 'admin.php?page=siotp-dashboard' ) ) );
			exit;
	    }

		if ( ( 'activate-otp' === $this->current_action() || 'deactivate-otp' === $this->current_action() ) && isset( $_POST['action2'] ) ) {
			$method = ( 'activate-otp' === $this->current_action() ) ? 'activate-otp' : 'deactivate-otp';
	        check_admin_referer( 'bulk-' . $this->_args['plural'] );
			$otp_ids = esc_sql( $_POST['otp'] );

			foreach ( $otp_ids as $otp_id ) {
				$this->update_otp_status( absint( $otp_id ), $method );
			}
			
			$action   = ( 'activate-otp' === $this->current_action() ) ? 'enabled' : 'deactivated';
			$messages = array(
				'type'    => 'success',
				'message' => 'The One-Time Password has been ' . $action . '.'
			);
			set_transient( 'siotp_custom_notices', $messages, 60 );
			wp_redirect( esc_url_raw( admin_url( 'admin.php?page=siotp-dashboard' ) ) );
			exit;
		}

		if( 'lock-user' === $this->current_action() || 'unlock-user' === $this->current_action() ) {
			$nonce = esc_attr( $_GET['_wpnonce'] );
	        $method = ( 'lock-user' === $this->current_action() ) ? 'lock-user' : 'unlock-user';

	        if ( ! wp_verify_nonce( $nonce, $method . '-' . $_GET['otp_id'] ) ) {
	            die( 'Go get a life script kiddies' );
	        } else {
	            $this->update_user_lock_status( absint( $_GET['otp_id'] ), $method );
	            $action   = ( 'lock-user' === $this->current_action() ) ? 'locked' : 'unlocked';
	            $messages = array(
                	'type'    => 'success',
                	'message' => 'The user\'s account has been successfully ' . $action . '.'
                );
                set_transient( 'siotp_custom_notices', $messages, 60 );
                wp_redirect( esc_url_raw( admin_url( 'admin.php?page=siotp-dashboard' ) ) );
	            exit;
	        }
		}

	    // If the delete bulk action is triggered
	    if ( ( isset( $_POST['action'] ) && 'bulk-delete' == $_POST['action'] )
	         || ( isset( $_POST['action2'] ) && 'bulk-delete' == $_POST['action2'] )
	    ) {
			check_admin_referer( 'bulk-' . $this->_args['plural'] );
	        $delete_ids = esc_sql( $_POST['otp'] );

	        // loop over the array of record IDs and delete them
	        foreach ( $delete_ids as $id ) {
	            $this->delete_otp( $id );
	        }

	        wp_redirect( esc_url_raw( add_query_arg() ) );
	        exit;
	    }
	}

	/**
	 * Outputs a message when there are no items to show in the table.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'There are currently no OTP entries.', SIOTP_TEXTDOMAIN );
	}

	/**
	 * Deletes the OTP with the specified ID from the database.
	 *
	 * @param int $id OTP ID
	 */
	public function delete_otp( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}otp",
			[ 'otp_id' => $id ],
			[ '%d' ]
		);
	}

	/**
	 * Function to lock or unlock a user.
	 *
	 * @param int    $id      The ID of the user record.
	 * @param string $type    The type of action to perform (e.g., 'lock' or 'unlock').
	 */
	public function update_user_lock_status( $id, $type ) {
		global $wpdb;

		$user_status = ( $type == 'lock-user' ) ? 1 : 0;
		$user_id     = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}otp WHERE otp_id = %d",
				$id
			)
		);

		if( $user_id ) {
			$wpdb->update(
				"{$wpdb->prefix}otp",
				[ 'user_status' => $user_status ],
				[ 'otp_id' => $id ],
				[ '%d' ],
				[ '%d', '%d' ]
			);

			

			if( $type == 'lock-user' ) {
				$reason = sprintf( 'This user account is locked for security reasons. Please use <b>Lost your password</b> option to unlock it' );
				update_user_meta( $user_id, 'siotp_user_locked', true );
				update_user_meta( $user_id, 'siotp_user_locked_reason', $reason );
				update_user_meta( $user_id, 'siotp_user_locked_at', wp_date( 'Y-m-d h:i:s' ) );
			} else {
				delete_user_meta( $user_id, 'siotp_user_locked' );
				delete_user_meta( $user_id, 'siotp_user_locked_reason' );
				delete_user_meta( $user_id, 'siotp_user_locked_at' );
			}
		}
	}

	/**
	 * Function to activate or deactivate the OTP.
	 *
	 * @param int    $id      The ID of the user record.
	 * @param string $type    The type of action to perform (e.g., 'activate' or 'deactivate').
	 */
	public function update_otp_status( $id, $type ) {
		global $wpdb;

		$status = ( $type == 'activate-otp' ) ? 1 : 0;
		$wpdb->update(
			"{$wpdb->prefix}otp",
			[ 'status' => $status ],
			[ 'otp_id' => $id ],
			[ '%d' ],
			[ '%d', '%d' ]
		);
	}
}
