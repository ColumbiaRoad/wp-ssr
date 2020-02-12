<?php

namespace WPSSR;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SSR custom post type class.
 */
class Post_Type {

	/**
	 * Holds the Object instance.
	 *
	 * @var WPSSR\Post_Type|null
	 */
	protected static $instance = null;

	/**
	 * Name of the custom post type.
	 *
	 * @var string
	 */
	protected static $post_type = 'wp-ssr-render';

	/**
	 * Slug of the custom post type.
	 *
	 * @var string
	 */
	protected static $post_slug = 'wp-ssr-render';

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
		add_action( 'init', [ $this, 'register_custom_post_type' ] );
		add_action( 'add_meta_boxes', [ $this, 'create_fields' ] );
	}

	/**
	 * Get the post type name.
	 *
	 * @return string
	 */
	public static function get_post_type() : string {
		return self::$post_type;
	}

	/**
	 * Create instance of the plugin object.
	 *
	 * @return WPSSR\Post_Type
	 */
	public static function init() : Post_Type {
		is_null( self::$instance ) && self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Register the post type.
	 *
	 * @return void
	 */
	public function register_custom_post_type() {

		$labels = array(
			'name'               => __( 'Renders', 'wp-ssr' ),
			'singular_name'      => __( 'Render', 'wp-ssr' ),
			'add_new_item'       => __( 'Add New Render', 'wp-ssr' ),
			'edit_item'          => __( 'Edit Render', 'wp-ssr' ),
			'new_item'           => __( 'New Render', 'wp-ssr' ),
			'view_item'          => __( 'View Render', 'wp-ssr' ),
			'view_items'         => __( 'View Renders', 'wp-ssr' ),
			'search_items'       => __( 'Search for Renders', 'wp-ssr' ),
			'not_found'          => __( 'No Renders found', 'wp-ssr' ),
			'not_found_in_trash' => __( 'No Renders found in trash', 'wp-ssr' ),
		);

		$args = array(
			'labels'              => $labels,
			'description'         => '',
			'public'              => false,
			'show_ui'             => true,
			'has_archive'         => false,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'capabilities'        => [
				'create_posts' => false,
			],
			'map_meta_cap'        => true,
			'hierarchical'        => false,
			'menu_position'       => 5,
			'rewrite'             => [ 'slug' => self::$post_slug ],
			'query_var'           => true,
			'supports'            => [ 'title', 'revisions' ],
			'taxonomies'          => [],
			'menu_icon'           => 'dashicons-layout',
		);

		register_post_type( self::$post_type, $args );
	}

	/**
	 * Create metabox fields to be shown in
	 * posts edit page.
	 *
	 * @return void
	 */
	public function create_fields() {
		// Add metabox for the fields.
		add_meta_box(
			'wp_react_ssr_fields',
			__( 'INFO', 'wp-ssr' ),
			[ $this, 'fields_html' ],
			self::$post_type
		);
	}

	/**
	 * Callback function to return the fields markup.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function fields_html( \WP_Post $post ) {
		$url_field              = $this->get_url_field_html( $post );
		$render_field           = $this->get_render_field_html( $post );
		$last_modified_field     = $this->get_last_modified_field_html( $post );
		$app_selector_field     = $this->get_app_selector_field_html( $post );
		$waitfor_selector_field = $this->get_waitfor_selector_field_html( $post );
		?>
		<fieldset>
			<?php echo $last_modified_field; // phpcs:ignore ?>
			<?php echo $app_selector_field; // phpcs:ignore ?>
			<?php echo $waitfor_selector_field; // phpcs:ignore ?>
			<?php echo $url_field; // phpcs:ignore ?>
			<?php echo $render_field; // phpcs:ignore ?>
		</fieldset>
		<?php
	}

	/**
	 * Get the markup for url field.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return string
	 */
	public function get_url_field_html( \WP_Post $post ) : string {
		$url = self::get_url( $post->ID );
		\ob_start();
		?>
		<p>
			<label for="wp_react_ssr_url"><?php echo esc_html_x( 'Url', 'label', 'wp-ssr' ); ?></label>
		</p>
		<p>
			<input class="large-text" id="wp_react_ssr_url" type="text" value="<?php echo esc_attr( $url ); ?>" readonly />
		</p>
		<?php
		$result = \ob_get_clean();
		return $result;
	}

	/**
	 * Get the markup for app selector field.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return string
	 */
	public function get_app_selector_field_html( \WP_Post $post ) : string {
		$app_selector = self::get_app_selector( $post->ID );
		\ob_start();
		?>
		<p>
			<label for="wp_react_ssr_app_selector"><?php echo esc_html_x( 'App selector', 'label', 'wp-ssr' ); ?></label>
		</p>
		<p>
			<input class="large-text" id="wp_react_ssr_app_selector" type="text" value="<?php echo esc_attr( $app_selector ); ?>" readonly />
		</p>
		<?php
		$result = \ob_get_clean();
		return $result;
	}

	/**
	 * Get the markup for waitfor selector field.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return string
	 */
	public function get_waitfor_selector_field_html( \WP_Post $post ) : string {
		$waitfor_selector = self::get_waitfor_selector( $post->ID );
		\ob_start();
		?>
		<p>
			<label for="wp_react_ssr_waitfor_selector"><?php echo esc_html_x( 'Waitfor selector', 'label', 'wp-ssr' ); ?></label>
		</p>
		<p>
			<input class="large-text" id="wp_react_ssr_waitfor_selector" type="text" value="<?php echo esc_attr( $waitfor_selector ); ?>" readonly />
		</p>
		<?php
		$result = \ob_get_clean();
		return $result;
	}

	/**
	 * Get the markup for last modified field.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return string
	 */
	public function get_last_modified_field_html( \WP_Post $post ) : string {
		$last_modified = self::get_last_modified( $post->ID );
		$date = gmdate( 'H:i d.m.Y', (int) $last_modified );
		\ob_start();
		?>
		<p>
			<label for="wp_react_ssr_last_modified"><?php echo esc_html_x( 'Last modified', 'label', 'wp-ssr' ); ?></label>
		</p>
		<p>
			<input class="large-text" id="wp_react_ssr_last_modified" type="text" value="<?php echo esc_attr( $date ); ?>" readonly />
		</p>
		<?php
		$result = \ob_get_clean();
		return $result;
	}

	/**
	 * Get the markup for render field markup.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return string
	 */
	public function get_render_field_html( \WP_Post $post ) : string {
		$html = self::get_html( $post->ID );
		\ob_start();
		?>
		<p>
			<label for="wp_react_ssr_html"><?php echo esc_html_x( 'Html', 'label', 'wp-ssr' ); ?></label>
		</p>
		<p>
			<textarea class="large-text" id="wp_react_ssr_html" type="text" rows="20" cols="50" readonly>
				<?php echo esc_html( $html ); ?>
			</textarea>
		</p>
		<?php
		$result = \ob_get_clean();
		return $result;
	}

	/**
	 * Get url value.
	 *
	 * @param integer $post_id Post ID.
	 * @return string
	 */
	public static function get_url( int $post_id ) : string {
		$url = get_post_meta( $post_id, 'wp_react_ssr_url', true );
		return $url;
	}

	/**
	 * Set url.
	 *
	 * @param integer $post_id Current post id.
	 * @param string  $value Url.
	 * @return void
	 */
	public static function set_url( int $post_id, string $value ) {
		update_post_meta( $post_id, 'wp_react_ssr_url', $value );
		self::set_last_modified( $post_id );
	}

	/**
	 * Get app selector value.
	 *
	 * @param integer $post_id Post ID.
	 * @return string
	 */
	public static function get_app_selector( int $post_id ) : string {
		$app_selector = get_post_meta( $post_id, 'wp_react_ssr_app_selector', true );
		return $app_selector;
	}

	/**
	 * Set app selector
	 *
	 * @param integer $post_id Current post id.
	 * @param string  $value App selector.
	 * @return void
	 */
	public static function set_app_selector( int $post_id, string $value ) {
		update_post_meta( $post_id, 'wp_react_ssr_app_selector', $value );
		self::set_last_modified( $post_id );
	}

	/**
	 * Get waitfor selector value.
	 *
	 * @param integer $post_id Post ID.
	 * @return string
	 */
	public static function get_waitfor_selector( int $post_id ) : string {
		$app_selector = get_post_meta( $post_id, 'wp_react_ssr_waitfor_selector', true );
		return $app_selector;
	}

	/**
	 * Set waitfor selector.
	 *
	 * @param integer $post_id Current post id.
	 * @param string  $value Waitfor selector.
	 * @return void
	 */
	public static function set_waitfor_selector( int $post_id, string $value ) {
		update_post_meta( $post_id, 'wp_react_ssr_waitfor_selector', $value );
		self::set_last_modified( $post_id );
	}

	/**
	 * Get render html markup.
	 *
	 * @param integer $post_id Post ID.
	 * @return string
	 */
	public static function get_html( int $post_id ) : string {
		$html = get_post_meta( $post_id, 'wp_react_ssr_html', true );
		return $html;
	}

	/**
	 * Set render html markup.
	 *
	 * @param integer $post_id Current post id.
	 * @param string  $value Html.
	 * @return void
	 */
	public static function set_html( int $post_id, string $value ) {
		update_post_meta( $post_id, 'wp_react_ssr_html', $value );
		self::set_last_modified( $post_id );
	}

	/**
	 * Get last modified timestamp.
	 *
	 * @param integer $post_id Post ID.
	 * @return integer
	 */
	public static function get_last_modified( int $post_id ) : int {
		$last_modified = get_post_meta( $post_id, 'wp_react_ssr_last_modified', true );
		return (int) $last_modified;
	}

	/**
	 * Set the last modified time.
	 *
	 * @param integer $post_id Current post id.
	 * @return void
	 */
	private static function set_last_modified( int $post_id ) {
		update_post_meta( $post_id, 'wp_react_ssr_last_modified', time() );
	}

} // Class ends
