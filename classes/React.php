<?php


class CourseSequenceReact {

	public $plugin_domain;
	public $views_dir;
	public $version;

	public function __construct() {
		$this->plugin_domain = 'course-sequence';
		$this->views_dir     = trailingslashit( COURSE_SEQUENCE_PLUGIN_DIR ) . 'server/views';
		$this->version       = '1.0';

		require_once COURSE_SEQUENCE_PLUGIN_DIR . '/server/wprb-rest-server.php';
		$wprb_rest_server = new WPRB_Rest_Server();
		$wprb_rest_server->init();

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	public function admin_menu() {
		$title = __( 'Course Sequence', $this->plugin_domain );

		$hook_suffix = add_management_page( 
            $title, 
            $title, 
            'export', 
            $this->plugin_domain, 
            array( $this, 'load_admin_view' )
        );

		add_action( 'load-' . $hook_suffix, array( $this, 'load_assets' ) );
	}

	public function load_view( $view ) {
		$path = trailingslashit( $this->views_dir ) . $view;

		if ( file_exists( $path ) ) {
			include $path;
		}
	}

	public function load_admin_view() {
		$this->load_view( 'admin.php' );
	}

	public function load_assets() {
        $a = new CourseSequenceCsv();
        $a->csv_buttons();
die( __METHOD__ );
		wp_register_script( $this->plugin_domain . '-bundle', COURSE_SEQUENCE_PLUGIN_URL . 'dist/bundle.js', array(), $this->version, 'all' );

		wp_localize_script( $this->plugin_domain . '-bundle', 'wpApiSettings', array(
			'root' => esc_url_raw( rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'wprb_ajax_base' => defined( 'WPRB_AJAX_BASE' ) ? WPRB_AJAX_BASE : '',
			'wprb_basic_auth' => defined( 'WPRB_AJAX_BASIC_AUTH' ) ? WPRB_AJAX_BASIC_AUTH : null,
		) );

		wp_enqueue_script( $this->plugin_domain . '-bundle' );
		wp_add_inline_script( $this->plugin_domain . '-bundle', '', 'before' );

		wp_enqueue_style( $this->plugin_domain . '-bundle-styles', COURSE_SEQUENCE_PLUGIN_URL . 'dist/style.bundle.css', array(), $this->version, 'all' );
	}
}

