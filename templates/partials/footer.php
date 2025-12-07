<?php
/**
 * email footer
 *
 * @version	1.0.0
 * @author JWC Extentions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$template_footer = "
	border-top:1px solid #E2E2E2;
	background: " . $settings['siotp_email_base_color'] . ";
";

$credit = "
	border:0;
	color: " . $settings[ 'siotp_email_text_color' ] . ";
	font-family: Arial;
	font-size: 14px;
	line-height:125%;
	text-align: left;
";
?>
                                            				</div>
                                            			</td>
                                                    </tr>
                                                </table>
                                                <!-- End Content -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Body -->
                                </td>
                            </tr>
                        	<tr>
                            	<td align="center" valign="top">
                                    <!-- Footer -->
                                	<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer" style="<?php echo $template_footer; ?>">
                                    	<tr>
                                        	<td valign="top">
                                                <table border="0" cellpadding="10" cellspacing="0" width="100%" style="padding: 12px;">
                                                    <tr>
                                                        <td colspan="2" valign="middle" id="credit" style="<?php echo $credit; ?>">
                                                            <?php
                                                            if( ! empty( $settings['siotp_email_header_image'] ) ) {
                                                                echo '<img style="max-width:100%;height: 30px;display: block;margin-bottom: 10px;" src="' . $settings['siotp_email_header_image'] . '" alt="' . get_bloginfo( 'name' ) . '"/>';
                                                            } else {
                                                                echo get_bloginfo( 'name');
                                                            } ?>
                                                        	<?php echo apply_filters( 'siotp_footer_text', do_shortcode( $settings['siotp_email_footer_text'] ) ); ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- End Footer -->
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
    </body>
</html>