<?php
/**
 * Page Break
 *
 * @author Takuto Yanagida
 * @version 2021-03-30
 */

namespace wpinc\compass\page_break;

/**
 *
 * Based on _wp_link_page( $i ).
 *
 * @param int $i
 * @param \WP_Post $post
 * @return string
 */
function get_page_break_link( int $i, \WP_Post $post ): string {
	global $wp_rewrite;

	$url = get_permalink( $post );
	if ( 1 < $i ) {
		if ( empty( get_option( 'permalink_structure' ) ) || ( $post && in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) ) {
			$url = add_query_arg( 'page', $i, $url );
		} elseif ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) === $post->ID ) {
			$url = trailingslashit( $url ) . user_trailingslashit( "$wp_rewrite->pagination_base/" . $i, 'single_paged' );
		} else {
			$url = trailingslashit( $url ) . user_trailingslashit( $i, 'single_paged' );
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

/**
 *
 */
function initialize() {
	add_action( 'wp_head', '\wpinc\compass\page_break\_cb_wp_head' );
}


// -----------------------------------------------------------------------------


/**
 * The callback function for 'wp_head' action.
 */
function _cb_wp_head() {
	if ( ! is_singular() || is_attachment() ) {
		return;
	}
	$post     = get_post();
	$page_num = _get_page_break_count( $post );

	if ( 0 < $page_num ) {
		$url_prev = _get_adjacent_page_break_url( true, $post, $page_num );
		$url_next = _get_adjacent_page_break_url( false, $post, $page_num );

		if ( $url_prev ) {
			echo '<link rel="prev" href="' . esc_url( $url_prev ) . '">';
		}
		if ( $url_next ) {
			echo '<link rel="next" href="' . esc_url( $url_next ) . '">';
		}
	}
}

/**
 *
 *
 * @access private
 *
 * @param \WP_Post $post
 * @return int
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
 *
 *
 * @access private
 *
 * @param bool     $previous
 * @param \WP_Post $post
 * @param int      $page_num
 * @return string The URL.
 */
function _get_adjacent_page_break_url( bool $previous = true, \WP_Post $post, int $page_num ): string {
	global $wp_query;

	$page = $wp_query->get( 'page', 1 );
	$idx  = $previous ? $page - 1 : $page + 1;

	if ( $idx <= 0 || $page_num < $idx ) {
		return '';
	}
	return get_page_break_link( $i, $post );
}
