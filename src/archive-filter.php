<?php
/**
 * Archive Filter
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-10
 */

namespace wpinc\navi;

require_once __DIR__ . '/markup.php';

/**
 * Display yearly archive select.
 *
 * @param array $args (Optional) Array of arguments. See get_date_archives() for information on accepted arguments.
 */
function the_yearly_archive_select( array $args = array() ) {
	$js = 'document.location.href=this.value;';
	$dt = $args['default_label'] ?? __( 'Year' );

	$args += array(
		'before'    => "<select onchange=\"$js\">\n<option value=\"#\">" . esc_html( $dt ) . "</option>\n",
		'after'     => '</select>',
		'post_type' => 'post',
		'date'      => 'yearly',
		'type'      => 'option',
		'meta_key'  => '',  // phpcs:ignore
	);
	the_date_archives( $args );
}

/**
 * Display taxonomy archive select.
 *
 * @param array $args (Optional) Array of arguments. See get_taxonomy_archives() for information on accepted arguments.
 */
function the_taxonomy_archive_select( array $args = array() ) {
	$js = 'document.location.href=this.value;';
	$dt = $args['default_label'] ?? __( 'Category' );

	$args += array(
		'before'    => "<select onchange=\"$js\">\n<option value=\"#\">" . esc_html( $dt ) . "</option>\n",
		'after'     => '</select>',
		'post_type' => 'post',
		'taxonomy'  => 'category',
		'type'      => 'option',
	);
	the_taxonomy_archives( $args );
}


// -----------------------------------------------------------------------------


/**
 * Display date archive links based on type and format.
 *
 * @param array $args (Optional) Array of arguments. See get_date_archives() for information on accepted arguments.
 */
function the_date_archives( array $args = array() ) {
	echo get_date_archives( $args );  // phpcs:ignore
}

/**
 * Retrieves date archive links based on type and format.
 *
 * @param array $args {
 *     (Optional) Array of type, format, and term query parameters.
 *
 *     @type string     'before'          Content to prepend to the output. Default ''.
 *     @type string     'after'           Content to append to the output. Default ''.
 *     @type string     'type'          Can be 'link', 'option', 'html', or custom. Default value: 'html'
 *     @type string     'item_before'     Content to prepend to each link. Default value: ''
 *     @type string     'item_after'      Content to append to each link. Default value: ''
 *     @type bool       'show_post_count' Whether to display the post count alongside the link. Default false.
 *     @type string     'date'            Type of archive to retrieve. Accepts 'daily', 'monthly', or 'yearly'. Default 'monthly'.
 *     @type string|int 'limit'           Number of links to limit the query to. Default empty (no limit).
 *     @type string     'order'           Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'. Default 'DESC'.
 *     @type string     'post_type'       Post type. Default 'post'.
 *     @type string     'year'            Year. Default current year.
 *     @type string     'monthnum'        Month number. Default current month number.
 *     @type string     'day'             Day. Default current day.
 *     @type string     'meta_key'        Meta key used instead of post_date.
 * }
 * @return array String of links.
 */
function get_date_archives( array $args = array() ): string {
	global $wpdb, $wp_locale;
	$args += array(
		'before'          => '',
		'after'           => '',

		'type'            => 'html',
		'class'           => '',
		'item_before'     => '',
		'item_after'      => '',
		'show_post_count' => false,
		'default_label'   => '',

		'date'            => 'monthly',
		'limit'           => '',
		'order'           => 'DESC',
		'post_type'       => 'post',
		'year'            => get_query_var( 'year' ),
		'monthnum'        => get_query_var( 'monthnum' ),
		'day'             => get_query_var( 'day' ),
		'meta_key'        => '',  // phpcs:ignore
	);
	$items = get_date_archive_links( $args );
	$alm   = make_archive_links_markup( $items, $args['type'], $args['class'], $args['item_before'], $args['item_after'], $args['show_post_count'], $args['default_label'] );
	return $args['before'] . $alm . $args['after'];
}

/**
 * Retrieves date archive link data.
 *
 * @param array $args {
 *     (Optional) Array of type, format, and date query parameters.
 *
 *     @type string     'date'            Type of archive to retrieve. Accepts 'daily', 'monthly', or 'yearly'. Default 'monthly'.
 *     @type string|int 'limit'           Number of links to limit the query to. Default empty (no limit).
 *     @type string     'order'           Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'. Default 'DESC'.
 *     @type string     'post_type'       Post type. Default 'post'.
 *     @type string     'year'            Year. Default current year.
 *     @type string     'monthnum'        Month number. Default current month number.
 *     @type string     'day'             Day. Default current day.
 *     @type string     'meta_key'        Meta key used instead of post_date.
 * }
 * @return array Link data.
 */
