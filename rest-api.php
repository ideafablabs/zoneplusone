<?php

class ZonePlusOne_Controller extends WP_REST_Controller {
	public function register_routes() {
		$namespace = 'zoneplusone/v1';        

		register_rest_route( $namespace, '/reader/', [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_token_id' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'add_token_id' ),
				'permission_callback' => array( $this, 'post_items_permissions_check' )
			),

		]);

		register_rest_route( $namespace, '/zones/', [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_all_zones' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
			),

		]);

		register_rest_route( $namespace, '/zones/(?P<zone_id>\d+)', [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_zone_count' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' )
				),
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'plus_one_zone' ),
				'permission_callback' => array( $this, 'post_items_permissions_check' )
			),

		]);
	}

	public function get_all_zones($request) {

		global $IFLZonePlusOne;        

		// Get Zone Count by ID.
		$zone_counts = $IFLZonePlusOne->get_zone_plus_ones_array_for_dashboard();

		// Error Cases.
		if (is_wp_error($zone_counts)) {
			$IFLZonePlusOne->log_action($zone_counts);
			return $error;
		}

		// Success Case.
		return new WP_REST_Response($zone_counts, 200);
	}

	public function get_zone_count($request) {

		global $IFLZonePlusOne;

		$zone_id = $request['zone_id'];        

		// Get Zone Count by ID.
		$zone_count = $IFLZonePlusOne->get_total_plus_one_count_by_zone_id($zone_id);

		// Error Cases.
		if (is_wp_error($zone_count)) {
			$IFLZonePlusOne->log_action($zone_count);
			return $error;
		}
		
		/// Somewhere in here we want to return either Total or Month Count

		// Success Case.
		return new WP_REST_Response($zone_count, 200);
	}

	public function plus_one_zone($request) {

		global $IFLZonePlusOne;

		$zone_id = $request->get_param( 'zone_id' );
		$token_id = $request->get_param( 'token_id' );

		// return new WP_REST_Response($token_id.' '.$zone_id , 200);	
		/// Add Error Logging.

		$errors = new WP_Error;

		// Error Cases
		if ($zone_id === false) {
			$errors->add('no_zone_id', 'Capturing Zone ID Failed.',array( 'status' => 400 ) );
		}
		if (empty($token_id)) {
			$errors->add('no_token_id', 'Capturing Token ID Failed.' ,array( 'status' => 400 ));
		}

		if ( empty( $errors->get_error_codes() ) ) {
				
			$response = $IFLZonePlusOne->add_plus_one_to_plus_one_zones_table($zone_id,$token_id);
			
			if ( is_wp_error( $response ) ) { 
				/// Log error.  
				error_log($response->get_error_message());              
				return new WP_REST_Response($response->get_error_messages() , 200);  
			} else {
				/// Log success.                
				return new WP_REST_Response($response , 201);	
			}
			
		} else {
			// return $response;
			/// Log error.
			return $errors;
		}

	}

	public function get_token_id($request) {

		$reader_value = get_option('token_id');

		// in case we ever add more readers
		// $reader_id = $request['reader_id'];
		// $reader_value = get_option('reader_'.$reader_id);

		if ($reader_value === false) {
			return new WP_Error( 'no_value', 'Retrieving reader value failed.', array( 'status' => 404 ) );
		}
		if ($reader_value == 0) {
			return new WP_Error( 'no_id', 'No new ID available.', array( 'status' => 200 ) );
		}

		// Clear the reader value.
		// update_option('reader_'.$reader_id,0);

		return new WP_REST_Response($reader_value, 200);
	}

	public function add_token_id($request) {
		$token_id = $request->get_param( 'token_id' );
		// $reader_value = $request->get_param( 'reader_value' );
		// if (!empty($reader_value) && !empty($token_id) ) {
		
		if (!empty($token_id)) {
	
			update_option('token_id',$token_id);
			// update_option('reader_'.$reader_id,$token_id);

			return new WP_REST_Response("Reader Value Updated: ".$token_id, 200);
		} else {
			/// Could be error object here?
			return new WP_REST_Response("NFC parameters not found", 200);
		}
	}

	public function get_items_permissions_check($request) {
		/// Do we need this? What can we check here?
		return true;
	}
	public function post_items_permissions_check($request) {
		/// Do we need this? What can we check here?
		return true;
	}
}

class MemberMouse_Controller extends WP_REST_Controller {
	public function register_routes() {
		$namespace = 'members/v1';        

		register_rest_route( $namespace, '/active/', [
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_active_members' ),
				'permission_callback' => array( $this, 'get_members_permissions_check' )
				)			
		]);

		// We can add another API call for checking if a member is active without exposing anything. IF email, reply true or false.

	}
	public function get_active_members($request) {

		global $IFLZonePlusOne;        

		// Get array of all members that are active.
		$active_members = $IFLZonePlusOne->get_list_of_active_membermouse_users();

		// Error Cases.
		if (is_wp_error($active_members)) {			
			$IFLZonePlusOne->log_action($active_members);
			return $error;
		}		
		// Success Case.
		return new WP_REST_Response($active_members, 200);
	}

	public function get_members_permissions_check($request) {
		
		// $credentials = $request->get_param( 'credentials' );
		/// check credentials before exposing member emails.

		return true;
	}
}



?>