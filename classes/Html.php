<?php

class CourseSequenceHtml {
    var $program_id;
    var $html;
    var $sections;
    // HTML Render Engine (shortcode)
    function __construct( $program_id ){
        $this->program_id = trim( $program_id );
        $this->html = '';
        $this->sections = array();
    }
    
    function render(){
        $this->get_rows();
        $this->start_table();
        // note that sections must be mapped (i.e., are described by 'section' rows ) to appear in the output.
        foreach( $this->section_map as $section_id => $order ):
            $this->process_section( $section_id );
        endforeach;
        $this->end_table();
        // return formatted html
        return $this->html;
    }

    // read rows by program id
    function get_rows(){
        global $wpdb;
        $res = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}course_sequence WHERE program_id = %s", $this->program_id ), ARRAY_A );
        foreach ( $res as $rowarr ):
            if ( 'section' == $rowarr[ 'row_type' ] )
                $this->section_map[ $rowarr[ 'section_id' ] ] = $rowarr[ 'sort_order' ];
            $this->sections[ $rowarr[ 'section_id' ] ][] = $rowarr;
        endforeach;
        asort( $this->section_map );
    }
    
    // format html
    function process_section( $section_id ){
        $has_header = 0;
        usort( $this->sections[ $section_id ], array( $this, 'sort_section' ) );
        foreach ( $this->sections[ $section_id ] as $section_row ):
            if ( 'section' == $section_row[ 'row_type' ] )
                continue;
            if ( 'course' == $section_row[ 'row_type' ] && !$has_header ):
                $this->html .= "<tr>\n<th>Course</th><th>Class</th><th>Lab</th><th>Work/Clinic</th><th>Credits</th></tr>";
                $has_header++;
            endif;
            $this->html .= "<tr>\n";
            switch( $section_row[ 'row_type' ] ):
                case 'headline':
                    $this->html .= '<td colspan="5"><span class="course-sequence-headline">' . htmlentities( $section_row[ 'description' ] ) . "</span></td>\n";
                    break;
                case 'note':
                    $this->html .= '<td colspan="5"><span class="course-sequence-note">' . htmlentities( $section_row[ 'description' ] ) . "</span></td>\n";
                    break;
                case 'subtotal':
                    $this->html .= '<td><span class="course-sequence-subtotal-descr">' . htmlentities( $section_row[ 'description' ] ) . "</span></td>\n";
                    $this->html .= '<td><span class="course-sequence-subtotal-hrs-class">' . htmlentities( $section_row[ 'hrs_class' ] ) . "</span></td>\n";
                    $this->html .= '<td><span class="course-sequence-subtotal-hrs-lab">' . htmlentities( $section_row[ 'hrs_lab' ] ) . "</span></td>\n";
                    $this->html .= '<td><span class="course-sequence-subtotal-hrs-work">' . htmlentities( $section_row[ 'hrs_work' ] ) . "</span></td>\n";
                    $this->html .= '<td><span class="course-sequence-subtotal-hrs-credits">' . htmlentities( $section_row[ 'hrs_credits' ] ) . "</span></td>\n";
                    break;
                case 'course':
                default:
                    $this->html .= '<td><span class="course-sequence-course-descr">' . htmlentities( $section_row[ 'description' ] ) . "</span></td>\n";
                    $this->html .= '<td><span class="course-sequence-course-hrs-class">' . htmlentities( $section_row[ 'hrs_class' ] ) . "</span></td>\n";
                    $this->html .= '<td><span class="course-sequence-course-hrs-lab">' . htmlentities( $section_row[ 'hrs_lab' ] ) . "</span></td>\n";
                    $this->html .= '<td><span class="course-sequence-course-hrs-work">' . htmlentities( $section_row[ 'hrs_work' ] ) . "</span></td>\n";
                    $this->html .= '<td><span class="course-sequence-course-hrs-credits">' . htmlentities( $section_row[ 'hrs_credits' ] ) . "</span></td>\n";
                    break;
            endswitch;
            $this->html .= "</tr>\n";
        endforeach;
    }
    
    function sort_section( $a, $b ){
        if ( $a[ 'sort_order' ] == $b[ 'sort_order' ] )
            return 0;
        return ($a[ 'sort_order' ] < $b[ 'sort_order' ] ) ? -1 : 1;
    }
    
    function start_table(){
        $this->html .= "<table class=\"course-sequence\">\n";
    }
    function end_table(){
        $this->html .= "</table>\n";
    }
}