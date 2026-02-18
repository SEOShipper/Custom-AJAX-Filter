<?php
/**
 * Taxonomies registration and management
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APF_Taxonomies {

	private static $instance = null;

	/**
	 * Taxonomy definitions
	 */
	private $taxonomies = array(
		'product_type' => array(
			'label'           => 'Product Type',
			'public'          => false,
			'show_in_filters' => false,
		),
		'product_label' => array(
			'label'           => 'Product Label',
			'public'          => false,
			'show_in_filters' => false,
		),
		'product_brand' => array(
			'label'           => 'Replace Brand',
			'public'          => true,
			'show_in_filters' => true,
		),
		'product_application' => array(
			'label'           => 'Application',
			'public'          => true,
			'show_in_filters' => true,
		),
		'product_flow_rate' => array(
			'label'           => 'Flow Rate',
			'public'          => true,
			'show_in_filters' => true,
		),
		'product_micron' => array(
			'label'           => 'Micron Filtering',
			'public'          => true,
			'show_in_filters' => true,
		),
	);

	/**
	 * Default terms to create on activation
	 */
	private $default_terms = array(
		'product_type' => array(
			'High Flow Filter Cartridge',
			'Pleated Filter Cartridge',
			'String Wound Filter Cartridge',
			'Membrane Filter Cartridge',
			'Melt Blown Filter Cartridge',
			'Stainless Steel Filter Cartridge',
			'Stainless Steel Filter Housing',
			'FRP Membrane Housing',
			'Photoresist Filter Element',
		),
		'product_brand' => array(
			'PALL',
			'3M CUNO',
			'Integris',
			'Parker',
			'Graver',
			'Pentair',
			'Millipore',
			'Sartorius',
			'Roki',
		),
		'product_application' => array(
			'Petrochemical',
			'Water Treatment',
			'Food & Beverage',
			'Pharmaceutical',
		),
		'product_flow_rate' => array(
			'0-5 GPM',
			'5-50 GPM',
			'50-100 GPM',
			'100-200 GPM',
			'200-500 GPM',
		),
		'product_micron' => array(
			'1-5 Microns',
			'5-25 Microns',
			'25-100 Microns',
		),
	);

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register all taxonomies
	 */
	public function register_taxonomies() {
		foreach ( $this->taxonomies as $taxonomy => $config ) {
			$args = array(
				'labels'            => $this->get_taxonomy_labels( $config['label'] ),
				'public'            => $config['public'],
				'publicly_queryable' => $config['public'],
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => $config['public'],
				'show_admin_column' => $config['show_in_filters'],
				'show_in_rest'      => true,
				'query_var'         => $config['public'] ? $taxonomy : false,
				'rewrite'           => $config['public'] ? array( 'slug' => $taxonomy ) : false,
			);

			register_taxonomy( $taxonomy, 'product', $args );
		}
	}

	/**
	 * Generate taxonomy labels
	 */
	private function get_taxonomy_labels( $singular ) {
		$plural = $singular . 's';

		return array(
			'name'              => $plural,
			'singular_name'     => $singular,
			'search_items'      => 'Search ' . $plural,
			'all_items'         => 'All ' . $plural,
			'parent_item'       => 'Parent ' . $singular,
			'parent_item_colon' => 'Parent ' . $singular . ':',
			'edit_item'         => 'Edit ' . $singular,
			'update_item'       => 'Update ' . $singular,
			'add_new_item'      => 'Add New ' . $singular,
			'new_item_name'     => 'New ' . $singular . ' Name',
			'menu_name'         => $plural,
		);
	}

	/**
	 * Create default terms on activation
	 */
	public function create_default_terms() {
		foreach ( $this->default_terms as $taxonomy => $terms ) {
			foreach ( $terms as $term ) {
				if ( ! term_exists( $term, $taxonomy ) ) {
					wp_insert_term( $term, $taxonomy );
				}
			}
		}
	}

	/**
	 * Get filter taxonomies (for shortcode use)
	 */
	public function get_filter_taxonomies() {
		$filter_taxonomies = array();

		foreach ( $this->taxonomies as $taxonomy => $config ) {
			if ( $config['show_in_filters'] ) {
				$filter_taxonomies[ $taxonomy ] = $config['label'];
			}
		}

		return $filter_taxonomies;
	}

	/**
	 * Get all taxonomy definitions
	 */
	public function get_taxonomies() {
		return $this->taxonomies;
	}
}
