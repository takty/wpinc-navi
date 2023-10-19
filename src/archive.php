<?php
/**
 * Archive Filter
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2023-10-19
 */

declare(strict_types=1);

namespace wpinc\navi;

require_once __DIR__ . '/markup.php';

/** phpcs:ignore
 * Displays yearly archive select.
 *
 * phpcs:ignore
 * @param array{
 *     type?        : string,
 *     default_text?: string,
 *     date?        : string,
 *     meta_key?    : string,
 * } $args (Optional) Array of arguments. See get_date_archives() for information on accepted arguments.
 */
function the_yearly_archive_select( array $args = array() ): void {
	$args += array(
		'type'         => 'select',
		'default_text' => __( 'Year' ),
		'date'         => 'yearly',
		'meta_key'     => '',  // phpcs:ignore
	);
	the_date_archives( $args );
}

/** phpcs:ignore
 * Displays taxonomy archive select.
 *
 * phpcs:ignore
 * @param array{
 *     type?        : string,
 *     default_text?: string,
 *     taxonomy?    : string,
 * } $args (Optional) Array of arguments. See get_taxonomy_archives() for information on accepted arguments.
 */
function the_taxonomy_archive_select( array $args = array() ): void {
	$args += array(
		'type'         => 'select',
		'default_text' => __( 'Category' ),
		'taxonomy'     => 'category',
	);
	the_taxonomy_archives( $args );
}


// -----------------------------------------------------------------------------


/** phpcs:ignore
 * Displays date archive links based on type and format.
 *
 * phpcs:ignore
 * @param array{
 *     type?        : string,
 *     default_text?: string,
 *     date?        : string,
 *     meta_key?    : string,
 * } $args (Optional) Array of arguments. See get_date_archives() for information on accepted arguments.
 */
function the_date_archives( array $args = array() ): void {
	echo get_date_archives( $args );  // phpcs:ignore
}

/** phpcs:ignore
 * Retrieves date archive links based on type and format.
 *
 * phpcs:ignore
 * @param array{
 *     before?       : string,
 *     after?        : string,
 *     type?         : string,
 *     link_before?  : string,
 *     link_after?   : string,
 *     do_show_count?: bool,
 *     default_text? : string,
 *     post_type?    : string,
 *     date?         : string,
 *     limit?        : string|int,
 *     order?        : string,
 *     meta_key?     : string,
 * } $args (Optional) Array of type, format, and term query parameters.
 *
 * $args {
 *     (Optional) Array of type, format, and term query parameters.
 *
 *     @type string     'before'        Content to prepend to the output. Default ''.
 *     @type string     'after'         Content to append to the output. Default ''.
 *     @type string     'type'          Link format. Can be 'list' or 'select'. Default 'list'.
 *     @type string     'link_before'   Content to prepend to each link. Default value: ''
 *     @type string     'link_after'    Content to append to each link. Default value: ''
 *     @type bool       'do_show_count' Whether to display the post count alongside the link. Default false.
 *     @type string     'default_text'  Default text used when 'type' is 'select'. Default ''.
 *     @type string     'post_type'     Post type. Default 'post'.
 *     @type string     'date'          Type of archive to retrieve. Accepts 'daily', 'monthly', or 'yearly'. Default 'yearly'.
 *     @type string|int 'limit'         Number of links to limit the query to. Default empty (no limit).
 *     @type string     'order'         Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'. Default 'DESC'.
 *     @type string     'meta_key'      Meta key used instead of post_date.
 * }
 * @return string String of links.
 */
function get_date_archives( array $args = array() ): string {
	$pt = get_post_type();
	$pt = $pt ? $pt : 'post';

	$args += array(
		'before'        => '',
		'after'         => '',
		'type'          => 'list',

		'link_before'   => '',
		'link_after'    => '',
		'do_show_count' => false,
		'default_text'  => '',
		'post_type'     => $pt,

		'date'          => 'yearly',
		'limit'         => '',
		'order'         => 'DESC',
		'meta_key'      => '',  // phpcs:ignore
	);

	$lis = _get_date_link_items( $args['date'], $args['limit'], $args['order'], $args['post_type'], $args['meta_key'] );
	if ( empty( $lis ) ) {
		return '';
	}
	$ls = make_archive_links_markup( $lis, $args['type'], '', $args['link_before'], $args['link_after'], $args['do_show_count'], $args['default_text'] );
	return $args['before'] . $ls . $args['after'];
}

