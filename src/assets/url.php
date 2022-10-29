<?php
/**
 * URL Utilities.
 *
 * @package Wpinc
 * @author Takuto Yanagida
 * @version 2022-10-28
 */

namespace wpinc;

if ( ! function_exists( '\wpinc\get_current_url' ) ) {
	/**
	 * Gets current URL.
	 *
	 * @return string Current URL.
	 */
	function get_current_url(): string {
		if ( is_singular() ) {
			return get_permalink();
		}
		if ( ! isset( $_SERVER['HTTP_HOST'] ) || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return home_url();
		}
		return get_request_url();
	}
}

if ( ! function_exists( '\wpinc\get_request_url' ) ) {
	/**
	 * Gets request URL.
	 *
	 * @param bool $orig Whether to get the original URL.
	 * @return string URL.
	 */
	function get_request_url( bool $orig = false ): string {
		// phpcs:disable
		$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'];  // When reverse proxy exists.
		$req  = ( $orig && isset( $_SERVER['REQUEST_URI_ORIG'] ) ) ? $_SERVER['REQUEST_URI_ORIG'] : $_SERVER['REQUEST_URI'];
		// phpcs:enable
		return ( is_ssl() ? 'https://' : 'http://' ) . wp_unslash( $host ) . wp_unslash( $req );
	}
}

if ( ! function_exists( '\wpinc\serialize_url' ) ) {
	/**
	 * Serializes URL components.
	 *
	 * @param array $cs URL components.
	 * @return string URL.
	 */
	function serialize_url( array $cs ): string {
		// phpcs:disable
		$scheme = isset( $cs['scheme'] )   ? "{$cs['scheme']}://" : '';
		$host   = isset( $cs['host'] )     ? $cs['host']          : '';
		$port   = isset( $cs['port'] )     ? ":{$cs['port']}"     : '';
		$user   = isset( $cs['user'] )     ? $cs['user']          : '';
		$pass   = isset( $cs['pass'] )     ? ":{$cs['pass']}"     : '';
		$pass   = ( $user || $pass )       ? "$pass@"             : '';
		$path   = isset( $cs['path'] )     ? $cs['path']          : '';
		$query  = isset( $cs['query'] )    ? "?{$cs['query']}"    : '';
		$frag   = isset( $cs['fragment'] ) ? "#{$cs['fragment']}" : '';
		return "$scheme$user$pass$host$port$path$query$frag";
		// phpcs:enable
	}
}
