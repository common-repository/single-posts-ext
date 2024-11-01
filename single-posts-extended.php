<?php

/*
Plugin Name: Single Posts Ext
Plugin URI: https://wordpress.org/plugins/network-posts-extended/
Description: Network Posts Extended plugin enables you to share posts over WP Multi Site network.  You can display on any blog in your network the posts selected by taxonomy from any blogs including main.
Version: 1.0.0
Author: John Cardell, Superfrontender
Author URI: https://www.johncardell.com
Text Domain: SinglePosts
Domain Path: /language
*/

if ( realpath( __FILE__ ) == realpath( $_SERVER['SCRIPT_FILENAME'] ) ) {
	exit( 'Please don\'t access this file directly.' );
}
define( 'SINGLE_POSTS_MAIN_PLUGIN_FILE', __FILE__ );

use SinglePosts\Components\db\SinglePostsQuery;
use SinglePosts\Components\db\SinglePostsReviewQuery;
use SinglePosts\Components\db\SinglePostsWPMLQuery;
use SinglePosts\Components\SinglePostsDBQuery;
use SinglePosts\Components\SinglePostsShortcodeContainer;
use SinglePosts\Components\SinglePostsThumbnailManager;
use SinglePosts\db\SinglePostsCategoryQuery;

function single_posts_path( $file ) {
	return plugin_dir_path( __FILE__ ) . $file;
}


require_once single_posts_path( 'single-posts-init.php' );

