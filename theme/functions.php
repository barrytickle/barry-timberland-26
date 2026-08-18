<?php
/**
 * @package WordPress
 * @subpackage Timberland
 * @since Timberland 2.1.0
 */

use Twig\TwigFunction;
use Twig\TwigFilter;
// use BarryTimberHelpers; // Commented out as it is not defined

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/theme/src/custom-functions.php';
use BarryTimberHelpers\BarryTimberHelpers;

BarryTimberHelpers::init();

// use function BarryTimberHelpers\has_class_name;

Timber\Timber::init();
Timber::$dirname    = array( 'views', 'blocks' );
Timber::$autoescape = false;


class Timberland extends Timber\Site {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_filter( 'script_loader_tag', array( $this, 'load_frontend_script_as_module' ), 10, 2 );
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
		add_filter( 'timber/twig', array( $this, 'add_twig_functions' ) );
		add_action( 'block_categories_all', array( $this, 'block_categories_all' ) );
		add_action( 'acf/init', array( $this, 'acf_register_blocks' ) );
		add_action( 'acf/init', array( $this, 'register_options_pages' ) );
		add_action( 'after_setup_theme', array( $this, 'enqueue_editor_styles' ), 20 );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_editor_scripts' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'admin_menu', array( $this, 'hide_default_posts_menu' ) );
		add_action( 'admin_menu', array( $this, 'add_view_insights_menu_item' ) );
		add_action( 'admin_bar_menu', array( $this, 'hide_default_posts_admin_bar_item' ), 999 );

