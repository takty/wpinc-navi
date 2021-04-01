<?php
/**
 * Archive Filter
 *
 * @author Takuto Yanagida
 * @version 2021-04-01
 */

namespace wpinc\compass;

/**
 *
 * @param array $args {
 * }
 */
function the_yearly_archive_select( array $args = array() ) {
	$args = array_merge(
		array(
			'post_type'     => 'post',
			'type'          => 'yearly',
			'format'        => 'option',
			'default_title' => __( 'Year' ),
			'meta_key'      => false,
		),
		$args
	);
	?>
	<select onchange="document.location.href = this.value;">
		<option value="#"><?php echo esc_html( $args['default_title'] ); ?></option>
	<?php
	if ( false === $args['meta_key'] ) {
		wp_get_archives( $args );
	} else {
		get_archives_by_meta( $args['meta_key'], $args );
	}
	?>
	</select>
	<?php
}

/**
 *
 */
function get_archives_by_meta( string $meta_key, array $args = array() ) {
	global $wpdb, $wp_locale;
	$args = array_merge(
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
			'year'            => get_query_var( 'year' ),
			'monthnum'        => get_query_var( 'monthnum' ),
			'day'             => get_query_var( 'day' ),
		),
		$args
	);

	$post_type_object = get_post_type_object( $args['post_type'] );
	if ( ! is_post_type_viewable( $post_type_object ) ) {
		return;
	}
	$args['post_type'] = $post_type_object->name;

	if ( '' === $args['type'] ) {
		$args['type'] = 'monthly';
	}
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
	$join  = "INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->postmeta.meta_key = '$meta_key' )";
	$join  = apply_filters( 'getarchives_join', $join, $args );

	$output       = '';
	$last_changed = wp_cache_get_last_changed( 'posts' );
	$limit        = $args['limit'];

	if ( 'yearly' === $args['type'] ) {
		$query   = "SELECT YEAR(meta_value) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(meta_value) ORDER BY meta_value $order $limit";
		$key     = md5( $query );
		$key     = "wp_get_archives:$key:$last_changed";
		$results = wp_cache_get( $key, 'posts' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );  // phpcs:ignore
			wp_cache_set( $key, $results, 'posts' );
		}
		if ( $results ) {
			$after = $args['after'];
			foreach ( (array) $results as $result ) {
				$url = get_year_link( $result->year );
				if ( 'post' !== $args['post_type'] ) {
					$url = add_query_arg( 'post_type', $args['post_type'], $url );
				}
				$text = sprintf( '%d', $result->year );
				if ( $args['show_post_count'] ) {
					$args['after'] = '&nbsp;(' . $result->posts . ')' . $after;
				}
				$selected = is_archive() && (string) $args['year'] === $result->year;
				$output  .= get_archives_link( $url, $text, $args['format'], $args['before'], $args['after'], $selected );
			}
		}
	} elseif ( 'monthly' === $args['type'] ) {
		$query   = "SELECT YEAR(meta_value) AS `year`, MONTH(meta_value) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(meta_value), MONTH(meta_value) ORDER BY meta_value $order $limit";
		$key     = md5( $query );
		$key     = "wp_get_archives:$key:$last_changed";
		$results = wp_cache_get( $key, 'posts' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );  // phpcs:ignore
			wp_cache_set( $key, $results, 'posts' );
		}
		if ( $results ) {
			$after = $args['after'];
			foreach ( (array) $results as $result ) {
				$url = get_month_link( $result->year, $result->month );
				if ( 'post' !== $args['post_type'] ) {
					$url = add_query_arg( 'post_type', $args['post_type'], $url );
				}
				/* translators: 1: month name, 2: 4-digit year */
				$text = sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $result->month ), $result->year );
				if ( $args['show_post_count'] ) {
					$args['after'] = '&nbsp;(' . $result->posts . ')' . $after;
				}
				$selected = is_archive() && (string) $args['year'] === $result->year && (string) $args['monthnum'] === $result->month;
				$output  .= get_archives_link( $url, $text, $args['format'], $args['before'], $args['after'], $selected );
			}
		}
    } elseif ( 'daily' === $args['type'] ) {
		$query   = "SELECT YEAR(meta_value) AS `year`, MONTH(meta_value) AS `month`, DAYOFMONTH(meta_value) AS `dayofmonth`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(meta_value), MONTH(meta_value), DAYOFMONTH(meta_value) ORDER BY meta_value $order $limit";
		$key     = md5( $query );
		$key     = "wp_get_archives:$key:$last_changed";
		$results = wp_cache_get( $key, 'posts' );
		if ( ! $results ) {
			$results = $wpdb->get_results( $query );  // phpcs:ignore
			wp_cache_set( $key, $results, 'posts' );
		}
		if ( $results ) {
			$after = $args['after'];
			foreach ( (array) $results as $result ) {
				$url = get_day_link( $result->year, $result->month, $result->dayofmonth );
				if ( 'post' !== $args['post_type'] ) {
					$url = add_query_arg( 'post_type', $args['post_type'], $url );
				}
				$date = sprintf( '%1$d-%2$02d-%3$02d 00:00:00', $result->year, $result->month, $result->dayofmonth );
				$text = mysql2date( get_option( 'date_format' ), $date );
				if ( $args['show_post_count'] ) {
					$args['after'] = '&nbsp;(' . $result->posts . ')' . $after;
				}
				$selected = is_archive() && (string) $args['year'] === $result->year && (string) $args['monthnum'] === $result->month && (string) $args['day'] === $result->dayofmonth;
				$output  .= get_archives_link( $url, $text, $args['format'], $args['before'], $args['after'], $selected );
			}
		}
	}
	if ( $args['echo'] ) {
		echo $output;  // phpcs:ignore
	} else {
		return $output;
	}
}


