<?php
/**
 * Auto update feature.
 * This file enables the auto update feature for
 * the plugin.
 *
 * @package ChabokIO
 * @subpackage Misc/Update
 */

/**
 * Push the update to the WordPress transients.
 *
 * @param object $transient Transient for the plugin update.
 * @return object Manipulated transient.
 */
function chabok_push_update( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}

	if ( false == $remote = get_transient( 'chabok_upgrade' ) ) {
		$remote = wp_remote_get( 'https://raw.githubusercontent.com/chabok-io/chabok-wordpress-plugin/master/info.json', array(
			'timeout'		=> 10,
			'headers'		=> array(
				'Accept'	=> 'application/json',
			),
		) );

		if ( ! is_wp_error( $remote ) && isset( $remote['response']['code'] ) && $remote['response']['code'] == 200 ) {
			set_transient( 'chabok_upgrade', $remote, 43200 );
		}
	}

	if ( $remote && ! is_wp_error( $remote ) ) {
		$remote = json_decode( $remote['body'] );

		if ( $remote && version_compare( CHABOK_VER, $remote->version, '<' ) && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' ) ) {
			$res = new stdClass();
			$res->slug = 'chabok-io';
			$res->plugin = plugin_basename( CHABOK_ROOT );
			$res->new_version = $remote->version;
			$res->tested = $remote->tested;
			$res->package = $remote->package;
			$transient->response[ $res->plugin ] = $res;
		}
	}

	return $transient;
}
add_action( 'site_transient_update_plugins', 'chabok_push_update' );
