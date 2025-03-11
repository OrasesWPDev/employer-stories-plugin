<?php
/**
 * Plugin Name: Employer Stories Plugin
 * Plugin URI:
 * Description: A plugin to manage and display Employer Stories with custom fields using ACF Pro.
 * Version: 1.0.0
 * Author: Orases
 * Author URI: https://orases.com
 * Text Domain: employer-stories
 * Requires at least: 5.7
 * Requires PHP: 7.4
 *
 * @package EmployerStories
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('ES_PLUGIN_FILE', __FILE__);
define('ES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ES_PLUGIN_VERSION', '1.0.0');
define('ES_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Define directory constants
define('ES_INCLUDES_DIR', ES_PLUGIN_DIR . 'includes/');
define('ES_TEMPLATES_DIR', ES_PLUGIN_DIR . 'templates/');
define('ES_ASSETS_DIR', ES_PLUGIN_DIR . 'assets/');
define('ES_CSS_DIR', ES_ASSETS_DIR . 'css/');
define('ES_JS_DIR', ES_ASSETS_DIR . 'js/');
define('ES_IMAGES_DIR', ES_ASSETS_DIR . 'images/');
define('ES_ACF_JSON_DIR', ES_PLUGIN_DIR . 'acf-json/');
define('ES_LANGUAGES_DIR', ES_PLUGIN_DIR . 'languages/');

// Define URL constants
define('ES_ASSETS_URL', ES_PLUGIN_URL . 'assets/');
define('ES_CSS_URL', ES_ASSETS_URL . 'css/');
define('ES_JS_URL', ES_ASSETS_URL . 'js/');
define('ES_IMAGES_URL', ES_ASSETS_URL . 'images/');

/**
 * Main Employer Stories Plugin Class
 */
final class Employer_Stories_Plugin {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Required files for the plugin.
	 *
	 * @var array
	 */
	private $required_files = array();

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		// Log plugin initialization
		error_log('Employer Stories Plugin: Initializing');

		// Set up required files
		$this->set_required_files();

		// Initialize directory structure
		$this->init_directories();

		// Plugin activation/deactivation hooks
		register_activation_hook(ES_PLUGIN_FILE, array($this, 'activate'));
		register_deactivation_hook(ES_PLUGIN_FILE, array($this, 'deactivate'));

		// Load plugin dependencies
		$this->load_dependencies();

		// Initialize plugin components
		add_action('plugins_loaded', array($this, 'init'));

