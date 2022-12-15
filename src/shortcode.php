<?php
/**
 * Shortcode
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2022-12-15
 */

namespace wpinc\navi;

require_once __DIR__ . '/page-hierarchy.php';
require_once __DIR__ . '/list.php';

/**
 * Adds page navigation shortcodes.
 */
function add_page_navigation_shortcode(): void {
	add_shortcode( 'child-page-nav', '\wpinc\navi\_sc_child_page_nav' );
	add_shortcode( 'sibling-page-nav', '\wpinc\navi\_sc_sibling_page_nav' );
}

/**
 * Callback function for shortcode 'child-page-nav'.
 *
 * @access private
 *
 * @param array|string $atts Attributes.
 * @return string Result of the shortcode.
 */
function _sc_child_page_nav( $atts ): string {
	$atts = shortcode_atts( array( 'style' => '' ), $atts );
	return \wpinc\navi\get_the_child_page_navigation(
		array(
			'class' => "child-page-navigation {$atts['style']}",
		)
	);
}

/**
 * Callback function for shortcode 'sibling-page-nav'.
 *
 * @access private
 *
 * @param array|string $atts Attributes.
 * @return string Result of the shortcode.
 */
function _sc_sibling_page_nav( $atts ): string {
	$atts = shortcode_atts( array( 'style' => '' ), $atts );
	return \wpinc\navi\get_the_sibling_page_navigation(
		array(
			'class' => "sibling-page-navigation {$atts['style']}",
		)
	);
}


// -----------------------------------------------------------------------------


/**
 * Adds page list shortcode.
 *
 * @param string $post_type Post type.
 * @param string $taxonomy  Taxonomy.
 * @param array  $args      Arguments for get_post_list.
 */
function add_post_list_shortcode( string $post_type, string $taxonomy = '', array $args = array() ): void {
	$args += array(
		'post_type' => $post_type,
		'before'    => '<ul class="list-item list-item-' . $post_type . '" shortcode>',
		'taxonomy'  => $taxonomy,
		'latest'    => 10,
	);
	add_shortcode(
		$post_type . '-list',
		function ( $atts, string $content ) use ( $post_type, $taxonomy, $args ) {
			return _sc_post_list( $atts, $content, $post_type, $taxonomy, $args );
		}
	);
}

/**
 * Callback function for shortcode 'post-list'.
 *
 * @access private
 *
 * @param array|string $atts      Attributes.
 * @param string       $content   The shortcode content.
 * @param string       $post_type Post type.
 * @param string       $taxonomy  Taxonomy.
 * @param array        $args      Arguments for get_post_list.
 */
function _sc_post_list( $atts, string $content, string $post_type, string $taxonomy, array $args ): string {
	$new_atts = array();
	if ( is_array( $atts ) ) {
		foreach ( $atts as $key => $val ) {
			$key              = str_replace( '-', '_', $key );
			$new_atts[ $key ] = $val;

			if ( 'term' === $key ) {
				$new_atts['terms'] = $val;
			}
		}
	}
	$atts = _shortcode_atts_filter(
		array(
			'echo_content_on_empty',

			'post_type',
			// Key 'year_date_function' is removed.
			'before',
			'after',
			'template_slug',
			'heading_level',
			'year_heading_level',
			'year_format',
			'taxonomy',
			'terms',
			'latest',
			'sticky',
			'order',
			'orderby',
			'date_after',
			'date_before',
		),
		$new_atts
	);
	$ret  = get_post_list( array_merge( $args, $atts ) );
	if (
		empty( $ret ) &&
		( ! isset( $args['echo_content_on_empty'] ) || false !== $args['echo_content_on_empty'] ) &&
		! empty( $content )
	) {
		return $content;
	}
	return $ret;
}

/**
 * Filter user attributes with known attributes.
 *
 * @access private
 *
 * @param array $keys Entire list of supported attributes keys.
 * @param array $atts User defined attributes in shortcode tag.
 * @return array Filtered attribute list.
 */
function _shortcode_atts_filter( array $keys, array $atts ): array {
	$atts = (array) $atts;
	$out  = array();
	foreach ( $keys as $key ) {
		if ( array_key_exists( $key, $atts ) ) {
			$out[ $key ] = $atts[ $key ];
		}
	}
	return $out;
}
