<?php
/**
 * Plugin settings page
 *
 * Registers a Products > Settings page with the WordPress Settings API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class APF_Settings {

	private static $instance = null;

	/** WordPress option key for the Elementor popup ID */
	const OPTION_POPUP_ID = 'apf_quote_popup_id';

	/** WordPress option key for the Why Choose Elementor template */
	const OPTION_WHY_CHOOSE_TPL = 'apf_why_choose_template_id';

	/** WordPress option key for the Case Studies Elementor template */
	const OPTION_CASE_STUDIES_TPL = 'apf_case_studies_template_id';

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
		add_submenu_page(
			'edit.php?post_type=product',
			'Product Filter Settings',
			'Settings',
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

		register_setting( 'apf_settings_group', self::OPTION_WHY_CHOOSE_TPL, array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( 'apf_settings_group', self::OPTION_CASE_STUDIES_TPL, array(
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

		add_settings_section(
			'apf_single_section',
			'Single Product Page',
			array( $this, 'render_single_section_description' ),
			'apf-settings'
		);

		add_settings_field(
			self::OPTION_WHY_CHOOSE_TPL,
			'Why Choose Template ID',
			array( $this, 'render_why_choose_field' ),
			'apf-settings',
			'apf_single_section'
		);

		add_settings_field(
			self::OPTION_CASE_STUDIES_TPL,
			'Case Studies Template ID',
			array( $this, 'render_case_studies_field' ),
			'apf-settings',
			'apf_single_section'
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

	public function render_single_section_description() {
		echo '<p>Replace sections on single product pages with Elementor templates.</p>';
	}

	public function render_why_choose_field() {
		$value = get_option( self::OPTION_WHY_CHOOSE_TPL, '' );
		printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="e.g. 7342" />',
			esc_attr( self::OPTION_WHY_CHOOSE_TPL ),
			esc_attr( $value )
		);
		echo '<p class="description">Elementor template ID for the "Why Choose" section. Leave empty for the default layout.</p>';
	}

	public function render_case_studies_field() {
		$value = get_option( self::OPTION_CASE_STUDIES_TPL, '' );
		printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="e.g. 7350" />',
			esc_attr( self::OPTION_CASE_STUDIES_TPL ),
			esc_attr( $value )
		);
		echo '<p class="description">Elementor template ID for the "Case Studies" section. Leave empty for the default layout.</p>';
	}

	/**
	 * Get the Elementor template ID for the Why Choose section.
	 */
	public static function get_why_choose_template_id() {
		return get_option( self::OPTION_WHY_CHOOSE_TPL, '' );
	}

	/**
	 * Get the Elementor template ID for the Case Studies section.
	 */
	public static function get_case_studies_template_id() {
		return get_option( self::OPTION_CASE_STUDIES_TPL, '' );
	}

	/**
	 * Get the raw popup post ID (or empty string if not configured).
	 */
	public static function get_popup_id() {
		return get_option( self::OPTION_POPUP_ID, '' );
	}

	/**
	 * Build the Elementor popup action URL for a given popup ID.
	 *
	 * Returns an empty string when no popup ID is configured so callers
	 * can fall back to the default quote link.
	 */
	public static function get_popup_url() {
		$popup_id = self::get_popup_id();
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
