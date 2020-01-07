<?php

class ZonePlusOne_Controller extends WP_REST_Controller {
    public function register_routes() {
        $namespace = 'zoneplusone/v1';
        $path = 'zone/(?P<zone_id>\d+)';

        register_rest_route( $namespace, '/zone/', [
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_all_zones' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' )
            ),

        ]);

        register_rest_route( $namespace, '/zone/(?P<zone_id>\d+)', [
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
    public function get_all_zones($request) {

    	global $IFLZonePlusOne;        

        // Get Zone Count by ID.
        $zone_counts = $IFLZonePlusOne->get_zone_plus_ones_array_for_dashboard();

        // Error Cases.
        if (is_wp_error($zone_counts)) {
            $IFLZonePlusOne->log($zone_counts);
            return $error;
        }

        // Success Case.
        /// it appears that WP_REST_Response json-encodes already.
        // $zone_counts = json_encode($zone_counts);
        return new WP_REST_Response($zone_counts, 200);
    }

    public function get_item($request) {

        global $IFLZonePlusOne;

        $zone_id = $request['zone_id'];        

        // Get Zone Count by ID.
        $zone_count = $IFLZonePlusOne->get_total_plus_one_count_by_zone_id($zone_id);

        // Error Cases.
        if (is_wp_error($zone_count)) {
            $IFLZonePlusOne->log($zone_count);
            return $error;
        }
        
        /// Somewhere in here we want to return either Total or Month Count

        // Success Case.
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
            	return new WP_REST_Response($response , 200);	
            }
            
        } else {
            // return $response;
        	/// Log error.
            return $errors;
        }

    }

    public function get_items_permissions_check($request) {
        /// Do we need this? What can we check here?
        return true;
    }
}


?>
