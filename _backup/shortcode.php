<?php
/**
 * Shortcode
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-15
 */

namespace wpinc\navi\shortcode;

require_once __DIR__ . '/page.php';
require_once __DIR__ . '/list.php';

/**
 * The.
 */
function add_page_navigation_shortcode() {
	add_shortcode( 'child-page-nav', '\wpinc\navi\shortcode\_sc_child_page_nav' );
	add_shortcode( 'sibling-page-nav', '\wpinc\navi\shortcode\_sc_sibling_page_nav' );
}

/**
 * The.
 *
 * @access private
 */
function _sc_child_page_nav( array $atts ) {
	$atts = shortcode_atts( array( 'style' => '' ), $atts );
	return \wpinc\navi\navigation\get_the_child_page_navigation( array( 'class' => $atts['style'] ) );
}

/**
 * The.
 *
 * @access private
 */
function _sc_sibling_page_nav( array $atts ) {
	$atts = shortcode_atts( array( 'style' => '' ), $atts );
	return \wpinc\navi\navigation\get_the_sibling_page_navigation( array( 'class' => $atts['style'] ) );
}


// -----------------------------------------------------------------------------


/**
 * The.
 */
function add_post_type_list_shortcode( $post_type, $taxonomy = false, $args = array() ) {
	$args += array(
		'year_date_function' => '\wpinc\navi\shortcode\get_item_year_date_news',
		'year_format'        => false,
	);
	add_shortcode(
		$post_type . '-list',
		function ( $atts, $content ) use ( $post_type, $taxonomy, $args ) {
			$defs = array(
				'term'                  => '',
				'taxonomy'              => $taxonomy,
				'style'                 => '',
				'heading'               => false,
				'year-heading'          => false,
				'latest'                => false,
				'sticky'                => false,
				'order'                 => 'desc',
				'orderby'               => 'date',  // Only 'date' and 'menu_order' is available.
				'date-after'            => '',
				'date-before'           => '',
				'echo-content-on-empty' => false,
			);
			$atts = shortcode_atts( $defs, $atts );

			$atts['order'] = strtolower( $atts['order'] );
			if ( ! empty( $atts['date-after'] ) ) {
				$atts['date-after'] = preg_replace( '/[^0-9]/', '', $atts['date-after'] );
				$atts['date-after'] = str_pad( $atts['date-after'], 8, '0' );
			}
			if ( ! empty( $atts['date-before'] ) ) {
				$atts['date-before'] = preg_replace( '/[^0-9]/', '', $atts['date-before'] );
				$atts['date-before'] = str_pad( $atts['date-before'], 8, '9' );
			}
			$terms = empty( $atts['term'] ) ? false : $atts['term'];
			$items = _get_item_list( $post_type, $taxonomy, $terms, $atts['latest'], $atts['sticky'], $args['year_date_function'], $atts['date-after'], $atts['date-before'], $atts['orderby'] );
			if ( empty( $items ) ) {
				if ( false !== $atts['echo-content-on-empty'] && ! empty( $content ) ) {
					return $content;
				}
				return '';
			}

			if ( 'asc' === $atts['order'] ) {
				$items = array_reverse( $items );
			}
			return _echo_list( $atts, $items, $post_type, $args['year_format'] );
		}
	);
}
