<?php
/**
 * Page Break
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2023-10-13
 */

namespace wpinc\navi;

require_once __DIR__ . '/page-break.php';

/**
 * Enables next and previous link tags.
 */
function enable_link_page_break(): void {
	add_action( 'wp_head', '\wpinc\navi\_cb_wp_head__link_page_break' );
}

/**
 * Callback function for 'wp_head' action.
 *
 * @access private
 */
function _cb_wp_head__link_page_break(): void {
	if ( ! is_singular() || is_attachment() ) {
		return;
	}
	$post = get_post();
	if ( ! ( $post instanceof \WP_Post ) ) {
		return;
	}
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
 * Retrieves page counts.
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
 * Retrieves adjacent page break url.
 *
 * @access private
 * @global \WP_Query $wp_query
 *
 * @param bool     $previous   Whether to retrieve previous post.
 * @param \WP_Post $post       Post.
 * @param int      $total      Total page count.
 * @return string The URL.
 */
function _get_adjacent_page_break_url( bool $previous, \WP_Post $post, int $total ): string {
	global $wp_query;

	$now = $wp_query->get( 'page', 1 );
	$idx = $previous ? $now - 1 : $now + 1;

	if ( $idx <= 0 || $total < $idx ) {
		return '';
	}
	return get_page_break_link( $idx, $post );
}
