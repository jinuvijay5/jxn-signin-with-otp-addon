<?php
/**
 * email header
 *
 * @version	1.0.0
 * @author Jinesh P.V
 */
if ( ! defined( 'ABSPATH' ) ) exit; 

if( $is_mobile ) {
	$wrapper = "
		background-color:".$settings['siotp_email_body_background_color'].";
		width:100%;
		-webkit-text-size-adjust:none !important;
		margin:0;
		padding: 20px 0 20px 0;
	";
} else {
	$wrapper = "
		background-color:".$settings['siotp_email_body_background_color'].";
		width:100%;
		-webkit-text-size-adjust:none !important;
		margin:0;
		padding: 70px 0 70px 0;
	";
}
if( $is_mobile ) {
	$template_container = "
		background-color: #fafafa;
		max-width: 100%;
	";
} else {
	$template_container = "
		background-color: #fafafa;
		max-width: 80%;
	";
}
$template_header = "
	background-color: ".$settings['siotp_email_base_color'].";
	color: #f1f1f1;
	border-bottom: 0;
	font-family:Arial;
	font-weight:bold;
	line-height:100%;
	vertical-align:middle;
";
$body_content = "
	background-color: ".$settings['siotp_email_background_color'].";
";
$body_content_inner = "
	color: ".$settings[ 'siotp_email_text_color' ].";
	font-family:Arial;
	font-size:14px;
	line-height:150%;
	text-align:left;
";
$header_content_h1 = "
	color: #ffffff;
	margin:0;
	padding: 30px 30px 30px 30px;
	display:block;
	font-family:Arial;
	font-size: 42px;
	font-weight:bold;
	text-align:left;
	line-height: 42px;
";
$header_content_h1_a = "
	color: #ffffff;
	text-decoration: none;
";
?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=<?php echo get_bloginfo( 'charset' );?>" />
        <title><?php echo get_bloginfo( 'name' ); ?></title>
	    <style type="text/css">
		    #template_body a {
			    color: #e25358;
		    }
	    </style>
	</head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
    	<div id="body" style="<?php echo $wrapper; ?>">
        	<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%">
            	<tr>
                	<td align="center" valign="top">
                    	<table border="0" cellpadding="0" cellspacing="0"  id="template_container" style="<?php echo $template_container; ?>">
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Header -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" style="<?php echo $template_header; ?>">
                                        <tr>
                                            <td>
                                            	<h1 style="<?php echo $header_content_h1; ?>" id="logo">
		                                            <a style="<?php echo $header_content_h1_a;?>" href="<?php echo home_url();?>" title="<?php echo get_bloginfo( 'name' );?>"><?php
		                                            if( ! empty( $settings['siotp_email_header_image'] ) ) {
			                                            echo '<img style="max-width:100%;height: 60px;" src="' . $settings['siotp_email_header_image'] . '" alt="' . get_bloginfo( 'name' ) .'"/>';
		                                            } else {
														echo get_bloginfo( 'name' );
		                                            } ?>
		                                            </a>
	                                            </h1>

                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Header -->
                                </td>
                            </tr>
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Body -->
                                	<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body">
                                    	<tr>
                                            <td valign="top" style="<?php echo $body_content; ?>">
                                                <!-- Content -->
                                                <table border="0" cellpadding="30" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td valign="top">
                                                            <div style="<?php echo $body_content_inner; ?>">