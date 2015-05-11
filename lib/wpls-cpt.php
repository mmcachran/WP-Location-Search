<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WP_Location_Search_CPT {
	public function __construct() {}

	public static function register_cpt() {
		$labels = array(
			'name'               => _x( 'Locations', 'post type general name', 'wp_location_search' ),
			'singular_name'      => _x( 'Location', 'post type singular name', 'wp_location_search' ),
			'menu_name'          => _x( 'Locations', 'admin menu', 'wp_location_search' ),
			'name_admin_bar'     => _x( 'Location', 'add new on admin bar', 'wp_location_search' ),
			'add_new'            => _x( 'Add New', 'location', 'wp_location_search' ),
			'add_new_item'       => __( 'Add New Location', 'wp_location_search' ),
			'new_item'           => __( 'New Location', 'wp_location_search' ),
			'edit_item'          => __( 'Edit Location', 'wp_location_search' ),
			'view_item'          => __( 'View Locations', 'wp_location_search' ),
			'all_items'          => __( 'All Locations', 'wp_location_search' ),
			'search_items'       => __( 'Search Locations', 'wp_location_search' ),
			'parent_item_colon'  => __( 'Parent Locations:', 'wp_location_search' ),
			'not_found'          => __( 'No locations found.', 'wp_location_search' ),
			'not_found_in_trash' => __( 'No locations found in Trash.', 'wp_location_search' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'location' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'thumbnail' )
		);

		register_post_type( 'location', $args );
	}
}