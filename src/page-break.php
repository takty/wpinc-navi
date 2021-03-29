<?php
/**
 * Page Break
 *
 * @author Takuto Yanagida
 * @version 2021-03-23
 */


namespace st\page_break {

	function initialize() {
		add_action( 'wp_head', '\st\page_break\adjacent_posts_rel_link_wp_head' );
		add_filter(
			'content_pagination',
			function ( $pages ) {
				foreach ( $pages as &$page ) {
					$page = force_balance_tags( $page );
				}
				return $pages;
			}
		);
	}

	function adjacent_posts_rel_link_wp_head() {
		if ( ! is_singular() || is_attachment() ) {
			return;
		}
		$prev = '';
		$next = '';

		$post     = get_post();
		$numpages = get_page_break_count( $post );

		if ( 0 < $numpages ) {
			$url_prev = get_adjacent_page_break_url( true, $post, $numpages );
			$url_next = get_adjacent_page_break_url( false, $post, $numpages );

			if ( $url_prev ) {
				$prev = '<link rel="prev" href="' . $url_prev . '" />';
			}
			if ( $url_next ) {
				$next = '<link rel="next" href="' . $url_next . '" />';
			}
		}
		if ( is_single() ) {
			if ( empty( $prev ) ) {
				$prev = get_adjacent_post_rel_link( '%title', false, '', true, 'category' );
			}
			if ( empty( $next ) ) {
				$next = get_adjacent_post_rel_link( '%title', false, '', false, 'category' );
			}
		}
		echo $prev;
		echo $next;
	}

	function get_page_break_count( $post ) {
		$content = $post->post_content;
		if ( false === strpos( $content, '<!--nextpage-->' ) ) {
			return 1;
		}
		$content = str_replace( "\n<!--nextpage-->\n", '<!--nextpage-->', $content );
		$content = str_replace( "\n<!--nextpage-->", '<!--nextpage-->', $content );
		$content = str_replace( "<!--nextpage-->\n", '<!--nextpage-->', $content );
		if ( 0 === strpos( $content, '<!--nextpage-->' ) ) {
			$content = substr( $content, 15 );
		}
		return count( explode( '<!--nextpage-->', $content ) );
	}

	function get_adjacent_page_break_url( $previous = true, $post, $numpages ) {
		global $wp_query;

		$page = $wp_query->get( 'page' );
		if ( ! $page ) {
			$page = 1;
		}
		$i = $previous ? $page - 1 : $page + 1;
		if ( $i <= 0 || $numpages < $i ) {
			return '';
		}
		return get_page_break_url( $i, $post );
	}

	// Based on _wp_link_page( $i ).
	function get_page_break_url( $i, $post ) {
		global $wp_rewrite;

		$url = get_permalink( $post );
		if ( 1 === $i ) {
		} elseif ( empty( get_option( 'permalink_structure' ) ) || ( $post && in_array( $post->post_status, [ 'draft', 'pending' ], true ) ) ) {
			$url = add_query_arg( 'page', $i, $url );
		} elseif ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) === $post->ID ) {
			$url = trailingslashit( $url ) . user_trailingslashit( "$wp_rewrite->pagination_base/" . $i, 'single_paged' );
		} else {
			$url = trailingslashit( $url ) . user_trailingslashit( $i, 'single_paged' );
		}
		if ( is_preview() ) {
			$query_args = array();
			if ( ( 'draft' !== $post->post_status ) && isset( $_GET['preview_id'], $_GET['preview_nonce'] ) ) {
				$query_args['preview_id']    = wp_unslash( $_GET['preview_id'] );
				$query_args['preview_nonce'] = wp_unslash( $_GET['preview_nonce'] );
			}
			$url = get_preview_post_link( $post, $query_args, $url );
		}
		return $url;
	}
}  // namespace st\singular_paging

namespace st {

	function the_page_break_navigation( array $args = array() ) {
		echo get_the_page_break_navigation( $args );
	}

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
				$_url = esc_url( \st\page_break\get_page_break_url( $i, $post ) );

				$output .= "<a class=\"nav-page-break-link\" href=\"$_url\">$i</a>";
			} else {
				$output .= "<span class=\"nav-page-break-current\">$i</span>";
			}
		}
		$output .= '</div></nav>';
		return $output;
	}
}  // namespace st
