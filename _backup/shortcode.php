<?php
/**
 * Shortcode
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-05
 */

namespace wpinc\navi\shortcode;

require_once __DIR__ . '/navigation.php';

/**
 * The.
 */
function add_page_navigation_shortcode() {
	add_shortcode( 'child-page-nav', '\wpinc\navi\shortcode\_sc_child_page_nav' );
	add_shortcode( 'sibling-page-nav', '\wpinc\navi\shortcode\_sc_sibling_page_nav' );
}

/**
 * The.
 *
 * @access private
 */
function _sc_child_page_nav( array $atts ) {
	$atts = shortcode_atts( array( 'style' => false ), $atts );
	return \wpinc\navi\navigation\get_the_child_page_navigation( array( 'class' => $atts['style'] ) );
}

/**
 * The.
 *
 * @access private
 */
function _sc_sibling_page_nav( array $atts ) {
	$atts = shortcode_atts( array( 'style' => false ), $atts );
	return \wpinc\navi\navigation\get_the_sibling_page_navigation( array( 'class' => $atts['style'] ) );
}


// -----------------------------------------------------------------------------


/**
 * The.
 */
function add_youtube_shortcode() {
	add_shortcode( 'youtube', '\wpinc\navi\shortcode\_sc_youtube' );
}

/**
 * The.
 */
function add_vimeo_shortcode() {
	add_shortcode( 'vimeo', '\wpinc\navi\shortcode\_sc_vimeo' );
}

/**
 * The.
 */
function add_instagram_shortcode() {
	add_shortcode( 'instagram', '\wpinc\navi\shortcode\_sc_instagram' );
	add_action( 'wp_enqueue_scripts', '\wpinc\navi\shortcode\_cb_wp_enqueue_scripts_instagram' );
}

/**
 * The.
 *
 * @access private
 */
function _sc_youtube( array $atts ): string {
	return _sc_video_common(
		$atts,
		'<iframe src="https://www.youtube.com/embed/%s" width="%s" height="%s" frameborder="0" allow="autoplay;encrypted-media;fullscreen;picture-in-picture"></iframe>'
	);
}

/**
 * The.
 *
 * @access private
 */
function _sc_vimeo( array $atts ): string {
	return _sc_video_common(
		$atts,
		'<iframe src="https://player.vimeo.com/video/%s" width="%s" height="%s" frameborder="0" allow="autoplay;fullscreen"></iframe>'
	);
}

/**
 * The.
 *
 * @access private
 */
function _sc_video_common( array $atts, string $tag ): string {
	$atts = shortcode_atts(
		array(
			'id'     => '',
			'width'  => '',
			'aspect' => '16:9',
		),
		$atts
	);
	if ( empty( $atts['id'] ) ) {
		return '';
	}
	list( $w, $h ) = _extract_aspect_size( $atts['aspect'] );

	ob_start();
	if ( ! empty( $atts['width'] ) ) {
		echo '<div style="max-width:' . esc_attr( $atts['width'] ) . 'px">' . "\n";
	}
	printf( "\t$tag\t", esc_attr( $id ), esc_attr( $w ), esc_attr( $h ) );  // phpcs:ignore
	if ( ! empty( $atts['width'] ) ) {
		echo '</div>' . "\n";
	}
	return ob_get_clean();
}

/**
 * The.
 *
 * @access private
 */
function _extract_aspect_size( string $aspect, int $base = 1920 ): array {
	$as = array( 16, 9 );
	if ( ! empty( $aspect ) ) {
		$ts = explode( ':', $aspect );
		if ( count( $ts ) === 2 ) {
			$w = (float) $ts[0];
			$h = (float) $ts[1];
			if ( 0 !== $w && 0 !== $h ) {
				$as = array( $w, $h );
			}
		}
	}
	return array( $base, (int) ( $base * $as[1] / $as[0] ) );
}

/**
 * The.
 *
 * @access private
 */