// -----------------------------------------------------------------------------


function the_taxonomy_archive_select( array $args = array() ) {
	$args = array_merge(
		array(
			'taxonomy'      => 'category',
			'default_title' => __( 'Category' ),
			'meta_key'      => false,
			'hide_empty'    => false,
			'parent'        => 0,
		),
		$args
	);
	?>
	<select onchange="document.location.href = this.value;">
		<option value="#"><?php echo esc_html( $args['default_title'] ); ?></option>
		<?php the_taxonomy_archive_option( $args['taxonomy'], $args ); ?>
	</select>
	<?php
}

function the_taxonomy_archive_option( $args = array() ) {
	$args = array_merge(
		array(
			'hide_empty' => false,
			'parent'     => 0,
		),
		$args
	);

	$output = '';
	foreach ( get_terms( $taxonomy, $args ) as $t ) {
		$url     = get_term_link( $t );
		$output .= get_archives_link( $url, $t->name, 'option' );

		$args['parent'] = $t->term_id;

		foreach ( get_terms( $taxonomy, $args ) as $ct ) {
			$url     = get_term_link( $ct );
			$output .= get_archives_link( $url, '— ' . $ct->name, 'option' );
		}
	}
	echo $output;  // phpcs:ignore
}


// -----------------------------------------------------------------------------


/**
 * The callback function for 'get_archives_link' filter.
 *
 * @param string $link_html The archive HTML link content.
 * @param string $url       URL to archive.
 * @param string $text      Archive text description.
 * @param string $format    Link format. Can be 'link', 'option', 'html', or custom.
 * @param string $before    Content to prepend to the description.
 * @param string $after     Content to append to the description.
 * @param bool   $selected  True if the current page is the selected archive.
 * @return string The filtered link.
 */
function _cb_get_archives_link( string $link_html, string $url, string $text, string $format, string $before, string $after, bool $selected ): string {
	if ( $selected ) {
		if ( 'html' === $format ) {
			$link_html = str_replace( "\t<li>", "\t<li class=\"current\">", $link_html );
		}
	}
	static $urls = array();
	if ( 'option' === $format ) {
		if ( empty( $urls ) ) {
			add_action(
				'wp_footer',
				function () use ( &$urls ) {
					echo '<div style="display:none;"><!-- archive links of option values -->' . "\n";
					foreach ( $urls as $url ) {
						echo '<a href="' . esc_attr( $url ) . '"></a>' . "\n";
					}
					echo '</div>' . "\n";
				}
			);
		}
		$urls[] = $url;
	}
	return $link_html;
}
