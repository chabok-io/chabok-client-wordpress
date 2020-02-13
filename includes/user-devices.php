<?php
/**
 * Adds devices list to user screens.
 *
 * @package ChabokIO
 * @subpackage Admin
 */

/**
 * Adds devices list to user profile.
 *
 * @param WP_User $user
 * @return void
 */
function chabok_devices_list_profile( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	?>
	<h2><?php _e( 'Chabok', 'chabok-io' ); ?></h2>
	<table class="form-table" role="presentation">
		<tbody>
			<tr id="chabokDevicesRow">
				<th><label for="chabokDevices"><?php _e( 'Devices (Installations)', 'chabok-io' ); ?></label></th>
				<td>
					<?php
						$devices = $user->chabok_devices;
						if ( ! $devices ) {
							_e( 'No devices for this user, yet.', 'chabok-io' );
						} else {
							foreach ( $devices as $v ) {
								echo '<li><code>' . $v . '</code></li>';
							}
						}
					?>
				</td>
			</tr>
			<tr id="chabokDevicesReset">
				<th><label for="chabokDevices"><?php _e( 'Reset', 'chabok-io' ); ?></label></th>
				<td>
					<a href="<?php echo wp_nonce_url( add_query_arg( array( 'chabok_reset' => $user->ID ) ), 'chabok_reset_' . $user->ID ); ?>" class="button"><?php _e( 'Clear devices', 'chabok-io' ); ?></a>
					<p class="description"><?php _e( 'This is used mostly for debugging purposes. If you are not sure about it, call Chabok support.', 'chabok-io' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
add_action( 'edit_user_profile', 'chabok_devices_list_profile' );
add_action( 'show_user_profile', 'chabok_devices_list_profile' );

/**
 * Resets the user devices.
 *
 * @return void
 */
function chabok_reset_devices_for_user() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['chabok_reset'] ) || ! isset( $_GET['_wpnonce'] ) ) {
		return;
	}

	$user_id = absint( $_GET['chabok_reset'] );
	$nonce = sanitize_text_field( $_GET['_wpnonce'] );

	if ( ! wp_verify_nonce( $nonce, 'chabok_reset_' . $user_id ) ) {
		return;
	}

	$user = new WP_User( $user_id );
	if ( ! $user || $user instanceof WP_Error ) {
		return;
	}

	update_user_meta( $user->ID, 'chabok_devices', array() );

	return;
}
add_action( 'admin_init', 'chabok_reset_devices_for_user' );

/**
 * Resets all chabok_devices values in
 * database.
 *
 * @return void
 */
function chabok_reset_all_devices() {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_GET['chabok_reset_all'] ) && ! isset( $_GET['_wpnonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( $_GET['_wpnonce'] );

	if ( ! wp_verify_nonce( $nonce, 'chabok_reset_all' ) ) {
		return;
	}

	$sql = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}usermeta WHERE `meta_key` = %s", 'chabok_devices' );
	if ( $wpdb->query( $sql ) ) {
		wp_die( __( 'Cleared all devices from WordPress database!', 'chabok-io' ), 'Reset successful', array(
			'response' 		=> 200,
			'back_link'		=> true,
		) );
	} else {
		wp_die( __( 'There was a problem with resetting devices.', 'chabok-io' ) );
	}
}
add_action( 'admin_init', 'chabok_reset_all_devices' );
