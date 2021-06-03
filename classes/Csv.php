<?php

class CourseSequenceCsv {
    
    // save data
    function csv_buttons() {
        if ( isset( $_POST[ 'csv_upload_course_sequence' ] ) && !empty( $_FILES ) ):
            $this->handle_csv_file();
        elseif ( isset( $_POST[ 'csv_cancel_course_sequence' ] ) ):
            $this->cancel_csv();
        elseif ( isset( $_POST[ 'csv_export_course_sequence' ] ) ):
            $this->export_csv_file();
        endif;
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
    
    static function get_csv_files(){
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