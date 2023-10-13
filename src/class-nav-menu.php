<?php
/**
 * Nav Menu
 *
 * @package Wpinc Navi
 * @author Takuto Yanagida
 * @version 2023-10-13
 */

namespace wpinc\navi;

require_once __DIR__ . '/assets/url.php';

/**
 * Navigation menu.
 *
 * @psalm-suppress UnusedClass
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
	 * @var bool
	 */
	protected static $do_cache = false;

	/**
	 * An array of custom post types to archive slug.
	 *
	 * @var array<string, string>
	 */
	protected static $custom_post_type_archive = array();

	/**
	 * An array of used ids of menu items.
	 *
	 * @var int[]
	 */
	protected static $used_ids = array();

	/**
	 * Enable cache of navigation menus.
	 */
	public static function enable_cache(): void {
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
	public static function add_custom_post_type_archive( string $post_type, string $slug ): void {
		self::$custom_post_type_archive[ $post_type ] = $slug;
	}

	/**
	 * Anchored page IDs.
	 *
	 * @var int[]
	 */
	protected $anchored_page_ids;

	/**
	 * Object types can be current.
	 *
	 * @var string[]
	 */
	protected $object_types_can_be_current = array();

	/**
	 * Home URL.
	 *
	 * @var string
	 */
	protected $home_url;

	/**
	 * Filter function for titles.
	 *
	 * @var callable
	 */
	protected $title_filter;

	/**
	 * Filter function for contents.
	 *
	 * @var callable
	 */
	protected $content_filter;

	/**
	 * Whether to output ID of groups.
	 *
	 * @var bool
	 */
	protected $do_echo_group_id;

	/**
	 * Current URL.
	 *
	 * @var string
	 */
	protected $cur_url;

	/**
	 * Relations of parent ID to child IDs.
	 *
	 * @var array<int, \WP_Post[]>
	 */
	protected $p_to_cs;

	/**
	 * Menu item attributes.
	 *
	 * @var array<int, string[]>
	 */
	protected $id_to_ats;

	/**
	 * Menu ID.
	 *
	 * @var int|null
	 */
	protected $menu_id;

	/** phpcs:ignore
	 * Constructs a navigation menu.
	 *
	 * phpcs:ignore
	 * @param array{
	 *     menu_location    : string,
	 *     anchored_page_ids: int[],
	 *     object_types     : string[]|string,
	 *     home_url         : string,
	 *     title_filter     : callable,
	 *     content_filter   : callable,
	 *     do_echo_group_id : bool
	 * } $args An array of arguments.
	 *
	 * $args {
	 *     An array of arguments.
	 *
	 *     @type string          'menu_location'     Menu location.
	 *     @type int[]           'anchored_page_ids' Page ids for making their links of menu items anchor links.
	 *     @type string[]|string 'object_types'      Object types.
	 *     @type string          'home_url'          Home URL.
	 *     @type callable        'title_filter'      Filter function for titles. Default 'esc_html'.
	 *     @type callable        'content_filter'    Filter function for contents. Default 'esc_html'.
	 *     @type bool            'do_echo_group_id'  Whether to output ID of groups. Default true.
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
			'do_echo_group_id'  => true,
		);

		$this->anchored_page_ids = $args['anchored_page_ids'];
		if ( ! empty( $args['object_types'] ) ) {
			$this->object_types_can_be_current = is_array( $args['object_types'] ) ? $args['object_types'] : array( $args['object_types'] );
		}
		$this->home_url         = trailingslashit( $args['home_url'] );
		$this->title_filter     = $args['title_filter'];
		$this->content_filter   = $args['content_filter'];
		$this->do_echo_group_id = $args['do_echo_group_id'];

		$this->cur_url = trailingslashit( (string) strtok( \wpinc\get_request_url( true ), '?' ) );

		$mis       = $this->get_all_items_( $args['menu_location'] );
		$c2p       = $this->get_child_to_parent_( $mis );
		$p2cs      = $this->get_parent_to_children_( $mis );
		$ancestors = $this->get_ancestors_of_current_( $mis, $c2p );
		$id2ats    = $this->get_attributes_( $mis, $c2p, $p2cs, $ancestors );

		$this->p_to_cs   = $p2cs;
		$this->id_to_ats = $id2ats;
	}


	// -------------------------------------------------------------------------


	/**
	 * Retrieves all menu items.
	 *
	 * @param string $menu_location Menu location.
	 * @return \WP_Post[] Menu items.
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
	 * @psalm-suppress UndefinedMagicPropertyFetch
	 *
	 * @param \WP_Post[] $mis Menu items.
	 * @return array<int, \WP_Post[]> Relations of parent ID to child IDs.
	 */
	protected function get_parent_to_children_( array $mis ): array {
		$p2cs = array();
		foreach ( $mis as $mi ) {
			$p = (int) $mi->menu_item_parent;  // @phpstan-ignore-line
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
	 * @psalm-suppress UndefinedMagicPropertyFetch
	 *
	 * @param \WP_Post[] $mis Menu items.
	 * @return array<int, int> Relations of child ID to parent ID.
	 */
	protected function get_child_to_parent_( array $mis ): array {
		$ret = array();
		foreach ( $mis as $mi ) {
			$ret[ $mi->ID ] = (int) $mi->menu_item_parent;  // @phpstan-ignore-line
		}
		return $ret;
	}

	/**
	 * Collects ancestor items of the current.
	 *
	 * @access protected
	 * @psalm-suppress UndefinedMagicPropertyFetch
	 *
	 * @param \WP_Post[]      $mis Menu items.
	 * @param array<int, int> $c2p Relations of child ID to parent ID.
	 * @return int[] A pair of an array of menu ancestors and Array of menu item ID to its parent ID.
	 */
	protected function get_ancestors_of_current_( array $mis, array $c2p ): array {
		$post_type     = ( is_archive() || is_single() ) ? get_post_type() : null;
		$cur_tx        = null;
		$cur_term_id   = null;
		$cur_term_urls = array();

		if ( is_tax() ) {
			$qo = get_queried_object();
			if ( $qo instanceof \WP_Term ) {
				$cur_tx      = $qo->taxonomy;
				$cur_term_id = $qo->term_id;
			}
		}
		if ( is_single() ) {
			$txs = get_object_taxonomies( (string) $post_type );
			$pid = get_the_ID();
			if ( $pid ) {
				$ts = array();
				foreach ( $txs as $tx ) {
					$p_ts = wp_get_post_terms( $pid, $tx );
					if ( ! is_wp_error( $p_ts ) ) {
						foreach ( $p_ts as $t ) {
							$ts[ $t->term_taxonomy_id ] = $t;

							while ( 0 !== $t->parent ) {
								$t = get_term( $t->parent, $t->taxonomy );
								if ( $t instanceof \WP_Term ) {
									$ts[ $t->term_taxonomy_id ] = $t;
								} else {
									break;
								}
							}
						}
					}
				}
				$cur_term_urls = array();
				foreach ( $ts as $t ) {
					$link = get_term_link( $t );
					if ( is_string( $link ) ) {
						$cur_term_urls[] = $link;
					}
				}
			}
		}
		$archive_slug = self::$custom_post_type_archive[ $post_type ] ?? null;
		$has_curs     = array();

		foreach ( $mis as $mi ) {
			$slugs     = explode( '/', untrailingslashit( $mi->url ) );  // @phpstan-ignore-line
			$last_slug = array_pop( $slugs );

			$url = user_trailingslashit( strip_fragment_from_url( $mi->url ) );  // @phpstan-ignore-line
			if (
				$archive_slug === $last_slug ||
				( $mi->object === $cur_tx && $mi->object_id === $cur_term_id ) || // @phpstan-ignore-line
				$mi->object === $post_type || // @phpstan-ignore-line
				in_array( $url, $cur_term_urls, true )
			) {
				$has_curs[] = $mi->ID;
			}
			if ( $this->is_current_( $mi ) ) {
				$has_curs[] = (int) $mi->menu_item_parent;  // @phpstan-ignore-line
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
	 * @global \WP_Post $post
	 * @psalm-suppress UndefinedMagicPropertyFetch
	 *
	 * @param \WP_Post[]             $mis       Menu items.
	 * @param array<int, int>        $c2p       Relations of child ID to parent ID.
	 * @param array<int, \WP_Post[]> $p2cs      Relations of parent ID to child IDs.
	 * @param int[]                  $ancestors Ancestor menu item IDs.
	 * @return array<int, string[]> Relation of item ID to its attributes.
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
		$page_parent    = 0;
		$page_ancestors = array();
		if ( is_page() ) {
			$page_parent    = $post->post_parent;
			$page_ancestors = $post->ancestors;
		}
		$id2ats = array();
		$pas    = array();
		foreach ( $mis as $mi ) {
			$ats = array();

			$url = trailingslashit( $mi->url );  // @phpstan-ignore-line
			if ( $url === $this->home_url ) {
				$ats[] = self::CLS_HOME;
			}
			if ( $this->is_current_( $mi ) ) {
				$ats[] = self::CLS_CURRENT;
			}
			if ( isset( $p_has_current[ $mi->ID ] ) ) {
				$ats[] = self::CLS_MENU_PARENT;
			}
			if ( in_array( $mi->ID, $ancestors, true ) ) {
				$ats[] = self::CLS_MENU_ANCESTOR;
			}
			if ( is_page() ) {
				if ( $page_parent === (int) $mi->object_id ) {  // @phpstan-ignore-line
					$ats[] = self::CLS_PAGE_PARENT;
				}
				if ( in_array( (int) $mi->object_id, $page_ancestors, true ) ) {  // @phpstan-ignore-line
					$ats[] = self::CLS_PAGE_ANCESTOR;
					$pas[] = $mi;
				}
			}
			if ( '#' === $mi->url ) {  // @phpstan-ignore-line
				if ( mb_ereg_match( '-+', $mi->title ) ) {  // @phpstan-ignore-line
					$ats[] = self::CLS_SEPARATOR;
				} else {
					$ats[] = self::CLS_GROUP;
				}
			}
			$id2ats[ $mi->ID ] = $ats;
		}
		foreach ( $pas as $pa ) {
			$id = $c2p[ $pa->ID ];
			while ( 0 !== $id ) {
				if ( ! in_array( self::CLS_MP_ANCESTOR, $id2ats[ $id ], true ) ) {
					$id2ats[ $id ][] = self::CLS_MP_ANCESTOR;
				}
				$id = $c2p[ $id ] ?? 0;
			}
		}
		return $id2ats;
	}

	/**
	 * Checks whether the menu item is currently selected.
	 *
	 * @access protected
	 * @psalm-suppress UndefinedMagicPropertyFetch
	 *
	 * @param \WP_Post $mi Menu item.
	 * @return bool True if it is currently selected.
	 */
	protected function is_current_( \WP_Post $mi ): bool {
		$url = trailingslashit( strip_fragment_from_url( $mi->url ) );  // @phpstan-ignore-line
		if ( $url !== $this->cur_url ) {
			return false;
		}
		if (
			! empty( $this->object_types_can_be_current ) &&
			! in_array( $mi->object, $this->object_types_can_be_current, true )  // @phpstan-ignore-line
		) {
			return false;
		}
		return true;
	}


	// -------------------------------------------------------------------------


	/**
	 * Callback function for 'wp_update_nav_menu' action.
	 *
	 * @param int                       $menu_id   ID of the updated menu.
	 * @param array<string, mixed>|null $menu_data An array of menu data.
	 */
	public static function cb_wp_update_nav_menu_( int $menu_id, ?array $menu_data = null ): void {
		if ( is_array( $menu_data ) && isset( $menu_data['menu-name'] ) && is_string( $menu_data['menu-name'] ) ) {
			$menu = wp_get_nav_menu_object( $menu_data['menu-name'] );
			if ( $menu ) {
				$key = 'cache-menu-id-' . $menu->term_id;
				delete_transient( $key );
			}
		}
	}

	/**
	 * Callback function for 'save_post_page' action.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @param bool     $update  Whether this is an existing post being updated.
	 */
	public static function cb_save_post_page_( int $post_id, \WP_Post $post, bool $update ): void {
		if ( $update ) {
			foreach ( get_nav_menu_locations() as $menu_name ) {
				$menu = wp_get_nav_menu_object( $menu_name );
				if ( $menu ) {
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
	 * @return \WP_Post[] Array of menu items, otherwise false.
	 */
	public static function get_nav_menu_items( $id ): array {
		if ( self::$do_cache ) {
			$key   = 'cache-menu-id-' . $id;
			$items = get_transient( $key );
			if ( is_array( $items ) ) {
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
	 * @return string[] Attributes.
	 */
	public function get_attributes( int $id ): array {
		return $this->id_to_ats[ $id ] ?? array();
	}

	/**
	 * Retrieves item IDs.
	 *
	 * @param int $parent_id (Optional) Parent ID. Default 0.
	 * @return int[] Item IDs.
	 */
	public function get_item_ids( int $parent_id = 0 ): array {
		if ( empty( $this->p_to_cs[ $parent_id ] ) ) {
			return array();
		}
		return array_column( $this->p_to_cs[ $parent_id ], 'ID' );
	}

	/**
	 * Retrieves item ID with the attributes.
	 *
	 * @param int      $parent_id  (Optional) Parent ID. Default 0.
	 * @param string[] $attributes (Optional) Attributes. Default empty.
	 * @return int|null Item ID.
	 */
	public function get_item_id( int $parent_id = 0, array $attributes = array() ): ?int {
		if ( ! is_page() ) {
			return null;
		}
		$mis = $this->p_to_cs[ $parent_id ] ?? array();

		foreach ( $mis as $mi ) {
			$id = $mi->ID;
			if ( empty( $this->p_to_cs[ $id ] ) ) {
				continue;
			}
			$ats = $this->id_to_ats[ $id ];
			if ( ! empty( array_intersect( $attributes, $ats ) ) ) {
				return $id;
			}
		}
		return null;
	}

	/**
	 * Checks whether the parent has any children.
	 *
	 * @param int $parent_id (Optional) Parent ID. Default 0.
	 * @return bool True if it has children.
	 */
	public function has_items( int $parent_id = 0 ): bool {
		return ! empty( $this->p_to_cs[ $parent_id ] );
	}

	/**
	 * Checks whether the parent has any grandchildren, or one of the children of the parent has any children.
	 *
	 * @param int $parent_id (Optional) Parent ID. Default 0.
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


	/** phpcs:ignore
	 * Displays menu items.
	 *
	 * phpcs:ignore
	 * @param array{
	 *     before?          : string,
	 *     after?           : string,
	 *     before_by_depth? : array<int, string>,
	 *     after_by_depth?  : array<int, string>,
	 *     id?              : int,
	 *     depth?           : int,
	 *     group_tag_name?  : string,
	 *     title_filter?    : callable,
	 *     content_filter?  : callable,
	 *     do_echo_group_id?: bool
	 * } $args An array of arguments.
	 *
	 * $args {
	 *     An array of arguments.
	 *
	 *     @type string             'before'           Content to prepend to the output. Default ''.
	 *     @type string             'after'            Content to append to the output. Default ''.
	 *     @type array<int, string> 'before_by_depth'  Content to prepend to the output by depth. Default array().
	 *     @type array<int, string> 'after_by_depth'   Content to append to the output by depth. Default array().
	 *     @type int                'id'               Parent ID. Default 0.
	 *     @type int                'depth'            Hierarchy depth. Default 1.
	 *     @type string             'group_tag_name'   Tag name of group items.
	 *     @type callable           'title_filter'     Filter function for titles.
	 *     @type callable           'content_filter'   Filter function for contents.
	 *     @type bool               'do_echo_group_id' Whether to output ID of groups.
	 * }
	 */
	public function echo_items( array $args = array() ): void {
		$args += array(
			'before'           => '<ul class="menu">',
			'after'            => '</ul>',
			'before_by_depth'  => array(),
			'after_by_depth'   => array(),
			'id'               => 0,
			'depth'            => 1,
			'group_tag_name'   => 'label',
			'title_filter'     => $this->title_filter,
			'content_filter'   => $this->content_filter,
			'do_echo_group_id' => $this->do_echo_group_id,
		);
		$this->echo_items_( $args['id'], 1, $args );
	}

	/** phpcs:ignore
	 * Displays menu items.
	 *
	 * @access protected
	 *
	 * @param int    $parent_id Parent ID.
	 * @param int    $depth     Hierarchy depth.
	 * phpcs:ignore
	 * @param array{
	 *     before          : string,
	 *     after           : string,
	 *     before_by_depth : array<int, string>,
	 *     after_by_depth  : array<int, string>,
	 *     id              : int,
	 *     depth           : int,
	 *     group_tag_name  : string,
	 *     title_filter    : callable,
	 *     content_filter  : callable,
	 *     do_echo_group_id: bool
	 * } $args An array of arguments.
	 */
	protected function echo_items_( int $parent_id, int $depth, array $args ): void {
		if ( empty( $this->p_to_cs[ $parent_id ] ) ) {
			return;
		}
		$mis = $this->p_to_cs[ $parent_id ];

		if ( isset( $args['before_by_depth'][ $depth ] ) ) {
			echo $args['before_by_depth'][ $depth ];  // phpcs:ignore
		} else {
			echo $args['before'];  // phpcs:ignore
		}
		foreach ( $mis as $mi ) {
			$ats  = $this->id_to_ats[ $mi->ID ];
			$item = $this->get_item_( $mi, $ats, $args );
			if ( $depth < $args['depth'] && ! empty( $this->p_to_cs[ $mi->ID ] ) ) {
				echo $item['before'] . "\n";  // phpcs:ignore
				$this->echo_items_( $mi->ID, $depth + 1, $args );
				echo $item['after'];  // phpcs:ignore
			} else {
				echo $item['before'] . $item['after'];  // phpcs:ignore
			}
		}
		if ( isset( $args['after_by_depth'][ $depth ] ) ) {
			echo $args['after_by_depth'][ $depth ];  // phpcs:ignore
		} else {
			echo $args['after'];  // phpcs:ignore
		}
	}

	/** phpcs:ignore
	 * Makes list item markup.
	 *
	 * @access protected
	 * @psalm-suppress UndefinedMagicPropertyFetch
	 *
	 * @param \WP_Post $mi   Menu item.
	 * @param string[] $ats  Attributes of the menu item.
	 * phpcs:ignore
	 * @param array{
	 *     before          : string,
	 *     after           : string,
	 *     before_by_depth : array<int, string>,
	 *     after_by_depth  : array<int, string>,
	 *     id              : int,
	 *     depth           : int,
	 *     group_tag_name  : string,
	 *     title_filter    : callable,
	 *     content_filter  : callable,
	 *     do_echo_group_id: bool
	 * } $args An array of arguments.
	 * @return array<string, string> Array of markup.
	 */
	protected function get_item_( \WP_Post $mi, array $ats, array $args ): array {
		$ats[] = "menu-item-{$mi->ID}";
		if ( ! empty( $mi->classes ) ) {
			$ats = array_merge( $ats, $mi->classes );
		}
		$cls = implode( ' ', $ats );

		$id_at = '';
		if ( ! in_array( $mi->ID, self::$used_ids, true ) ) {
			$id_at            = " id=\"menu-item-{$mi->ID}\"";
			self::$used_ids[] = $mi->ID;
		}

		$li_at    = $id_at . " class=\"$cls\"";
		$title    = $args['title_filter']( $mi->title, $mi );  // @phpstan-ignore-line
		$cont     = $args['content_filter']( trim( $mi->post_content ) );
		$cont_div = empty( $cont ) ? '' : "<div class=\"description\">$cont</div>";

		if ( 'post_type_archive' === $mi->type ) {  // @phpstan-ignore-line
			$pto = get_post_type_object( $mi->object );  // @phpstan-ignore-line
			if ( $pto && $pto->labels->archives === $mi->title ) {  // @phpstan-ignore-line
				$title = apply_filters( 'post_type_archive_title', $pto->labels->archives, $mi->object );  // @phpstan-ignore-line
				$title = $args['title_filter']( $title, $mi );
			}
		}
		$before = "<li$li_at>";
		if ( in_array( self::CLS_SEPARATOR, $ats, true ) ) {
			$before .= '<div></div>';
		} elseif ( in_array( self::CLS_GROUP, $ats, true ) ) {
			$tag = $args['group_tag_name'];
			$ida = '';
			if ( 'label' === $tag ) {
				$ida = $args['do_echo_group_id'] ? " for=\"panel-{$mi->ID}-ctrl\"" : '';
			} elseif ( in_array( $tag, array( 'button', 'span', 'div' ), true ) ) {
				$ida = $args['do_echo_group_id'] ? " data-panel=\"panel-{$mi->ID}-ctrl\"" : '';
			}
			$before .= "<$tag$ida>$title$cont_div</$tag>";
		} else {
			$obj_id = (int) $mi->object_id;  // @phpstan-ignore-line
			if ( in_array( $obj_id, $this->anchored_page_ids, true ) ) {
				$href = esc_url( "#post-$obj_id" );
			} else {
				$href = esc_url( $mi->url );  // @phpstan-ignore-line
			}
			$target  = esc_attr( $mi->target );  // @phpstan-ignore-line
			$before .= "<a href=\"$href\" target=\"$target\">$title$cont_div</a>";
		}
		$after = "</li>\n";
		return compact( 'before', 'after' );
	}
}