/**
 * Retrieves date archive link data.
 *
 * @access private
 *
 * @global \wpdb $wpdb
 * @global \WP_Locale $wp_locale
 *
 * @param string     $type      Type of archive to retrieve. Accepts 'daily', 'monthly', or 'yearly'.
 * @param string|int $limit     Number of links to limit the query to.
 * @param string     $order     Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'.
 * @param string     $post_type Post type.
 * @param string     $meta_key  Meta key used instead of post_date.
 * @return array{ url?: string, text: string, count?: int, current?: bool }[] Link items.
 */
function _get_date_link_items( string $type, $limit, string $order, string $post_type, string $meta_key = '' ): array {
	global $wpdb, $wp_locale;
	if ( ! empty( $limit ) ) {
		$limit = ' LIMIT ' . absint( $limit );
	}
	$order = strtoupper( $order );
	if ( 'ASC' !== $order ) {
		$order = 'DESC';
	}
	$pto = get_post_type_object( $post_type );
	if ( ! $pto || ! is_post_type_viewable( $pto ) ) {
		return array();
	}
	$post_type = $pto->name;
	$year      = get_query_var( 'year' );
	$monthnum  = get_query_var( 'monthnum' );
	$day       = get_query_var( 'day' );
	$args      = compact( 'type', 'limit', 'order', 'post_type', 'year', 'monthnum', 'day' );

	$where = $wpdb->prepare( "WHERE post_type = %s AND post_status = 'publish'", $post_type );
	$where = apply_filters( 'getarchives_where', $where, $args );
	$join  = empty( $meta_key ) ? '' : "INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' )";
	$join  = apply_filters( 'getarchives_join', $join, $args );

	$column = empty( $meta_key ) ? 'post_date' : 'meta_value';
	if ( 'yearly' === $type ) {
		$query = "SELECT YEAR($column) AS `year`, count(ID) as `count` FROM $wpdb->posts $join $where GROUP BY YEAR($column) ORDER BY $column $order $limit";
	} elseif ( 'monthly' === $type ) {
		$query = "SELECT YEAR($column) AS `year`, MONTH($column) AS `month`, count(ID) as `count` FROM $wpdb->posts $join $where GROUP BY YEAR($column), MONTH($column) ORDER BY $column $order $limit";
	} elseif ( 'daily' === $type ) {
		$query = "SELECT YEAR($column) AS `year`, MONTH($column) AS `month`, DAYOFMONTH($column) AS `dayofmonth`, count(ID) as `count` FROM $wpdb->posts $join $where GROUP BY YEAR($column), MONTH($column), DAYOFMONTH($column) ORDER BY $column $order $limit";
	} else {
		return array();
	}

	$last = wp_cache_get_last_changed( 'posts' );
	$key  = md5( $query );
	$key  = "get_date_archives:$key:$last";
	$rs   = wp_cache_get( $key, 'posts' );
	if ( ! $rs ) {
		$rs = $wpdb->get_results( $query );  // phpcs:ignore
		wp_cache_set( $key, $rs, 'posts' );
	}
	if ( ! $rs ) {
		return array();
	}

	$lis = array();
	if ( 'yearly' === $type ) {
		foreach ( (array) $rs as $r ) {
			$url = get_year_link( $r->year );
			if ( 'post' !== $post_type ) {
				$url = add_query_arg( 'post_type', $post_type, $url );
			}
			$text    = sprintf( '%d', $r->year );
			$count   = (int) $r->count;
			$current = is_archive() &&
				is_numeric( $year ) && is_numeric( $r->year ) &&
				(string) $year === (string) $r->year;
			$lis[]   = compact( 'url', 'text', 'count', 'current' );
		}
	} elseif ( 'monthly' === $type ) {
		foreach ( (array) $rs as $r ) {
			$url = get_month_link( $r->year, $r->month );
			if ( 'post' !== $post_type ) {
				$url = add_query_arg( 'post_type', $post_type, $url );
			}
			/* translators: 1: month name, 2: 4-digit year */
			$text    = sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $r->month ), $r->year );
			$count   = (int) $r->count;
			$current = is_archive() &&
				is_numeric( $year ) && is_numeric( $r->year ) &&
				is_numeric( $monthnum ) && is_numeric( $r->month ) &&
				(string) $year === (string) $r->year &&
				(string) $monthnum === (string) $r->month;
			$lis[]   = compact( 'url', 'text', 'count', 'current' );
		}
	} else {  // If $type is 'daily', that is obvious.
		foreach ( (array) $rs as $r ) {
			$url = get_day_link( $r->year, $r->month, $r->dayofmonth );
			if ( 'post' !== $post_type ) {
				$url = add_query_arg( 'post_type', $post_type, $url );
			}
			$text = sprintf( '%1$d-%2$02d-%3$02d 00:00:00', $r->year, $r->month, $r->dayofmonth );
			$df   = get_option( 'date_format' );
			if ( is_string( $df ) ) {
				$text = (string) mysql2date( $df, $text );
			}
			$count   = (int) $r->count;
			$current = is_archive() &&
				is_numeric( $year ) && is_numeric( $r->year ) &&
				is_numeric( $monthnum ) && is_numeric( $r->month ) &&
				is_numeric( $day ) && is_numeric( $r->day ) &&
				(string) $year === (string) $r->year &&
				(string) $monthnum === (string) $r->month &&
				(string) $day === (string) $r->dayofmonth;
			$lis[]   = compact( 'url', 'text', 'count', 'current' );
		}
	}
	return $lis;
}


