<?php
/**
 * Import/Export functionality for products and settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APF_Import_Export {

	private static $instance = null;

	/**
	 * All plugin taxonomies (same order as APF_Taxonomies)
	 */
	private $taxonomies = array(
		'product_type',
		'product_label',
		'product_brand',
		'product_application',
		'product_flow_rate',
		'product_micron',
	);

	/**
	 * Whitelisted meta keys for export/import
	 */
	private $meta_keys = array(
		'_product_subtitle',
		'_product_flow_rate',
		'_product_micron',
		'_product_temp',
		'_product_description',
		'_product_gallery',
		'_product_tabs',
	);

	/**
	 * Whitelisted settings keys
	 */
	private $settings_keys = array(
		'apf_quote_popup_id',
		'apf_why_choose_template_id',
		'apf_case_studies_template_id',
	);

	/**
	 * Products per import batch
	 */
	const BATCH_SIZE = 10;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_init', array( $this, 'handle_export' ) );

		// Import AJAX endpoints
		add_action( 'wp_ajax_apf_import_validate', array( $this, 'ajax_validate_import' ) );
		add_action( 'wp_ajax_apf_import_taxonomies', array( $this, 'ajax_import_taxonomies' ) );
		add_action( 'wp_ajax_apf_import_settings', array( $this, 'ajax_import_settings' ) );
		add_action( 'wp_ajax_apf_import_batch', array( $this, 'ajax_import_batch' ) );
	}

	/**
	 * Register submenu page under Products
	 */
	public function add_admin_page() {
		add_submenu_page(
			'edit.php?post_type=product',
			'Import / Export Products',
			'Import / Export',
			'manage_options',
			'apf-import-export',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Enqueue CSS/JS on import-export page only
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'product_page_apf-import-export' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'apf-import-export',
			APF_PLUGIN_URL . 'assets/css/import-export.css',
			array(),
			APF_VERSION
		);

		wp_enqueue_script(
			'apf-import-export',
			APF_PLUGIN_URL . 'assets/js/import-export.js',
			array( 'jquery' ),
			APF_VERSION,
			true
		);

		wp_localize_script( 'apf-import-export', 'apfImportExport', array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'apf_import_export' ),
			'batchSize' => self::BATCH_SIZE,
		) );
	}

	// =========================================================================
	// Admin Page Rendering
	// =========================================================================

	/**
	 * Render the Import / Export admin page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$products = get_posts( array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'orderby'        => 'title',
			'order'          => 'ASC',
		) );
		?>
		<div class="wrap apf-ie-wrap">
			<h1>Import / Export Products</h1>

			<!-- Export Section -->
			<div class="apf-ie-section">
				<h2>Export</h2>
				<form method="post" id="apf-export-form">
					<?php wp_nonce_field( 'apf_export', 'apf_export_nonce' ); ?>
					<input type="hidden" name="apf_action" value="export" />

					<fieldset>
						<legend>Product Selection</legend>
						<label class="apf-ie-radio">
							<input type="radio" name="export_scope" value="all" checked />
							All products (<?php echo absint( count( $products ) ); ?>)
						</label>
						<label class="apf-ie-radio">
							<input type="radio" name="export_scope" value="selected" />
							Selected products
						</label>

						<div id="apf-product-checklist" class="apf-product-checklist" style="display:none;">
							<?php if ( empty( $products ) ) : ?>
								<p class="description">No products found.</p>
							<?php else : ?>
								<label class="apf-ie-check apf-ie-check-all">
									<input type="checkbox" id="apf-check-all" />
									<strong>Select All</strong>
								</label>
								<?php foreach ( $products as $product ) : ?>
									<label class="apf-ie-check">
										<input type="checkbox" name="export_products[]" value="<?php echo esc_attr( $product->ID ); ?>" />
										<?php echo esc_html( $product->post_title ); ?>
										<span class="apf-ie-status">(<?php echo esc_html( $product->post_status ); ?>)</span>
									</label>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</fieldset>

					<fieldset>
						<legend>Options</legend>
						<label class="apf-ie-check">
							<input type="checkbox" name="export_settings" value="1" checked />
							Include plugin settings
						</label>
					</fieldset>

					<p class="description">
						<strong>Note:</strong> Gallery image IDs and featured image URLs are site-specific. After importing on another site, you will need to re-upload images and reassign them manually.
					</p>

					<p class="submit">
						<button type="submit" class="button button-primary">Export to JSON</button>
					</p>
				</form>
			</div>

			<!-- Import Section -->
			<div class="apf-ie-section">
				<h2>Import</h2>

				<div id="apf-import-upload">
					<p>
						<label for="apf-import-file">Select a JSON file exported from this plugin:</label>
					</p>
					<p>
						<input type="file" id="apf-import-file" accept=".json" />
					</p>
				</div>

				<div id="apf-import-summary" style="display:none;">
					<h3>File Summary</h3>
					<table class="widefat striped apf-ie-summary-table">
						<tbody>
							<tr>
								<th>Source Site</th>
								<td id="apf-summary-site">-</td>
							</tr>
							<tr>
								<th>Exported At</th>
								<td id="apf-summary-date">-</td>
							</tr>
							<tr>
								<th>Products</th>
								<td id="apf-summary-products">-</td>
							</tr>
							<tr>
								<th>Taxonomy Terms</th>
								<td id="apf-summary-terms">-</td>
							</tr>
							<tr>
								<th>Settings</th>
								<td id="apf-summary-settings">-</td>
							</tr>
						</tbody>
					</table>

					<fieldset>
						<legend>Duplicate Handling</legend>
						<p class="description">When a product with the same slug already exists:</p>
						<label class="apf-ie-radio">
							<input type="radio" name="duplicate_mode" value="skip" checked />
							Skip &mdash; leave existing product unchanged
						</label>
						<label class="apf-ie-radio">
							<input type="radio" name="duplicate_mode" value="overwrite" />
							Overwrite &mdash; update existing product with imported data
						</label>
						<label class="apf-ie-radio">
							<input type="radio" name="duplicate_mode" value="create" />
							Create new &mdash; always create, WordPress will suffix the slug
						</label>
					</fieldset>

					<fieldset>
						<legend>Import Options</legend>
						<label class="apf-ie-check">
							<input type="checkbox" id="apf-import-taxonomies" checked />
							Import taxonomy terms
						</label>
						<label class="apf-ie-check">
							<input type="checkbox" id="apf-import-settings" />
							Import plugin settings
						</label>
					</fieldset>

					<p class="submit">
						<button type="button" id="apf-import-start" class="button button-primary">Start Import</button>
						<button type="button" id="apf-import-cancel" class="button">Cancel</button>
					</p>
				</div>

				<div id="apf-import-progress" style="display:none;">
					<h3>Import Progress</h3>
					<div class="apf-ie-progress-bar">
						<div class="apf-ie-progress-fill" id="apf-progress-fill"></div>
					</div>
					<p id="apf-progress-text">Preparing...</p>
				</div>

				<div id="apf-import-results" style="display:none;">
					<h3>Import Results</h3>
					<table class="widefat striped apf-ie-results-table">
						<tbody>
							<tr>
								<th>Products Created</th>
								<td id="apf-result-created">0</td>
							</tr>
							<tr>
								<th>Products Updated</th>
								<td id="apf-result-updated">0</td>
							</tr>
							<tr>
								<th>Products Skipped</th>
								<td id="apf-result-skipped">0</td>
							</tr>
							<tr>
								<th>Taxonomy Terms Imported</th>
								<td id="apf-result-terms">0</td>
							</tr>
							<tr>
								<th>Errors</th>
								<td id="apf-result-errors">0</td>
							</tr>
						</tbody>
					</table>
					<div id="apf-result-error-list" style="display:none;">
						<h4>Error Details</h4>
						<ul id="apf-error-messages"></ul>
					</div>
					<p>
						<button type="button" id="apf-import-reset" class="button">Import Another File</button>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	// =========================================================================
	// Export
	// =========================================================================

	/**
	 * Handle export form submission (runs on admin_init before headers)
	 */
	public function handle_export() {
		if ( ! isset( $_POST['apf_action'] ) || 'export' !== $_POST['apf_action'] ) {
			return;
		}

		if ( ! isset( $_POST['apf_export_nonce'] ) ||
			! wp_verify_nonce( $_POST['apf_export_nonce'], 'apf_export' ) ) {
			wp_die( 'Invalid nonce.' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized.' );
		}

		$data = $this->build_export_data();

		$filename = 'apf-export-' . gmdate( 'Y-m-d-His' ) . '.json';
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/**
	 * Build the full export data array
	 */
	private function build_export_data() {
		$scope          = isset( $_POST['export_scope'] ) ? sanitize_text_field( $_POST['export_scope'] ) : 'all';
		$include_settings = ! empty( $_POST['export_settings'] );

		// Get product IDs
		if ( 'selected' === $scope && ! empty( $_POST['export_products'] ) ) {
			$product_ids = array_map( 'absint', (array) $_POST['export_products'] );
		} else {
			$product_ids = get_posts( array(
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			) );
		}

		// Build export
		$export = array(
			'plugin'      => 'ajax-product-filter',
			'version'     => APF_VERSION,
			'format'      => '1.0',
			'exported_at' => gmdate( 'c' ),
			'site_url'    => get_site_url(),
			'data'        => array(
				'settings'   => array(),
				'taxonomies' => array(),
				'products'   => array(),
			),
		);

		// Settings
		if ( $include_settings ) {
			foreach ( $this->settings_keys as $key ) {
				$export['data']['settings'][ $key ] = get_option( $key, '' );
			}
		}

		// Taxonomy terms
		foreach ( $this->taxonomies as $taxonomy ) {
			$export['data']['taxonomies'][ $taxonomy ] = $this->export_taxonomy_terms( $taxonomy );
		}

		// Products
		foreach ( $product_ids as $product_id ) {
			$product_data = $this->export_single_product( $product_id );
			if ( $product_data ) {
				$export['data']['products'][] = $product_data;
			}
		}

		return $export;
	}

	/**
	 * Export all terms for a taxonomy
	 */
	private function export_taxonomy_terms( $taxonomy ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'parent',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		// Build lookup map for parent slug resolution
		$slug_map = wp_list_pluck( $terms, 'slug', 'term_id' );

		$exported = array();
		foreach ( $terms as $term ) {
			$term_data = array(
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent_slug' => '',
			);

			// Resolve parent slug from lookup map
			if ( $term->parent > 0 && isset( $slug_map[ $term->parent ] ) ) {
				$term_data['parent_slug'] = $slug_map[ $term->parent ];
			}

			// Term meta (only _product_type_link for product_type)
			if ( 'product_type' === $taxonomy ) {
				$link = get_term_meta( $term->term_id, '_product_type_link', true );
				$term_data['meta'] = array(
					'_product_type_link' => $link ? $link : '',
				);
			}

			$exported[] = $term_data;
		}

		return $exported;
	}

	/**
	 * Export a single product
	 */
	private function export_single_product( $product_id ) {
		$post = get_post( $product_id );
		if ( ! $post || 'product' !== $post->post_type ) {
			return null;
		}

		$product_data = array(
			'post_title'          => $post->post_title,
			'post_name'           => $post->post_name,
			'post_content'        => $post->post_content,
			'post_status'         => $post->post_status,
			'post_excerpt'        => $post->post_excerpt,
			'menu_order'          => $post->menu_order,
			'featured_image_url'  => '',
			'meta'                => array(),
			'taxonomies'          => array(),
		);

		// Featured image URL
		$thumb_id = get_post_thumbnail_id( $product_id );
		if ( $thumb_id ) {
			$thumb_url = wp_get_attachment_url( $thumb_id );
			if ( $thumb_url ) {
				$product_data['featured_image_url'] = $thumb_url;
			}
		}

		// Meta fields
		foreach ( $this->meta_keys as $key ) {
			$product_data['meta'][ $key ] = get_post_meta( $product_id, $key, true );
		}

		// Taxonomy terms (as slugs)
		foreach ( $this->taxonomies as $taxonomy ) {
			$terms = wp_get_object_terms( $product_id, $taxonomy, array( 'fields' => 'slugs' ) );
			$product_data['taxonomies'][ $taxonomy ] = is_wp_error( $terms ) ? array() : $terms;
		}

		return $product_data;
	}

	// =========================================================================
	// Import — Validate
	// =========================================================================

	/**
	 * AJAX: Validate uploaded JSON, store in transient, return summary
	 */
	public function ajax_validate_import() {
		$this->verify_ajax_request();

		if ( empty( $_POST['json_data'] ) ) {
			wp_send_json_error( array( 'message' => 'No data received.' ) );
		}

		$data = json_decode( wp_unslash( $_POST['json_data'] ), true );

		if ( null === $data ) {
			wp_send_json_error( array( 'message' => 'Invalid JSON: ' . json_last_error_msg() ) );
		}

		// Validate structure
		if ( empty( $data['plugin'] ) || 'ajax-product-filter' !== $data['plugin'] ) {
			wp_send_json_error( array( 'message' => 'This file was not exported by the AJAX Product Filter plugin.' ) );
		}

		if ( empty( $data['data'] ) ) {
			wp_send_json_error( array( 'message' => 'Export data is empty.' ) );
		}

		// Store in transient keyed to the current user (expires in 1 hour)
		$import_key = 'apf_import_' . get_current_user_id() . '_' . wp_hash( wp_json_encode( $data ) );
		set_transient( $import_key, $data, HOUR_IN_SECONDS );

		// Count terms
		$term_count = 0;
		if ( ! empty( $data['data']['taxonomies'] ) ) {
			foreach ( $data['data']['taxonomies'] as $terms ) {
				if ( is_array( $terms ) ) {
					$term_count += count( $terms );
				}
			}
		}

		$product_count  = ! empty( $data['data']['products'] ) ? count( $data['data']['products'] ) : 0;
		$has_settings   = ! empty( $data['data']['settings'] ) && count( $data['data']['settings'] ) > 0;

		wp_send_json_success( array(
			'import_key'    => $import_key,
			'site_url'      => ! empty( $data['site_url'] ) ? esc_url( $data['site_url'] ) : 'Unknown',
			'exported_at'   => ! empty( $data['exported_at'] ) ? sanitize_text_field( $data['exported_at'] ) : 'Unknown',
			'version'       => ! empty( $data['version'] ) ? sanitize_text_field( $data['version'] ) : 'Unknown',
			'product_count' => $product_count,
			'term_count'    => $term_count,
			'has_settings'  => $has_settings,
		) );
	}

	// =========================================================================
	// Import — Taxonomies
	// =========================================================================

	/**
	 * AJAX: Import taxonomy terms
	 */
	public function ajax_import_taxonomies() {
		$this->verify_ajax_request();

		$data = $this->get_import_data();
		if ( empty( $data['data']['taxonomies'] ) ) {
			wp_send_json_success( array( 'imported' => 0 ) );
		}

		$imported = 0;

		foreach ( $this->taxonomies as $taxonomy ) {
			if ( empty( $data['data']['taxonomies'][ $taxonomy ] ) ) {
				continue;
			}

			$terms = $data['data']['taxonomies'][ $taxonomy ];

			// Parents first (parent_slug empty), then children
			usort( $terms, function( $a, $b ) {
				$a_is_child = ! empty( $a['parent_slug'] ) ? 1 : 0;
				$b_is_child = ! empty( $b['parent_slug'] ) ? 1 : 0;
				return $a_is_child - $b_is_child;
			} );

			foreach ( $terms as $term_data ) {
				$name        = sanitize_text_field( $term_data['name'] );
				$slug        = sanitize_title( $term_data['slug'] );
				$description = isset( $term_data['description'] ) ? sanitize_textarea_field( $term_data['description'] ) : '';

				// Resolve parent
				$parent_id = 0;
				if ( ! empty( $term_data['parent_slug'] ) ) {
					$parent_term = get_term_by( 'slug', sanitize_title( $term_data['parent_slug'] ), $taxonomy );
					if ( $parent_term ) {
						$parent_id = $parent_term->term_id;
					}
				}

				// Check if term exists
				$existing = get_term_by( 'slug', $slug, $taxonomy );

				if ( $existing ) {
					// Update existing term
					wp_update_term( $existing->term_id, $taxonomy, array(
						'name'        => $name,
						'description' => $description,
						'parent'      => $parent_id,
					) );
					$term_id = $existing->term_id;
				} else {
					// Insert new term
					$result = wp_insert_term( $name, $taxonomy, array(
						'slug'        => $slug,
						'description' => $description,
						'parent'      => $parent_id,
					) );

					if ( is_wp_error( $result ) ) {
						continue;
					}

					$term_id = $result['term_id'];
					$imported++;
				}

				// Term meta
				if ( 'product_type' === $taxonomy && ! empty( $term_data['meta']['_product_type_link'] ) ) {
					update_term_meta( $term_id, '_product_type_link', esc_url_raw( $term_data['meta']['_product_type_link'] ) );
				}
			}
		}

		wp_send_json_success( array( 'imported' => $imported ) );
	}

	// =========================================================================
	// Import — Settings
	// =========================================================================

	/**
	 * AJAX: Import plugin settings
	 */
	public function ajax_import_settings() {
		$this->verify_ajax_request();

		$data = $this->get_import_data();
		if ( empty( $data['data']['settings'] ) ) {
			wp_send_json_success( array( 'imported' => 0 ) );
		}

		$imported = 0;
		foreach ( $data['data']['settings'] as $key => $value ) {
			if ( ! in_array( $key, $this->settings_keys, true ) ) {
				continue;
			}
			update_option( $key, sanitize_text_field( $value ) );
			$imported++;
		}

		wp_send_json_success( array( 'imported' => $imported ) );
	}

	// =========================================================================
	// Import — Product Batch
	// =========================================================================

	/**
	 * AJAX: Import a batch of products
	 */
	public function ajax_import_batch() {
		$this->verify_ajax_request();

		$data   = $this->get_import_data();
		$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$mode   = isset( $_POST['duplicate_mode'] ) ? sanitize_text_field( $_POST['duplicate_mode'] ) : 'skip';

		if ( ! in_array( $mode, array( 'skip', 'overwrite', 'create' ), true ) ) {
			$mode = 'skip';
		}

		if ( empty( $data['data']['products'] ) ) {
			wp_send_json_success( array(
				'created' => 0,
				'updated' => 0,
				'skipped' => 0,
				'errors'  => array(),
				'done'    => true,
			) );
		}

		$products = array_slice( $data['data']['products'], $offset, self::BATCH_SIZE );
		$created  = 0;
		$updated  = 0;
		$skipped  = 0;
		$errors   = array();

		foreach ( $products as $product_data ) {
			$result = $this->import_single_product( $product_data, $mode );

			switch ( $result['status'] ) {
				case 'created':
					$created++;
					break;
				case 'updated':
					$updated++;
					break;
				case 'skipped':
					$skipped++;
					break;
				case 'error':
					$errors[] = $result['message'];
					break;
			}
		}

		$total     = count( $data['data']['products'] );
		$processed = $offset + count( $products );

		wp_send_json_success( array(
			'created' => $created,
			'updated' => $updated,
			'skipped' => $skipped,
			'errors'  => $errors,
			'done'    => $processed >= $total,
		) );
	}

	/**
	 * Import a single product
	 */
	private function import_single_product( $product_data, $mode ) {
		if ( empty( $product_data['post_name'] ) && empty( $product_data['post_title'] ) ) {
			return array(
				'status'  => 'error',
				'message' => 'Product entry missing both title and slug.',
			);
		}

		$slug     = sanitize_title( isset( $product_data['post_name'] ) ? $product_data['post_name'] : $product_data['post_title'] );
		$existing = $this->find_existing_product( $slug );

		// Skip mode: bail if exists
		if ( $existing && 'skip' === $mode ) {
			return array( 'status' => 'skipped' );
		}

		// Prepare post data
		$post_arr = array(
			'post_type'    => 'product',
			'post_title'   => sanitize_text_field( isset( $product_data['post_title'] ) ? $product_data['post_title'] : '' ),
			'post_name'    => $slug,
			'post_content' => wp_kses_post( isset( $product_data['post_content'] ) ? $product_data['post_content'] : '' ),
			'post_status'  => $this->sanitize_post_status( isset( $product_data['post_status'] ) ? $product_data['post_status'] : 'draft' ),
			'post_excerpt' => sanitize_textarea_field( isset( $product_data['post_excerpt'] ) ? $product_data['post_excerpt'] : '' ),
			'menu_order'   => absint( isset( $product_data['menu_order'] ) ? $product_data['menu_order'] : 0 ),
		);

		if ( $existing && 'overwrite' === $mode ) {
			// Update existing
			$post_arr['ID'] = $existing->ID;
			$result = wp_update_post( $post_arr, true );

			if ( is_wp_error( $result ) ) {
				return array(
					'status'  => 'error',
					'message' => sprintf( 'Failed to update "%s": %s', $product_data['post_title'], $result->get_error_message() ),
				);
			}

			$post_id = $existing->ID;
			$status  = 'updated';
		} else {
			// Create new (both 'create' mode and no existing post)
			$result = wp_insert_post( $post_arr, true );

			if ( is_wp_error( $result ) ) {
				return array(
					'status'  => 'error',
					'message' => sprintf( 'Failed to create "%s": %s', $product_data['post_title'], $result->get_error_message() ),
				);
			}

			$post_id = $result;
			$status  = 'created';
		}

		// Import meta fields
		if ( ! empty( $product_data['meta'] ) ) {
			foreach ( $product_data['meta'] as $key => $value ) {
				if ( ! in_array( $key, $this->meta_keys, true ) ) {
					continue;
				}

				$sanitized = $this->sanitize_meta_value( $key, $value );
				update_post_meta( $post_id, $key, $sanitized );
			}
		}

		// Import taxonomy terms
		if ( ! empty( $product_data['taxonomies'] ) ) {
			foreach ( $product_data['taxonomies'] as $taxonomy => $slugs ) {
				if ( ! in_array( $taxonomy, $this->taxonomies, true ) ) {
					continue;
				}

				if ( ! is_array( $slugs ) || empty( $slugs ) ) {
					wp_set_object_terms( $post_id, array(), $taxonomy );
					continue;
				}

				$term_ids = array();
				foreach ( $slugs as $term_slug ) {
					$term = get_term_by( 'slug', sanitize_title( $term_slug ), $taxonomy );
					if ( $term ) {
						$term_ids[] = $term->term_id;
					}
				}

				wp_set_object_terms( $post_id, $term_ids, $taxonomy );
			}
		}

		return array( 'status' => $status );
	}

	/**
	 * Find an existing product by slug
	 */
	private function find_existing_product( $slug ) {
		$posts = get_posts( array(
			'post_type'      => 'product',
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'any',
		) );

		return ! empty( $posts ) ? $posts[0] : null;
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Verify AJAX request nonce and capability
	 */
	private function verify_ajax_request() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'apf_import_export' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid security token.' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
		}
	}

	/**
	 * Get import data from transient (stored during validation step)
	 */
	private function get_import_data() {
		if ( empty( $_POST['import_key'] ) ) {
			wp_send_json_error( array( 'message' => 'Missing import key. Please re-upload the file.' ) );
		}

		$import_key = sanitize_text_field( $_POST['import_key'] );
		$data       = get_transient( $import_key );

		if ( false === $data || empty( $data['plugin'] ) || 'ajax-product-filter' !== $data['plugin'] ) {
			wp_send_json_error( array( 'message' => 'Import session expired. Please re-upload the file.' ) );
		}

		return $data;
	}

	/**
	 * Sanitize a meta value based on key
	 */
	private function sanitize_meta_value( $key, $value ) {
		switch ( $key ) {
			case '_product_gallery':
				if ( ! is_string( $value ) ) {
					return '';
				}
				$ids = array_map( 'absint', explode( ',', $value ) );
				$ids = array_filter( $ids );
				return implode( ',', $ids );

			case '_product_tabs':
				// Handle both string (JSON) and already-decoded array
				if ( is_array( $value ) ) {
					$tabs = $value;
				} else {
					$tabs = json_decode( $value, true );
				}
				if ( ! is_array( $tabs ) ) {
					return '';
				}
				$clean = array();
				foreach ( $tabs as $tab ) {
					if ( empty( $tab['title'] ) ) {
						continue;
					}
					$clean[] = array(
						'title'   => sanitize_text_field( $tab['title'] ),
						'content' => wp_kses_post( $tab['content'] ),
					);
				}
				return wp_json_encode( $clean );

			case '_product_description':
				return sanitize_textarea_field( $value );

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Ensure post_status is a valid value
	 */
	private function sanitize_post_status( $status ) {
		$allowed = array( 'publish', 'draft', 'pending', 'private' );
		return in_array( $status, $allowed, true ) ? $status : 'draft';
	}
}