function _sc_instagram( array $atts ): string {
	$atts = shortcode_atts(
		array(
			'url'   => '',
			'width' => '',
		),
		$atts
	);
	ob_start();
	if ( ! empty( $atts['width'] ) ) {
		echo '<div style="max-width:' . esc_attr( $atts['width'] ) . 'px">' . "\n";
		echo "\t" . '<style>iframe.instagram-media{min-width:initial!important;}</style>' . "\n";
	}
	echo "\t" . '<blockquote class="instagram-media" data-instgrm-version="12" style="max-width:99.5%;min-width:300px;width:calc(100% - 2px);display:none;">' . "\n";
	echo "\t\t" . '<a href="' . esc_url( $url ) . '"></a>' . "\n";
	echo "\t" . '</blockquote>' . "\n";
	if ( ! empty( $atts['width'] ) ) {
		echo '</div>' . "\n";
	}
	return ob_get_clean();
}

/**
 * The.
 *
 * @access private
 */
function _cb_wp_enqueue_scripts_instagram() {
	global $post;
	if ( $post && has_shortcode( $post->post_content, 'instagram' ) ) {
		wp_enqueue_script( 'instagram', '//platform.instagram.com/en_US/embeds.js', array(), 1.0, true );
	}
}


// -----------------------------------------------------------------------------


/**
 * The.
 */
function add_post_type_list_shortcode( $post_type, $taxonomy = false, $args = array() ) {
	if ( ! is_array( $args ) ) {  // for backward compatibility.
		$args = array( 'year_date_function' => $args );
	}
	$defs = array(
		'year_date_function' => '\wpinc\navi\shortcode\get_item_year_date_news',
		'year_format'        => false,
	);
	$args = array_merge( $defs, $args );
	add_shortcode(
		$post_type . '-list',
		function ( $atts, $content ) use ( $post_type, $taxonomy, $args ) {
			$defs = array(
				'term'                  => '',
				'taxonomy'              => $taxonomy,
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
			$atts = shortcode_atts( $defs, $atts );

			$atts['order'] = strtolower( $atts['order'] );
			if ( ! empty( $atts['date-after'] ) ) {
				$atts['date-after'] = preg_replace( '/[^0-9]/', '', $atts['date-after'] );
				$atts['date-after'] = str_pad( $atts['date-after'], 8, '0' );
			}
			if ( ! empty( $atts['date-before'] ) ) {
				$atts['date-before'] = preg_replace( '/[^0-9]/', '', $atts['date-before'] );
				$atts['date-before'] = str_pad( $atts['date-before'], 8, '9' );
			}
			$terms = empty( $atts['term'] ) ? false : $atts['term'];
			$items = _get_item_list( $post_type, $taxonomy, $terms, $atts['latest'], $atts['sticky'], $args['year_date_function'], $atts['date-after'], $atts['date-before'], $atts['orderby'] );
			if ( empty( $items ) ) {
				if ( false !== $atts['echo-content-on-empty'] && ! empty( $content ) ) {
					return $content;
				}
				return '';
			}

			if ( 'asc' === $atts['order'] ) {
				$items = array_reverse( $items );
			}
			return _echo_list( $atts, $items, $post_type, $args['year_format'] );
		}
	);
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
function _echo_list( array $atts, array $items, string $post_type, string $year_format = '' ) {
	ob_start();
	if ( false !== $atts['heading'] ) {
		$tag = _get_item_list_heading( $atts['heading'] );
		$t   = get_term_by( 'slug', $atts['term'], $atts['taxonomy'] );
		if ( false !== $t ) {
			echo "<$tag>" . esc_html( \wpinc\get_term_name( $t ) ) . "</$tag>";  // phpcs:ignore
		}
	}
	if ( $atts['year-heading'] ) {
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

		$sub_tag = _get_item_list_heading( $atts['year-heading'] );

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
			_echo_item_list( $items, $atts['style'], $post_type );
		}
	} else {
		_echo_item_list( $items, $atts['style'], $post_type );
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
	$date_bgn = get_post_meta( $post_id, \wpinc\event\PMK_DATE_BGN, true );

	$year = (int) explode( '-', $date_bgn )[0];
	$date = (int) str_replace( '-', '', $date_bgn );
	return array( $year, $date );
}
