<?php
/**
 * Shortcode
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2023-10-13
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
 * @param array<string, string>|string $atts Attributes.
 * @return string Result of the shortcode.
 */
function _sc_child_page_nav( $atts ): string {
	$atts = shortcode_atts( array( 'style' => '' ), (array) $atts );
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
 * @param array<string, string>|string $atts Attributes.
 * @return string Result of the shortcode.
 */
function _sc_sibling_page_nav( $atts ): string {
	$atts = shortcode_atts( array( 'style' => '' ), (array) $atts );
	return \wpinc\navi\get_the_sibling_page_navigation(
		array(
			'class' => "sibling-page-navigation {$atts['style']}",
		)
	);
}


// -----------------------------------------------------------------------------


/** phpcs:ignore
 * Adds page list shortcode.
 *
 * @param string $post_type Post type.
 * @param string $taxonomy  Taxonomy.
 * phpcs:ignore
 * @param array{
 *     post_type?         : string,
 *     year_date_function?: callable,
 *     before?            : string,
 *     after?             : string,
 *     template_slug?     : string,
 *     heading_level?     : int,
 *     year_heading_level?: int,
 *     year_format?       : string,
 *     taxonomy?          : string,
 *     terms?             : string|string[],
 *     latest?            : int,
 *     sticky?            : bool,
 *     order?             : string,
 *     orderby?           : string,
 *     date_after?        : string,
 *     date_before?       : string,
 * } $args Arguments for get_post_list.
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
		/**
		 * Callback function.
		 *
		 * @param array<string, string>|string $atts    Attributes.
		 * @param string                       $content The shortcode content.
		 */
		function ( $atts, string $content ) use ( $args ) {
			return _sc_post_list( is_array( $atts ) ? $atts : array(), $content, $args );
		}
	);
}

/** phpcs:ignore
 * Callback function for shortcode 'post-list'.
 *
 * @access private
 * @psalm-suppress ArgumentTypeCoercion, InvalidScalarArgument
 *
 * @param array<string, string> $atts    Attributes.
 * @param string                $content The shortcode content.
 * phpcs:ignore
 * @param array{
 *     post_type?         : string,
 *     year_date_function?: callable,
 *     before?            : string,
 *     after?             : string,
 *     template_slug?     : string,
 *     heading_level?     : int,
 *     year_heading_level?: int,
 *     year_format?       : string,
 *     taxonomy?          : string,
 *     terms?             : string|string[],
 *     latest?            : int,
 *     sticky?            : bool,
 *     order?             : string,
 *     orderby?           : string,
 *     date_after?        : string,
 *     date_before?       : string,
 * } $args Arguments for get_post_list.
 */
function _sc_post_list( array $atts, string $content, array $args ): string {
	$temp = array();
	foreach ( $atts as $key => $val ) {
		$key          = str_replace( '-', '_', $key );
		$temp[ $key ] = $val;

		if ( 'term' === $key ) {
			$temp['terms'] = $val;
		}
	}
	$new_atts = _shortcode_atts_filter(
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
		$temp
	);
	if ( isset( $new_atts['heading_level'] ) ) {
		if ( is_numeric( $new_atts['heading_level'] ) ) {
			$new_atts['heading_level'] = (int) $new_atts['heading_level'];
		} else {
			unset( $new_atts['heading_level'] );
		}
	}
	if ( isset( $new_atts['year_heading_level'] ) ) {
		if ( is_numeric( $new_atts['year_heading_level'] ) ) {
			$new_atts['year_heading_level'] = (int) $new_atts['year_heading_level'];
		} else {
			unset( $new_atts['year_heading_level'] );
		}
	}
	if ( isset( $new_atts['latest'] ) ) {
		if ( is_numeric( $new_atts['latest'] ) ) {
			$new_atts['latest'] = (int) $new_atts['latest'];
		} else {
			unset( $new_atts['latest'] );
		}
	}
	if ( isset( $new_atts['sticky'] ) && is_string( $new_atts['sticky'] ) ) {
		if ( '0' === $new_atts['sticky'] || 'false' === strtolower( $new_atts['sticky'] ) ) {
			$new_atts['sticky'] = false;
		} else {
			$new_atts['sticky'] = true;
		}
	}
	$args = array_merge( $args, $new_atts );
	$ret  = get_post_list( $args );  // @phpstan-ignore-line
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
 * @param string[]              $keys Entire list of supported attributes keys.
 * @param array<string, string> $atts User defined attributes in shortcode tag.
 * @return array<string, string> Filtered attribute list.
 */
function _shortcode_atts_filter( array $keys, array $atts ): array {
	$out = array();
	foreach ( $keys as $key ) {
		if ( array_key_exists( $key, $atts ) ) {
			$out[ $key ] = $atts[ $key ];
		}
	}
	return $out;
}
