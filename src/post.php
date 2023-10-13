<?php
/**
 * Post and Posts Navigation
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2023-10-13
 */

namespace wpinc\navi;

require_once __DIR__ . '/markup.php';
require_once __DIR__ . '/page-break.php';

/** phpcs:ignore
 * Displays a post navigation, when applicable.
 *
 * phpcs:ignore
 * @param array{
 *     before?            : string,
 *     after?             : string,
 *     prev_text?         : string,
 *     next_text?         : string,
 *     screen_reader_text?: string,
 *     aria_label?        : string,
 *     class?             : string,
 *     in_same_term?      : bool,
 *     excluded_terms?    : int[]|string,
 *     taxonomy?          : string,
 *     has_archive_link?  : bool,
 *     archive_text?      : string,
 *     archive_link_pos?  : string,
 * } $args (Optional) Default post navigation arguments.
 */
function the_post_navigation( array $args = array() ): void {
	echo get_the_post_navigation( $args );  // phpcs:ignore
}

/** phpcs:ignore
 * Displays a posts navigation, when applicable.
 *
 * phpcs:ignore
 * @param array{
 *     before?            : string,
 *     after?             : string,
 *     prev_text?         : string,
 *     next_text?         : string,
 *     screen_reader_text?: string,
 *     aria_label?        : string,
 *     class?             : string,
 *     type?              : string,
 *     mid_size?          : string,
 *     end_size?          : string,
 *     link_before?       : string,
 *     link_after?        : string,
 *     links_before?      : string,
 *     links_after?       : string,
 *     add_args?          : string,
 *     add_fragment?      : string,
 * } $args (Optional) Default posts navigation arguments.
 */
function the_posts_navigation( array $args = array() ): void {
	echo get_the_posts_navigation( $args );  // phpcs:ignore
}


// -----------------------------------------------------------------------------


/** phpcs:ignore
 * Retrieves a post navigation, when applicable.
 *
 * @global \WP_Post $post
 * phpcs:ignore
 * @param array{
 *     before?            : string,
 *     after?             : string,
 *     prev_text?         : string,
 *     next_text?         : string,
 *     screen_reader_text?: string,
 *     aria_label?        : string,
 *     class?             : string,
 *     in_same_term?      : bool,
 *     excluded_terms?    : int[]|string,
 *     taxonomy?          : string,
 *     has_archive_link?  : bool,
 *     archive_text?      : string,
 *     archive_link_pos?  : string,
 * } $args (Optional) Default post navigation arguments.
 * $args {
 *     (Optional) Default post navigation arguments.
 *
 *     @type string       'before'               Content to prepend to the output. Default ''.
 *     @type string       'after'                Content to append to the output. Default ''.
 *     @type string       'prev_text'            Anchor text to display in the previous post link. Default ''.
 *     @type string       'next_text'            Anchor text to display in the next post link. Default ''.
 *     @type string       'screen_reader_text'   Screen reader text for the nav element. Default 'Post navigation'.
 *     @type string       'aria_label'           ARIA label text for the nav element. Default 'Post'.
 *     @type string       'class'                Custom class for the nav element. Default 'post-navigation'.
 *     @type bool         'in_same_term'         Whether link should be in a same taxonomy term. Default false.
 *     @type int[]|string 'excluded_terms'       Array or comma-separated list of excluded term IDs.
 *     @type string       'taxonomy'             Taxonomy, if 'in_same_term' is true. Default 'category'.
 *     @type bool         'has_archive_link'     Whether the archive link is contained. Default false.
 *     @type string       'archive_text'         Anchor text to display in the archive link. Default 'List'.
 *     @type string       'archive_link_pos'     Position of archive link, if 'has_archive_link' is true. Can be 'start', 'center', or 'end'. Default 'center'.
 * }
 * @return string Markup for post links.
 */
