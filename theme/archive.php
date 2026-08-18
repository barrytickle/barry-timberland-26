<?php
/**
 * @package WordPress
 * @subpackage Timberland
 * @since Timberland 2.1.0
 */

$templates = array( 'archive.twig', 'index.twig' );

$context = Timber::context();

$context['title'] = 'Archive';
if ( is_day() ) {
	$context['title'] = 'Archive: ' . get_the_date( 'D M Y' );
} elseif ( is_month() ) {
	$context['title'] = 'Archive: ' . get_the_date( 'M Y' );
} elseif ( is_year() ) {
	$context['title'] = 'Archive: ' . get_the_date( 'Y' );
} elseif ( is_tag() ) {
	$context['title'] = single_tag_title( '', false );
} elseif ( is_category() ) {
	$context['title'] = single_cat_title( '', false );
	array_unshift( $templates, 'archive-' . get_query_var( 'cat' ) . '.twig' );
} elseif ( is_post_type_archive() ) {
	$context['title'] = post_type_archive_title( '', false );
	$post_type = get_query_var( 'post_type' );
	$post_type = is_array( $post_type ) ? reset( $post_type ) : $post_type;
	array_unshift( $templates, 'archive-' . $post_type . '.twig' );
}

if ( is_post_type_archive( 'insight' ) ) {
	$insights_options = function_exists( 'get_fields' )
		? ( get_fields( 'insights_options' ) ?: array() )
		: array();

	$featured_article = $insights_options['featured_article']['article'] ?? null;
	$featured_article = $featured_article instanceof WP_Post
		? $featured_article
		: get_post( $featured_article );

	if ( $featured_article instanceof WP_Post && 'insight' === $featured_article->post_type ) {
		$thumbnail_id = get_post_thumbnail_id( $featured_article );
		$categories   = get_the_category( $featured_article->ID );
		$image        = $thumbnail_id
			? array(
				'url' => wp_get_attachment_image_url( $thumbnail_id, 'large' ),
				'alt' => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
			)
			: null;

		$insights_options['featured_article'] = array(
			'eyebrow' => $categories ? $categories[0]->name : '',
			'heading' => get_the_title( $featured_article ),
			'excerpt' => get_the_excerpt( $featured_article ),
			'image'   => $image,
			'button'  => array(
				'url'    => get_permalink( $featured_article ),
				'title'  => 'Read insight',
				'target' => '',
			),
		);
	} else {
		$insights_options['featured_article'] = array();
	}

	$insight_posts = get_posts(
		array(
			'post_type'      => 'insight',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$insights_options['insights_group']['articles'] = array_map(
		static function ( $insight ) {
			$categories = get_the_category( $insight->ID );

			return array(
				'eyebrow' => $categories ? $categories[0]->name : '',
				'title'   => get_the_title( $insight ),
				'excerpt' => get_the_excerpt( $insight ),
				'href'    => get_permalink( $insight ),
			);
		},
		$insight_posts
	);
	$insights_options['insights_group']['cta_link'] = null;

	$context['insights_options'] = $insights_options;
}

$context['posts'] = Timber::get_posts();

Timber::render( $templates, $context );
