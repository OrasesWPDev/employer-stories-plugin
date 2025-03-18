<?php
/**
 * Custom Post Type Registration
 *
 * @package EmployerStories
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Employer_Stories_CPT
 *
 * Handles the registration of the Employer Story custom post type
 */
class Employer_Stories_CPT {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	protected $post_type = 'employer-story';

	/**
	 * URL slug to use for permalinks.
	 *
	 * @var string
	 */
	protected $url_slug = 'employer-stories';

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		// Register the custom post type
		add_action('init', array($this, 'register_post_type'), 5);
		
		// Setup permalink filters
		add_action('init', array($this, 'setup_permalink_filters'), 10);
		
		// Fix permalinks in admin
		add_filter('get_sample_permalink', array($this, 'fix_admin_permalink'), 10, 5);

		// Fix admin bar links
		add_action('admin_bar_menu', array($this, 'fix_admin_bar_links'), 999);

		// Register breadcrumbs shortcode
		add_shortcode('employer_story_breadcrumbs', array($this, 'breadcrumbs_shortcode'));
		
		// Add early hook for permalink structure
		add_action('pre_get_posts', array($this, 'fix_query_vars'), 1);
		
		// Add a filter to parse request to ensure our custom permalinks are recognized
		add_filter('request', array($this, 'parse_request'), 10);
		
		// Add a filter to redirect old URLs to new ones
		add_action('template_redirect', array($this, 'redirect_old_urls'), 1);
		
		// Add filter for post type archive link
		add_filter('post_type_archive_link', array($this, 'fix_archive_link'), 10, 2);
		
		// Prevent archive template from being used for page with same slug
		add_filter('template_include', array($this, 'prevent_archive_template'), 99);
		
		// Maybe flush rules when explicitly requested
		add_action('init', array($this, 'maybe_flush_rules'), 999);
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
			// error_log('Employer Stories CPT: Instance created');
			
