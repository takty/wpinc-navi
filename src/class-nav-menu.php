<?php
/**
 * Nav Menu
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2022-01-16
 */

namespace wpinc\navi;

/**
 * Navigation menu.
 */
class Nav_Menu {

	const CLS_HOME          = 'home';
	const CLS_CURRENT       = 'current';
	const CLS_MENU_PARENT   = 'menu-parent';
	const CLS_MENU_ANCESTOR = 'menu-ancestor';
	const CLS_PAGE_PARENT   = 'page-parent';
	const CLS_PAGE_ANCESTOR = 'page-ancestor';
	const CLS_MP_ANCESTOR   = 'menu-ancestor-page-ancestor';
	const CLS_SEPARATOR     = 'separator';
	const CLS_GROUP         = 'group';

	const CACHE_EXPIRATION = DAY_IN_SECONDS;

	/**
	 * Whether the menu is cached.
	 *
	 * @var 1.0
	 */
	protected static $do_cache = false;

	/**
	 * An array of custom post types to archive slug.
	 *
	 * @var 1.0
	 */
	protected static $custom_post_type_archive = array();

	/**
	 * Enable cache of navigation menus.
	 */
	public static function enable_cache() {
		self::$do_cache = true;
		add_action( 'wp_update_nav_menu', array( '\wpinc\navi\Nav_Menu', 'cb_wp_update_nav_menu_' ), 10, 2 );
		add_action( 'save_post_page', array( '\wpinc\navi\Nav_Menu', 'cb_save_post_page_' ), 10, 3 );
	}

	/**
	 * Adds the archive slug of a custom post type.
	 *
	 * @param string $post_type Post type.
	 * @param string $slug      Slug of the archive page.
	 */
	public static function add_custom_post_type_archive( string $post_type, string $slug ) {
		self::$custom_post_type_archive[ $post_type ] = $slug;
	}

	/**
	 * Retrieves the current URL.
	 *
	 * @access protected
	 *
	 * @param bool $raw Whether the returned value is raw.
	 * @return string The current URL.
	 */
	protected static function get_current_url_( bool $raw = false ): string {
		// phpcs:disable
		$host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'];  // When reverse proxy exists.
		$req  = ( $raw && isset( $_SERVER['REQUEST_URI_ORIG'] ) ) ? $_SERVER['REQUEST_URI_ORIG'] : $_SERVER['REQUEST_URI'];
		// phpcs:enable
		return ( is_ssl() ? 'https://' : 'http://' ) . wp_unslash( $host ) . wp_unslash( $req );
	}

	/**
	 * Anchored page IDs.
	 *
	 * @var 1.0
	 */
	protected $anchored_page_ids;

	/**
	 * Object types can be current.
	 *
	 * @var 1.0
	 */
	protected $object_types_can_be_current;

	/**
	 * Home URL.
	 *
	 * @var 1.0
	 */
	protected $home_url;

	/**
	 * Filter function for titles.
	 *
	 * @var 1.0
	 */
	protected $title_filter;

	/**
	 * Filter function for contents.
	 *
	 * @var 1.0
	 */
	protected $content_filter;

	/**
	 * Current URL.
	 *
	 * @var 1.0
	 */
	protected $cur_url;

	/**
	 * Relations of parent ID to child IDs.
	 *
	 * @var 1.0
	 */
	protected $p_to_cs;

	/**
	 * Menu item attributes.
	 *
	 * @var 1.0
	 */
	protected $id_to_as;

