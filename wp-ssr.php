<?php
/**
 * Plugin Name: WP SSR
 * Description: Server-side rendering for JavScript apps inside WordPress templates.
 * Version: 0.1.0
 * Author: Roope Merikukka
 * Author uri: https://github.com/roopemerikukka/
 * Text Domain: wp-ssr
 */

namespace WPSSR;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Classes.
require 'class/class-init.php';
require 'class/class-post-type.php';
require 'class/class-settings.php';
require 'class/class-rest-api.php';
require 'class/class-render.php';

// Run installation function only once on activation.
add_action( 'plugins_loaded', [ 'WPSSR\Init', 'init' ] );
register_activation_hook( __FILE__, [ 'WPSSR\Init', 'on_activation' ] );
register_deactivation_hook( __FILE__, [ 'WPSSR\Init', 'on_deactivation' ] );
