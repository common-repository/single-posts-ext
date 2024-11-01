<?php
############  SETUP  ####################
require_once single_posts_path('components/SinglePostsUtils.php');
require_once single_posts_path( 'components/db/SinglePostsCategoryQuery.php' );
require_once single_posts_path( 'components/db/SinglePostsQuery.php' );
require_once single_posts_path( 'components/db/SinglePostsReviewQuery.php' );
require_once single_posts_path('components/db/SinglePostsDBQuery.php');
require_once single_posts_path( 'components/db/SinglePostsWPMLQuery.php' );
require_once single_posts_path('components/SinglePostsShortcodeContainer.php');
require_once single_posts_path('components/SinglePostsSettings.php');
require_once single_posts_path( 'components/SinglePostsThumbnailManager.php' );

require_once single_posts_path('components/SinglePostsSettings.php');
require_once single_posts_path('components/SinglePostsTemplateRenderer.php');

use \SinglePosts\Components\SinglePostsSettings;
use SinglePosts\Components\SinglePostsTemplateRenderer;

define( 'DEFAULT_THUMBNAIL_WIDTH', 300 );
define( 'BASE_JS_PATH', plugins_url( '/single-posts-extended/js' ) );
define( 'POST_VIEWS_PATH', plugin_dir_path( __FILE__ ) . 'views/post' );
define( 'SINGLE_POSTS_VIEW_PATH', plugin_dir_path( __FILE__ ) . 'views' );

if( !defined( 'SINGLE_POST_TEST' ) ) {
	add_action( 'admin_init', array( SinglePostsSettings::class, 'register_settings' ) );

	SinglePostsTemplateRenderer::init( SINGLE_POSTS_VIEW_PATH );
	add_action( 'wp_enqueue_scripts', 'single_posts_add_js' );
	add_action( "plugins_loaded", "single_posts_load_translations" );

	add_action( 'admin_menu', array( SinglePostsSettings::class, 'add_toolpage' ) );
	add_action( 'admin_enqueue_scripts', 'single_posts_init_settings_page' );

	add_shortcode( 'single-posts', 'single_posts_shortcode' );
}

$plugin = plugin_basename( __FILE__ );

add_filter( "plugin_action_links_$plugin", array(
    \SinglePosts\Components\SinglePostsSettings::class,
    'plugin_settings_link'
) );


function single_posts_add_js(){
	wp_enqueue_script( 'single-posts-js', plugins_url( 'dist/single-posts-public.js', __FILE__ ), array(), '1.0.0', true );
}

function single_posts_load_translations() {
    register_uninstall_hook( __FILE__, 'net_shared_posts_uninstall' );
    if( get_option( 'load_plugin_styles', 1 ) ) {
        add_action( 'wp_enqueue_scripts', 'single_posts_add_stylesheet' );
    }
    load_plugin_textdomain( 'single-posts-extended', false, basename( dirname( __FILE__ ) ) . '/language' );
}

function single_posts_add_stylesheet() {
    wp_register_style( 'single_posts_css', plugins_url( '/css/net_posts_extended.css', __FILE__ ) );
    wp_enqueue_style( 'single_posts_css' );

	wp_register_style( 'single_posts_star_css', plugins_url( '/css/fontawesome-stars.css', __FILE__ ) );
	wp_enqueue_style( 'single_posts_star_css' );
}

function single_posts_init_settings_page() {
    if ( isset( $_GET['page'] ) && $_GET['page'] == 'single_posts_page' ) {
        wp_register_style( 'single_posts_admin_css', plugins_url( '/css/settings.css', __FILE__ ) );
        wp_enqueue_style( 'single_posts_admin_css' );
    }
}

function net_shared_posts_uninstall() {
    remove_shortcode( 'single-posts' );
}

function single_posts_url( $relative_url ){
	return plugins_url( $relative_url, __FILE__ );
}
