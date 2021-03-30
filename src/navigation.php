<?php
/**
 * Navigation Tags
 *
 * @author Takuto Yanagida
 * @version 2021-03-26
 */

namespace st;

require_once __DIR__ . '/../util/text.php';
require_once __DIR__ . '/../util/url.php';


function the_yearly_archive_select( $post_type = 'post', $default_title = 'Year', $args = array(), $meta_key = false ) {
	$args = array_merge(
		array(
			'post_type' => $post_type,
			'type'      => 'yearly',
			'format'    => 'option',
		),
		$args
	);
	?>
	<select onchange="document.location.href = this.value;">
		<option value="#"><?php echo esc_html( $default_title ); ?></option>
	<?php
	if ( false === $meta_key ) {
		wp_get_archives( $args );
	} else {
		get_custom_archives( $meta_key, $args );
	}
	?>
	</select>
	<?php
}

function the_taxonomy_archive_select( $taxonomy, $default_title = 'Category', $args = array() ) {
	?>
	<select onchange="document.location.href = this.value;">
		<option value="#"><?php echo esc_html( $default_title ); ?></option>
		<?php the_taxonomy_archive_option( $taxonomy, $args ); ?>
	</select>
	<?php
}

function the_taxonomy_archive_option( $taxonomy, $args = array() ) {
	$args  = array_merge(
		array(
			'hide_empty' => false,
			'parent'     => 0,
		),
		$args
	);
	$terms = get_terms( $taxonomy, $args );

	foreach ( $terms as $t ) {
		echo '<option value="' . esc_attr( get_term_link( $t ) ) . '">' . esc_html( $t->name ) . '</option>';
		$args['parent'] = $t->term_id;

		$cts = get_terms( $taxonomy, $args );
		foreach ( $cts as $ct ) {
			echo '<option value="' . esc_attr( get_term_link( $ct ) ) . '">' . '— ' . esc_html( $ct->name ) . '</option>';
		}
	}
}


// -----------------------------------------------------------------------------


function get_custom_archives( $meta_key, $args = array() ) {
	global $wpdb, $wp_locale;
	$r = array_merge(
		array(
			'type'            => 'monthly',
			'limit'           => '',
			'format'          => 'html',
			'before'          => '',
			'after'           => '',
			'show_post_count' => false,
			'echo'            => 1,
			'order'           => 'DESC',
			'post_type'       => 'post',
		),
		$args
	);

	$post_type_object = get_post_type_object( $r['post_type'] );
	if ( ! is_post_type_viewable( $post_type_object ) ) {
		return;
	}
	if ( ! empty( $r['limit'] ) ) {
		$r['limit'] = absint( $r['limit'] );
		$r['limit'] = ' LIMIT ' . $r['limit'];
	}

	$order = strtoupper( $r['order'] );
	if ( 'ASC' !== $order ) {
		$order = 'DESC';
	}
	$where = $wpdb->prepare( "WHERE post_type = %s AND post_status = 'publish'", $r['post_type'] );
	$where = apply_filters( 'getarchives_where', $where, $r );
	$join  = "INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' )";
	$join  = apply_filters( 'getarchives_join', $join, $r );

	$output       = '';
	$last_changed = wp_cache_get_last_changed( 'posts' );
	$limit        = $r['limit'];

	if ( 'monthly' === $r['type'] ) {
		$query = "SELECT YEAR(meta_value) AS `year`, MONTH(meta_value) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(meta_value), MONTH(meta_value) ORDER BY meta_value $order $limit";
		$key   = md5( $query );
		$key   = "wp_get_archives:$key:$last_changed";

		$results = wp_cache_get( $key, 'posts' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'posts' );
		}
		if ( $results ) {
			$after = $r['after'];
			foreach ( (array) $results as $result ) {
				$url = get_month_link( $result->year, $result->month );
				if ( 'post' !== $r['post_type'] ) {
					$url = add_query_arg( 'post_type', $r['post_type'], $url );
				}
				/* translators: 1: month name, 2: 4-digit year */
				$text = sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $result->month ), $result->year );
				if ( $r['show_post_count'] ) {
					$r['after'] = '&nbsp;(' . $result->posts . ')' . $after;
				}
				$output .= get_archives_link( $url, $text, $r['format'], $r['before'], $r['after'] );
			}
		}
	} elseif ( 'yearly' === $r['type'] ) {
		$query = "SELECT YEAR(meta_value) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(meta_value) ORDER BY meta_value $order $limit";
		$key   = md5( $query );
		$key   = "wp_get_archives:$key:$last_changed";

		$results = wp_cache_get( $key, 'posts' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );
			wp_cache_set( $key, $results, 'posts' );
		}
		if ( $results ) {
			$after = $r['after'];
			foreach ( (array) $results as $result ) {
				$url = get_year_link( $result->year );
				if ( 'post' !== $r['post_type'] ) {
					$url = add_query_arg( 'post_type', $r['post_type'], $url );
				}
				$text = sprintf( '%d', $result->year );
				if ( $r['show_post_count'] ) {
					$r['after'] = '&nbsp;(' . $result->posts . ')' . $after;
				}
				$output .= get_archives_link( $url, $text, $r['format'], $r['before'], $r['after'] );
			}
		}
	}
	if ( $r['echo'] ) {
		echo $output;
	} else {
		return $output;
	}
}


// -----------------------------------------------------------------------------


function the_post_navigation_with_list_link( $args = array() ) {
	$args = array_merge( array( 'has_list_link' => true ), $args );
	echo get_the_post_navigation( $args );
}

function get_the_post_navigation_with_list_link( $args = array() ) {
	$args = array_merge( array( 'has_list_link' => true ), $args );
	return get_the_post_navigation( $args );
}

function the_post_navigation( $args = array() ) {
	echo get_the_post_navigation( $args );
}

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


// -----------------------------------------------------------------------------


function the_posts_pagination( $args = array() ) {
	echo get_the_posts_pagination( $args );
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


function the_child_page_navigation( $args = array() ) {
	echo get_the_child_page_navigation( $args );
}

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

function the_sibling_page_navigation( $args = array() ) {
	echo get_the_sibling_page_navigation( $args );
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