// -----------------------------------------------------------------------------


/** phpcs:ignore
 * Displays taxonomy archive links based on type and format.
 *
 * phpcs:ignore
 * @param array{
 *     type?        : string,
 *     default_text?: string,
 *     taxonomy?    : string,
 * } $args (Optional) Array of arguments. See get_taxonomy_archives() for information on accepted arguments.
 */
function the_taxonomy_archives( array $args = array() ): void {
	echo get_taxonomy_archives( $args );  // phpcs:ignore
}

/** phpcs:ignore
 * Retrieves taxonomy archive links based on type and format.
 *
 * phpcs:ignore
 * @param array{
 *     before?       : string,
 *     after?        : string,
 *     type?         : string,
 *     link_before?  : string,
 *     link_after?   : string,
 *     do_show_count?: bool,
 *     default_text? : string,
 *     post_type?    : string,
 *     taxonomy?     : string,
 *     limit?        : string|int,
 *     order?        : string,
 *     hierarchical? : bool,
 *     parent?       : int,
 * } $args (Optional) Array of type, format, and term query parameters.
 *
 * $args {
 *     (Optional) Array of type, format, and term query parameters.
 *
 *     @type string     'before'        Content to prepend to the output. Default ''.
 *     @type string     'after'         Content to append to the output. Default ''.
 *     @type string     'type'          Link format. Can be 'list' or 'select'. Default 'list'.
 *     @type string     'link_before'   Content to prepend to each link. Default value: ''
 *     @type string     'link_after'    Content to append to each link. Default value: ''
 *     @type bool       'do_show_count' Whether to display the post count alongside the link. Default false.
 *     @type string     'default_text'  Default text used when 'type' is 'select'. Default ''.
 *     @type string     'post_type'     Post type. Default ''.
 *     @type string     'taxonomy'      Taxonomy name to which results should be limited.
 *     @type string|int 'limit'         Number of links to limit the query to. Default empty (no limit).
 *     @type string     'order'         Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'. Default 'DESC'.
 *     @type bool       'hierarchical'  Whether to include terms that have non-empty descendants. Default false.
 *     @type int        'parent'        Parent term ID to retrieve direct-child terms of. Default 0.
 * }
 * @return string String of links.
 */