			// Don't flush rules on every instance creation - only on activation
		}
		return self::$instance;
	}



	/**
	 * Fix permalinks displayed in the admin edit screen
	 *
	 * @param array $permalink Sample permalink
	 * @param int $post_id Post ID
	 * @param string $title Post title
	 * @param string $name Post name
	 * @param WP_Post $post Post object
	 * @return array Modified permalink
	 */
	public function fix_admin_permalink($permalink, $post_id, $title, $name, $post) {
		if ($post && $post->post_type === $this->post_type) {
			// Use our permalink generator
			$custom_permalink = $this->get_permalink($post, true);
			if ($custom_permalink) {
				$permalink[0] = str_replace('%postname%', $name, $custom_permalink);
			}
		}
		return $permalink;
	}

	/**
	 * Fix links in the admin bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object
	 */
	public function fix_admin_bar_links($wp_admin_bar) {
		$view_node = $wp_admin_bar->get_node('view');
		if ($view_node && is_singular($this->post_type)) {
			$post = get_post();
			if ($post) {
				$view_node->href = $this->get_permalink($post);
				$wp_admin_bar->add_node($view_node);
			}
		}
	}

	/**
	 * Register the custom post type
	 */
	public function register_post_type() {
		if (!post_type_exists($this->post_type)) {
			$args = array(
				'labels' => array(
					'name' => 'Employer Stories',
					'singular_name' => 'Employer Story',
					'menu_name' => 'Employer Stories',
					'all_items' => 'All Employer Stories',
					'edit_item' => 'Edit Employer Story',
					'view_item' => 'View Employer Story',
					'view_items' => 'View Employer Stories',
					'add_new_item' => 'Add New Employer Story',
					'add_new' => 'Add New Employer Story',
					'new_item' => 'New Employer Story',
					'parent_item_colon' => 'Parent Employer Story:',
					'search_items' => 'Search Employer Stories',
					'not_found' => 'No employer stories found',
					'not_found_in_trash' => 'No employer stories found in Trash',
					'archives' => 'Employer Story Archives',
					'attributes' => 'Employer Story Attributes',
					'insert_into_item' => 'Insert into employer story',
					'uploaded_to_this_item' => 'Uploaded to this employer story',
					'filter_items_list' => 'Filter employer stories list',
					'filter_by_date' => 'Filter employer stories by date',
					'items_list_navigation' => 'Employer Stories list navigation',
					'items_list' => 'Employer Stories list',
					'item_published' => 'Employer Story published.',
					'item_published_privately' => 'Employer Story published privately.',
					'item_reverted_to_draft' => 'Employer Story reverted to draft.',
					'item_scheduled' => 'Employer Story scheduled.',
					'item_updated' => 'Employer Story updated.',
					'item_link' => 'Employer Story Link',
					'item_link_description' => 'A link to a employer story.',
				),
				'description' => 'Add or Edit Employer Stories',
				'public' => true,
				'show_in_rest' => true,
				'menu_icon' => 'dashicons-buddicons-buddypress-logo',
				'supports' => array(
					'title',
					'page-attributes',
					'thumbnail',
					'custom-fields',
				),
				'taxonomies' => array(
					'category',
					'block_categories',
					'post_tag',
				),
				'delete_with_user' => false,
				'rewrite' => array(
					'slug' => $this->url_slug,
					'with_front' => false,
					'feeds' => false,
					'pages' => true,
					'ep_mask' => EP_PERMALINK,
				),
				'has_archive' => false, // Disable automatic archive page
				'query_var' => $this->post_type,
				'can_export' => true,
				'publicly_queryable' => true,
			);

			register_post_type($this->post_type, $args);
			
			// Add all necessary rewrite rules in one place
			$this->add_rewrite_rules();
		}
	}
	
	/**
	 * Add all necessary rewrite rules
	 */
	private function add_rewrite_rules() {
		// Remove any existing rules for the singular post type
		add_rewrite_rule(
			'^' . $this->post_type . '/([^/]+)/?$',
			'index.php?p=0',
			'top'
		);
		
		// Add our custom permalink structure (no trailing slash)
		add_rewrite_rule(
			'^' . $this->url_slug . '/([^/]+)$',
			'index.php?' . $this->post_type . '=$matches[1]',
			'top'
		);
		
		// Add feed rules
		add_rewrite_rule(
			'^' . $this->url_slug . '/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$',
			'index.php?' . $this->post_type . '=$matches[1]&feed=$matches[2]',
			'top'
		);
		
		add_rewrite_rule(
			'^' . $this->url_slug . '/([^/]+)/(feed|rdf|rss|rss2|atom)/?$',
			'index.php?' . $this->post_type . '=$matches[1]&feed=$matches[2]',
			'top'
		);
		
		// Add trackback rule
		add_rewrite_rule(
			'^' . $this->url_slug . '/([^/]+)/trackback/?$',
			'index.php?' . $this->post_type . '=$matches[1]&trackback=1',
			'top'
		);
		
		// Add rewrite tag
		add_rewrite_tag('%' . $this->post_type . '%', '([^/]+)');
	}

	/**
	 * Get the permalink for an employer story
	 * This is the single source of truth for all permalink generation
	 *
	 * @param int|WP_Post $post_id Post ID or object
	 * @param bool $leavename Whether to keep the post name
	 * @return string|false The permalink or false if not found
	 */
	public function get_permalink($post_id, $leavename = false) {
		// Get post data
		$post = is_object($post_id) ? $post_id : get_post($post_id);
		if (!$post || $post->post_type !== $this->post_type) {
			return false;
		}
		
		// Check cache first
		$cached = get_post_meta($post->ID, '_employer_story_permalink', true);
		if (!empty($cached) && !$leavename) {
			return $cached;
		}
		
		// Generate permalink
		$post_name = $leavename ? '%postname%' : $post->post_name;
		if (empty($post_name) && !$leavename) {
			$post_name = sanitize_title($post->post_title);
		}
		
		$permalink = home_url($this->url_slug . '/' . $post_name);
		
		// Cache the permalink
		if (!$leavename && isset($_GET['refresh_permalinks']) && current_user_can('manage_options')) {
			update_post_meta($post->ID, '_employer_story_permalink', $permalink);
		}
		
		return $permalink;
	}

	/**
	 * Fix query vars for our custom post type
	 * 
	 * @param WP_Query $query The WordPress query object
	 */
	public function fix_query_vars($query) {
		// Only run once
		static $ran = false;
		if ($ran) return;
		$ran = true;
		
		// Make sure WordPress knows about our custom permalink structure
		global $wp;
		$wp->add_query_var($this->post_type);
		
		// Prevent archive behavior for pages with the same slug as our post type
		if (!is_admin() && $query->is_main_query() && !$query->is_singular) {
			// Check if this is a page with our archive slug
			if (isset($query->query['pagename']) && $query->query['pagename'] === $this->url_slug) {
				// Force it to be treated as a page, not an archive
				$query->is_post_type_archive = false;
				$query->is_archive = false;
				
				// error_log('Employer Stories CPT: Prevented archive query for page with slug: ' . $this->url_slug);
			}
		}
		
		// error_log('Employer Stories CPT: Added query var for ' . $this->post_type);
	}
	
	/**
	 * Parse request to handle our custom permalink structure
	 * 
	 * @param array $query_vars The query variables
	 * @return array Modified query variables
	 */
	public function parse_request($query_vars) {
		// Check if we're on an employer story page
		$path = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';
		
		// If the path starts with our URL slug (no trailing slash)
		if (preg_match('|^' . $this->url_slug . '/([^/]+)$|', $path, $matches)) {
			$post_name = $matches[1];
			
			// Set the query var for our post type
			$query_vars[$this->post_type] = $post_name;
		}
		
		return $query_vars;
	}
	
	/**
	 * Redirect old URLs to new ones
	 */
	public function redirect_old_urls() {
		// Check if we're on a page with the old URL structure
		$path = isset($_SERVER['REQUEST_URI']) ? trim($_SERVER['REQUEST_URI'], '/') : '';
		
		// If the path starts with the post type (singular)
		if (preg_match('|^' . $this->post_type . '/([^/]+)/?$|', $path, $matches)) {
			$post_name = $matches[1];
			
			// Get the post by name
			$posts = get_posts(array(
				'name' => $post_name,
				'post_type' => $this->post_type,
				'posts_per_page' => 1
			));
			
			if (!empty($posts)) {
				// Redirect to the new URL
				wp_redirect($this->get_permalink($posts[0]), 301);
				exit;
			}
		}
	}

	/**
	 * Breadcrumbs shortcode implementation
	 *
	 * @return string HTML markup for breadcrumbs
	 */
	public function breadcrumbs_shortcode() {
		ob_start();

		$home_url = home_url();
		$home_label = __('Ptcb', 'employer-stories');
		$archive_url = home_url($this->url_slug);
		$archive_label = __('Employer Stories', 'employer-stories');

		// Start breadcrumbs container
		echo '<div class="es-breadcrumbs">';

		// Home link
		echo '<a href="' . esc_url($home_url) . '">' . esc_html($home_label) . '</a>';
		echo '<span class="es-breadcrumb-divider">/</span>';

		// Always add the archive link, regardless of page type
		echo '<a href="' . esc_url($archive_url) . '">' . esc_html($archive_label) . '</a>';
		
		// For single posts, add the post title
		if (is_singular($this->post_type)) {
			echo '<span class="es-breadcrumb-divider">/</span>';
			echo '<span class="breadcrumb_last">' . get_the_title() . '</span>';
		}

		echo '</div>';

		return ob_get_clean();
	}
	
	
	
	
	/**
	 * Maybe flush rewrite rules - only during development or when needed
	 */
	public function maybe_flush_rules() {
		// Check for our special query parameter
		if (isset($_GET['employer_stories_flush_rules']) && current_user_can('manage_options')) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules(true);
			// error_log('Employer Stories CPT: Flushed rewrite rules via query parameter');
		}
		
		// Removed hourly transient-based flushing to prevent excessive updates
	}
	
	/**
	 * Fix archive link for our custom post type
	 *
	 * @param string $link The archive link
	 * @param string $post_type The post type
	 * @return string Modified archive link
	 */
	public function fix_archive_link($link, $post_type) {
		if ($post_type === $this->post_type) {
			return home_url($this->url_slug);
		}
		return $link;
	}
	
	/**
	 * Prevent archive template from being used for page with same slug
	 * 
	 * @param string $template The template to include
	 * @return string The modified template path
	 */
	public function prevent_archive_template($template) {
		global $wp_query;
		
		// If this is a page with our slug but not a post type archive
		if (is_page() && !is_post_type_archive($this->post_type)) {
			$current_page = get_queried_object();
			
			// Check if the page slug matches our post type archive slug
			if ($current_page && isset($current_page->post_name) && $current_page->post_name === $this->url_slug) {
				// Force WordPress to use the page template, not the archive template
				$wp_query->is_post_type_archive = false;
				
				// Log this for debugging
				// error_log('Employer Stories CPT: Prevented archive template for page with slug: ' . $this->url_slug);
			}
		}
		
		return $template;
	}
	
	/**
	 * Setup permalink filters for consistent URL generation
	 */
	public function setup_permalink_filters() {
		// Single filter for all permalink generation
		add_filter('post_type_link', function($permalink, $post, $leavename) {
			if ($post->post_type === $this->post_type) {
				return $this->get_permalink($post, $leavename);
			}
			return $permalink;
		}, 10, 3);
		
		// Also filter get_permalink
		add_filter('get_permalink', function($permalink, $post) {
			if (is_object($post) && $post->post_type === $this->post_type) {
				return $this->get_permalink($post);
			}
			return $permalink;
		}, 10, 2);
		
		// Clear permalink cache when a post is saved
		add_action('save_post_' . $this->post_type, function($post_id) {
			delete_post_meta($post_id, '_employer_story_permalink');
			clean_post_cache($post_id);
		});
	}
}
