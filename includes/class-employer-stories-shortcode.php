<?php
/**
 * Shortcode Implementation
 *
 * @package EmployerStories
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Employer_Stories_Shortcode
 *
 * Handles the shortcode functionality for Employer Stories
 */
class Employer_Stories_Shortcode {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Shortcode tag.
	 *
	 * @var string
	 */
	protected $shortcode_tag = 'employer_stories';

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		// Register the shortcode
		add_shortcode($this->shortcode_tag, array($this, 'render_shortcode'));

		// Add shortcode button to editor (optional)
		add_action('admin_init', array($this, 'register_shortcode_button'));
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Register shortcode button for TinyMCE.
	 */
	public function register_shortcode_button() {
		// This is a placeholder for adding a button to the editor
		// Will be implemented if needed
	}

	/**
	 * Render the employer stories shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_shortcode($atts) {
		// Parse shortcode attributes
		$atts = shortcode_atts(array(
			// Display options
			'columns' => 3,                   // Number of columns (1-4)
			'posts_per_page' => 9,            // Number of stories to show
			'pagination' => 'false',          // Show pagination

			// Ordering parameters
			'order' => 'DESC',                // ASC or DESC
			'orderby' => 'date',              // date, title, menu_order, rand

			// Filtering parameters
			'category' => '',                 // Filter by category slug or ID
			'tag' => '',                      // Filter by tag slug or ID
			'include' => '',                  // Include specific story IDs
			'exclude' => '',                  // Exclude specific story IDs

			// Advanced parameters
			'offset' => 0,                    // Number of posts to skip
			'class' => '',                    // Additional CSS classes
		), $atts, $this->shortcode_tag);

		// Convert string boolean values to actual booleans
		$atts['pagination'] = filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN);

		// Convert numeric strings to integers
		foreach (array('columns', 'posts_per_page', 'offset') as $int_att) {
			$atts[$int_att] = absint($atts[$int_att]);
		}

		// Validate columns (1-4)
		$atts['columns'] = max(1, min(4, $atts['columns']));

		// Get the appropriate image size based on columns
		$image_size = Employer_Stories::get_column_image_size($atts['columns']);

		// Set up WP_Query arguments
		$query_args = array(
			'post_type' => 'employer-story',
			'posts_per_page' => $atts['posts_per_page'],
			'order' => $atts['order'],
			'orderby' => $atts['orderby'],
			'offset' => $atts['offset'],
			'ignore_sticky_posts' => true,
		);

		// Handle pagination
		if ($atts['pagination']) {
			$paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
			$query_args['paged'] = $paged;

			// Offset breaks pagination, so we need to handle it differently
			if ($atts['offset'] > 0) {
				$query_args['offset'] = ($paged - 1) * $atts['posts_per_page'] + $atts['offset'];
			}
		}

		// Handle category filtering
		if (!empty($atts['category'])) {
			$cat_args = array();

			// Check if it's an ID or slug
			if (is_numeric($atts['category'])) {
				$cat_args['cat'] = absint($atts['category']);
			} else {
				$cat_args['category_name'] = sanitize_text_field($atts['category']);
			}

			$query_args = array_merge($query_args, $cat_args);
		}

		// Handle tag filtering
		if (!empty($atts['tag'])) {
			$tag_args = array();

			// Check if it's an ID or slug
			if (is_numeric($atts['tag'])) {
				$tag_args['tag_id'] = absint($atts['tag']);
			} else {
				$tag_args['tag'] = sanitize_text_field($atts['tag']);
			}

			$query_args = array_merge($query_args, $tag_args);
		}

		// Handle include parameter
		if (!empty($atts['include'])) {
			$include_ids = array_map('absint', explode(',', $atts['include']));
			$query_args['post__in'] = $include_ids;

			// Override orderby to preserve post__in order if set to default
			if ($atts['orderby'] === 'date') {
				$query_args['orderby'] = 'post__in';
			}
		}

		// Handle exclude parameter
		if (!empty($atts['exclude'])) {
			$exclude_ids = array_map('absint', explode(',', $atts['exclude']));
			$query_args['post__not_in'] = $exclude_ids;
		}

		// Run the query
		$stories_query = new WP_Query($query_args);

		// Start output buffering
		ob_start();

		// Set up column classes based on columns setting
		$grid_classes = 'es-employer-stories-grid row';
		$column_class = 'large-' . (12 / $atts['columns']) . ' medium-6 small-12';

		// Add custom class if provided
		if (!empty($atts['class'])) {
			$grid_classes .= ' ' . sanitize_html_class($atts['class']);
		}

		// Wrapper div with appropriate classes
		echo '<div class="es-employer-stories-container">';

		if ($stories_query->have_posts()) {
			echo '<div class="' . esc_attr($grid_classes) . '">';

			while ($stories_query->have_posts()) {
				$stories_query->the_post();

				echo '<div class="es-employer-story-item col ' . esc_attr($column_class) . '">';
				echo '<article id="employer-story-' . get_the_ID() . '" class="es-employer-story-card">';

				// Get the permalink using the CPT class's method
				$permalink = Employer_Stories_CPT::get_instance()->get_permalink(get_the_ID());
				echo '<a href="' . esc_url($permalink) . '" class="es-employer-story-link">';

				// Display featured image if available
				if (has_post_thumbnail()) {
					echo '<div class="es-employer-story-thumbnail">';
					the_post_thumbnail($image_size, array('class' => 'es-card-image'));
					echo '</div>';
				} else {
					// Placeholder for posts without featured image
					echo '<div class="es-employer-story-thumbnail es-no-image">';
					echo '<div class="es-placeholder">' . __('No Image', 'employer-stories') . '</div>';
					echo '</div>';
				}

				echo '</a>'; // End .es-employer-story-link

				echo '</article>'; // End .es-employer-story-card
				echo '</div>'; // End .es-employer-story-item
			}

			echo '</div>'; // End .es-employer-stories-grid

			// Pagination
			if ($atts['pagination']) {
				echo '<div class="es-pagination">';

				$big = 999999999; // need an unlikely integer

				echo paginate_links(array(
					'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
					'format' => '?paged=%#%',
					'current' => max(1, get_query_var('paged')),
					'total' => $stories_query->max_num_pages,
					'prev_text' => '&larr; ' . __('Previous', 'employer-stories'),
					'next_text' => __('Next', 'employer-stories') . ' &rarr;',
				));

				echo '</div>';
			}

		} else {
			// No stories found
			echo '<div class="es-no-employer-stories">';
			echo '<p>' . __('No employer stories found.', 'employer-stories') . '</p>';
			echo '</div>';
		}

		echo '</div>'; // End .es-employer-stories-container

		// Reset post data
		wp_reset_postdata();

		// Return the buffered content
		return ob_get_clean();
	}
}