function get_the_post_navigation( array $args = array() ): string {
	$args += array(
		'before'             => '',
		'after'              => '',
		'prev_text'          => '',
		'next_text'          => '',
		'screen_reader_text' => __( 'Post navigation' ),
		'aria_label'         => __( 'Post' ),
		'class'              => 'post-navigation',

		'in_same_term'       => false,
		'excluded_terms'     => '',
		'taxonomy'           => 'category',

		'has_archive_link'   => false,
		'archive_text'       => __( 'List' ),
		'archive_link_pos'   => 'center',
	);

	$prev = _get_adjacent_post_link( $args['prev_text'], true, $args['in_same_term'], $args['excluded_terms'], $args['taxonomy'] );
	$next = _get_adjacent_post_link( $args['next_text'], false, $args['in_same_term'], $args['excluded_terms'], $args['taxonomy'] );
	$arch = '';
	global $post;
	if ( $args['has_archive_link'] ) {
		$url  = (string) get_post_type_archive_link( $post->post_type );
		$arch = sprintf( '<div class="nav-archive"><a class="nav-link" href="%s">%s</a></div>', esc_url( $url ), esc_html( $args['archive_text'] ) );
	}
	$temps = array(
		'start'  => '%3$s%1$s%2$s',
		'center' => '%1$s%3$s%2$s',
		'end'    => '%1$s%2$s%3$s',
	);
	$align = $temps[ $args['archive_link_pos'] ] ?? $temps['center'];

	$ls  = sprintf( $align, $prev, $next, $arch );
	$nav = make_navigation_markup( $ls, $args['class'], $args['screen_reader_text'], $args['aria_label'] );
	return $args['before'] . $nav . $args['after'];
}

/**
 * Retrieves the adjacent page break link.
 *
 * @access private
 * @psalm-suppress RedundantCastGivenDocblockType
 *
 * @param string       $text           Text for anchor link.
 * @param bool         $previous       Whether to display link to previous or next post.
 * @param bool         $in_same_term   Whether link should be in a same taxonomy term..
 * @param int[]|string $excluded_terms Array or comma-separated list of excluded terms IDs.
 * @param string       $taxonomy       Taxonomy, if $in_same_term is true.
 * @return string The link URL of the previous or next post in relation to the current post.
 */
function _get_adjacent_post_link( string $text, bool $previous, bool $in_same_term, $excluded_terms, string $taxonomy ): string {
	$cls  = $previous ? 'nav-previous' : 'nav-next';
	$post = get_adjacent_post( $in_same_term, $excluded_terms, $previous, $taxonomy );

	if ( $post instanceof \WP_Post ) {
		$url = (string) get_permalink( $post );
		$rel = $previous ? 'prev' : 'next';
		return sprintf( '<div class="%s"><a class="nav-link" href="%s" rel="%s">%s</a></div>', $cls, esc_url( $url ), $rel, esc_html( $text ) );
	}
	return sprintf( '<div class="%s disabled"><span>%s</span></div>', $cls, esc_html( $text ) );
}


// -----------------------------------------------------------------------------


/** phpcs:ignore
 * Retrieves a posts navigation, when applicable.
 *
 * @global \WP_Query   $wp_query   Query.
 * @global \WP_Rewrite $wp_rewrite Rewrite.
 * phpcs:ignore
 * @param array{
 *     before?            : string,
 *     after?             : string,
 *     prev_text?         : string,
 *     next_text?         : string,
 *     screen_reader_text?: string,
 *     aria_label?        : string,
 *     class?             : string,
 *     type?              : string,
 *     mid_size?          : string,
 *     end_size?          : string,
 *     link_before?       : string,
 *     link_after?        : string,
 *     links_before?      : string,
 *     links_after?       : string,
 *     add_args?          : string,
 *     add_fragment?      : string,
 * } $args (Optional) Default posts navigation arguments.
 * $args {
 *     (Optional) Default posts navigation arguments.
 *
 *     @type string 'before'             Content to prepend to the output. Default 'Previous'.
 *     @type string 'after'              Content to append to the output. Default 'Next'.
 *     @type string 'prev_text'          Anchor text to display in the previous post link. Default ''.
 *     @type string 'next_text'          Anchor text to display in the next post link. Default ''.
 *     @type string 'screen_reader_text' Screen reader text for the nav element. Default 'Posts navigation'.
 *     @type string 'aria_label'         ARIA label text for the nav element. Default 'Pages'.
 *     @type string 'class'              Custom class for the nav element. Default 'page-break-navigation'.
 *     @type string 'type'               Link format. Can be 'list', 'select', or custom.
 *     @type string 'mid_size'           How many numbers to either side of the current pages. Default 2.
 *     @type string 'end_size'           How many numbers on either the start and the end list edges. Default 1.
 *     @type string 'link_before'        Content to prepend to each page number. Default ''.
 *     @type string 'link_after'         Content to append to each page number. Default ''.
 *     @type string 'links_before'       Content to prepend to the page numbers. Default ''.
 *     @type string 'links_after'        Content to append to the page numbers. Default ''.
 *     @type string 'add_args'           An array of query args to add.
 *     @type string 'add_fragment'       A string to append to each link.
 * }
 * @return string Markup for posts links.
 */
