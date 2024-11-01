<?php/** * Created by PhpStorm. * User: Andrew * Date: 26.07.2017 * Time: 10:03 */namespace SinglePosts\Components;class SinglePostsDBQuery {	protected $wpdb;	protected $wp_tables;	protected $estore_tables;	private function __construct( $db ) {		$this->wpdb = $db;	}	public function init() {		$this->wp_tables = array(			'posts_table' => $this->wpdb->prefix . "posts",			'term_relationship_table' => $this->wpdb->prefix . "term_relationships",			'term_taxonomy_table' => $this->wpdb->prefix . "term_taxonomy",			'term_table' => $this->wpdb->prefix . "terms",			'post_meta' => $this->wpdb->prefix . 'postmeta'		);		$this->estore_tables = array(			'product_table' => $this->wpdb->prefix . "wp_eStore_tbl",			'category_table' => $this->wpdb->prefix . "wp_eStore_cat_tbl",			'category_relationships' => $this->wpdb->prefix . "wp_eStore_cat_prod_rel_tbl"		);	}	public function get_category_post_ids( $categories, $include_posts = null ) {		$result_ids = array();		if ( is_array( $categories ) ) {			$cat_arr = array();			foreach ( $categories as $category ) {				$cat_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT term_id FROM {$this->wp_tables['term_table']} WHERE LOWER(slug) LIKE LOWER('%s') ", trim( $category ) ) );				if ( $cat_id ) {					$cat_arr[] = $cat_id;				}			}			$cat_arr = array_unique( $cat_arr );			$taxonomy_arr = array();			foreach ( $cat_arr as $cat_id ) {				$tax_id = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT term_taxonomy_id FROM {$this->wp_tables['term_taxonomy_table']} WHERE  term_id = %s", $cat_id ) );				if ( $tax_id ) {					$taxonomy_arr[] = $tax_id;				}			}			$include = '';			if ( $include_posts ) {				$include = format_inclusion( $this->wp_tables['term_relationship_table'], 'object_id', $include_posts );				$include = " AND $include";			}			foreach ( $taxonomy_arr as $tax_id ) {				$post_ids = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT object_id AS ID FROM {$this->wp_tables['term_relationship_table']} WHERE term_taxonomy_id = %d $include",					$tax_id ), ARRAY_A );				if ( $post_ids ) {					$result_ids = array_merge_recursive( $result_ids, $post_ids );				}			}		}		return $result_ids;	}	public function get_post_categories( $post_id ) {		$terms_table              = $this->wp_tables['term_table'];		$terms_relationship_table = $this->wp_tables['term_relationship_table'];		$terms_taxonomy           = $this->wp_tables['term_taxonomy_table'];		return $this->wpdb->get_results( "SELECT $terms_table.name as cat_name, $terms_table.term_id as cat_id								  FROM $terms_table								  LEFT JOIN $terms_relationship_table ON $terms_relationship_table.term_taxonomy_id = $terms_table.term_id								  LEFT JOIN $terms_taxonomy ON $terms_taxonomy.term_id = $terms_table.term_id								  WHERE $terms_relationship_table.object_id = $post_id AND $terms_taxonomy.taxonomy = 'category'", ARRAY_A );	}	public function get_posts( $is_random, $paginate, $post_type_search, $ids, $old, $limit, array $meta_keys ) {		$PostsTable          = $this->wp_tables['posts_table'];		$MetaTable           = $this->wp_tables['post_meta'];		$meta_full_condition = '';		if ( count( $meta_keys ) ) {			$meta_key_conditions = [];			foreach ( $meta_keys as $key ) {				$meta_key_conditions[] = "$MetaTable.meta_key = '$key'";			}			$meta_full_condition = 'AND (' . join( ' OR ', $meta_key_conditions ) . ')';			$join_query          = " LEFT JOIN $MetaTable ON ($MetaTable.post_id = $PostsTable.ID)";			$meta_columns        = ", $MetaTable.meta_key, $MetaTable.meta_value ";		} else {			$join_query   = "";			$meta_columns = "";		}		if ( $is_random ) {			if ( $paginate ) {				return $this->wpdb->get_results( $this->wpdb->prepare(					"SELECT $PostsTable.ID, $PostsTable.post_title, $PostsTable.post_excerpt, $PostsTable.post_content, $PostsTable.post_author, $PostsTable.post_date,                     $PostsTable.post_type $meta_columns				 FROM $PostsTable $join_query WHERE $PostsTable.post_status = %s $ids  AND $post_type_search $meta_full_condition $old  $limit", 'publish' ), ARRAY_A );			} else {				return $this->wpdb->get_results( $this->wpdb->prepare(					"SELECT $PostsTable.ID, $PostsTable.post_title, $PostsTable.post_excerpt, $PostsTable.post_content, $PostsTable.post_author,                         $PostsTable.post_date, $PostsTable.post_type $meta_columns				 FROM $PostsTable $join_query  WHERE $PostsTable.post_status = %s $ids  AND $post_type_search $meta_full_condition $old ORDER BY RAND() $limit", 'publish' ), ARRAY_A );			}		} else {			return $this->wpdb->get_results( $this->wpdb->prepare(				"SELECT $PostsTable.ID, $PostsTable.post_title, $PostsTable.post_excerpt, $PostsTable.post_content, $PostsTable.post_author, $PostsTable.post_date,                  $PostsTable.post_type $meta_columns			 FROM $PostsTable $join_query WHERE $PostsTable.post_status = %s $ids  AND $post_type_search $meta_full_condition $old $limit", 'publish' ), ARRAY_A );		}	}	public function get_formatted_estore_category_post_ids( $categories, $type = 'include', $include_posts = null ) {		$ids = $this->get_estore_category_post_ids( $categories, $include_posts );		$column_name = 'id';		if ( $type == 'include' ) {			return format_inclusion( $this->estore_tables['product_table'], $column_name, $ids );		} else {			return format_exclusion( $this->estore_tables['product_table'], $column_name, $ids );		}	}	public function get_estore_category_post_ids( $taxonomy, $include_posts = null ) {		global $wpdb;		if ( strpos( $taxonomy, ',' ) > 0 ) {			$categories = explode( ',', $taxonomy );		} else {			$categories = [ $taxonomy ];		}		$estore_cat_array = array();		$result_ids = array();		foreach ( $categories as $category ) {			$cat_id = $wpdb->get_var( $wpdb->prepare( "SELECT cat_id FROM {$this->estore_tables['category_table']} WHERE LOWER(cat_name) = LOWER('%s') ", trim( $category ) ) );			if ( $cat_id ) {				$estore_cat_array[] = $cat_id;			}		}		$include = '';		if ( $include_posts ) {			$include = format_inclusion( $this->estore_tables['category_relationships'], 'prod_id', $include_posts );			$include = " AND $include";		}		foreach ( $estore_cat_array as $estore_category ) {			$estore_post_ids = $wpdb->get_results( $wpdb->prepare( "SELECT prod_id FROM {$this->estore_tables['category_relationships']} WHERE cat_id = %s $include", $estore_category ), ARRAY_A );			if ( ! empty( $estore_post_ids ) ) {				$result_ids = array_merge_recursive( $result_ids, $estore_post_ids );			}		}		return $result_ids;	}	public function get_estore_products( $is_random, $paginate, $formatted_ids = '', $limit = '' ) {		if ( ! empty( $formatted_ids ) ) {			$estore_ids = ' WHERE ' . $formatted_ids;		}		$EStoreTable = $this->estore_tables['product_table'];		if ( $is_random ) {			if ( $paginate ) {				return $this->wpdb->get_results( "SELECT $EStoreTable.id AS ID,'estore' AS post_type, $EStoreTable.name AS post_title, $EStoreTable.price AS price,                                                         $EStoreTable.description AS post_content, $EStoreTable.author_id AS post_author,                                                         $EStoreTable.product_url AS guid, $EStoreTable.thumbnail_url                                                  FROM $EStoreTable $estore_ids $limit;", ARRAY_A );			} else {				return $this->wpdb->get_results( "SELECT $EStoreTable.id AS ID,'estore' AS post_type, $EStoreTable.name AS post_title, $EStoreTable.price AS price,                                                         $EStoreTable.description AS post_content, $EStoreTable.author_id AS post_author, $EStoreTable.product_url AS guid,                                                         $EStoreTable.thumbnail_url                                                  FROM $EStoreTable $estore_ids ORDER BY RAND() $limit;", ARRAY_A );			}		} else {			return $this->wpdb->get_results( "SELECT $EStoreTable.id AS ID,'estore' AS post_type, $EStoreTable.name AS post_title, $EStoreTable.price AS price,                                                     $EStoreTable.description AS post_content, $EStoreTable.author_id AS post_author, $EStoreTable.product_url AS guid,                                                     $EStoreTable.thumbnail_url                                              FROM $EStoreTable $estore_ids $limit;", ARRAY_A );		}	}	public function is_estore_installed() {		$result = $this->wpdb->get_results( "SHOW TABLES LIKE '" . $this->wpdb->base_prefix . "wp_estore_tbl'", ARRAY_A );		return count( $result ) == 1;	}	public function is_woocommerce_installed() {		$result = $this->wpdb->get_results( "SHOW TABLES LIKE '" . $this->wpdb->base_prefix . "woocommerce_termmeta'", ARRAY_A );		return count( $result ) == 1;	}	public function create_orderby_query_path( $column ) {		return " ORDER BY $column ";	}	public function create_by_date_query_path( $column, $days ) {		return "$column >= DATE_SUB(CURRENT_DATE(), INTERVAL $days DAY)";	}	public function get_skipped_ids( $categories, $count ) {		$excluded = array();		if ( $count > 0 ) {			foreach ( $categories as $category ) {				$posts = $this->get_category_post_ids( array( $category ) );				$posts_count = count( $posts );				if ( $posts_count > $count ) {					$temp = array_slice( $posts, count( $posts ) - $count );				} else {					$temp = $posts;				}				$excluded = array_merge( $excluded, $temp );			}		}		return $excluded;	}	public static function new_instance( $db ) {		$instance = new SinglePostsDBQuery( $db );		$instance->init();		return $instance;	}}