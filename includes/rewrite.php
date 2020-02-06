<?php
/**
 * Rewriting ServiceWorker file path
 * in the root of the WordPress site.
 *
 * @package ChabokIO
 * @subpackage Rewrite
 */

/**
 * WordPress kept showing inconsistent
 * behaviors when using proper methods like
 * WP_Rewrite::add_rules(), so we hacked the
 * WordPress rewriting like this.
 * It ain't much, but it's honest work.
 *
 * @return void
 */
add_action( 'init', function() {
	global $chabok_options;

	if ( ! isset( $chabok_options['rewrite_sw'] ) || 'on' !== $chabok_options['rewrite_sw'] ) {
		return;
	}

	if ( false !== strpos( $_SERVER['REQUEST_URI'], '/ChabokSDKWorker.js' ) ) {
		if ( 'map' === substr( $_SERVER['REQUEST_URI'], -3 ) ) {
			header( 'Content-Type: application/json;charset=UTF-8' );
			echo file_get_contents( CHABOK_DIR . 'assets/js/ChabokSDKWorker.js.map' );
		} else {
			header( 'Content-Type: text/javascript;charset=UTF-8' );
			echo file_get_contents( CHABOK_DIR . 'assets/js/ChabokSDKWorker.js' );
		}
		die();
	}
}, 0 );
