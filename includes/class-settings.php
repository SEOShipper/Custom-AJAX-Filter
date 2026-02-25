<?php
/**
 * Plugin settings page
 *
 * Registers a Settings > Product Filter page with the WordPress Settings API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APF_Settings {

	private static $instance = null;

	/** WordPress option key for the Elementor popup ID */
	const OPTION_POPUP_ID = 'apf_quote_popup_id';

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function add_settings_page() {
		add_options_page(
			'Product Filter Settings',
			'Product Filter',
			'manage_options',
			'apf-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'apf_settings_group', self::OPTION_POPUP_ID, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_section(
			'apf_quote_section',
			'Quote Button',
			array( $this, 'render_section_description' ),
			'apf-settings'
		);

		add_settings_field(
			self::OPTION_POPUP_ID,
			'Elementor Popup ID',
			array( $this, 'render_popup_id_field' ),
			'apf-settings',
			'apf_quote_section'
		);
	}

	public function render_section_description() {
		echo '<p>Configure the "Get Quote" button behaviour across product pages and product cards.</p>';
	}

	public function render_popup_id_field() {
		$value = get_option( self::OPTION_POPUP_ID, '' );
		printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="e.g. 1234" />',
			esc_attr( self::OPTION_POPUP_ID ),
			esc_attr( $value )
		);
		echo '<p class="description">Enter the Elementor popup post ID. Leave empty to keep the default link to the contact page.</p>';
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'apf_settings_group' );
				do_settings_sections( 'apf-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Build the Elementor popup action URL for a given popup ID.
	 *
	 * Returns an empty string when no popup ID is configured so callers
	 * can fall back to the default quote link.
	 */
	public static function get_popup_url() {
		$popup_id = get_option( self::OPTION_POPUP_ID, '' );
		if ( empty( $popup_id ) ) {
			return '';
		}

		$settings = base64_encode( wp_json_encode( array(
			'id'     => $popup_id,
			'toggle' => false,
		) ) );

		return '#elementor-action%3Aaction%3Dpopup%3Aopen%26settings%3D' . $settings;
	}
}
