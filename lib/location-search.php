<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WP_Location_Search {
	const VERSION = '1.0.0';

	const GMAPS_API_KEY = '';

	public static 
		$url,
		$path,
		$name;

	protected $location_search_page_name = 'search-locations';
	protected $location_search_page_title = 'Search Locations';

	/**
	 * Instance of this class.
	 *
	 * @since   0.4.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since     0.4.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Sets up our plugin
	 * @since  0.1.0
	 */
	private function __construct() {
		// Useful variables
		self::$url  = trailingslashit( plugin_dir_url( __FILE__ ) );
		self::$path = trailingslashit( dirname( __FILE__ ) );
		self::$name = __( 'WP Location Search', 'wp_location_search' );

		add_action( 'init', array( $this, 'init' ) );

		// register CPT
		require_once( self::$path . 'wpls-cpt.php' );
		add_action( 'init', array( 'WP_Location_Search_CPT', 'register_cpt' ) );

		// register meta box
		add_action( 'add_meta_boxes', array( $this->meta_box(), 'meta_box_add' ) );
		add_action( 'save_post', array( $this->meta_box(), 'meta_box_save' ) );

		// show location search map
		add_action( 'init', array( $this, 'check_page_existence' ) );
		add_shortcode( 'location_search', array ( $this, 'shortcode' ) );

		// add content filter for location search page
		add_filter( 'the_content', array( $this, 'location_search_content_filter' ) );

		add_action( 'wp_ajax_wpls_fetch_locations', array( $this, 'fetch_locations' ) );
		add_action( 'wp_ajax_nopriv_wpls_fetch_locations', array( $this, 'fetch_locations' ) );
	}

	public function meta_box() {
		if ( ! isset( $this->meta_box ) ) {
			require_once( self::$path . 'meta-box.php' );
			$this->meta_box = new WPLS_Meta_Box( $this );
		}

		return $this->meta_box;
	}

	/**
	 * Init hooks
	 * @since  0.1.0
	 * @return null
	 */
	public function init() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp_location_search' );
		load_textdomain( 'wp_location_search', WP_LANG_DIR . '/wp-location-search/wp-location-search-' . $locale . '.mo' );
		load_plugin_textdomain( 'wp_location_search', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	public function shortcode( $atts ) {
		return $this->get_location_search_page_content();
	}

	public function location_search_content_filter( $content ) {
		global $post;

		// failsafe in case this isn't the link accounts page
		if ( $post->post_name !== $this->location_search_page_name ) {
			return $content;
		}

		$location_search_content = $this->get_location_search_page_content();

		// make sure we have content
		return ! empty( $location_search_content ) ? $location_search_content : $content;
	}

	public function get_location_search_page_content() {
		if ( file_exists( self::$path . 'page-content.php' ) ) {
			$data = array(
				'cancel_message' => __( 'Unless you save your post you will lose any changes you have made.  Are you sure you want to leave this page?'),
				'redirect_url'   => site_url(),
				'nonce'			 => wp_create_nonce( 'wp-location-search' ),
				'ajaxurl'		 => admin_url('admin-ajax.php'),
			);

			// enqueue necessary scripts
			self::enqueue_scripts( $data );

			ob_start();
			include( self::$path . 'page-content.php' );
			$content = ob_get_contents();
			ob_end_clean();

			return $content;
		}
	}

	public function check_page_existence() {
		$this->location_search_page_name = apply_filters( 'location_search_page_name', $this->location_search_page_name );

		$this->location_search_page_title = apply_filters( 'location_search_page_title' , $this->location_search_page_title );

		// Create 'location search' page if it doesn't exist.
		$exists = get_page_by_path( $this->location_search_page_name );

		if ( ! $exists ) {
			$this->add_location_search_page();
		}
	}

	protected function add_location_search_page() {
		wp_insert_post( array(
			'post_type'   => 'page',
			'post_title'  => $this->location_search_page_title,
			'post_name'   => $this->location_search_page_name,
			'post_status' => 'publish',
			'post_content' => ''
		) );
	}
	
	public static function enqueue_scripts( $data = array() ) {
		// CSS
		wp_enqueue_style( 'wpls-styles', self::$url . 'location-search.css', array(), WP_LOCATION_SEARCH_VERSION, true );

		// JS
		wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp', array( 'jquery' ), WP_LOCATION_SEARCH_VERSION );
		wp_enqueue_script( 'wp-location-search', self::$url . 'location-search.js', array( 'jquery', 'google-maps' ), WP_LOCATION_SEARCH_VERSION );
		wp_localize_script( 'wp-location-search', 'wpls_config', $data );
	}

	public function fetch_coordinates( $address ) {
		$address = str_replace( " ", "+", $address );
		$url = "https://maps.google.com/maps/api/geocode/json?sensor=false&address=$address&key=" . self::GMAPS_API_KEY;
		$response = file_get_contents( $url );
		$json = json_decode( $response,true );
	
		if( isset( $json['results'][0] ) ){
			return array(
				'lat' => $json['results'][0]['geometry']['location']['lat'],
				'lng' => $json['results'][0]['geometry']['location']['lng'],
			);
		}
	
		// Did we go over our API limit today?
		if( $json['status'] == 'OVER_QUERY_LIMIT' ) {
			echo "<p class='error'>We went over our API limit today!</p>";
			die;
		}
	
		// if we couldn't find the coordinates, return false
		return false;
	}

	/**
	 * Search for locations
	 *
	 * @since     1.0.0
	 *
	 * @return    null    outputs a JSON string to be consumed by an AJAX call
	 */
	public function fetch_locations() {
		$security_check_passes = (
			! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] )
			&& 'xmlhttprequest' === strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] )
			&& isset( $_GET['nonce'] )
			&& wp_verify_nonce( $_GET['nonce'],  'wp-location-search' )
		);

		if ( ! $security_check_passes ) {
			wp_send_json_error( $_GET );
			wp_die();
		}
		
		$search = $_GET['q'];

		$locations = get_posts( array( 'post_type' => 'location' ) );

		$results  = array();
		foreach ( $locations as $location ) {
			$lat = get_post_meta( $location->ID, '_wpls_lat', true );
			$lng = get_post_meta( $location->ID, '_wpls_lng', true );

			$address = get_post_meta( $location->ID, '_wpls_address', true );
			$city = get_post_meta( $location->ID, '_wpls_city', true );
			$state = get_post_meta( $location->ID, '_wpls_state', true );
			$zip = get_post_meta( $location->ID, '_wpls_zip', true );

			$permalink = get_permalink( $location->ID );

			$results[] = array( 
				'id' => $location->ID, 
				'title' => $location->post_title,
				'lat' => $lat,
				'lng' => $lng,
				'address' => $address,
				'city' => $city,
				'state' => $state,
				'zip' => $zip,
				'permalink' => $permalink,
			);
		}
		
		wp_send_json_success( $results );
	}
}
