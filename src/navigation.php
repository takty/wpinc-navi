<?php
/**
 * Navigation Tags
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-09
 */

namespace wpinc\navi;

require_once __DIR__ . '/markup.php';
require_once __DIR__ . '/page-break.php';

/**
 * Displays a post navigation, when applicable.
 *
 * @param array $args (Optional) See get_the_post_navigation() for available arguments.
 */
function the_post_navigation( array $args = array() ) {
	echo get_the_post_navigation( $args );  // phpcs:ignore
}

/**
 * Displays a posts navigation, when applicable.
 *
 * @param array $args (Optional) See get_the_posts_navigation() for available arguments.
 */
function the_posts_navigation( array $args = array() ) {
	echo get_the_posts_navigation( $args );  // phpcs:ignore
}


// -----------------------------------------------------------------------------


/**
 * Retrieves a post navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default navigation arguments.
 *
 *     @type string 'before'                   Content to prepend to the output. Default ''.
 *     @type string 'after'                    Content to append to the output. Default ''.
 * }
 * @return string Markup for post links.
 */
function get_the_post_navigation( array $args = array() ): string {
	$args += array(
		'before'             => '',
		'after'              => '',
		'prev_text'          => '',
		'next_text'          => '',
		'in_same_term'       => false,
		'excluded_terms'     => '',
		'taxonomy'           => 'category',
		'screen_reader_text' => __( 'Post navigation' ),
		'aria_label'         => __( 'Post' ),

		'has_archive_link'   => false,
		'archive_text'       => __( 'List' ),
		'archive_link_pos'   => 'center',
	);

	$prev = _get_adjacent_post_link( $args['prev_text'], true, $args['in_same_term'], $args['excluded_terms'], $args['taxonomy'] );
	$next = _get_adjacent_post_link( $args['next_text'], false, $args['in_same_term'], $args['excluded_terms'], $args['taxonomy'] );
	$arch = '';
	if ( $args['has_archive_link'] ) {
		global $post;
		$url  = get_post_type_archive_link( $post->post_type );
		$arch = sprintf( '<div class="nav-archive"><a class="nav-link" href="%s">%s</a></div>', esc_url( $url ), esc_html( $args['archive_text'] ) );
	}
	$temps = array(
		'start'  => '%3$s%1$s%2$s',
		'center' => '%1$s%3$s%2$s',
		'end'    => '%1$s%2$s%3$s',
	);
	$align = $temps[ $args['archive_link_pos'] ] ?? $temps['center'];
	$links = sprintf( $align, $prev, $next, $arch );
	$nav   = make_navigation_markup( $links, 'post-navigation', $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}

/**
 * Retrieves the adjacent page break link.
 *
 * @access private
 *
 * @param string       $text           Text for anchor link.
 * @param bool         $previous       Whether to display link to previous or next post.
 * @param bool         $in_same_term   Whether link should be in a same taxonomy term..
 * @param int[]|string $excluded_terms Array or comma-separated list of excluded terms IDs.
 * @param string       $taxonomy       Taxonomy, if $in_same_term is true.
 * @return string The link URL of the previous or next post in relation to the current post.
 */
function _get_adjacent_post_link( string $text, bool $previous, $in_same_term, $excluded_terms, $taxonomy ): string {
	$cls  = $previous ? 'nav-previous' : 'nav-next';
	$post = get_adjacent_post( $in_same_term, $excluded_terms, $previous, $taxonomy );

	if ( $post ) {
		$url = get_permalink( $post );
		$rel = $previous ? 'prev' : 'next';
		return sprintf( '<div class="%s"><a class="nav-link" href="%s" rel="%s">%s</a></div>', $cls, esc_url( $url ), $rel, esc_html( $text ) );
	}
	return sprintf( '<div class="%s disabled"><span>%s</span></div>', $cls, esc_html( $text ) );
}


// -----------------------------------------------------------------------------


/**
 * Retrieves a posts navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default navigation arguments.
 *
 *     @type string 'before'                   Content to prepend to the output. Default ''.
 *     @type string 'after'                    Content to append to the output. Default ''.
 * }
 * @return string Markup for posts links.
 */
function get_the_posts_navigation( array $args = array() ): string {
	if ( $GLOBALS['wp_query']->max_num_pages < 2 ) {
		return '';
	}
	$args += array(
		'before'             => '',
		'after'              => '',
		'mid_size'           => 1,
		'prev_text'          => _x( 'Previous', 'previous set of posts' ),
		'next_text'          => _x( 'Next', 'next set of posts' ),
		'screen_reader_text' => __( 'Posts navigation' ),
		'aria_label'         => __( 'Posts' ),
		'class'              => 'posts-navigation',
		'type'               => 'list',
	);
	global $wp_query, $wp_rewrite;

	$pagenum_link = html_entity_decode( get_pagenum_link() );
	$url_parts    = explode( '?', $pagenum_link );
	$total        = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
	$current      = get_query_var( 'paged' ) ? ( (int) get_query_var( 'paged' ) ) : 1;
	$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';
	$format       = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
	$format      .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

	$defaults = array(
		'base'          => $pagenum_link,
		'format'        => $format,
		'total'         => $total,
		'current'       => $current,
		'aria_current'  => 'page',
		'show_all'      => false,
		'prev_next'     => true,
		'end_size'      => 1,
		'mid_size'      => 2,
		'add_args'      => array(), // array of query args to add.
		'add_fragment'  => '',
		'number_before' => '',
		'number_after'  => '',
	);

	$args = wp_parse_args( $args, $defaults );
	if ( ! is_array( $args['add_args'] ) ) {
		$args['add_args'] = array();
	}
	if ( isset( $url_parts[1] ) ) {
		$format       = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
		$format_query = isset( $format[1] ) ? $format[1] : '';
		wp_parse_str( $format_query, $format_args );
		wp_parse_str( $url_parts[1], $url_query_args );
		foreach ( $format_args as $format_arg => $format_arg_value ) {
			unset( $url_query_args[ $format_arg ] );
		}
		$args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
	}

	$total = (int) $args['total'];
	if ( $total < 2 ) {
		return '';
	}
	$current  = (int) $args['current'];
	$end_size = (int) $args['end_size'];
	$mid_size = (int) $args['mid_size'];

	$page_link = _get_paging_link_function( $args['format'], $args['base'], $args['add_args'], $args['add_fragment'] );

	$lis = _get_paging_link_items( $total, $current, $mid_size, $end_size, $page_link );
	$ls  = array();

	if ( $args['prev_next'] ) {
		$ls[] = _get_adjacent_paging( true, $args['prev_text'], $total, $current, $page_link );
	}
	$ls[] = '<div class="nav-items">';
	$ls[] = make_archive_links_markup( $lis, $args['type'], '', $args['number_before'], $args['number_after'] );
	$ls[] = '</div>';
	if ( $args['prev_next'] ) {
		$ls[] = _get_adjacent_paging( false, $args['next_text'], $total, $current, $page_link );
	}
	$ls  = improve( "\n", $ls ) . "\n";
	$nav = make_navigation_markup( $ls, 'page-break-navigation', $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}

/**
 * The.
 *
 * @access private
 */
function _get_paging_link_function( string $format, string $base, array $add_args, string $add_fragment ): callable {
	return function ( int $idx ) use ( $format, $base, $add_args, $add_fragment ) {
		$url = str_replace( '%_%', 1 === $idx ? '' : $format, $base );
		$url = str_replace( '%#%', $idx, $url );
		if ( $add_args ) {
			$url = add_query_arg( $add_args, $url );
		}
		$url .= $add_fragment;
		return apply_filters( 'paginate_links', $url );
	};
}

/**
 *
 * @access private
 */
function _get_adjacent_paging( bool $previous, string $text, int $total, int $current, callable $page_link ): string {
	$cls     = $previous ? 'nav-previous' : 'nav-next';
	$is_link = $previous ? ( 1 < $current ) : ( $current < $total );

	if ( $is_link ) {
		$url = $page_link( $current + ( $previous ? -1 : 1 ) );
		return '<div class="' . $cls . '"><a href="' . $url . '">' . $text . '</a></div>';
	}
	return '<div class="' . $cls . ' disabled"><span>' . $text . '</span></div>';
}

/**
 *
 * @access private
 */
function _get_paging_link_items( int $total, int $current, int $mid_size, int $end_size, callable $page_link ): array {
	$end_size = ( $end_size < 1 ) ? 1 : $end_size;
	$mid_size = ( $mid_size < 0 ) ? 2 : $mid_size;

	$dots = false;
	$lis  = array();

	for ( $n = 1; $n <= $total; ++$n ) {
		if (
			$n === $current ||
			$n <= $end_size ||
			( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) ||
			$n > $total - $end_size
		) {
			$dots  = true;
			$lis[] = array(
				'url'     => $page_link( $n ),
				'text'    => number_format_i18n( $n ),
				'current' => $n === $current,
			);
		} elseif ( $dots ) {
			$dots  = false;
			$lis[] = array(
				'text' => __( '&hellip;' ),
				'dots' => true,
			);
		}
	}
	return $lis;
}