	/**
	 * Menu ID.
	 *
	 * @var 1.0
	 */
	protected $menu_id;

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
	 */
	public function __construct( array $args ) {
		$args += array(
			'menu_location'     => '',
			'anchored_page_ids' => array(),
			'object_types'      => array(),
			'home_url'          => home_url(),
			'title_filter'      => 'esc_html',
			'content_filter'    => 'esc_html',
		);

		$this->anchored_page_ids = $args['anchored_page_ids'];
		if ( ! empty( $args['object_types'] ) ) {
			$this->object_types_can_be_current = is_array( $args['object_types'] ) ? $args['object_types'] : array( $args['object_types'] );
		}
		$this->home_url       = trailingslashit( $args['home_url'] );
		$this->title_filter   = $args['title_filter'];
		$this->content_filter = $args['content_filter'];

		$this->cur_url = trailingslashit( strtok( self::get_current_url_( true ), '?' ) );

		$mis       = $this->get_all_items_( $args['menu_location'] );
		$c2p       = $this->get_child_to_parent_( $mis );
		$p2cs      = $this->get_parent_to_children_( $mis );
		$ancestors = $this->get_ancestors_of_current_( $mis, $c2p );
		$id2as     = $this->get_attributes_( $mis, $c2p, $p2cs, $ancestors );

		$this->p_to_cs  = $p2cs;
		$this->id_to_as = $id2as;
	}


	// -------------------------------------------------------------------------


	/**
	 * Retrieves all menu items.
	 *
	 * @param string $menu_location Menu location.
	 * @return array Menu items.
	 */
	protected function get_all_items_( string $menu_location ): array {
		$ls = get_nav_menu_locations();
		if ( ! isset( $ls[ $menu_location ] ) ) {
			return array();
		}
		$menu = wp_get_nav_menu_object( $ls[ $menu_location ] );
		if ( false === $menu ) {
			return array();
		}
		$this->menu_id = $menu->term_id;
		return self::get_nav_menu_items( $menu->term_id );
	}

	/**
	 * Collects relations of parent ID to child IDs.
	 *
	 * @access protected
	 *
	 * @param array $mis Menu items.
	 * @return array Relations of parent ID to child IDs.
	 */
	protected function get_parent_to_children_( array $mis ): array {
		$p2cs = array();
		foreach ( $mis as $mi ) {
			$p = (int) $mi->menu_item_parent;
			if ( isset( $p2cs[ $p ] ) ) {
				$p2cs[ $p ][] = $mi;
			} else {
				$p2cs[ $p ] = array( $mi );
			}
		}
		return $p2cs;
	}

	/**
	 * Collects relations of child ID to parent ID.
	 *
	 * @access protected
	 *
	 * @param array $mis Menu items.
	 * @return array Relations of child ID to parent ID.
	 */
	protected function get_child_to_parent_( array $mis ): array {
		$ret = array();
		foreach ( $mis as $mi ) {
			$ret[ $mi->ID ] = (int) $mi->menu_item_parent;
		}
		return $ret;
	}

	/**
	 * Collects ancestor items of the current.
	 *
	 * @access protected
	 *
	 * @param array $mis Menu items.
	 * @param array $c2p Relations of child ID to parent ID.
	 * @return array A pair of an array of menu ancestors and Array of menu item ID to its parent ID.
	 */
	protected function get_ancestors_of_current_( array $mis, array $c2p ): array {
		$post_type     = ( is_archive() || is_single() ) ? get_post_type() : null;
		$cur_tx        = null;
		$cur_term_id   = null;
		$cur_term_urls = array();

		if ( is_tax() ) {
			$qo          = get_queried_object();
			$cur_tx      = $qo->taxonomy;
			$cur_term_id = $qo->term_id;
		}
		if ( is_single() ) {
			$txs = get_object_taxonomies( $post_type );
			$pid = get_the_ID();
			$ts  = array();
			foreach ( $txs as $tx ) {
				foreach ( wp_get_post_terms( $pid, $tx ) as $t ) {
					$ts[ $t->term_taxonomy_id ] = $t;

					while ( 0 !== $t->parent ) {
						$t = get_term( $t->parent, $t->taxonomy );

						$ts[ $t->term_taxonomy_id ] = $t;
					}
				}
			}
			$cur_term_urls = array_map( 'get_term_link', $ts );
		}
		$archive_slug = self::$custom_post_type_archive[ $post_type ] ?? null;
		$has_curs     = array();

		foreach ( $mis as $mi ) {
			$slugs     = explode( '/', untrailingslashit( $mi->url ) );
			$last_slug = array_pop( $slugs );
			if (
				$archive_slug === $last_slug ||
				( $mi->object === $cur_tx && $mi->object_id === $cur_term_id ) ||
				$mi->object === $post_type ||
				in_array( $mi->url, $cur_term_urls, true )  // TODO.
			) {
				$has_curs[] = $mi->ID;
			}
			if ( $this->is_current_( $mi ) ) {
				$has_curs[] = (int) $mi->menu_item_parent;
			}
		}
		$ret = array();
		foreach ( $has_curs as $id ) {
			while ( 0 !== $id ) {
				$ret[] = $id;
				$id    = $c2p[ $id ] ?? 0;
			}
		}
		return $ret;
	}

