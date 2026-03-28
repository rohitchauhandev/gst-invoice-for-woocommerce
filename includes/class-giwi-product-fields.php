<?php
/**
 * Product-level GST fields for WooCommerce.
 *
 * @package GSTInvoiceForWooCommerceIndia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GIWI_Product_Fields {

	/**
	 * Allowed GST rates.
	 *
	 * @var array<string,string>
	 */
	private $gst_rates = array(
		'0'  => '0%',
		'5'  => '5%',
		'12' => '12%',
		'18' => '18%',
		'28' => '28%',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ) );
	}

	/**
	 * Render GST fields in product data panel.
	 *
	 * @return void
	 */
	public function render_fields() {
		echo '<div class="options_group">';

		woocommerce_wp_text_input(
			array(
				'id'                => '_giwi_hsn_sac_code',
				'label'             => __( 'HSN/SAC Code', 'gst-invoice-for-woocommerce-india' ),
				'desc_tip'          => true,
				'description'       => __( 'Enter the HSN or SAC code used for GST classification.', 'gst-invoice-for-woocommerce-india' ),
				'type'              => 'text',
				'custom_attributes' => array(
					'maxlength'    => '20',
					'autocomplete' => 'off',
				),
			)
		);

		woocommerce_wp_select(
			array(
				'id'          => '_giwi_gst_rate',
				'label'       => __( 'GST Rate', 'gst-invoice-for-woocommerce-india' ),
				'desc_tip'    => true,
				'description' => __( 'Choose the GST rate that applies to this product.', 'gst-invoice-for-woocommerce-india' ),
				'options'     => $this->get_rate_options(),
			)
		);

		echo '</div>';
	}

	/**
	 * Save GST-related product meta.
	 *
	 * @param WC_Product $product Product object.
	 * @return void
	 */
	public function save_fields( $product ) {
		$hsn_raw      = isset( $_POST['_giwi_hsn_sac_code'] ) ? wp_unslash( $_POST['_giwi_hsn_sac_code'] ) : '';
		$gst_rate_raw = isset( $_POST['_giwi_gst_rate'] ) ? wp_unslash( $_POST['_giwi_gst_rate'] ) : '';

		$hsn_code = strtoupper( sanitize_text_field( $hsn_raw ) );
		$hsn_code = preg_replace( '/[^A-Z0-9\/-]/', '', $hsn_code );
		$hsn_code = is_string( $hsn_code ) ? substr( $hsn_code, 0, 20 ) : '';

		if ( '' !== $hsn_code && ! preg_match( '/^[A-Z0-9\/-]{2,20}$/', $hsn_code ) ) {
			$this->add_admin_error( __( 'HSN/SAC Code must contain only letters, numbers, "/" or "-".', 'gst-invoice-for-woocommerce-india' ) );
			$hsn_code = '';
		}

		$gst_rate = sanitize_text_field( $gst_rate_raw );
		if ( '' !== $gst_rate && ! array_key_exists( $gst_rate, $this->gst_rates ) ) {
			$this->add_admin_error( __( 'Please select a valid GST rate for the product.', 'gst-invoice-for-woocommerce-india' ) );
			$gst_rate = '';
		}

		$product->update_meta_data( '_giwi_hsn_sac_code', $hsn_code );
		$product->update_meta_data( '_giwi_gst_rate', $gst_rate );
	}

	/**
	 * Get dropdown options with placeholder.
	 *
	 * @return array<string,string>
	 */
	private function get_rate_options() {
		return array( '' => __( 'Select GST rate', 'gst-invoice-for-woocommerce-india' ) ) + $this->gst_rates;
	}

	/**
	 * Add validation errors to WooCommerce admin screen.
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	private function add_admin_error( $message ) {
		if ( class_exists( 'WC_Admin_Meta_Boxes' ) ) {
			WC_Admin_Meta_Boxes::add_error( $message );
		}
	}
}