function get_taxonomy_archives( array $args = array() ): string {
	$pt = get_post_type();
	$pt = $pt ? $pt : 'post';

	$args += array(
		'before'        => '',
		'after'         => '',
		'type'          => 'list',

		'link_before'   => '',
		'link_after'    => '',
		'do_show_count' => false,
		'default_text'  => '',
		'post_type'     => $pt,

		'taxonomy'      => 'category',
		'limit'         => '',
		'order'         => 'ASC',
		'hierarchical'  => false,
		'parent'        => 0,
	);
	if ( ! taxonomy_exists( $args['taxonomy'] ) ) {
		return '';
	}

	$gt_args = $args;
	foreach ( array( 'before', 'after', 'type', 'link_before', 'link_after', 'do_show_count', 'post_type' ) as $key ) {
		unset( $gt_args[ $key ] );
	}

	$lis = _get_taxonomy_link_items( $args['taxonomy'], $args['limit'], $args['order'], $args['hierarchical'], $args['post_type'] );
	$lis = _sort_taxonomy_link_items( $lis, $args['hierarchical'], $args['post_type'], $gt_args );
	if ( empty( $lis ) ) {
		return '';
	}
	$ls = make_archive_links_markup( $lis, $args['type'], '', $args['link_before'], $args['link_after'], $args['do_show_count'], $args['default_text'] );
	return $args['before'] . $ls . $args['after'];
}

/**
 * Retrieves taxonomy archive link items.
 *
 * @access private
 *
 * @param string     $taxonomy     Taxonomy name to which results should be limited.
 * @param string|int $limit        Number of links to limit the query to.
 * @param string     $order        Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'.
 * @param bool       $hierarchical Whether to include terms that have non-empty descendants. Default false.
 * @param string     $post_type    Post type.
 * @return array<string, array{ url?: string, text: string, count?: int, current?: bool }> Link items.
 */
function _get_taxonomy_link_items( string $taxonomy, $limit, string $order, bool $hierarchical, string $post_type ): array {
	global $wpdb;
	if ( ! empty( $limit ) ) {
		$limit = ' LIMIT ' . absint( $limit );
	}
	$order = strtoupper( $order );
	if ( 'ASC' !== $order ) {
		$order = 'DESC';
	}
	if ( ! empty( $post_type ) ) {
		$pto = get_post_type_object( $post_type );
		if ( ! $pto || ! is_post_type_viewable( $pto ) ) {
			return array();
		}
		$post_type = $pto->name;
	}
	$term = get_query_var( 'term' );
	if ( ! is_string( $term ) ) {
		$term = null;
	}
	$type = 'term';
	$args = compact( 'type', 'limit', 'order', 'post_type', 'term' );

	if ( empty( $post_type ) ) {
		$where = $wpdb->prepare( "WHERE post_status = 'publish' AND tt.taxonomy = %s", $taxonomy );
	} else {
		$where = $wpdb->prepare( "WHERE post_type = %s AND post_status = 'publish' AND tt.taxonomy = %s", $post_type, $taxonomy );
	}
	$where = apply_filters( 'getarchives_where', $where, $args );
	$join  = "INNER JOIN $wpdb->term_relationships AS tr ON $wpdb->posts.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
	$join  = apply_filters( 'getarchives_join', $join, $args );

	$query = "SELECT tr.term_taxonomy_id as `tt_id`, count(ID) as `count` FROM $wpdb->posts $join $where GROUP BY tr.term_taxonomy_id ORDER BY tr.term_taxonomy_id $order $limit";

	$last = wp_cache_get_last_changed( 'posts' );
	$key  = md5( $query );
	$key  = "get_taxonomy_archives:$key:$last";
	$rs   = wp_cache_get( $key, 'posts' );
	if ( ! $rs ) {
		$rs = $wpdb->get_results( $query );  // phpcs:ignore
		wp_cache_set( $key, $rs, 'posts' );
	}
	if ( ! $rs ) {
		return array();
	}

	$lis = array();
	foreach ( (array) $rs as $r ) {
		$t = get_term_by( 'term_taxonomy_id', (int) $r->tt_id, $taxonomy );
		if ( $t instanceof \WP_Term ) {
			$it = _create_taxonomy_link_item( $t, $hierarchical, $post_type, $r->count, is_tax() ? $term : null );

			$lis[ $t->slug ] = $it;
		}
	}
	return $lis;
}

