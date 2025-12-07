<?php
$settings   = SignInWithOtp::opts();
$is_mobile  = SignInWithOtp::is_mobile_device();

include_once( 'partials/header.php' );
include_once( 'partials/email-content.php');
include_once( 'partials/footer.php' );