function get_date_archive_links( array $args = array() ): array {
	global $wpdb, $wp_locale;
	$args += array(
		'date'      => 'monthly',
		'limit'     => '',
		'order'     => 'DESC',
		'post_type' => 'post',
		'year'      => get_query_var( 'year' ),
		'monthnum'  => get_query_var( 'monthnum' ),
		'day'       => get_query_var( 'day' ),
		'meta_key'  => '',  // phpcs:ignore
	);

	$meta_key = $args['meta_key'];
	$column   = empty( $meta_key ) ? 'post_date' : 'meta_value';

	$post_type_object = get_post_type_object( $args['post_type'] );
	if ( ! is_post_type_viewable( $post_type_object ) ) {
		return array();
	}
	$args['post_type'] = $post_type_object->name;

	if ( ! empty( $args['limit'] ) ) {
		$args['limit'] = absint( $args['limit'] );
		$args['limit'] = ' LIMIT ' . $args['limit'];
	}
	$order = strtoupper( $args['order'] );
	if ( 'ASC' !== $order ) {
		$order = 'DESC';
	}

	$where = $wpdb->prepare( "WHERE post_type = %s AND post_status = 'publish'", $args['post_type'] );
	$where = apply_filters( 'getarchives_where', $where, $args );
	$join  = empty( $meta_key ) ? '' : "INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' )";
	$join  = apply_filters( 'getarchives_join', $join, $args );

	$links        = array();
	$last_changed = wp_cache_get_last_changed( 'posts' );
	$limit        = $args['limit'];

	if ( 'yearly' === $args['date'] ) {
		$query = "SELECT YEAR($column) AS `year`, count(ID) as `count` FROM $wpdb->posts $join $where GROUP BY YEAR($column) ORDER BY $column $order $limit";
		$key   = md5( $query );
		$key   = "get_date_archives:$key:$last_changed";
		$rs    = wp_cache_get( $key, 'posts' );
		if ( ! $rs ) {
			$rs = $wpdb->get_results( $query );  // phpcs:ignore
			wp_cache_set( $key, $rs, 'posts' );
		}
		if ( $rs ) {
			$after = $args['item_after'];
			foreach ( (array) $rs as $r ) {
				$url = get_year_link( $r->year );
				if ( 'post' !== $args['post_type'] ) {
					$url = add_query_arg( 'post_type', $args['post_type'], $url );
				}
				$text    = sprintf( '%d', $r->year );
				$count   = $r->count;
				$current = is_archive() && (string) $args['year'] === $r->year;
				$links[] = compact( 'url', 'text', 'count', 'current' );
			}
		}
	} elseif ( 'monthly' === $args['date'] ) {
		$query = "SELECT YEAR($column) AS `year`, MONTH($column) AS `month`, count(ID) as `count` FROM $wpdb->posts $join $where GROUP BY YEAR($column), MONTH($column) ORDER BY $column $order $limit";
		$key   = md5( $query );
		$key   = "get_date_archives:$key:$last_changed";
		$rs    = wp_cache_get( $key, 'posts' );
		if ( ! $rs ) {
			$rs = $wpdb->get_results( $query );  // phpcs:ignore
			wp_cache_set( $key, $rs, 'posts' );
		}
		if ( $rs ) {
			$after = $args['item_after'];
			foreach ( (array) $rs as $r ) {
				$url = get_month_link( $r->year, $r->month );
				if ( 'post' !== $args['post_type'] ) {
					$url = add_query_arg( 'post_type', $args['post_type'], $url );
				}
				/* translators: 1: month name, 2: 4-digit year */
				$text    = sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $r->month ), $r->year );
				$count   = $r->count;
				$current = is_archive() && (string) $args['year'] === $r->year && (string) $args['monthnum'] === $r->month;
				$links[] = compact( 'url', 'text', 'count', 'current' );
			}
		}
	} elseif ( 'daily' === $args['date'] ) {
		$query = "SELECT YEAR($column) AS `year`, MONTH($column) AS `month`, DAYOFMONTH($column) AS `dayofmonth`, count(ID) as `count` FROM $wpdb->posts $join $where GROUP BY YEAR($column), MONTH($column), DAYOFMONTH($column) ORDER BY $column $order $limit";
		$key   = md5( $query );
		$key   = "get_date_archives:$key:$last_changed";
		$rs    = wp_cache_get( $key, 'posts' );
		if ( ! $rs ) {
			$rs = $wpdb->get_results( $query );  // phpcs:ignore
			wp_cache_set( $key, $rs, 'posts' );
		}
		if ( $rs ) {
			$after = $args['item_after'];
			foreach ( (array) $rs as $r ) {
				$url = get_day_link( $r->year, $r->month, $r->dayofmonth );
				if ( 'post' !== $args['post_type'] ) {
					$url = add_query_arg( 'post_type', $args['post_type'], $url );
				}
				$date = sprintf( '%1$d-%2$02d-%3$02d 00:00:00', $r->year, $r->month, $r->dayofmonth );

				$text    = mysql2date( get_option( 'date_format' ), $date );
				$count   = $r->count;
				$current = is_archive() && (string) $args['year'] === $r->year && (string) $args['monthnum'] === $r->month && (string) $args['day'] === $r->dayofmonth;
				$links[] = compact( 'url', 'text', 'count', 'current' );
			}
		}
	}
	return $links;
}


