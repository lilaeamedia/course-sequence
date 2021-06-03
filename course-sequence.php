<?php


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/*
    Plugin Name: Course Sequence Framework
    Description: Import, export, edit and render Course Sequence data.
    Version: 1.0.0
    Author: Lilaea Media
    Author URI: http://www.lilaeamedia.com/
    Text Domain: course_sequence
    Domain Path: /lang
  
    This file and all accompanying files (C) 2021 Lilaea Media LLC except where noted. See license for details.
*/

define( 'COURSE_SEQUENCE_PLUGIN_DIR',                dirname( __FILE__ ) );
define( 'COURSE_SEQUENCE_PLUGIN_URL',                plugin_dir_url( __FILE__ ) );

    /**
     * autoloader
     */
    function course_sequence_autoload( $class ) {
        if ( preg_match( "/^CourseSequence(\w+)$/", $class, $matches ) 
            && ( $path = dirname( __FILE__ ) . '/classes/' . $matches[ 1 ] . '.php' )
                && file_exists( $path ) )
            include_once( $path );
    }

    /**
     * Activate
     */
    function course_sequence_activate(){

        global $wpdb;
        $table_name = $wpdb->prefix . "course_sequence";
        $charset_collate = $wpdb->get_charset_collate();

        if ( $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name ):

            $sql = "CREATE TABLE $table_name (
                    ID mediumint(9) NOT NULL AUTO_INCREMENT,
                    `program_id` text NOT NULL,
                    `section_id` text NOT NULL,
                    `description` text NOT NULL,
                    `row_type` text NOT NULL,
                    `hrs_class` int(9) NOT NULL,
                    `hrs_lab` int(9) NOT NULL,
                    `hrs_work` int(9) NOT NULL,
                    `hrs_credits` int(9) NOT NULL,
                    `sort_order` int(9) NOT NULL,
                    PRIMARY KEY  (ID)
            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta( $sql );
        endif;
    }

// register activate action
register_activation_hook( __FILE__ , 'course_sequence_activate' );

// register autoloader
spl_autoload_register( 'course_sequence_autoload' );

// initialize Course Sequence
add_action( 'plugins_loaded', 'CourseSequenceCore::init', 20 );