		parent::__construct();
	}

	public function load_frontend_script_as_module( $tag, $handle ) {
		if ( 'main' !== $handle ) {
			return $tag;
		}

		return str_replace( '<script ', '<script type="module" ', $tag );
	}

	public function add_twig_functions( $twig ) {
		$twig->addFunction( new TwigFunction( 'check_url_match', array( $this, 'check_url_match' ) ) );
		$twig->addFunction( new TwigFunction( 'to_snake_case', array( $this, 'to_snake_case' ) ) );
		$twig->addFilter( new TwigFilter( 'nl2br', 'nl2br' ) );
		$twig->addFilter(
			new TwigFilter(
				'strip_wp_classes',
				'timberland_strip_wp_classes',
				array( 'is_safe' => array( 'html' ) )
			)
		);
		return $twig;
	}

	public function check_url_match( $link ) {
		$current_path = wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		$current_path = trailingslashit( $current_path ?: '/' );

		$link_path = wp_parse_url( $link, PHP_URL_PATH );

		if ( $link_path === null || $link_path === false || $link_path === '' ) {
			$link_path = trailingslashit( $link );
		} else {
			$link_path = trailingslashit( $link_path );
		}

		if ( $current_path === $link_path ) {
			return true;
		}

		// Highlight parent nav items on sub-pages, e.g. /services/cro/ → Services.
		if ( $link_path !== '/' && str_starts_with( $current_path, $link_path ) ) {
			return true;
		}

		return false;
	}

	public function add_to_context( $context ) {
		global $post;

		$context['site'] = $this;

		// Block previews call Timber::context() from acf_block_render_callback.
		// Skip the full context build here — wrap_non_acf_blocks() re-renders
		// every block in the post, which recursively triggers this callback again.
		if ( ! empty( $GLOBALS['timberland_rendering_acf_block'] ) ) {
			$context['options'] = get_fields( 'options' );
			return $context;
		}

		$context['processed_content'] = ( $post instanceof WP_Post )
			? wrap_non_acf_blocks( $post->post_content )
			: '';

		$menus = wp_get_nav_menus();
		$context['menus'] = [];
		$context['all_posts'] = Timber::get_posts(array(
			'posts_per_page' => -1
		));
		$context['pathname'] = $_SERVER['REQUEST_URI'];

		$context['options'] = get_fields('options');
		foreach ($menus as $menu) {
			$context['menus'][$menu->slug] = Timber::get_menu($menu->term_id);
		}

		$context['header_cta'] = [];
		$header_menu = Timber::get_menu('header');
		if ($header_menu && !empty($header_menu->items)) {
			$context['header_cta'] = end($header_menu->items);
		}
		$context['header_menu'] = $header_menu;

		// Require block functions files
		foreach ( glob( __DIR__ . '/blocks/*/functions.php' ) as $file ) {
			require_once $file;
		}

		return $context;
	}

	public function add_to_twig( $twig ) {
		return $twig;
	}

	public function theme_supports() {
		add_theme_support( 'automatic-feed-links' );
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);
		add_theme_support( 'menus' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'editor-styles' );
	}

	public function wrap_non_acf_blocks($content) {
		$pattern = '/<!-- wp:(?!acf\/)[\s\S]*?-->([\s\S]*?)<!-- \/wp:[\s\S]*?-->/';
		$replacement = '<div class="custom-container">$0</div>';
		return preg_replace($pattern, $replacement, $content);
	}

	private function get_vite_environment() {
		if ( file_exists( get_template_directory() . '/../config.json' ) ) {
			$config = json_decode( file_get_contents( get_template_directory() . '/../config.json' ), true );
			return $config['vite']['environment'] ?? 'production';
		}

		return 'production';
	}

	private function get_vite_manifest() {
		$manifest_path = get_template_directory() . '/assets/dist/.vite/manifest.json';

		if ( ! file_exists( $manifest_path ) ) {
			return null;
		}

		return json_decode( file_get_contents( $manifest_path ), true );
	}

	public function enqueue_frontend_assets() {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-block-style' );
		wp_dequeue_script( 'jquery' );
		wp_dequeue_style( 'global-styles' );

		$vite_env = $this->get_vite_environment();
		$manifest = $this->get_vite_manifest();
		$dist_uri = get_template_directory_uri() . '/assets/dist';

		if ( is_array( $manifest ) && $vite_env === 'production' ) {
			$js_file = 'theme/assets/main.js';
			wp_enqueue_style( 'main', $dist_uri . '/' . $manifest[ $js_file ]['css'][0] );
			wp_enqueue_script(
				'main',
				$dist_uri . '/' . $manifest[ $js_file ]['file'],
				array(),
				'',
				array(
					'strategy'  => 'defer',
					'in_footer' => true,
				)
			);
			return;
		}

		if ( $vite_env === 'development' ) {
			if ( ! function_exists( 'vite_head_module_hook' ) ) {
				function vite_head_module_hook() {
					echo '<script type="module" crossorigin src="http://localhost:3000/@vite/client"></script>';
					echo '<script type="module" crossorigin src="http://localhost:3000/theme/assets/main.js"></script>';
				}
			}
			add_action( 'wp_head', 'vite_head_module_hook' );
		}
	}

	public function enqueue_editor_styles() {
		$vite_env = $this->get_vite_environment();
		$manifest = $this->get_vite_manifest();
		$dist_uri = get_template_directory_uri() . '/assets/dist';

		if ( is_array( $manifest ) && $vite_env === 'production' ) {
			$editor_js_file = 'theme/assets/editor.js';
			add_editor_style( $dist_uri . '/' . $manifest[ $editor_js_file ]['css'][0] );
			return;
		}

		if ( $vite_env === 'development' ) {
			// Prefer the built bundle when available — avoids mixed-content blocks when
			// the admin runs on https://*.local but Vite serves http://localhost:3000.
			if ( is_array( $manifest ) ) {
				$editor_js_file = 'theme/assets/editor.js';
				add_editor_style( $dist_uri . '/' . $manifest[ $editor_js_file ]['css'][0] );
			}

			// Live Tailwind rebuilds when Vite dev is running (http-only admin setups).
			add_editor_style( 'http://localhost:3000/theme/assets/styles/editor-style.scss' );
		}
	}

	public function enqueue_editor_scripts() {
		if ( ! is_admin() ) {
			return;
		}

		$vite_env = $this->get_vite_environment();

		// Only load block JS in production. Vite HMR + Alpine in the editor iframe
		// conflicts with Gutenberg's React runtime and can collapse block previews.
		if ( $vite_env !== 'production' ) {
			return;
		}

		$manifest = $this->get_vite_manifest();
		$dist_uri = get_template_directory_uri() . '/assets/dist';

		if ( ! is_array( $manifest ) ) {
			return;
		}

		$editor_js_file = 'theme/assets/editor.js';
		wp_enqueue_script(
			'theme-editor',
			$dist_uri . '/' . $manifest[ $editor_js_file ]['file'],
			array(),
			'',
			array(
				'strategy'  => 'defer',
				'in_footer' => false,
			)
		);
	}

	public function block_categories_all( $categories ) {
		return array_merge(
			array(
				array(
					'slug'  => 'heroes',
					'title' => __( 'Heroes' ),
					'icon'  => 'cover-image',
				),
				array(
					'slug'  => 'calls-to-action',
					'title' => __( 'Calls to Action' ),
					'icon'  => 'megaphone',
				),
				array(
					'slug'  => 'work-portfolio',
					'title' => __( 'Work & Portfolio' ),
					'icon'  => 'portfolio',
				),
				array(
					'slug'  => 'case-studies',
					'title' => __( 'Case Studies' ),
					'icon'  => 'media-document',
				),
				array(
					'slug'  => 'blog-insights',
					'title' => __( 'Blog & Insights' ),
					'icon'  => 'welcome-write-blog',
				),
				array(
					'slug'  => 'testimonials',
					'title' => __( 'Testimonials' ),
					'icon'  => 'format-quote',
				),
				array(
					'slug'  => 'social-proof',
					'title' => __( 'Social Proof' ),
					'icon'  => 'groups',
				),
				array(
					'slug'  => 'stats-credentials',
					'title' => __( 'Stats & Credentials' ),
					'icon'  => 'awards',
				),
				array(
					'slug'  => 'content-about',
					'title' => __( 'Content & About' ),
					'icon'  => 'admin-page',
				),
				array(
					'slug'  => 'images-media',
					'title' => __( 'Images & Media' ),
					'icon'  => 'format-image',
				),
				array(
					'slug'  => 'contact',
					'title' => __( 'Contact' ),
					'icon'  => 'email',
				),
				array(
					'slug'  => 'utility-pages',
					'title' => __( 'Utility Pages' ),
					'icon'  => 'admin-tools',
				),
				array(
					'slug'  => 'layout',
					'title' => __( 'Layout' ),
					'icon'  => 'grid-view',
				),
			),
			$categories
		);
	}

	public function register_options_pages() {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		acf_add_options_page(
			array(
				'page_title' => 'Site Settings',
				'menu_title' => 'Site Settings',
				'menu_slug'  => 'site-settings',
				'capability' => 'manage_options',
				'redirect'   => false,
				'icon_url'   => 'dashicons-admin-generic',
				'position'   => 61,
			)
		);

		acf_add_options_sub_page(
			array(
				'page_title'  => 'Insights Settings',
				'menu_title'  => 'Insights Settings',
				'menu_slug'   => 'insights-settings',
				'parent_slug' => 'edit.php?post_type=insight',
				'capability'  => 'edit_posts',
				'post_id'     => 'insights_options',
				'redirect'    => false,
			)
		);
	}

	/**
	 * Converts a string to snake_case.
	 *
	 * @param string $string The input string.
	 * @return string The snake_cased string.
	 */
	public function to_snake_case( $string ) {
		// Replace spaces and hyphens with underscores
		$string = preg_replace( '/[\s\-]+/', '_', $string );
		// Insert underscores before any uppercase char that's preceded by a lowercase or another uppercase followed by a lowercase
		$string = preg_replace( '/(?<=\w)([A-Z])/', '_$1', $string );
		// Convert to lowercase
		$string = strtolower( $string );
		// Remove any duplicate underscores
		$string = preg_replace( '/_+/', '_', $string );
		// Trim leading/trailing underscores
		$string = trim( $string, '_' );
		return $string;
	}

	public function register_post_types() {
		register_post_type(
			'project',
			array(
				'label'       => 'Projects',
				'labels'      => array(
					'name'          => 'Projects',
					'singular_name' => 'Project',
					'add_new_item'  => 'Add New Project',
					'edit_item'     => 'Edit Project',
					'all_items'     => 'All Projects',
					'search_items'  => 'Search Projects',
					'not_found'     => 'No projects found',
				),
				'public'      => true,
				'show_in_rest' => true,
				'menu_icon'   => 'dashicons-portfolio',
				'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
				'has_archive' => false,
				'rewrite'     => array( 'slug' => 'projects' ),
			)
		);

		register_post_type(
			'service',
			array(
				'label'       => 'Services',
				'labels'      => array(
					'name'          => 'Services',
					'singular_name' => 'Service',
					'add_new_item'  => 'Add New Service',
					'edit_item'     => 'Edit Service',
					'all_items'     => 'All Services',
					'search_items'  => 'Search Services',
					'not_found'     => 'No services found',
				),
				'public'      => true,
				'show_in_rest' => true,
				'menu_icon'   => 'dashicons-hammer',
				'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
				'has_archive' => false,
				'rewrite'     => array( 'slug' => 'services' ),
			)
		);

		register_post_type(
			'insight',
			array(
				'label'        => 'Insights',
				'labels'       => array(
					'name'          => 'Insights',
					'singular_name' => 'Insight',
					'add_new_item'  => 'Add New Insight',
					'edit_item'     => 'Edit Insight',
					'all_items'     => 'All Insights',
					'search_items'  => 'Search Insights',
					'not_found'     => 'No insights found',
				),
				'public'       => true,
				'show_in_rest' => true,
				'menu_icon'    => 'dashicons-lightbulb',
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'custom-fields' ),
				'taxonomies'   => array( 'category' ),
				'has_archive'  => true,
				'rewrite'      => array( 'slug' => 'insights' ),
			)
		);

		register_taxonomy(
			'project_category',
			'project',
			array(
				'label'        => 'Project Categories',
				'labels'       => array(
					'name'          => 'Project Categories',
					'singular_name' => 'Project Category',
					'all_items'     => 'All Categories',
					'edit_item'     => 'Edit Category',
					'add_new_item'  => 'Add New Category',
					'search_items'  => 'Search Categories',
				),
				'hierarchical' => true,
				'public'       => true,
				'show_in_rest' => true,
				'rewrite'      => array( 'slug' => 'project-category' ),
			)
		);

		foreach ( array( 'CRO & Experimentation', 'Web App', 'Website' ) as $category ) {
			if ( ! term_exists( $category, 'project_category' ) ) {
				wp_insert_term( $category, 'project_category' );
			}
		}
	}

	public function hide_default_posts_menu() {
		remove_menu_page( 'edit.php' );
	}

	public function add_view_insights_menu_item() {
		global $submenu;

		$parent_slug = 'edit.php?post_type=insight';
		$archive_url = get_post_type_archive_link( 'insight' );

		if ( ! $archive_url || ! isset( $submenu[ $parent_slug ] ) ) {
			return;
		}

		$submenu[ $parent_slug ][] = array(
			'View Insights',
			'edit_posts',
			$archive_url,
		);
	}

	public function hide_default_posts_admin_bar_item( $admin_bar ) {
		$admin_bar->remove_node( 'new-post' );
	}

	public function acf_register_blocks() {
		$blocks = array();

		foreach ( new DirectoryIterator( __DIR__ . '/blocks' ) as $dir ) {
			if ( $dir->isDot() ) {
				continue;
			}

			if ( file_exists( $dir->getPathname() . '/block.json' ) ) {
				$blocks[] = $dir->getPathname();
			}
		}

		asort( $blocks );

		foreach ( $blocks as $block ) {
			register_block_type( $block );
		}
	}
}