/** phpcs:ignore
 * Sorts taxonomy archive link items.
 *
 * @access private
 * @psalm-suppress ArgumentTypeCoercion
 * phpcs:ignore
 * @param array<
 *     string,
 *     array{ url?: string, text: string, count?: int, current?: bool }
 * > $items Link items.
 * @param bool                 $hierarchical Whether to include terms that have non-empty descendants.
 * @param string               $post_type    Post type.
 * @param array<string, mixed> $query_args   Query arguments for get_terms.
 * @return list<array{ url?: string, text: string, count?: int, current?: bool }> Link items.
 */
function _sort_taxonomy_link_items( array $items, bool $hierarchical, string $post_type, array $query_args ): array {
	$ret = array();

	if ( $hierarchical ) {
		$tx = $query_args['taxonomy'];
		if ( ! is_string( $tx ) ) {
			wp_die( '$query_args[\'taxonomy\'] must be a taxonomy name.' );
		}
		$ids = array();
		foreach ( $items as $slug => $_it ) {
			$t = get_term_by( 'slug', $slug, $tx );
			if ( $t instanceof \WP_Term ) {
				$ids = array_merge( $ids, get_ancestors( $t->term_id, $tx ) );
			}
		}
		$ids = array_unique( $ids );

		$qvt = is_tax() ? get_query_var( 'term' ) : null;
		if ( ! is_string( $qvt ) ) {
			$qvt = null;
		}
		foreach ( $ids as $id ) {
			$t = get_term_by( 'term_id', $id, $tx );
			if ( $t instanceof \WP_Term ) {
				$it = _create_taxonomy_link_item( $t, $hierarchical, $post_type, 0, $qvt );

				$items[ $t->slug ] = $it;
			}
		}
		$query_args['parent'] = '';
	}
	$ts = get_terms( $query_args );  // @phpstan-ignore-line
	if ( is_array( $ts ) ) {
		foreach ( $ts as $t ) {
			if ( $t instanceof \WP_Term ) {
				if ( ! isset( $items[ $t->slug ] ) ) {
					continue;
				}
				$ret[] = $items[ $t->slug ];
			}
		}
	}
	return $ret;
}

/**
 * Creates a taxonomy link item.
 *
 * @param \WP_Term    $t            Term.
 * @param bool        $hierarchical Whether to include terms that have non-empty descendants.
 * @param string      $post_type    Post type.
 * @param int         $count        Count of posts.
 * @param string|null $slug         Slug of current term.
 * @return array{ url: string, text: string, count: int, current: bool } An item.
 */
function _create_taxonomy_link_item( \WP_Term $t, bool $hierarchical, string $post_type, int $count = 0, ?string $slug = null ): array {
	$url = get_term_link( $t );
	if ( ! is_string( $url ) ) {
		$url = '';
	}
	if ( $post_type && 'post' !== $post_type ) {
		$url = add_query_arg( 'post_type', $post_type, $url );
	}
	$text = $t->name;
	if ( $hierarchical && $t->parent ) {
		$line = str_repeat( '—', _get_term_depth( $t ) );
		$text = "$line $text";
	}
	$current = $slug === $t->slug;
	return compact( 'url', 'text', 'count', 'current' );
}

/**
 * Gets depth of the term.
 *
 * @param \WP_Term $t Term.
 * @return int Depth.
 */
function _get_term_depth( \WP_Term $t ): int {
	$d = 0;
	while ( $t instanceof \WP_Term && $t->parent ) {
		$t = get_term_by( 'term_id', $t->parent, $t->taxonomy );
		++$d;
	}
	return $d;
}
