<?php
/*
* This file the functionality of ajax for event listing and file upload.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WP_Event_Manager_Ajax class.
*/

class WP_Event_Manager_Ajax {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since  2.5
	 */
	private static $_instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @since  2.5
	 * @static
	 * @return self Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Constructor
	*/

	public function __construct() {

		add_action( 'init', array( __CLASS__, 'add_endpoint') );

		add_action( 'template_redirect', array( __CLASS__, 'do_em_ajax'), 0 );

		// EM Ajax endpoints

		add_action( 'event_manager_ajax_get_listings', array( $this, 'get_listings' ) );

		add_action( 'event_manager_ajax_upload_file', array( $this, 'upload_file' ) );

		// BW compatible handlers

		add_action( 'wp_ajax_nopriv_event_manager_get_listings', array( $this, 'get_listings' ) );

		add_action( 'wp_ajax_event_manager_get_listings', array( $this, 'get_listings' ) );

		add_action( 'wp_ajax_nopriv_event_manager_upload_file', array( $this, 'upload_file' ) );

		add_action( 'wp_ajax_event_manager_upload_file', array( $this, 'upload_file' ) );
	}
	
	/**
	 * Add our endpoint for frontend ajax requests
	*/

	public static function add_endpoint() {

		add_rewrite_tag( '%em-ajax%', '([^/]*)' );

		add_rewrite_rule( 'em-ajax/([^/]*)/?', 'index.php?em-ajax=$matches[1]', 'top' );

		add_rewrite_rule( 'index.php/em-ajax/([^/]*)/?', 'index.php?em-ajax=$matches[1]', 'top' );
	}

	/**
	 * Get Event Manager Ajax Endpoint
	 * @param  string $request Optional
	 * @param  string $ssl     Optional
	 * @return string
	 */

	public static function get_endpoint( $request = '%%endpoint%%', $ssl = null ) {

		if ( strstr( get_option( 'permalink_structure' ), '/index.php/' ) ) {

			$endpoint = trailingslashit( home_url( '/index.php/em-ajax/' . $request . '/', 'relative' ) );

		} elseif ( get_option( 'permalink_structure' ) ) {

			$endpoint = trailingslashit( home_url( '/em-ajax/' . $request . '/', 'relative' ) );

		} else {

			$endpoint = add_query_arg( 'em-ajax', $request, trailingslashit( home_url( '', 'relative' ) ) );
		}
		
		return esc_url_raw( $endpoint );
	}

	/**
	 * Check for WC Ajax request and fire action
	 */

	public static function do_em_ajax() {

		global $wp_query;

		if ( ! empty( $_GET['em-ajax'] ) ) {

			 $wp_query->set( 'em-ajax', sanitize_text_field( $_GET['em-ajax'] ) );
		}

   		if ( $action = $wp_query->get( 'em-ajax' ) ) {

   			if ( ! defined( 'DOING_AJAX' ) ) {

				define( 'DOING_AJAX', true );
			}
			
			// Not home - this is an ajax endpoint

			$wp_query->is_home = false;

   			do_action( 'event_manager_ajax_' . sanitize_text_field( $action ) );

   			die();
   		}
	}

	/**
	 * Get listings via ajax
	 */

