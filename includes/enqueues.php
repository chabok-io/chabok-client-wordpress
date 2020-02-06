<?php
/**
 * Adds and enqueues JavaScript files.
 *
 * @package ChabokIO
 * @subpackage Core/Enqueues
 */

/**
 * Adds SDKWorker and chabokpush to enqueue list.
 *
 * @return void
 */
function chabok_enqueue() {
	global $chabok_options;

	wp_register_script(
		'chabokpush',
		CHABOK_URL . 'assets/js/chabokpush.min.js',
		array(),
		CHABOK_VER,
		true
	);

	wp_register_script(
		'chabok',
		CHABOK_URL . 'assets/js/chabok-init.js',
		array( 'chabokpush', 'jquery' ),
		CHABOK_VER,
		true
	);

	wp_enqueue_script( 'chabok' );

	wp_localize_script( 'chabok', 'chabok_params', array(
		'options' 		=> $chabok_options,
		'xhr_endpoint'	=> admin_url( 'admin-ajax.php' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'chabok_enqueue' );
