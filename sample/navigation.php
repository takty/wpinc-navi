<?php
/**
 * Navigation
 *
 * @package Sample
 * @author Takuto Yanagida
 * @version 2022-01-25
 */

namespace sample;

require_once __DIR__ . '/navi/archive.php';
require_once __DIR__ . '/navi/class-nav-menu.php';
require_once __DIR__ . '/navi/link-page-break.php';
require_once __DIR__ . '/navi/page-break.php';
require_once __DIR__ . '/navi/page-hierarchy.php';
require_once __DIR__ . '/navi/post.php';
require_once __DIR__ . '/navi/shortcode.php';

/**
 * Constructs a navigation menu.
 *
 * @param array $args {
 *     An array of arguments.
 *
 *     @type string   'menu_location'     Menu location.
 *     @type array    'anchored_page_ids' Page ids for making their links of menu items anchor links.
 *     @type array    'object_types'      Object types.
 *     @type string   'home_url'          Home URL.
 *     @type callable 'title_filter'      Filter function for titles. Default 'esc_html'.
 *     @type callable 'content_filter'    Filter function for contents. Default 'esc_html'.
 * }
 * @return \wpinc\navi\Nav_Menu Navigation menu.
 */
function create_nav_menu( array $args ): \wpinc\navi\Nav_Menu {
	return new \wpinc\navi\Nav_Menu( $args );
}

/**
 * Enable cache of navigation menus.
 */
function enable_nav_menu_cache() {
	\wpinc\navi\Nav_Menu::enable_cache();
}

/**
 * Adds the archive slug of a custom post type to navigation menus.
 *
 * @param string $post_type Post type.
 * @param string $slug      Slug of the archive page.
 */
function add_custom_post_type_archive_to_nav_menu( string $post_type, string $slug ) {
	\wpinc\navi\Nav_Menu::add_custom_post_type_archive( $post_type, $slug );
}


// -----------------------------------------------------------------------------


/**
 * Initializes next and previous link tags.
 */
function initialize_link_page_break() {
	\wpinc\navi\link_page_break\initialize();
}


// -----------------------------------------------------------------------------


/**
 * Displays yearly archive select.
 *
 * @param array $args (Optional) Array of arguments. See get_date_archives() for information on accepted arguments.
 */
function the_yearly_archive_select( array $args = array() ) {
	\wpinc\navi\the_yearly_archive_select( $args );
}

/**
 * Displays taxonomy archive select.
 *
 * @param array $args (Optional) Array of arguments. See get_taxonomy_archives() for information on accepted arguments.
 */
function the_taxonomy_archive_select( array $args = array() ) {
	\wpinc\navi\the_taxonomy_archive_select( $args );
}


// -----------------------------------------------------------------------------


/**
 * Displays date archive links based on type and format.
 *
 * @param array $args (Optional) Array of arguments. See get_date_archives() for information on accepted arguments.
 */
function the_date_archives( array $args = array() ) {
	\wpinc\navi\the_date_archives( $args );
}

/**
 * Retrieves date archive links based on type and format.
 *
 * @param array $args {
 *     (Optional) Array of type, format, and term query parameters.
 *
 *     @type string     'before'        Content to prepend to the output. Default ''.
 *     @type string     'after'         Content to append to the output. Default ''.
 *     @type string     'type'          Can be 'link', 'option', 'html', or custom. Default value: 'html'
 *     @type string     'item_before'   Content to prepend to each link. Default value: ''
 *     @type string     'item_after'    Content to append to each link. Default value: ''
 *     @type bool       'do_show_count' Whether to display the post count alongside the link. Default false.
 *     @type string     'default_text'  Default text used when 'type' is 'select'. Default ''.
 *     @type string     'post_type'     Post type. Default 'post'.
 *     @type string     'date'          Type of archive to retrieve. Accepts 'daily', 'monthly', or 'yearly'. Default 'yearly'.
 *     @type string|int 'limit'         Number of links to limit the query to. Default empty (no limit).
 *     @type string     'order'         Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'. Default 'DESC'.
 *     @type string     'meta_key'      Meta key used instead of post_date.
 * }
 * @return array String of links.
 */
function get_date_archives( array $args = array() ): string {
	return \wpinc\navi\get_date_archives( $args );
}

/**
 * Displays taxonomy archive links based on type and format.
 *
 * @param array $args (Optional) Array of arguments. See get_taxonomy_archives() for information on accepted arguments.
 */
function the_taxonomy_archives( array $args = array() ) {
	\wpinc\navi\the_taxonomy_archives( $args );
}

