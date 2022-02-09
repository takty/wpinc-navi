<?php
/**
 * Markup Template Tags
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2022-02-09
 */

namespace wpinc\navi;

/**
 * Makes navigational markup using passed links.
 *
 * @param string $links              Navigational links.
 * @param string $class              Custom class for the nav element.
 * @param string $screen_reader_text Screen reader text for the nav element.
 * @param string $aria_label         ARIA label for the nav element.
 * @return string Navigation template tag.
 */
function make_navigation_markup( string $links, string $class, string $screen_reader_text, string $aria_label ): string {
	if ( empty( $screen_reader_text ) ) {
		$screen_reader_text = __( 'Posts navigation' );
	}
	if ( empty( $aria_label ) ) {
		$aria_label = $screen_reader_text;
	}
	$temp = array(
		'<nav class="navigation %1$s" role="navigation" aria-label="%2$s">',
		'	<h2 class="screen-reader-text">%3$s</h2>',
		'	<div class="nav-links">',
		'%4$s',
		'	</div>',
		'</nav>',
	);
	return sprintf(
		implode( "\n", $temp ) . "\n",
		sanitize_html_class( $class ),
		esc_attr( $aria_label ),
		esc_html( $screen_reader_text ),
		$links
	);
}

/**
 * Makes adjacent link content.
 *
 * @param callable $get_link The function that retrieves archive page URLs.
 * @param bool     $previous Whether to retrieve previous post.
 * @param string   $text     Link text description.
 * @param int      $total    Total pages.
 * @param int      $current  Current page.
 * @return string HTML content.
 */
function make_adjacent_link_markup( $get_link, bool $previous, string $text, int $total, int $current ): string {
	$cls     = $previous ? 'nav-previous' : 'nav-next';
	$is_link = $previous ? ( 1 < $current ) : ( $current < $total );

	if ( $is_link ) {
		$url = call_user_func( $get_link, $current + ( $previous ? -1 : 1 ) );
		$rel = $previous ? 'prev' : 'next';
		return sprintf( '<div class="%s"><a class="nav-link" href="%s" rel="%s">%s</a></div>', $cls, esc_url( $url ), $rel, esc_html( $text ) );
	}
	return sprintf( '<div class="%s disabled"><span>%s</span></div>', $cls, esc_html( $text ) );
}

/**
 * Makes archive links content.
 *
 * @param array  $items         Link item.
 * @param string $type          Link format. Can be 'list', or 'select'.
 * @param string $class         Custom class for the ul or select element.
 * @param string $before        Content to prepend to each link.
 * @param string $after         Content to append to each link.
 * @param bool   $do_show_count Whether the count is shown.
 * @param string $label         Default label for the select element.
 * @return string HTML content.
 */
function make_archive_links_markup( array $items, string $type = 'list', string $class = '', string $before = '', string $after = '', bool $do_show_count = false, string $label = '' ): string {
	$class = empty( $class ) ? '' : ( sanitize_html_class( $class ) . ' ' );

	$lms = '';
	if ( 'list' === $type ) {
		foreach ( $items as $item ) {
			$url   = $item['url'] ?? null;
			$text  = $item['text'] ?? null;
			$cur   = $item['current'] ?? null;
			$count = $item['count'] ?? null;
			$dots  = $item['dots'] ?? null;

			if ( $dots ) {
				$lms .= sprintf( '	<li class="dots"><span>%s</span></li>', $text ) . "\n";
			} else {
				$after_mod = ( $do_show_count && isset( $count ) ) ? "<span class=\"count\">($count)</span>$after" : $after;

				$lms .= make_archive_link_markup( $url, $text, $cur, 'html', $before, $after_mod );
			}
		}
		$temp = array( '<ul class="%2$slinks">', '%1$s', '</ul>' );
		$temp = implode( "\n", $temp ) . "\n";
		return sprintf( $temp, $lms, $class );
	} elseif ( 'select' === $type ) {
		$has_cur = false;
		foreach ( $items as $item ) {
			$url   = $item['url'] ?? null;
			$text  = $item['text'] ?? null;
			$cur   = $item['current'] ?? null;
			$count = $item['count'] ?? null;

			$after_mod = ( $do_show_count && isset( $count ) ) ? "<span class=\"count\">($count)</span>$after" : $after;
			if ( $cur ) {
				$has_cur = true;
			}
			$lms .= make_archive_link_markup( $url, $text, $cur, 'option', $before, $after );
		}
		$temp = array(
			'<select class="%2$slinks" onchange="%3$s">',
			'	<option value="#"' . ( $has_cur ? ' disabled' : '' ) . '>%4$s</option>',
			'%1$s',
			'</select>',
		);
		$temp = implode( "\n", $temp ) . "\n";
		if ( class_exists( 'Simply_Static\Plugin' ) ) {
			$js = 'document.location.href=document.getElementById(this.value).href;';
		} else {
			$js = 'document.location.href=this.value;';
		}
		return sprintf( $temp, $lms, $class, $js, esc_html( $label ) );
	}
	return '';
}

