<?php
/**
 * Utility functions.
 *
 * @package ChabokIO
 * @subpackage Util
 */

/**
 * Starts a session if it wasn't already
 * started.
 *
 * @return void
 */
function ch_start_session() {
	if ( ! session_id() ) {
		session_start();
	}
}

/**
 * Gets the current user ID for registering
 * in Chabok.
 *
 * @return string|null
 */
function chabok_get_user() {
	global $chabok_options;

	$user = wp_get_current_user();
	$key = null;
	$attribute = isset( $chabok_options['user_id_key'] ) ? $chabok_options['user_id_key'] : 'user_email';
	if ($user) {
		$key = $user->get($attribute);
	}

	return $key;
}

/**
 * Adds menu links to the plugin entry
 * in the plugins menu.
 *
 * @param array $links
 * @param string $file
 * @return array
 */
function chabok_action_links($links, $file) {
	if ( $file === plugin_basename( CHABOK_ROOT ) ) {
		$plugin_links[] = '<a href="' . admin_url( 'admin.php?page=chabok' ) . '">' . __( 'Settings', 'chabok-io' ) . '</a>';
		$plugin_links[] = '<a target="_blank" href="https://chabok.io/">' . __( 'Support', 'chabok-io' ) . '</a>';

		foreach( $plugin_links as $link ) {
			array_unshift( $links, $link );
		}
	}

	return $links;
}
add_filter( 'plugin_action_links', 'chabok_action_links', 10, 2 );