	/**
	 * Retrieves menu item attributes.
	 *
	 * @access protected
	 *
	 * @param array $mis       Menu items.
	 * @param array $c2p       Relations of child ID to parent ID.
	 * @param array $p2cs      Relations of parent ID to child IDs.
	 * @param array $ancestors Ancestor menu item IDs.
	 * @return array Relation of item ID to its attributes.
	 */
	protected function get_attributes_( array $mis, array $c2p, array $p2cs, array $ancestors ): array {
		$p_has_current = array();
		foreach ( $p2cs as $p => $cs ) {
			foreach ( $cs as $c ) {
				if ( $this->is_current_( $c ) ) {
					$p_has_current[ $p ] = true;
					break;
				}
			}
		}
		global $post;
		if ( is_page() ) {
			$page_parent    = $post->post_parent;
			$page_ancestors = $post->ancestors ?? array();
		}
		$id2as = array();
		$pas   = array();
		foreach ( $mis as $mi ) {
			$as = array();

			$url = trailingslashit( $mi->url );
			if ( $url === $this->home_url ) {
				$as[] = self::CLS_HOME;
			}
			if ( $this->is_current_( $mi ) ) {
				$as[] = self::CLS_CURRENT;
			}
			if ( isset( $p_has_current[ $mi->ID ] ) ) {
				$as[] = self::CLS_MENU_PARENT;
			}
			if ( in_array( $mi->ID, $ancestors, true ) ) {
				$as[] = self::CLS_MENU_ANCESTOR;
			}
			if ( is_page() ) {
				if ( $page_parent === (int) $mi->object_id ) {
					$as[] = self::CLS_PAGE_PARENT;
				}
				if ( in_array( (int) $mi->object_id, $page_ancestors, true ) ) {
					$as[]  = self::CLS_PAGE_ANCESTOR;
					$pas[] = $mi;
				}
			}
			if ( '#' === $mi->url ) {
				if ( mb_ereg_match( '-+', $mi->title ) ) {
					$as[] = self::CLS_SEPARATOR;
				} else {
					$as[] = self::CLS_GROUP;
				}
			}
			$id2as[ $mi->ID ] = $as;
		}
		foreach ( $pas as $pa ) {
			$id = $c2p[ $pa->ID ];
			while ( 0 !== $id ) {
				if ( ! in_array( self::CLS_MP_ANCESTOR, $id2as[ $id ], true ) ) {
					$id2as[ $id ][] = self::CLS_MP_ANCESTOR;
				}
				$id = $c2p[ $id ] ?? 0;
			}
		}
		return $id2as;
	}

	/**
	 * Checks whether the menu item is currently selected.
	 *
	 * @access protected
	 *
	 * @param \WP_Post $mi Menu item.
	 * @return bool True if it is currently selected.
	 */
	protected function is_current_( \WP_Post $mi ): bool {
		$url = trailingslashit( $mi->url );
		if ( $url !== $this->cur_url ) {
			return false;
		}
		if (
			! empty( $this->object_types_can_be_current ) &&
			! in_array( $mi->object, $this->object_types_can_be_current, true )
		) {
			return false;
		}
		return true;
	}


	// -------------------------------------------------------------------------


	/**
	 * Callback function for 'wp_update_nav_menu' hook.
	 *
	 * @param int        $menu_id   ID of the updated menu.
	 * @param array|null $menu_data An array of menu data.
	 */
	public static function cb_wp_update_nav_menu_( int $menu_id, ?array $menu_data = null ) {
		if ( is_array( $menu_data ) && isset( $menu_data['menu-name'] ) ) {
			$menu = wp_get_nav_menu_object( $menu_data['menu-name'] );
			if ( isset( $menu->term_id ) ) {
				$key = 'cache-menu-id-' . $menu->term_id;
				delete_transient( $key );
			}
		}
	}