function get_the_posts_navigation( array $args = array() ): string {
	global $wp_query, $wp_rewrite;
	if ( $wp_query->max_num_pages < 2 ) {
		return '';
	}
	if ( is_search() && empty( get_search_query() ) ) {
		return '';
	}
	if ( ! empty( $args['screen_reader_text'] ) && empty( $args['aria_label'] ) ) {
		$args['aria_label'] = $args['screen_reader_text'];
	}
	$args += array(
		'before'             => '',
		'after'              => '',
		'prev_text'          => _x( 'Previous', 'previous set of posts' ),
		'next_text'          => _x( 'Next', 'next set of posts' ),
		'screen_reader_text' => __( 'Posts navigation' ),
		'aria_label'         => __( 'Posts' ),
		'class'              => 'posts-navigation',
		'type'               => 'list',

		'mid_size'           => 2,
		'end_size'           => 1,
		'link_before'        => '',
		'link_after'         => '',
		'links_before'       => '',
		'links_after'        => '',
		'add_args'           => array(), // Array of query args to add.
		'add_fragment'       => '',
	);
	if ( ! is_array( $args['add_args'] ) ) {
		$args['add_args'] = array();
	}
	$base      = html_entity_decode( get_pagenum_link() );
	$url_parts = explode( '?', $base );
	$total     = $wp_query->max_num_pages;
	$current   = get_query_var( 'paged', 1 );
	$current   = is_numeric( $current ) ? (int) $current : 1;
	$base      = trailingslashit( $url_parts[0] ) . '%_%';
	$format    = $wp_rewrite->using_index_permalinks() && ! strpos( $base, 'index.php' ) ? 'index.php/' : '';
	$format   .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

	if ( isset( $url_parts[1] ) ) {
		$ps             = explode( '?', str_replace( '%_%', $format, $base ) );
		$format_query   = $ps[1] ?? '';
		$format_args    = array();
		$url_query_args = array();
		wp_parse_str( $format_query, $format_args );
		wp_parse_str( $url_parts[1], $url_query_args );
		if ( ! empty( $format_args ) && ! empty( $url_query_args ) ) {
			foreach ( $format_args as $format_arg => $_format_arg_value ) {
				unset( $url_query_args[ $format_arg ] );
			}
			$args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
		}
	}
	$get_link = _get_paging_link_function( $format, $base, $args['add_args'], $args['add_fragment'] );

	$lis = get_archive_link_items( $get_link, $total, $current, (int) $args['mid_size'], (int) $args['end_size'], 'select' === $args['type'] );

	$ls   = array();
	$ls[] = make_adjacent_link_markup( $get_link, true, $args['prev_text'], $total, $current );
	$ls[] = $args['links_before'];
	$ls[] = make_archive_links_markup( $lis, $args['type'], 'nav-items', $args['link_before'], $args['link_after'] );
	$ls[] = $args['links_after'];
	$ls[] = make_adjacent_link_markup( $get_link, false, $args['next_text'], $total, $current );

	$ls  = implode( "\n", array_filter( $ls ) ) . "\n";
	$nav = make_navigation_markup( $ls, $args['class'], $args['screen_reader_text'], $args['aria_label'] );  // @phan-suppress-current-line PhanTypePossiblyInvalidDimOffset
	return $args['before'] . $nav . $args['after'];
}

/**
 * Gets the function that retrieves paging links.
 *
 * @access private
 *
 * @param string               $format       Format for the pagination structure.
 * @param string               $base         Base of the paginated url.
 * @param array<string, mixed> $add_args     An array of query args to add.
 * @param string               $add_fragment A string to append to each link.
 * @return callable Function that retrieves paging links.
 */
function _get_paging_link_function( string $format, string $base, array $add_args, string $add_fragment ): callable {
	return function ( int $idx ) use ( $format, $base, $add_args, $add_fragment ) {
		$url = str_replace( '%_%', 1 === $idx ? '' : $format, $base );
		$url = str_replace( '%#%', (string) $idx, $url );
		if ( $add_args ) {
			$url = add_query_arg( $add_args, $url );
		}
		$url = user_trailingslashit( $url ) . $add_fragment;
		return apply_filters( 'paginate_links', $url );
	};
}
