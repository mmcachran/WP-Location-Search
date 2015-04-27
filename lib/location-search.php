<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WP_Location_Search {
	const VERSION = '1.0.0';

	const GMAPS_API_KEY = 'AIzaSyANwMWdchK3Hdls935DT_QG8FHO6uNFRg8';

	public static 
		$url,
		$path,
		$name;

	protected $location_search_page_name = 'search-locations';
	protected $location_search_page_title = 'Search Locations';

	public static $text_domain = 'wp_location_search';

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
	
	public function enqueue_scripts( $data ) {
		wp_enqueue_script( 'wp-location-search', self::$url . 'location-search.js', array( 'jquery' ) );
		wp_localize_script( 'wp-location-search', 'wpls_config', $data );
	}
	
	public function add_shortcode( $params, $content = null ) {
		if(
			( isset( $params['video_id'] ) || isset( $params['playlist_id'] ) ) 
			&& isset( $params['type'] )
		) {
			$html = '<div class="article-media"><div class="video-container">';
			
			switch ( $params['type'] ) {
				case 'youtube':
					$id = isset ( $params['video_id'] ) ? $params['video_id'] : $params['playlist_id'];
					$html .= '<div id="yt-frame-'.$id.'" data-key="'.$params['video_id'].'" data-playlist-key="'.$params['playlist_id'].'"></div>';
					break;

				case 'vimeo':
					$html .= '<iframe id='.$params['video_id'].'src="//player.vimeo.com/video/'.$params['video_id'].'?portrait=0&color=333" api="1" class="vimeo-video" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
					break;
			}
		}
		
		return $html . '</div></div>';
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
}