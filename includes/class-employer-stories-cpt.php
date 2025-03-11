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

		// Modify permalink structure
		add_filter('post_type_link', array($this, 'modify_permalink_structure'), 10, 4);

		// Fix permalinks in admin
		add_filter('get_sample_permalink', array($this, 'fix_admin_permalink'), 10, 5);

		// Fix admin bar links
		add_action('admin_bar_menu', array($this, 'fix_admin_bar_links'), 999);

		// Register breadcrumbs shortcode
		add_shortcode('employer_story_breadcrumbs', array($this, 'breadcrumbs_shortcode'));

		// Register a function to run after WordPress is loaded to fix permalinks
		add_action('wp_loaded', array($this, 'fix_permalinks_on_load'), 20);
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
		}
		return self::$instance;
	}

	/**
	 * Fix permalinks when WordPress is fully loaded
	 */
	public function fix_permalinks_on_load() {
		global $wp_rewrite;

		// Add our custom permalink structure
		add_rewrite_rule(
			$this->url_slug . '/([^/]+)/?$',
			'index.php?' . $this->post_type . '=$matches[1]',
			'top'
		);

		// Add rewrite for the archive
		add_rewrite_rule(
			$this->url_slug . '/?$',
			'index.php?post_type=' . $this->post_type,
			'top'
		);

		// Flush rewrite rules - use sparingly, only during development or when needed
		if (isset($_GET['employer_stories_flush']) && current_user_can('manage_options')) {
			$wp_rewrite->flush_rules();
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
			// Replace the default slug with our custom slug
			if ($sample || !$leavename) {
				error_log('Modifying permalink for post ID ' . $post->ID);
				$post_link = home_url($this->url_slug . '/' . $post->post_name . '/');
			}
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
				),
				'has_archive' => true,
			);

			register_post_type($this->post_type, $args);
			error_log('Employer Stories CPT: Post type registered with slug: ' . $this->url_slug);
		} else {
			error_log('Employer Stories CPT: Post type already exists');
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

		// Start breadcrumbs container
		echo '<div class="es-breadcrumbs">';

		// Home link
		echo '<a href="' . esc_url($home_url) . '">' . esc_html($home_label) . '</a>';
		echo '<span class="es-breadcrumb-divider">/</span>';

		if (is_singular($this->post_type)) {
			// Archive link
			$archive_url = home_url($this->url_slug . '/');
			$archive_label = __('Employer Stories', 'employer-stories');
			echo '<a href="' . esc_url($archive_url) . '">' . esc_html($archive_label) . '</a>';
			echo '<span class="es-breadcrumb-divider">/</span>';

			// Current post
			echo '<span class="breadcrumb_last">' . get_the_title() . '</span>';
		} elseif (is_post_type_archive($this->post_type)) {
			// Just archive title for the archive page
			echo '<span class="breadcrumb_last">' . __('Employer Stories', 'employer-stories') . '</span>';
		}

		echo '</div>';

		return ob_get_clean();
	}
}