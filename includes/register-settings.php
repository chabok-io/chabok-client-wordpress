<?php
/**
 * Adds the options page for the plugin.
 * The following code is heavily inspired from
 * Easy Digital Downloads plugin which is made by
 * Pippin Williamson and contributors.
 *
 * @package ChabokIO
 * @subpackage UI/Settings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds Chabok settings page to the menu.
 *
 * @see $chabok_options
 * @return void
 */
function chabok_add_to_menu() {
	add_menu_page(
		__( 'Chabok', 'chabok-io' ),
		__( 'Chabok', 'chabok-io' ),
		'manage_options',
		'chabok',
		'chabok_render_settings'
	);
}
add_action( 'admin_menu', 'chabok_add_to_menu' );

/**
 * Retrieves the options values.
 *
 * @return array
 */
function chabok_get_options() {
	$options = get_option( 'chabok_options' );

	if ( empty( $options ) ) {
		// Adds the default values to database.
		update_option( 'chabok_options', array(
			'app_id'			=> '',
			'web_key'			=> '',
			'api_key'			=> '',
			'webpush'			=> 'off',
			'vapid'				=> '',
			'env'				=> 'dev',
			'realtime'			=> 'off',
			'register_users'	=> 'off',
			'user_id_key'		=> 'email',
			'track_posts'		=> 'off',
			'track_search'		=> 'off',
			'track_commenting'	=> 'off',
			'rewrite_sw'		=> 'on',
		) );
	}

	return apply_filters( 'chabok_get_options', $options );
}

/**
 * Registers the options in WordPress
 * core and tells it how to render each
 * field.
 *
 * @return void
 */
function chabok_register_options() {
	if ( false === get_option( 'chabok_options' ) ) {
		add_option( 'chabok_options' );
	}

	foreach ( chabok_get_registered_options() as $tab => $settings ) {
		add_settings_section(
			'chabok_options_' . $tab,
			__return_null(),
			'__return_false',
			'chabok_options_' . $tab
		);

		foreach ( $settings as $option ) {
			$name = isset( $option['name'] ) ? $option['name'] : '';

			add_settings_field(
				'chabok_option[' . $option['id'] . ']',
				$name,
				function_exists( 'chabok_' . $option['type'] . '_callback' )
					? 'chabok_' . $option['type'] . '_callback' : 'chabok_option_fallback',
				'chabok_options_' . $tab,
				'chabok_options_' . $tab,
				array(
					'id'			=> isset( $option['id'] ) ? $option['id'] : null,
					'desc'			=> ! empty( $option['desc'] ) ? $option['desc'] : '',
					'name'			=> isset( $option['name'] ) ? $option['name'] : '',
					'section'		=> $tab,
					'size'			=> isset( $option['size'] ) ? $option['size'] : null,
					'options'		=> isset( $option['options'] ) ? $option['options'] : [],
					'std'			=> isset( $option['std'] ) ? $option['std'] : null,
					'input_class'	=> isset( $option['input_class'] ) ? $option['input_class'] : '',
					'multiple'		=> ( isset( $option['multiple'] ) && $option['multiple'] ),
				)
			);

			register_setting( 'chabok_options', 'chabok_options', 'chabok_options_sanitize' );
		}
	}
}
add_action( 'admin_init', 'chabok_register_options' );

/**
 * Returns the options tabs.
 *
 * @return array
 */
function chabok_get_tabs() {
	return array(
		'core'			=> sprintf( __( '%s Parameters', 'chabok-io' ), '<span class="dashicons dashicons-feedback"></span>' ),
		'attribution'	=> sprintf( __( '%s Attribution', 'chabok-io' ), '<span class="dashicons dashicons-admin-users"></span>' ),
		'tracking'		=> sprintf( __( '%s Tracking', 'chabok-io' ), '<span class="dashicons dashicons-chart-pie"></span>' ),
		'advanced'		=> sprintf( __( '%s Advanced', 'chabok-io' ), '<span class="dashicons dashicons-admin-tools"></span>' ),
	);
}