new Timberland();

/**
 * Hydrate reusable Insights Group rows from their selected Insight posts.
 * Manually entered row values remain available when no post is selected.
 */
function timberland_prepare_insights_group_fields( $fields ) {
	if ( empty( $fields['articles'] ) || ! is_array( $fields['articles'] ) ) {
		return $fields;
	}

	$fields['articles'] = array_map(
		static function ( $article ) {
			if ( empty( $article['article'] ) ) {
				return $article;
			}

			$insight = get_post( $article['article'] ?? null );

			if ( ! $insight instanceof WP_Post || 'insight' !== $insight->post_type ) {
				return $article;
			}

			$categories   = get_the_category( $insight->ID );
			$thumbnail_id = get_post_thumbnail_id( $insight );
			$image        = $thumbnail_id
				? array(
					'url' => wp_get_attachment_image_url( $thumbnail_id, 'large' ),
					'alt' => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
				)
				: null;

			return array_merge(
				$article,
				array(
					'eyebrow' => $categories ? $categories[0]->name : '',
					'title'   => get_the_title( $insight ),
					'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $insight ) ), 24, '...' ),
					'href'    => get_permalink( $insight ),
					'image'   => $image,
				)
			);
		},
		$fields['articles']
	);

	return $fields;
}

/**
 * Don't edit this one
 */
