<?php
/**
 * Help Documentation Page
 *
 * @package EmployerStories
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class to handle Employer Stories help documentation.
 */
class Employer_Stories_Help {
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
        // Add submenu page
        add_action('admin_menu', array($this, 'add_help_page'), 30);
        // Add admin-specific styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        // Add plugin action links
        add_filter('plugin_action_links_' . ES_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
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
     * Add Help/Documentation page for the plugin
     */
    public function add_help_page() {
        add_submenu_page(
            'edit.php?post_type=employer-story',  // Parent menu slug
            'Employer Stories Help',             // Page title
            'Help & Documentation',              // Menu title
            'edit_posts',                        // Capability
            'employer-stories-help',             // Menu slug
            array($this, 'help_page_content')    // Callback function
        );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing plugin action links
     * @return array Modified links
     */
    public function add_plugin_action_links($links) {
        $help_link = '<a href="' . admin_url('edit.php?post_type=employer-story&page=employer-stories-help') . '">' . __('Help', 'employer-stories') . '</a>';
        array_unshift($links, $help_link);
        return $links;
    }

    /**
     * Enqueue styles for admin help page
     *
     * @param string $hook Current admin page
     */
    public function enqueue_admin_styles($hook) {
        // Only load on our help page
        if ('employer-story_page_employer-stories-help' !== $hook) {
            return;
        }

        // Add inline styles for help page
        wp_add_inline_style('wp-admin', $this->get_admin_styles());
    }

    /**
     * Get admin styles for help page
     *
     * @return string CSS styles
     */
    private function get_admin_styles() {
        return '
            .es-help-wrap {
                max-width: 1300px;
                margin: 20px 20px 0 0;
            }
            .es-help-header {
                background: #fff;
                padding: 20px;
                border-radius: 3px;
                margin-bottom: 20px;
                border-left: 4px solid #0073aa;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .es-help-section {
                background: #fff;
                padding: 20px;
                border-radius: 3px;
                margin-bottom: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
                overflow-x: auto;
            }
            .es-help-section h2 {
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
                margin-top: 0;
            }
            .es-help-section h3 {
                margin-top: 1.5em;
                margin-bottom: 0.5em;
            }
            .es-help-section table {
                border-collapse: collapse;
                width: 100%;
                margin: 1em 0;
                table-layout: fixed;
            }
            .es-help-section table th,
            .es-help-section table td {
                text-align: left;
                padding: 8px;
                border: 1px solid #ddd;
                vertical-align: top;
                word-wrap: break-word;
                word-break: break-word;
                hyphens: auto;
            }
            .es-help-section table th:nth-child(1),
            .es-help-section table td:nth-child(1) {
                width: 15%;
            }
            .es-help-section table th:nth-child(2),
            .es-help-section table td:nth-child(2) {
                width: 25%;
            }
            .es-help-section table th:nth-child(3),
            .es-help-section table td:nth-child(3) {
                width: 10%;
            }
            .es-help-section table th:nth-child(4),
            .es-help-section table td:nth-child(4) {
                width: 20%;
            }
            .es-help-section table th:nth-child(5),
            .es-help-section table td:nth-child(5) {
                width: 30%;
            }
            .es-help-section table th {
                background-color: #f8f8f8;
                font-weight: 600;
            }
            .es-help-section table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .es-help-section code {
                background: #f8f8f8;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 13px;
                color: #0073aa;
                display: inline-block;
                max-width: 100%;
                overflow-wrap: break-word;
                white-space: normal;
            }
            .es-shortcode-example {
                background: #f8f8f8;
                padding: 15px;
                border-left: 4px solid #0073aa;
                font-family: monospace;
                margin: 10px 0;
                overflow-x: auto;
                white-space: pre-wrap;
                word-break: break-word;
            }
        ';
    }

    /**
     * Content for help page
     */
    public function help_page_content() {
        ?>
        <div class="wrap es-help-wrap">
            <div class="es-help-header">
                <h1><?php esc_html_e('Employer Stories - Documentation', 'employer-stories'); ?></h1>
                <p><?php esc_html_e('This page provides documentation on how to use Employer Stories shortcodes and features.', 'employer-stories'); ?></p>
            </div>

            <!-- Overview Section -->
            <div class="es-help-section">
                <h2><?php esc_html_e('Overview', 'employer-stories'); ?></h2>
                <p><?php esc_html_e('Employer Stories allows you to create and display employer story profiles on your site. The plugin provides a shortcode to display stories in a grid layout.', 'employer-stories'); ?></p>
                <ul>
                    <li><code>[employer_stories]</code> - <?php esc_html_e('Display multiple employer stories in a grid layout', 'employer-stories'); ?></li>
                    <li><code>[employer_story_breadcrumbs]</code> - <?php esc_html_e('Display breadcrumb navigation for employer stories', 'employer-stories'); ?></li>
                </ul>
            </div>

            <!-- Shortcode Section -->
            <div class="es-help-section">
                <h2><?php esc_html_e('Shortcode: [employer_stories]', 'employer-stories'); ?></h2>
                <p><?php esc_html_e('This shortcode displays a grid of Employer Stories with various customization options.', 'employer-stories'); ?></p>

                <h3><?php esc_html_e('Basic Usage', 'employer-stories'); ?></h3>
                <div class="es-shortcode-example">
                    [employer_stories]
                </div>

                <h3><?php esc_html_e('Display Options', 'employer-stories'); ?></h3>
                <table>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Description', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Default', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Options', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Examples', 'employer-stories'); ?></th>
                    </tr>
                    <tr>
                        <td><code>columns</code></td>
                        <td><?php esc_html_e('Number of columns in grid view', 'employer-stories'); ?></td>
                        <td><code>3</code></td>
                        <td><?php esc_html_e('1-4', 'employer-stories'); ?></td>
                        <td><code>columns="2"</code><br><code>columns="4"</code></td>
                    </tr>
                    <tr>
                        <td><code>posts_per_page</code></td>
                        <td><?php esc_html_e('Number of stories to display', 'employer-stories'); ?></td>
                        <td><code>9</code></td>
                        <td><?php esc_html_e('any number, -1 for all', 'employer-stories'); ?></td>
                        <td><code>posts_per_page="6"</code><br><code>posts_per_page="-1"</code></td>
                    </tr>
                    <tr>
                        <td><code>pagination</code></td>
                        <td><?php esc_html_e('Whether to show pagination', 'employer-stories'); ?></td>
                        <td><code>false</code></td>
                        <td><code>true</code>, <code>false</code></td>
                        <td><code>pagination="true"</code></td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Ordering Parameters', 'employer-stories'); ?></h3>
                <table>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Description', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Default', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Options', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Examples', 'employer-stories'); ?></th>
                    </tr>
                    <tr>
                        <td><code>order</code></td>
                        <td><?php esc_html_e('Sort order', 'employer-stories'); ?></td>
                        <td><code>DESC</code></td>
                        <td><code>ASC</code>, <code>DESC</code></td>
                        <td><code>order="ASC"</code></td>
                    </tr>
                    <tr>
                        <td><code>orderby</code></td>
                        <td><?php esc_html_e('Field to order by', 'employer-stories'); ?></td>
                        <td><code>date</code></td>
                        <td><code>date</code>, <code>title</code>, <code>menu_order</code>, <code>rand</code></td>
                        <td><code>orderby="title"</code><br><code>orderby="rand"</code></td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Filtering Parameters', 'employer-stories'); ?></h3>
                <table>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Description', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Default', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Options', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Examples', 'employer-stories'); ?></th>
                    </tr>
                    <tr>
                        <td><code>category</code></td>
                        <td><?php esc_html_e('Filter by category', 'employer-stories'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('category slug or ID', 'employer-stories'); ?></td>
                        <td><code>category="featured"</code><br><code>category="5"</code></td>
                    </tr>
                    <tr>
                        <td><code>tag</code></td>
                        <td><?php esc_html_e('Filter by tag', 'employer-stories'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('tag slug or ID', 'employer-stories'); ?></td>
                        <td><code>tag="healthcare"</code><br><code>tag="8"</code></td>
                    </tr>
                    <tr>
                        <td><code>include</code></td>
                        <td><?php esc_html_e('Include only specific stories', 'employer-stories'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('IDs separated by commas', 'employer-stories'); ?></td>
                        <td><code>include="42,51,90"</code></td>
                    </tr>
                    <tr>
                        <td><code>exclude</code></td>
                        <td><?php esc_html_e('Exclude specific stories', 'employer-stories'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('IDs separated by commas', 'employer-stories'); ?></td>
                        <td><code>exclude="42,51,90"</code></td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Advanced Parameters', 'employer-stories'); ?></h3>
                <table>
                    <tr>
                        <th><?php esc_html_e('Parameter', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Description', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Default', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Options', 'employer-stories'); ?></th>
                        <th><?php esc_html_e('Examples', 'employer-stories'); ?></th>
                    </tr>
                    <tr>
                        <td><code>offset</code></td>
                        <td><?php esc_html_e('Number of posts to skip', 'employer-stories'); ?></td>
                        <td><code>0</code></td>
                        <td><?php esc_html_e('any number', 'employer-stories'); ?></td>
                        <td><code>offset="3"</code></td>
                    </tr>
                    <tr>
                        <td><code>class</code></td>
                        <td><?php esc_html_e('Additional CSS classes', 'employer-stories'); ?></td>
                        <td><code>''</code></td>
                        <td><?php esc_html_e('any class names', 'employer-stories'); ?></td>
                        <td><code>class="featured-stories"</code></td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Example Shortcodes', 'employer-stories'); ?></h3>
                <p><?php esc_html_e('Basic grid with 3 columns:', 'employer-stories'); ?></p>
                <div class="es-shortcode-example">
                    [employer_stories columns="3" posts_per_page="6"]
                </div>

                <p><?php esc_html_e('Display stories from a specific category with pagination:', 'employer-stories'); ?></p>
                <div class="es-shortcode-example">
                    [employer_stories category="healthcare" pagination="true" posts_per_page="12"]
                </div>

                <p><?php esc_html_e('Display stories in a 2-column layout, randomly ordered:', 'employer-stories'); ?></p>
                <div class="es-shortcode-example">
                    [employer_stories columns="2" orderby="rand"]
                </div>

                <p><?php esc_html_e('Display specific stories by ID:', 'employer-stories'); ?></p>
                <div class="es-shortcode-example">
                    [employer_stories include="42,51,90" orderby="post__in"]
                </div>
            </div>

            <!-- Breadcrumbs Shortcode Section -->
            <div class="es-help-section">
                <h2><?php esc_html_e('Shortcode: [employer_story_breadcrumbs]', 'employer-stories'); ?></h2>
                <p><?php esc_html_e('This shortcode displays breadcrumb navigation for employer stories.', 'employer-stories'); ?></p>

                <h3><?php esc_html_e('Basic Usage', 'employer-stories'); ?></h3>
                <div class="es-shortcode-example">
                    [employer_story_breadcrumbs]
                </div>

                <p><?php esc_html_e('The breadcrumbs will display:', 'employer-stories'); ?></p>
                <ul>
                    <li><?php esc_html_e('Ptcb > Employer Stories (when on the archive page)', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('Ptcb > Employer Stories > Story Title (when on a single story page)', 'employer-stories'); ?></li>
                </ul>
            </div>

            <!-- Finding IDs Section -->
            <div class="es-help-section">
                <h2><?php esc_html_e('Finding Story IDs', 'employer-stories'); ?></h2>
                <p><?php esc_html_e('To find the ID of an Employer Story:', 'employer-stories'); ?></p>
                <ol>
                    <li><?php esc_html_e('Go to Employer Stories in the admin menu', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('Hover over a story\'s title', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('Look at the URL that appears in your browser\'s status bar', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('The ID is the number after "post=", e.g., post=42', 'employer-stories'); ?></li>
                </ol>
                <p><?php esc_html_e('Alternatively, open a story for editing and the ID will be visible in the URL.', 'employer-stories'); ?></p>
            </div>

            <!-- Create New Stories Section -->
            <div class="es-help-section">
                <h2><?php esc_html_e('Creating Employer Stories', 'employer-stories'); ?></h2>
                <p><?php esc_html_e('To create a new Employer Story:', 'employer-stories'); ?></p>
                <ol>
                    <li><?php esc_html_e('Go to Employer Stories > Add New in the admin menu', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('Add a title for your story', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('Set a featured image - this will be displayed in the grid view', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('Fill in the custom fields in the Employer Stories Field Group section', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('Publish your story when ready', 'employer-stories'); ?></li>
                </ol>
                <p><?php esc_html_e('The featured image is particularly important as it is what displays in the grid view on archive pages and in the shortcode output.', 'employer-stories'); ?></p>
            </div>

            <!-- Need Help Section -->
            <div class="es-help-section">
                <h2><?php esc_html_e('Need More Help?', 'employer-stories'); ?></h2>
                <p><?php esc_html_e('If you need further assistance:', 'employer-stories'); ?></p>
                <ul>
                    <li><?php esc_html_e('Contact your website administrator', 'employer-stories'); ?></li>
                    <li><?php esc_html_e('Refer to the WordPress documentation for general shortcode usage', 'employer-stories'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
