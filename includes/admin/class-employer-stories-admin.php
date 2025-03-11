<?php
/**
 * Admin Functionality
 *
 * @package EmployerStories
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Employer_Stories_Admin
 *
 * Handles the admin-specific functionality for Employer Stories
 */
class Employer_Stories_Admin {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		// Add admin menu items
		add_action('admin_menu', array($this, 'add_admin_menu'), 20);

		// Add settings link to plugin page
		add_filter('plugin_action_links_' . ES_PLUGIN_BASENAME, array($this, 'add_plugin_links'));

		// Enqueue admin scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

		// Add admin notices
		add_action('admin_notices', array($this, 'admin_notices'));

		// Add custom meta boxes
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

		// Add filter for admin columns
		add_filter('manage_employer-story_posts_columns', array($this, 'set_custom_columns'));
		add_action('manage_employer-story_posts_custom_column', array($this, 'custom_column_content'), 10, 2);

		// Add filter for sorting columns
		add_filter('manage_edit-employer-story_sortable_columns', array($this, 'set_sortable_columns'));
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
	 * Add admin menu items
	 */
	public function add_admin_menu() {
		// Add settings page if needed
		add_submenu_page(
			'edit.php?post_type=employer-story',
			__('Employer Stories Settings', 'employer-stories'),
			__('Settings', 'employer-stories'),
			'manage_options',
			'employer-stories-settings',
			array($this, 'settings_page')
		);
	}

	/**
	 * Settings page callback
	 */
	public function settings_page() {
		?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
				<?php
				// Output security fields
				settings_fields('employer_stories_settings');

				// Output setting sections
				do_settings_sections('employer_stories_settings');

				// Submit button
				submit_button();
				?>
            </form>
        </div>
		<?php
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		// Register settings for options page if needed
		register_setting(
			'employer_stories_settings', // Option group
			'employer_stories_options',  // Option name
			array($this, 'sanitize_settings') // Sanitize callback
		);

		// Add settings section
		add_settings_section(
			'employer_stories_display_settings', // ID
			__('Display Settings', 'employer-stories'), // Title
			array($this, 'settings_section_callback'), // Callback
			'employer_stories_settings' // Page
		);

		// Add settings fields
		add_settings_field(
			'default_columns', // ID
			__('Default Columns', 'employer-stories'), // Title
			array($this, 'default_columns_callback'), // Callback
			'employer_stories_settings', // Page
			'employer_stories_display_settings' // Section
		);
	}

	/**
	 * Settings section callback
	 */
	public function settings_section_callback() {
		echo '<p>' . __('Configure display settings for Employer Stories shortcode and archive.', 'employer-stories') . '</p>';
	}

