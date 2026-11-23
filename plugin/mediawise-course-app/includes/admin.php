<?php
/**
 * WP admin > MediaWise Courses: upload a Rise SCORM 1.2 zip for each course, see what's live, remove an upload.
 * Only users who can manage_options (administrators) see it.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'mwca_admin_menu' );
add_action( 'admin_post_mwca_upload', 'mwca_handle_upload' );
add_action( 'admin_post_mwca_remove', 'mwca_handle_remove' );

function mwca_admin_menu() {
	add_menu_page( 'MediaWise Courses', 'MediaWise Courses', 'manage_options', 'mwca', 'mwca_admin_page', 'dashicons-welcome-learn-more', 58 );
}

function mwca_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$catalog = mwca_read_catalog();
	$limit   = size_format( wp_max_upload_size() );
	$notice  = isset( $_GET['mwca_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['mwca_msg'] ) ) : '';
	$is_err  = isset( $_GET['mwca_err'] );
	?>
	<div class="wrap">
		<h1>MediaWise Courses</h1>
		<?php if ( $notice ) : ?>
			<div class="notice <?php echo $is_err ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div>
		<?php endif; ?>
		<p>Put the app on any page with the shortcode <code>[mediawise_course]</code>.</p>
		<p>For each course, upload its Rise export as a <strong>SCORM 1.2 zip</strong> (in Rise: Publish &gt; LMS &gt; SCORM 1.2, with completion set to pass the course quiz). A course with no upload shows the sample course. Uploading again replaces the course; learners keep their badges, and their saved place starts over if the course changed.</p>
		<p>Largest upload this site allows: <strong><?php echo esc_html( $limit ); ?></strong>. Files are stored in <code><?php echo esc_html( str_replace( ABSPATH, '', mwca_courses_dir() ) ); ?></code> and must be served from this site's own domain (exclude that folder from any CDN or media offload).</p>

		<table class="widefat striped" style="max-width:1100px">
			<thead><tr><th>Course</th><th>Live version</th><th>Upload a new zip</th><th></th></tr></thead>
			<tbody>
			<?php foreach ( mwca_courses() as $id => $name ) : $c = isset( $catalog[ $id ] ) ? $catalog[ $id ] : null; ?>
				<tr>
					<td><strong><?php echo esc_html( $name ); ?></strong><br><code><?php echo esc_html( $id ); ?></code></td>
					<td>
						<?php if ( $c ) : ?>
							<?php echo esc_html( $c['title'] ? $c['title'] : '(untitled)' ); ?><br>
							<span class="description">Rise package <?php echo esc_html( $c['version'] ? $c['version'] : 'unknown' ); ?> · uploaded <?php echo esc_html( mysql2date( 'M j, Y g:i a', get_date_from_gmt( gmdate( 'Y-m-d H:i:s', strtotime( $c['uploaded'] ) ) ) ) ); ?></span>
						<?php else : ?>
							<span class="description">Sample course (nothing uploaded)</span>
						<?php endif; ?>
					</td>
					<td>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
							<input type="hidden" name="action" value="mwca_upload">
							<input type="hidden" name="course" value="<?php echo esc_attr( $id ); ?>">
							<?php wp_nonce_field( 'mwca_upload_' . $id ); ?>
							<input type="file" name="zip" accept=".zip,application/zip" required>
							<button class="button button-primary">Upload</button>
						</form>
					</td>
					<td>
						<?php if ( $c ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Remove this upload? The course will show the sample course until you upload again.');">
							<input type="hidden" name="action" value="mwca_remove">
							<input type="hidden" name="course" value="<?php echo esc_attr( $id ); ?>">
							<?php wp_nonce_field( 'mwca_remove_' . $id ); ?>
							<button class="button-link button-link-delete">Remove</button>
						</form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<h2 style="margin-top:2em">Learner data</h2>
		<p>This version keeps each learner's progress in their own browser (the same as the demo). Saving to a database (WordPress, Google Sheets or Supabase) is the next step once Poynter IT picks one. See <code>database/</code> in the project repository.</p>
	</div>
	<?php
}

function mwca_back( $msg, $error = false ) {
	$args = array( 'page' => 'mwca', 'mwca_msg' => rawurlencode( $msg ) );
	if ( $error ) {
		$args['mwca_err'] = 1;
	}
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}

function mwca_handle_upload() {
	$id = isset( $_POST['course'] ) ? sanitize_key( wp_unslash( $_POST['course'] ) ) : '';
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'mwca_upload_' . $id ) ) {
		wp_die( 'Not allowed.' );
	}
	$courses = mwca_courses();
	if ( ! isset( $courses[ $id ] ) ) {
		mwca_back( 'Unknown course.', true );
	}
	if ( empty( $_FILES['zip'] ) || ! empty( $_FILES['zip']['error'] ) ) {
		$code = isset( $_FILES['zip']['error'] ) ? (int) $_FILES['zip']['error'] : -1;
		mwca_back( in_array( $code, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true )
			? 'That zip is larger than this site allows (' . size_format( wp_max_upload_size() ) . '). Ask IT to raise the upload limit, or put the course on the server by SFTP.'
			: 'The upload did not arrive. Please try again.', true );
	}
	$name = sanitize_file_name( wp_unslash( $_FILES['zip']['name'] ) );
	if ( 'zip' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
		mwca_back( 'Please upload a .zip file.', true );
	}
	$tmp = $_FILES['zip']['tmp_name']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- path from PHP, validated by is_uploaded_file.
	if ( ! is_uploaded_file( $tmp ) ) {
		mwca_back( 'The upload could not be verified.', true );
	}
	$res = mwca_install_course_zip( $id, $tmp );
	if ( is_wp_error( $res ) ) {
		mwca_back( $res->get_error_message(), true );
	}
	$extra = $res['removed'] ? sprintf( ' %d file(s) of types a course doesn\'t need were removed.', $res['removed'] ) : '';
	mwca_back( sprintf( '%s is live (Rise package %s).%s', $courses[ $id ], $res['version'] ? $res['version'] : 'unknown', $extra ) );
}

function mwca_handle_remove() {
	$id = isset( $_POST['course'] ) ? sanitize_key( wp_unslash( $_POST['course'] ) ) : '';
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'mwca_remove_' . $id ) ) {
		wp_die( 'Not allowed.' );
	}
	mwca_remove_course( $id );
	$courses = mwca_courses();
	mwca_back( ( isset( $courses[ $id ] ) ? $courses[ $id ] : 'Course' ) . ' upload removed. It shows the sample course for now.' );
}
