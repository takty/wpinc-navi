<?php
/**
 * Navigation for Static Pages
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-05
 */

namespace wpinc\navi;

require_once __DIR__ . '/page-break.php';

/**
 * The.
 *
 * @param array $args       (Optional) The.
 * @param array $query_args (Optional) The.
 */
function the_child_page_navigation( array $args = array(), array $query_args = array() ) {
	echo get_the_child_page_navigation( $args, $query_args );  // phpcs:ignore
}

/**
 * The.
 *
 * @param array $args (Optional) The.
 * @param array $query_args (Optional) The.
 */
function the_sibling_page_navigation( array $args = array(), array $query_args = array() ) {
	echo get_the_sibling_page_navigation( $args, $query_args );  // phpcs:ignore
}


// -----------------------------------------------------------------------------


/**
 * The.
 *
 * @param array $args       The.
 * @param array $query_args The.
 * @return string The.
 */
function get_the_child_page_navigation( array $args = array(), array $query_args = array() ): string {
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}
	$args += array(
		'before'                   => '',
		'after'                    => '',
		'screen_reader_text'       => __( 'Child page navigation' ),
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
	$link = _make_nav_link_array( $ps, $post->ID, 'nav-children' );
	$html = _make_nav_markup( "$parent\n$link", $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $html . $args['after'];
}

/**
 * The.
 *
 * @param array $args       The.
 * @param array $query_args The.
 * @return string The.
 */
function get_the_sibling_page_navigation( array $args = array(), array $query_args = array() ): string {
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}
	$args += array(
		'before'                   => '',
		'after'                    => '',
		'screen_reader_text'       => __( 'Sibling page navigation' ),
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
		$href   = get_permalink( $pid );
		$title  = get_the_title( $pid );
		$parent = '<div class="nav-parent"><a class="nav-link" href="' . esc_attr( $href ) . '">' . esc_html( $title ) . '</a></div>';
	}
	$link = _make_nav_link_array( $ps, $post->ID, 'nav-sibling' );
	$html = _make_nav_markup( "$parent\n$link", $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $html . $args['after'];
}


// -----------------------------------------------------------------------------


/**
 * The.
 *
 * @access private
 *
 * @param array $ps   The.
 * @param array $args The.
 * @return string The.
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
 * The.
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
 * The.
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
 * The.
 *
 * @access private
 *
 * @param array  $ps         Arguments to retrieve posts.
 * @param int    $current_id The ID of the current post.
 * @param string $class      Custom class for the wrapping div element.
 * @return array Array of list items.
 */
function _make_li_markups( array $ps, int $current_id, string $class ): array {
	$lis = array();
	foreach ( $ps as $p ) {
		$title = get_the_title( $p->ID );
		if ( $current_id !== $p->ID ) {
			$link  = get_permalink( $post->ID );
			$lis[] = sprintf( '<li><a class="nav-link" href="%s">%s</a></li>', esc_url( $link ), esc_html( $title ) );
		} else {
			$lis[] = sprintf( '<li class="current"><span>%s</span></li>', esc_html( $title ) );
		}
	}
	$lis = implode( "\n", $lis );
	return sprintf( "<div class=\"%s\">\n<ul class\"pages\">\n%s</ul>\n</div>", $class, $lis );
}

/**
 * Wraps passed links in navigational markup.
 *
 * @access private
 *
 * @param string $links              Navigational links.
 * @param string $class              Custom class for the nav element.
 * @param string $screen_reader_text Screen reader text for the nav element.
 * @param string $aria_label         ARIA label for the nav element. Defaults to the value of $screen_reader_text.
 * @return string Navigation template tag.
 */
function _make_nav_markup( string $links, string $class, string $screen_reader_text, string $aria_label ): string {
	$temp = array(
		'<nav class="navigation %s" role="navigation" aria-label="%s">',
		"\t" . '<h2 class="screen-reader-text">%s</h2>',
		"\t" . '<div class="nav-links">%s</div>',
		'</nav>',
	);
	return sprintf(
		$template,
		sanitize_html_class( $class ),
		esc_attr( $aria_label ),
		esc_html( $screen_reader_text ),
		$links,
	);
}
