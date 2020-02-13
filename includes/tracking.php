<?php
/**
 * Tracks users behaviors and actions they take.
 *
 * @package ChabokIO
 * @subpackage Track
 */

/**
 * Tracks the users commenting.
 *
 * @param int $comment_ID
 * @param boolean $comment_approved
 * @param mixed $commentdata
 * @return void
 */
function chabok_on_comment_post($comment_ID, $comment_approved, $commentdata) {
	global $chabok_options;
	if ( ! isset( $chabok_options['track_commenting'] ) || 'on' !== $chabok_options['track_commenting'] ) {
		return;
	}

	ch_start_session();

	$installation_id = $_SESSION['chabok_device_id'];
	$user_id = chabok_get_user();
	if (! $user_id) {
		if ( isset( $_SESSION['chabok_user_id'] ) ) {
			$user_id = $_SESSION['chabok_user_id'];
		}
	}
	chabok_io()->api->track_event(
		'comment',
		$user_id,
		$installation_id,
		apply_filters( 'chabok_on_comment_data', array(
			'author' 			=> $commentdata['comment_author'],
			'comment_post_id' 	=> $commentdata['comment_post_ID'],
			'comment_post_type' => get_post_type( $commentdata['comment_post_ID'] ),
		), $comment_ID, $comment_approved, $commentdata )
	);
}
add_action( 'comment_post', 'chabok_on_comment_post', 10, 3 );

/**
 * Tracks the users searching.
 *
 * @param WP_Query $query
 * @return void
 */
function chabok_on_search($query) {
	global $chabok_options;
	if ( ! isset( $chabok_options['track_search'] ) || 'on' !== $chabok_options['track_search'] ) {
		return;
	}

	if ( $query->is_search() && $query->is_main_query() ) {
		ch_start_session();

		$installation_id = $_SESSION['chabok_device_id'];
		$user_id = chabok_get_user();
		if (! $user_id) {
			if ( isset( $_SESSION['chabok_user_id'] ) ) {
				$user_id = $_SESSION['chabok_user_id'];
			}
		}
		chabok_io()->api->track_event(
			'search',
			$user_id,
			$installation_id,
			apply_filters( 'chabok_on_search_data', array(
				'query'			=> $query->query['s'],
			), $query )
		);
	}
}
add_action( 'pre_get_posts', 'chabok_on_search' );

/**
 * Tracks the users viewing a post.
 *
 * @param WP_Query $query
 * @return void
 */
function chabok_on_single($query) {
	global $chabok_options;
	if ( isset( $chabok_options['track_posts'] ) && 'off' === $chabok_options['track_posts'] ) {
		return;
	}

	if ( is_single() && get_the_ID() ) {
		ch_start_session();

		$installation_id = $_SESSION['chabok_device_id'];
		$user_id = chabok_get_user();
		if (! $user_id) {
			if ( isset( $_SESSION['chabok_user_id'] ) ) {
				$user_id = $_SESSION['chabok_user_id'];
			}
		}

		$custom_attr = get_post_meta( get_the_ID(), '_chabok_custom_attribute', true );
		if ( ! $custom_attr ) {
			$custom_attr = '';
		}

		chabok_io()->api->track_event(
			'view_post',
			$user_id,
			$installation_id,
			apply_filters( 'chabok_on_single_data', array(
				'post_ID'			=> get_the_ID(),
				'post_type'			=> get_post_type(),
				'custom_attribute'	=> get_post_meta( get_the_ID(), '_chabok_custom_attribute', true ),
			), $query )
		);
	}
}
add_action( 'wp', 'chabok_on_single' );
