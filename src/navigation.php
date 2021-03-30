<?php
/**
 * Navigation Tags
 *
 * @author Takuto Yanagida
 * @version 2021-03-30
 */

namespace wpinc\compass;

/**
 *
 */
function the_post_navigation( array $args = array() ) {
	echo get_the_post_navigation( $args );  // phpcs:disable
}

/**
 *
 */
function the_posts_pagination( array $args = array() ) {
	echo get_the_posts_pagination( $args );  // phpcs:disable
}

/**
 *
 */
function the_child_page_navigation( array $args = array() ) {
	echo get_the_child_page_navigation( $args );  // phpcs:disable
}

/**
 *
 */
function the_sibling_page_navigation( array $args = array() ) {
	echo get_the_sibling_page_navigation( $args );  // phpcs:disable
}

/**
 *
 */
function the_page_break_navigation( array $args = array() ) {
	echo get_the_page_break_navigation( $args );  // phpcs:disable
}


// -----------------------------------------------------------------------------


function get_the_post_navigation( $args = array() ) {
	$args = array_merge(
		array(
			'before'             => '',
			'after'              => '',
			'prev_text'          => '%title',
			'next_text'          => '%title',
			'list_text'          => 'List',
			'in_same_term'       => false,
			'excluded_terms'     => '',
			'taxonomy'           => 'category',
			'screen_reader_text' => __( 'Post navigation' ),
			'has_list_link'      => false,
			'link_list_pos'      => 'center',
		),
		$args
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

function get_the_posts_pagination( $args = array() ) {
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
	$links = paginate_links( $args );
	if ( $links ) {
		return $args['before'] . _navigation_markup( $links, $args['class'], 'pagination', $args['screen_reader_text'], $args['aria_label'] ) . $args['after'];
	}
	return '';
}

function paginate_links( $args = array() ) {
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
		return;
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


function get_the_child_page_navigation( $args = array() ) {
	$ps = get_child_pages( false, $args );
	if ( isset( $args['hide_page_with_thumbnail'] ) && $args['hide_page_with_thumbnail'] ) {
		$ps = array_values(
			array_filter(
				$ps,
				function ( $p ) {
					return ! has_post_thumbnail( $p->ID );
				}
			)
		);
	}
	if ( count( $ps ) === 0 ) {
		return;
	}
	$cls = isset( $args['class'] ) ? ( ' ' . esc_attr( $args['class'] ) ) : '';

	ob_start();
	?>
	<nav class="navigation child-page-navigation<?php echo esc_attr( $cls ); ?>">
		<div class="nav-parent current"><span><?php the_title(); ?></span></div>
		<div class="nav-children">
			<ul class="nav-link-list">
				<?php foreach ( $ps as $p ) : ?>
					<?php the_post_list_item( $p, 'nav-link' ); ?>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>
	<?php
	return ob_get_clean();
}

function get_the_sibling_page_navigation( $args = array() ) {
	$ps = get_sibling_pages( false, $args );
	if ( isset( $args['hide_page_with_thumbnail'] ) && $args['hide_page_with_thumbnail'] ) {
		$ps = array_values(
			array_filter(
				$ps,
				function ( $p ) {
					return ! has_post_thumbnail( $p->ID );
				}
			)
		);
	}
	if ( count( $ps ) === 0 ) {
		return;
	}
	$cls = isset( $args['class'] ) ? ( ' ' . esc_attr( $args['class'] ) ) : '';

	global $post;
	$pid = $post->post_parent;
	if ( $pid ) {
		$e_href  = get_permalink( $pid );
		$e_title = get_the_title( $pid );
	}
	ob_start();
	?>
	<nav class="navigation sibling-page-navigation<?php echo esc_attr( $cls ); ?>">
	<?php if ( $pid ) : ?>
		<div class="nav-parent"><a class="nav-link" href="<?php echo esc_attr( $e_href ); ?>"><?php echo esc_html( $e_title ); ?></a></div>
	<?php endif; ?>
		<div class="nav-siblings">
			<ul class="nav-link-list">
				<?php foreach ( $ps as $p ) : ?>
					<?php the_post_list_item( $p, 'nav-link', '', $post->ID === $p->ID ? 'current' : false ); ?>
				<?php endforeach; ?>
			</ul>
		</div>
	</nav>
	<?php
	return ob_get_clean();
}


// -----------------------------------------------------------------------------


function get_the_page_break_navigation( array $args = array() ) {
	$args += array(
		'before' => '',
		'after'  => '',
	);

	global $page, $numpages, $multipage, $post;
	if ( ! $multipage ) {
		return;
	}
	$output = '<nav class="navigation page-break-navigation"><div class="nav-links">';
	for ( $i = 1; $i <= $numpages; ++$i ) {
		if ( $i !== $page ) {
			$_url = esc_url( \wpinc\compass\page_break\get_page_break_link( $i, $post ) );

			$output .= "<a class=\"nav-page-break-link\" href=\"$_url\">$i</a>";
		} else {
			$output .= "<span class=\"nav-page-break-current\">$i</span>";
		}
	}
	$output .= '</div></nav>';
	return $output;
}