// -----------------------------------------------------------------------------


/**
 * Display taxonomy archive links based on type and format.
 *
 * @param array $args (Optional) Array of arguments. See get_taxonomy_archives() for information on accepted arguments.
 */
function the_taxonomy_archives( array $args = array() ) {
	echo get_date_archives( $args );  // phpcs:ignore
}

/**
 * Retrieves taxonomy archive links based on type and format.
 *
 * @param array $args {
 *     (Optional) Array of type, format, and term query parameters.
 *
 *     @type string     'taxonomy'        Taxonomy name to which results should be limited.
 *     @type int        'parent'          Parent term ID to retrieve direct-child terms of. Default 0.
 *     @type bool       'hierarchical'    Whether to include terms that have non-empty descendants (even if $hide_empty is set to true). Default false.
 *     @type string     'before'          Content to prepend to the output. Default ''.
 *     @type string     'after'           Content to append to the output. Default ''.
 *     @type string     'type'          Can be 'link', 'option', 'html', or custom. Default value: 'html'
 *     @type string     'item_before'     Content to prepend to each link. Default value: ''
 *     @type string     'item_after'      Content to append to each link. Default value: ''
 *     @type bool       'show_post_count' Whether to display the post count alongside the link. Default false.
 *     @type string|int 'limit'           Number of links to limit the query to. Default empty (no limit).
 *     @type string     'order'           Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'. Default 'DESC'.
 *     @type string     'post_type'       Post type. Default 'post'.
 *     @type string     'term'            Term slug. Default current term.
 * }
 * @return array String of links.
 */
function get_taxonomy_archives( $args = array() ): string {
	$args += array(
		'taxonomy'        => 'category',
		'parent'          => 0,
		'hierarchical'    => false,

		'before'          => '',
		'after'           => '',
		'type'            => 'html',
		'item_before'     => '',
		'item_after'      => '',
		'show_post_count' => false,

		'limit'           => '',
		'order'           => 'DESC',
		'post_type'       => 'post',
		'term'            => get_query_var( 'term' ),
	);

	$gt_args = $args;
	foreach ( array( 'type', 'item_before', 'item_after', 'show_post_count', 'limit', 'order', 'post_type', 'term' ) as $key ) {
		unset( $gt_args[ $key ] );
	}

	$links = get_taxonomy_archive_links( $args );
	$lms   = '';

	foreach ( get_terms( $gt_args ) as $t ) {
		if ( ! isset( $links[ $t->slug ] ) ) {
			continue;
		}
		$link = $links[ $t->slug ];
		list( 'url' => $url, 'text' => $text, 'current' => $cur, 'count' => $count ) = $link;

		$after = $args['show_post_count'] ? "&nbsp;($count){$args['item_after']}" : $args['item_after'];
		$lms  .= make_archive_link_markup( $url, $text, $cur, $args['type'], $args['item_before'], $after );

		if ( $args['hierarchical'] ) {
			$gt_args['parent'] = $t->term_id;

			foreach ( get_terms( $gt_args ) as $ct ) {
				if ( ! isset( $links[ $ct->slug ] ) ) {
					continue;
				}
				$clink = $links[ $ct->slug ];
				list( 'url' => $url, 'text' => $text, 'current' => $cur, 'count' => $count ) = $clink;

				$after = $args['show_post_count'] ? "&nbsp;($count){$args['item_after']}" : $args['item_after'];
				$lms  .= make_archive_link_markup( $url, '— ' . $text, $cur, $args['type'], $args['item_before'], $after );
			}
		}
	}
	return $args['before'] . $lms . $args['after'];
}

