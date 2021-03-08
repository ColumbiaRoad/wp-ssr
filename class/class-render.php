<?php

namespace WPSSR;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Render class.
 */
class Render {

	/**
	 * Holds the Object instance.
	 *
	 * @var WPSSR\Render|null
	 */
	protected static $instance = null;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {

		if ( ! is_null( self::$instance ) ) {
			return self::$instance;
		}
	}

	/**
	 * Create instance of the plugin object.
	 *
	 * @return WPSSR\Render
	 */
	public static function init() : Render {
		is_null( self::$instance ) && self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Check if current request is SSR request by
	 * checking the X-WP-SSR header value.
	 *
	 * @return boolean
	 */
	public static function is_ssr_request() : bool {
		$ssr_header = isset( $_SERVER['HTTP_X_WP_SSR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_SSR'] ) ) : '';
		return 'ssr' === $ssr_header;
	}

		/**
		 * Remove unwanted url parameters from the url according to settings
		 *
		 * @param string $url Url to remove params from.
		 * @return string
		 */
	public static function clear_url_params( string $url ) : string {
		if ( Settings::get_allowed_params() === '' ) { return $url;}

		$allowed_params = explode( ',', Settings::get_allowed_params() );
		$parsed_url = parse_url( $url );
		$params = [];

		if ( array_key_exists( 'query', $parsed_url ) ) {
			foreach ( explode( '&', $parsed_url['query'] ) as $param ) {
				[$key] = explode( '=', $param, 2 );
				if ( in_array( $key, $allowed_params ) ) { array_push( $params, $param );
			}
			}
			$params = '?' . implode( '&', $params );
		}

		return $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'] . $params;
	}


	/**
	 * Function returns rendered html of the application
	 * if there is one and requests new renders.
	 *
	 * @param string $url Url to find the render for.
	 * @param string $app_selector App css selector.
	 * @param string $waitfor_selector Waitfor css selector.
	 * @return string|null
	 */
	public static function render( string $url, string $app_selector = '', string $waitfor_selector = '' ) {
		$url = self::clear_url_params( $url );
		$query = new \WP_Query(
			[
				'post_type'      => Post_Type::get_post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'     => 'wp_react_ssr_url',
						'value'   => $url,
						'compare' => '=',
					],
				],
			]
		);

		$posts = $query->get_posts();

		/*
		// Request renderer if its not the one doing request.
		if ( ! self::is_ssr_request() ) {
			self::request_renderer();
		}
		*/

		if ( ! $posts ) {
			// Save the url to db without html
			if ( ! self::is_ssr_request() ) {
				self::create_new_render_post( $url, $app_selector, $waitfor_selector );
			}
			return null;
		}

		$post = $posts[0];
		$html = Post_Type::get_html( $posts[0]->ID );

		if ( ! self::is_ssr_request() ) {
			Post_Type::set_last_visited( $posts[0]->ID );
		}

		// Return null if there is no html.
		if ( empty( $html ) || self::is_ssr_request() ) {
			return null;
		}

		// Return the html.
		return $html;
	}

	/**
	 * Create new render post to wait for ssr application to
	 * render.
	 *
	 * @param string $url Application url.
	 * @param string $app_selector App css selector.
	 * @param string $waitfor_selector App waitfor selector.
	 * @return void
	 */
	private static function create_new_render_post( string $url, string $app_selector = '', string $waitfor_selector = '' ) {
		if ( ! $url ) {
			return;
		}

		$post_id = wp_insert_post(
			[
				'post_title' => sanitize_title( $url ),
				'post_status' => 'publish',
				'post_type' => Post_Type::get_post_type(),
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			error_log( print_r( $post_id, true ) );
			return;
		}

		Post_Type::set_url( $post_id, $url );
		Post_Type::set_app_selector( $post_id, $app_selector );
		Post_Type::set_waitfor_selector( $post_id, $waitfor_selector );
	}

	/**
	 * Request the Node app to start rendering the apps.
	 *
	 * @return boolean
	 */
	private static function request_renderer() : bool {

		$url = Settings::get_nodeapp_url();

		if ( ! $url ) {
			error_log( 'No Node App url defined.' );
			return false;
		}

		$response = wp_remote_get(
			$url,
			[
				'headers' => [
					'X-WP-SSR-Key' => Settings::get_api_key(),
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			error_log( print_r( $response->get_error_message(), true ) );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			error_log( print_r( $body, true ) );
			return false;
		}

		return true;
	}

} // Class ends
