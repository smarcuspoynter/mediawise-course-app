<?php
/**
 * The course list, where uploaded Rise courses live, and the catalog the app reads.
 *
 * Uploaded courses go in wp-content/uploads/mediawise-courses/<course id>/.
 * catalog.json in that folder tells the app which courses have their own upload:
 *   {"start": {"path": "start", "version": "8bNGzVaW", "uploaded": "2026-11-23T15:00:00+00:00", "title": "..."}}
 * "version" is Rise's coursePackageVersion. The app drops a learner's saved resume point
 * when the version changes, so a re-exported course never tries to reopen a missing lesson.
 * Courses with no upload fall back to the sample course bundled with the app.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The 10 courses in the series. Ids must match the app (index.html, COURSES).
 */
function mwca_courses() {
	return array(
		'start'     => 'Start Here',
		'detect'    => 'Evaluating and Detecting AI Material',
		'science'   => 'Science',
		'social'    => 'Social Studies',
		'ela'       => 'English Language Arts',
		'math'      => 'Math',
		'arts'      => 'Arts',
		'health'    => 'Health & PE',
		'cte'       => 'Career & Technical Education',
		'classroom' => 'Take It to Your Classroom',
	);
}

function mwca_courses_dir() {
	$u = wp_upload_dir( null, false );
	return trailingslashit( $u['basedir'] ) . 'mediawise-courses';
}

function mwca_courses_url() {
	$u = wp_upload_dir( null, false );
	return trailingslashit( $u['baseurl'] ) . 'mediawise-courses';
}

/**
 * Create the folder, block script execution in it (Apache), and stop directory listing.
 */
function mwca_ensure_courses_dir() {
	$dir = mwca_courses_dir();
	if ( ! wp_mkdir_p( $dir ) ) {
		return false;
	}
	$htaccess = $dir . '/.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		file_put_contents( $htaccess, "# MediaWise Course App: course files are static. Never run scripts here.\n<FilesMatch \"\\.(php|phtml|phar|pl|py|cgi|sh)$\">\n  Require all denied\n</FilesMatch>\nOptions -Indexes\n" );
	}
	if ( ! file_exists( $dir . '/index.html' ) ) {
		file_put_contents( $dir . '/index.html', '' );
	}
	return true;
}

function mwca_read_catalog() {
	$file = mwca_courses_dir() . '/catalog.json';
	if ( ! file_exists( $file ) ) {
		return array();
	}
	$data = json_decode( (string) file_get_contents( $file ), true );
	return is_array( $data ) ? $data : array();
}

function mwca_write_catalog( $catalog ) {
	mwca_ensure_courses_dir();
	return false !== file_put_contents( mwca_courses_dir() . '/catalog.json', wp_json_encode( $catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
}

/**
 * File types a Rise SCORM export is allowed to contain. Anything else is deleted on upload.
 */
function mwca_allowed_extensions() {
	return array( 'html', 'htm', 'js', 'mjs', 'css', 'json', 'xml', 'xsd', 'dtd', 'txt', 'map',
		'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp',
		'woff', 'woff2', 'ttf', 'otf', 'eot',
		'mp3', 'mp4', 'm4a', 'm4v', 'wav', 'ogg', 'webm', 'vtt', 'srt', 'pdf' );
}

/**
 * Install an uploaded Rise SCORM 1.2 zip as one course.
 *
 * @param string $course_id One of mwca_courses().
 * @param string $zip_path  Path to the uploaded zip.
 * @return array|WP_Error   Catalog entry on success.
 */
function mwca_install_course_zip( $course_id, $zip_path ) {
	$courses = mwca_courses();
	if ( ! isset( $courses[ $course_id ] ) ) {
		return new WP_Error( 'mwca_course', 'Unknown course.' );
	}
	if ( ! mwca_ensure_courses_dir() ) {
		return new WP_Error( 'mwca_dir', 'Could not create the course folder in uploads.' );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	if ( ! WP_Filesystem() ) {
		return new WP_Error( 'mwca_fs', 'WordPress could not get file access to unpack the course.' );
	}
	global $wp_filesystem;

	$base = mwca_courses_dir();
	$tmp  = $base . '/.tmp-' . $course_id . '-' . wp_generate_password( 8, false );
	$res  = unzip_file( $zip_path, $tmp );
	if ( is_wp_error( $res ) ) {
		$wp_filesystem->delete( $tmp, true );
		return new WP_Error( 'mwca_unzip', 'Could not unpack the zip: ' . $res->get_error_message() );
	}

	// Find the export's root: the folder holding scormdriver/indexAPI.html (at the top, or one folder down).
	$root = '';
	foreach ( array_merge( array( $tmp ), (array) glob( $tmp . '/*', GLOB_ONLYDIR ) ) as $cand ) {
		if ( file_exists( $cand . '/scormdriver/indexAPI.html' ) ) {
			$root = $cand;
			break;
		}
	}
	if ( '' === $root ) {
		$wp_filesystem->delete( $tmp, true );
		return new WP_Error( 'mwca_not_rise', 'This zip is not a Rise SCORM 1.2 export (no scormdriver/indexAPI.html). In Rise, use Publish > LMS > SCORM 1.2.' );
	}

	// Delete anything that isn't a static file type a Rise export uses (PHP, .htaccess, executables, ...).
	$allowed = mwca_allowed_extensions();
	$removed = 0;
	$it      = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $f ) {
		if ( $f->isLink() ) {
			unlink( $f->getPathname() );
			$removed++;
			continue;
		}
		if ( $f->isFile() ) {
			$ext = strtolower( pathinfo( $f->getFilename(), PATHINFO_EXTENSION ) );
			if ( ! in_array( $ext, $allowed, true ) || 0 === strpos( $f->getFilename(), '.' ) ) {
				unlink( $f->getPathname() );
				$removed++;
			}
		}
	}

	// Rise's package version, from the launcher's driver config.
	$version  = '';
	$launcher = (string) file_get_contents( $root . '/scormdriver/indexAPI.html' );
	if ( preg_match( '/"coursePackageVersion"\s*:\s*"([^"]+)"/', $launcher, $m ) ) {
		$version = sanitize_text_field( $m[1] );
	}
	$title = '';
	if ( preg_match( '/<title>([^<]*)<\/title>/i', $launcher, $m ) ) {
		$title = sanitize_text_field( html_entity_decode( $m[1] ) );
	}

	// Swap it into place.
	$dest = $base . '/' . $course_id;
	if ( $wp_filesystem->exists( $dest ) ) {
		$wp_filesystem->delete( $dest, true );
	}
	if ( ! rename( $root, $dest ) ) {
		$wp_filesystem->delete( $tmp, true );
		return new WP_Error( 'mwca_move', 'Could not move the course into place.' );
	}
	$wp_filesystem->delete( $tmp, true );

	$entry = array(
		'path'     => $course_id,
		'version'  => $version,
		'title'    => $title,
		'uploaded' => gmdate( 'c' ),
		'removed'  => $removed,
	);
	$catalog               = mwca_read_catalog();
	$catalog[ $course_id ] = $entry;
	mwca_write_catalog( $catalog );
	return $entry;
}

/**
 * Remove a course's upload so it falls back to the bundled sample.
 */
function mwca_remove_course( $course_id ) {
	$courses = mwca_courses();
	if ( ! isset( $courses[ $course_id ] ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	if ( WP_Filesystem() ) {
		global $wp_filesystem;
		$wp_filesystem->delete( mwca_courses_dir() . '/' . $course_id, true );
	}
	$catalog = mwca_read_catalog();
	unset( $catalog[ $course_id ] );
	mwca_write_catalog( $catalog );
}