/**
 * Retrieves taxonomy archive links based on type and format.
 *
 * @param array $args {
 *     (Optional) Array of type, format, and term query parameters.
 *
 *     @type string     'before'        Content to prepend to the output. Default ''.
 *     @type string     'after'         Content to append to the output. Default ''.
 *     @type string     'type'          Link format. Can be 'list', or 'select'.
 *     @type string     'item_before'   Content to prepend to each link. Default value: ''
 *     @type string     'item_after'    Content to append to each link. Default value: ''
 *     @type bool       'do_show_count' Whether to display the post count alongside the link. Default false.
 *     @type string     'default_text'  Default text used when 'type' is 'select'. Default ''.
 *     @type string     'post_type'     Post type. Default 'post'.
 *     @type string     'taxonomy'      Taxonomy name to which results should be limited.
 *     @type string|int 'limit'         Number of links to limit the query to. Default empty (no limit).
 *     @type string     'order'         Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'. Default 'DESC'.
 *     @type bool       'hierarchical'  Whether to include terms that have non-empty descendants. Default false.
 *     @type int        'parent'        Parent term ID to retrieve direct-child terms of. Default 0.
 * }
 * @return array String of links.
 */
function get_taxonomy_archives( array $args = array() ): string {
	return \wpinc\navi\get_taxonomy_archives( $args );
}


// -----------------------------------------------------------------------------


/**
 * Displays a page break navigation, when applicable.
 *
 * @param array $args (Optional) See get_the_page_break_navigation() for available arguments.
 */
function the_page_break_navigation( array $args = array() ) {
	\wpinc\navi\the_page_break_navigation( $args );
}

/**
 * Displays the navigation to page breaks, when applicable.
 *
 * @param array $args {
 *     (Optional) Default page break navigation arguments.
 *
 *     @type string 'before'             Content to prepend to the output. Default ''.
 *     @type string 'after'              Content to append to the output. Default ''.
 *     @type string 'prev_text'          Anchor text to display in the previous post link. Default ''.
 *     @type string 'next_text'          Anchor text to display in the next post link. Default ''.
 *     @type string 'screen_reader_text' Screen reader text for the nav element. Default 'Post break navigation'.
 *     @type string 'aria_label'         ARIA label text for the nav element. Default 'Page breaks'.
 *     @type string 'class'              Custom class for the nav element. Default 'page-break-navigation'.
 *     @type string 'type'               Link format. Can be 'list', 'select', or custom.
 *     @type string 'mid_size'           How many numbers to either side of the current pages. Default 2.
 *     @type string 'end_size'           How many numbers on either the start and the end list edges. Default 1.
 *     @type string 'number_before'      A string to appear before the page number.
 *     @type string 'number_after'       A string to append after the page number.
 * }
 * @return string Markup for page break links.
 */
function get_the_page_break_navigation( array $args = array() ): string {
	return \wpinc\navi\get_the_page_break_navigation( $args );
}


// -----------------------------------------------------------------------------


/**
 * Displays a child page navigation, when applicable.
 *
 * @param array $args       (Optional) See get_the_child_page_navigation() for available arguments.
 * @param array $query_args (Optional) Arguments for get_post().
 */
function the_child_page_navigation( array $args = array(), array $query_args = array() ) {
	\wpinc\navi\the_child_page_navigation( $args, $query_args );
}

/**
 * Displays a sibling page navigation, when applicable.
 *
 * @param array $args       (Optional) See get_the_sibling_page_navigation() for available arguments.
 * @param array $query_args (Optional) Arguments for get_post().
 */
function the_sibling_page_navigation( array $args = array(), array $query_args = array() ) {
	\wpinc\navi\the_sibling_page_navigation( $args, $query_args );
}

/**
 * Retrieves a child page navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default navigation arguments.
 *
 *     @type string 'before'                   Content to prepend to the output. Default ''.
 *     @type string 'after'                    Content to append to the output. Default ''.
 *     @type string 'screen_reader_text'       Screen reader text for navigation element. Default 'Child pages navigation'.
 *     @type string 'aria_label'               ARIA label text for the nav element. Default 'Child pages'.
 *     @type string 'class'                    Custom class for the nav element. Default 'child-page-navigation'.
 *     @type string 'type'                     Link format. Can be 'list', 'select', or custom.
 *     @type bool   'hide_page_with_thumbnail' Whether pages with post thumbnails are hidden. Default false.
 * }
 * @param array $query_args (Optional) Arguments for get_post().
 * @return string Markup for child page links.
 */
function get_the_child_page_navigation( array $args = array(), array $query_args = array() ): string {
	return \wpinc\navi\get_the_child_page_navigation( $args, $query_args );
}

