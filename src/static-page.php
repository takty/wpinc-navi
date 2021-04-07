<?php
/**
 * Navigation for Static Pages
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-06
 */

namespace wpinc\navi;

require_once __DIR__ . '/markup.php';

/**
 * Displays a child page navigation, when applicable.
 *
 * @param array $args       (Optional) See get_the_child_page_navigation() for available arguments.
 * @param array $query_args (Optional) Arguments for get_post().
 */
function the_child_page_navigation( array $args = array(), array $query_args = array() ) {
	echo get_the_child_page_navigation( $args, $query_args );  // phpcs:ignore
}

/**
 * Displays a sibling page navigation, when applicable.
 *
 * @param array $args       (Optional) See get_the_sibling_page_navigation() for available arguments.
 * @param array $query_args (Optional) Arguments for get_post().
 */
function the_sibling_page_navigation( array $args = array(), array $query_args = array() ) {
	echo get_the_sibling_page_navigation( $args, $query_args );  // phpcs:ignore
}


// -----------------------------------------------------------------------------


/**
 * Retrieves a child page navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default navigation arguments.
 *
 *     @type string 'before'                   Content to prepend to the output. Default ''.
 *     @type string 'after'                    Content to append to the output. Default ''.
 *     @type string 'screen_reader_text'       Screen reader text for navigation element. Default 'Child pages navigation'.
 *     @type string 'aria_label'               ARIA label text for the nav element. Default 'Child pages'.
 *     @type string 'class'                    Custom class for the nav element. Default 'child-page-navigation'.
 *     @type bool   'hide_page_with_thumbnail' Whether pages with post thumbnails are hidden. Default false.
 * }
 * @param array $query_args (Optional) Arguments for get_post().
 * @return string Markup for child page links.
 */
function get_the_child_page_navigation( array $args = array(), array $query_args = array() ): string {
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}
	$args += array(
		'before'                   => '',
		'after'                    => '',
		'screen_reader_text'       => __( 'Child pages navigation' ),
		'aria_label'               => __( 'Child pages' ),
		'class'                    => 'child-page-navigation',
		'hide_page_with_thumbnail' => false,
	);

	$ps = _filter_posts( _get_child_pages( $query_args ), $args );
	if ( count( $ps ) === 0 ) {
		return '';
	}
	$parent = '<div class="nav-parent current"><span>' . esc_html( get_the_title() ) . '</span></div>';
	global $post;
	$items = _make_list_markup( $ps, $post->ID, 'nav-children' );
	$nav   = make_navigation_markup( "$parent\n$items", $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}

/**
 * Retrieves a sibling page navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default navigation arguments.
 *
 *     @type string 'before'                   Content to prepend to the output. Default ''.
 *     @type string 'after'                    Content to append to the output. Default ''.
 *     @type string 'screen_reader_text'       Screen reader text for navigation element. Default 'Sibling pages navigation'.
 *     @type string 'aria_label'               ARIA label text for the nav element. Default 'Sibling pages'.
 *     @type string 'class'                    Custom class for the nav element. Default 'sibling-page-navigation'.
 *     @type bool   'hide_page_with_thumbnail' Whether pages with post thumbnails are hidden. Default false.
 * }
 * @param array $query_args (Optional) Arguments for get_post().
 * @return string Markup for sibling page links.
 */
function get_the_sibling_page_navigation( array $args = array(), array $query_args = array() ): string {
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}
	$args += array(
		'before'                   => '',
		'after'                    => '',
		'screen_reader_text'       => __( 'Sibling pages navigation' ),
		'aria_label'               => __( 'Sibling pages' ),
		'class'                    => 'sibling-page-navigation',
		'hide_page_with_thumbnail' => false,
	);

	$ps = _filter_posts( _get_sibling_pages( $query_args ), $args );
	if ( count( $ps ) === 0 ) {
		return '';
	}
	$parent = '';
	global $post;
	$pid = $post->post_parent;
	if ( $pid ) {
		$url    = get_permalink( $pid );
		$text   = get_the_title( $pid );
		$parent = '<div class="nav-parent"><a class="nav-link" href="' . esc_attr( $url ) . '">' . esc_html( $text ) . '</a></div>';
	}
	$items = _make_list_markup( $ps, $post->ID, 'nav-sibling' );
	$nav   = make_navigation_markup( "$parent\n$items", $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}


// -----------------------------------------------------------------------------


/**
 * Filter an array of posts.
 *
 * @access private
 *
 * @param array $ps   Array of posts.
 * @param array $args Arguments.
 * @return array The filtered posts.
 */
function _filter_posts( array $ps, array $args ): array {
	if ( isset( $args['hide_page_with_thumbnail'] ) && $args['hide_page_with_thumbnail'] ) {
		$ps = array_filter(
			$ps,
			function ( $p ) {
				return ! has_post_thumbnail( $p->ID );
			}
		);
	}
	return array_values( $ps );
}

/**
 * Retrieves child pages.
 *
 * @access private
 *
 * @param array $args Arguments to retrieve posts.
 * @return array Array of post objects.
 */
function _get_child_pages( array $args = array() ): array {
	$args += array(
		'post_parent'    => get_the_ID(),
		'posts_per_page' => -1,
		'post_type'      => 'page',
		'orderby'        => 'menu_order',
		'order'          => 'asc',
	);
	return get_posts( $args );
}

/**
 * Retrieves sibling pages.
 *
 * @access private
 *
 * @param array $args Arguments to retrieve posts.
 * @return array Array of post objects.
 */
function _get_sibling_pages( array $args = array() ): array {
	$post  = get_post( get_the_ID() );
	$args += array(
		'post_parent'    => empty( $post ) ? 0 : $post->post_parent,
		'posts_per_page' => -1,
		'post_type'      => 'page',
		'orderby'        => 'menu_order',
		'order'          => 'asc',
	);
	return get_posts( $args );
}

/**
 * Wraps passed link in list item markup.
 *
 * @access private
 *
 * @param array  $ps         Arguments to retrieve posts.
 * @param int    $current_id Current post ID.
 * @param string $class      Custom class for the wrapping div element.
 * @return array Array of list items.
 */
function _make_list_markup( array $ps, int $current_id, string $class ): array {
	$lis = '';
	foreach ( $ps as $p ) {
		$url  = get_permalink( $post->ID );
		$text = get_the_title( $p->ID );
		$lis .= make_link_markup( $url, $text, 'html', '', '', $current_id === $p->ID );
	}
	return "<div class=\"$class\">\n<ul class=\"pages\">\n$lis</ul>\n</div>";
}
