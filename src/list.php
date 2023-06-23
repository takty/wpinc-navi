<?php
/**
 * Post Type List
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2023-06-23
 */

namespace wpinc\navi;

/**
 * Makes post list of specific post type.
 *
 * @param array $args {
 *     Arguments.
 *
 *     @type string          'post_type'          Post type.
 *     @type callable        'year_date_function' Function that retrieves year and date from a post.
 *     @type string          'before'             Content to prepend to the output. Default '<ul>'.
 *     @type string          'after'              Content to append to the output. Default '</ul>'.
 *     @type string          'template_slug'      The slug name for the generic template. Default ''.
 *     @type int             'heading_level'      Heading element level. Default 3.
 *     @type int             'year_heading_level' Year heading element level. Default 4.
 *     @type string          'year_format'        Format string of year.
 *     @type string          'taxonomy'           Taxonomy.
 *     @type string|string[] 'terms'              Terms.
 *     @type int             'latest'             Count of latest posts.
 *     @type bool            'sticky'             Whether to sort sticky items first. Default false.
 *     @type string          'order'              Order. Default desc.
 *     @type string          'orderby'            Orderby. Only 'date' and 'menu_order' is available. Default date.
 *     @type string          'date_after'         Date to retrieve posts after.
 *     @type string          'date_before'        Date to retrieve posts before.
 * }
 */
function get_post_list( array $args = array() ): string {
	$args += array(
		'post_type'          => '',
		'year_date_function' => '\wpinc\navi\get_item_year_date_topic',
		'before'             => '<ul>',
		'after'              => '</ul>',
		'template_slug'      => '',
		'heading_level'      => 3,
		'year_heading_level' => 4,
		'year_format'        => _x( 'Y', 'yearly archives date format' ),
		'taxonomy'           => '',
		'terms'              => array(),
		'latest'             => 0,
		'sticky'             => false,
		'order'              => 'desc',
		'orderby'            => 'date',  // Only 'date' and 'menu_order' is available.
		'date_after'         => '',
		'date_before'        => '',
	);

	$args['order'] = strtolower( $args['order'] );
	if ( ! empty( $args['date_after'] ) ) {
		$args['date_after'] = _align_date( $args['date_after'], '0' );
	}
	if ( ! empty( $args['date_before'] ) ) {
		$args['date_before'] = _align_date( $args['date_before'], '9' );
	}
	if ( ! is_array( $args['terms'] ) ) {
		$args['terms'] = array_map( 'trim', explode( ',', $args['terms'] ) );
	}
	$ps = _get_item_list( $args['post_type'], $args['taxonomy'], $args['terms'], $args['latest'], $args['sticky'] );
	if ( 'menu_order' === $args['orderby'] ) {
		usort(
			$ps,
			function ( $p1, $p2 ) {
				return (int) $p1->menu_order <=> (int) $p2->menu_order;
			}
		);
	}
	$items = _make_item_list( $ps, $args['taxonomy'], $args['year_date_function'], $args['date_after'], $args['date_before'] );
	if ( empty( $items ) ) {
		return '';
	}
	foreach ( $items as &$it ) {
		$it['type'] = $args['post_type'];
	}
	if ( 'asc' === $args['order'] ) {
		$items = array_reverse( $items );
	}
	return _make_list( $args, $items );
}

/**
 * Get aligned date string.
 *
 * @access private
 *
 * @param string $date Date string.
 * @param string $pad  Character for padding.
 * @return string Aligned date string.
 */
function _align_date( string $date, string $pad ): string {
	$date = strtolower( $date );
	if ( 'yesterday' === $date ) {
		$date = wp_date( 'Ymd', strtotime( '-1 day' ) );
	} elseif ( 'today' === $date ) {
		$date = wp_date( 'Ymd' );
	} elseif ( 'tomorrow' === $date ) {
		$date = wp_date( 'Ymd', strtotime( '+1 day' ) );
	} else {
		$date = preg_replace( '/[^0-9]/', '', $date );
		$date = str_pad( $date, 8, $pad );
	}
	return $date;
}

/**
 * Retrieves posts.
 *
 * @access private
 *
 * @param string   $post_type    Post type.
 * @param string   $taxonomy     Taxonomy.
 * @param string[] $term_slugs   Term slugs.
 * @param int      $latest_count Count of latest posts.
 * @param bool     $sticky       Whether to sort sticky items first.
 */
function _get_item_list( string $post_type, string $taxonomy, array $term_slugs, int $latest_count, bool $sticky ) {
	$args = array(
		'post_type'        => $post_type,
		'suppress_filters' => false,
	);
	if ( $taxonomy && $term_slugs ) {
		$args['tax_query']   = array();  // phpcs:ignore
		$args['tax_query'][] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => $term_slugs,
		);
	}
	if ( $latest_count ) {
		if ( $sticky ) {
			$args_s                   = $args;
			$args_s['posts_per_page'] = -1;
			$args_s['meta_query']     = array();  // phpcs:ignore
			$args_s['meta_query'][]   = array(
				'key'   => '_sticky',
				'value' => '1',
			);
			$args['posts_per_page']   = $latest_count;

			return _add_posts( get_posts( $args_s ), get_posts( $args ), $latest_count );
		} else {
			$args['posts_per_page'] = $latest_count;
			return get_posts( $args );
		}
	}
	$args['posts_per_page'] = -1;
	return get_posts( $args );
}

/**
 * Adds post objects.
 *
 * @access private
 *
 * @param \WP_Post[] $augend Array of post objects to which others are added.
 * @param \WP_Post[] $addend Array of post objects which are added to others.
 * @param int|null   $count  Counts of total number.
 * @return \WP_Post[] Array of post objects.
 */