function acf_block_render_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$GLOBALS['timberland_rendering_acf_block'] = true;
	$block_post = $post_id ? get_post( $post_id ) : get_queried_object();
	$use_placeholders = $is_preview || ( $block_post instanceof WP_Post && 'elements' === $block_post->post_name );

	$context          = Timber::context();
	$context['post']  = $post_id ? Timber::get_post( $post_id ) : Timber::get_post();
	$context['block'] = $block;
	$context['fields'] = timberland_get_block_fields_with_placeholders( $block, get_fields(), $use_placeholders );
	$block_name       = explode( '/', $block['name'] )[1];

	if ( 'insights-group' === $block_name ) {
		$context['fields'] = timberland_prepare_insights_group_fields( $context['fields'] );
	}

	$template = 'blocks/' . $block_name . '/index.twig';

	$context['is_preview']    = (bool) $is_preview;
	// Always null: the block editor loads the theme's compiled editor bundle (see
	// enqueue_editor_styles() / enqueue_editor_scripts()), so every block's dynamic
	// markup renders correctly in the editor — no static component-previews/ fallback.
	$context['preview_image'] = null;

	Timber::render( $template, $context );

	$GLOBALS['timberland_rendering_acf_block'] = false;
}

// Remove ACF block wrapper div
function acf_should_wrap_innerblocks( $wrap, $name ) {
	return false;
}

