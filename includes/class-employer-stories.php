<?php
/**
 * Main Employer Stories class
 *
 * @package EmployerStories
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Class Employer_Stories
 *
 * Main class for handling Employer Stories functionality
 */
class Employer_Stories {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Custom image sizes.
	 *
	 * @var array
	 */
	protected $image_sizes = array(
		'es-3-column' => array(515, 343, true),  // Default 3-column size
		'es-2-column' => array(770, 512, true),  // 2-column size
		'es-4-column' => array(385, 257, true),  // 4-column size
		'es-1-column' => array(1200, 800, true), // 1-column size
	);

	/**
	 * Initialize the class.
	 */
	public function __construct() {
		$this->setup_actions();
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
	 * Setup actions and filters
	 */
	private function setup_actions() {
		// Register custom image sizes
		add_action('after_setup_theme', array($this, 'register_image_sizes'));

		// Enqueue scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

		// Add template filter for single posts only
		add_filter('single_template', array($this, 'load_single_template'));

		// Add body class
		add_filter('body_class', array($this, 'add_body_class'));
	}

	/**
	 * Register custom image sizes
	 */
	public function register_image_sizes() {
		foreach ($this->image_sizes as $name => $dimensions) {
			add_image_size($name, $dimensions[0], $dimensions[1], $dimensions[2]);
		}
	}

	/**
	 * Get the appropriate image size based on column count
	 *
	 * @param int $columns Number of columns
	 * @return string Image size name
	 */
	public static function get_column_image_size($columns) {
		switch ($columns) {
			case 1:
				return 'es-1-column';
			case 2:
				return 'es-2-column';
			case 4:
				return 'es-4-column';
			case 3:
			default:
				return 'es-3-column';
		}
	}

	/**
	 * Enqueue scripts and styles but only on relevant pages
	 */
	public function enqueue_scripts() {
		// Only load on single employer story or archive page
		if (!$this->is_employer_story_page()) {
			return;
		}

		// Common CSS
		$this->enqueue_style(
			'employer-stories-common-css',
			'assets/css/employer-stories-common.css'
		);

		// Always load the archive/grid CSS for shortcodes
		$this->enqueue_style(
			'employer-stories-archive-css',
			'assets/css/employer-stories-archive.css'
		);

		// Single CSS - load only on single employer story pages
		if (is_singular('employer-story')) {
			$this->enqueue_style(
				'employer-stories-single-css',
				'assets/css/employer-stories-single.css'
			);
		}

		// JavaScript
		$this->enqueue_script(
			'employer-stories-js',
			'assets/js/employer-stories.js',
			array('jquery')
		);
	}

	/**
	 * Helper to enqueue styles with version based on file modification time
	 *
	 * @param string $handle Style handle
	 * @param string $path Style path relative to plugin directory
	 */
	private function enqueue_style($handle, $path) {
		$full_path = ES_PLUGIN_DIR . $path;
		$url = ES_PLUGIN_URL . $path;

		if (file_exists($full_path)) {
			$version = filemtime($full_path);
			wp_enqueue_style($handle, $url, array(), $version);
		}
	}

	/**
	 * Helper to enqueue scripts with version based on file modification time
	 *
	 * @param string $handle Script handle
	 * @param string $path Script path relative to plugin directory
	 * @param array $deps Script dependencies
	 */
	private function enqueue_script($handle, $path, $deps = array()) {
		$full_path = ES_PLUGIN_DIR . $path;
		$url = ES_PLUGIN_URL . $path;

		if (file_exists($full_path)) {
			$version = filemtime($full_path);
			wp_enqueue_script($handle, $url, $deps, $version, true);
		}
	}

	/**
	 * Check if current page is related to employer stories
	 *
	 * @return bool
	 */
	private function is_employer_story_page() {
		return is_singular('employer-story');
	}

	/**
	 * Load single template for employer story
	 *
	 * @param string $template Template path
	 * @return string Modified template path
	 */
	public function load_single_template($template) {
		if (is_singular('employer-story')) {
			$custom_template = ES_TEMPLATES_DIR . 'single-employer-story.php';
			if (file_exists($custom_template)) {
				return $custom_template;
			}
		}
		return $template;
	}


	/**
	 * Add custom body class for employer story pages
	 *
	 * @param array $classes Body classes
	 * @return array Modified body classes
	 */
	public function add_body_class($classes) {
		if ($this->is_employer_story_page()) {
			$classes[] = 'employer-stories-plugin';

			if (is_singular('employer-story')) {
				$classes[] = 'employer-story-single';
			}
		}

		return $classes;
	}
}
