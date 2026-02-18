<?php
/**
 * AJAX handler for filtering products
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APF_Ajax_Handler {

	private static $instance = null;

	/**
	 * Filter taxonomies
	 */
	private $filter_taxonomies = array(
		'product_brand',
		'product_application',
		'product_flow_rate',
		'product_micron',
	);

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_apf_filter_products', array( $this, 'filter_products' ) );
		add_action( 'wp_ajax_nopriv_apf_filter_products', array( $this, 'filter_products' ) );
	}

	/**
	 * Handle AJAX filter request
	 */
	public function filter_products() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'apf_filter_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid security token' ) );
		}

		// Build query args
		$limit = ! empty( $_POST['limit'] ) ? intval( $_POST['limit'] ) : -1;
		$args  = array(
			'post_type'      => 'product',
			'posts_per_page' => $limit > 0 ? $limit : -1,
			'post_status'    => 'publish',
		);

		// Build tax query
		$tax_query = array();

		// Filter by product_type if specified (from shortcode attribute)
		if ( ! empty( $_POST['product_type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $_POST['product_type'] ),
			);
		}

		// Filter by product_application if specified (from shortcode attribute)
		if ( ! empty( $_POST['product_application'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_application',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $_POST['product_application'] ),
			);
		}

		// Add filter taxonomies
		foreach ( $this->filter_taxonomies as $taxonomy ) {
			if ( ! empty( $_POST[ $taxonomy ] ) && is_array( $_POST[ $taxonomy ] ) ) {
				$terms       = array_map( 'sanitize_text_field', $_POST[ $taxonomy ] );
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $terms,
					'operator' => 'IN',
				);
			}
		}

		if ( ! empty( $tax_query ) ) {
			$tax_query['relation'] = 'AND';
			$args['tax_query']     = $tax_query;
		}

		// Handle search
		if ( ! empty( $_POST['search'] ) ) {
			$args['s'] = sanitize_text_field( $_POST['search'] );
		}

		// Handle sort
		$sort = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'featured';

		switch ( $sort ) {
			case 'name-asc':
				$args['orderby'] = 'title';
				$args['order']   = 'ASC';
				break;
			case 'name-desc':
				$args['orderby'] = 'title';
				$args['order']   = 'DESC';
				break;
			case 'featured':
			default:
				$args['orderby'] = 'menu_order';
				$args['order']   = 'ASC';
				break;
		}

		// Get quote URL from request or default
		$quote_url = isset( $_POST['quote_url'] ) ? esc_url( $_POST['quote_url'] ) : '/contact/';

		// Show description instead of specs table
		$show_description = ! empty( $_POST['show_description'] );

		// Run query
		$query = new WP_Query( $args );

		// Generate HTML
		ob_start();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				include APF_PLUGIN_DIR . 'templates/product-card.php';
			}
			wp_reset_postdata();
		} else {
			echo '<div class="apf-no-results">';
			echo '<p>No products found matching your criteria.</p>';
			echo '<p>Try adjusting your filters or <a href="#" class="apf-clear-filters-link">clear all filters</a>.</p>';
			echo '</div>';
		}

		$html = ob_get_clean();

		// Calculate term counts with current filters
		$counts = $this->calculate_term_counts( $_POST );

		// Send response
		wp_send_json_success( array(
			'html'   => $html,
			'count'  => $query->found_posts,
			'counts' => $counts,
		) );
	}

	/**
	 * Calculate term counts with current filters applied
	 */
	private function calculate_term_counts( $filters ) {
		$counts = array();

		// Normalize filters to known keys only for cache key
		$known_keys  = array_merge( $this->filter_taxonomies, array( 'product_type', 'product_application', 'search' ) );
		$cache_input = array();
		foreach ( $known_keys as $key ) {
			if ( ! empty( $filters[ $key ] ) ) {
				$cache_input[ $key ] = $filters[ $key ];
			}
		}
		$cache_key = 'apf_counts_' . md5( wp_json_encode( $cache_input ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		foreach ( $this->filter_taxonomies as $taxonomy ) {
			$terms = get_terms( array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			) );

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			$counts[ $taxonomy ] = array();

			foreach ( $terms as $term ) {
				// Build query with all filters EXCEPT this taxonomy
				$tax_query = array();

				// Add product_type filter if specified
				if ( ! empty( $filters['product_type'] ) ) {
					$tax_query[] = array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $filters['product_type'] ),
					);
				}

				// Add product_application pre-filter if specified
				if ( ! empty( $filters['product_application'] ) ) {
					$tax_query[] = array(
						'taxonomy' => 'product_application',
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $filters['product_application'] ),
					);
				}

				// Add this specific term
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $term->slug,
				);

				// Add other active filters
				foreach ( $this->filter_taxonomies as $other_tax ) {
					if ( $other_tax !== $taxonomy && ! empty( $filters[ $other_tax ] ) && is_array( $filters[ $other_tax ] ) ) {
						$other_terms = array_map( 'sanitize_text_field', $filters[ $other_tax ] );
						$tax_query[] = array(
							'taxonomy' => $other_tax,
							'field'    => 'slug',
							'terms'    => $other_terms,
							'operator' => 'IN',
						);
					}
				}

				$args = array(
					'post_type'      => 'product',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				);

				if ( ! empty( $tax_query ) ) {
					$tax_query['relation'] = 'AND';
					$args['tax_query']     = $tax_query;
				}

				// Add search if present
				if ( ! empty( $filters['search'] ) ) {
					$args['s'] = sanitize_text_field( $filters['search'] );
				}

				$query                              = new WP_Query( $args );
				$counts[ $taxonomy ][ $term->slug ] = $query->found_posts;
			}
		}

		// Cache for 1 hour
		set_transient( $cache_key, $counts, HOUR_IN_SECONDS );

		return $counts;
	}
}
