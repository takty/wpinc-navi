<?php
/**
 * Page Break
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-02
 */

namespace wpinc\navi\link_page_break;

require_once __DIR__ . '/page-break.php';

/**
 * Initialize next and previous link tags.
 */
function initialize() {
	add_action( 'wp_head', '\wpinc\navi\page_break\_cb_wp_head' );
}

/**
 * The callback function for 'wp_head' action.

 * @access private
 */
function _cb_wp_head() {
	if ( ! is_singular() || is_attachment() ) {
		return;
	}
	$post     = get_post();
	$page_num = _get_page_break_count( $post );

	if ( 0 < $page_num ) {
		$prev = _get_adjacent_page_break_url( true, $post, $page_num );
		$next = _get_adjacent_page_break_url( false, $post, $page_num );

		if ( $prev ) {
			echo '<link rel="prev" href="' . esc_url( $prev ) . '">';
		}
		if ( $next ) {
			echo '<link rel="next" href="' . esc_url( $next ) . '">';
		}
	}
}

/**
 * Retrieve page counts.
 *
 * @access private
 *
 * @param \WP_Post $post The post.
 * @return int Page count.
 */
function _get_page_break_count( \WP_Post $post ): int {
	$content = $post->post_content;
	if ( false === strpos( $content, '<!--nextpage-->' ) ) {
		return 1;
	}
	$content = str_replace( "\n<!--nextpage-->\n", '<!--nextpage-->', $content );
	$content = str_replace( "\n<!--nextpage-->", '<!--nextpage-->', $content );
	$content = str_replace( "<!--nextpage-->\n", '<!--nextpage-->', $content );
	if ( 0 === strpos( $content, '<!--nextpage-->' ) ) {
		$content = substr( $content, 15 );
	}
	return count( explode( '<!--nextpage-->', $content ) );
}

/**
 * Retrieve adjacent page break url.
 *
 * @access private
 *
 * @param bool     $previous   Whether to retrieve previous post. Default true.
 * @param \WP_Post $post       Post.
 * @param int      $page_count Page count.
 * @return string The URL.
 */
function _get_adjacent_page_break_url( bool $previous = true, \WP_Post $post, int $page_count ): string {
	global $wp_query;

	$page = $wp_query->get( 'page', 1 );
	$idx  = $previous ? $page - 1 : $page + 1;

	if ( $idx <= 0 || $page_count < $idx ) {
		return '';
	}
	return get_page_break_link( $idx, $post );
}
