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
		add_action( 'wp_head', array( $this, 'output_google_analytics' ), 1 );
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
		add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ) );
		add_filter( 'document_title_separator', array( $this, 'filter_document_title_separator' ) );
		add_filter( 'language_attributes', array( $this, 'filter_language_attributes' ) );
		add_filter( 'wp_robots', array( $this, 'filter_robots' ) );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'filter_sitemap_posts' ), 10, 2 );
		add_filter( 'wp_sitemaps_taxonomies', array( $this, 'filter_sitemap_taxonomies' ) );
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'filter_sitemap_providers' ), 10, 2 );
		add_filter( 'upload_mimes', array( $this, 'allow_jfif_uploads' ) );
		add_filter( 'wp_handle_upload', array( $this, 'convert_jfif_upload_to_jpeg' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'hide_non_content_archives' ), 0 );
		add_action( 'template_redirect', array( $this, 'redirect_legacy_project_urls' ), 1 );
		add_action( 'template_redirect', array( $this, 'redirect_duplicate_insight_post' ), 1 );
		add_action( 'wp_head', array( $this, 'output_resource_hints' ), 2 );
		add_action( 'wp_head', array( $this, 'output_canonical_for_insight_archive' ), 4 );
		add_action( 'wp_head', array( $this, 'output_seo_meta' ), 5 );
		add_action( 'wp_head', array( $this, 'output_social_meta' ), 6 );
		add_action( 'wp_head', array( $this, 'output_homepage_schema' ), 20 );
		add_action( 'wp_head', array( $this, 'output_service_schema' ), 21 );
		add_action( 'wp_head', array( $this, 'output_insight_schema' ), 22 );
		add_action( 'wp_head', array( $this, 'output_breadcrumb_schema' ), 23 );
		add_action( 'wp_head', array( $this, 'output_faq_schema' ), 24 );
		add_filter( 'pings_open', '__return_false' );
		add_filter( 'xmlrpc_methods', array( $this, 'disable_pingback_xmlrpc_methods' ) );
		add_action( 'send_headers', array( $this, 'output_security_headers' ) );

		parent::__construct();
	}

	public function output_resource_hints() {
		echo '<link rel="preconnect" href="https://www.googletagmanager.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

		if ( ! is_front_page() ) {
			return;
		}

		$lcp_image_id = $this->get_homepage_lcp_image_id();

		if ( ! $lcp_image_id ) {
			return;
		}

		$image = Timber\Timber::get_image( $lcp_image_id );

		if ( ! $image ) {
			return;
		}

		echo '<link rel="preload" as="image" href="' . esc_url( $image->src( 'large' ) ) . '" imagesrcset="' . esc_attr( $image->srcset( 'large' ) ) . '" imagesizes="(min-width: 1024px) 50vw, 100vw">' . "\n";
	}

	/**
	 * Finds the image feeding the homepage's featured-article block (currently the
	 * page's LCP element — hero is text-only) by reading the front page's block
	 * data directly, so the preload target self-corrects if that block's image, or
	 * the homepage layout itself, changes later.
	 */
	private function get_homepage_lcp_image_id() {
		$front_page_id = (int) get_option( 'page_on_front' );

		if ( ! $front_page_id ) {
			return 0;
		}

		$front_page = get_post( $front_page_id );

		if ( ! $front_page ) {
			return 0;
		}

		foreach ( parse_blocks( $front_page->post_content ) as $block ) {
			if ( 'acf/featured-article' === ( $block['blockName'] ?? '' ) ) {
				return (int) ( $block['attrs']['data']['image'] ?? 0 );
			}
		}

		return 0;
	}

	public function disable_pingback_xmlrpc_methods( $methods ) {
		unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );

		return $methods;
	}

	/**
	 * Allow JPEG File Interchange Format images in the media library.
	 */
	public function allow_jfif_uploads( $mimes ) {
		$mimes['jfif'] = 'image/jpeg';

		return $mimes;
	}

	/**
	 * Convert an accepted JFIF upload to JPEG before WordPress creates its attachment.
	 *
	 * Conversion happens after the HTTP upload check so the replacement file does
	 * not fail PHP's is_uploaded_file() validation.
	 */
	public function convert_jfif_upload_to_jpeg( $upload, $context = 'upload' ) {
		unset( $context );

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return $upload;
		}

		$extension = strtolower( pathinfo( $upload['file'], PATHINFO_EXTENSION ) );

		if ( 'jfif' !== $extension ) {
			return $upload;
		}

		$image = wp_get_image_editor( $upload['file'] );

		if ( is_wp_error( $image ) ) {
			wp_delete_file( $upload['file'] );

			return array(
				'error' => __( 'WordPress could not read this JFIF image. Please convert it to JPG and try again.', 'barry-portfolio' ),
			);
		}

		$directory     = dirname( $upload['file'] );
		$jfif_filename = basename( $upload['file'] );
		$jpg_filename = wp_unique_filename(
			$directory,
			pathinfo( $jfif_filename, PATHINFO_FILENAME ) . '.jpg'
		);
		$jpg_path     = trailingslashit( $directory ) . $jpg_filename;
		$saved        = $image->save( $jpg_path, 'image/jpeg' );

		if ( is_wp_error( $saved ) || empty( $saved['path'] ) ) {
			wp_delete_file( $upload['file'] );

			return array(
				'error' => __( 'WordPress could not convert this JFIF image to JPG. Please convert it manually and try again.', 'barry-portfolio' ),
			);
		}

		wp_delete_file( $upload['file'] );

		$upload['file'] = $saved['path'];
		$upload['url']  = str_replace( $jfif_filename, $jpg_filename, $upload['url'] );
		$upload['type'] = 'image/jpeg';

		return $upload;
	}

	/**
	 * Emitted via PHP header() rather than .htaccess — survives GoDaddy Managed
	 * WordPress regenerating .htaccess, and works regardless of their Apache
	 * config. Known limitation: doesn't cover static assets Apache serves
	 * directly (images, built CSS/JS) without passing through PHP.
	 */
	public function output_security_headers() {
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Strict-Transport-Security: max-age=63072000; includeSubDomains' );
		// Reflects known integrations (gtag, Google Fonts, the inline x-cloak style/
		// GA config script) so report-only mode surfaces real anomalies instead of
		// flooding the console with our own first-party resources. A stricter
		// nonce-based policy is a worthwhile follow-on, not attempted here.
		header(
			"Content-Security-Policy-Report-Only: default-src 'self'; " .
			"script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://www.google-analytics.com; " .
			"style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
			"font-src 'self' https://fonts.gstatic.com; " .
			"img-src 'self' data: https://www.google-analytics.com; " .
			"connect-src 'self' https://www.google-analytics.com https://*.google-analytics.com https://*.analytics.google.com"
		);
		header_remove( 'X-Powered-By' );
	}

	public function output_google_analytics() {
		$measurement_id = 'G-VRNK1LDN5C';
		$script_url     = 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $measurement_id );

		echo '<script async src="' . esc_url( $script_url ) . '"></script>' . "\n";
		echo "<script>\nwindow.dataLayer = window.dataLayer || [];\nfunction gtag(){dataLayer.push(arguments);}\ngtag('js', new Date());\ngtag('config', '" . esc_js( $measurement_id ) . "');\n</script>\n";
	}

	private function get_current_seo_field( $field_name ) {
		if ( ! is_singular() ) {
			return null;
		}

		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return null;
		}

		if ( function_exists( 'get_field' ) ) {
			return get_field( $field_name, $post_id );
		}

		return get_post_meta( $post_id, $field_name, true );
	}

	public function filter_document_title( $title ) {
		$seo_title = trim( (string) $this->get_current_seo_field( 'seo_title' ) );

		if ( $seo_title !== '' ) {
			return $seo_title;
		}

		if ( is_singular( 'project' ) ) {
			return $this->build_fallback_title( get_the_title(), 'Case Study | Barry Tickle' );
		}

		if ( is_singular( 'insight' ) ) {
			return $this->build_fallback_title( get_the_title(), 'Barry Tickle' );
		}

		return $title;
	}

	/**
	 * Builds a "{title} — {suffix}" fallback title, dropping the suffix (and, if
	 * still too long, truncating the bare title) rather than emitting anything over
	 * 60 characters. Used when a post has no explicit seo_title.
	 */
	private function build_fallback_title( $post_title, $suffix ) {
		$post_title = trim( wp_strip_all_tags( (string) $post_title ) );
		$with_suffix = $post_title . ' — ' . $suffix;

		if ( mb_strlen( $with_suffix ) <= 60 ) {
			return $with_suffix;
		}

		if ( mb_strlen( $post_title ) <= 60 ) {
			return $post_title;
		}

		$truncated = rtrim( mb_substr( $post_title, 0, 59 ), " \t\n\r\0\x0B-–—" );

		return $truncated . '…';
	}

	/**
	 * Every title on the site should end "... | Barry Tickle" — WP core's default
	 * separator is a hyphen, which pages with no seo_title (Services, Projects,
	 * Contact Me, Privacy Policy, etc.) would otherwise fall through to.
	 */
	public function filter_document_title_separator() {
		return '|';
	}

	public function filter_language_attributes( $output ) {
		return preg_replace( '/lang="[^"]*"/', 'lang="en-GB"', $output );
	}

	public function filter_robots( $robots ) {
		if ( $this->get_current_seo_field( 'seo_noindex' ) ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}

		return $robots;
	}

	public function filter_sitemap_posts( $args, $post_type ) {
		$exclude_noindex = array(
			'relation' => 'OR',
			array(
				'key'     => 'seo_noindex',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => 'seo_noindex',
				'value'   => '1',
				'compare' => '!=',
			),
		);

		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = $exclude_noindex;
		} else {
			$args['meta_query'] = array(
				'relation' => 'AND',
				$args['meta_query'],
				$exclude_noindex,
			);
		}

		return $args;
	}

	public function filter_sitemap_taxonomies( $taxonomies ) {
		unset( $taxonomies['category'], $taxonomies['project_category'] );

		return $taxonomies;
	}

	public function filter_sitemap_providers( $provider, $name ) {
		return 'users' === $name ? false : $provider;
	}

	public function hide_non_content_archives() {
		if ( ! is_author() && ! is_category() && ! is_tax( 'project_category' ) ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	public function redirect_legacy_project_urls() {
		$request_path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH );
		$request_path = trailingslashit( $request_path ?: '/' );

		if ( in_array( $request_path, array( '/portfolio/', '/case-studies/' ), true ) ) {
			wp_safe_redirect( home_url( '/projects/' ), 301, 'BarryTickle Theme' );
			exit;
		}
	}

	/**
	 * One-off fix: post #1249 ("WordPress development without the unnecessary
	 * complexity", post_type `post`) is a near-duplicate of the real insight at
	 * /insights/wordpress-development-without-the-unnecessary-complexity/. Catches
	 * every access path (pretty URL, ?p=1249) rather than string-matching the URL.
	 */
	public function redirect_duplicate_insight_post() {
		if ( is_singular( 'post' ) && 1249 === get_queried_object_id() ) {
			wp_safe_redirect( home_url( '/insights/wordpress-development-without-the-unnecessary-complexity/' ), 301, 'BarryTickle Theme' );
			exit;
		}
	}

	public function output_seo_meta() {
		$description = $this->resolve_seo_meta_description();

		if ( $description === '' ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
	}

	/**
	 * seo_meta_description first; for singular posts with that field empty, falls
	 * back to the post excerpt (WP auto-generates one from content if none is set),
	 * truncated to ~155 chars. No fallback for archives — those need drafted copy,
	 * not an auto-generated stand-in.
	 */
	private function resolve_seo_meta_description() {
		if ( ! is_singular() ) {
			return '';
		}

		$description = trim( wp_strip_all_tags( (string) $this->get_current_seo_field( 'seo_meta_description' ) ) );

		if ( $description !== '' ) {
			return $description;
		}

		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			return '';
		}

		$excerpt = trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) );

		if ( $excerpt === '' ) {
			$post    = get_post( $post_id );
			$excerpt = $post ? $this->extract_text_from_acf_blocks( $post->post_content ) : '';
		}

		return $excerpt !== '' ? $this->truncate_to_length( $excerpt, 155 ) : '';
	}

	/**
	 * get_the_excerpt() comes back empty for this theme's content — every block
	 * here is a self-closing `acf/*` block (`<!-- wp:acf/x {"data":{...}} /-->`),
	 * and rendering it via ACF's callback outside the normal page-render flow
	 * (which is what wp_trim_excerpt()'s the_content pass does) doesn't produce
	 * usable HTML. The real copy lives directly in the block's JSON `data`
	 * attributes, so read it from there instead of relying on rendered output.
	 */
	private function extract_text_from_acf_blocks( $post_content ) {
		$text_parts = array();

		foreach ( parse_blocks( $post_content ) as $block ) {
			$data = $block['attrs']['data'] ?? null;

			if ( ! is_array( $data ) ) {
				continue;
			}

			foreach ( $data as $key => $value ) {
				// ACF pairs every "field_name" with a "_field_name" => field key
				// entry in block data — skip those, and skip non-text values.
				if ( ! is_string( $value ) || strpos( $key, '_' ) === 0 ) {
					continue;
				}

				$text_parts[] = wp_strip_all_tags( $value );
			}
		}

		return trim( implode( ' ', array_filter( $text_parts ) ) );
	}

	private function truncate_to_length( $text, $max_length ) {
		$text = trim( $text );

		if ( mb_strlen( $text ) <= $max_length ) {
			return $text;
		}

		$truncated  = mb_substr( $text, 0, $max_length - 1 );
		$last_space = mb_strrpos( $truncated, ' ' );

		if ( $last_space !== false ) {
			$truncated = mb_substr( $truncated, 0, $last_space );
		}

		return rtrim( $truncated, " \t\n\r\0\x0B.,;:" ) . '…';
	}

	/**
	 * Core's rel_canonical() only fires on is_singular() — the insight archive
	 * (/insights/) has no canonical at all without this.
	 */
	public function output_canonical_for_insight_archive() {
		if ( ! is_post_type_archive( 'insight' ) ) {
			return;
		}

		echo '<link rel="canonical" href="' . esc_url( $this->get_current_url() ) . '">' . "\n";
	}

	private function get_current_url() {
		if ( is_singular() ) {
			return get_permalink();
		}

		if ( is_post_type_archive( 'insight' ) ) {
			$paged = max( 1, (int) get_query_var( 'paged' ) );

			return $paged > 1 ? get_pagenum_link( $paged ) : get_post_type_archive_link( 'insight' );
		}

		if ( is_front_page() ) {
			return home_url( '/' );
		}

		global $wp;

		return home_url( add_query_arg( array(), $wp->request ?? '' ) );
	}

	/**
	 * og:image/twitter:image: featured image on singulars, else the sitewide
	 * fallback (Site Settings → SEO & Social → Social Share Fallback Image).
	 */
	private function resolve_social_image() {
		if ( is_singular() && has_post_thumbnail() ) {
			$image = Timber\Timber::get_image( get_post_thumbnail_id() );

			if ( $image ) {
				return $image->src( 'large' );
			}
		}

		$fallback = function_exists( 'get_field' ) ? get_field( 'og_fallback_image', 'option' ) : null;

		if ( ! empty( $fallback['ID'] ) ) {
			$image = Timber\Timber::get_image( $fallback['ID'] );

			if ( $image ) {
				return $image->src();
			}
		}

		return '';
	}

	public function output_social_meta() {
		$title       = wp_get_document_title();
		$description = $this->resolve_seo_meta_description();
		$url         = $this->get_current_url();
		$image       = $this->resolve_social_image();
		$type        = is_singular( 'insight' ) ? 'article' : 'website';

		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";

		if ( $description !== '' ) {
			echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
		}

		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";

		if ( $image !== '' ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
		}

		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";

		if ( $description !== '' ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
		}

		if ( $image !== '' ) {
			echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
		}
	}

	public function output_homepage_schema() {
		if ( ! is_front_page() ) {
			return;
		}

		$home_url = home_url( '/' );
		$location = function_exists( 'get_field' )
			? get_field( 'location_label', 'option' )
			: get_option( 'options_location_label' );
		$location = trim( (string) ( $location ?: get_option( 'options_location_label' ) ) );
		$social_links = function_exists( 'get_field' )
			? get_field( 'social_links', 'option' )
			: array();
		$same_as = array();

		foreach ( (array) $social_links as $social_link ) {
			$url = esc_url_raw( $social_link['url'] ?? '' );

			if ( $url !== '' ) {
				$same_as[] = $url;
			}
		}

		$person = array(
			'@type'     => 'Person',
			'@id'       => $home_url . '#person',
			'name'      => 'Barry Tickle',
			'url'       => $home_url,
			'jobTitle'  => 'Web Developer',
			'knowsAbout' => array(
				'WordPress development',
				'Front-end development',
				'Conversion rate optimisation',
			),
		);

		if ( $location !== '' ) {
			$person['homeLocation'] = array(
				'@type'   => 'Place',
				'name'    => $location,
				'address' => array(
					'@type'          => 'PostalAddress',
					'addressCountry' => 'GB',
				),
			);
		}

		if ( $same_as ) {
			$person['sameAs'] = array_values( array_unique( $same_as ) );
		}

		$professional_service = array(
			'@type'      => 'ProfessionalService',
			'@id'        => $home_url . '#business',
			'name'       => 'Barry Tickle',
			'url'        => $home_url,
			'founder'    => array( '@id' => $home_url . '#person' ),
			'address'    => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'Oswestry',
				'addressRegion'   => 'Shropshire',
				'postalCode'      => 'SY11',
				'addressCountry'  => 'GB',
			),
			'areaServed' => array( 'Shropshire', 'United Kingdom' ),
			'knowsAbout' => array(
				'WordPress development',
				'Front-end development',
				'Conversion rate optimisation',
			),
		);

		$schema = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'@type'     => 'WebSite',
					'@id'       => $home_url . '#website',
					'name'      => get_bloginfo( 'name' ),
					'url'       => $home_url,
					'inLanguage' => 'en-GB',
					'publisher' => array( '@id' => $home_url . '#person' ),
				),
				$person,
				$professional_service,
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * Ordered { name, url } trail for the current request. Shared by the visible
	 * breadcrumbs.twig component and output_breadcrumb_schema() so the two can't
	 * drift apart — the schema only ever marks up what's actually on the page.
	 */
	public function get_breadcrumb_trail() {
		$trail = array(
			array( 'name' => 'Home', 'url' => home_url( '/' ) ),
		);

		if ( is_singular( 'project' ) ) {
			$trail[] = array( 'name' => 'Projects', 'url' => home_url( '/projects/' ) );
			$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
		} elseif ( is_singular( 'service' ) ) {
			$trail[] = array( 'name' => 'Services', 'url' => home_url( '/services/' ) );
			$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
		} elseif ( is_singular( 'insight' ) ) {
			$lifestyle = has_category( 'lifestyle', get_queried_object_id() );
			$trail[] = $lifestyle
				? array( 'name' => 'Journal', 'url' => home_url( '/journal/' ) )
				: array( 'name' => 'Insights', 'url' => get_post_type_archive_link( 'insight' ) );
			$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
		} elseif ( is_post_type_archive( 'insight' ) ) {
			$trail[] = array( 'name' => 'Insights', 'url' => get_post_type_archive_link( 'insight' ) );
		} elseif ( is_page() && ! is_front_page() ) {
			$trail[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
		}

		return $trail;
	}

	public function output_service_schema() {
		if ( ! is_singular( 'service' ) ) {
			return;
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Service',
			'name'     => get_the_title(),
			'url'      => get_permalink(),
			'provider' => array( '@id' => home_url( '/' ) . '#person' ),
		);

		$description = $this->resolve_seo_meta_description();

		if ( $description !== '' ) {
			$schema['description'] = $description;
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	public function output_insight_schema() {
		if ( ! is_singular( 'insight' ) ) {
			return;
		}

		$post_id = get_queried_object_id();
		$schema  = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'headline'         => get_the_title( $post_id ),
			'datePublished'    => get_the_date( 'c', $post_id ),
			'dateModified'     => get_the_modified_date( 'c', $post_id ),
			'author'           => array( '@id' => home_url( '/' ) . '#person' ),
			'mainEntityOfPage' => get_permalink( $post_id ),
			'inLanguage'       => 'en-GB',
		);

		if ( has_post_thumbnail( $post_id ) ) {
			$image = Timber\Timber::get_image( get_post_thumbnail_id( $post_id ) );

			if ( $image ) {
				$schema['image'] = $image->src( 'large' );
			}
		}

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * FAQPage schema is optional, semantic-only structured data (Google retired the
	 * FAQ rich result in May 2026) — read directly from the same numbered-accordion
	 * block data the front end renders, so the schema can never say something the
	 * visible page doesn't.
	 */
	public function output_faq_schema() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();

		if ( ! $post ) {
			return;
		}

		$questions = array();

		foreach ( parse_blocks( $post->post_content ) as $block ) {
			if ( ( $block['blockName'] ?? '' ) !== 'acf/numbered-accordion' ) {
				continue;
			}

			$data  = $block['attrs']['data'] ?? array();
			$count = (int) ( $data['items'] ?? 0 );

			for ( $i = 0; $i < $count; $i++ ) {
				$question = trim( wp_strip_all_tags( (string) ( $data[ "items_{$i}_title" ] ?? '' ) ) );
				$answer   = trim( wp_strip_all_tags( (string) ( $data[ "items_{$i}_content" ] ?? '' ) ) );

				if ( $question === '' || $answer === '' ) {
					continue;
				}

				$questions[] = array(
					'@type'          => 'Question',
					'name'           => $question,
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => $answer,
					),
				);
			}
		}

		if ( ! $questions ) {
			return;
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $questions,
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	public function output_breadcrumb_schema() {
		$trail = $this->get_breadcrumb_trail();

		if ( count( $trail ) < 2 ) {
			return;
		}

		$items = array();

		foreach ( $trail as $index => $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['url'],
			);
		}

		$schema = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BreadcrumbList',
			'itemListElement'  => $items,
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
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
		$twig->addFunction( new TwigFunction( 'is_front_page', 'is_front_page' ) );
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
		$context['breadcrumb_trail'] = $this->get_breadcrumb_trail();
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

	/**
	 * Return CSS emitted for a Vite entry and each of its imported chunks.
	 */
	private function get_vite_entry_css( $manifest, $entry, &$visited = array() ) {
		if ( isset( $visited[ $entry ] ) || empty( $manifest[ $entry ] ) ) {
			return array();
		}

		$visited[ $entry ] = true;
		$css               = $manifest[ $entry ]['css'] ?? array();

		foreach ( $manifest[ $entry ]['imports'] ?? array() as $import ) {
			$css = array_merge( $css, $this->get_vite_entry_css( $manifest, $import, $visited ) );
		}

		return array_values( array_unique( $css ) );
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

			foreach ( $this->get_vite_entry_css( $manifest, $js_file ) as $index => $css_file ) {
				$handle = 0 === $index ? 'main' : 'main-' . $index;
				wp_enqueue_style( $handle, $dist_uri . '/' . $css_file );
			}

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

			foreach ( $this->get_vite_entry_css( $manifest, $editor_js_file ) as $css_file ) {
				add_editor_style( $dist_uri . '/' . $css_file );
			}

			return;
		}

		if ( $vite_env === 'development' ) {
			// Prefer the built bundle when available — avoids mixed-content blocks when
			// the admin runs on https://*.local but Vite serves http://localhost:3000.
			if ( is_array( $manifest ) ) {
				$editor_js_file = 'theme/assets/editor.js';

				foreach ( $this->get_vite_entry_css( $manifest, $editor_js_file ) as $css_file ) {
					add_editor_style( $dist_uri . '/' . $css_file );
				}
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

/** Load the latest three published Insights into the reusable Insights Group. */
function timberland_prepare_insights_group_fields( $fields ) {
	$insights = get_posts(
		array(
			'post_type'      => 'insight',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$fields['articles'] = array_map(
		static function ( $insight ) {
			$categories   = get_the_category( $insight->ID );
			$thumbnail_id = get_post_thumbnail_id( $insight );
			$image        = $thumbnail_id ?: null;

			return array(
				'eyebrow' => $categories ? $categories[0]->name : '',
				'title'   => get_the_title( $insight ),
				'excerpt' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $insight ) ), 18, '...' ),
				'href'    => get_permalink( $insight ),
				'image'   => $image,
			);
		},
		$insights
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