/**
 * Retrieves taxonomy archive link data.
 *
 * @param array $args {
 *     (Optional) Array of type, format, and term query parameters.
 *
 *     @type string     'taxonomy'        Taxonomy name to which results should be limited.
 *     @type bool       'hide_empty'      Whether to hide terms not assigned to any posts. Default true.
 *     @type int        'parent'          Parent term ID to retrieve direct-child terms of. Default 0.
 *     @type bool       'hierarchical'    Whether to include terms that have non-empty descendants (even if $hide_empty is set to true). Default false.
 *     @type string     'type'          Can be 'link', 'option', 'html', or custom. Default value: 'html'
 *     @type string     'before'          Content to prepend to the description. Default value: ''
 *     @type string     'after'           Content to append to the description. Default value: ''
 *     @type bool       'show_post_count' Whether to display the post count alongside the link. Default false.
 *     @type string|int 'limit'           Number of links to limit the query to. Default empty (no limit).
 *     @type string     'order'           Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'. Default 'DESC'.
 *     @type string     'post_type'       Post type. Default 'post'.
 *     @type string     'term'            Term slug. Default current term.
 * }
 * @return array Link data.
 */
function get_taxonomy_archive_links( $args = array() ): array {
	global $wpdb;
	$args += array(
		'taxonomy'  => 'category',
		'limit'     => '',
		'order'     => 'DESC',
		'post_type' => 'post',
		'term'      => get_query_var( 'term' ),
	);

	$post_type_object = get_post_type_object( $args['post_type'] );
	if ( ! is_post_type_viewable( $post_type_object ) ) {
		return array();
	}
	$args['post_type'] = $post_type_object->name;

	if ( ! empty( $args['limit'] ) ) {
		$args['limit'] = absint( $args['limit'] );
		$args['limit'] = ' LIMIT ' . $args['limit'];
	}

	$order = strtoupper( $args['order'] );
	if ( 'ASC' !== $order ) {
		$order = 'DESC';
	}

	$where = $wpdb->prepare( "WHERE post_type = %s AND post_status = 'publish' AND tt.taxonomy = %s", $args['post_type'], $args['taxonomy'] );
	$where = apply_filters( 'getarchives_where', $where, $r );
	$join  = "INNER JOIN $wpdb->term_relationships AS tr ON $wpdb->posts.ID = tr.object_id INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
	$join  = apply_filters( 'getarchives_join', $join, $r );

	$links        = array();
	$last_changed = wp_cache_get_last_changed( 'posts' );
	$limit        = $args['limit'];

	// Do query.
	$query = "SELECT tr.term_taxonomy_id as `tt_id`, count(ID) as `count` FROM $wpdb->posts $join $where GROUP BY tr.term_taxonomy_id ORDER BY tr.term_taxonomy_id $order $limit";
	$key   = md5( $query );
	$key   = "get_taxonomy_archives:$key:$last_changed";
	$rs    = wp_cache_get( $key, 'posts' );
	if ( ! $rs ) {
		$rs = $wpdb->get_results( $query );  // phpcs:ignore
		wp_cache_set( $key, $rs, 'posts' );
	}
	if ( $rs ) {
		$after = $args['item_after'];
		foreach ( (array) $rs as $r ) {
			$t = get_term_by( 'term_taxonomy_id', (int) $r->term_taxonomy_id, $args['taxonomy'] );

			$url = get_term_link( $t );
			if ( 'post' !== $args['post_type'] ) {
				$url = add_query_arg( 'post_type', $args['post_type'], $url );
			}
			$text    = sprintf( '%s', $t->name );
			$current = is_tax() && $args['term'] === $t->slug;
			$count   = $r->count;

			$links[ $t->slug ] = compact( 'url', 'text', 'current', 'count' );
		}
	}
	return $links;
}
