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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
		add_filter( 'timber/twig', array( $this, 'add_twig_functions' ) );
		add_action( 'block_categories_all', array( $this, 'block_categories_all' ) );
		add_action( 'acf/init', array( $this, 'acf_register_blocks' ) );
		add_action( 'acf/init', array( $this, 'register_options_pages' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
		add_action( 'init', array( $this, 'register_post_types' ) );

		parent::__construct();
	}

	public function add_twig_functions( $twig ) {
		$twig->addFunction( new TwigFunction( 'check_url_match', array( $this, 'check_url_match' ) ) );
		$twig->addFunction( new TwigFunction( 'to_snake_case', array( $this, 'to_snake_case' ) ) );
		$twig->addFilter( new TwigFilter( 'nl2br', 'nl2br' ) );
		return $twig;
	}

	public function check_url_match ($string){
		$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		if($_SERVER['REQUEST_URI'] === $string  || $url === $string) {
			return true;
		}
		return false;
	}

	public function add_to_context( $context ) {
		global $post;
		$context['processed_content'] = wrap_non_acf_blocks($post->post_content);
		$context['site'] = $this;
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

	public function enqueue_assets() {
		// Prevent dequeueing of critical scripts in admin
		if (is_admin()) {
			return;
		}
	
		wp_dequeue_style('wp-block-library');
		wp_dequeue_style('wp-block-library-theme');
		wp_dequeue_style('wc-block-style');
		wp_dequeue_script('jquery');
		wp_dequeue_style('global-styles');

		

		$vite_env = 'production';

		if ( file_exists( get_template_directory() . '/../config.json' ) ) {
			$config   = json_decode( file_get_contents( get_template_directory() . '/../config.json' ), true );
			$vite_env = $config['vite']['environment'] ?? 'production';
		}

		$dist_uri  = get_template_directory_uri() . '/assets/dist';
		$dist_path = get_template_directory() . '/assets/dist';
		$manifest  = null;

		if ( file_exists( $dist_path . '/.vite/manifest.json' ) ) {
			$manifest = json_decode( file_get_contents( $dist_path . '/.vite/manifest.json' ), true );
		}

		if ( is_array( $manifest ) ) {
			if ( $vite_env === 'production' || is_admin() ) {
				$js_file = 'theme/assets/main.js';
				wp_enqueue_style( 'main', $dist_uri . '/' . $manifest[ $js_file ]['css'][0] );
				$strategy = is_admin() ? 'async' : 'defer';
				$in_footer = is_admin() ? false : true;
				wp_enqueue_script(
					'main',
					$dist_uri . '/' . $manifest[ $js_file ]['file'],
					array(),
					'',
					array(
						'strategy'  => $strategy,
						'in_footer' => $in_footer,
					)
				);

				// wp_enqueue_style('prefix-editor-font', '//fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap');
				$editor_css_file = 'theme/assets/styles/editor-style.css';
				add_editor_style( $dist_uri . '/' . $manifest[ $editor_css_file ]['file'] );
			}
		}

		if ( $vite_env === 'development' ) {
			function vite_head_module_hook() {
				echo '<script type="module" crossorigin src="http://localhost:3000/@vite/client"></script>';
				echo '<script type="module" crossorigin src="http://localhost:3000/theme/assets/main.js"></script>';
			}
			add_action( 'wp_head', 'vite_head_module_hook' );
		}
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
					'slug'  => 'work-blog',
					'title' => __( 'Work & Blog' ),
					'icon'  => 'portfolio',
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
					'slug'  => 'content-contact',
					'title' => __( 'Content & Contact' ),
					'icon'  => 'admin-page',
				),
				array(
					'slug'  => 'utility-pages',
					'title' => __( 'Utility Pages' ),
					'icon'  => 'admin-tools',
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
				'has_archive' => true,
				'rewrite'     => array( 'slug' => 'projects' ),
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
 * Don't edit this one
 */
function acf_block_render_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
	$context           = Timber::context();
	$context['post']   = Timber::get_post();
	$context['block']  = $block;
	$context['fields']  = get_fields();
    $block_name        = explode( '/', $block['name'] )[1];
    $template          = 'blocks/'. $block_name . '/index.twig';

	$context['is_preview']    = (bool) $is_preview;
	$context['preview_image'] = null;

	if ( $is_preview ) {
		$preview_path = get_template_directory() . '/assets/component-previews/desktop/' . $block_name . '.png';
		if ( file_exists( $preview_path ) ) {
			$context['preview_image'] = get_template_directory_uri() . '/assets/component-previews/desktop/' . $block_name . '.png';
		}
	}

	Timber::render( $template, $context );
}

// Remove ACF block wrapper div
function acf_should_wrap_innerblocks( $wrap, $name ) {
	return false;
}

add_filter( 'acf/blocks/wrap_frontend_innerblocks', 'acf_should_wrap_innerblocks', 10, 2 );

add_filter('timber/twig', function ($twig) {
	return $twig;
});