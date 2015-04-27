<?php
/**
 * @package   WP Location Search
 * @author    mmcachran
 * @license   GPL-2.0+
 *
 * Plugin Name: WP Location Search
 * Description: Search a list of locations plotted on Google Maps
 * Version:           1.0.0
 * Author:            mmcachran
 * Text Domain:       wp_location_search
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WP_LOCATION_SEARCH_VERSION', '1.0.0' );

// Are we in DEV mode?
if ( ! defined( 'WP_LOCATION_SEARCH' ) ) {
	define( 'WP_LOCATION_SEARCH', true );
}

// load the plugin
require_once( plugin_dir_path( __FILE__ ) . 'lib/location-search.php' );	
add_action( 'plugins_loaded', array( 'WP_Location_Search', 'get_instance' ) );