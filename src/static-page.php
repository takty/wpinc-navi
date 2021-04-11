<?php
/**
 * Navigation for Static Pages
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-10
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

/**
 * Displays a page break navigation, when applicable.
 *
 * @param array $args (Optional) See get_the_page_break_navigation() for available arguments.
 */
function the_page_break_navigation( array $args = array() ) {
	echo get_the_page_break_navigation( $args );  // phpcs:ignore
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
 *     @type string 'type'                     Link format. Can be 'list', 'select', or custom.
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
		'type'                     => 'list',
		'hide_page_with_thumbnail' => false,
	);
	global $post;
	$lis = _get_page_link_items( $query_args, $post->ID, $args['hide_page_with_thumbnail'] );
	if ( count( $lis ) === 0 ) {
		return '';
	}
	$ls   = array();
	$ls[] = '<div class="nav-parent current"><span>' . esc_html( get_the_title() ) . '</span></div>';
	$ls[] = '<div class="nav-items">';
	$ls[] = make_archive_links_markup( $lis, $args['type'] );
	$ls[] = '</div>';

	$ls  = improve( "\n", $ls ) . "\n";
	$nav = make_navigation_markup( $ls, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
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
 *     @type string 'type'                     Link format. Can be 'list', 'select', or custom.
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
		'type'                     => 'list',
		'hide_page_with_thumbnail' => false,
	);
	global $post;
	$lis = _get_page_link_items( $query_args, $post->post_parent, $args['hide_page_with_thumbnail'] );
	if ( count( $lis ) === 0 ) {
		return '';
	}
	$ls   = array();
	$ls[] = _get_parent_page_link();
	$ls[] = '<div class="nav-items">';
	$ls[] = make_archive_links_markup( $lis, $args['type'] );
	$ls[] = '</div>';

	$ls  = improve( "\n", array_filter( $ls ) ) . "\n";
	$nav = make_navigation_markup( $ls, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}

/**
 * Retrieves the parent page link.
 *
 * @access private
 *
 * @return string The parent page link wrapped in a div element.
 */
function _get_parent_page_link(): string {
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
 * @param array $query_args               Arguments for get_post().
 * @param int   $parent_id                The ID of the parent page.
 * @param bool  $hide_page_with_thumbnail Whether pages with post thumbnails are hidden. Default false.
 * @return array Link items.
 */
function _get_page_link_items( array $query_args, int $parent_id, bool $hide_page_with_thumbnail ): array {
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
		if ( $hide_page_with_thumbnail && has_post_thumbnail( $p->ID ) ) {
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


// -----------------------------------------------------------------------------


/**
 * Displays the navigation to page breaks, when applicable.
 *
 * @param array $args {
 *     (Optional) Default post navigation arguments.
 *
 *     @type string 'before'             Content to prepend to the output. Default ''.
 *     @type string 'after'              Content to append to the output. Default ''.
 *     @type string 'prev_text'          Anchor text to display in the previous post link. Default ''.
 *     @type string 'next_text'          Anchor text to display in the next post link. Default ''.
 *     @type string 'screen_reader_text' Screen reader text for the nav element. Default 'Post navigation'.
 *     @type string 'aria_label'         ARIA label text for the nav element. Default 'Page breaks'.
 *     @type string 'class'              Custom class for the nav element. Default 'page-break-navigation'.
 *     @type string 'type'               Link format. Can be 'list', 'select', or custom.
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
	);

	$lis = _get_page_break_link_items();

	$ls   = array();
	$ls[] = _get_adjacent_page_break_link( true, $args['prev_text'] );
	$ls[] = '<div class="nav-items">';
	$ls[] = make_archive_links_markup( $lis, $args['type'] );
	$ls[] = '</div>';
	$ls[] = _get_adjacent_page_break_link( false, $args['next_text'] );

	$ls  = improve( "\n", $ls ) . "\n";
	$nav = make_navigation_markup( $ls, 'page-break-navigation', $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}

/**
 * Retrieves the adjacent page break link.
 *
 * @access private
 *
 * @param bool   $previous Whether to display link to previous or next post.
 * @param string $text     Text for anchor link.
 * @return string The page break link wrapped in a div element.
 */
function _get_adjacent_page_break_link( bool $previous, string $text ): string {
	global $page, $numpages, $post;

	$cls     = $previous ? 'nav-previous' : 'nav-next';
	$is_link = $previous ? ( 1 !== $page ) : ( $numpages !== $page );

	if ( $is_link ) {
		$url = \wpinc\navi\page_break\get_page_break_link( $page + ( $previous ? -1 : 1 ), $post );
		$rel = $previous ? 'prev' : 'next';
		return sprintf( '<div class="%s"><a class="nav-link" href="%s" rel="%s">%s</a></div>', $cls, esc_url( $url ), $rel, esc_html( $text ) );
	}
	return sprintf( '<div class="%s disabled"><span>%s</span></div>', $cls, esc_html( $text ) );
}

/**
 * Retrieves archive link items of page breaks.
 *
 * @access private
 *
 * @return array Array of link items.
 */
function _get_page_break_link_items(): array {
	global $page, $numpages, $post;
	$lis = array();
	for ( $n = 1; $n <= $numpages; ++$n ) {
		$lis[] = array(
			'url'     => \wpinc\navi\page_break\get_page_break_link( $n, $post ),
			'text'    => number_format_i18n( $n ),
			'current' => ( $n === $page ),
		);
	}
	return $lis;
}
