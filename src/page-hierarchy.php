<?php
/**
 * Page Hierarchy Navigation
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2022-02-14
 */

namespace wpinc\navi;

require_once __DIR__ . '/markup.php';

/**
 * Displays a child page navigation, when applicable.
 *
 * @param array $args       (Optional) See get_the_child_page_navigation() for available arguments.
 * @param array $query_args (Optional) Arguments for get_post().
 */
function the_child_page_navigation( array $args = array(), array $query_args = array() ): void {
	echo get_the_child_page_navigation( $args, $query_args );  // phpcs:ignore
}

/**
 * Displays a sibling page navigation, when applicable.
 *
 * @param array $args       (Optional) See get_the_sibling_page_navigation() for available arguments.
 * @param array $query_args (Optional) Arguments for get_post().
 */
function the_sibling_page_navigation( array $args = array(), array $query_args = array() ): void {
	echo get_the_sibling_page_navigation( $args, $query_args );  // phpcs:ignore
}


// -----------------------------------------------------------------------------


/**
 * Retrieves a child page navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default navigation arguments.
 *
 *     @type string        'before'             Content to prepend to the output. Default ''.
 *     @type string        'after'              Content to append to the output. Default ''.
 *     @type string        'screen_reader_text' Screen reader text for navigation element. Default 'Child pages navigation'.
 *     @type string        'aria_label'         ARIA label text for the nav element. Default 'Child pages'.
 *     @type string        'class'              Custom class for the nav element. Default 'child-page-navigation'.
 *     @type string        'type'               Link format. Can be 'list', 'select', or custom.
 *     @type string        'link_before'        Content to prepend to each link. Default value: ''
 *     @type string        'link_after'         Content to append to each link. Default value: ''
 *     @type string        'links_before'       Content to prepend to links. Default value: ''
 *     @type string        'links_after'        Content to append to links. Default value: ''
 *     @type callable|null 'filter'             Callback function for filtering. Default null.
 * }
 * @param array $query_args (Optional) Arguments for get_post().
 * @return string Markup for child page links.
 */
function get_the_child_page_navigation( array $args = array(), array $query_args = array() ): string {
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}
	$args += array(
		'before'             => '',
		'after'              => '',
		'screen_reader_text' => __( 'Child pages navigation' ),
		'aria_label'         => __( 'Child pages' ),
		'class'              => 'child-page-navigation',
		'type'               => 'list',

		'link_before'        => '',
		'link_after'         => '',
		'links_before'       => '',
		'links_after'        => '',
		'filter'             => null,
	);
	global $post;
	$lis = _get_page_link_items( $query_args, $post->ID, $args['filter'] );
	if ( count( $lis ) === 0 ) {
		return '';
	}
	$ls   = array();
	$ls[] = '<div class="nav-parent current"><span>' . esc_html( get_the_title() ) . '</span></div>';
	$ls[] = $args['links_before'];
	$ls[] = make_archive_links_markup( $lis, $args['type'], 'nav-items', $args['link_before'], $args['link_after'] );
	$ls[] = $args['links_after'];

	$ls  = implode( "\n", array_filter( $ls ) ) . "\n";
	$nav = make_navigation_markup( $ls, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}

/**
 * Retrieves a sibling page navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default navigation arguments.
 *
 *     @type string        'before'             Content to prepend to the output. Default ''.
 *     @type string        'after'              Content to append to the output. Default ''.
 *     @type string        'screen_reader_text' Screen reader text for navigation element. Default 'Sibling pages navigation'.
 *     @type string        'aria_label'         ARIA label text for the nav element. Default 'Sibling pages'.
 *     @type string        'class'              Custom class for the nav element. Default 'sibling-page-navigation'.
 *     @type string        'type'               Link format. Can be 'list', 'select', or custom.
 *     @type string        'link_before'        Content to prepend to each link. Default value: ''
 *     @type string        'link_after'         Content to append to each link. Default value: ''
 *     @type string        'links_before'       Content to prepend to links. Default value: ''
 *     @type string        'links_after'        Content to append to links. Default value: ''
 *     @type callable|null 'filter'             Callback function for filtering. Default null.
 * }
 * @param array $query_args (Optional) Arguments for get_post().
 * @return string Markup for sibling page links.
 */
function get_the_sibling_page_navigation( array $args = array(), array $query_args = array() ): string {
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}
	$args += array(
		'before'             => '',
		'after'              => '',
		'screen_reader_text' => __( 'Sibling pages navigation' ),
		'aria_label'         => __( 'Sibling pages' ),
		'class'              => 'sibling-page-navigation',
		'type'               => 'list',

		'link_before'        => '',
		'link_after'         => '',
		'links_before'       => '',
		'links_after'        => '',
		'filter'             => null,
	);
	global $post;
	$lis = _get_page_link_items( $query_args, $post->post_parent, $args['filter'] );
	if ( count( $lis ) === 0 ) {
		return '';
	}
	$ls   = array();
	$ls[] = _make_parent_page_link_markup();
	$ls[] = $args['links_before'];
	$ls[] = make_archive_links_markup( $lis, $args['type'], 'nav-items', $args['link_before'], $args['link_after'] );
	$ls[] = $args['links_after'];

	$ls  = implode( "\n", array_filter( $ls ) ) . "\n";
	$nav = make_navigation_markup( $ls, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}

/**
 * Makes the parent page link markup.
 *
 * @access private
 *
 * @return string The parent page link wrapped in a div element.
 */
function _make_parent_page_link_markup(): string {
	global $post;
	$pid = $post->post_parent;
	if ( ! $pid ) {
		return '';
	}
	$url  = get_permalink( $pid );
	$text = get_the_title( $pid );
	return sprintf( '<div class="nav-parent"><a class="nav-link" href="%s">%s</a></div>', esc_attr( $url ), esc_html( $text ) );
}

/**
 * Retrieves archive link items of pages.
 *
 * @access private
 *
 * @param array         $query_args Arguments for get_posts().
 * @param int           $parent_id  The ID of the parent page.
 * @param callable|null $filter     Callback function for filtering. Default null.
 * @return array Link items.
 */
function _get_page_link_items( array $query_args, int $parent_id, ?callable $filter = null ): array {
	$query_args += array(
		'post_parent'    => $parent_id,
		'posts_per_page' => -1,
		'post_type'      => 'page',
		'orderby'        => 'menu_order',
		'order'          => 'asc',
	);

	$ps = get_posts( $query_args );
	global $post;
	$lis = array();
	foreach ( $ps as $p ) {
		if ( $filter && ! call_user_func( $filter, $p ) ) {
			continue;
		}
		$lis[] = array(
			'url'     => get_permalink( $p->ID ),
			'text'    => get_the_title( $p->ID ),
			'current' => $post->ID === $p->ID,
		);
	}
	return $lis;
}
