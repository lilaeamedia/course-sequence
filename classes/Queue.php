<?php

class CourseSequenceQueue extends IntelliWidgetMainBackgroundProcess {
	/**
	 * @var string
	 */
    protected $action       = 'course_sequence_queue';
    protected $wait_delay   = 200000; // microseconds
	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		// Actions to perform
        usleep( $this->wait_delay ); // wait delay
        if ( file_exists( $item ) ):
            $i = new CourseSequenceImport( $item );
            $i->load_csv();
            $i->log( __METHOD__ . ' ' . $this->is_queue_empty( TRUE ) . " batches in queue." );
        endif;
        return FALSE;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
    
	protected function complete() {
		parent::complete();
		// Show notice to user or perform some other arbitrary task...
	}    
}
