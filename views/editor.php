<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )exit;

?>
<div class="wrap">
  <h1>Course Sequence Editor</h1>
  <?php
  if ( $files = $this->get_csv_files() ):
    $error = 'Course Sequence files are being processed.' . print_r( $files, TRUE );
  ?>
  <div class="notice notice-warning">
    <p><?php echo $error; ?></p>
  </div>
  <form method="post" class="inline-form">
    <input class="button-secondary" type="submit" value="Cancel Processing" name="csv_cancel_course_sequence" />
  </form>
  <?php
  endif;
  $error = '';
  if ( isset( $_REQUEST[ 'error' ] ) ):
    $error = sanitize_text_field( $_REQUEST[ 'error' ] );
  $errorclass = stristr( $_REQUEST[ 'error' ], 'success' ) ? 'notice-success' : 'notice-error';
  endif;
  if ( $error ):
    ?>
  <div class="notice <?php echo $errorclass; ?>">
    <p><?php echo $error; ?></p>
  </div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="inline-form">
    <label>
      <?php _e( 'Import from CSV', 'iwelements' ); ?>
      :</label>
    <input type="file" name="importfile" />
    <input class="button-secondary" type="submit" value="Upload" name="csv_upload_course_sequence" />
  </form>
  &nbsp; &nbsp; | &nbsp; &nbsp;
  <form method="post" class="inline-form">
    <input class="button-secondary" type="submit" value="Export All Sequences as CSV" name="csv_export_course_sequence" />
  </form>
  <div id="root"></div>
</div>
