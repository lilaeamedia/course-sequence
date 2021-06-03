<?php

/**
 * Class WPRB_Rest_Server
 *
 * Example rest server that allows for CRUD operations on the wp_options table
 *
 */
class CourseSequenceRestServer extends WP_Rest_Controller {

	public $namespace = 'wprb/';
	public $version = 'v1';


	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		$namespace = $this->namespace . $this->version;

        
        /**
         * methods required for editor
         */
        // get programs
        // get sequence
        // create section
        // delete section
        // reorder sections in program
        // create row
        // delete row
        // reorder rows in section
        // update row
        // update section
		register_rest_route( $namespace, '/records', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_options' ),
				'permission_callback' => array( $this, 'get_options_permission' )
			),
		) );

		register_rest_route( $namespace, '/programs', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_programs' ),
				'permission_callback' => array( $this, 'get_programs_permission' )
			),
		) );

		register_rest_route( $namespace, '/sequence/(?P<program>(\d*)+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_sequence' ),
				'permission_callback' => array( $this, 'get_programs_permission' )
			),
		) );

		register_rest_route( $namespace, '/record/(?P<slug>(.*)+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_option' ),
				'permission_callback' => array( $this, 'get_options_permission' )
			),
			array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => array( $this, 'edit_option' ),
				'permission_callback' => array( $this, 'get_options_permission' )
			),
		) );
	}

	public function get_programs( WP_REST_Request $request ) {
        global $wpdb;
        $rows = array();
        $query = $wpdb->prepare( 
            "SELECT p.ID, p.post_title 
            FROM {$wpdb->prefix}posts p 
            WHERE p.post_type = 'program'
              AND p.post_status = 'publish'
            ORDER BY p.post_title"
        );
        foreach( $wpdb->get_results( $query ) as $row ):
            $rows[ $row->ID ] = $row->post_title;
        endforeach;
        return $rows;
        
	}

    /**
     * returns the entire sequence array for a program
     * ordered by section[sort_order], then by row[sort_order]
     * 
     */
    public function get_sequence( WP_REST_Request $request ) {
        global $wpdb;
		$params = $request->get_params();
		if ( ! isset( $params['program'] ) || empty( $params['program'] ) ) {
			return new WP_Error( 'no-param', __( 'No program param' ) );
		}
        $sequence = array();
        $sections = array();
        $section_map = array();
        $sql = "SELECT s.* FROM {$wpdb->prefix}postmeta pm
        JOIN {$wpdb->prefix}course_sequence s ON s.program_id = pm.meta_value
            AND pm.meta_key = 'program_id'
            AND pm.post_id = %d";
        $res = $wpdb->get_results( $wpdb->prepare( $sql, $params['program'] ), ARRAY_A );
        foreach ( $res as $rowarr ):
            if ( 'section' == $rowarr[ 'row_type' ] )
                $section_map[ $rowarr[ 'section_id' ] ] = $rowarr[ 'sort_order' ];
            $sections[ $rowarr[ 'section_id' ] ][] = $rowarr;
        endforeach;
        //now sort each section and return data in order without sort values
        asort( $section_map );
        foreach ( $section_map as $section_id => $section_order ):
            usort( $sections[ $section_id ], array( $this, 'sort_section' ) );
            $sequence[] = array( 
                'section_id' => $section_id,
                'rows' => $sections[ $section_id ],
            );
        endforeach;
        return $sequence;

    }
    
    public function sort_section( $a, $b ){
        if ($a[ 'sort_order' ] == $b[ 'sort_order' ] )
            return 0;
        return ( $a[ 'sort_order' ] < $b[ 'sort_order' ] ) ? -1 : 1;
    }
    
	public function get_options( WP_REST_Request $request ) {
		return wp_load_alloptions();
	}

	public function get_options_permission() {

		if ( ! current_user_can( 'install_themes' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to manage options.', 'course-sequence' ), array( 'status' => 401 ) );
		}

		return true;
	}

	public function get_programs_permission() {

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permissions to manage course sequences.', 'course-sequence' ), array( 'status' => 401 ) );
		}

		return true;
	}

	public function get_option( WP_REST_Request $request ) {

		$params = $request->get_params();

		if ( ! isset( $params['slug'] ) || empty( $params['slug'] ) ) {
			return new WP_Error( 'no-param', __( 'No slug param' ) );
		}

		$converted_slug = $this->_convert_slug( $params['slug'] );

		$opt_value = get_site_option( $converted_slug );

		if ( ! $opt_value ) {
			return new WP_Error( 'option-not-found', __( 'Option not found' ) );
		}

		return $opt_value;
	}

	public function edit_option( WP_REST_Request $request ) {
		$params = $request->get_params();

		if ( ! isset( $params['slug'] ) || empty( $params['slug'] ) ) {
			return new WP_Error( 'no-param', __( 'No slug param' ) );
		}

		$body = $request->get_body();

		if ( empty( $body ) ) {
			return new WP_Error( 'no-body', __( 'Request body empty' ) );
		}

		$decoded_body = json_decode( $body );

		if ( $decoded_body ) {
			if ( isset( $decoded_body->key, $decoded_body->value ) ) {

				if ( ! get_site_option( $decoded_body->key ) ) {
					return false;
				}

				if ( update_option( $decoded_body->key, $decoded_body->value ) ) {
					return true;
				}
			}
		}

		return false;
	}
}