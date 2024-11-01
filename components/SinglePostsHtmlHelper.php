<?php

namespace SinglePosts\Components;

class SinglePostsHtmlHelper {
	public static function get_date($date, $format){
		return '<span>' . $date->format($format) . '</span><br/>';
	}

	public static function create_link($url, $label, $open_in_new_tab = '', $class = ''){
		return '<a href="' . $url . '" ' . $open_in_new_tab . ' class="' . $class . '">' . $label . '</a>';
	}

	public static function create_term_link( int $id, string $name, string $open_in_new_tab = '', string $class = ''  ): string {
		$url = get_term_link( $id );
		return self::create_link($url, $name, $open_in_new_tab, $class);
	}

	public static function create_author_link($url, $author_label, $open_in_new_tab = '', $class = ''){
		$link = self::create_link($url, $author_label, $open_in_new_tab, $class);
		return '<span class="single-posts-author-label">' . __( 'Author', 'SinglePosts' ) . '</span> ' . $link;
	}

	public static function create_span($text, $class = '', $style = ''){
		return '<span class="' . $class . '" style="' . $style . '">' . $text . '</span>';
	}
}