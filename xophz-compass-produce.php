<?php

/**
 * The plugin bootstrap file for Xophz Local Produce.
 *
 * @link              https://hallofthegods.com/
 * @since             1.0.0
 * @package           Xophz_Compass_Produce
 *
 * @wordpress-plugin
 * Category:          Command Deck
 * Group:             Economics
 * Plugin Name:       Xophz Local Produce
 * Plugin URI:        https://github.com/HalloftheGods/xophz-compass-produce
 * Description:       Universal EDVEX Data Royalty Engine & Farmer's Market for COMPASS & YouMeOS.
 * Version:           26.9.5
 * Author:            Hall of the Gods, Inc.
 * Author URI:        https://hallofthegods.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       xophz-compass-produce
 * Domain Path:       /languages
 * Update URI:        https://github.com/HalloftheGods/xophz-compass-produce
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'XOPHZ_COMPASS_PRODUCE_VERSION', '26.9.5' );
define( 'XOPHZ_COMPASS_PRODUCE_PATH', plugin_dir_path( __FILE__ ) );
define( 'XOPHZ_COMPASS_PRODUCE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation hook handler.
 */
function activate_xophz_compass_produce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-xophz-compass-produce-activator.php';
	Xophz_Compass_Produce_Activator::activate();
}

/**
 * Deactivation hook handler.
 */
function deactivate_xophz_compass_produce() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'activate_xophz_compass_produce' );
register_deactivation_hook( __FILE__, 'deactivate_xophz_compass_produce' );

/**
 * Register with COMPASS performance widgets.
 */
add_filter( 'compass_perform_widgets', function( $widgets ) {
	$widgets[] = array(
		'key'           => 'produce-market-overview',
		'plugin'        => 'xophz-compass-produce',
		'title'         => 'Local Produce Market',
		'description'   => 'Real-time telemetry of sovereign data crates, active leases, and royalty payouts.',
		'icon'          => 'fal fa-apple-crate',
		'color'         => '#4caf50',
		'spark_id'      => 'local-produce',
		'metric_label'  => 'Active Carts',
		'metric_getter' => function() {
			require_once XOPHZ_COMPASS_PRODUCE_PATH . 'includes/class-xophz-compass-produce-engine.php';
			return count( Xophz_Compass_Produce_Engine::get_active_providers() );
		}
	);
	return $widgets;
} );

/**
 * Core plugin class bootstrap.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-xophz-compass-produce.php';

function run_xophz_compass_produce() {
	$plugin = new Xophz_Compass_Produce();
	$plugin->run();
}
run_xophz_compass_produce();
