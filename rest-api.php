<?php

class ZonePlusOne_Controller extends WP_REST_Controller {
    public function register_routes() {
        $namespace = 'zoneplusone/v1';
        $path = 'zone/(?P<zone_id>\d+)';

        register_rest_route( $namespace, '/' . $path, [
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' )
                ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'post_item' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),

        ]);
    }
    public function get_item($request) {

    	global $IFLZonePlusOne;

        $zone_id = $request['zone_id'];        

        /// This needs to be get_zone_count_by_id
        $zone_count = $IFLZonePlusOne->get_total_plus_one_count_by_zone_id($zone_id);
        // get_option('zone_'.$zone_id);
        // $reader_value = get_option('zone_'.$reader_id);

        // Error Cases
        if ($zone_count === false) {
            return new WP_Error( 'no_value', 'Retrieving zone count failed.', array( 'status' => 404 ) );
        }        
        // if ($zone_count == 0) {
        //     return new WP_Error( 'no_id', 'No new ID available.', array( 'status' => 200 ) );
        // }        

        // Somewhere in here we want to return either Total or Month Count

        // Success Case
        return new WP_REST_Response($zone_count, 200);
    }

    public function post_item($request) {

    	global $IFLZonePlusOne;

        $zone_id = $request->get_param( 'zone_id' );
        $token_id = $request->get_param( 'token_id' );

        // return new WP_REST_Response($token_id.' '.$zone_id , 200);	
        /// Add Error Logging.

        $errors = new WP_Error;

        // Error Cases
        if ($zone_id === false) {
			$errors->add('no_zone_id', 'Capturing Zone ID Failed.' );
        }
        if (empty($token_id)) {
			$errors->add('no_token_id', 'Capturing Token ID Failed.' );
        }

        if ( empty( $errors->get_error_codes() ) ) {
				
            $response = $IFLZonePlusOne->add_plus_one_to_plus_one_zones_table($zone_id,$token_id);
            
            if ( is_wp_error( $response ) ) { 
            	/// Log error.
            	return $response->get_error_messages();
            } else {
            	/// Log success.
            	return $response;
            	return new WP_REST_Response("Zone ".$zone_id.' +1' , 200);	
            }
            
        } else {
        	/// Log error.
            return $errors->get_error_messages();
        }

    }

    public function get_items_permissions_check($request) {
        /// Do we need this? What can we check here?
        return true;
    }
}


?>
