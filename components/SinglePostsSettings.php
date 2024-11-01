<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 01.06.2018
 * Time: 10:22
 */

namespace SinglePosts\Components;
require_once 'SinglePostsTemplateRenderer.php';

class SinglePostsSettings {

###################  TOOL PAGE  #########################

	public static function print_page() {
		$data                             = [];

		$data['nonce']                    = wp_create_nonce( 'single_posts_page-options' );
		$data['pages']                    = get_option( 'hide_readmore_link_pages' );
		$data['hide_all_readmore_links']  = checked( get_option( 'hide_all_readmore_links' ), 1, false);
		$data['use_single_images_folder'] = checked( get_option( 'use_single_images_folder' ), 1, false);
		$data['use_compressed_images']    = checked(get_option( 'use_compressed_images' ), 1, false);
		$data['load_plugin_styles']        = checked( get_option( 'load_plugin_styles', 1 ), 1, false );

		echo \SinglePosts\Components\SinglePostsTemplateRenderer::render(  '/settings.html', $data);
	}

	public static function plugin_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=single_posts_page">Settings</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	public static function add_toolpage() {
		add_options_page( 'Single Posts Ext', 'Single Posts Ext', 'manage_options', 'single_posts_page', array( self::class, 'print_page') );
	}

	public static function register_settings(){
		add_option_whitelist( [
			'single_posts_page' => [
				'hide_readmore_link_pages', 'hide_all_readmore_links',
				'use_single_images_folder', 'use_compressed_images',
				'load_plugin_styles'
			]]);
		register_setting( 'single_posts_page', 'hide_readmore_link_pages' );
		register_setting( 'single_posts_page', 'hide_all_readmore_links' );
		register_setting( 'single_posts_page', 'use_single_images_folder' );
		register_setting( 'single_posts_page', 'use_compressed_images' );
		register_setting( 'single_posts_page', 'load_plugin_styles' );
	}
}