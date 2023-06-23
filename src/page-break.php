<?php
/**
 * Page Break Navigation
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2023-06-23
 */

namespace wpinc\navi;

/**
 * Displays a page break navigation, when applicable.
 *
 * @param array $args (Optional) See get_the_page_break_navigation() for available arguments.
 */
function the_page_break_navigation( array $args = array() ): void {
	echo get_the_page_break_navigation( $args );  // phpcs:ignore
}


// -----------------------------------------------------------------------------


/**
 * Displays the navigation to page breaks, when applicable.
 *
 * @param array $args {
 *     (Optional) Default page break navigation arguments.
 *
 *     @type string 'before'             Content to prepend to the output. Default ''.
 *     @type string 'after'              Content to append to the output. Default ''.
 *     @type string 'prev_text'          Anchor text to display in the previous post link. Default ''.
 *     @type string 'next_text'          Anchor text to display in the next post link. Default ''.
 *     @type string 'screen_reader_text' Screen reader text for the nav element. Default 'Post break navigation'.
 *     @type string 'aria_label'         ARIA label text for the nav element. Default 'Page breaks'.
 *     @type string 'class'              Custom class for the nav element. Default 'page-break-navigation'.
 *     @type string 'type'               Link format. Can be 'list', 'select', or custom.
 *     @type string 'mid_size'           How many numbers to either side of the current pages. Default 2.
 *     @type string 'end_size'           How many numbers on either the start and the end list edges. Default 1.
 *     @type string 'link_before'        Content to prepend to each page number. Default ''.
 *     @type string 'link_after'         Content to append to each page number. Default ''.
 *     @type string 'links_before'       Content to prepend to the page numbers. Default ''.
 *     @type string 'links_after'        Content to append to the page numbers. Default ''.
 * }
 * @return string Markup for page break links.
 */
function get_the_page_break_navigation( array $args = array() ): string {
	global $page, $numpages, $multipage, $post;
	if ( ! $multipage ) {
		return '';
	}
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}
	$args += array(
		'before'             => '',
		'after'              => '',
		'prev_text'          => '',
		'next_text'          => '',
		'screen_reader_text' => __( 'Page break navigation' ),
		'aria_label'         => __( 'Page breaks' ),
		'class'              => 'page-break-navigation',
		'type'               => 'list',

		'mid_size'           => 2,
		'end_size'           => 1,
		'link_before'        => '',
		'link_after'         => '',
		'links_before'       => '',
		'links_after'        => '',
	);

	$lis = get_archive_link_items( '\wpinc\navi\get_page_break_link', $numpages, $page, (int) $args['mid_size'], (int) $args['end_size'], 'select' === $args['type'] );

	$ls   = array();
	$ls[] = make_adjacent_link_markup( '\wpinc\navi\get_page_break_link', true, $args['prev_text'], $numpages, $page );
	$ls[] = $args['links_before'];
	$ls[] = make_archive_links_markup( $lis, $args['type'], 'nav-items', $args['link_before'], $args['link_after'] );
	$ls[] = $args['links_after'];
	$ls[] = make_adjacent_link_markup( '\wpinc\navi\get_page_break_link', false, $args['next_text'], $numpages, $page );

	$ls  = implode( "\n", array_filter( $ls ) ) . "\n";
	$nav = make_navigation_markup( $ls, $args['class'], $args['screen_reader_text'] ?? '', $args['aria_label'] ?? '' );
	return $args['before'] . $nav . $args['after'];
}


// -----------------------------------------------------------------------------


/**
 * Retrieves page break link url. Based on _wp_link_page().
 *
 * @param int           $idx  Page number.
 * @param \WP_Post|null $post The post.
 * @return string Link.
 */
function get_page_break_link( int $idx, ?\WP_Post $post = null ): string {
	global $wp_rewrite;
	if ( empty( $post ) && isset( $GLOBALS['post'] ) ) {
		$post = $GLOBALS['post'];
	}
	if ( ! $post ) {
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