	/**
	 * Callback function for 'save_post_page' hook.
	 *
	 * @param int     $post_ID Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public static function cb_save_post_page_( int $post_ID, WP_Post $post, bool $update ) {
		if ( $update ) {
			foreach ( get_nav_menu_locations() as $loc => $menu_name ) {
				$menu = wp_get_nav_menu_object( $menu_name );
				if ( isset( $menu->term_id ) ) {
					$key = 'cache-menu-id-' . $menu->term_id;
					delete_transient( $key );
				}
			}
		}
	}

	/**
	 * Retrieves all menu items of a navigation menu.
	 *
	 * @param int|string $id Menu ID, slug, name, or object.
	 * @return array|false Array of menu items, otherwise false.
	 */
	public static function get_nav_menu_items( $id ) {
		if ( self::$do_cache ) {
			$key   = 'cache-menu-id-' . $id;
			$items = get_transient( $key );
			if ( false !== $items ) {
				return $items;
			}
			$items = wp_get_nav_menu_items( $id );
			if ( $items ) {
				set_transient( $key, $items, self::CACHE_EXPIRATION );
			}
		} else {
			$items = wp_get_nav_menu_items( $id );
		}
		return $items ? $items : array();
	}


	// -------------------------------------------------------------------------


	/**
	 * Retrieves menu ID.
	 *
	 * @return int|null Menu ID.
	 */
	public function get_menu_id(): ?int {
		return $this->menu_id;
	}

	/**
	 * Retrieves item attributes.
	 *
	 * @param int $id Item ID.
	 * @return array Attributes.
	 */
	public function get_attributes( int $id ): array {
		return $this->id_to_as[ $id ] ?? array();
	}

	/**
	 * Retrieves item IDs.
	 *
	 * @param int $parent_id Parent ID.
	 * @return array Item IDs.
	 */
	public function get_item_ids( int $parent_id = 0 ): array {
		if ( empty( $this->p_to_cs[ $parent_id ] ) ) {
			return array();
		}
		return array_map(
			function ( $e ) {
				return $e->ID;
			},
			$this->p_to_cs[ $parent_id ]
		);
	}

	/**
	 * Retrieves item ID with the attributes.
	 *
	 * @param int   $parent_id  Parent ID.
	 * @param array $attributes Attributes.
	 * @return int|null Item ID.
	 */
	public function get_item_id( int $parent_id = 0, array $attributes ): ?int {
		if ( ! is_page() ) {
			return null;
		}
		$mis = $this->p_to_cs[ $parent_id ] ?? array();

		foreach ( $mis as $mi ) {
			$id = $mi->ID;
			if ( empty( $this->p_to_cs[ $id ] ) ) {
				continue;
			}
			$as = $this->id_to_as[ $id ];
			if ( ! empty( array_intersect( $attributes, $as ) ) ) {
				return $id;
			}
		}
		return null;
	}

	/**
	 * Checks whether the parent has any children.
	 *
	 * @param int $parent_id Parent ID.
	 * @return bool True if it has children.
	 */
	public function has_items( int $parent_id = 0 ): bool {
		return ! empty( $this->p_to_cs[ $parent_id ] );
	}

	/**
	 * Checks whether the parent has any grandchildren, or one of the children of the parent has any children.
	 *
	 * @param int $parent_id Parent ID.
	 * @return bool True if it has grandchildren.
	 */
	public function has_sub_items( int $parent_id = 0 ): bool {
		if ( empty( $this->p_to_cs[ $parent_id ] ) ) {
			return false;
		}
		$mis = $this->p_to_cs[ $parent_id ];
		foreach ( $mis as $mi ) {
			if ( ! empty( $this->p_to_cs[ $mi->ID ] ) ) {
				return true;
			}
		}
		return false;
	}


	// -------------------------------------------------------------------------


