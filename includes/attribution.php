<?php
/**
 * Attribution, registering and tracking
 * users behavior.
 *
 * @package ChabokIO
 * @subpackage Attribution
 */

/**
 * Saves the received deviceId in the current
 * session. It is used for guests and when the admin
 * disabled "registering logged-in users."
 *
 * @see chabok_read_from_session()
 * @return void
 */
function chabok_register_in_session() {
	ch_start_session();
	$device_id = isset( $_POST['deviceId'] ) ? sanitize_text_field( $_POST['deviceId'] ) : null;
	$user_id = isset( $_POST['userId'] ) ? sanitize_text_field( $_POST['userId'] ) : null;

	if (! $device_id) {
		return;
	}

	$_SESSION['chabok_device_id'] = $device_id;
	$_SESSION['chabok_user_id'] = $user_id;

	$user = wp_get_current_user();
	$devices = array();

	if ( $user ) {
		$devices = $user->get('chabok_devices');

		if ( false === in_array( $device_id, $devices ) ) {
			$devices[] = $device_id;
			update_user_meta( $user->ID, 'chabok_devices', $devices );
		}
	}

	echo json_encode( array(
		'ok' => true,
		'devices_count' => count( $devices ),
	) );

	wp_die();
}
add_action( 'wp_ajax_chabok_register_in_session', 'chabok_register_in_session' );
add_action( 'wp_ajax_nopriv_chabok_register_in_session', 'chabok_register_in_session' );

/**
 * Reads a previously saved deviceId in the
 * current session.
 *
 * @see chabok_register_in_session()
 * @return void
 */
function chabok_read_from_session() {
	ch_start_session();

	$not_registered = true;
	$device_id = isset( $_SESSION['chabok_device_id'] ) ? $_SESSION['chabok_device_id'] : null;

	if ($device_id) {
		$user = wp_get_current_user();
		if ( $user ) {
			$devices = $user->get( 'chabok_devices' );
			if ( ! $devices ) {
				$devices = array();
			}
			if ( in_array( $device_id, $user->get( 'chabok_devices' ) ) ) {
				$not_registered = false;
			}
		}
	}

	echo json_encode( array(
		'ok' => true,
		'not_registered' => $not_registered,
		'device_id' => $device_id,
	) );

	wp_die();
}
add_action( 'wp_ajax_chabok_read_from_session', 'chabok_read_from_session' );
add_action( 'wp_ajax_nopriv_chabok_read_from_session', 'chabok_read_from_session' );

/**
 * Get the current user ID for registering
 * in Chabok.
 *
 * @return void
 */
function chabok_get_user_ajax() {
	ch_start_session();
	$user = chabok_get_user();
	$logged_out = false;
	if ( ! $user ) {
		if ( isset( $_SESSION['chabok_logged_out'] ) ) {
			$logged_out = (bool) $_SESSION['chabok_logged_out'];
		}
	}
	echo json_encode( array(
		'ok' => true,
		'user' => chabok_get_user(),
		'logged_out' => $logged_out,
	) );

	wp_die();
}
add_action( 'wp_ajax_chabok_get_user', 'chabok_get_user_ajax' );
add_action( 'wp_ajax_nopriv_chabok_get_user', 'chabok_get_user_ajax' );

/**
 * Sets the session value for logging out of Chabok.
 *
 * @return void
 */
function chabok_logout() {
	ch_start_session();
	$_SESSION['chabok_logged_out'] = true;

	echo json_encode( array(
		'ok'		=> true,
	) );
	wp_die();
}
add_action( 'wp_ajax_chabok_logout', 'chabok_logout' );
add_action( 'wp_ajax_nopriv_chabok_logout', 'chabok_logout' );

/**
 * Gets called when user is logged in to Chabok.
 *
 * @return void
 */
function chabok_logged_in() {
	ch_start_session();
	$_SESSION['chabok_logged_out'] = false;

	echo json_encode( array(
		'ok' => true,
	) );

	wp_die();
}
add_action( 'wp_ajax_chabok_logged_in', 'chabok_logged_in' );
add_action( 'wp_ajax_nopriv_chabok_logged_in', 'chabok_logged_in' );


/**
 * Executes on WP logging out hook.
 * It clears out the current device from
 * users' devices list.
 *
 * @return void
 */
function chabok_on_wp_logout() {
	ch_start_session();

	$user = wp_get_current_user();
	$user_devices = $user->get( 'chabok_devices' );
	$device_id = isset( $_SESSION['chabok_device_id'] ) ? $_SESSION['chabok_device_id'] : null;


	if ($device_id && $user_devices) {
		$key = array_search( $device_id, $user_devices );
		if ( false !== $key ) {
			unset( $user_devices[ $key ] );
			update_user_meta( $user->ID, 'chabok_devices', $user_devices );
		}
	}
}
add_action( 'clear_auth_cookie', 'chabok_on_wp_logout' );
