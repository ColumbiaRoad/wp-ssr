<?php

namespace WPSSR;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Settings class.
 */
class Settings {

	/**
	 * Holds the Object instance.
	 *
	 * @var WPSSR\Settings|null
	 */
	protected static $instance = null;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {

		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}

		/**
		 * Hooks.
		 */
		add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		add_action( 'admin_init', [ $this, 'init_settings' ] );
	}

	/**
	 * Create instance of the plugin object.
	 *
	 * @return WPSSR\Settings
	 */
	public static function init() : Settings {
		is_null( self::$instance ) && self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Initialize settings for the plugin.
	 *
	 * @return void
	 */
	public function init_settings() {
		register_setting( 'wpssr', 'wpssr_options' );

		// General settings section.
		add_settings_section(
			'wpssr_options_general',
			__( 'General settings', 'wp-ssr' ),
			null,
			'wpssr'
		);

		// API key field.
		add_settings_field(
			'wpssr_options_authentication_api_key',
			__( 'API key', 'wp-ssr' ),
			[ $this, 'api_key_field' ],
			'wpssr',
			'wpssr_options_general',
			[ 'label_for' => 'wpssr_options_authentication_api_key' ]
		);

		// Node process ping url.
		add_settings_field(
			'wpssr_options_nodeapp_url',
			__( 'Node App url', 'wp-ssr' ),
			[ $this, 'nodeapp_url_field' ],
			'wpssr',
			'wpssr_options_general',
			[ 'label_for' => 'wpssr_options_nodeapp_url' ]
		);

		// Render interval.
		add_settings_field(
			'wpssr_options_render_interval',
			__( 'Render interval', 'wp-ssr' ),
			[ $this, 'render_interval_field' ],
			'wpssr',
			'wpssr_options_general',
			[ 'label_for' => 'wpssr_options_render_interval' ]
		);

			// Molded render delete time.
			add_settings_field(
				'wpssr_options_delete_timeout',
				__( 'Delete timeout', 'wp-ssr' ),
				[ $this, 'delete_timeout_field' ],
				'wpssr',
				'wpssr_options_general',
				[ 'label_for' => 'wpssr_options_delete_timeout' ]
			);
	}

	/**
	 * Callback for the nodeapp url field markup.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	public function nodeapp_url_field( array $args ) {
		$url = self::get_nodeapp_url();
		?>
		<input
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			class="regular-text"
			name="wpssr_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			value="<?php echo esc_attr( $url ); ?>"
			type="text"
			autocomplete="off"
		/>
		<?php
	}

	/**
	 * Callback function for the API key field markup.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	public function api_key_field( array $args ) {
		$api_key = self::get_api_key();
		?>
		<input
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			class="regular-text"
			name="wpssr_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			value="<?php echo esc_attr( $api_key ); ?>"
			type="text"
			autocomplete="off"
		/>
		<?php
	}

	/**
	 * Callback function for the render interval field markup.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	public function render_interval_field( array $args ) {
		$interval = self::get_render_interval();
		?>
		<p>Give the interval to render in hours.</p>
		<input
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			class="regular-small"
			name="wpssr_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			value="<?php echo esc_attr( $interval ); ?>"
			min="0"
			type="number"
			autocomplete="off"
		/>
		<?php
	}

		/**
		 * Callback function for the render interval field markup.
		 *
		 * @param array $args Arguments.
		 * @return void
		 */
	public function delete_timeout_field( array $args ) {
		$timeout = self::get_delete_timeout();
		?>
		<p>Give the expiry time of not visited posts in days.</p>
		<input
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			class="regular-small"
			name="wpssr_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
			value="<?php echo esc_attr( $timeout ); ?>"
			min="0"
			type="number"
			autocomplete="off"
		/>
		<?php
	}

	/**
	 * Get the Node app url option value.
	 *
	 * @return string
	 */
	public static function get_nodeapp_url() : string {
		$options = get_option( 'wpssr_options' );
		return $options['wpssr_options_nodeapp_url'] ?? '';
	}

	/**
	 * Get the API key option value.
	 *
	 * @return string
	 */
	public static function get_api_key() : string {
		$options = get_option( 'wpssr_options' );
		return $options['wpssr_options_authentication_api_key'] ?? '';
	}

	/**
	 * Get the render interval.
	 *
	 * @return integer
	 */
	public static function get_render_interval() : int {
		$options  = get_option( 'wpssr_options' );
		$interval = $options['wpssr_options_render_interval'] ?? 24;
		return (int) $interval;
	}


	/**
	 * Get the render interval.
	 *
	 * @return integer
	 */
	public static function get_delete_timeout() : int {
		$options  = get_option( 'wpssr_options' );
		$interval = $options['wpssr_options_delete_timeout'] ?? 30;
		return (int) $interval;
	}

	/**
	 * Initialize the settings page.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		$post_type = Post_Type::get_post_type();
		add_submenu_page(
			"edit.php?post_type={$post_type}",
			__( 'WordPress React SSR Settings', 'wp-ssr' ),
			__( 'SSR Settings', 'wp-ssr' ),
			'manage_options',
			'wp-ssr-settings',
			[ $this, 'settings_page_content' ]
		);

	}

	/**
	 * Callback to get the settings page html markup.
	 *
	 * @return void
	 */
	public function settings_page_content() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		// WordPress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( 'wpssr_messages', 'wpssr_message', __( 'Settings Saved', 'wp-ssr' ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'wpssr_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
			<?php
				settings_fields( 'wpssr' );
				do_settings_sections( 'wpssr' );
				submit_button( 'Save Settings' );
			?>
			</form>
		</div>
		<?php
	}

} // Class ends
