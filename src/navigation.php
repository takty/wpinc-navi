<?php
/**
 * Navigation Tags
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
 * @param array $args (Optional) The.
 */
function the_post_navigation( array $args = array() ) {
	echo get_the_post_navigation( $args );  // phpcs:ignore
}

/**
 * The.
 *
 * @param array $args (Optional) The.
 */
function the_posts_navigation( array $args = array() ) {
	echo get_the_posts_navigation( $args );  // phpcs:ignore
}

/**
 * The.
 *
 * @param array $args (Optional) The.
 */
function the_page_break_navigation( array $args = array() ) {
	echo get_the_page_break_navigation( $args );  // phpcs:ignore
}


// -----------------------------------------------------------------------------


/**
 * The.
 *
 * @param array $args (Optional) The.
 * @return string The.
 */
function get_the_post_navigation( array $args = array() ): string {
	$args += array(
		'before'             => '',
		'after'              => '',
		'prev_text'          => '%title',
		'next_text'          => '%title',
		'list_text'          => __( 'List' ),
		'in_same_term'       => false,
		'excluded_terms'     => '',
		'taxonomy'           => 'category',
		'screen_reader_text' => __( 'Post navigation' ),
		'has_list_link'      => false,
		'link_list_pos'      => 'center',
	);

	$prev = get_previous_post_link(
		'<div class="nav-previous">%link</div>',
		$args['prev_text'],
		$args['in_same_term'],
		$args['excluded_terms'],
		$args['taxonomy']
	);
	if ( ! $prev ) {
		$str  = ( '%' === $args['prev_text'][0] ) ? '&nbsp;' : $args['prev_text'];
		$prev = '<div class="nav-previous disabled"><a>' . $str . '</a></div>';
	}
	$next = get_next_post_link(
		'<div class="nav-next">%link</div>',
		$args['next_text'],
		$args['in_same_term'],
		$args['excluded_terms'],
		$args['taxonomy']
	);
	if ( ! $next ) {
		$str  = ( '%' === $args['next_text'][0] ) ? '&nbsp;' : $args['next_text'];
		$next = '<div class="nav-next disabled"><a>' . $str . '</a></div>';
	}
	$list = '';
	if ( $args['has_list_link'] ) {
		global $post;
		$list = '<div class="nav-list"><a href="' . esc_url( get_post_type_archive_link( $post->post_type ) ) . '">' . $args['list_text'] . '</a></div>';
	}
	if ( ! $prev && ! $next && ! $list ) {
		return '';
	}
	$temp = '';
	switch ( $args['link_list_pos'] ) {
		case 'start':
			$temp = $list . $prev . $next;
			break;
		case 'center':
			$temp = $prev . $list . $next;
			break;
		case 'end':
			$temp = $prev . $next . $list;
			break;
	}
	return $args['before'] . _navigation_markup( $temp, 'post-navigation', $args['screen_reader_text'] ) . $args['after'];
}

/**
 * The.
 *
 * @param array $args (Optional) The.
 * @return string The.
 */
function get_the_posts_navigation( array $args = array() ): string {
	if ( $GLOBALS['wp_query']->max_num_pages < 2 ) {
		return '';
	}
	$args = wp_parse_args(
		$args,
		array(
			'before'             => '',
			'after'              => '',
			'mid_size'           => 1,
			'prev_text'          => _x( 'Previous', 'previous set of posts' ),
			'next_text'          => _x( 'Next', 'next set of posts' ),
			'screen_reader_text' => __( 'Posts navigation' ),
			'aria_label'         => __( 'Posts' ),
			'class'              => 'pagination',
		)
	);
	if ( isset( $args['type'] ) && 'array' === $args['type'] ) {
		$args['type'] = 'plain';
	}
	$links = _paginate_links( $args );
	if ( $links ) {
		return $args['before'] . _navigation_markup( $links, $args['class'], 'pagination', $args['screen_reader_text'], $args['aria_label'] ) . $args['after'];
	}
	return '';
}

/**
 * The.
 *
 * @access private
 *
 * @param array $args The.
 * @return string The.
 */
