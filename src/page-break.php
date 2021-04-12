<?php
/**
 * Page Break
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-12
 */

namespace wpinc\navi\page_break;

/**
 * Retrieves page break link url. Based on _wp_link_page().
 *
 * @param int       $idx  Page number.
 * @param ?\WP_Post $post The post.
 * @return string Link.
 */
function get_page_break_link( int $idx, ?\WP_Post $post = null ): string {
	global $wp_rewrite;
	if ( empty( $post ) && isset( $GLOBALS['post'] ) ) {
		$post = $GLOBALS['post'];
	}
	if ( ! $_post ) {
		return '';
	}
	$url = get_permalink( $post );
	if ( 1 < $idx ) {
		if ( empty( get_option( 'permalink_structure' ) ) || ( $post && in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) ) {
			$url = add_query_arg( 'page', $idx, $url );
		} elseif ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) === $post->ID ) {
			$url = trailingslashit( $url ) . user_trailingslashit( "$wp_rewrite->pagination_base/" . $idx, 'single_paged' );
		} else {
			$url = trailingslashit( $url ) . user_trailingslashit( $idx, 'single_paged' );
		}
	}
	if ( is_preview() ) {
		$query_args = array();
		// phpcs:disable
		if ( ( 'draft' !== $post->post_status ) && isset( $_GET['preview_id'], $_GET['preview_nonce'] ) ) {
			$query_args['preview_id']    = wp_unslash( $_GET['preview_id'] );
			$query_args['preview_nonce'] = wp_unslash( $_GET['preview_nonce'] );
		}
		// phpcs:enable
		$url = get_preview_post_link( $post, $query_args, $url );
	}
	return $url;
}
