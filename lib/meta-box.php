<?php
if( ! class_exists( 'WPLS_Meta_Box' ) ):
class WPLS_Meta_Box {

	public function __construct( $wpls ) {
		$this->wpls = $wpls;
	}

	public $allowed_meta = array(
		'_wpls_address',
		'_wpls_city',
		'_wpls_state',
		'_wpls_zip',
	);

	public function meta_box_add() {
		add_meta_box( 'wpls-meta-box', 'Location Details', array( $this, 'meta_box_cb' ), 'location', 'normal', 'high' );
	}
	
	public function meta_box_cb() {
	    // $post is already set, and contains an object: the WordPress post
	    global $post;
	    $values = get_post_custom( $post->ID );
	    $address = isset( $values['_wpls_address'][0] ) ? esc_attr( $values['_wpls_address'][0] ) : '';
	    $city = isset( $values['_wpls_city'][0] ) ? esc_attr( $values['_wpls_city'][0] ) : '';
	    $state = isset( $values['_wpls_state'][0] ) ? esc_attr( $values['_wpls_state'][0] ) : '';
	    $zip = isset( $values['_wpls_zip'][0] ) ? esc_attr( $values['_wpls_zip'][0] ) : '';
	     
	    // We'll use this nonce field later on when saving.
	    wp_nonce_field( 'wpee_meta_box_nonce', 'meta_box_nonce' );
	    ?>     
	    <p>
			<label for="_wpls_address">Address</label> &nbsp; &nbsp;
	        <input type="text" id="_wpls_address" name="_wpls_address" value="<?php echo $address; ?>" /> 
	    </p>
	    <p>
			<label for="_wpls_city">City</label> &nbsp; &nbsp;
	        <input type="text" id="_wpls_city" name="_wpls_city" value="<?php echo $city; ?>" /> 
	    </p>
	    <p>
			<label for="_wpls_state">State</label> &nbsp; &nbsp;
	        <input type="text" id="_wpls_state" name="_wpls_state" value="<?php echo $state; ?>" /> 
	    </p>
	    <p>
			<label for="_wpls_zip">Zip</label> &nbsp; &nbsp;
	        <input type="text" id="_wpls_zip" name="_wpls_zip" value="<?php echo $zip; ?>" /> 
	    </p>
	    <?php  
	}
	
	public function meta_box_save( $post_id ) {
		// bail if we're autosaving
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		// bail if our nounce if not verified
		if( ! isset( $_POST['meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['meta_box_nonce'], 'wpee_meta_box_nonce' ) ) {
		    return;
		}

		// bail if our current user can't edit this post
		if( ! current_user_can( 'edit_post' ) ) {
			return;
		}
	         
		// save the post meta
		$full_address = '';
		foreach ( $this->allowed_meta as $meta_key ) {
			if ( isset( $_POST[$meta_key] ) ) {
				// save post_meta
				update_post_meta( $post_id, $meta_key, $_POST[$meta_key] );

				// get full address
				$full_address .= $_POST[$meta_key];
			}
		}

		// save coordinates
		$coors = $this->wpls->fetch_coordinates( $full_address );

		if ( $coors ) {
			update_post_meta( $post_id, '_wpls_lat', $coors['lat'] );
			update_post_meta( $post_id, '_wpls_lng', $coors['lng'] );
		}
	}
}
endif;