<?php

class CourseSequenceAdmin {
	public $namespace = 'wprb/';
	public $version = 'v1';
    // menu item
    function __construct() {
        $hook = add_menu_page(
            'course-sequence', 
            "Course Sequence", 
            'edit_theme_options', 
            "course-sequence", 
            array( $this, 'options_page' ),
            'dashicons-admin-generic'
        );
        // only load plugin-specific data 
        // when options page is loaded
        add_action( 'load-' . $hook, array( $this, 'options_init' ) );
    }
    
    // save data
    function options_init() {
        if ( isset( $_POST[ 'csv_upload_course_sequence' ] ) && !empty( $_FILES ) ):
            $this->handle_csv_file();
        elseif ( isset( $_POST[ 'csv_cancel_course_sequence' ] ) ):
            $this->cancel_csv();
        elseif ( isset( $_POST[ 'csv_export_course_sequence' ] ) ):
            $this->export_csv_file();
        endif;
        add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_admin' ) );
    }
    
    // Option page
    // interface for csv import / export
    // interface for loading data ( react ) - see react design
    function options_page() {
        global $pagenow;
        // load admin page
        include( COURSE_SEQUENCE_PLUGIN_DIR . '/views/editor.php' );
    }
    
    // scripts and styles
    function enqueue_admin(){
		wp_register_script( 'course-sequence-bundle', COURSE_SEQUENCE_PLUGIN_URL . 'dist/bundle.js', array(), NULL, 'all' );

		wp_localize_script( 'course-sequence-bundle', 'wpApiSettings', array(
			'root' => esc_url_raw( rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'wprb_ajax_base' => $this->namespace . $this->version,
			'wprb_basic_auth' => defined( 'WPRB_AJAX_BASIC_AUTH' ) ? WPRB_AJAX_BASIC_AUTH : null,
		) );

		wp_enqueue_script( 'course-sequence-bundle' );
		wp_add_inline_script( 'course-sequence-bundle', '', 'before' );

		wp_enqueue_style( 'course-sequence-bundle-styles', COURSE_SEQUENCE_PLUGIN_URL . 'dist/style.bundle.css', array(), NULL, 'all' );
    }

    // handle upload
    function handle_csv_file(){
        $uploads = wp_upload_dir();
        $tempfile = $uploads[ 'basedir' ] . "/" . uniqid() . "_temp_course_sequence.csv";
        //import handling (test CSV)
        if ( !file_exists( $tempfile ) ):
            if ( current_user_can( 'upload_files' ) && current_user_can( 'import' ) ):
                $error = '';
                if ( 'text/csv' == $_FILES['importfile']['type']
                    || ( 'application/vnd.ms-excel' == $_FILES[ 'importfile' ][ 'type' ]
                        && preg_match( "{\.csv$}", $_FILES['importfile']['name'] ) ) ): 
                    if ( move_uploaded_file( $_FILES['importfile']['tmp_name'], $tempfile ) ):
                        $error = "File Uploaded Successfully.";
                        $this->spawn_background_process( $tempfile );
                    else:
                        $error = "Could not upload the file. Check your site's directory permissions.";
                    endif;
                else:
                    $error = "Only CSV imports are currently supported. This filetype was: {$_FILES['importfile']['type']}."; 
                endif;
            else:                    
                $error = 'User unauthorized';
            endif;
            wp_redirect( $this->get_redirect_url( $error ) );
            die();
        endif;
    }
    
    function get_csv_files(){
        $files = array();
        $uploads = wp_upload_dir();
        $dir = $uploads[ 'basedir' ]; // . "/" . uniqid() . "_temp_course_sequence.csv";
        if ( is_dir( $dir ) ):
            if ( $dh = opendir( $dir ) ):
                while ( FALSE !== ( $file = readdir( $dh ) ) ):
                    if ( strstr( $file, '_temp_course_sequence.csv' ) )
                        $files[] = $dir . '/' . $file;
                endwhile;
                closedir($dh);
            endif;
        endif;
        return $files;
    }
    
    function cancel_csv(){
        foreach ( $this->get_csv_files() as $filename ):
            if ( file_exists( $filename ) )
                @unlink( $filename );
        endforeach;
        wp_redirect( $this->get_redirect_url( 'Course Sequence processing canceled.' ) );
        die();
    }
    
    function export_csv_file(){
        $cse = new CourseSequenceExport();
        $cse->export();
        exit();
    }
    
    function get_redirect_url( $error = NULL ){
        return admin_url() . 'admin.php?page=course-sequence' . ( $error ? '&error=' . urlencode( $error ) : '' );
    }
        
    function spawn_background_process( $tempfile ){
        // make sure backgroundprocess class exists
        if ( class_exists( 'CourseSequenceQueue' ) ):
            // spawn background process for loading
            $q = new CourseSequenceQueue();
            $q->push_to_queue( $tempfile );
            $q->save()->dispatch();
        endif;
    }

}