/**
 * Makes archive link content based on predefined or custom code.
 * Based on get_archives_link().
 *
 * @param string $url     URL to archive.
 * @param string $text    Archive text description.
 * @param bool   $current True if the current page is the selected archive.
 * @param string $type    Link format. Can be 'link', 'option', 'html', or custom.
 * @param string $before  Content to prepend to the description.
 * @param string $after   Content to append to the description.
 * @return string HTML content.
 */
function make_archive_link_markup( string $url, string $text, bool $current = false, string $type = 'html', string $before = '', string $after = '' ): string {
	$url  = _apply_get_archives_link_filter( $url );
	$url  = esc_url( $url );
	$text = wptexturize( $text );

	if ( 'link' === $type ) {
		$text = esc_attr( $text );
		$html = sprintf( '	<link rel="archives" href="%1$s" title="%2$s">', $url, $text ) . "\n";
	} elseif ( 'option' === $type ) {
		$cur  = $current ? ' selected="selected"' : '';
		$url  = _assign_link_tags( $url );
		$html = sprintf( '	<option value="%1$s"%3$s>%4$s%2$s%5$s</option>', $url, $text, $cur, $before, $after ) . "\n";
	} else {
		$aria = $current ? ' aria-current="page"' : '';
		if ( 'html' === $type ) {
			$cls  = $current ? ' class="current"' : '';
			$html = sprintf( '	<li%3$s>%5$s<a class="nav-link" href="%1$s"%4$s>%2$s</a>%6$s</li>', $url, $text, $cls, $aria, $before, $after ) . "\n";
		} else {
			$html = sprintf( '	%4$s<a class="nav-link" href="%1$s"%3$s>%2$s</a>%5$s', $url, $text, $aria, $before, $after ) . "\n";
		}
	}
	return apply_filters( 'get_archives_link', $html, $url, $text, $type, $before, $after, $current );
}

/**
 * Retrieves URL to which the 'get_archives_link' filter is applied.
 *
 * @access private
 *
 * @param string $url URL.
 * @return string Modified URL.
 */
function _apply_get_archives_link_filter( string $url ): string {
	$output = trim( get_archives_link( $url, '', 'custom' ) );
	return preg_replace( '/<a href=(["\'])(.*?)\1><\/a>/', '${2}', $output );
}

/**
 * Assigns link tags.
 *
 * @access private
 *
 * @param string $url Added URL.
 * @return string URL or ID of the URL.
 */
function _assign_link_tags( string $url ): string {
	static $urls = array();
	if ( ! class_exists( 'Simply_Static\Plugin' ) ) {
		return $url;
	}
	if ( empty( $urls ) ) {
		add_action(
			'wp_footer',
			function () use ( &$urls ) {
				echo "\n<div style=\"display:none;\"><!-- for simply static -->\n";
				foreach ( $urls as $ui ) {
					list( $url, $id ) = $ui;
					echo "\t<link rel=\"archive\" href=\"$url\" id=\"$id\">\n";  // phpcs:ignore
				}
				echo "</div>\n";
			}
		);
	}
	$id     = '#' . hash( 'crc32b', $url );
	$urls[] = array( $url, $id );
	return $id;
}


// -----------------------------------------------------------------------------


/**
 * Retrieves archive link items.
 *
 * @param callable $get_link The function that retrieves archive page URLs.
 * @param int      $total    Total pages.
 * @param int      $current  Current page.
 * @param int      $mid_size How many numbers to either side of the current pages.
 * @param int      $end_size How many numbers on either the start and the end list edges.
 * @return array Link items.
 */
function get_archive_link_items( $get_link, int $total, int $current, int $mid_size, int $end_size ): array {
	$end_size = ( $end_size < 1 ) ? 1 : $end_size;
	$mid_size = ( $mid_size < 0 ) ? 2 : $mid_size;

	$dots = false;
	$lis  = array();

	for ( $n = 1; $n <= $total; ++$n ) {
		if (
			$n === $current ||
			$n <= $end_size ||
			( $current && $n >= $current - $mid_size && $n <= $current + $mid_size ) ||
			$n > $total - $end_size
		) {
			$dots  = true;
			$lis[] = array(
				'url'     => call_user_func( $get_link, $n ),
				'text'    => number_format_i18n( $n ),
				'current' => $n === $current,
			);
		} elseif ( $dots ) {
			$dots  = false;
			$lis[] = array(
				'text' => __( '&hellip;' ),
				'dots' => true,
			);
		}
	}
	return $lis;
}
