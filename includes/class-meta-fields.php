<?php
/**
 * Meta fields registration and meta box
 */

if (!defined('ABSPATH')) {
    exit;
}

class APF_Meta_Fields {

    private static $instance = null;

    /**
     * Meta field definitions
     */
    private $meta_fields = array(
        '_product_flow_rate' => array(
            'label' => 'Flow Rate',
            'type' => 'text',
            'placeholder' => 'e.g., 50-200 GPM',
        ),
        '_product_micron' => array(
            'label' => 'Micron Rating',
            'type' => 'text',
            'placeholder' => 'e.g., 1-100 Microns',
        ),
        '_product_temp' => array(
            'label' => 'Max Temperature',
            'type' => 'text',
            'placeholder' => 'e.g., 180°F',
        ),
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post_page', array($this, 'save_meta_box'));
        add_action('init', array($this, 'register_meta_fields'));
    }

    /**
     * Register meta fields for REST API
     */
    public function register_meta_fields() {
        foreach ($this->meta_fields as $key => $config) {
            register_post_meta('page', $key, array(
                'type' => 'string',
                'single' => true,
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => function($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', $post_id);
                },
            ));
        }
    }

    /**
     * Add meta box to page editor
     */
    public function add_meta_box() {
        add_meta_box(
            'apf_product_specs',
            'Product Specifications',
            array($this, 'render_meta_box'),
            'page',
            'normal',
            'high'
        );
    }

    /**
     * Render meta box content
     */
    public function render_meta_box($post) {
        // Nonce field
        wp_nonce_field('apf_save_product_specs', 'apf_product_specs_nonce');

        echo '<table class="form-table">';

        foreach ($this->meta_fields as $key => $config) {
            $value = get_post_meta($post->ID, $key, true);
            ?>
            <tr>
                <th scope="row">
                    <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($config['label']); ?></label>
                </th>
                <td>
                    <input
                        type="<?php echo esc_attr($config['type']); ?>"
                        id="<?php echo esc_attr($key); ?>"
                        name="<?php echo esc_attr($key); ?>"
                        value="<?php echo esc_attr($value); ?>"
                        class="regular-text"
                        placeholder="<?php echo esc_attr($config['placeholder']); ?>"
                    />
                </td>
            </tr>
            <?php
        }

        echo '</table>';
    }

    /**
     * Save meta box data
     */
    public function save_meta_box($post_id) {
        // Verify nonce
        if (!isset($_POST['apf_product_specs_nonce']) ||
            !wp_verify_nonce($_POST['apf_product_specs_nonce'], 'apf_save_product_specs')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }

        // Save each field
        foreach ($this->meta_fields as $key => $config) {
            if (isset($_POST[$key])) {
                $value = sanitize_text_field($_POST[$key]);
                update_post_meta($post_id, $key, $value);
            }
        }
    }

    /**
     * Get meta field definitions
     */
    public function get_meta_fields() {
        return $this->meta_fields;
    }
}
