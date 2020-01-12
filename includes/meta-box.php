<?php
/**
 * Adds the Tracking meta box for
 * managing the tracking options.
 *
 * @package ChabokIO
 * @subpackage UI/MetaBox
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add Chabok tracker meta box
 * to all public post types screens.
 *
 * @return void
 */
function chabok_add_tracker_meta_box() {
	$post_types = get_post_types( array(
		'public'		=> true,
	) );

	$screens = array_keys( $post_types );

	foreach ( $screens as $screen ) {
		add_meta_box(
			'chabokTracking',
			__( 'Chabok Tracking', 'chabok-io' ),
			'chabok_tracking_box',
			$screen,
			'side'
		);
	}
}
add_action( 'add_meta_boxes', 'chabok_add_tracker_meta_box' );

/**
 * Displays the Tracking box contents.
 *
 * @param WP_Post $post Current post object in the screen.
 * @return void
 */
function chabok_tracking_box($post) {
	$attr = get_post_meta( $post->ID, '_chabok_custom_attribute', true );
	?>
	<label for="chabok_custom_attribute"><?php _e( 'Custom attribute', 'chabok-io' ); ?>: </label>
	<input type="text" name="chabok_custom_attribute" id="chabok_custom_attribute" value="<?php echo $attr; ?>">
	<p class="description"><a href="https://chabok.io"><?php _e( 'Learn more', 'chabok-io' ); ?></a></p>
	<?php
}

/**
 * Saves the Tracking box fields upon saving
 * a post.
 *
 * @param int $post_id Post ID
 * @return void
 */
function chabok_save_tracking_fields($post_id) {
	if (array_key_exists('chabok_custom_attribute', $_POST)) {
		update_post_meta(
			$post_id,
			'_chabok_custom_attribute',
			sanitize_text_field( $_POST['chabok_custom_attribute'] )
		);
	}
}
add_action( 'save_post', 'chabok_save_tracking_fields' );
