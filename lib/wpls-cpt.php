<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WP_Location_Search_CPT {
	public function __construct() {}

	public static function register_cpt() {
		$labels = array(
			'name'               => _x( 'Locations', 'post type general name', WP_Location_Search::$text_domain ),
			'singular_name'      => _x( 'Location', 'post type singular name', WP_Location_Search::$text_domain ),
			'menu_name'          => _x( 'Locations', 'admin menu', WP_Location_Search::$text_domain ),
			'name_admin_bar'     => _x( 'Location', 'add new on admin bar', WP_Location_Search::$text_domain ),
			'add_new'            => _x( 'Add New', 'location', WP_Location_Search::$text_domain ),
			'add_new_item'       => __( 'Add New Location', WP_Location_Search::$text_domain ),
			'new_item'           => __( 'New Location', WP_Location_Search::$text_domain ),
			'edit_item'          => __( 'Edit Location', WP_Location_Search::$text_domain ),
			'view_item'          => __( 'View Locations', WP_Location_Search::$text_domain ),
			'all_items'          => __( 'All Locations', WP_Location_Search::$text_domain ),
			'search_items'       => __( 'Search Locations', WP_Location_Search::$text_domain ),
			'parent_item_colon'  => __( 'Parent Locations:', WP_Location_Search::$text_domain ),
			'not_found'          => __( 'No locations found.', WP_Location_Search::$text_domain ),
			'not_found_in_trash' => __( 'No locations found in Trash.', WP_Location_Search::$text_domain )
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