function _paginate_links( array $args = array() ): string {
	global $wp_query, $wp_rewrite;

	$pagenum_link = html_entity_decode( get_pagenum_link() );
	$url_parts    = explode( '?', $pagenum_link );
	$total        = isset( $wp_query->max_num_pages ) ? $wp_query->max_num_pages : 1;
	$current      = get_query_var( 'paged' ) ? ( (int) get_query_var( 'paged' ) ) : 1;
	$pagenum_link = trailingslashit( $url_parts[0] ) . '%_%';
	$format       = $wp_rewrite->using_index_permalinks() && ! strpos( $pagenum_link, 'index.php' ) ? 'index.php/' : '';
	$format      .= $wp_rewrite->using_permalinks() ? user_trailingslashit( $wp_rewrite->pagination_base . '/%#%', 'paged' ) : '?paged=%#%';

	$defaults = array(
		'base'               => $pagenum_link,
		'format'             => $format,
		'total'              => $total,
		'current'            => $current,
		'aria_current'       => 'page',
		'show_all'           => false,
		'prev_next'          => true,
		'prev_text'          => __( '&laquo; Previous' ),
		'next_text'          => __( 'Next &raquo;' ),
		'end_size'           => 1,
		'mid_size'           => 2,
		'type'               => 'plain',
		'add_args'           => array(), // array of query args to add.
		'add_fragment'       => '',
		'before_page_number' => '',
		'after_page_number'  => '',
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
	if ( $end_size < 1 ) {
		$end_size = 1;
	}
	$mid_size = (int) $args['mid_size'];
	if ( $mid_size < 0 ) {
		$mid_size = 2;
	}
	$add_args = $args['add_args'];

	$pages = array();
	$prev  = '';
	$next  = '';
	$dots  = false;

	if ( $args['prev_next'] ) {
		$link = '';
		if ( 1 < $current ) {
			$link = str_replace( '%_%', $current <= 2 ? '' : $args['format'], $args['base'] );
			$link = str_replace( '%#%', $current - 1, $link );
			if ( $add_args ) {
				$link = add_query_arg( $add_args, $link );
			}
			$link .= $args['add_fragment'];
			$link  = esc_url( apply_filters( 'paginate_links', $link ) );
		}
		if ( $link ) {
			$prev = '<div class="nav-previous"><a href="' . $link . '">' . $args['prev_text'] . '</a></div>';
		} else {
			$prev = '<div class="nav-previous disabled"><span>' . $args['prev_text'] . '</span></div>';
		}
	}
	for ( $n = 1; $n <= $total; ++$n ) {
		if ( $n === $current ) {
			$pages[] = '<li class="page-number current"><span aria-current="' . esc_attr( $args['aria_current'] ) . '">' . $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number'] . '</span></li>';
			$dots    = true;
		} else {
			if ( $args['show_all'] || ( $n <= $end_size || ( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) || $n > $total - $end_size ) ) {
				$link = str_replace( '%_%', 1 === $n ? '' : $args['format'], $args['base'] );
				$link = str_replace( '%#%', $n, $link );
				if ( $add_args ) {
					$link = add_query_arg( $add_args, $link );
				}
				$link .= $args['add_fragment'];

				$pages[] = '<li class="page-number"><a href="' . esc_url( apply_filters( 'paginate_links', $link ) ) . '">' . $args['before_page_number'] . number_format_i18n( $n ) . $args['after_page_number'] . '</a></li>';
				$dots    = true;
			} elseif ( $dots && ! $args['show_all'] ) {
				$pages[] = '<li class="dots"><span>' . __( '&hellip;' ) . '</span></li>';
				$dots    = false;
			}
		}
	}
	if ( $args['prev_next'] ) {
		$link = '';
		if ( $current < $total ) {
			$link = str_replace( '%_%', $args['format'], $args['base'] );
			$link = str_replace( '%#%', $current + 1, $link );
			if ( $add_args ) {
				$link = add_query_arg( $add_args, $link );
			}
			$link .= $args['add_fragment'];
			$link  = esc_url( apply_filters( 'paginate_links', $link ) );
		}
		if ( $link ) {
			$next = '<div class="nav-next"><a href="' . $link . '">' . $args['next_text'] . '</a></div>';
		} else {
			$next = '<div class="nav-next disabled"><span>' . $args['next_text'] . '</span></div>';
		}
	}
	$r = join( '', $pages );
	return $prev . '<div class="nav-page-numbers"><ul class="page-numbers">' . $r . '</ul></div>' . $next;
}


// -----------------------------------------------------------------------------


/**
 * Displays the navigation to pages, when applicable.
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
	);

	$prev = _get_adjacent_page_break_link( $args['prev_text'], true );
	$next = _get_adjacent_page_break_link( $args['next_text'], false );

	$lis = array();
	for ( $i = 1; $i <= $numpages; ++$i ) {
		if ( $i !== $page ) {
			$link  = \wpinc\navi\page_break\get_page_break_link( $i, $post );
			$lis[] = sprintf( '<li><a class="nav-link" href="%s">%d</a></li>', esc_url( $link ), $i );
		} else {
			$lis[] = sprintf( '<li class="current"><span>%d</span></li>', $i );
		}
	}
	$link = sprintf( "<div class=\"nav-numbers\">\n<ul class\"breaks\">\n%s</ul>\n</div>", implode( "\n", $lis ) );
	$temp = array(
		'<nav class="navigation %s" role="navigation" aria-label="%s">',
		"\t" . '<h2 class="screen-reader-text">%s</h2>',
		"\t" . '<div class="nav-links">%s</div>',
		'</nav>',
	);
	$html = sprintf(
		implode( "\n", $temp ) . "\n",
		sanitize_html_class( $args['class'] ),
		esc_html( $args['screen_reader_text'] ),
		esc_html( $args['aria_label'] ),
		implode( "\n", array( $prev, $link, $next ) ),
	);
	return $args['before'] . $html . $args['after'];
}

/**
 * Retrieves the adjacent page break link.
 *
 * @access private
 *
 * @param string $text        Text for anchor link.
 * @param bool   $is_previous Whether to display link to previous or next post.
 * @return string The page break link wrapped in a div element.
 */
function _get_adjacent_page_break_link( string $text, bool $is_previous ): string {
	global $page, $numpages, $post;

	$is_link = $is_previous ? ( 1 !== $page ) : ( $numpages !== $page );
	$cls     = $is_previous ? 'nav-previous' : 'nav-next';
	$idx     = $is_previous ? ( $page - 1 ) : ( $page + 1 );

	if ( $is_link ) {
		return sprintf(
			'<div class="%s"><a href="%s" rel="%s">%s</a></div>',
			$cls,
			esc_url( \wpinc\navi\page_break\get_page_break_link( $idx, $post ) ),
			$is_previous ? 'prev' : 'next',
			esc_html( $text )
		);
	}
	return sprintf(
		'<div class="%s disabled"><span>%s</span></div>',
		$cls,
		esc_html( $text )
	);
}
