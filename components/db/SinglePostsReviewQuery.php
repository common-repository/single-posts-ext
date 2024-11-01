<?php


namespace SinglePosts\Components\db;


class SinglePostsReviewQuery {

	public static function get_post_avg_rating( \wpdb $wpdb, $id ) {
		$table_prefix = $wpdb->prefix;
		$query = 'SELECT ROUND(AVG(rating),1) AS rating FROM ' . $table_prefix . 'reviews WHERE post_id=' . $id;
		$record = $wpdb->get_results( $query, ARRAY_A );
		return intval( $record[0]['rating'] );
	}
}