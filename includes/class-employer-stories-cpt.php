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
		// error_log('Employer Stories CPT: Constructor called');

		// Register the custom post type
		add_action('init', array($this, 'register_post_type'), 5);

		// Modify permalink structure - extremely high priority to ensure it runs first
		add_filter('post_type_link', array($this, 'modify_permalink_structure'), -999999, 4);
		
		// Add a second filter with extremely high priority to ensure our structure is used
		add_filter('post_link', array($this, 'force_employer_story_permalink'), -999999, 3);
		add_filter('post_type_link', array($this, 'force_employer_story_permalink'), -999999, 3);

		// Fix permalinks in admin
		add_filter('get_sample_permalink', array($this, 'fix_admin_permalink'), 10, 5);

		// Fix admin bar links
		add_action('admin_bar_menu', array($this, 'fix_admin_bar_links'), 999);

		// Register breadcrumbs shortcode
		add_shortcode('employer_story_breadcrumbs', array($this, 'breadcrumbs_shortcode'));

		// Register a function to run after WordPress is loaded to fix permalinks
		add_action('wp_loaded', array($this, 'fix_permalinks_on_load'), 1);
		
		// Add early hook for permalink structure with high priority
		add_action('pre_get_posts', array($this, 'fix_query_vars'), 1);
		
		// Add a filter to parse request to ensure our custom permalinks are recognized with high priority
		add_filter('request', array($this, 'parse_request'), -999999);
		
		// Add a filter to redirect old URLs to new ones with high priority
		add_action('template_redirect', array($this, 'redirect_old_urls'), 1);
		
		// Add debug action for admins
		if (is_admin() && current_user_can('manage_options')) {
			add_action('admin_init', array($this, 'debug_rewrite_rules'));
		}
		
		// Add more permalink filters to ensure our structure is used with high priority
		add_filter('pre_post_link', array($this, 'pre_post_link'), -999999, 2);
		add_filter('post_rewrite_rules', array($this, 'custom_post_rewrite_rules'), -999999);
		
		// Force flush rewrite rules on init for testing
		add_action('init', array($this, 'maybe_flush_rules'), 999);
		
		// Add filter for post type archive link with high priority
		add_filter('post_type_archive_link', array($this, 'fix_archive_link'), -999999, 2);
		
		// Prevent archive template from being used for page with same slug
		add_filter('template_include', array($this, 'prevent_archive_template'), 99);
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
			
			// Force flush rewrite rules on first instance creation
			add_action('shutdown', function() {
				global $wp_rewrite;
				$wp_rewrite->flush_rules(true);
				// error_log('Employer Stories CPT: Forced rewrite rules flush on instance creation');
			});
		}
		return self::$instance;
	}

	/**
	 * Fix permalinks when WordPress is fully loaded
	 */
	public function fix_permalinks_on_load() {
		global $wp_rewrite, $wpdb;

		// Remove any existing rules for the singular post type by adding a rule that won't match
		add_rewrite_rule(
			'^' . $this->post_type . '/([^/]+)/?$',
			'index.php?p=0',
			'top'
		);

		// Add our custom permalink structure with higher specificity for the plural slug
		add_rewrite_rule(
			'^' . $this->url_slug . '/([^/]+)/?$',
			'index.php?' . $this->post_type . '=$matches[1]',
			'top'
		);

		// Add a feed rule if needed
		add_rewrite_rule(
			'^' . $this->url_slug . '/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$',
			'index.php?' . $this->post_type . '=$matches[1]&feed=$matches[2]',
			'top'
		);
		
		// Add a comment feed rule if needed
		add_rewrite_rule(
			'^' . $this->url_slug . '/([^/]+)/(feed|rdf|rss|rss2|atom)/?$',
			'index.php?' . $this->post_type . '=$matches[1]&feed=$matches[2]',
			'top'
		);
		
		// Add a trackback rule if needed
		add_rewrite_rule(
			'^' . $this->url_slug . '/([^/]+)/trackback/?$',
			'index.php?' . $this->post_type . '=$matches[1]&trackback=1',
			'top'
		);
		
		// We don't need an archive page rule since we're using a page with shortcode

		// Add rewrite tag to ensure WordPress recognizes our custom permalink structure
		add_rewrite_tag('%' . $this->post_type . '%', '([^/]+)');
		
		// error_log('Employer Stories CPT: Added rewrite rules for ' . $this->url_slug . ' and removed rules for ' . $this->post_type);

		// Force flush rewrite rules on first load after activation
		static $flushed = false;
		if (!$flushed) {
			$wp_rewrite->flush_rules(true);
			$flushed = true;
			// error_log('Employer Stories CPT: Flushed rewrite rules during page load');
			
			// Direct database update for existing posts to ensure correct permalinks
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = %s",
					$this->post_type
				)
			);
			
			if (!empty($posts)) {
				foreach ($posts as $post) {
					// Update post meta to force permalink refresh
					update_post_meta($post->ID, '_employer_story_permalink_fixed', time());
					// error_log("Employer Stories CPT: Updated post meta for ID {$post->ID} to refresh permalink");
					
					// Trigger a post update to refresh permalinks
					wp_update_post(array('ID' => $post->ID));
					
					// Clear any cached permalinks
					clean_post_cache($post->ID);
				}
			}
			
			// Also update the permalink structure in the database directly
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = 'rewrite_rules'",
					''
				)
			);
			// error_log('Employer Stories CPT: Cleared rewrite rules in database');
		}

		// Flush rewrite rules - use sparingly, only during development or when needed
		if (isset($_GET['employer_stories_flush']) && current_user_can('manage_options')) {
			$wp_rewrite->flush_rules(true);
		}
	}

	/**
	 * Modify permalinks for our custom post type
	 *
	 * @param string $post_link The default post link
	 * @param WP_Post $post The post object
	 * @param bool $leavename Whether to leave the post name
	 * @param bool $sample Is it a sample permalink
	 * @return string Modified permalink
	 */
	public function modify_permalink_structure($post_link, $post, $leavename, $sample) {
		if ($post->post_type == $this->post_type) {
			// Always force the correct permalink structure regardless of other conditions
			$post_name = $leavename ? '%postname%' : $post->post_name;
			if (empty($post_name) && !$leavename) {
				$post_name = sanitize_title($post->post_title);
			}
			
			// Get the original link for logging
			$original_link = $post_link;
			
			// Force the correct URL structure with the plural slug
			$post_link = home_url($this->url_slug . '/' . $post_name . '/');
			// error_log('Employer Stories CPT: Forced permalink for post ID ' . $post->ID . ': ' . $post_link . ' (original: ' . $original_link . ')');
		}
		return $post_link;
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
			$permalink[0] = str_replace($this->post_type, $this->url_slug, $permalink[0]);
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
			$view_node->href = str_replace($this->post_type, $this->url_slug, $view_node->href);
			$wp_admin_bar->add_node($view_node);
		}
	}

	/**
	 * Register the custom post type
	 */
	public function register_post_type() {
		// error_log('Employer Stories CPT: register_post_type method called');

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
			// error_log('Employer Stories CPT: Post type registered with slug: ' . $this->url_slug);
			
			// Immediately after registering, add our custom rewrite rules
			add_rewrite_rule(
				'^' . $this->url_slug . '/([^/]+)/?$',
				'index.php?' . $this->post_type . '=$matches[1]',
				'top'
			);
			
			// We don't need an archive rewrite rule since we're using a page with shortcode
			
			// Add rewrite tag
			add_rewrite_tag('%' . $this->post_type . '%', '([^/]+)');
			
			// error_log('Employer Stories CPT: Added immediate rewrite rules after registration');
		} else {
			// error_log('Employer Stories CPT: Post type already exists');
		}
	}

	/**
	 * Force the correct permalink structure for employer stories
	 * This is a backup method that runs in addition to modify_permalink_structure
	 *
	 * @param string $permalink The post's permalink
	 * @param WP_Post|object $post The post object
	 * @param bool $leavename Whether to keep the post name
	 * @return string The modified permalink
	 */
	public function force_employer_story_permalink($permalink, $post, $leavename) {
		// Only process our post type
		if (!is_object($post) || $post->post_type !== $this->post_type) {
			return $permalink;
		}
		
		// Get the original link for logging
		$original_link = $permalink;
		
		// Force the correct structure
		$post_name = $leavename ? '%postname%' : $post->post_name;
		if (empty($post_name) && !$leavename) {
			$post_name = sanitize_title($post->post_title);
		}
		
		// Force the correct URL structure with the plural slug
		$forced_link = home_url($this->url_slug . '/' . $post_name . '/');
		
		// Log the change
		// error_log('Employer Stories CPT: Forced permalink in secondary filter: ' . $forced_link . ' (original: ' . $original_link . ')');
		
		// Store the correct permalink in post meta for caching
		if (!$leavename) {
			update_post_meta($post->ID, '_employer_story_permalink', $forced_link);
		}
		
		return $forced_link;
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
		
		// If the path starts with our URL slug
		if (preg_match('|^' . $this->url_slug . '/([^/]+)/?$|', $path, $matches)) {
			$post_name = $matches[1];
			
			// Set the query var for our post type
			$query_vars[$this->post_type] = $post_name;
			// error_log('Employer Stories CPT: Parsed request for ' . $post_name);
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
			
			// Build the new URL
			$new_url = home_url($this->url_slug . '/' . $post_name . '/');
			
			// error_log('Employer Stories CPT: Redirecting from ' . $path . ' to ' . $new_url);
			
			// Redirect to the new URL
			wp_redirect($new_url, 301);
			exit;
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
		$home_label = __('Home', 'employer-stories');
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
	 * Debug rewrite rules - only for admins
	 */
	public function debug_rewrite_rules() {
		// Only run this when a specific query parameter is present
		if (isset($_GET['debug_employer_stories_rewrites']) && current_user_can('manage_options')) {
			global $wp_rewrite;
			
			// Get all rewrite rules
			$rules = $wp_rewrite->wp_rewrite_rules();
			
			// Log our specific rules
			// error_log('Employer Stories CPT: Debugging rewrite rules');
			
			foreach ($rules as $pattern => $query) {
				if (strpos($pattern, $this->post_type) !== false || strpos($pattern, $this->url_slug) !== false) {
					// error_log("Rule: {$pattern} => {$query}");
				}
			}
			
			// Check if our rules exist
			$singular_rule_exists = false;
			$plural_rule_exists = false;
			
			foreach ($rules as $pattern => $query) {
				if (strpos($pattern, '^' . $this->post_type . '/') === 0) {
					$singular_rule_exists = true;
				}
				if (strpos($pattern, '^' . $this->url_slug . '/') === 0) {
					$plural_rule_exists = true;
				}
			}
			
			// error_log('Singular rule exists: ' . ($singular_rule_exists ? 'Yes' : 'No'));
			// error_log('Plural rule exists: ' . ($plural_rule_exists ? 'Yes' : 'No'));
			
			// Force flush rewrite rules if requested
			if (isset($_GET['flush_rules']) && $_GET['flush_rules'] === '1') {
				$wp_rewrite->flush_rules(true);
				// error_log('Employer Stories CPT: Flushed rewrite rules via debug function');
			}
		}
	}
	
	/**
	 * Filter for pre_post_link to ensure our URL structure is used
	 *
	 * @param string $permalink The post's permalink.
	 * @param object $post The post in question.
	 * @return string The filtered permalink.
	 */
	public function pre_post_link($permalink, $post) {
		if ($post->post_type === $this->post_type) {
			// Force our URL structure
			return home_url($this->url_slug . '/%postname%/');
		}
		return $permalink;
	}
	
	/**
	 * Custom rewrite rules for our post type
	 *
	 * @param array $rules The post rewrite rules.
	 * @return array Modified rules.
	 */
	public function custom_post_rewrite_rules($rules) {
		// Add our custom rule at the beginning to ensure it takes precedence
		$new_rules = array(
			$this->url_slug . '/([^/]+)/?$' => 'index.php?' . $this->post_type . '=$matches[1]',
		);
		
		// Remove any rules for the singular post type
		foreach ($rules as $pattern => $query) {
			if (strpos($pattern, $this->post_type . '/') === 0) {
				unset($rules[$pattern]);
			}
		}
		
		// error_log('Employer Stories CPT: Added custom post rewrite rules');
		return array_merge($new_rules, $rules);
	}
	
	/**
	 * Maybe flush rewrite rules - only during development or when needed
	 */
	public function maybe_flush_rules() {
		// Check for our special query parameter
		if (isset($_GET['employer_stories_flush_rules'])) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules(true);
			// error_log('Employer Stories CPT: Flushed rewrite rules via query parameter');
		}
		
		// Alternatively, use a transient to avoid flushing on every page load
		if (get_transient('employer_stories_flush_rules') === false) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules(true);
			set_transient('employer_stories_flush_rules', 1, HOUR_IN_SECONDS);
			// error_log('Employer Stories CPT: Flushed rewrite rules via transient check');
		}
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
			return home_url($this->url_slug . '/');
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
}
