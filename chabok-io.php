<?php
/**
 * Plugin Name: Chabok Integration
 * Version: 0.02.14-alpha
 * Author: Chabok Team
 * Description: This plugin provides Chabok integration features such as Push notifications and tracking.
 * Plugin URI: https://chabok.io/
 * Author URI: https://chabok.io/
 * Text Domain: chabok-io
 * Domain Path: languages
 *
 * Chabok Integration is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Chabok Integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Chabok Integration.
 *
 * @author Ehsān Forqāni <ehsaan@riseup.net> (https://ehsaan.dev/)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'ChabokIO' ) ):

/**
 * Chabok Integration core class.
 * @package ChabokIO
 * @category Core
 */
final class ChabokIO {
	/**
	 * @var ChabokIO The one true ChabokIO.
	 */
	private static $instance;

	/**
	 * @var Chabok_API Chabok API instance.
	 */
	public $api;

	/**
	 * Retrieves the main ChabokIO instance.
	 *
	 * Insures that only one instance of ChabokIO class exists in memory
	 * at any one time. Also prevents needing to define globals all over the place.
	 *
	 * @static
	 * @staticvar ChabokIO $instance
	 * @uses ChabokIO::setup_constants() Setup the constants needed.
	 * @uses ChabokIO::includes() Include the required files.
	 * @uses ChabokIO::load_textdomain() Load the language files.
	 * @see chabok_io()
	 * @return object|ChabokIO The one true ChabokIO class instance
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) || ! ! ( self::$instance instanceof ChabokIO ) ) {
			self::$instance	= new ChabokIO;
			self::$instance	->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ), 1 );

			self::$instance->includes();
			self::$instance->api = new Chabok_API();
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object. Therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Forbidden', 'chabok-io' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Forbidden', 'chabok-io' ), '1.0' );
	}

	/**
	 * Setup the plugin constants.
	 *
	 * @access private
	 * @return void
	 */
	private function setup_constants() {
		if ( ! defined( 'CHABOK_ROOT' ) ) {
			define( 'CHABOK_ROOT', __FILE__ );
		}

		if ( ! defined( 'CHABOK_DIR' ) ) {
			define( 'CHABOK_DIR', plugin_dir_path( CHABOK_ROOT ) );
		}

		if ( ! defined( 'CHABOK_URL' ) ) {
			define( 'CHABOK_URL', plugin_dir_url( CHABOK_ROOT ) );
		}

		if ( ! defined( 'CHABOK_VER' ) ) {
			define( 'CHABOK_VER', '0.02.14-alpha' );
		}
	}

	/**
	 * Include the required files to get
	 * the plugin working.
	 *
	 * @access private
	 * @return void
	 */
	private function includes() {
		global $chabok_options;

		require_once CHABOK_DIR . 'includes/register-settings.php';
		$chabok_options = chabok_get_options();

		require_once CHABOK_DIR . 'includes/functions.php';
		require_once CHABOK_DIR . 'includes/class-chabok-api.php';
		require_once CHABOK_DIR . 'includes/meta-box.php';
		require_once CHABOK_DIR . 'includes/enqueues.php';
		require_once CHABOK_DIR . 'includes/rewrite.php';
		require_once CHABOK_DIR . 'includes/attribution.php';
		require_once CHABOK_DIR . 'includes/tracking.php';
		require_once CHABOK_DIR . 'includes/user-devices.php';
	}

	/**
	 * Loads the plugin language files.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		global $wp_version;

		/*
		 * Due to the introduction of language packs through translate.wordpress.org, loading our textdomain is complex.
		 *
		 * To support existing translation files from before the change, we must look for translation files in several places and under several names.
		 *
		 * - wp-content/languages/plugins/chabok-io (introduced with language packs)
		 * - wp-content/languages/chabok-io/ (custom folder we have supported since 1.4)
		 * - wp-content/plugins/chabok-io/languages/
		 *
		 * In wp-content/languages/chabok-io/ we must look for "chabok-io-{lang}_{country}.mo"
		 * In wp-content/languages/plugins/chabok-io/ we only need to look for "chabok-io-{lang}_{country}.mo" as that is the new structure
		 * In wp-content/plugins/chabok-io/languages/, we must look for both naming conventions. This is done by filtering "load_textdomain_mofile"
		 */

		// Set filter for the languages directory of plugin.
		$lang_dir	= dirname( plugin_basename( CHABOK_ROOT ) ) . '/languages/';
		$lang_dir	= apply_filters( 'chabok_languages_directory', $lang_dir );

		$get_locale = get_locale();
		if ( $wp_version >= 4.7 ) {

			$get_locale = get_user_locale();
		}

		$locale 	= apply_filters( 'plugin_locale', $get_locale, 'chabok-io' );
		$mofile		= sprintf( '%1$s-%2$s.mo', 'chabok-io', $locale );

		// Look for wp-content/languages/chabok-io/chabok-io-{lang}_{country}.mo
		$mofile_global1 = WP_LANG_DIR . '/chabok-io/chabok-io-' . $locale . '.mo';

		// Look for wp-content/languages/plugins/chabok-io
		$mofile_global2 = WP_LANG_DIR . '/plugins/chabok-io/' . $mofile;

		if ( file_exists( $mofile_global1 ) ) {

			load_textdomain( 'chabok-io', $mofile_global1 );
		} elseif ( file_exists( $mofile_global2 ) ) {

			load_textdomain( 'chabok-io', $mofile_global2 );
		} else {

			// Load the default language files.
			load_plugin_textdomain( 'chabok-io', false, $lang_dir );
		}
	}
}

endif; // End if class_exists check.

/**
 * The main function for that returns ChabokIO
 *
 * @return object|ChabokIO The one true ChabokIO instance.
 */
function chabok_io() {
	return ChabokIO::instance();
}

// Get it running!
chabok_io();
