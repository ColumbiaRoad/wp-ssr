<?php

namespace WPSSR;

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * REST API class.
 */
class REST_API {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'init_rest_endpoint' ] );
	}

	/**
	 * Init rest endpoint.
	 *
	 * @return void
	 */
	public function init_rest_endpoint() {
		register_rest_route(
			'wp-ssr/v1',
			'renders',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'rest_get_renders' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);

		register_rest_route(
			'wp-ssr/v1',
			'save_renders',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'rest_save_renders' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => $this->rest_save_renders_args(),
			]
		);
	}

	/**
	 * Callback to check the REST API access permissions.
	 *
	 * @param \WP_REST_Request $request Current request object.
	 * @return boolean
	 */
	public function check_permission( \WP_REST_Request $request ) : bool {
		$request_key = $request->get_header( 'X-WP-SSR-Key' );
		return Settings::get_api_key() === $request_key;
	}

	/**
	 * Callback to get the arguments for save_renders endpoint.
	 *
	 * @return array
	 */
	public function rest_save_renders_args() : array {
		$args = [
			'renders' => [
				'type'  => 'array',
				'required' => true,
				'items' => [
					'type'       => 'object',
					'required'   => true,
					'properties' => [
						'id' => [
							'type'     => 'integer',
							'required' => true,
						],
						'html' => [
							'type'     => 'string',
							'required' => true,
						],
					],
				],
			],
		];
		return $args;
	}

	/**
	 * Callback function to get the render objects.
	 *
	 * @param \WP_REST_Request $request Current request object.
	 * @return array
	 */
	public function rest_get_renders( \WP_REST_Request $request ) : array {
		$query = new \WP_Query(
			[
				'post_type'              => Post_Type::get_post_type(),
				'posts_per_page'         => 500,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'fields'                 => 'ids',
			]
		);

		$posts = $query->get_posts();

		if ( ! $posts ) {
			return [];
		}

		$renders = array_map( [ $this, 'map_post_to_render' ], $posts );

		$renders = array_filter( $renders, [ $this, 'filter_renders' ] );

		return $renders;
	}

	/**
	 * Array map callback to get the needed fields for
	 * rest response.
	 *
	 * @param integer $post_id Render post ID.
	 * @return array
	 */
	public function map_post_to_render( int $post_id ) : array {
		return [
			'id'              => $post_id,
			'url'             => Post_Type::get_url( $post_id ),
			'html'            => Post_Type::get_html( $post_id ),
			'last_modified'   => Post_Type::get_last_modified( $post_id ),
			'appSelector'     => Post_Type::get_app_selector( $post_id ),
			'waitForSelector' => Post_Type::get_waitfor_selector( $post_id ),
		];
	}

	/**
	 * Filter renders so that only ones with empty html
	 * or last modified interval has passed will be served.
	 *
	 * @param array $item Item to check against.
	 * @return boolean
	 */
	public function filter_renders( array $item ) : bool {
		$interval_hours = Settings::get_render_interval();
		$interval_seconds = $interval_hours * 60 * 60;
		return ( $item['last_modified'] + $interval_seconds ) <= time() || empty( $item['html'] );
	}

	/**
	 * Callback function to save the render results.
	 *
	 * @param \WP_REST_Request $request Current request object.
	 * @return mixed
	 */
	public function rest_save_renders( \WP_REST_Request $request ) {
		$renders = $request->get_param( 'renders' );
		foreach ( $renders as $render ) {
			$post_id = $render['id'];
			$html    = $render['html'];
			if ( get_post( $post_id ) ) {
				Post_Type::set_html( $post_id, $html );
			}
		}
		return rest_ensure_response( true );
	}
}
