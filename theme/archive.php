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

	$insight_posts = get_posts(
		array(
			'post_type'      => 'insight',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$formatted_insights = array_map(
		static function ( $insight ) {
			$categories   = get_the_category( $insight->ID );
			$thumbnail_id = get_post_thumbnail_id( $insight );
			$image        = $thumbnail_id
				? array(
					'url' => wp_get_attachment_image_url( $thumbnail_id, 'large' ),
					'alt' => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
				)
				: null;

			return array(
				'id'      => $insight->ID,
				'eyebrow' => $categories ? $categories[0]->name : '',
				'category_slugs' => array_map(
					static function ( $category ) {
						return $category->slug;
					},
					$categories
				),
				'heading' => get_the_title( $insight ),
				'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $insight ) ), 16, '…' ),
				'image'   => $image,
				'date'    => get_the_date( 'j M Y', $insight ),
				'button'  => array(
					'url'    => get_permalink( $insight ),
					'title'  => 'Read insight',
					'target' => '',
				),
				'compact_heading' => true,
				'link_content'    => true,
			);
		},
		$insight_posts
	);

	$featured_insights = array_slice( $formatted_insights, 0, 3 );
	$archive_insights  = array_slice( $formatted_insights, 3 );
	$archive_categories = array();

	foreach ( $archive_insights as $insight ) {
		foreach ( $insight['category_slugs'] as $category_slug ) {
			$category = get_category_by_slug( $category_slug );

			if ( $category ) {
				$archive_categories[ $category_slug ] = $category->name;
			}
		}
	}

	asort( $archive_categories, SORT_NATURAL | SORT_FLAG_CASE );

	$context['insights_options']    = $insights_options;
	$context['featured_insights']  = $featured_insights;
	$context['archive_insights']   = $archive_insights;
	$context['insight_categories'] = $archive_categories;
}

$context['posts'] = Timber::get_posts();

Timber::render( $templates, $context );
