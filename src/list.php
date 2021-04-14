<?php
/**
 * Post Type List
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-15
 */

namespace wpinc\navi;

/**
 * The.
 */
function get_post_type_list( $args = array() ) {
	$args += array(
		'post_type'             => '',
		'year_date_function'    => '\wpinc\navi\get_item_year_date_news',
		'year_format'           => false,

		'taxonomy'              => false,
		'term'                  => '',
		'style'                 => '',
		'heading'               => false,
		'year-heading'          => false,
		'latest'                => false,
		'sticky'                => false,
		'order'                 => 'desc',
		'orderby'               => 'date',  // Only 'date' and 'menu_order' is available.
		'date-after'            => '',
		'date-before'           => '',
		'echo-content-on-empty' => false,
	);

	$args['order'] = strtolower( $args['order'] );
	if ( ! empty( $args['date-after'] ) ) {
		$args['date-after'] = preg_replace( '/[^0-9]/', '', $args['date-after'] );
		$args['date-after'] = str_pad( $args['date-after'], 8, '0' );
	}
	if ( ! empty( $args['date-before'] ) ) {
		$args['date-before'] = preg_replace( '/[^0-9]/', '', $args['date-before'] );
		$args['date-before'] = str_pad( $args['date-before'], 8, '9' );
	}
	$terms = empty( $args['term'] ) ? false : $args['term'];
	$items = _get_item_list( $args['post_type'], $args['taxonomy'], $terms, $args['latest'], $args['sticky'], $args['year_date_function'], $args['date-after'], $args['date-before'], $args['orderby'] );
	if ( empty( $items ) ) {
		if ( false !== $args['echo-content-on-empty'] && ! empty( $content ) ) {
			return $content;
		}
		return '';
	}
	if ( 'asc' === $args['order'] ) {
		$items = array_reverse( $items );
	}
	return _echo_list( $args, $items, $args['post_type'], $args['year_format'] );
}

/**
 * The.
 *
 * @access private
 */
function _get_item_list( $post_type, $taxonomy, $term_slug, $latest_count, $sticky, $year_date, $after, $before, $orderby ) {
	$args = array( 'suppress_filters' => false );

	if ( false !== $latest_count && is_numeric( $latest_count ) ) {
		$latest_count = (int) $latest_count;
		if ( $term_slug ) {
			$args = \wpinc\append_tax_query( $taxonomy, $term_slug, $args );
		}
		if ( $sticky ) {
			$ps = \wpinc\get_custom_sticky_and_latest_posts( $post_type, $latest_count, $args );
		} else {
			$ps = \wpinc\get_latest_posts( $post_type, $latest_count, $args );
		}
	} else {
		$args = \wpinc\append_post_type_query( $post_type, -1 );
		if ( $term_slug ) {
			$args = \wpinc\append_tax_query( $taxonomy, $term_slug, $args );
		}
		$ps = get_posts( $args );
	}
	if ( count( $ps ) === 0 ) {
		return array();
	}
	if ( 'menu_order' === $orderby ) {
		usort(
			$ps,
			function ( $p1, $p2 ) {
				$a = (int) $p1->menu_order;
				$b = (int) $p2->menu_order;
				if ( $a === $b ) {
					return 0;
				}
				return $a < $b ? 1 : -1;
			}
		);
	}
	$items = array();
	foreach ( $ps as $p ) {
		$title = esc_html( wp_strip_all_tags( get_the_title( $p->ID ) ) );
		$cats  = \wpinc\get_the_term_names( $p->ID, $taxonomy );
		$url   = esc_attr( get_the_permalink( $p->ID ) );

		list( $year, $date ) = call_user_func( $year_date, $p->ID );

		if ( $after && $date < $after ) {
			continue;
		}
		if ( $before && $before < $date ) {
			continue;
		}
		$type    = $post_type;
		$items[] = compact( 'title', 'cats', 'url', 'year', 'date', 'type', 'p' );
	}
	return $items;
}

/**
 * The.
 *
 * @access private
 */
function _echo_list( array $args, array $items, string $post_type, string $year_format = '' ) {
	ob_start();
	if ( false !== $args['heading'] ) {
		$tag = _get_item_list_heading( $args['heading'] );
		$t   = get_term_by( 'slug', $args['term'], $args['taxonomy'] );
		if ( false !== $t ) {
			echo "<$tag>" . esc_html( \wpinc\get_term_name( $t ) ) . "</$tag>";  // phpcs:ignore
		}
	}
	if ( $args['year-heading'] ) {
		$ac = array();
		foreach ( $items as $it ) {
			$year = $it['year'];
			if ( false === $year ) {
				$year = '-';
			}
			if ( ! isset( $ac[ $year ] ) ) {
				$ac[ $year ] = array();
			}
			$ac[ $year ][] = $it;
		}

		$sub_tag = _get_item_list_heading( $args['year-heading'] );

		if ( empty( $year_format ) ) {
			$year_format = _x( 'Y', 'yearly archives date format' );
		}

		foreach ( $ac as $year => $items ) {
			if ( false !== $sub_tag ) {
				$year = $items[0]['year'];
				if ( false !== $year ) {
					$date = date_create_from_format( 'Y', $year );
					echo "<$sub_tag>" . esc_html( date_format( $date, $year_format ) ) . "</$sub_tag>";  // phpcs:ignore
				}
			}
			_echo_item_list( $items, $args['style'], $post_type );
		}
	} else {
		_echo_item_list( $items, $args['style'], $post_type );
	}
	return ob_get_clean();
}

/**
 * The.
 *
 * @access private
 */
function _get_item_list_heading( string $tag ): string {
	if ( is_numeric( $tag ) ) {
		$l = (int) $tag;
		if ( 3 <= $l && $l <= 6 ) {
			return "h$l";
		}
	}
	return 'h3';
}

/**
 * The.
 *
 * @access private
 */
function _echo_item_list( array $items, string $style = '', string $post_type = '' ) {
	if ( 'full' === $style ) {
		$posts = array_map(
			function ( $it ) {
				return $it['p'];
			},
			$items
		);
		?>
		<ul class="list-item list-item-<?php echo esc_attr( $post_type ); ?> shortcode">
			<?php \wpinc\the_loop_posts( 'template-parts/item', $post_type, $posts ); ?>
		</ul>
		<?php
	} else {
		echo '<ul>';
		foreach ( $items as $it ) {
			?>
			<li><a href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['title'] ); ?></a></li>
			<?php
		}
		echo '</ul>';
	}
}


// -----------------------------------------------------------------------------


/**
 * The.
 *
 * @access private
 */
function get_item_year_date_news( $post_id ) {
	$year = (int) get_the_date( 'Y', $post_id );
	$date = (int) get_the_date( 'Ymd', $post_id );
	return array( $year, $date );
}

/**
 * The.
 *
 * @access private
 */
function get_item_year_date_event( $post_id ) {
	$date = get_post_meta( $post_id, \wpinc\event\PMK_DATE_BGN, true );
	$year = (int) explode( '-', $date )[0];
	$date = (int) str_replace( '-', '', $date );
	return array( $year, $date );
}
