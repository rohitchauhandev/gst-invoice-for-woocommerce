<?php
/**
 * Admin settings for GST Invoice for WooCommerce India.
 *
 * @package GSTInvoiceForWooCommerceIndia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GIWI_Settings {

	/**
	 * Settings option key.
	 *
	 * @var string
	 */
	private $option_name = 'giwi_settings';

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	private $settings_group = 'giwi_settings_group';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ), 99 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add submenu under WooCommerce.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_submenu_page(
			'woocommerce',
			__( 'GST Invoice Settings', 'gst-invoice-for-woocommerce-india' ),
			__( 'GST Invoice', 'gst-invoice-for-woocommerce-india' ),
			'manage_woocommerce',
			'giwi-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			$this->settings_group,
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);

		add_settings_section(
			'giwi_general_section',
			__( 'Store GST Details', 'gst-invoice-for-woocommerce-india' ),
			array( $this, 'render_section_description' ),
			'giwi-settings'
		);

		$fields = array(
			'store_name'    => __( 'Store Name', 'gst-invoice-for-woocommerce-india' ),
			'store_address' => __( 'Store Address', 'gst-invoice-for-woocommerce-india' ),
			'gstin'         => __( 'GSTIN', 'gst-invoice-for-woocommerce-india' ),
			'logo_id'       => __( 'Upload Logo', 'gst-invoice-for-woocommerce-india' ),
			'enable_gst'    => __( 'Enable GST', 'gst-invoice-for-woocommerce-india' ),
		);

		foreach ( $fields as $field_id => $label ) {
			add_settings_field(
				$field_id,
				$label,
				array( $this, 'render_field' ),
				'giwi-settings',
				'giwi_general_section',
				array(
					'id'    => $field_id,
					'label' => $label,
				)
			);
		}
	}

	/**
	 * Enqueue admin assets only on plugin page.
	 *
	 * @param string $hook_suffix Admin page suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'woocommerce_page_giwi-settings' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'giwi-admin',
			GIWI_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			GIWI_VERSION
		);

		wp_enqueue_script(
			'giwi-admin',
			GIWI_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			GIWI_VERSION,
			true
		);

		wp_localize_script(
			'giwi-admin',
			'giwiAdmin',
			array(
				'title'      => __( 'Choose a logo', 'gst-invoice-for-woocommerce-india' ),
				'buttonText' => __( 'Use this logo', 'gst-invoice-for-woocommerce-india' ),
				'removeText' => __( 'Remove logo', 'gst-invoice-for-woocommerce-india' ),
			)
		);
	}

	/**
	 * Sanitize and validate settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults  = $this->get_default_settings();
		$sanitized = $defaults;
		$has_error = false;

		$sanitized['store_name'] = isset( $input['store_name'] ) ? sanitize_text_field( wp_unslash( $input['store_name'] ) ) : '';

		if ( isset( $input['store_address'] ) ) {
			$address                     = sanitize_textarea_field( wp_unslash( $input['store_address'] ) );
			$sanitized['store_address'] = trim( preg_replace( "/\r\n|\r|\n/", "\n", $address ) );
		}

		if ( isset( $input['gstin'] ) ) {
			$gstin = strtoupper( sanitize_text_field( wp_unslash( $input['gstin'] ) ) );
			if ( '' !== $gstin && ! preg_match( '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[A-Z0-9]{1}Z[A-Z0-9]{1}$/', $gstin ) ) {
				$has_error = true;
				add_settings_error(
					$this->option_name,
					'invalid_gstin',
					__( 'Please enter a valid GSTIN in the standard 15-character format.', 'gst-invoice-for-woocommerce-india' ),
					'error'
				);
			} else {
				$sanitized['gstin'] = $gstin;
			}
		}

		$sanitized['logo_id'] = isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;
		if ( $sanitized['logo_id'] && 'image/' !== substr( (string) get_post_mime_type( $sanitized['logo_id'] ), 0, 6 ) ) {
			$has_error             = true;
			$sanitized['logo_id'] = 0;
			add_settings_error(
				$this->option_name,
				'invalid_logo',
				__( 'The selected logo must be a valid image from the media library.', 'gst-invoice-for-woocommerce-india' ),
				'error'
			);
		}

		$sanitized['enable_gst'] = ! empty( $input['enable_gst'] ) ? 'yes' : 'no';

		if ( ! $has_error ) {
			add_settings_error(
				$this->option_name,
				'settings_saved',
				__( 'GST invoice settings saved.', 'gst-invoice-for-woocommerce-india' ),
				'updated'
			);
		}

		return $sanitized;
	}

	/**
	 * Render section description.
	 *
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure store details used for GST invoices generated for WooCommerce orders.', 'gst-invoice-for-woocommerce-india' ) . '</p>';
	}

	/**
	 * Render field based on field ID.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_field( $args ) {
		$id       = $args['id'];
		$settings = $this->get_settings();
		$name     = $this->option_name . '[' . $id . ']';

		switch ( $id ) {
			case 'store_name':
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" maxlength="120" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $settings['store_name'] )
				);
				echo '<p class="description">' . esc_html__( 'Displayed on the GST invoice header.', 'gst-invoice-for-woocommerce-india' ) . '</p>';
				break;

			case 'store_address':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4" class="large-text">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $settings['store_address'] )
				);
				echo '<p class="description">' . esc_html__( 'Add the complete billing address for your registered store location.', 'gst-invoice-for-woocommerce-india' ) . '</p>';
				break;

			case 'gstin':
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text giwi-uppercase" maxlength="15" placeholder="22AAAAA0000A1Z5" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $settings['gstin'] )
				);
				echo '<p class="description">' . esc_html__( 'Enter your 15-character GST Identification Number.', 'gst-invoice-for-woocommerce-india' ) . '</p>';
				break;

			case 'logo_id':
				$this->render_logo_field( $name, (int) $settings['logo_id'] );
				break;

			case 'enable_gst':
				printf(
					'<label class="giwi-toggle"><input type="checkbox" id="%1$s" name="%2$s" value="yes" %3$s /> <span>%4$s</span></label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( 'yes', $settings['enable_gst'], false ),
					esc_html__( 'Enable GST details on WooCommerce invoices', 'gst-invoice-for-woocommerce-india' )
				);
				break;
		}
	}

	/**
	 * Render logo field.
	 *
	 * @param string $name Option field name.
	 * @param int    $logo_id Attachment ID.
	 * @return void
	 */
	private function render_logo_field( $name, $logo_id ) {
		$image_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		$has_logo  = ! empty( $image_url );

		echo '<div class="giwi-logo-control">';
		printf(
			'<input type="hidden" id="logo_id" name="%1$s" value="%2$d" />',
			esc_attr( $name ),
			(int) $logo_id
		);
		echo '<div class="giwi-logo-preview-wrap">';
		if ( $has_logo ) {
			printf(
				'<img src="%1$s" alt="%2$s" class="giwi-logo-preview" />',
				esc_url( $image_url ),
				esc_attr__( 'Store logo preview', 'gst-invoice-for-woocommerce-india' )
			);
		} else {
			echo '<div class="giwi-logo-placeholder">' . esc_html__( 'No logo selected', 'gst-invoice-for-woocommerce-india' ) . '</div>';
		}
		echo '</div>';
		echo '<div class="giwi-logo-actions">';
		echo '<button type="button" class="button button-secondary giwi-upload-logo">' . esc_html__( 'Upload Logo', 'gst-invoice-for-woocommerce-india' ) . '</button>';
		echo '<button type="button" class="button button-link-delete giwi-remove-logo' . ( $has_logo ? '' : ' hidden' ) . '">' . esc_html__( 'Remove Logo', 'gst-invoice-for-woocommerce-india' ) . '</button>';
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Choose an image from the media library to display on invoices.', 'gst-invoice-for-woocommerce-india' ) . '</p>';
		echo '</div>';
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap woocommerce giwi-settings-page">
			<h1><?php esc_html_e( 'GST Invoice for WooCommerce India', 'gst-invoice-for-woocommerce-india' ); ?></h1>
			<div class="giwi-settings-card">
				<?php settings_errors( $this->option_name ); ?>
				<form action="options.php" method="post">
					<?php
					settings_fields( $this->settings_group );
					do_settings_sections( 'giwi-settings' );
					submit_button( __( 'Save Settings', 'gst-invoice-for-woocommerce-india' ) );
					?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Get settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( $this->option_name, array() );

		return wp_parse_args( is_array( $settings ) ? $settings : array(), $this->get_default_settings() );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function get_default_settings() {
		return array(
			'store_name'    => '',
			'store_address' => '',
			'gstin'         => '',
			'logo_id'       => 0,
			'enable_gst'    => 'no',
		);
	}
}