function _add_posts( array $augend, array $addend, ?int $count = null ): array {
	$augend_ips = array_column( $augend, null, 'ID' );
	$addend_ips = array_column( $addend, null, 'ID' );

	$ret = array_values( $augend_ips + $addend_ips );
	if ( 0 < $count ) {
		array_splice( $ret, $count );
	}
	return $ret;
}

/**
 * Makes post items.
 *
 * @access private
 *
 * @param array    $ps        Posts.
 * @param string   $taxonomy  Taxonomy.
 * @param callable $year_date Function that retrieves year and date from a post.
 * @param string   $after     Date to retrieve posts after.
 * @param string   $before    Date to retrieve posts before.
 * @return array Post item.
 */
function _make_item_list( array $ps, string $taxonomy, callable $year_date, string $after, string $before ): array {
	$items = array();
	foreach ( $ps as $p ) {
		$title = esc_html( wp_strip_all_tags( get_the_title( $p->ID ) ) );
		$url   = esc_attr( get_permalink( $p->ID ) );
		$cats  = array();
		$ts    = get_the_terms( $p, $taxonomy );
		if ( is_array( $ts ) ) {
			foreach ( $ts as $t ) {
				$cats[] = $t->name;
			}
		}
		list( $year, $date ) = call_user_func( $year_date, $p->ID );

		if ( $after && $date < $after ) {
			continue;
		}
		if ( $before && $before < $date ) {
			continue;
		}
		$items[] = compact( 'title', 'cats', 'url', 'year', 'date', 'p' );
	}
	return $items;
}

/**
 * Makes list markup.
 *
 * @access private
 *
 * @param array $args {
 *     Arguments.
 *
 *     @type string 'before'             Content to prepend to the output.
 *     @type string 'after'              Content to append to the output.
 *     @type string 'template_slug'      (Optional) The slug name for the generic template.
 *     @type string 'heading_level'      Heading element level.
 *     @type int    'year_heading_level' Year heading element level.
 *     @type string 'year_format'        Format string of year.
 *     @type string 'taxonomy'           Taxonomy.
 *     @type array  'terms'              Terms.
 * }
 * @param array $items Post items.
 */
function _make_list( array $args, array $items ): string {
	ob_start();
	if ( $args['heading_level'] ) {
		$tag = _get_heading_tag_name( (int) $args['heading_level'] );
		$tns = array();
		foreach ( $args['terms'] as $slug ) {
			$t = get_term_by( 'slug', $slug, $args['taxonomy'] );
			if ( false !== $t ) {
				$tns[] = $t->name;
			}
		}
		if ( ! empty( $tns ) ) {
			echo "<$tag>" . esc_html( implode( ', ', $tns ) ) . "</$tag>";  // phpcs:ignore
		}
	}
	if ( $args['year_heading_level'] ) {
		$ac = array();
		foreach ( $items as $it ) {
			$year = is_numeric( $it['year'] ) ? $it['year'] : '-';
			if ( ! isset( $ac[ $year ] ) ) {
				$ac[ $year ] = array();
			}
			$ac[ $year ][] = $it;
		}
		$sub_tag = _get_heading_tag_name( (int) $args['year_heading_level'] );

		foreach ( $ac as $year => $items ) {
			$year = $items[0]['year'];
			if ( is_numeric( $year ) ) {
				$date = date_create_from_format( 'Y', $year );
				echo "<$sub_tag>" . esc_html( date_format( $date, $args['year_format'] ) ) . "</$sub_tag>";  // phpcs:ignore
			}
			echo $args['before'];  // phpcs:ignore
			_echo_items( $items, $args['template_slug'] );
			echo $args['after'];  // phpcs:ignore
		}
	} else {
		echo $args['before'];  // phpcs:ignore
		_echo_items( $items, $args['template_slug'] );
		echo $args['after'];  // phpcs:ignore
	}
	return ob_get_clean();
}

/**
 * Makes heading tag name.
 *
 * @access private
 *
 * @param int $level Heading level.
 * @return string Tag name.
 */
function _get_heading_tag_name( int $level ): string {
	return ( 1 <= $level && $level <= 6 ) ? "h$level" : 'h3';
}

/**
 * Echos post items.
 *
 * @access private
 *
 * @param array  $items         Post items.
 * @param string $template_slug The slug name for the generic template.
 */
function _echo_items( array $items, string $template_slug ): void {
	global $post;
	if ( $template_slug ) {
		$ps = array_column( $items, 'p' );
		foreach ( $ps as $post ) {  // phpcs:ignore
			setup_postdata( $post );
			get_template_part( $template_slug );
		}
		wp_reset_postdata();
	} else {
		foreach ( $items as $it ) {
			$cls = ( $post && $post->ID === $it['p']->ID ) ? ' class="current"' : '';
			?>
			<li<?php echo $cls;  // phpcs:ignore ?>>
				<a href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['title'] ); ?></a>
			</li>
			<?php
		}
	}
}


// -----------------------------------------------------------------------------


/**
 * Retrieves year and date from topic-like post.
 *
 * @access private
 *
 * @param int $post_id Post ID.
 * @return int[] Array of the year and date strings.
 */
function get_item_year_date_topic( int $post_id ): array {
	$year = (int) get_the_date( 'Y', $post_id );
	$date = (int) get_the_date( 'Ymd', $post_id );
	return array( $year, $date );
}

/**
 * Retrieves year and date from event-like post.
 *
 * @access private

 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key.
 * @return int[] Array of the year and date strings.
 */
function get_item_year_date_event( int $post_id, string $meta_key ): array {
	$date = get_post_meta( $post_id, $meta_key, true );
	$year = (int) explode( '-', $date )[0];
	$date = (int) str_replace( '-', '', $date );
	return array( $year, $date );
}
