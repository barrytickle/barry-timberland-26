<?php
/**
 * @package WordPress
 * @subpackage Timberland
 * @since Timberland 2.1.0
 */

$context                     = Timber::context();
$context['not_found_fields'] = array(
	'heading_highlight' => '404',
	'heading'           => 'Not Found',
	'body'              => 'It seems you got a little bit lost.',
	'cta_link'          => array(
		'url'    => home_url( '/' ),
		'title'  => 'Back to homepage',
		'target' => '',
	),
);

Timber::render( '404.twig', $context );