		// Add debug information
		if (is_admin()) {
			add_action('admin_notices', array($this, 'debug_notice'));
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Set up the list of required files.
	 */
	private function set_required_files() {
		$this->required_files = array(
			// Core functionality
			'core' => array(
				ES_INCLUDES_DIR . 'class-employer-stories-cpt.php',
				ES_INCLUDES_DIR . 'class-employer-stories.php',
			),
			// ACF integration
			'acf' => array(
				ES_INCLUDES_DIR . 'class-employer-stories-acf.php',
			),
			// Shortcodes
			'shortcodes' => array(
				ES_INCLUDES_DIR . 'class-employer-stories-shortcode.php',
			),
			// Admin functionality
			'admin' => array(
				ES_INCLUDES_DIR . 'admin/class-employer-stories-admin.php',
			),
		);
	}

	/**
	 * Initialize directory structure.
	 */
	private function init_directories() {
		$directories = array(
			ES_INCLUDES_DIR,
			ES_INCLUDES_DIR . 'admin/',
			ES_TEMPLATES_DIR,
			ES_ASSETS_DIR,
			ES_CSS_DIR,
			ES_JS_DIR,
			ES_IMAGES_DIR,
			ES_ACF_JSON_DIR,
			ES_LANGUAGES_DIR
		);

		foreach ($directories as $directory) {
			if (!file_exists($directory)) {
				wp_mkdir_p($directory);
				error_log("Employer Stories Plugin: Created directory {$directory}");
			}
		}

		// Create protection files
		$this->create_protection_files($directories);
	}

	/**
	 * Create protection files to prevent direct access.
	 *
	 * @param array $directories List of directories to protect
	 */
	private function create_protection_files($directories) {
		foreach ($directories as $directory) {
			if (file_exists($directory) && !file_exists($directory . 'index.php')) {
				@file_put_contents($directory . 'index.php', '<?php // Silence is golden.');
			}
		}
	}

	/**
	 * Load the required dependencies.
	 */
	private function load_dependencies() {
		// Log dependency loading
		error_log('Employer Stories Plugin: Loading dependencies');

		// Load core files first
		foreach ($this->required_files['core'] as $file) {
			if (file_exists($file)) {
				require_once $file;
				error_log("Employer Stories Plugin: Loaded {$file}");
			} else {
				error_log("Employer Stories Plugin: Failed to load {$file} - file not found");
			}
		}

		// Load ACF integration if ACF is active
		if (class_exists('ACF')) {
			error_log('Employer Stories Plugin: ACF class exists, loading ACF integration');
			foreach ($this->required_files['acf'] as $file) {
				if (file_exists($file)) {
					require_once $file;
					error_log("Employer Stories Plugin: Loaded {$file}");
				} else {
					error_log("Employer Stories Plugin: Failed to load {$file} - file not found");
				}
			}
		} else {
			error_log('Employer Stories Plugin: ACF class does not exist');
		}

		// Load remaining files
		$remaining_types = array('shortcodes', 'admin');
		foreach ($remaining_types as $type) {
			if (!empty($this->required_files[$type])) {
				foreach ($this->required_files[$type] as $file) {
					if (file_exists($file)) {
						require_once $file;
						error_log("Employer Stories Plugin: Loaded {$file}");
					} else {
						error_log("Employer Stories Plugin: Failed to load {$file} - file not found");
					}
				}
			}
		}
	}

	/**
	 * Initialize the plugin components.
	 */
	public function init() {
		error_log('Employer Stories Plugin: init() method called');

		// Check for ACF Pro dependency
		if (!$this->check_dependencies()) {
			error_log('Employer Stories Plugin: Dependencies not met, aborting initialization');
			return;
		}

		// Initialize ACF integration first - this handles both field groups and CPT registration via JSON
		if (class_exists('Employer_Stories_ACF')) {
			Employer_Stories_ACF::get_instance();
			error_log('Employer Stories Plugin: ACF class initialized');
		} else {
			error_log('Employer Stories Plugin: ACF class not found');
		}

		// Initialize main plugin functionality
		if (class_exists('Employer_Stories')) {
			Employer_Stories::get_instance();
			error_log('Employer Stories Plugin: Main class initialized');
		} else {
			error_log('Employer Stories Plugin: Main class not found');
		}

		// Initialize shortcode functionality
		if (class_exists('Employer_Stories_Shortcode')) {
			Employer_Stories_Shortcode::get_instance();
			error_log('Employer Stories Plugin: Shortcode class initialized');
		} else {
			error_log('Employer Stories Plugin: Shortcode class not found');
		}

		// Initialize admin
		if (is_admin() && class_exists('Employer_Stories_Admin')) {
			Employer_Stories_Admin::get_instance();
			error_log('Employer Stories Plugin: Admin class initialized');
		} else if (is_admin()) {
			error_log('Employer Stories Plugin: Admin class not found');
		}

		// Load text domain
		$this->load_text_domain();
	}

	/**
	 * Debug notice for admin.
	 */
	public function debug_notice() {
		// Debug notices temporarily disabled
		return;
	}

	/**
	 * Save ACF JSON to plugin directory.
	 *
	 * @param string $path The path to save ACF JSON
	 * @return string The modified path
	 */
	public function acf_json_save_point($path) {
		error_log('Employer Stories Plugin: Setting ACF JSON save point to ' . ES_ACF_JSON_DIR);
		return ES_ACF_JSON_DIR;
	}

	/**
	 * Load ACF JSON from plugin directory.
	 *
	 * @param array $paths The paths to load ACF JSON from
	 * @return array The modified paths
	 */
	public function acf_json_load_point($paths) {
		$paths[] = ES_ACF_JSON_DIR;
		error_log('Employer Stories Plugin: Adding ACF JSON load point: ' . ES_ACF_JSON_DIR);
		return $paths;
	}

	/**
	 * Check plugin dependencies.
	 *
	 * @return bool True if dependencies are met
	 */
	private function check_dependencies() {
		// Check if ACF Pro is active
		if (!class_exists('ACF')) {
			add_action('admin_notices', array($this, 'acf_pro_notice'));
			return false;
		}
		return true;
	}

	/**
	 * Admin notice for ACF Pro dependency.
	 */
	public function acf_pro_notice() {
		?>
        <div class="notice notice-error">
            <p><?php _e('Employer Stories Plugin requires Advanced Custom Fields Pro to be installed and activated.', 'employer-stories'); ?></p>
        </div>
		<?php
	}

	/**
	 * Load text domain for translations.
	 */
	private function load_text_domain() {
		load_plugin_textdomain(
			'employer-stories',
			false,
			dirname(ES_PLUGIN_BASENAME) . '/languages'
		);
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		error_log('Employer Stories Plugin: Activating plugin');

		// Ensure our CPT is registered before flushing rewrite rules
		if (class_exists('Employer_Stories_CPT')) {
			Employer_Stories_CPT::get_instance()->register_post_type();
			error_log('Employer Stories Plugin: Registered CPT during activation');
		}

		// Flush rewrite rules on activation
		global $wp_rewrite;
		$wp_rewrite->flush_rules(true);
		error_log('Employer Stories Plugin: Flushed rewrite rules');
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Flush rewrite rules on deactivation
		flush_rewrite_rules();
		error_log('Employer Stories Plugin: Deactivated plugin and flushed rewrite rules');
	}
}

// Initialize the plugin
Employer_Stories_Plugin::get_instance();