/**
 * Retrieves a sibling page navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default navigation arguments.
 *
 *     @type string 'before'                   Content to prepend to the output. Default ''.
 *     @type string 'after'                    Content to append to the output. Default ''.
 *     @type string 'screen_reader_text'       Screen reader text for navigation element. Default 'Sibling pages navigation'.
 *     @type string 'aria_label'               ARIA label text for the nav element. Default 'Sibling pages'.
 *     @type string 'class'                    Custom class for the nav element. Default 'sibling-page-navigation'.
 *     @type string 'type'                     Link format. Can be 'list', 'select', or custom.
 *     @type bool   'hide_page_with_thumbnail' Whether pages with post thumbnails are hidden. Default false.
 * }
 * @param array $query_args (Optional) Arguments for get_post().
 * @return string Markup for sibling page links.
 */
function get_the_sibling_page_navigation( array $args = array(), array $query_args = array() ): string {
	return \wpinc\navi\get_the_sibling_page_navigation( $args, $query_args );
}


// -----------------------------------------------------------------------------


/**
 * Displays a post navigation, when applicable.
 *
 * @param array $args (Optional) See get_the_post_navigation() for available arguments.
 */
function the_post_navigation( array $args = array() ) {
	\wpinc\navi\the_post_navigation( $args );
}

/**
 * Displays a posts navigation, when applicable.
 *
 * @param array $args (Optional) See get_the_posts_navigation() for available arguments.
 */
function the_posts_navigation( array $args = array() ) {
	\wpinc\navi\the_posts_navigation( $args );
}

/**
 * Retrieves a post navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default post navigation arguments.
 *
 *     @type string       'before'               Content to prepend to the output. Default ''.
 *     @type string       'after'                Content to append to the output. Default ''.
 *     @type string       'prev_text'            Anchor text to display in the previous post link. Default ''.
 *     @type string       'next_text'            Anchor text to display in the next post link. Default ''.
 *     @type string       'screen_reader_text'   Screen reader text for the nav element. Default 'Post navigation'.
 *     @type string       'aria_label'           ARIA label text for the nav element. Default 'Post'.
 *     @type string       'class'                Custom class for the nav element. Default 'post-navigation'.
 *     @type bool         'in_same_term'         Whether link should be in a same taxonomy term. Default false.
 *     @type int[]|string 'excluded_terms'       Array or comma-separated list of excluded term IDs.
 *     @type string       'taxonomy'             Taxonomy, if 'in_same_term' is true. Default 'category'.
 *     @type bool         'has_archive_link'     Whether the archive link is contained. Default false.
 *     @type string       'archive_text'         Anchor text to display in the archive link. Default 'List'.
 *     @type string       'archive_link_pos'     Position of archive link, if 'has_archive_link' is true. Can be 'start', 'center', or 'end'. Default 'center'.
 * }
 * @return string Markup for post links.
 */
function get_the_post_navigation( array $args = array() ): string {
	return \wpinc\navi\get_the_post_navigation( $args );
}

/**
 * Retrieves a posts navigation, when applicable.
 *
 * @param array $args {
 *     (Optional) Default posts navigation arguments.
 *
 *     @type string 'before'             Content to prepend to the output. Default 'Previous'.
 *     @type string 'after'              Content to append to the output. Default 'Next'.
 *     @type string 'prev_text'          Anchor text to display in the previous post link. Default ''.
 *     @type string 'next_text'          Anchor text to display in the next post link. Default ''.
 *     @type string 'screen_reader_text' Screen reader text for the nav element. Default 'Posts navigation'.
 *     @type string 'aria_label'         ARIA label text for the nav element. Default 'Pages'.
 *     @type string 'class'              Custom class for the nav element. Default 'page-break-navigation'.
 *     @type string 'type'               Link format. Can be 'list', 'select', or custom.
 *     @type string 'mid_size'           How many numbers to either side of the current pages. Default 2.
 *     @type string 'end_size'           How many numbers on either the start and the end list edges. Default 1.
 *     @type string 'number_before'      A string to appear before the page number.
 *     @type string 'number_after'       A string to append after the page number.
 *     @type string 'add_args'           An array of query args to add.
 *     @type string 'add_fragment'       A string to append to each link.
 * }
 * @return string Markup for posts links.
 */
function get_the_posts_navigation( array $args = array() ): string {
	return \wpinc\navi\get_the_posts_navigation( $args );
}


// -----------------------------------------------------------------------------


/**
 * Adds page navigation shortcodes.
 */
function add_page_navigation_shortcode(): void {
	\wpinc\navi\add_page_navigation_shortcode();
}

/**
 * Adds page list shortcode.
 *
 * @param string $post_type Post type.
 * @param string $taxonomy  Taxonomy.
 * @param array  $args      Arguments for get_post_list.
 */
function add_post_list_shortcode( string $post_type, string $taxonomy = '', array $args = array() ): void {
	\wpinc\navi\add_post_list_shortcode( $post_type, $taxonomy, $args );
}
