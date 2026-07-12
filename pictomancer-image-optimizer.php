<?php
/**
 * Plugin Name:       Pictomancer Image Optimizer
 * Plugin URI:        https://pictomancer.ai/integrations/wordpress
 * Description:       Real-time image optimization powered by Pictomancer.ai API.
 * Version:           0.1.1
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Pictomancer.ai
 * Author URI:        https://pictomancer.ai
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pictomancer-image-optimizer
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PICTOMANCER_VERSION', '0.1.1' );

require_once plugin_dir_path( __FILE__ ) . 'inc/class-pictomancer.php';
