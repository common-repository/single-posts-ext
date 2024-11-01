<?php/** * Created by PhpStorm. * User: Admin * Date: 31.05.2017 * Time: 11:06 */namespace SinglePosts\Components;class SinglePostsShortcodeContainer {	private static $DEFAULT_VALUES = array(		'show_title' => true,		'limit' => 999,		'number_latest_x_posts_excluded' => 0,		'taxonomy_offset_names' => '',		'taxonomy_offset_type' => 'category', // category|tag|any		'show_categories' => false,		'days' => 0,		'page_title_style' => '',		'title' => '',		'titles_only' => false,		'include_link_title' => false,		'exclude_link_title_posts' => '',		'link_open_new_window' => false,		'wrap_start' => null,		'wrap_end' => null,		'thumbnail' => false,		'default_thumbnail' => null,		'post_type' => 'post',		'exclude_post' => null,		'include_post' => null,		'title_length' => null,		'title_length_characters' => 999,		'taxonomy' => '',		'taxonomy_type' => 'category,tag',		'show_all_taxonomies' => false,		'show_all_taxonomy_types' => false,		'exclude_taxonomy' => '',		'hide_excerpt' => false,		'hide_source'  => false,		'include_price' => '',		'paginate' => false,		'pages' => null,		'list' => 10,		'excerpt_length' => null,		'excerpt_letters_length' => 400,		'auto_excerpt' => false,		'filter_excerpt' => false,		'use_shortcode_in_excerpt' => false,		'show_author' => false,		'full_text' => false,		'size' => 'thumbnail',		'image_class' => 'post-thumbnail',		'date_format' => 'n/j/Y',		'end_size' => '',		'mid_size' => '',		'prev_next' => true,		'prev' => '&laquo; Previous',		'next' => 'Next &raquo;',		'title_color' => '',		'text_color' => '',		'meta_info' => 'true',		'wrap_title_start' => '',		'wrap_title_end' => '',		'wrap_image_start' => '',		'wrap_image_end' => '',		'wrap_text_start' => '',		'wrap_text_end' => '',		'wrap_price_start' => '',		'wrap_price_end' => '',		'meta_width' => '100%',		'menu_name' => '',		'menu_class' => '',		'container_class' => '',		'post_height' => null,		'manual_excerpt_length' => null,		'manual_excerpt_letters_length' => null,		'random' => false,		'order_post_by' => '',		'use_image' => '',		'use_layout' => 'default', //there can be 2 values. if we use "default" then views/post_layout_default.php will		//be used otherwise if we use "inline" - views/post_layout_inline.php will be used		'align_thumbnail' => 'left', //this attribute can be "left" or "right",		'wrap_excerpt_start' => '',		'wrap_excerpt_end' => '',		'show_after_date' => '',		'show_before_date' => '',		'show_for_today' => '',		'exclude_all_past_events' => '',		'show_past_events' => '',		'show_order' => false,		'include_post_meta' => '',		'include_acf_fields' => false,		'hide_acf_labels' => false,        'taxonomy_offsets' => [],		'must_include_categories' => false,		'filter_by_title_keywords' => false,		'show_tags' => false,		'show_rating' => false,		'domain_mapping' => 'site_url',		'page_has_no_child' => false,		'order_post_by_acf_date' => false,		'acf_date_format' => false,		'read_more_text' => '',		'add_link_to_acf' => false,		'add_link_to_date' => false,		'show_category_icon' => false,		'show_tag_icon' => false,		'show_custom_taxonomies' => false,		'show_custom_taxonomy_icon' => false,		'load_posts_dynamically' => false,		'posts_preloader_icon' => '',		'show_preloader_icon'  => true,		'shortcode_id'  => null,		'show_only_after_x_days_old' => null	);	private $attributes = array();	public function get_shortcode_attributes() {		return $this->attributes;	}	public function set_shortcode_attributes( $atts ) {		$this->attributes = shortcode_atts( self::$DEFAULT_VALUES, $atts );	}	public function add_attributes( $atts ) {		$this->attributes = array_merge( $this->attributes, $this->prepare_array( $atts ) );	}	public function has_value( $name ) {		return isset( $this->attributes[ $name ] ) && ! empty( $this->attributes[ $name ] );	}	public function get( $name ) {		return $this->attributes[ $name ];	}	public function get_boolean( $name ) {		if( $this->has_value( $name ) ){			$value = $this->get( $name );			if( is_numeric( $value ) ){				return $value == 1;			}			if( is_string( $value ) ) {				return strtolower( $value ) == 'true';			}			else{				return $value == true;			}		}		return false;	}	public function is_match( $name, $pattern ) {		$has_value = $this->has_value( $name );		if ( $has_value ) {			$value = $this->get( $name );			return preg_match( $pattern, $value );		} else {			return false;		}	}	public function split_array( $name, $pattern ) {		$value = $this->get( $name );		return array_map( function($str){ return trim($str); }, mb_split( $pattern, $value ) );	}	public static function newInstance( $atts ) {		$mgr = new SinglePostsShortcodeContainer();		$mgr->set_shortcode_attributes( $atts );		return $mgr;	}	private function prepare_array( $data ) {		$new_data = [];		foreach ( $data as $key => $value ) {			$new_data[ $key ] = esc_sql( $value );		}		return $new_data;	}	public function get_int( string $attribute ): int {		return intval( $this->get( $attribute ) );	}}