/**
 * Gets the available keys for user ID assigning.
 *
 * @return array
 */
function chabok_get_user_attribute_keys() {
	return apply_filters(
		'chabok_user_id_keys',
		array(
			'ID' => 'ID',
			'user_login' => __( 'Username', 'chabok-io' ),
			'user_email' => __( 'Email', 'chabok-io' ),
		)
	);
}

/**
 * Sanitizes and saves settings after submit.
 *
 * @param               array $input Options input
 * @return              array Sanitized options to be saved
 */
function chabok_options_sanitize( $input = array() ) {
	global $chabok_options;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = chabok_get_registered_options();
	$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'core';

	$input = $input ? $input : array();
	$input = apply_filters( 'chabok_options_' . $tab . '_sanitize', $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {

		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[ $tab ][ $key ]['type'] ) ? $settings[ $tab ][ $key ]['type'] : false;

		if ( $type ) {
			// Field type specific filter
			$input[ $key ] = apply_filters( 'chabok_options_sanitize_' . $type, $value, $key );
		}

		// General filter
		$input[ $key ] = apply_filters( 'chabok_options_sanitize', $value, $key );
	}


	// Loop through the whitelist and unset any that are empty for the tab being saved
	if ( ! empty( $settings[ $tab ] ) ) {
		foreach ( $settings[ $tab ] as $key => $value ) {

			// settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
			if ( is_numeric( $key ) ) {
				$key = $value['id'];
			}

			if ( $settings[$tab][ $key ][ 'type' ] == 'checkbox' && $settings[$tab][ $key ][ 'type' ] == 'multicheck' ) {
				if ( empty( $input[ $key ] ) ) {
					unset( $chabok_options[ $key ] );
				}
				if ( array_key_exists( $key, $input ) && $input[ $key ] === '-1' ) {
					unset( $chabok_options[ $key ] );
				}
			} else {
				if ( array_key_exists( $key, $input ) && empty( $input[ $key ] ) || ( array_key_exists( $key, $chabok_options ) && ! array_key_exists( $key, $input ) ) ) {
					unset( $chabok_options[ $key ] );
				}
			}
		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $chabok_options, $input );
	global $chabok_options;
	$chabok_options = $output;

	add_settings_error( 'chabok_options', '', __( 'Settings updated.', 'chabok-io' ), 'updated' );
	return $output;

}

/**
 * Returns the available options fields.
 *
 * @return array
 */
function chabok_get_registered_options() {
	return apply_filters( 'chabok_registered_options', array(
		'core'					=> apply_filters( 'chabok_core_options', array(
			'app_id'			=> array(
				'id'			=> 'app_id',
				'name'			=> __( 'App ID', 'chabok-io' ),
				'type'			=> 'text',
				'std'			=> '',
				'input_class'	=> 'code',
			),
			'web_key'			=> array(
				'id'			=> 'web_key',
				'name'			=> __( 'Web Key', 'chabok-io' ),
				'type'			=> 'text',
				'std'			=> '',
				'input_class'	=> 'code',
			),
			'api_key'			=> array(
				'id'			=> 'api_key',
				'name'			=> __( 'API Key', 'chabok-io' ),
				'type'			=> 'text',
				'std'			=> '',
				'desc'			=> sprintf( __( 'You can receive App ID, Web Key and API Key in <a href="%s">your Chabok panel &raquo; Settings &raquo; Platforms</a>.', 'chabok-io' ), 'https://chabok.io' ),
				'input_class'	=> 'code',
			),
			'webpush'			=> array(
				'id'			=> 'webpush',
				'name'			=> __( 'WebPush', 'chabok-io' ),
				'type'			=> 'radio',
				'std'			=> 'off',
				'options'		=> array(
					'on'		=> __( 'On', 'chabok-io' ),
					'off'		=> __( 'Off', 'chabok-io' ),
				),
				'desc'			=> __( 'Turn this option on if you want to send your users real-time push messages.', 'chabok-io' ),
			),
			'vapid'				=> array(
				'id'			=> 'vapid',
				'name'			=> __( 'VAPID Public Key', 'chabok-io' ),
				'type'			=> 'text',
				'std'			=> '',
				'desc'			=> sprintf( __( 'VAPID public key is required when you want to use WebPush. You can receive VAPID public key in <a href="%s">your Chabok panel &raquo; Settings &raquo; Platforms &raquo; Web &raquo; Config</a>', 'chabok-io' ), 'https://chabok.io' ),
				'input_class'	=> 'code',
			),
			'env'				=> array(
				'id'			=> 'env',
				'name'			=> __( 'Environment', 'chabok-io' ),
				'type'			=> 'radio',
				'std'			=> 'dev',
				'options'		=> array(
					'dev'		=> __( 'Development', 'chabok-io' ),
					'prod'		=> __( 'Production', 'chabok-io' ),
				),
				'desc'			=> __( 'Check the "Production" option when you are ready to blast off.', 'chabok-io' )
			),
			'realtime'			=> array(
				'id'			=> 'realtime',
				'name'			=> __( 'Real-time usage', 'chabok-io' ),
				'type'			=> 'radio',
				'std'			=> 'off',
				'options'		=> array(
					'on'		=> __( 'On', 'chabok-io' ),
					'off'		=> __( 'Off', 'chabok-io' ),
				),
				'desc'			=> __( "If it's on, users' online status and activity will be shown on your Chabok panel as soon as it happens.", 'chabok-io' ),
			),
		) ),
		'attribution'			=> apply_filters( 'chabok_attribution_options', array(
			'register_users'	=> array(
				'id'			=> 'register_users',
				'name'			=> __( 'Register logged-in users', 'chabok-io' ),
				'type'			=> 'radio',
				'options'		=> array(
					'on'		=> __( 'On', 'chabok-io' ),
					'off'		=> __( 'Off', 'chabok-io' ),
				),
				'std'			=> 'off',
				'desc'			=> __( 'Turn this on if you want logged-in users to be registered and tracked by Chabok.', 'chabok-io' ),
			),
			'user_id_key'		=> array(
				'id'			=> 'user_id_key',
				'name'			=> __( 'User attribution key', 'chabok-io' ),
				'type'			=> 'select',
				'options'		=> chabok_get_user_attribute_keys(),
				'std'			=> 'user_email',
				'desc'			=> __( 'Choose which one of the user attributes should be used for his/her attribution in Chabok.', 'chabok-io' ),
			),
		) ),
		'tracking'				=> apply_filters( 'chabok_tracking_options', array(
			'track_posts'		=> array(
				'id'			=> 'track_posts',
				'name'			=> __( 'Track posts', 'chabok-io' ),
				'type'			=> 'radio',
				'options'		=> array(
					'on'		=> __( 'On', 'chabok-io' ),
					'off'		=> __( 'Off', 'chabok-io' ),
				),
				'std'			=> 'off',
				'desc'			=> __( 'If enabled, each time a user views a post (in a single page), the behavior details will be sent to Chabok.', 'chabok-io' ),
			),
			'track_search'		=> array(
				'id'			=> 'track_search',
				'name'			=> __( 'Track searches', 'chabok-io' ),
				'type'			=> 'radio',
				'options'		=> array(
					'on'		=> __( 'On', 'chabok-io' ),
					'off'		=> __( 'Off', 'chabok-io' ),
				),
				'std'			=> 'off',
				'desc'			=> __( 'You can track search queries, number of results and the clicked result.', 'chabok-io' ),
			),
			'track_commenting'	=> array(
				'id'			=> 'track_commenting',
				'name'			=> __( 'Track comments', 'chabok-io' ),
				'type'			=> 'radio',
				'options'		=> array(
					'on'		=> __( 'On', 'chabok-io' ),
					'off'		=> __( 'Off', 'chabok-io' ),
				),
				'std'			=> 'off',
				'desc'			=> __( 'Track users who leave a comment.', 'chabok-io' ),
			),
		) ),
		'advanced'				=> apply_filters( 'chabok_advanced_options', array(
			'rewrite_sw'		=> array(
				'id'			=> 'rewrite_sw',
				'name'			=> __( 'Rewrite ServiceWorker', 'chabok-io' ),
				'type'			=> 'radio',
				'options'		=> array(
					'on'		=> __( 'On', 'chabok-io' ),
					'off'		=> __( 'Off', 'chabok-io' ),
				),
				'std'			=> 'on',
				'desc'			=> sprintf( __( 'Turn this option off if you use your web server to rewrite ChabokSDKWorker.js file on your website root. <a href="%s">Learn more</a>', 'chabok-io' ), '#' ),
			),
			'forget_all'		=> array(
				'id'			=> 'forget_all',
				'name'			=> __( 'Forget saved devices and users', 'chabok-io' ),
				'type'			=> 'forget_button',
			),
		) ),
	) );
}

/**
 * Header option rendering callback.
 *
 * @param array $args
 * @return void
 */
function chabok_header_callback( $args ) {
	echo '<hr>';
}

/**
 * Check box option rendering callback.
 *
 * @param array $args
 * @return void
 */
function chabok_checkbox_callback( $args ) {
	global $chabok_options;

	$checked = isset( $chabok_options[ $args['id'] ] ) ? checked( 1, $chabok_options[ $args['id'] ], false ) : '';
	$html    = '<input type="checkbox" id="chabok_options[' . $args['id'] . ']" name="chabok_options[' . $args['id'] . ']" value="1" ' . $checked . '/>';
	$html    .= '<label for="chabok_options[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Radio option rendering callback.
 *
 * @param array $args
 * @return void
 */
function chabok_radio_callback( $args ) {
	global $chabok_options;

	foreach ( $args['options'] as $key => $option ) :
		$checked = false;

		if ( isset( $chabok_options[ $args['id'] ] ) && $chabok_options[ $args['id'] ] == $key ) {
			$checked = true;
		} elseif ( isset( $args['std'] ) && $args['std'] == $key && ! isset( $chabok_options[ $args['id'] ] ) ) {
			$checked = true;
		}

		echo '<input name="chabok_options[' . $args['id'] . ']"" id="chabok_options[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked( true, $checked, false ) . '/>';
		echo '<label for="chabok_options[' . $args['id'] . '][' . $key . ']">' . $option . '</label>&nbsp;&nbsp;';
	endforeach;

	echo '<p class="description">' . $args['desc'] . '</p>';
}

/**
 * Text field rendering callback.
 *
 * @param array $args
 * @return void
 */
function chabok_text_callback( $args ) {
	global $chabok_options;

	if ( isset( $chabok_options[ $args['id'] ] ) ) {
		$value = $chabok_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text ' . ( $args['input_class'] ? $args['input_class'] : '' ) . '" id="chabok_options[' . $args['id'] . ']" name="chabok_options[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<p class="description"> ' . $args['desc'] . '</p>';

	echo $html;
}

/**
 * Textarea field rendering callback.
 *
 * @param array $args
 * @return void
 */
function chabok_textarea_callback( $args ) {
	global $chabok_options;

	if ( isset( $chabok_options[ $args['id'] ] ) ) {
		$value = $chabok_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = '<textarea class="large-text" cols="50" rows="5" id="chabok_options[' . $args['id'] . ']" name="chabok_options[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="chabok_options[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @param array $args Arguments passed by the setting
 * @return void
 */
function chabok_select_callback($args) {
	global $chabok_options;

	$chabok_option = isset( $chabok_options[ $args['id'] ] ) ? $chabok_options[ $args['id'] ] : null;

	if ( $chabok_option ) {
		$value = $chabok_option;
	} else {

		// Properly set default fallback if the Select Field allows Multiple values
		if ( empty( $args['multiple'] ) ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		} else {
			$value = ! empty( $args['std'] ) ? $args['std'] : array();
		}

	}

	// If the Select Field allows Multiple values, save as an Array
	$name_attr = 'chabok_options[' . esc_attr( $args['id'] ) . ']';
	$name_attr = ( $args['multiple'] ) ? $name_attr . '[]' : $name_attr;

	$html = '<select id="chabok_options[' . $args['id'] . ']" name="' . $name_attr . '" class="" ' . ( ( $args['multiple'] ) ? 'multiple="true"' : '' ) . '>';

	foreach ( $args['options'] as $option => $name ) {

		if ( ! $args['multiple'] ) {
			$selected = selected( $option, $value, false );
			$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
		} else {
			// Do an in_array() check to output selected attribute for Multiple
			$html .= '<option value="' . esc_attr( $option ) . '" ' . ( ( in_array( $option, $value ) ) ? 'selected="true"' : '' ) . '>' . esc_html( $name ) . '</option>';
		}

	}

	$html .= '</select>';
	$html .= '<p class="description">' . wp_kses_post( $args['desc'] ) . '</p>';

	echo $html;
}

/**
 * Renders a "forget all" button for certain
 * situations.
 *
 * @return void
 */
function chabok_forget_button_callback() {
	?>
	<a href="<?php echo wp_nonce_url( add_query_arg( 'chabok_reset_all', 1 ), 'chabok_reset_all' ); ?>" onclick="return confirm('<?php _e( 'Are you sure? This is irreversible!', 'chabok-io' ); ?>')" class="button"><?php _e( 'Forget all', 'chabok-io' ); ?></a>
	<p class="description"><?php _e( 'Use this button only for certain situations that you need to clear all devices and their corresponding users IDs from WordPress database. This is useful for testing and/or debugging.', 'chabok-io' ); ?></p>
	<?php
}

/**
 * Rendering callback for malicious options.
 *
 * @param array $args
 * @return void
 */
function chabok_option_fallback( $args ) {
	return '&mdash;';
}

/**
 * Renders the options page.
 *
 * @return void
 */
function chabok_render_settings() {
	$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], chabok_get_tabs() ) ? $_GET['tab'] : 'core';

	ob_start();
	?>
	<div class="wrap chabok-settings-wrap">
		<p style="text-align: center;">
			<a href="https://chabok.io/" target="_blank">
				<img style="height: 64px;" src="<?php echo CHABOK_URL; ?>/assets/chabok-logo.svg" alt="<?php _e( 'Chabok logo', 'chabok-io' ); ?>">
			</a>
		</p>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( chabok_get_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab'              => $tab_id
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab' . $active . '">';
				echo $tab_name;
				echo '</a>';
			}
			?>
		</h2>
		<?php echo settings_errors( 'chabok_options' ); ?>
		<?php if ( $active_tab === 'tracking' ) { ?>
			<p><?php echo sprintf( __( 'You can set up the behaviors you want to be tracked and sent to Chabok. You can use <a href="%s">extensions</a> to add the support of more behaviors.', 'chabok-io' ), 'https://doc.chabok.io/wordpress/extensions.html' ); ?></p>
		<?php } ?>
		<div id="tab_container">
			<form method="post" action="options.php">
				<table class="form-table">
					<?php
						settings_fields( 'chabok_options' );
						do_settings_fields( 'chabok_options_' . $active_tab, 'chabok_options_' . $active_tab );
					?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div><!-- #tab_container-->

		<?php if ( $active_tab === 'advanced' ) { ?>
			<div class="notice error settings-error">
				<p>
					<?php echo sprintf( __( 'You are in <b>Advanced</b> tab. Misconfiguring the options below might cause malfunction or data inconsistency between your website and Chabok. Please proceed with care. You can <a href="%s">contact the support</a> if you are unsure.', 'chabok-io' ), 'https://chabok.io/contact.html' ); ?>
				</p>
			</div>
		<?php } else { ?>
			<div class="notice settings-error">
				<p><?php echo sprintf( __( 'If you are facing problems or having questions about Chabok, you can <a href="%s">call the support</a>.', 'chabok-io' ), 'https://chabok.io/contact.html' ); ?>
			</div>
		<?php } ?>
	</div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}
