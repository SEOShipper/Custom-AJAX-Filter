<?php
/**
 * Meta fields registration and meta boxes for the product CPT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APF_Meta_Fields {

	private static $instance = null;

	/**
	 * Spec meta field definitions (simple text fields)
	 */
	private $spec_fields = array(
		'_product_subtitle' => array(
			'label'       => 'Subtitle',
			'type'        => 'text',
			'placeholder' => 'e.g., Industrial High Flow Replacement',
		),
		'_product_flow_rate' => array(
			'label'       => 'Flow Rate',
			'type'        => 'text',
			'placeholder' => 'e.g., 50-200 GPM',
		),
		'_product_micron' => array(
			'label'       => 'Micron Rating',
			'type'        => 'text',
			'placeholder' => 'e.g., 1-100 Microns',
		),
		'_product_temp' => array(
			'label'       => 'Max Temperature',
			'type'        => 'text',
			'placeholder' => 'e.g., 180°F',
		),
		'_product_rating' => array(
			'label'       => 'Rating',
			'type'        => 'number',
			'placeholder' => 'e.g., 4.8',
		),
		'_product_description' => array(
			'label'       => 'Product Description',
			'type'        => 'textarea',
			'placeholder' => 'Brief product description for showcase cards',
		),
	);

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_product', array( $this, 'save_meta_boxes' ) );
		add_action( 'init', array( $this, 'register_meta_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Enqueue admin assets for the product edit screen
	 */
	public function enqueue_admin_assets( $hook ) {
		global $post_type;

		if ( 'product' !== $post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'apf-meta-box',
			APF_PLUGIN_URL . 'assets/css/meta-box.css',
			array(),
			APF_VERSION
		);

		wp_enqueue_script(
			'apf-meta-box',
			APF_PLUGIN_URL . 'assets/js/meta-box.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			APF_VERSION,
			true
		);
	}

	/**
	 * Register meta fields for REST API
	 */
	public function register_meta_fields() {
		foreach ( $this->spec_fields as $key => $config ) {
			register_post_meta( 'product', $key, array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => ( 'textarea' === $config['type'] ) ? 'sanitize_textarea_field' : 'sanitize_text_field',
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			) );
		}

		// Gallery (comma-separated attachment IDs)
		register_post_meta( 'product', '_product_gallery', array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => array( $this, 'sanitize_gallery_ids' ),
			'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		) );

		// Tabs (JSON array of {title, content})
		register_post_meta( 'product', '_product_tabs', array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => array( $this, 'sanitize_tabs_json' ),
			'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		) );
	}

	/**
	 * Sanitize gallery IDs to strictly comma-separated integers
	 */
	public function sanitize_gallery_ids( $value ) {
		$ids = array_map( 'absint', explode( ',', $value ) );
		$ids = array_filter( $ids );
		return implode( ',', $ids );
	}

	/**
	 * Sanitize tabs JSON data
	 */
	public function sanitize_tabs_json( $value ) {
		$tabs = json_decode( $value, true );
		if ( ! is_array( $tabs ) ) {
			return '';
		}

		$clean = array();
		foreach ( $tabs as $tab ) {
			if ( ! empty( $tab['title'] ) ) {
				$clean[] = array(
					'title'   => sanitize_text_field( $tab['title'] ),
					'content' => wp_kses_post( $tab['content'] ),
				);
			}
		}

		return wp_json_encode( $clean );
	}

	/**
	 * Add meta boxes to product editor
	 */
	public function add_meta_boxes() {
		add_meta_box(
			'apf_product_specs',
			'Product Specifications',
			array( $this, 'render_specs_meta_box' ),
			'product',
			'normal',
			'high'
		);

		add_meta_box(
			'apf_product_gallery',
			'Product Gallery',
			array( $this, 'render_gallery_meta_box' ),
			'product',
			'normal',
			'high'
		);

		add_meta_box(
			'apf_product_tabs',
			'Product Tabs',
			array( $this, 'render_tabs_meta_box' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Render specs meta box
	 */
	public function render_specs_meta_box( $post ) {
		wp_nonce_field( 'apf_save_product_meta', 'apf_product_meta_nonce' );

		echo '<table class="form-table">';

		foreach ( $this->spec_fields as $key => $config ) {
			$value = get_post_meta( $post->ID, $key, true );
			?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $config['label'] ); ?></label>
				</th>
				<td>
					<?php if ( 'textarea' === $config['type'] ) : ?>
					<textarea
						id="<?php echo esc_attr( $key ); ?>"
						name="<?php echo esc_attr( $key ); ?>"
						class="large-text"
						rows="4"
						placeholder="<?php echo esc_attr( $config['placeholder'] ); ?>"
					><?php echo esc_textarea( $value ); ?></textarea>
					<?php else : ?>
					<input
						type="<?php echo esc_attr( $config['type'] ); ?>"
						id="<?php echo esc_attr( $key ); ?>"
						name="<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						class="regular-text"
						placeholder="<?php echo esc_attr( $config['placeholder'] ); ?>"
						<?php if ( 'number' === $config['type'] ) : ?>
						step="0.1" min="0" max="5"
						<?php endif; ?>
					/>
					<?php endif; ?>
				</td>
			</tr>
			<?php
		}

		echo '</table>';
	}

	/**
	 * Render gallery meta box
	 */
	public function render_gallery_meta_box( $post ) {
		$gallery_ids = get_post_meta( $post->ID, '_product_gallery', true );
		$ids_array   = ! empty( $gallery_ids ) ? explode( ',', $gallery_ids ) : array();
		?>
		<div class="apf-gallery-wrap">
			<input type="hidden" id="apf-gallery-ids" name="_product_gallery" value="<?php echo esc_attr( $gallery_ids ); ?>" />

			<div id="apf-gallery-grid" class="apf-gallery-grid">
				<?php foreach ( $ids_array as $id ) :
					$id  = intval( $id );
					$url = wp_get_attachment_image_url( $id, 'thumbnail' );
					if ( $url ) : ?>
					<div class="apf-gallery-item" data-id="<?php echo esc_attr( $id ); ?>">
						<img src="<?php echo esc_url( $url ); ?>" alt="" />
						<button type="button" class="apf-gallery-remove" aria-label="Remove image">&times;</button>
					</div>
					<?php endif;
				endforeach; ?>
			</div>

			<p>
				<button type="button" id="apf-gallery-add" class="button">Add Gallery Images</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Render tabs meta box
	 */
	public function render_tabs_meta_box( $post ) {
		$tabs_json = get_post_meta( $post->ID, '_product_tabs', true );
		$tabs      = ! empty( $tabs_json ) ? json_decode( $tabs_json, true ) : array();

		if ( ! is_array( $tabs ) ) {
			$tabs = array();
		}
		?>
		<div class="apf-tabs-wrap">
			<input type="hidden" id="apf-tabs-json" name="_product_tabs" value="<?php echo esc_attr( $tabs_json ); ?>" />

			<div id="apf-tabs-list" class="apf-tabs-list">
				<?php foreach ( $tabs as $i => $tab ) : ?>
				<div class="apf-tab-row" data-index="<?php echo esc_attr( $i ); ?>">
					<span class="apf-tab-handle dashicons dashicons-menu"></span>
					<input type="text" class="apf-tab-title regular-text" value="<?php echo esc_attr( $tab['title'] ); ?>" placeholder="Tab title" />
					<button type="button" class="apf-tab-toggle button-link" aria-label="Toggle content">
						<span class="dashicons dashicons-arrow-down-alt2"></span>
					</button>
					<button type="button" class="apf-tab-remove button-link" aria-label="Remove tab">
						<span class="dashicons dashicons-trash"></span>
					</button>
					<div class="apf-tab-content-wrap" style="display:none;">
						<textarea class="apf-tab-content large-text" rows="5" placeholder="Tab content (HTML allowed)"><?php echo esc_textarea( $tab['content'] ); ?></textarea>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<p>
				<button type="button" id="apf-tab-add" class="button">Add Tab</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Save all meta boxes
	 */
	public function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['apf_product_meta_nonce'] ) ||
			! wp_verify_nonce( $_POST['apf_product_meta_nonce'], 'apf_save_product_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save spec fields
		foreach ( $this->spec_fields as $key => $config ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = ( 'textarea' === $config['type'] )
					? sanitize_textarea_field( $_POST[ $key ] )
					: sanitize_text_field( $_POST[ $key ] );
				update_post_meta( $post_id, $key, $value );
			}
		}

		// Save gallery IDs (strictly comma-separated integers)
		if ( isset( $_POST['_product_gallery'] ) ) {
			$gallery = $this->sanitize_gallery_ids( $_POST['_product_gallery'] );
			update_post_meta( $post_id, '_product_gallery', $gallery );
		}

		// Save tabs JSON
		if ( isset( $_POST['_product_tabs'] ) ) {
			update_post_meta( $post_id, '_product_tabs', wp_unslash( $_POST['_product_tabs'] ) );
		}
	}

	/**
	 * Get spec field definitions
	 */
	public function get_meta_fields() {
		return $this->spec_fields;
	}
}