add_filter( 'acf/blocks/wrap_frontend_innerblocks', 'acf_should_wrap_innerblocks', 10, 2 );

// Case study body content: a lean toolbar (no alignment/color/blockquote) so the
// editor stays focused on plain narrative text with occasional H4 sub-headings.
function acf_case_study_wysiwyg_toolbar( $toolbars ) {
	$toolbars['Case Study'] = array(
		1 => array( 'formatselect', 'bold', 'italic', 'bullist', 'numlist', 'link', 'undo', 'redo' ),
	);
	$toolbars['Body Text'] = array(
		1 => array( 'formatselect', 'bold', 'bullist', 'link', 'unlink', 'undo', 'redo' ),
	);
	return $toolbars;
}
add_filter( 'acf/fields/wysiwyg/toolbars', 'acf_case_study_wysiwyg_toolbar' );

// Per-field heading options. ACF clones every wysiwyg from the hidden #acf_content
// template, so tiny_mce_before_init on acf_content overrides all fields — use
// acf/fields/wysiwyg/settings instead so each field gets its own block_formats.
function acf_wysiwyg_field_settings( $settings, $field ) {
	$key = $field['key'] ?? '';

	if ( $key === 'field_case_study_body_content' ) {
		$settings['block_formats'] = 'Paragraph=p;Heading 4=h4';
	}

	if ( $key === 'field_showcasy_body_text_content' ) {
		$settings['block_formats'] = 'Paragraph=p;Heading 1=h1;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6';
	}

	return $settings;
}
add_filter( 'acf/fields/wysiwyg/settings', 'acf_wysiwyg_field_settings', 10, 2 );

add_filter('timber/twig', function ($twig) {
	return $twig;
});
