<?php
/**
 * Barry Timberland 26 theme bootstrap.
 *
 * @package BarryTimberland26
 */

declare(strict_types=1);

use Timber\Menu;
use Timber\Site;
use Timber\Timber;

require_once dirname(__DIR__) . '/vendor/autoload.php';

Timber::init();
Timber::$dirname = array('views', 'blocks');

add_filter(
	'timber/twig/environment/options',
	static function (array $options): array {
		$options['autoescape'] = 'html';
		return $options;
	}
);

final class Barry_Timberland_26 extends Site {
	private const TEXT_DOMAIN = 'barry-timberland-26';

	public function __construct() {
		add_action('after_setup_theme', array($this, 'setup_theme'));
		add_action('init', array($this, 'register_case_study_post_type'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
		add_action('acf/init', array($this, 'register_acf_features'));
		add_filter('acf/settings/save_json', array($this, 'acf_json_save_path'));
		add_filter('acf/settings/load_json', array($this, 'acf_json_load_paths'));
		add_filter('block_categories_all', array($this, 'add_block_category'));
		add_filter('timber/context', array($this, 'add_to_context'));

		parent::__construct();
	}

	public function setup_theme(): void {
		load_theme_textdomain(self::TEXT_DOMAIN, get_template_directory() . '/languages');
		add_theme_support('automatic-feed-links');
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support('responsive-embeds');
		add_theme_support('editor-styles');
		add_theme_support('html5', array('comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'search-form'));
		add_image_size('bt26-project', 1600, 1000, true);
		add_image_size('bt26-logo', 320, 160, false);

		register_nav_menus(
			array(
				'primary'          => __('Primary navigation', self::TEXT_DOMAIN),
				'footer_primary'   => __('Footer primary navigation', self::TEXT_DOMAIN),
				'footer_secondary' => __('Footer secondary navigation', self::TEXT_DOMAIN),
			)
		);
	}

	/** @param array<string, mixed> $context */
	public function add_to_context(array $context): array {
		$context['site'] = $this;
		$context['primary_menu'] = $this->get_menu_for_location('primary');
		$context['footer_primary_menu'] = $this->get_menu_for_location('footer_primary');
		$context['footer_secondary_menu'] = $this->get_menu_for_location('footer_secondary');
		$context['site_settings'] = function_exists('get_fields') ? (get_fields('option') ?: array()) : array();
		$context['current_year'] = wp_date('Y');
		$context['icon_names'] = bt26_allowed_icons();

		return $context;
	}

	private function get_menu_for_location(string $location): ?Menu {
		$locations = get_nav_menu_locations();
		if (empty($locations[$location])) {
			return null;
		}

		$menu = Timber::get_menu((int) $locations[$location]);
		return $menu instanceof Menu ? $menu : null;
	}

	public function register_case_study_post_type(): void {
		register_post_type(
			'case_study',
			array(
				'labels' => array(
					'name'          => __('Case Studies', self::TEXT_DOMAIN),
					'singular_name' => __('Case Study', self::TEXT_DOMAIN),
					'add_new_item'  => __('Add New Case Study', self::TEXT_DOMAIN),
					'edit_item'     => __('Edit Case Study', self::TEXT_DOMAIN),
					'view_item'     => __('View Case Study', self::TEXT_DOMAIN),
					'all_items'     => __('All Case Studies', self::TEXT_DOMAIN),
				),
				'public'       => true,
				'show_in_rest' => true,
				'has_archive'  => true,
				'rewrite'      => array('slug' => 'work'),
				'menu_icon'    => 'dashicons-portfolio',
				'supports'     => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'page-attributes'),
			)
		);
	}

	public function register_acf_features(): void {
		if (! function_exists('acf_add_options_page')) {
			return;
		}

		acf_add_options_page(
			array(
				'page_title' => __('Site Settings', self::TEXT_DOMAIN),
				'menu_title' => __('Site Settings', self::TEXT_DOMAIN),
				'menu_slug'  => 'bt26-site-settings',
				'capability' => 'manage_options',
				'redirect'   => false,
				'position'   => 61,
				'icon_url'   => 'dashicons-admin-generic',
			)
		);

		foreach (glob(__DIR__ . '/blocks/*/block.json') ?: array() as $block_json) {
			register_block_type(dirname($block_json));
		}
	}

	public function acf_json_save_path(): string {
		return __DIR__ . '/acf-json';
	}

	/** @param array<int, string> $paths */
	public function acf_json_load_paths(array $paths): array {
		$paths[] = __DIR__ . '/acf-json';
		return array_values(array_unique($paths));
	}

	/** @param array<int, array<string, string>> $categories */
	public function add_block_category(array $categories): array {
		array_unshift($categories, array('slug' => 'bt26-portfolio', 'title' => __('Portfolio', self::TEXT_DOMAIN)));
		return $categories;
	}

	public function enqueue_assets(): void {
		$this->enqueue_vite_assets(false);
	}

	public function enqueue_editor_assets(): void {
		$this->enqueue_vite_assets(true);
	}

	private function enqueue_vite_assets(bool $editor): void {
		$config_path = dirname(__DIR__) . '/config.json';
		$config = file_exists($config_path) ? json_decode((string) file_get_contents($config_path), true) : array();
		$environment = $config['vite']['environment'] ?? 'production';

		if ('development' === $environment && ! $editor) {
			wp_enqueue_script_module('bt26-vite-client', 'http://localhost:3000/@vite/client', array(), null);
			wp_enqueue_script_module('bt26-main', 'http://localhost:3000/theme/assets/main.js', array(), null);
			return;
		}

		$dist_path = __DIR__ . '/assets/dist';
		$dist_uri = get_template_directory_uri() . '/assets/dist';
		$manifest_path = $dist_path . '/.vite/manifest.json';
		if (! file_exists($manifest_path)) {
			return;
		}

		$manifest = json_decode((string) file_get_contents($manifest_path), true);
		if (! is_array($manifest)) {
			return;
		}

		if ($editor) {
			$entry = $manifest['theme/assets/styles/editor-style.css'] ?? null;
			if (is_array($entry) && ! empty($entry['file'])) {
				wp_enqueue_style('bt26-editor', $dist_uri . '/' . $entry['file'], array(), null);
			}
			return;
		}

		$entry = $manifest['theme/assets/main.js'] ?? null;
		if (! is_array($entry) || empty($entry['file'])) {
			return;
		}

		foreach ($entry['css'] ?? array() as $index => $css) {
			wp_enqueue_style('bt26-main-' . $index, $dist_uri . '/' . $css, array(), null);
		}

		wp_enqueue_script_module('bt26-main', $dist_uri . '/' . $entry['file'], array(), null);
	}
}

new Barry_Timberland_26();

/** @return array<int, string> */
function bt26_allowed_icons(): array {
	return array('accessibility', 'arrow-right', 'arrow-up-right', 'close', 'code', 'flask', 'github', 'linkedin', 'location', 'mail', 'menu', 'paper-plane', 'quote', 'speedometer');
}

function bt26_icon_path(string $name): ?string {
	$name = sanitize_key($name);
	if (! in_array($name, bt26_allowed_icons(), true)) {
		return null;
	}

	$path = __DIR__ . '/assets/icons/' . $name . '.svg';
	return file_exists($path) ? $path : null;
}

/** @param array<string, mixed> $block */
function acf_block_render_callback(array $block, string $content = '', bool $is_preview = false, int $post_id = 0): void {
	$context = Timber::context();
	$context['post'] = Timber::get_post($post_id ?: null);
	$context['block'] = $block;
	$context['fields'] = function_exists('get_fields') ? (get_fields() ?: array()) : array();
	$context['is_preview'] = $is_preview || ! empty($block['data']['is_preview']);
	$block_name = str_replace('acf/', '', (string) ($block['name'] ?? ''));

	if (! empty($context['fields']['project'])) {
		$context['project'] = Timber::get_post($context['fields']['project']);
	}

	if ('project-grid' === $block_name) {
		$context['projects'] = bt26_get_project_grid_posts($context['fields']);
	}

	Timber::render('blocks/' . $block_name . '/index.twig', $context);
}

/** @param array<string, mixed> $fields @return iterable<Timber\Post> */
function bt26_get_project_grid_posts(array $fields): iterable {
	$limit = max(1, min(12, (int) ($fields['number_of_projects'] ?? 6)));
	if ('manual' === ($fields['selection_mode'] ?? '') && ! empty($fields['projects'])) {
		return Timber::get_posts(array('post_type' => 'case_study', 'post__in' => array_map('intval', (array) $fields['projects']), 'orderby' => 'post__in', 'posts_per_page' => $limit));
	}

	$featured = Timber::get_posts(
		array(
			'post_type'      => 'case_study',
			'posts_per_page' => $limit,
			'meta_query'     => array(array('key' => 'project_is_featured', 'value' => '1')),
			'meta_key'       => 'project_featured_order',
			'orderby'        => array('meta_value_num' => 'ASC', 'menu_order' => 'ASC', 'date' => 'DESC'),
		)
	);
	$projects = array();
	$featured_ids = array();
	foreach ($featured as $project) {
		$projects[] = $project;
		$featured_ids[] = $project->id;
	}

	if (count($projects) < $limit) {
		$recent = Timber::get_posts(array('post_type' => 'case_study', 'posts_per_page' => $limit - count($projects), 'post__not_in' => $featured_ids, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC')));
		foreach ($recent as $project) {
			$projects[] = $project;
		}
	}

	return $projects;
}

add_filter('acf/blocks/wrap_frontend_innerblocks', '__return_false');
