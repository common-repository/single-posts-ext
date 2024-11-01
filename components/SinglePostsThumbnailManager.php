<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 20.11.2018
 * Time: 12:52
 */
namespace SinglePosts\Components;

class SinglePostsThumbnailManager
{
    private static $instance;
    static $sizes;

    private function __construct(){}


    /**
     * @param $args array
     * @required $post_id int
     * @required $size_name string - size alias name
     * $args[image_class] string
     * $args[column] int - decide whether posts are divided to columns or not
     * $args[use_images_single_folder] boolean - this flag is important when all site images
     * are in one place
     * $args[compress_images] boolean
     * @return string - returns img html string
     */

    public static function get_thumbnail( $post_id, $size_name, array $args = [] ) {
            $current_blog = get_blog_details();
            $thumb_id = has_post_thumbnail($post_id);
            if (!$thumb_id) {
                return false;
            }
            $post_blog_details = get_blog_details();

            if (isset($args['image_class'])) {
                $image_class = esc_html($args['image_class']);
            } else {
                $image_class = '';
            }
            if (isset($args['column']) && is_int($args['column'])) {
                $column = (int)$args['column'];
            } else {
                $column = 1;
            }

            if ($column > 1) {
                $image_class = $image_class . " more-column";
            }

            $attrs = array('class' => $image_class);

            $use_compressed_images = isset($args['compress_images']) && $args['compress_images'];

            if ($use_compressed_images) {
                $img = get_the_post_thumbnail($post_id, $size_name, $attrs);
            } else {
                $img = get_the_post_thumbnail($post_id, $size_name, $attrs);
                $img = preg_replace('/(\bsizes\=.*?\")[\s\/]/', "", $img);
                $img = preg_replace('/(\bsrcset\=.*?\\")[\s\/]/', "", $img);
            }

            if (isset($args['use_images_single_folder']) &&
                $args['use_images_single_folder']) {
                $thumbcode = $img;
            } else {
            	if( strpos( $img, $post_blog_details->siteurl ) === false ) {
		            $thumbcode = str_replace( $current_blog->siteurl,
			            $post_blog_details->siteurl, $img );
	            }
            	else{
            		$thumbcode = $img;
	            }
            }

            return $thumbcode;
    }
}