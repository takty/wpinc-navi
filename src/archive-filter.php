<?php
/**
 * Archive Filter
 *
 * @author Takuto Yanagida
 * @version 2021-03-30
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
		get_custom_archives( $args['meta_key'], $args );
	}
	?>
	</select>
	<?php
}

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
	$args  = array_merge(
		array(
			'hide_empty' => false,
			'parent'     => 0,
		),
		$args
	);
	foreach ( get_terms( $taxonomy, $args ) as $t ) {
		$link = get_term_link( $t );
		echo '<option value="' . esc_attr( $link ) . '">' . esc_html( $t->name ) . '</option>' . "\n";
		$args['parent'] = $t->term_id;

		foreach ( get_terms( $taxonomy, $args ) as $ct ) {
			$link = get_term_link( $ct );
			echo '<option value="' . esc_attr( $link ) . '">— ' . esc_html( $ct->name ) . '</option>' . "\n";
		}
	}
}
