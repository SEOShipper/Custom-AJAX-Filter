<?php
/**
 * Product Custom Post Type registration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APF_Post_Type {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ), 0 );
	}

	/**
	 * Register the product custom post type
	 */
	public function register_post_type() {
		// Guard against WooCommerce conflict
		if ( post_type_exists( 'product' ) ) {
			return;
		}

		$labels = array(
			'name'                  => 'Products',
			'singular_name'         => 'Product',
			'menu_name'             => 'Products',
			'name_admin_bar'        => 'Product',
			'add_new'               => 'Add New',
			'add_new_item'          => 'Add New Product',
			'new_item'              => 'New Product',
			'edit_item'             => 'Edit Product',
			'view_item'             => 'View Product',
			'all_items'             => 'All Products',
			'search_items'          => 'Search Products',
			'not_found'             => 'No products found.',
			'not_found_in_trash'    => 'No products found in Trash.',
			'featured_image'        => 'Product Image',
			'set_featured_image'    => 'Set product image',
			'remove_featured_image' => 'Remove product image',
			'use_featured_image'    => 'Use as product image',
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'product', 'with_front' => false ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-cart',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		);

		register_post_type( 'product', $args );
	}
}