function single_posts_shortcode( $atts ) {

	if( is_string( $atts ) ){
		$atts = array();
	}

	if ( ! ( is_single() || is_singular() || is_page() ) ) {
		$atts['paginate'] = false;
	}

	global $wpdb;
	$db_manager = SinglePostsDBQuery::new_instance( $wpdb );

	$use_single_images_folder = get_option( 'use_single_images_folder', false );

	$shortcode_mgr = SinglePostsShortcodeContainer::newInstance( $atts );

	if ( ! empty( $_GET ) ) {
		$shortcode_mgr->add_attributes( $_GET );
	}

########  OUTPUT STAFF  ####################

	$titles_only = $shortcode_mgr->get_boolean( 'titles_only' );

	$thumbnail = $shortcode_mgr->get_boolean( 'thumbnail' );

	$paginate = $shortcode_mgr->get_boolean( 'paginate' );

	$auto_excerpt = $shortcode_mgr->get_boolean( 'auto_excerpt' );

	$show_author = $shortcode_mgr->get_boolean( 'show_author' );

	$full_text = $shortcode_mgr->get_boolean( 'full_text' );

	$prev_next = $shortcode_mgr->get_boolean( 'prev_next' );

	$random = $shortcode_mgr->get_boolean( 'random' );

	/* my updates are finished here */

	$price_woocommerce = false;

	$price_estore = false;

	$key_name = 'exclude_link_title_posts';

	if ( $shortcode_mgr->has_value( 'exclude_link_title_posts' ) ) {
		$exclude_title_links = $shortcode_mgr->split_array( 'exclude_link_title_posts', ',' );
	}

	global $img_sizes;

	global $wpdb;

	$key_name = 'include_price';

	if ( $shortcode_mgr->has_value( $key_name ) ) {
		$is_match = $shortcode_mgr->is_match( $key_name, '/(\|)+/' );

		if ( $is_match ) {
			$exs = $shortcode_mgr->split_array( $key_name, '|' );
		} else {
			$exs = [ $shortcode_mgr->get( $key_name ) ];
		}

		foreach ( $exs as $ex ) {
			if ( $ex == 'woocommerce' ) {
				$price_woocommerce = true;
			} elseif ( $ex == 'estore' ) {
				$price_estore = true;
			}
		}
	}

	$woocommerce_installed = $db_manager->is_woocommerce_installed();

	$estore_installed = $db_manager->is_estore_installed();

	global $table_prefix;

	define( "WOOCOMMERCE", "woocommerce" );

	define( "WPESTORE", "estore" );

	/* below is my updates */

	$page = get_query_var( 'paged' );

	if ( ! $page ) {
		$page = get_query_var( 'page' );
	}

	if ( ! $page ) {
		$page = 1;
	}

	$is_paginate = $page > 1 && $paginate;

	## Getting posts

	$postdata = array();

	$prices = array();


	$posts_query = new SinglePostsQuery();

	if ( $shortcode_mgr->has_value( 'days' ) ) {
		$days = $shortcode_mgr->get( 'days' );
		$posts_query->set_days( $days );
	}

	if ( $shortcode_mgr->has_value( 'limit' ) ) {
		$value = $shortcode_mgr->get( 'limit' );
		$posts_query->set_limit( intval( $value ) );
	}

	if ( $shortcode_mgr->has_value( 'offset' ) ) {
		$value = $shortcode_mgr->get( 'offset' );
		$posts_query->set_limit( intval( $value ) );
	}

	if ( $shortcode_mgr->has_value( 'random' ) ) {
		$posts_query->set_random();
	}

	if ( $shortcode_mgr->has_value( 'filter_by_title_keywords' ) ) {
		$keywords = $shortcode_mgr->split_array( 'filter_by_title_keywords', ',' );
		$posts_query->set_title_keywords( $keywords );
	}

	if ( $shortcode_mgr->get_boolean( 'page_has_no_child' ) ) {
		$posts_query->without_children();
	}


	$meta_keys = [];


	if ( $shortcode_mgr->has_value( 'post_type' ) ) {
		$post_type_array = $shortcode_mgr->split_array( 'post_type', ',' );
		$posts_query->set_post_type( $post_type_array );
	}

	if ( $shortcode_mgr->has_value( 'order_post_by_acf_date' ) ) {
		$sort_values = $shortcode_mgr->split_array( 'order_post_by_acf_date', ' ' );
		$meta_keys[] = $sort_values[0];
	}

	if ( $shortcode_mgr->has_value( 'show_after_date' ) ||
	     $shortcode_mgr->has_value( 'exclude_all_past_events' ) ) {
		if ( $shortcode_mgr->has_value( 'show_after_date' ) ) {
			$filter_values = $shortcode_mgr->split_array( 'show_after_date', '::' );
			$filter_column = $filter_values[0];
			$date_str      = $filter_values[1];
			$date_format   = $shortcode_mgr->get( 'date_format' );
			$date          = DateTime::createFromFormat( $date_format, $date_str );
		} else {
			$filter_column = $shortcode_mgr->get( 'exclude_all_past_events' );
			$date          = new DateTime();
		}
		if ( $filter_column !== 'post_date' ) {
			$meta_keys[] = $filter_column;

			if ( $date ) {
				$posts_query->set_acf_date_filter_field( $filter_column );
				$posts_query->filter_after_acf_date( $date->format( 'Ymd' ) );
			}
		}
	}
	if ( $shortcode_mgr->has_value( 'show_before_date' ) ||
	     $shortcode_mgr->has_value( 'show_past_events' ) ) {
		if ( $shortcode_mgr->has_value( 'show_before_date' ) ) {
			$filter_values = $shortcode_mgr->split_array( 'show_past_events', '::' );
			$filter_column = $filter_values[0];
			$date_str      = $filter_values[1];

			$date_format = $shortcode_mgr->get( 'date_format' );
			$date        = DateTime::createFromFormat( $date_format, $date_str );
		} else {
			$filter_column = $shortcode_mgr->get( 'show_past_events' );
			$date          = new DateTime();
		}
		if ( $filter_column !== 'post_date' ) {
			$meta_keys[] = $filter_column;

			if ( $date ) {
				$posts_query->set_acf_date_filter_field( $filter_column );
				$posts_query->filter_before_acf_date( $date->format( 'Ymd' ) );
			}
		}
	}
	if ( $shortcode_mgr->has_value( 'show_for_today' ) ) {
		$filter_column = $shortcode_mgr->get( 'show_for_today' );
		if ( $filter_column !== 'post_date' ) {
			$meta_keys[] = $filter_column;
			$posts_query->set_acf_date_filter_field( $filter_column );
			$date = date( 'Ymd' );
			$posts_query->filter_acf_date( $date );
		}
	}
	if ( $shortcode_mgr->has_value( 'show_only_after_x_days_old' ) ) {
		$after_days = $shortcode_mgr->get_int( 'show_only_after_x_days_old' );
		$posts_query->filter_after_days( $after_days );
	}

	if ( $shortcode_mgr->has_value( 'include_post_meta' ) ) {
		$keys      = $shortcode_mgr->split_array( 'include_post_meta', ',' );
		$meta_keys = array_merge( $meta_keys, $keys );
		$meta_keys = array_unique( $meta_keys );
	}

	$posts_query->set_meta_keys( $meta_keys );

	$home_url = get_home_url();


	$include_posts_id = array();

	if ( $shortcode_mgr->has_value( 'include_post' ) ) {
		$include_posts_id = $shortcode_mgr->split_array( 'include_post', ',' );
	}

	$exclude_posts = array();
	if ( $shortcode_mgr->has_value( 'exclude_post' ) ) {
		$exclude_posts = $shortcode_mgr->split_array( 'exclude_post', ',' );
	}


	$category_query = new SinglePostsCategoryQuery( $wpdb );

	/*
	 * SinglePostsCategoryQuery doesn't have default taxonomy type filter
	 * but shortcode attributes do.
	 */
	if ( ! $shortcode_mgr->get_boolean( 'show_all_taxonomy_types' ) ) {
		if ( $shortcode_mgr->has_value( 'taxonomy_type' ) ) {
			$category_query->set_taxonomy_type( $shortcode_mgr->split_array( 'taxonomy_type', ',' ) );
		}
	}
	if ( $shortcode_mgr->get_boolean( 'show_all_taxonomies' ) ) {
		$taxonomy_posts = $category_query->get_posts( array() );
	} elseif ( $shortcode_mgr->has_value( 'taxonomy' ) ) {
		$taxonomy       = $shortcode_mgr->split_array( 'taxonomy', ',' );
		$taxonomy_posts = $category_query->get_posts( $taxonomy,
			$shortcode_mgr->get_boolean( 'must_include_categories' ) );
	}

	if ( isset( $taxonomy_posts ) ) {
		if ( $include_posts_id ) {
			$include_posts_id = array_intersect( $include_posts_id, $taxonomy_posts );
		} else {
			$include_posts_id = $taxonomy_posts;
		}
	}
	$include_posts_id = single_posts_filter_empty_values( $include_posts_id );

	if ( $shortcode_mgr->has_value( 'taxonomy' ) &&
	     empty( $include_posts_id ) ) {
		$the_post = array();
	} else {
		$posts_query->include_posts( $include_posts_id );

		if ( $shortcode_mgr->has_value( 'exclude_taxonomy' ) ) {
			$exclude_taxonomy       = $shortcode_mgr->split_array( 'exclude_taxonomy', ',' );
			$exclude_taxonomy_posts = $category_query->get_posts( $exclude_taxonomy );
			$exclude_posts          = array_merge( $exclude_posts, $exclude_taxonomy_posts );
			$exclude_posts          = array_unique( $exclude_posts );
		}
		$exclude_posts = single_posts_filter_empty_values( $exclude_posts );
		$posts_query->exclude_posts( $exclude_posts );

		global $wpdb;
		if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
			$post_ids = $posts_query->get_ids( $wpdb );

			$translations = array();
			foreach ( $post_ids as $id ) {
				$type           = get_post_type( $id );
				$translations[] = apply_filters( 'wpml_object_id', $id, $type, true );
			}
			if ( ! empty( $translations ) ) {
				$translation_query = new SinglePostsQuery();
				$translation_query->include_posts( $translations );
				$translation_query->set_meta_keys( $meta_keys );
				if ( isset( $post_type_array ) ) {
					$translation_query->set_post_type( $post_type_array );
				}
				$the_post = $translation_query->get_posts( $wpdb );
			} else {
				$the_post = array();
			}
		} else {
			$the_post = $posts_query->get_posts( $wpdb );
		}
	}

	$count = count( $the_post );

	for ( $i = 0; $i < $count; $i ++ ) {
		if ( isset( $the_post[ $i ] ) ) {


			$item = $the_post[ $i ];
			/*
			 * Check whether $post contains meta value and set
			 * post field with name 'meta_key' and value 'meta_value'
			 */
			if ( isset( $item['meta_key'] ) && isset( $item['meta_value'] ) ) {
				$the_post[ $i ][ $item['meta_key'] ] = $item['meta_value'];
				unset( $the_post[ $i ]['meta_key'] );
				unset( $the_post[ $i ]['meta_value'] );
			}
			/*
			 * Search for $post duplications, set values to current $post and remove copies from array.
			 */
			for ( $j = $i + 1; $j < $count; $j ++ ) {
				if ( isset( $the_post[ $j ] ) ) {
					$next = $the_post[ $j ];
					if ( $next['ID'] === $item['ID'] ) {
						if ( isset( $next['meta_key'] ) && isset( $next['meta_value'] ) ) {
							$the_post[ $i ][ $next['meta_key'] ] = $next['meta_value'];
							unset( $the_post[ $j ] );
						}
					}
				}
			}
			if ( $the_post[ $i ]['post_type'] === 'product' ) {
				if ( defined( 'WOOCOMMERCE' ) ) {
					$id                           = $the_post[ $i ]['ID'];
					$the_post[ $i ]['categories'] = wp_get_post_terms( $id, 'product_cat' );
					$the_post[ $i ]['terms']      = wp_get_post_terms( $id, 'product_tag' );
				}
			} else {
				$the_post[ $i ]['categories'] = wp_get_post_categories( $item['ID'],
					array( 'fields' => 'all' ) );

				$the_post[ $i ]['terms'] = wp_get_post_terms( $item['ID'] );
			}
			$the_post[ $i ]['guid'] = get_permalink( $item['ID'] );

			if ( $shortcode_mgr->has_value( 'show_custom_taxonomies' ) ) {
				$show_custom_taxonomies = $shortcode_mgr->get_boolean( 'show_custom_taxonomies' );
				if ( $show_custom_taxonomies ) {
					$the_post[ $i ]['custom_taxonomies'] = single_posts_get_post_custom_taxonomies( $item['ID'] );
				} else {
					$custom_taxonomy                     = $shortcode_mgr->split_array( 'show_custom_taxonomies', ',' );
					$the_post[ $i ]['custom_taxonomies'] = single_posts_get_post_taxonomies( $item['ID'], $custom_taxonomy );
				}
			} else {
				$the_post[ $i ]['custom_taxonomies'] = single_posts_get_post_custom_taxonomies( $item['ID'] );
			}

			if ( $shortcode_mgr->get( 'domain_mapping' ) === 'home_url' ) {
				$site_url               = get_site_url();
				$the_post[ $i ]['guid'] = str_replace( $site_url, $home_url, $the_post[ $i ]['guid'] );
			}
		}
	}

	$postdata = array_merge_recursive( $postdata, $the_post );

	/* below is my updates */

	$order_by = "";

	$aorder = array();

	$aorder1 = array();

	if ( $shortcode_mgr->has_value( 'order_post_by_acf_date' ) ) {
		$tab_order_by1 = $shortcode_mgr->split_array( 'order_post_by_acf_date', ' ' );
		if ( strtoupper( $tab_order_by1[1] ) == "DESC" ) {
			$ordad1 = SORT_DESC;
		} else {
			$ordad1 = SORT_ASC;
		}
		$ordad0  = $tab_order_by1[0];
		$aorder1 = array( $tab_order_by1[0] => $ordad1 );
	}
	if ( $shortcode_mgr->has_value( 'order_post_by' ) ) {
		$tab_order_by1 = $shortcode_mgr->split_array( 'order_post_by', ' ' );

		$ordad = ( $tab_order_by1[1] ) ? $tab_order_by1[1] : "ASC";

		$aorder = array( $tab_order_by1[0] => $ordad );

		if ( $tab_order_by1[0] == "date_order" ) {
			$ordad0 = "post_date";
		} elseif ( $tab_order_by1[0] == "alphabetical_order" ) {
			$ordad0 = "post_title";
		} elseif ( $tab_order_by1[0] == "id" ) {
			$ordad0 = "ID";
		} else {
			$ordad0      = $tab_order_by1[0];
			$meta_keys[] = $tab_order_by1[0];
		}

		if ( strtoupper( $tab_order_by1[1] ) == "DESC" ) {
			$ordad1 = SORT_DESC;
		} else {
			$ordad1 = SORT_ASC;
		}

		$aorder1 = array( $ordad0 => $ordad1 );
	}

	/* below is my updates */

	if ( ! $random ) {
		if ( ! isset( $ordad0 ) || $ordad0 == "" ) {
			usort( $postdata, "custom_sort" );
		} elseif ( isset( $aorder1 ) ) {
			$postdata = array_msort( $postdata, $aorder1 );
		}
	}

	/* exclude latest n elements from categories */
	if ( $shortcode_mgr->has_value( 'taxonomy_offsets' ) ) {

		$taxonomy_offsets = $shortcode_mgr->split_array( 'taxonomy_offsets', ',' );

		if ( $shortcode_mgr->has_value( 'taxonomy_offset_names' ) ) {
			$skipped_categories = $shortcode_mgr->split_array( 'taxonomy_offset_names', ',' );
		} elseif ( $shortcode_mgr->has_value( 'taxonomy' ) ) {
			$skipped_categories = $shortcode_mgr->split_array( 'taxonomy', ',' );
		} else {
			$skipped_categories = [];
		}

		if ( count( $skipped_categories ) > 0 ) {
			$skipped = [];
			$tmp     = [];

			$taxonomy_offsets = array_slice( $taxonomy_offsets, 0, count( $skipped_categories ) );

			$skipped_categories = array_map( function ( $cat_name ) {
				$name = str_replace( '%', '', preg_quote( $cat_name ) );

				return '/\b' . $name . '\b/i';
			}, $skipped_categories );

			for ( $i = 0; $i < count( $skipped_categories ); $i ++ ) {
				$skipped[ $i ] = 0;
			}

			$taxonomy_offset_type = strtolower( $shortcode_mgr->get( 'taxonomy_offset_type' ) );
			for ( $k = 0; $k < count( $postdata ); $k ++ ) {
				$post  = $postdata[ $k ];
				$found = false;
				for ( $i = 0; $i < count( $skipped_categories ); $i ++ ) {
					if ( isset( $taxonomy_offsets[ $i ] ) ) {
						$to_skip = $taxonomy_offsets[ $i ];
					} else {
						$to_skip = 0;
					}
					if ( $skipped[ $i ] < $to_skip ) {
						if ( $taxonomy_offset_type === 'category' || $taxonomy_offset_type === 'any' ) {
							if ( $post['categories'] ) {
								foreach ( $post['categories'] as $category ) {
									if ( preg_match( $skipped_categories[ $i ], $category->name ) ) {
										$skipped[ $i ] ++;
										$found = true;
										$tmp[] = $k;
										break;
									}
								}
							}
						}
						if ( $found ) {
							break;
						}
						if ( $taxonomy_offset_type === 'tag' || $taxonomy_offset_type === 'any' ) {
							if ( $post['terms'] ) {
								foreach ( $post['terms'] as $term ) {
									if ( preg_match( $skipped_categories[ $i ], $term->name ) ) {
										$skipped[ $i ] ++;
										$found = true;
										$tmp[] = $k;
										break;
									}
								}
							}
						}
					}
					if ( $found ) {
						break;
					}
				}

				$all_skipped = true;
				for ( $j = 0; $j < count( $skipped ); $j ++ ) {
					$all_skipped = $all_skipped && $skipped[ $j ] == $taxonomy_offsets[ $j ];
				}
				if ( $all_skipped ) {
					break;
				}
			}
			foreach ( $tmp as $skipped_idx ) {
				unset( $postdata[ $skipped_idx ] );
			}
		}
	}

	$list   = $shortcode_mgr->get( 'list' );

	if ( is_array( $postdata ) ) {
		$skip = 0;

		if ( $shortcode_mgr->has_value( 'number_latest_x_posts_excluded' ) ) {
			$skip = (int) $shortcode_mgr->get( 'number_latest_x_posts_excluded' );
		}

		if ( $paginate ) {

			$total_records = count( $postdata ) - $skip;

			$total_pages = ceil( $total_records / $list );
			$postdata    = array_slice( $postdata, ( $page - 1 ) * $list + $skip, $list );
		} /* below is my updates */

		else {
			$postdata = array_slice( $postdata, $skip, $list );
		}

		$colomn_data[0] = $postdata;
	}

	## OUTPUT

	if ( $shortcode_mgr->has_value( 'page_title_style' ) ) {
		$page_title_style = $shortcode_mgr->get( 'page_title_style' );

		?>
        <style type="text/css">
            h2.pagetitle {

            <?php echo  $page_title_style; ?> <?php //echo get_option('net-style'); ?>

            }
        </style>
		<?php

	}

	$html = '<div class="single-posts-menu">';

	if ( $shortcode_mgr->has_value( 'menu_name' ) ) {
		$menu = array(
			'menu'            => $shortcode_mgr->get( 'menu_name' ),
			'menu_class'      => $shortcode_mgr->get( 'menu_class' ),
			'container_class' => $shortcode_mgr->get( 'container_class' )
		);

		wp_nav_menu( $menu );
	}

	if ( $shortcode_mgr->has_value( 'link_open_new_window' ) ) {
		$link_open_new_window = strtolower( $shortcode_mgr->get( 'link_open_new_window' ) ) === 'true' ? true
			: $shortcode_mgr->split_array( 'link_open_new_window', ',' );
	} else {
		$link_open_new_window = false;
	}

	$html .= '</div>';

	if ( $postdata ) {

		$show_categories = $shortcode_mgr->get_boolean( 'show_categories' );

		$screen_classes = array( 'single-posts-screen' );
		if ( $shortcode_mgr->get_boolean( 'load_posts_dynamically' ) ) {
			$screen_classes[] = 'ajax_load';
		}
		$screen_classes = join( ' ', $screen_classes );

		if ( $shortcode_mgr->has_value( 'shortcode_id' ) ) {
			$shortcode_id = $shortcode_mgr->get( 'shortcode_id' );
			$html         .= '<div id="' . $shortcode_id . '" class="' . $screen_classes . '">';
		} else {
			$html .= '<div class="' . $screen_classes . '">';
		}

		$html .= '<div class="single-posts-block-wrapper current">';
		if ( $shortcode_mgr->has_value( 'post_height' ) ) {
			$post_height    = $shortcode_mgr->get( "post_height" );
			$height_content = "height: " . $post_height . "px;";
		} else {
			$height_content = "";
		}

		if ( $shortcode_mgr->has_value( 'title' ) ) {
			$html .= '<span class="single-posts-title">' . $shortcode_mgr->get( 'title' ) . '</span><br />';
		}

		$use_layout        = $shortcode_mgr->get( 'use_layout' );
		$use_inline_layout = isset( $use_layout ) && strtolower( $use_layout ) == "inline";

		foreach ( $colomn_data as $data ) {

			foreach ( $data as $key => $the_post ) {

				if ( $shortcode_mgr->get_boolean( 'show_rating' ) ) {
					global $wpdb;
					$the_post['rating'] = SinglePostsReviewQuery::get_post_avg_rating( $wpdb, $the_post['ID'] );
				}

				$open_link_in_new_tab = $link_open_new_window === true ||
				                        is_array( $link_open_new_window ) &&
				                        in_array( $the_post['ID'], $link_open_new_window ) ? ' target="_blank"' : '';

				$blog_details = get_blog_details();

				$blog_name = $blog_details->blogname;

				$blog_url = $blog_details->siteurl;

				if ( $shortcode_mgr->has_value( 'wrap_start' ) ) {
					$wrap_start = $shortcode_mgr->get( 'wrap_start' );
					$wrap_start = str_replace( '</p>', '', $wrap_start );
					$html       .= $wrap_start;
				}

				$content_classes_arr = array(
					'post-' . $the_post['ID']
				);
				foreach ( $the_post['categories'] as $category ) {
					$content_classes_arr[] = 'category-' . $category->slug;
				}
				foreach ( $the_post['terms'] as $term ) {
					$content_classes_arr[] = 'tag-' . $term->slug;
				}
				foreach ( $the_post['custom_taxonomies'] as $taxonomy ) {
					$content_classes_arr[] = 'custom-taxonomy-' . $taxonomy->slug;
				}

				$content_classes = join( ' ', $content_classes_arr );

				$html .= '<div class="single-posts-content ' . $content_classes . '" style="' . $height_content . '">';
				ob_start();
				if ( $use_inline_layout ) {
					include( POST_VIEWS_PATH . '/layout/layout_inline.php' );
				} else {
					include( POST_VIEWS_PATH . '/layout/layout_default.php' );
				}
				$html .= ob_get_clean();
				$html .= '</div>';//end of single-posts-content

				if ( $shortcode_mgr->has_value( 'wrap_end' ) ) {
					$wrap_end = html_entity_decode( $shortcode_mgr->get( 'wrap_end' ) );
					$wrap_end = str_replace( '<p>', '', $wrap_end );
					$html     .= $wrap_end;
				}
			}
			$html .= '<div class="end-single-posts-content"></div>';
		}

		if ( ( $paginate ) and ( $total_pages > 1 ) ) {
			$html .= '<div class="single-posts-paginate">';

			$big = 999999999;

			$url_format = is_single() ? '?page=%#%' : '?paged=%#%';

			$base = str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) );

			$pagination = paginate_links( array(

				'base' => $base,

				'format' => $url_format,

				'current' => $page,

				'total' => $total_pages,

				'show_all' => ! $shortcode_mgr->get_boolean( 'prev_next' ),

				'prev_next' => $shortcode_mgr->get_boolean( 'prev_next' ),

				'prev_text' => __( $shortcode_mgr->get( 'prev' ) ),

				'next_text' => __( $shortcode_mgr->get( 'next' ) ),

				'end_size' => $shortcode_mgr->get( 'end_size' ),

				'mid_size' => $shortcode_mgr->get( 'mid_size' )

			) );
			if ( is_single() ) {
				$url        = get_permalink();
				$pagination = single_posts_modify_pagination( $url, $pagination, $page );
			}
			$html .= $pagination;

			$html .= '</div>';
		}

		$html .= '</div>'; //end of single-posts-block-wrapper
		if ( $shortcode_mgr->get_boolean( 'load_posts_dynamically' ) ) {
			if ( $shortcode_mgr->has_value( 'posts_preloader_icon' ) ) {
				$preloader_url = $shortcode_mgr->get( 'posts_preloader_icon' );
			} else {
				$preloader_url = plugins_url( 'pictures/preloader.svg', __FILE__ );
			}
			if ( $shortcode_mgr->get_boolean( 'show_preloader_icon' ) ) {
				$html .= '<div class="single-posts-preloader hidden"><img alt="Loading" src="' . $preloader_url . '"></div>';
			} else {
				$html .= '<div class="single-posts-preloader hidden"></div>';
			}
		}
		$html .= '</div>'; // .single-posts-screen
	}

	return $html;
}

##########################################################

function single_posts_get_thumbnail( $post_id, $size, $image_class, $column ) {

	$use_single_images_folder = get_option( 'use_single_images_folder', false );
	$compress_images          = get_option( 'use_compressed_images', false );

	return SinglePostsThumbnailManager::get_thumbnail(
		$post_id,
		$size,
		array(
			'image_class'              => $image_class,
			'column'                   => $column,
			'use_images_single_folder' => $use_single_images_folder,
			'compress_images'          => $compress_images
		)
	);
}