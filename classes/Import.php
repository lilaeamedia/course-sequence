<?php

class CourseSequenceImport {
    var $filename;
    var $cleared;
    var $error_rows;
    var $added;
    var $programs;
    // Importer
    function __construct( $file ){
        $this->filename     = $file;
        $this->cleared      = array();
        $this->error_rows   = array();
        $this->added        = 0;
        $this->programs     = array();
    }

    function log( $msg ){
        file_put_contents( COURSE_SEQUENCE_PLUGIN_DIR . '/importlog.txt', current_time( 'mysql' ) . ' ' . $msg . "\n", FILE_APPEND );
    }
    
    // handle file read loop
    function load_csv(){
        
        if ( file_exists( $this->filename ) ):
            $this->get_program_ids();
            // Read a CSV file
            $handle = fopen( $this->filename, "r" );

            // Optionally, you can keep the number of the line where
            // the loop its currently iterating over
            $count = 0;

            // Iterate over every line of the file
            while ( ( $raw_string = fgets( $handle ) ) !== false):
                // Increase the current line
                $count++;
                // Parse the raw csv string: "1, a, b, c"
                $row_arr = array();
                $row = str_getcsv( $raw_string );
                if ( $count == 1 ):
                    $header_row = $row;
                    if ( $this->header_ok( $header_row ) ):
                        continue;
                    else:
                        $this->error_rows[] = $header_row;
                        break;
                    endif;
                endif;
                foreach ( $header_row as $key )
                    $row_arr[ $key ] = array_shift( $row );
                if ( $this->program_exists( $row_arr[ 'program_id' ] ) ):
                    // remove existing rows for this program
                    $this->clear_rows( $row_arr[ 'program_id' ] );
        
                    // store row in database
                    $this->insert_row( $row_arr );
                    $this->added++;
                else:
                    $this->log( __METHOD__ . ' no program ' . $row_arr[ 'program_id' ] );
                    // add to error rows, do not load row
                    $this->error_rows[] = $row_arr;
                endif;
            endwhile;

            fclose($handle);
            
            $this->log( __METHOD__ . ' ' . $this->added . ' rows added.' );
            $this->log( __METHOD__ . ' ERROR ROWS: ' . print_r( $this->error_rows, TRUE ) );
            @unlink( $this->filename );
        endif;
    }
    
    function get_program_ids(){
        global $wpdb;
        $res = $wpdb->get_col( $wpdb->prepare( "
        SELECT TRIM( pm.meta_value )
        FROM {$wpdb->prefix}postmeta pm 
        JOIN {$wpdb->prefix}posts p ON p.ID = pm.post_id AND p.post_type = 'program' AND p.post_status = 'publish'
        WHERE pm.meta_key = 'program_id'
        ") );
        $this->programs = array_flip( $res );
        $this->log( __METHOD__ . ' PROGRAM IDS: ' . print_r( $this->programs, TRUE ) );
    }
    function program_exists( $id ){
        return isset( $this->programs[ $id ] );
    }
    function clear_rows( $id ){
        if ( empty( $id ) || isset( $this->cleared[ $id ] ) )
            return;
        global $wpdb;
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}course_sequence WHERE program_id = %s", $id ) );
        $this->cleared[ $id ] = 1;
    }
    
    function insert_row( $row_arr ){
        $this->log( __METHOD__ . ' ' . print_r( $row_arr, TRUE ) );
        global $wpdb;
        $sql = $wpdb->prepare( 
            "INSERT INTO {$wpdb->prefix}course_sequence 
            ( program_id, section_id, sort_order, row_type, hrs_class, hrs_lab, hrs_work, hrs_credits, description ) 
            VALUES ( %s, %s, %d, %s, %s, %s, %s, %s, %s )", 
            $row_arr[ 'program_id' ],
            $row_arr[ 'section_id' ],
            $row_arr[ 'sort_order' ],
            $row_arr[ 'row_type' ],
            $row_arr[ 'hrs_class' ],
            $row_arr[ 'hrs_lab' ],
            $row_arr[ 'hrs_work' ],
            $row_arr[ 'hrs_credits' ],
            $row_arr[ 'description' ]
        );
        $this->log( __METHOD__ . ' ' . $sql );
        $wpdb->query( $sql );
    }
    
    function header_ok( &$header ){
        $return_header = array();
        //$this->log( __METHOD__ . ' testing header array ' . print_r( $header, TRUE ) );
        $test_header = array(
            'program_id'    => 1,
            'section_id'    => 1,
            'sort_order'    => 1,
            'row_type'      => 1,
            'hrs_class'     => 1,
            'hrs_lab'       => 1,
            'hrs_work'      => 1,
            'hrs_credits'    => 1,
            'description'   => 1,
        );
        foreach ( $header as $key ):
            // remove any non-alphas from parse
            $key = preg_replace( "/\W/", '', $key );
            $return_header[] = $key;
            //$this->log( __METHOD__ . ' testing header key: "' . $key . '"' );
            if ( isset( $test_header[ $key ] ) ):
                unset( $test_header[ $key ] );
            else:
                //$this->log( __METHOD__ . ' no match to "' . $key . '" in ' . print_r( $test_header, TRUE ) );
                return FALSE;
            endif;
        endforeach;
        //$this->log( __METHOD__ . print_r( $header, TRUE ) . print_r( $test_header, TRUE ) );
        if ( $test_header )
            return FALSE;
        $header = $return_header;
        return TRUE;
    }
}