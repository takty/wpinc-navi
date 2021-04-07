<?php
/**
 * Markup Template Tags
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2021-04-06
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
		'	<div class="nav-links">%4$s</div>',
		'</nav>',
	);
	return sprintf(
		improve( "\n", $temp ),
		sanitize_html_class( $class ),
		esc_attr( $aria_label ),
		esc_html( $screen_reader_text ),
		$links
	);
}

/**
 * Retrieves archive link content based on predefined or custom code.
 * Based on get_archives_link().
 *
 * @param string $url      URL to archive.
 * @param string $text     Archive text description.
 * @param string $format   Link format. Can be 'link', 'option', 'html', or custom.
 * @param string $before   Content to prepend to the description.
 * @param string $after    Content to append to the description.
 * @param bool   $selected True if the current page is the selected archive.
 * @return string HTML link content for archive.
 */
function make_link_markup( string $url, string $text, string $format = 'html', string $before = '', string $after = '', bool $selected = false ): string {
	$text     = wptexturize( $text );
	$url      = esc_url( $url );
	$cur_sel  = $selected ? ' selected="selected"' : '';
	$cur_cls  = $selected ? ' class="current"' : '';
	$cur_aria = $selected ? ' aria-current="page"' : '';

	if ( 'link' === $format ) {
		$text = esc_attr( $text );
		$ret  = "\t<link rel=\"archives\" title=\"$text\" href=\"$url\">\n";
	} elseif ( 'option' === $format ) {
		$ret = "\t<option value=\"$url\"$cur_sel>$before$text$after</option>\n";
	} elseif ( 'html' === $format ) {
		$ret = "\t<li$cur_cls>$before<a class=\"nav-link\" href=\"$url\"$cur_aria>$text</a>$after</li>\n";
	} else {
		$ret = "\t$before<a class=\"nav-link\" href=\"$url\"$cur_aria>$text</a>$after\n";
	}

	static $urls = array();
	if ( 'option' === $format ) {
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
	return $ret;
}
