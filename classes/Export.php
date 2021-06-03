<?php

class CourseSequenceExport {
    var $tempfile;
    function __construct(){
        $uploads = wp_upload_dir();
        $dir = $uploads[ 'basedir' ];
        $this->tempfile = $uploads[ 'basedir' ] . '/export_temp_course_sequence.csv';
    }
    
    // Exporter
    function export(){
        global $wpdb;
        $res = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}course_sequence ", ARRAY_A );
        $counter = 0;
        $file = fopen( $this->tempfile, 'w' );
        foreach ( $res as $rowarr ):
            // if first post, generate to key row - post, postmeta, taxonomies
            if ( !$counter ):
                // if first post, write key row
                $key_row = array_keys( $rowarr );
                fputcsv( $file, $key_row );
            endif;
            fputcsv( $file, array_values( $rowarr ) );
            $counter++;
        // end each row
        endforeach;
        fclose( $file );
        
        // deliver file to browser
        // output headers so that the file is downloaded rather than displayed
        header('Content-type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="export_course_sequence.csv"');

        // do not cache the file
        header('Pragma: no-cache');
        header('Expires: 0');
        // encode UTF-8 BOM
        echo  chr(0xEF).chr(0xBB).chr(0xBF) . file_get_contents( $this->tempfile );
        @unlink( $this->tempfile );
        exit();
    }
}