	/**
	 * Default columns setting callback
	 */
	public function default_columns_callback() {
		$options = get_option('employer_stories_options');
		$default_columns = isset($options['default_columns']) ? $options['default_columns'] : 3;
		?>
        <select name="employer_stories_options[default_columns]">
            <option value="1" <?php selected($default_columns, 1); ?>><?php _e('1 Column', 'employer-stories'); ?></option>
            <option value="2" <?php selected($default_columns, 2); ?>><?php _e('2 Columns', 'employer-stories'); ?></option>
            <option value="3" <?php selected($default_columns, 3); ?>><?php _e('3 Columns', 'employer-stories'); ?></option>
            <option value="4" <?php selected($default_columns, 4); ?>><?php _e('4 Columns', 'employer-stories'); ?></option>
        </select>
        <p class="description"><?php _e('Default number of columns to display in shortcode and archive.', 'employer-stories'); ?></p>
		<?php
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input The settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings($input) {
		$sanitized = array();

		if (isset($input['default_columns'])) {
			$sanitized['default_columns'] = absint($input['default_columns']);
			// Make sure it's between 1 and 4
			$sanitized['default_columns'] = max(1, min(4, $sanitized['default_columns']));
		}

		return $sanitized;
	}

	/**
	 * Add links to plugin page
	 *
	 * @param array $links Existing plugin links.
	 * @return array Modified plugin links.
	 */
	public function add_plugin_links($links) {
		$plugin_links = array(
			'<a href="' . admin_url('edit.php?post_type=employer-story&page=employer-stories-settings') . '">' . __('Settings', 'employer-stories') . '</a>',
		);

		return array_merge($plugin_links, $links);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_assets($hook) {
		$screen = get_current_screen();

		// Only load on our admin pages or employer story edit screens
		if (!$screen ||
		    !in_array($screen->id, array(
			    'employer-story',
			    'edit-employer-story',
			    'employer-story_page_employer-stories-settings',
			    'employer-story_page_employer-stories-help'
		    ))
		) {
			return;
		}

		// CSS - only enqueue if file exists
		$css_file = ES_PLUGIN_DIR . 'assets/css/employer-stories-admin.css';
		if (file_exists($css_file)) {
			wp_enqueue_style(
				'employer-stories-admin-css',
				ES_PLUGIN_URL . 'assets/css/employer-stories-admin.css',
				array(),
				filemtime($css_file)
			);
		}

		// JavaScript - only enqueue if file exists
		$js_file = ES_PLUGIN_DIR . 'assets/js/employer-stories-admin.js';
		if (file_exists($js_file)) {
			wp_enqueue_script(
				'employer-stories-admin-js',
				ES_PLUGIN_URL . 'assets/js/employer-stories-admin.js',
				array('jquery'),
				filemtime($js_file),
				true
			);
		}
	}

	/**
	 * Admin notices
	 */
	public function admin_notices() {
		// Display any admin notices here
	}

	/**
	 * Add custom meta boxes
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'employer-stories-shortcode',
			__('Employer Stories Shortcode', 'employer-stories'),
			array($this, 'shortcode_meta_box'),
			'employer-story',
			'side',
			'default'
		);
	}

	/**
	 * Shortcode meta box callback
	 *
	 * @param WP_Post $post The post object.
	 */
	public function shortcode_meta_box($post) {
		?>
        <p><?php _e('Use this shortcode to display this employer story:', 'employer-stories'); ?></p>
        <code>[employer_stories include="<?php echo $post->ID; ?>"]</code>

        <p><?php _e('Display all employer stories:', 'employer-stories'); ?></p>
        <code>[employer_stories]</code>

        <p><a href="<?php echo admin_url('edit.php?post_type=employer-story&page=employer-stories-help'); ?>"><?php _e('View all shortcode options', 'employer-stories'); ?></a></p>
		<?php
	}

	/**
	 * Set custom columns for the employer story post type
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function set_custom_columns($columns) {
		$new_columns = array();

		// Add checkbox and title first
		if (isset($columns['cb'])) {
			$new_columns['cb'] = $columns['cb'];
		}

		if (isset($columns['title'])) {
			$new_columns['title'] = $columns['title'];
		}

		// Add our custom columns
		$new_columns['featured_image'] = __('Featured Image', 'employer-stories');
		$new_columns['employer'] = __('Employer', 'employer-stories');

		// Add remaining columns
		foreach ($columns as $key => $value) {
			if (!isset($new_columns[$key])) {
				$new_columns[$key] = $value;
			}
		}

		return $new_columns;
	}

	/**
	 * Custom column content
	 *
	 * @param string $column Column name.
	 * @param int $post_id Post ID.
	 */
	public function custom_column_content($column, $post_id) {
		switch ($column) {
			case 'featured_image':
				if (has_post_thumbnail($post_id)) {
					echo '<a href="' . get_edit_post_link($post_id) . '">';
					echo get_the_post_thumbnail($post_id, array(50, 50));
					echo '</a>';
				} else {
					echo '—';
				}
				break;

			case 'employer':
				if (function_exists('get_field')) {
					$employer_stats = get_field('employer_stats', $post_id);
					if ($employer_stats && !empty($employer_stats['employer'])) {
						echo esc_html($employer_stats['employer']);
					} else {
						echo '—';
					}
				} else {
					echo '—';
				}
				break;
		}
	}

	/**
	 * Set sortable columns
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function set_sortable_columns($columns) {
		$columns['employer'] = 'employer';
		return $columns;
	}
}
