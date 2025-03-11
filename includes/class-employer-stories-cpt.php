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
		error_log('Employer Stories CPT: Constructor called');

		// Register the custom post type
		add_action('init', array($this, 'register_post_type'), 5);

		// Modify permalink structure - highest priority to ensure it runs first
		add_filter('post_type_link', array($this, 'modify_permalink_structure'), 1, 4);
		
		// Add a second filter with even higher priority to ensure our structure is used
		add_filter('post_link', array($this, 'force_employer_story_permalink'), 1, 3);
		add_filter('post_type_link', array($this, 'force_employer_story_permalink'), 1, 3);

		// Fix permalinks in admin
		add_filter('get_sample_permalink', array($this, 'fix_admin_permalink'), 10, 5);

		// Fix admin bar links
		add_action('admin_bar_menu', array($this, 'fix_admin_bar_links'), 999);

		// Register breadcrumbs shortcode
		add_shortcode('employer_story_breadcrumbs', array($this, 'breadcrumbs_shortcode'));

		// Register a function to run after WordPress is loaded to fix permalinks
		add_action('wp_loaded', array($this, 'fix_permalinks_on_load'), 20);
		
		// Add early hook for permalink structure
		add_action('pre_get_posts', array($this, 'fix_query_vars'));
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
			error_log('Employer Stories CPT: Instance created');
			
			// Force flush rewrite rules on first instance creation
			add_action('shutdown', function() {
				global $wp_rewrite;
				$wp_rewrite->flush_rules(true);
				error_log('Employer Stories CPT: Forced rewrite rules flush on instance creation');
			});
		}
		return self::$instance;
	}

	/**
	 * Fix permalinks when WordPress is fully loaded
	 */
	public function fix_permalinks_on_load() {
		global $wp_rewrite, $wpdb;

		// Add our custom permalink structure
		add_rewrite_rule(
			$this->url_slug . '/([^/]+)/?$',
			'index.php?' . $this->post_type . '=$matches[1]',
			'top'
		);

		// Add rewrite tag to ensure WordPress recognizes our custom permalink structure
		add_rewrite_tag('%' . $this->post_type . '%', '([^/]+)');

		// Force flush rewrite rules on first load after activation
		static $flushed = false;
		if (!$flushed) {
			$wp_rewrite->flush_rules(true);
			$flushed = true;
			error_log('Employer Stories CPT: Flushed rewrite rules during page load');
			
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
					error_log("Employer Stories CPT: Updated post meta for ID {$post->ID} to refresh permalink");
					
					// Trigger a post update to refresh permalinks
					wp_update_post(array('ID' => $post->ID));
				}
			}
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
			$post_name = $post->post_name;
			if (empty($post_name)) {
				$post_name = sanitize_title($post->post_title);
			}
			$post_link = home_url($this->url_slug . '/' . $post_name . '/');
			error_log('Forced permalink for post ID ' . $post->ID . ': ' . $post_link);
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
		error_log('Employer Stories CPT: register_post_type method called');

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
				'has_archive' => false,
			);

			register_post_type($this->post_type, $args);
			error_log('Employer Stories CPT: Post type registered with slug: ' . $this->url_slug);
		} else {
			error_log('Employer Stories CPT: Post type already exists');
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
		
		// Force the correct structure
		$post_name = $post->post_name;
		if (empty($post_name)) {
			$post_name = sanitize_title($post->post_title);
		}
		
		$forced_link = home_url($this->url_slug . '/' . $post_name . '/');
		error_log('Employer Stories CPT: Forced permalink in secondary filter: ' . $forced_link);
		
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
		
		error_log('Employer Stories CPT: Added query var for ' . $this->post_type);
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

		// Start breadcrumbs container
		echo '<div class="es-breadcrumbs">';

		// Home link
		echo '<a href="' . esc_url($home_url) . '">' . esc_html($home_label) . '</a>';
		echo '<span class="es-breadcrumb-divider">/</span>';

		if (is_singular($this->post_type)) {
			// Current post (no archive link since we disabled it)
			echo '<span class="breadcrumb_last">' . get_the_title() . '</span>';
		}

		echo '</div>';

		return ob_get_clean();
	}
}