	/**
	 * Displays menu items.
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string   'before'         Content to prepend to the output. Default ''.
	 *     @type string   'after'          Content to append to the output. Default ''.
	 *     @type int      'id'             Parent ID. Default 0.
	 *     @type int      'depth'          Hierarchy depth. Default 1.
	 *     @type callable 'title_filter'   Filter function for titles.
	 *     @type callable 'content_filter' Filter function for contents.
	 * }
	 */
	public function echo_items( array $args = array() ) {
		$args += array(
			'before'         => '<ul class="menu">',
			'after'          => '</ul>',
			'id'             => 0,
			'depth'          => 1,
			'title_filter'   => $this->title_filter,
			'content_filter' => $this->content_filter,
		);
		$this->echo_items_( $args['before'], $args['after'], $args['id'], $args['depth'], $args['title_filter'], $args['content_filter'] );
	}

	/**
	 * Displays menu items.
	 *
	 * @access protected
	 *
	 * @param string   $before         Content to prepend to the output.
	 * @param string   $after          Content to append to the output.
	 * @param int      $parent_id      Parent ID.
	 * @param int      $depth          Hierarchy depth.
	 * @param callable $title_filter   Filter function for titles.
	 * @param callable $content_filter Filter function for contents.
	 */
	protected function echo_items_( string $before, string $after, int $parent_id, int $depth, $title_filter, $content_filter ) {
		if ( empty( $this->p_to_cs[ $parent_id ] ) ) {
			return;
		}
		$mis = $this->p_to_cs[ $parent_id ];

		echo $before;  // phpcs:ignore
		foreach ( $mis as $mi ) {
			$as   = $this->id_to_as[ $mi->ID ];
			$item = $this->get_item_( $mi, $as, $title_filter, $content_filter );
			if ( 1 < $depth && ! empty( $this->p_to_cs[ $mi->ID ] ) ) {
				echo $item['before'] . "\n";  // phpcs:ignore
				$this->echo_items_( $before, $after, $mi->ID, $depth - 1, $title_filter, $content_filter );
				echo $item['after'];  // phpcs:ignore
			} else {
				echo $item['before'] . $item['after'];  // phpcs:ignore
			}
		}
		echo $after;  // phpcs:ignore
	}

	/**
	 * Makes list item markup.
	 *
	 * @access protected
	 *
	 * @param \WP_Post $mi             Menu item.
	 * @param array    $as             Attributes of the menu item.
	 * @param callable $title_filter   Filter function for titles.
	 * @param callable $content_filter Filter function for contents.
	 * @return array Array of markup.
	 */
	protected function get_item_( \WP_Post $mi, array $as, $title_filter, $content_filter ): array {
		$as = is_array( $as ) ? $as : array();
		if ( ! empty( $mi->classes ) ) {
			$as = array_merge( $as, $mi->classes );
		}
		$cls      = implode( ' ', $as );
		$li_attr  = "id=\"menu-item-{$mi->ID}\"" . ( empty( $cls ) ? '' : " class=\"$cls\"" );
		$title    = $title_filter( $mi->title, $mi );
		$cont     = $content_filter( trim( $mi->post_content ) );
		$cont_div = empty( $cont ) ? '' : "<div class=\"description\">$cont</div>";

		if ( 'post_type_archive' === $mi->type ) {
			$pto = get_post_type_object( $mi->object );
			if ( $pto && $pto->labels->archives === $mi->title ) {
				$title = apply_filters( 'post_type_archive_title', $pto->labels->archives, $mi->object );
				$title = $title_filter( $title, $mi );
			}
		}

		if ( in_array( self::CLS_SEPARATOR, $as, true ) ) {
			$before = "<li $li_attr><div></div>";
		} elseif ( in_array( self::CLS_GROUP, $as, true ) ) {
			$before = "<li $li_attr><label for=\"panel-{$mi->ID}-ctrl\">$title$cont_div</label>";
		} else {
			$obj_id = (int) $mi->object_id;
			if ( in_array( $obj_id, $this->anchored_page_ids, true ) ) {
				$href = esc_url( "#post-$obj_id" );
			} else {
				$href = esc_url( $mi->url );
			}
			$target = esc_attr( $mi->target );
			$before = "<li $li_attr><a href=\"$href\" target=\"$target\">$title$cont_div</a>";
		}
		$after = "</li>\n";
		return compact( 'before', 'after' );
	}

}