	public function get_listings() {

		global $wp_post_types;

		$result            = array();

		$search_location   = sanitize_text_field( stripslashes( $_REQUEST['search_location'] ) );

		$search_keywords   = sanitize_text_field( stripslashes( $_REQUEST['search_keywords'] ) );

		$search_datetimes= isset( $_REQUEST['search_datetimes'] ) ? $_REQUEST['search_datetimes'] : '';

		$search_categories = isset( $_REQUEST['search_categories'] ) ? $_REQUEST['search_categories'] : '';

		$search_event_types= isset( $_REQUEST['search_event_types'] ) ? $_REQUEST['search_event_types'] : '';

		$search_ticket_prices= isset( $_REQUEST['search_ticket_prices'] ) ? $_REQUEST['search_ticket_prices'] : '';			

		$post_type_label   = $wp_post_types['event_listing']->labels->name;

		$orderby           = sanitize_text_field( $_REQUEST['orderby'] );

		if ( is_array( $search_datetimes) ) {

			$search_datetimes= array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_datetimes) ) );

		} else {

			$search_datetimes= array_filter( array( sanitize_text_field( stripslashes( $search_datetimes) ) ) );
		}

		if ( is_array( $search_categories ) ) {

			$search_categories = array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_categories ) ) );

		} else {

			$search_categories = array_filter( array( sanitize_text_field( stripslashes( $search_categories ) ) ) );
		}

		if ( is_array( $search_event_types) ) {

			$search_event_types= array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_event_types) ) );

		} else {

			$search_event_types= array_filter( array( sanitize_text_field( stripslashes( $search_event_types) ) ) );
		}		

		if ( is_array( $search_ticket_prices) ) {

			$search_ticket_prices= array_filter( array_map( 'sanitize_text_field', array_map( 'stripslashes', $search_ticket_prices) ) );

		} else {

			$search_ticket_prices= array_filter( array( sanitize_text_field( stripslashes( $search_ticket_prices) ) ) );
		}

		$args = array(

			'search_location'    => $search_location,

			'search_keywords'    => $search_keywords,

			'search_datetimes'  => $search_datetimes,

			'search_categories'  => $search_categories,

			'search_event_types'  => $search_event_types,

			'search_ticket_prices'  => $search_ticket_prices,			

			'orderby'            => $orderby,

			'order'              => sanitize_text_field( $_REQUEST['order'] ),

			'offset'             => ( absint( $_REQUEST['page'] ) - 1 ) * absint( $_REQUEST['per_page'] ),

			'posts_per_page'     => absint( $_REQUEST['per_page'] )
		);

		if ( isset( $_REQUEST['cancelled'] ) && ( $_REQUEST['cancelled'] === 'true' || $_REQUEST['cancelled'] === 'false' ) ) {

			$args['cancelled'] = $_REQUEST['cancelled'] === 'true' ? true : false;
		}

		if ( isset( $_REQUEST['featured'] ) && ( $_REQUEST['featured'] === 'true' || $_REQUEST['featured'] === 'false' ) ) {

			$args['featured'] = $_REQUEST['featured'] === 'true' ? true : false;

			$args['orderby']  = 'featured' === $orderby ? 'date' : $orderby;
		}

		ob_start();

		$events = get_event_listings( apply_filters( 'event_manager_get_listings_args', $args ) );

		$result['found_events'] = false;

		if ( $events->have_posts() ) : $result['found_events'] = true; ?>

			<?php while ( $events->have_posts() ) : $events->the_post(); ?>

				<?php get_event_manager_template_part( 'content', 'event_listing' ); ?>

			<?php endwhile; ?>

		<?php else : ?>

			<?php get_event_manager_template_part( 'content', 'no-events-found' ); ?>

		<?php endif;

		$result['html']    = ob_get_clean();

		$result['filter_value'] = array();	

		//categories
		if ( $search_categories ) {

			$showing_categories = array();

			foreach ( $search_categories as $category ) {

				$category_object = get_term_by( is_numeric( $category ) ? 'id' : 'slug', $category, 'event_listing_category' );

				if ( ! is_wp_error( $category_object ) ) {

					$showing_categories[] = $category_object->name;
				}
			}

			$result['filter_value'][] = implode( ', ', $showing_categories );
		}

		//event types
		if ( $search_event_types) {

			$showing_event_types = array();

			foreach ( $search_event_types as $event_type) {

				$event_type_object = get_term_by( is_numeric( $event_type) ? 'id' : 'slug', $event_type, 'event_listing_type' );

				if ( ! is_wp_error( $event_type_object ) ) {

					$showing_event_types[] = $event_type_object->name;
				}
			}
			
			$result['filter_value'][] = implode( ', ', $showing_event_types );
		}
		
		//datetimes

		if ($search_datetimes) 
		{	
			$showing_datetimes= array();			

			foreach ( $search_datetimes as $datetime) 

			{ 	
			    $showing_datetimes[]=WP_Event_Manager_Filters::get_datetime_value($datetime);
			}

			$result['filter_value'][] = implode( ', ', $showing_datetimes);		
		}
		
		//ticket prices	
		if ($search_ticket_prices) 
		{		
		    $showing_ticket_prices = array();	

			foreach ( $search_ticket_prices as $ticket_price) 
			{ 	
			    $showing_ticket_prices []= WP_Event_Manager_Filters::get_ticket_price_value($ticket_price);
			}	
			 $result['filter_value'][] = implode( ', ', $showing_ticket_prices );		
		}	

		if ( $search_keywords ) {
		    
			$result['filter_value'][] = '&ldquo;' . $search_keywords . '&rdquo;'; 	
		}		
       
        $last_filter_value = array_pop($result['filter_value']);   
        $result_implode=implode(', ', $result['filter_value']);
        if(  count($result['filter_value']) >= 1 )
        {
            $result['filter_value']= explode(" ",  $result_implode); 
            $result['filter_value'][]=  " &amp; ";
        }
        else
        {
            if(!empty($last_filter_value))
                $result['filter_value']= explode(" ",  $result_implode); 
        }      
        $result['filter_value'][] =  $last_filter_value ." " . $post_type_label;
        
		if ( $search_location ) {

			$result['filter_value'][] = sprintf( __( 'located in &ldquo;%s&rdquo;', 'wp-event-manager' ), $search_location );
		}

		if(sizeof( $result['filter_value'] ) > 1 ) 
        {	    
        	$message = sprintf( _n( 'Search completed. Found %d matching record.', 'Search completed. Found %d matching records.', $events->found_posts, 'wp-event-manager' ), $events->found_posts);
			$result['showing_applied_filters'] = true;
		} else {
		   
			$message = "";
			$result['showing_applied_filters'] = false;			
		}
		
		$search_values = array(
				'location'   => $search_location,
				'keywords'   => $search_keywords,
				'datetimes'  => $search_datetimes,
				'tickets'	 => $search_ticket_prices,
				'types'		 => $search_event_types,
				'categories' => $search_categories
		);
		$result['filter_value'] = apply_filters( 'event_manager_get_listings_custom_filter_text', $message, $search_values );
			
		//$result['filter_value'] = apply_filters( 'event_manager_get_listings_custom_filter_text', sprintf( __( 'Showing all %s', 'wp-event-manager' ), implode(' ', $result['filter_value'])) );
	
		
		// Generate RSS link
		$result['showing_links'] = event_manager_get_filtered_links( array(

			'search_keywords'   => $search_keywords,			

			'search_location'   => $search_location,

			'search_datetimes' => $search_datetimes,

			'search_categories' => $search_categories,

			'search_event_types' => $search_event_types,

			'search_ticket_prices' => $search_ticket_prices

		) );
		
		
		

		// Generate pagination

		if ( isset( $_REQUEST['show_pagination'] ) && $_REQUEST['show_pagination'] === 'true' ) {

			$result['pagination'] = get_event_listing_pagination( $events->max_num_pages, absint( $_REQUEST['page'] ) );
		}

		$result['max_num_pages'] = $events->max_num_pages;

		wp_send_json( apply_filters( 'event_manager_get_listings_result', $result, $events ) );
	}

	/**
	 * Upload file via ajax
	 *
	 * No nonce field since the form may be statically cached.
	 */

	public function upload_file() {
		
		if ( ! event_manager_user_can_upload_file_via_ajax() ) {
					wp_send_json_error( new WP_Error( 'upload', __( 'You must be logged in to upload files using this method.', 'wp-event-manager' ) ) );
					return;
		}

		$data = array( 'files' => array() );

		if ( ! empty( $_FILES ) ) {

			foreach ( $_FILES as $file_key => $file ) {

				$files_to_upload = event_manager_prepare_uploaded_files( $file );

				foreach ( $files_to_upload as $file_to_upload ) {

					$uploaded_file = event_manager_upload_file( $file_to_upload, array( 'file_key' => $file_key ) );

					if ( is_wp_error( $uploaded_file ) ) {

						$data['files'][] = array( 'error' => $uploaded_file->get_error_message() );

					} else {

						$data['files'][] = $uploaded_file;
					}
				}
			}
		}

		wp_send_json( $data );
	}
}

 WP_Event_Manager_Ajax::instance();