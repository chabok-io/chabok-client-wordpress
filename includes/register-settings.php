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
			'webpush'			=> 'off',
			'vapid'				=> '',
			'env'				=> 'dev',
			'welcome_enabled'	=> 'off',
			'welcome_message'	=> '',
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
					'id'		=> isset( $option['id'] ) ? $option['id'] : null,
					'desc'		=> ! empty( $option['desc'] ) ? $option['desc'] : '',
					'name'		=> isset( $option['name'] ) ? $option['name'] : '',
					'section'	=> $tab,
					'size'		=> isset( $option['size'] ) ? $option['size'] : null,
					'options'	=> isset( $option['options'] ) ? $option['options'] : [],
					'std'		=> isset( $option['std'] ) ? $option['std'] : null,
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
		'core'		=> sprintf( __( '%s Parameters', 'chabok-io' ), '<span class="dashicons dashicons-feedback"></span>' ),
		'tracker'	=> sprintf( __( '%s Tracker', 'chabok-io' ), '<span class="dashicons dashicons-chart-pie"></span>' ),
		'welcome'	=> sprintf( __( '%s Welcome message', 'chabok-io' ), '<span class="dashicons dashicons-share-alt"></span>' ),
	);
}

/**
 * Sanitizes and saves settings after submit.
 *
 * @param               array $input Options input
 * @return              array Sanitized options to be saved
 */
function chabok_settings_sanitize( $input = array() ) {
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

	add_settings_error( 'chabok-notices', '', __( 'Settings updated.', 'chabok-io' ), 'updated' );
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
			),
			'web_key'			=> array(
				'id'			=> 'web_key',
				'name'			=> __( 'Web Key', 'chabok-io' ),
				'type'			=> 'text',
				'std'			=> '',
				'desc'			=> sprintf( __( 'You can receive App ID and Web Key in <a href="%s">your Chabok panel</a>', 'chabok-io' ), 'https://chabok.io' ),
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
				'desc'			=> __( 'Turn this option on if you want to send your users real-time push messsages.', 'chabok-io' ),
			),
			'vapid'				=> array(
				'id'			=> 'vapid',
				'name'			=> __( 'VAPID Public Key', 'chabok-io' ),
				'type'			=> 'text',
				'std'			=> '',
				'desc'			=> sprintf( __( 'VAPID public key is required when you want to use WebPush. You can receive VAPID public key in <a href="%s">your Chabok panel</a>', 'chabok-io' ), 'https://chabok.io' ),
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
		) ),
		'tracker'				=> apply_filters( 'chabok_tracker_options', array(
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
		) ),
		'welcome'				=> apply_filters( 'chabok_welcome_options', array(
			'welcome_enabled'	=> array(
				'id'			=> 'welcome_enabled',
				'name'			=> __( 'Welcome prompt', 'chabok-io' ),
				'type'			=> 'radio',
				'options'		=> array(
					'on'		=> __( 'On', 'chabok-io' ),
					'off'		=> __( 'Off', 'chabok-io' ),
				),
				'std'			=> 'off',
				'desc'			=> __( 'You can set whether you\'d like a welcome prompt being shown to your website visitors.', 'chabok-io' ),
			),
			'welcome_message'	=> array(
				'id'			=> 'welcome_message',
				'name'			=> __( 'Welcome message', 'chabok-io' ),
				'type'			=> 'text',
				'desc'			=> __( 'The content of the welcome prompt', 'chabok-io' ),
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
	$html = '<input type="text" class="' . $size . '-text" id="chabok_options[' . $args['id'] . ']" name="chabok_options[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
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
		<h2><?php _e( 'Chabok Options', 'chabok-io' ) ?></h2>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach ( chabok_get_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'settings-updated' => false,
					'tab'              => $tab_id
				) );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
				echo $tab_name;
				echo '</a>';
			}
			?>
		</h2>
		<?php settings_errors( 'chabok-notices' ); ?>
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
	</div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}
