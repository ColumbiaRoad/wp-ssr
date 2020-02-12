<?php

namespace WPSSR;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Sponda Resource init class.
 */
class Init {

	/**
	 * Holds the Object instance.
	 *
	 * @var WPSSR\Init|null
	 */
	protected static $instance = null;

	/**
	 * Minimum required WordPress version.
	 *
	 * @var string
	 */
	protected static $min_wp_version = '5.1';

	/**
	 * Minimum required PHP version.
	 *
	 * @var string
	 */
	protected static $min_php_version = '7.2';

	/**
	 * Class dependencies of the plugin.
	 *
	 * @var array
	 */
	protected static $class_dependencies = [];

	/**
	 * PHP extensions required by the plugin.
	 *
	 * @var array
	 */
	protected static $required_php_extensions = [];

	/**
	 * Plugin constructor.
	 */
	public function __construct() {

		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		// Init other classes.
		new Post_Type();
		new Settings();
		new REST_API();
		new Render();

		/**
		 * Hooks.
		 */
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Create instance of the plugin object.
	 *
	 * @return WPSSR\Init
	 */
	public static function init() : Init {
		is_null( self::$instance ) && self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Load plugin textdomain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'wp-ssr', false, basename( dirname( __DIR__, 1 ) ) . '/languages' );
	}

	/**
	 * Checks if plugin dependencies & requirements are met.
	 *
	 * @return boolean
	 */
	protected static function are_requirements_met() : bool {
		// Check for WordPress version
		if ( version_compare( get_bloginfo( 'version' ), self::$min_wp_version, '<' ) ) {
			return false;
		}

		// Check the PHP version
		if ( version_compare( PHP_VERSION, self::$min_php_version, '<' ) ) {
			return false;
		}

		// Check PHP loaded extensions
		foreach ( self::$required_php_extensions as $ext ) {
			if ( ! extension_loaded( $ext ) ) {
				return false;
			}
		}

		// Check for required classes
		foreach ( self::$class_dependencies as $class_name ) {
			if ( ! class_exists( $class_name ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if plugin dependencies & requirements are met. If they are it doesn't
	 * do anything if they aren't it will die.
	 *
	 * @return void
	 */
	public static function ensure_requirements_are_met() {
		if ( ! self::are_requirements_met() ) {
			deactivate_plugins( __FILE__ );
			$error = '<p>Some of the plugin dependencies aren\'t met and the plugin can\'t be enabled. This plugin requires the followind dependencies:</p>';
			$error .= '<ul>';
			$error .= '<li>Minimum WP version: ' . esc_html( self::$min_wp_version ) . '</li>';
			$error .= '<li>Minimum PHP version: ' . esc_html( self::$min_php_version ) . '</li>';
			$error .= '<li>Classes / plugins: ' . implode( ', ', self::$class_dependencies ) . '</li>';
			$error .= '<li>PHP extensions: ' . implode( ', ', self::$required_php_extensions ) . '</li>';
			$error .= '</ul>';
			wp_die( $error ); // phpcs:ignore
		}
	}

	/**
	 * A function that's run once when the plugin is activated.
	 *
	 * @return void
	 */
	public static function on_activation() {
		// Security stuff.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "activate-plugin_{$plugin}" );

		// Check requirements.
		self::ensure_requirements_are_met();
	}

	/**
	 * A function that's run once when the plugin is deactivated.
	 *
	 * @return void
	 */
	public static function on_deactivation() {
		// Security stuff.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugin = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		check_admin_referer( "deactivate-plugin_{$plugin}" );
	}

} // Class ends
