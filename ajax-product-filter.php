<?php
/**
 * Plugin Name: AJAX Product Filter
 * Plugin URI: https://example.com/ajax-product-filter
 * Description: AJAX-based filtering system for product pages with custom taxonomies and meta fields.
 * Version: 1.0.2
 * Author: EcomExperts
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ajax-product-filter
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('APF_VERSION', '1.0.2');
define('APF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APF_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Ajax_Product_Filter {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once APF_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once APF_PLUGIN_DIR . 'includes/class-meta-fields.php';
        require_once APF_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once APF_PLUGIN_DIR . 'includes/class-ajax-handler.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize components
        APF_Taxonomies::get_instance();
        APF_Meta_Fields::get_instance();
        APF_Shortcodes::get_instance();
        APF_Ajax_Handler::get_instance();

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue frontend assets only when shortcodes are present
     */
    public function enqueue_assets() {
        global $post;

        // Check if our shortcodes are present
        if (!is_a($post, 'WP_Post')) {
            return;
        }

        $has_filters = has_shortcode($post->post_content, 'product_filters');
        $has_grid = has_shortcode($post->post_content, 'product_grid');

        if (!$has_filters && !$has_grid) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'apf-filter-styles',
            APF_PLUGIN_URL . 'assets/css/filter.css',
            array(),
            APF_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'apf-filter-script',
            APF_PLUGIN_URL . 'assets/js/filter.js',
            array('jquery'),
            APF_VERSION,
            true
        );

        // Localize script
        wp_localize_script('apf-filter-script', 'apfAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('apf_filter_nonce'),
        ));
    }
}

/**
 * Activation hook
 */
function apf_activate() {
    // Register taxonomies first (required before inserting terms)
    require_once APF_PLUGIN_DIR . 'includes/class-taxonomies.php';
    APF_Taxonomies::get_instance()->register_taxonomies();

    // Create predefined terms
    APF_Taxonomies::get_instance()->create_default_terms();

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'apf_activate');

/**
 * Deactivation hook
 */
function apf_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'apf_deactivate');

/**
 * Initialize plugin
 */
function apf_init() {
    Ajax_Product_Filter::get_instance();
}
add_action('plugins_loaded', 'apf_init');
