<?php
/**
 * Markup Template Tags
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-10
 */

namespace wpinc\navi;

/**
 * Wraps passed links in navigational markup.
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
		improve( "\n", $temp ) . "\n",
		sanitize_html_class( $class ),
		esc_attr( $aria_label ),
		esc_html( $screen_reader_text ),
		$links
	);
}

/**
 * Retrieves archive links content.
 *
 * @param array  $items          Link item.
 * @param string $type           Link format. Can be 'list', 'select', or custom.
 * @param string $class          Custom class for the ul or select element.
 * @param string $before         Content to prepend to each link.
 * @param string $after          Content to append to each link.
 * @param bool   $is_count_shown Whether the count is shown.
 * @param string $label          Default label for the select element.
 * @return string HTML content.
 */
function make_archive_links_markup( array $items, string $type = 'list', string $class = '', string $before = '', string $after = '', bool $is_count_shown = false, string $label = '' ): string {
	$class = empty( $class ) ? '' : ( ' ' . sanitize_html_class( $class ) );

	$lms = '';
	if ( 'list' === $type ) {
		foreach ( $items as $item ) {
			list( 'url' => $url, 'text' => $text, 'current' => $cur, 'count' => $count, 'dots' => $dots ) = $item;
			if ( $dots ) {
				$lms .= sprintf( '	<li class="dots"><span>%s</span></li>', $text ) . "\n";
			} else {
				$after_mod = ( $is_count_shown && isset( $count ) ) ? "&nbsp;($count)$after" : $after;

				$lms .= make_archive_link_markup( $url, $text, $cur, 'html', $before, $after_mod );
			}
		}
		$temp = array( '<ul class="links%2$s">', '%1$s', '</ul>' );
		$temp = improve( "\n", $temp ) . "\n";
		return sprintf( $temp, $lms, $class );
	} elseif ( 'select' === $type ) {
		foreach ( $items as $item ) {
			list( 'url' => $url, 'text' => $text, 'current' => $cur, 'count' => $count ) = $item;
			$after_mod = ( $is_count_shown && isset( $count ) ) ? "&nbsp;($count)$after" : $after;

			$lms .= make_archive_link_markup( $url, $text, $cur, 'option', $before, $after );
		}
		$temp = array(
			'<select class="links%2$s" onchange="%3$s">',
			'	<option value="#">%4$s</option>',
			'%1$s',
			'</select>',
		);
		$temp = improve( "\n", $temp ) . "\n";
		$js   = 'document.location.href=this.value;';
		return sprintf( $temp, $lms, $class, $js, esc_html( $label ) );
	}
	return '';
}

/**
 * Retrieves archive link content based on predefined or custom code.
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
	$url  = esc_url( $url );
	$text = wptexturize( $text );

	if ( 'link' === $type ) {
		$text = esc_attr( $text );
		$html = sprintf( '	<link rel="archives" href="%1$s" title="%2$s">', $url, $text ) . "\n";
	} elseif ( 'option' === $type ) {
		_assign_link_tags( $url );
		$cur  = $current ? ' selected="selected"' : '';
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
	/**
	 * Filters the archive link content.
	 *
	 * @param string $html    The archive HTML link content.
	 * @param string $url     URL to archive.
	 * @param string $text    Archive text description.
	 * @param bool   $current True if the current page is the selected archive.
	 * @param string $type    Link format. Can be 'link', 'option', 'html', or custom.
	 * @param string $before  Content to prepend to the description.
	 * @param string $after   Content to append to the description.
	 */
	return apply_filters( 'make_archive_link_markup', $html, $url, $text, $current, $type, $before, $after );
}

/**
 * Assign link tags.
 *
 * @access private
 *
 * @param string $url Added URL.
 */
function _assign_link_tags( string $url ) {
	static $urls = array();
	if ( class_exists( 'Simply_Static\Plugin' ) && empty( $urls ) ) {
		add_action(
			'wp_footer',
			function () use ( &$urls ) {
				echo "<div style=\"display:none;\"><!-- for simply static -->\n";
				foreach ( $urls as $url ) {
					echo "\t<link rel=\"archive\" href=\"$url\">\n";  // phpcs:ignore
				}
				echo "</div>\n";
			}
		);
	}
	$urls[] = $url;
}
