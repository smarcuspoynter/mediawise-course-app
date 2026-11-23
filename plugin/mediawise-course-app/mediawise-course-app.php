<?php
/**
 * Plugin Name:       MediaWise Course App
 * Description:       Media + AI Literacy for Educators: the course series app (sign-in, pretest, Rise courses, badges, certificate). Add it to a page with the shortcode [mediawise_course].
 * Version:           0.3.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            MediaWise at the Poynter Institute
 * License:           GPL-2.0-or-later
 * Text Domain:       mediawise-course-app
 */

defined( 'ABSPATH' ) || exit;

define( 'MWCA_VERSION', '0.3.0' );
define( 'MWCA_FILE', __FILE__ );
define( 'MWCA_DIR', plugin_dir_path( __FILE__ ) );
define( 'MWCA_URL', plugin_dir_url( __FILE__ ) );

require_once MWCA_DIR . 'includes/courses.php';
require_once MWCA_DIR . 'includes/shortcode.php';

if ( is_admin() ) {
	require_once MWCA_DIR . 'includes/admin.php';
}

register_activation_hook( __FILE__, 'mwca_activate' );

/**
 * On activation, create the course upload folder and protect it.
 */
function mwca_activate() {
	mwca_ensure_courses_dir();
}
