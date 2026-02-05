<?php
/**
 * Shortcodes for filter sidebar and product grid
 */

if (!defined('ABSPATH')) {
    exit;
}

class APF_Shortcodes {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('product_filters', array($this, 'render_filters'));
        add_shortcode('product_grid', array($this, 'render_grid'));
    }

    /**
     * Render filter sidebar shortcode
     */
    public function render_filters($atts) {
        $atts = shortcode_atts(array(
            'show_search' => 'true',
            'show_count' => 'false',
            'collapsed' => 'false',
        ), $atts, 'product_filters');

        // Convert string booleans
        $atts['show_search'] = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_count'] = filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN);
        $atts['collapsed'] = filter_var($atts['collapsed'], FILTER_VALIDATE_BOOLEAN);

        ob_start();
        include APF_PLUGIN_DIR . 'templates/filter-sidebar.php';
        return ob_get_clean();
    }

    /**
     * Render product grid shortcode
     */
    public function render_grid($atts) {
        $atts = shortcode_atts(array(
            'columns' => '3',
            'show_sort' => '',
            'show_count' => '',
            'quote_url' => '/contact/',
            'title' => '',
            'product_type' => '',
            'product_application' => '',
            'limit' => '',
            'show_description' => '',
        ), $atts, 'product_grid');

        // Auto-detect display-only mode when limit is set
        $has_limit = !empty($atts['limit']) && intval($atts['limit']) > 0;
        $atts['show_sort'] = $atts['show_sort'] !== ''
            ? filter_var($atts['show_sort'], FILTER_VALIDATE_BOOLEAN)
            : !$has_limit;
        $atts['show_count'] = $atts['show_count'] !== ''
            ? filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN)
            : !$has_limit;
        $atts['limit'] = $has_limit ? intval($atts['limit']) : '';
        $atts['show_description'] = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
        $atts['columns'] = intval($atts['columns']);

        // Get initial products
        $products = $this->get_products($atts);

        ob_start();
        include APF_PLUGIN_DIR . 'templates/product-grid.php';
        return ob_get_clean();
    }

    /**
     * Get products for initial render
     *
     * @param array $atts Shortcode attributes
     */
    private function get_products($atts) {
        $tax_query = array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'page_type',
                'field' => 'slug',
                'terms' => 'product',
            ),
        );

        // Add product_type filter if specified
        if (!empty($atts['product_type'])) {
            $tax_query[] = array(
                'taxonomy' => 'product_type',
                'field' => 'slug',
                'terms' => $atts['product_type'],
            );
        }

        // Add product_application filter if specified
        if (!empty($atts['product_application'])) {
            $tax_query[] = array(
                'taxonomy' => 'product_application',
                'field' => 'slug',
                'terms' => $atts['product_application'],
            );
        }

        $args = array(
            'post_type' => 'page',
            'posts_per_page' => !empty($atts['limit']) ? intval($atts['limit']) : -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'tax_query' => $tax_query,
        );

        return new WP_Query($args);
    }

    /**
     * Get filter taxonomies with terms
     */
    public static function get_filter_data() {
        $taxonomies = APF_Taxonomies::get_instance()->get_filter_taxonomies();
        $filter_data = array();

        foreach ($taxonomies as $taxonomy => $label) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ));

            if (!is_wp_error($terms) && !empty($terms)) {
                $filter_data[$taxonomy] = array(
                    'label' => $label,
                    'terms' => $terms,
                );
            }
        }

        return $filter_data;
    }
}
