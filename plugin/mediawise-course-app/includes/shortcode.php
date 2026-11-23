<?php
/**
 * [mediawise_course] shortcode: shows the app full width in a frame, served from this plugin's own folder
 * (same site as the page), and loads assets/embed.js, which sizes the frame and keeps it under the
 * sticky site header. Optional attribute: height="900" (starting height before the app reports its own).
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'mediawise_course', 'mwca_shortcode' );

function mwca_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'height' => '900' ), $atts, 'mediawise_course' );

	wp_enqueue_script( 'mwca-embed', MWCA_URL . 'assets/embed.js', array(), MWCA_VERSION, true );

	// The app finds uploaded courses through this same-site path (it ignores anything that isn't one).
	$courses = wp_make_link_relative( mwca_courses_url() );
	$src     = add_query_arg(
		array(
			'courses' => rawurlencode( $courses ),
			'v'       => MWCA_VERSION,
		),
		MWCA_URL . 'app/index.html'
	);

	return sprintf(
		'<div class="mwca-wrap" style="position:relative;left:50%%;width:100vw;margin-left:-50vw;">'
		. '<iframe id="mwca-app" src="%1$s" title="%2$s" style="display:block;width:100%%;height:%3$dpx;border:0" scrolling="no" allow="fullscreen; autoplay"></iframe>'
		. '</div>',
		esc_url( $src ),
		esc_attr__( 'Media + AI Literacy for Educators', 'mediawise-course-app' ),
		max( 300, (int) $atts['height'] )
	);
}
