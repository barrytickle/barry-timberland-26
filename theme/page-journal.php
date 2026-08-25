<?php
/**
 * Journal page controller for personal Lifestyle insights.
 *
 * @package WordPress
 * @subpackage Timberland
 */

$context = Timber::context();
$context['post'] = Timber::get_post();
$lifestyle = get_category_by_slug( 'lifestyle' );
$journal_posts = $lifestyle
	? get_posts(
		array(
			'post_type' => 'insight',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'category' => $lifestyle->term_id,
		)
	)
	: array();

$context['journal_posts'] = array_map(
	static function ( $insight ) {
		$thumbnail_id = get_post_thumbnail_id( $insight );

		return array(
			'title' => get_the_title( $insight ),
			'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $insight ) ), 20, '…' ),
			'url' => get_permalink( $insight ),
			'image' => $thumbnail_id ?: null,
			'date' => get_the_date( 'j F Y', $insight ),
		);
	},
	$journal_posts
);

Timber::render( 'page-journal.twig', $context );
