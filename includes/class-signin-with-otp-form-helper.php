<?php
/**
 * SIOTP_Form_Helper setup
 *
 * @package SIOTP_Form_Helper
 * @version   1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Class SIOTP_Form_Helper
 *
 * This class serves as a helper for handling forms related to SIOTP.
 * It encapsulates functions and methods for creating, validating, and processing form data specific to SIOTP.
 * Developers can use this class to streamline form handling processes and ensure consistency across SIOTP-related forms.
 */
class SIOTP_Form_Helper {
	/**
	 * Plugin basename.
	 *
	 * @version  1.0.0
	 *
	 * @var      string
	 */
	protected $options;

	/**
	 * Constructor method for the class.
	 * 
	 * This method is called automatically when an instance of the class is created.
	 * It initializes the object and sets up any necessary configurations or dependencies.
	 */
	public function __construct() {
		add_action( 'siotp_settings_save', array( $this, 'save_fields' ), 3, 100 );
	}

	/**
	 * Creates a form with the specified options.
	 *
	 * This function accepts an array of options as a parameter and uses
	 * those options to generate and customize the form.
	 *
	 * @param array $options An array containing options for customizing the form.
	 * @return void
	 */
	public function create_form( $options ) {
		$this->options = $options;

		foreach ($this->options as $value) {

			if (!isset($value['type'])) {
				continue;
			}
			if (!isset($value['id'])) {
				$value['id'] = '';
			}
			if (!isset($value['title'])) {
				$value['title'] = isset($value['name']) ? $value['name'] : '';
			}
			if (!isset($value['class'])) {
				$value['class'] = '';
			}
			if (!isset($value['css'])) {
				$value['css'] = '';
			}
			if (!isset($value['default'])) {
				$value['default'] = '';
			}
			if (!isset($value['desc'])) {
				$value['desc'] = '';
			}
			if (!isset($value['desc_tip'])) {
				$value['desc_tip'] = false;
			}
			if (!isset($value['placeholder'])) {
				$value['placeholder'] = '';
			}
			if (!isset($value['suffix'])) {
				$value['suffix'] = '';
			}
			if (!isset($value['value'])) {
				$value['value'] = self::get_option($value['id'], $value['default']);
			}

			// Custom attribute handling.
			$custom_attributes = array();

			if (!empty($value['custom_attributes']) && is_array($value['custom_attributes'])) {
				foreach ($value['custom_attributes'] as $attribute => $attribute_value) {
					$custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
				}
			}

			// Description handling.
			if ($value['desc_tip']) {
				if (!empty($value['desc'])) {
					$description = $value['desc'];
				} else {
					$description = '';
				}

			}

			// Switch based on type.
			switch ($value['type']) {

			// Section Titles.
			case 'title':
				if (!empty($value['title'])) {
					echo '<h2>' . esc_html($value['title']) . '</h2>';
				}
				if (!empty($value['desc'])) {
					echo '<div id="' . esc_attr(sanitize_title($value['id'])) . '-description">';
					echo wp_kses_post(wpautop(wptexturize($value['desc'])));
					echo '</div>';
				}
				echo '<table class="form-table">' . "\n\n";
				if (!empty($value['id'])) {
					do_action('siotp_settings_' . sanitize_title($value['id']));
				}
				break;

			// Section Ends.
			case 'sectionend':
				if (!empty($value['id'])) {
					do_action('siotp_settings_' . sanitize_title($value['id']) . '_end');
				}
				echo '</table>';
				if (!empty($value['id'])) {
					do_action('siotp_settings_' . sanitize_title($value['id']) . '_after');
				}
				break;

			// Standard text inputs and subtypes like 'number'.
			case 'text':
			case 'password':
			case 'date':
			case 'month':
			case 'time':
			case 'week':
			case 'number':
			case 'email':
			case 'url':
			case 'tel':
				$option_value = $value['value'];

				?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?><?php echo $this->jxn_help_tip( $description ); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
							<input
								name="<?php echo esc_attr($value['id']); ?>"
								id="<?php echo esc_attr($value['id']); ?>"
								type="<?php echo esc_attr($value['type']); ?>"
								style="<?php echo esc_attr($value['css']); ?>"
								value="<?php echo esc_attr($option_value); ?>"
								class="<?php echo esc_attr($value['class']); ?>"
								placeholder="<?php echo esc_attr($value['placeholder']); ?>"
								<?php echo implode(' ', $custom_attributes); // WPCS: XSS ok.       ?>
								/><?php echo esc_html($value['suffix']); ?>
						</td>
					</tr>
					<?php
				break;

			case 'upload':
				$option_value = $value['value'];

				?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?><?php echo $this->jxn_help_tip( $description ); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
							<div class="input-wrapper">
								<input
									name="<?php echo esc_attr($value['id']); ?>"
									id="<?php echo esc_attr($value['id']); ?>"
									type="text"
									style="<?php echo esc_attr($value['css']); ?>"
									value="<?php echo esc_attr($option_value); ?>"
									class="<?php echo esc_attr($value['class']); ?>"
									placeholder="<?php echo esc_attr($value['placeholder']); ?>"
									<?php echo implode(' ', $custom_attributes); // WPCS: XSS ok.       ?>
									/><input type="button" name="<?php echo esc_attr($value['id']); ?>_upload" id="<?php echo esc_attr($value['id']); ?>_upload" value="Upload" class="<?php echo esc_attr($value['btn_class']); ?>" />
							</div>
						</td>
					</tr>
					<?php
				break;

			case 'key':
				$option_value = $value['value'];

				?><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?><?php echo $this->jxn_help_tip( $description ); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
							<input
								name="<?php echo esc_attr($value['id']); ?>_mask"
								id="<?php echo esc_attr($value['id']); ?>_mask"
								type="text"
								style="<?php echo esc_attr($value['css']); ?>"
								value="<?php echo $this->obfuscate_string($option_value, '*'); ?>"
								class="<?php echo esc_attr($value['class']); ?>"
								placeholder="<?php echo esc_attr($value['placeholder']); ?>"
								<?php echo implode(' ', $custom_attributes); // WPCS: XSS ok.       ?>
								/><input type="hidden" name="<?php echo esc_attr($value['id']); ?>" value="" />
						</td>
					</tr>
					<?php
				break;		

			// Color picker.
			case 'color':
				$option_value = $value['value'];

				?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?><?php echo $this->jxn_help_tip( $description ); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">&lrm;
							<span class="colorpickpreview" style="background: <?php echo esc_attr($option_value); ?>">&nbsp;</span>
							<input
								name="<?php echo esc_attr($value['id']); ?>"
								id="<?php echo esc_attr($value['id']); ?>"
								type="text"
								dir="ltr"
								style="<?php echo esc_attr($value['css']); ?>"
								value="<?php echo esc_attr($option_value); ?>"
								class="<?php echo esc_attr($value['class']); ?>colorpick"
								placeholder="<?php echo esc_attr($value['placeholder']); ?>"
								<?php echo implode(' ', $custom_attributes); // WPCS: XSS ok.       ?>
								/>
								<div id="colorPickerDiv_<?php echo esc_attr($value['id']); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
						</td>
					</tr>
					<?php
				break;

			// Textarea.
			case 'textarea':
				$option_value = $value['value'];

				?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?><?php echo $this->jxn_help_tip( $description ); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
							<textarea
								name="<?php echo esc_attr($value['id']); ?>"
								id="<?php echo esc_attr($value['id']); ?>"
								style="<?php echo esc_attr($value['css']); ?>"
								class="<?php echo esc_attr($value['class']); ?>"
								placeholder="<?php echo esc_attr($value['placeholder']); ?>"
								<?php echo implode(' ', $custom_attributes); // WPCS: XSS ok.       ?>
								><?php echo esc_textarea($option_value); // WPCS: XSS ok.       ?></textarea>
						</td>
					</tr>
					<?php
				break;

			// WP Rich editor.
			case 'wp_editor':
				$option_value       = $value['value'];
				$custom_editor_id   = $value['id'];
				$custom_editor_name = $value['id'];

				$args = array(
					'media_buttons' => isset( ( $value['media'] ) ) ? esc_attr( $value['media'] ) : '', // This setting removes the media button.
					'textarea_name' => $custom_editor_name, // Set custom name.
					'textarea_rows' => isset( ( $value['rows'] ) ) ? esc_attr( $value['rows'] ) : '', //Determine the number of rows.
					'quicktags'     => isset( ( $value['quicktags'] ) ) ? esc_attr( $value['quicktags'] ) : '', // Remove view as HTML button.
				);
				?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?><?php echo isset( $description ) && $description ? $this->jxn_help_tip( $description ) : ""; // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<?php wp_editor( $option_value, esc_attr( $custom_editor_id ), $args ); ?>
						</td>
					</tr>
					<?php
					break;		

			// Select boxes.
			case 'select':
			case 'multiselect':
				$option_value = $value['value'];

				?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?><?php echo $this->jxn_help_tip( $description ); // WPCS: XSS ok. ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
							<select
								name="<?php echo esc_attr($value['id']); ?><?php echo ('multiselect' === $value['type']) ? '[]' : ''; ?>"
								id="<?php echo esc_attr($value['id']); ?>"
								style="<?php echo esc_attr($value['css']); ?>"
								class="<?php echo esc_attr($value['class']); ?>"
								<?php echo implode(' ', $custom_attributes); // WPCS: XSS ok.       ?>
								<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
								>
								<?php
								foreach ($value['options'] as $key => $val) {
									?>
									<option value="<?php echo esc_attr($key); ?>"
										<?php
										if (is_array($option_value)) {
											selected(in_array((string) $key, $option_value, true), true);
										} else {
											selected($option_value, (string) $key);
										}

										?>
									><?php echo esc_html($val); ?></option>
									<?php
								}
								?>
							</select>
						</td>
					</tr>
					<?php
					break;

			// Radio inputs.
			case 'radio':
				$option_value = $value['value'];

				?>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
						</th>
						<td class="forminp forminp-<?php echo esc_attr(sanitize_title($value['type'])); ?>">
							<fieldset>
								<?php echo $description; // WPCS: XSS ok.       ?>
								<ul>
								<?php
								foreach ($value['options'] as $key => $val) {
									?>
									<li>
										<label><input
											name="<?php echo esc_attr($value['id']); ?>"
											value="<?php echo esc_attr($key); ?>"
											type="radio"
											style="<?php echo esc_attr($value['css']); ?>"
											class="<?php echo esc_attr($value['class']); ?>"
											<?php echo implode(' ', $custom_attributes); // WPCS: XSS ok.       ?>
											<?php checked($key, $option_value);?>
											/> <?php echo esc_html($val); ?></label>
									</li>
									<?php
								}
								?>
								</ul>
							</fieldset>
						</td>
					</tr>
					<?php
				break;

			// Checkbox input.
			case 'checkbox':
				$option_value     = $value['value'];
				$visibility_class = array();

				if (!isset($value['hide_if_checked'])) {
					$value['hide_if_checked'] = false;
				}
				if (!isset($value['show_if_checked'])) {
					$value['show_if_checked'] = false;
				}
				if ('yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked']) {
					$visibility_class[] = 'hidden_option';
				}
				if ('option' === $value['hide_if_checked']) {
					$visibility_class[] = 'hide_options_if_checked';
				}
				if ('option' === $value['show_if_checked']) {
					$visibility_class[] = 'show_options_if_checked';
				}

				if (!isset($value['checkboxgroup']) || 'start' === $value['checkboxgroup']) {
					?>
					<tr valign="top" class="<?php echo esc_attr(implode(' ', $visibility_class)); ?>">
						<th scope="row" class="titledesc"><label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label></th>
						<td class="forminp forminp-checkbox">
							<fieldset>
							<?php
				} else {
					?>
					<fieldset class="<?php echo esc_attr(implode(' ', $visibility_class)); ?>">
						<?php
				}

				if ( ! empty( $value['title'] ) ) {
					?>
					<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
					<?php
				}
				?>
						<label for="<?php echo esc_attr( $value['id'] ); ?>" class="custom-checkbox">
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
								value="1"
								<?php checked( $option_value, 'yes' );?>
								<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
							/> <?php echo $description; // WPCS: XSS ok. ?><span class="checkmark"></span>
						</label>
					<?php

				if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
					?>
									</fieldset>
								</td>
							</tr>
						<?php
				} else {
					?>
					</fieldset>
						<?php
				}
				break;

			// Default: run an action.
			default:
				do_action( 'siotp_admin_field_' . $value['type'], $value );
				break;
			}
		}
	}

	/**
	 * Retrieves the value of the specified option from the database.
	 *
	 * This function fetches the value of the option with the given name from the WordPress
	 * options table in the database. If the option does not exist, it returns the default value
	 * provided as the second parameter.
	 *
	 * @param string $option_name The name of the option to retrieve.
	 * @param mixed $default      Optional. The default value to return if the option does not exist. Default is an empty string.
	 * @return mixed              The value of the option, or the default value if the option does not exist.
	 */
	public static function get_option( $option_name, $default = '' ) {
		if ( ! $option_name ) {
			return $default;
		}

		// Array value.
		if ( strstr( $option_name, '[' ) ) {
			parse_str( $option_name, $option_array );
			$option_name = current( array_keys( $option_array ) );
			$option_values = get_option( $option_name, '' );
			$key = key( $option_array[$option_name] );

			if ( isset( $option_values[$key] ) ) {
				$option_value = $option_values[$key];
			} else {
				$option_value = null;
			}
		} else {
			$option_value = get_option( $option_name, null );
		}

		if ( is_array( $option_value ) ) {
			$option_value = array_map('stripslashes', $option_value);
		} elseif ( !is_null( $option_value ) ) {
			$option_value = stripslashes( $option_value );
		}

		return ( null === $option_value ) ? $default : $option_value;
	}

	/**
	 * Saves fields with provided options and data.
	 *
	 * Loops though the woocommerce options array and outputs each field.
	 *
	 * @param array $options An array containing options for saving fields.
	 * @param array $data    Optional. Data to use for saving. Defaults to $_POST.
	 * @return bool
	 */
	public function save_fields( $options, $current_tab, $data = null ) {
		if ( empty( $data ) ) {
			$data = isset( $_POST ) ? wp_unslash( $_POST ) : null; // WPCS: input var okay, CSRF ok.
		}
		if ( empty( $data ) ) {
			return false;
		}		

		// Options to update will be stored here and saved later.
		$update_options   = array();
		$autoload_options = array();

		// Loop options and get values to save.
		if( $options ) {
			foreach ( $options as $option ) {
				if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) || ( isset( $option['is_option'] ) && false === $option['is_option'] ) ) {
					continue;
				}

				$option_name = $option['field_name'] ?? $option['id'];

				// Get posted value.
				if ( strstr( $option['id'], '[' ) ) {
					parse_str( $option['id'], $option_name_array );
					$option_name  = current( array_keys( $option_name_array ) );
					$setting_name = key( $option_name_array[ $option_name ] );
				
					// Safely get submitted value or fall back to existing if not sent
					if ( isset( $data[ $option_name ][ $setting_name ] ) ) {
						$raw_value = wp_unslash( $data[ $option_name ][ $setting_name ] );
					} else {
						// For checkboxes: missing from POST means unchecked
						if ( $option['type'] === 'checkbox' ) {
							$raw_value = 'no';
						} else {
							$raw_value = get_option( $option_name );
							$raw_value = is_array( $raw_value ) && isset( $raw_value[ $setting_name ] ) ? $raw_value[ $setting_name ] : null;
						}
					}
				} else {
					$setting_name = '';
					$option_name  = $option['id'];
					$current      = get_option( $option_name );
				
					if ( isset( $data[ $option['id'] ] ) ) {
						$raw_value = wp_unslash( $data[ $option['id'] ] );
					} else {
						if ( $option['type'] === 'checkbox' ) {
							$raw_value = 'no';
						} else {
							$raw_value = $current;
						}
					}
				}
				
				
				// Format the value based on option type.
				switch ( $option['type'] ) {
					case 'checkbox':
						$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
						break;
					case 'textarea':
						$value = wp_kses_post(trim($raw_value));
						break;
					case 'select':
						$allowed_values = empty( $option['options'] ) ? array() : array_map( 'strval', array_keys( $option['options'] ) );
						
						if ( empty( $option['default'] ) && empty( $allowed_values ) ) {
							$value = null;
							break;
						}
						$default = ( empty( $option['default'] ) ? $allowed_values[0] : $option['default'] );
						$value   = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;
						break;
					case 'key':	
						$value = isset( $data[$option['id'] . '_mask'] ) ? $data[$option['id'] . '_mask'] : null;
						if( preg_match( '/\*/', $value ) ) {
							$value = null;
						}
						break;
					default:
						$value = $raw_value;
						break;				
				}	

				/**
				 * Sanitize the value of an option.
				 *
				 * @since 1.0.0
				 */
				$value = apply_filters( 'siotp_admin_settings_sanitize_option', $value, $option, $raw_value );

				/**
				 * Sanitize the value of an option by option name.
				 *
				 * @since 1.0.0
				 */
				$value = apply_filters( "siotp_admin_settings_sanitize_option_$option_name", $value, $option, $raw_value );

				if ( is_null( $value ) ) {
					continue;
				}

				// Check if option is an array and handle that differently to single values.
				if ( $option_name && $setting_name ) {
					if ( ! isset( $update_options[$option_name] ) ) {
						$update_options[$option_name] = get_option( $option_name, array() );
					}
					if ( ! is_array( $update_options[$option_name] ) ) {
						$update_options[$option_name] = array();
					}
					$update_options[$option_name][$setting_name] = $value;
				} else {
					$update_options[$option_name] = $value;
				}

				$autoload_options[$option_name] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;
			}
		}

		if( $update_options ) {
			foreach ( $update_options as $name => $value ) {
				update_option( $name, $value, $autoload_options[$name] ? 'yes' : 'no' );
			}
		}
		
		// wp_redirect( esc_url_raw( $data[ '_wp_http_referer' ] . '&status=settings-updated' ) );
		// exit;
	}

	/**
	 * Takes a string and obfuscates it by replacing all occurrences of a specified character with another character.
	 *
	 * @param string $string The original string to obfuscate.
	 * @param string $char The character to replace in the string.
	 * @return string The obfuscated string with the specified character replaced.
	 */
	public function obfuscate_string( $string, $char ) {
		$length            = strlen( $string );
		$obfuscated_length = ceil( $length / 2 );
		$string            = str_repeat( $char, $obfuscated_length ) . substr( $string, $obfuscated_length );
		
		return $string;
	}

	/**
	 * Display a JXN help tip.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $tip        Help tip text.
	 * @param  bool   $allow_html Allow sanitized HTML if true or escape.
	 * @return string
	 */
	public static function jxn_help_tip( $tip, $allow_html = false ) {
		if ( $allow_html ) {
			$sanitized_tip = htmlspecialchars(
					wp_kses(
						html_entity_decode( $tip ?? '' ),
						array(
							'br'     => array(),
							'em'     => array(),
							'strong' => array(),
							'small'  => array(),
							'span'   => array(),
							'ul'     => array(),
							'li'     => array(),
							'ol'     => array(),
							'p'      => array(),
						)
					)
				);
		} else {
			$sanitized_tip = esc_attr( $tip );
		}

		/**
		 * Filter the help tip.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tip_html       Help tip HTML.
		 * @param string $sanitized_tip  Sanitized help tip text.
		 * @param string $tip            Original help tip text.
		 * @param bool   $allow_html     Allow sanitized HTML if true or escape.
		 *
		 * @return string
		 */
		return apply_filters( 'jxn_help_tip', '<span class="jxn-help-tip" tabindex="0" aria-label="' . $sanitized_tip . '" data-tip="' . $sanitized_tip . '"></span>', $sanitized_tip, $tip, $allow_html );
	}
}
