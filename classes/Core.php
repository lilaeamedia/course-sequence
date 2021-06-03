<?php

class CourseSequenceCore {
    static function init(){
		$wprb_rest_server = new CourseSequenceRestServer();
		$wprb_rest_server->init();
        add_action( 'admin_menu', 'CourseSequenceCore::load_admin' );
        // shortcode for html output
        //backend processor
        new CourseSequenceQueue();
        add_shortcode( 'courseseq', 'CourseSequenceCore::shortcode' );
    }
    
    static function load_admin(){
        new CourseSequenceAdmin();
    }
    
    static function shortcode( $attr ){
        if ( empty( $attr[ 'program_id' ] ) )
            return '';
        $html = new CourseSequenceHtml( $attr[ 'program_id' ] );
        return $html->render();
    }
}
