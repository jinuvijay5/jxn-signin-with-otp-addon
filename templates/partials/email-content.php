<?php
$admin   = new SIOTP_Admin_Functions();
$otp     = $admin->get_users_email_otp( $user_info->ID ); 
$color   = $settings[ 'siotp_email_text_color' ];
$message = $settings[ 'siotp_email_message' ];
echo str_replace( '%% JXN_OTP %%', $otp, $message );
?>