<?php
/**
 * Checkout Fields Manager (Enforces required WhatsApp phone field across Classic & Block checkouts)
 *
 * @package ShopChat\Frontend
 */

namespace ShopChat\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CheckoutFields {

	/**
	 * Register hooks for Classic Checkout, Gutenberg Checkout Blocks, and Store API
	 */
	public function init() {
		// Enforce option value to 'required' for WooCommerce Core & Blocks
		add_filter( 'option_woocommerce_checkout_phone_field', array( $this, 'enforce_phone_field_visibility' ) );
		add_filter( 'default_option_woocommerce_checkout_phone_field', array( $this, 'enforce_phone_field_visibility' ) );

		// Classic Checkout Address & Billing Fields filters
		add_filter( 'woocommerce_billing_fields', array( $this, 'filter_billing_fields' ), 999 );
		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_checkout_fields' ), 999 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'filter_default_address_fields' ), 999 );

		// Gutenberg Blocks Dynamic Attribute Enforcement (Twenty Twenty-Five & modern FSE block themes)
		add_filter( 'render_block_data', array( $this, 'filter_checkout_block_attributes' ), 10, 3 );

		// Server-side validation during checkout submission
		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout_phone' ) );

		// Sync block attributes on page content if needed
		add_action( 'init', array( $this, 'sync_checkout_settings' ) );
	}

	/**
	 * Ensure woocommerce_checkout_phone_field always returns 'required'
	 *
	 * @param mixed $value Stored option value.
	 * @return string 'required'
	 */
	public function enforce_phone_field_visibility( $value = '' ) {
		return 'required';
	}

	/**
	 * Filter WooCommerce billing fields for Classic Checkout
	 *
	 * @param array $fields Billing fields array.
	 * @return array Modified fields.
	 */
	public function filter_billing_fields( $fields ) {
		if ( ! isset( $fields['billing_phone'] ) ) {
			$fields['billing_phone'] = array(
				'label'        => __( 'Phone Number (for WhatsApp Updates)', 'shopchat' ),
				'required'     => true,
				'type'         => 'tel',
				'class'        => array( 'form-row-wide' ),
				'validate'     => array( 'phone' ),
				'autocomplete' => 'tel',
				'priority'     => 25,
				'placeholder'  => __( 'e.g. 9876543210', 'shopchat' ),
			);
		} else {
			$fields['billing_phone']['required']    = true;
			$fields['billing_phone']['label']       = __( 'Phone Number (for WhatsApp Updates)', 'shopchat' );
			$fields['billing_phone']['priority']    = 25;
			if ( empty( $fields['billing_phone']['placeholder'] ) ) {
				$fields['billing_phone']['placeholder'] = __( 'e.g. 9876543210', 'shopchat' );
			}
		}
		return $fields;
	}

	/**
	 * Filter top-level WooCommerce checkout fields
	 *
	 * @param array $fields All checkout fields.
	 * @return array
	 */
	public function filter_checkout_fields( $fields ) {
		if ( ! isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone'] = array(
				'label'        => __( 'Phone Number (for WhatsApp Updates)', 'shopchat' ),
				'required'     => true,
				'type'         => 'tel',
				'class'        => array( 'form-row-wide' ),
				'validate'     => array( 'phone' ),
				'autocomplete' => 'tel',
				'priority'     => 25,
				'placeholder'  => __( 'e.g. 9876543210', 'shopchat' ),
			);
		} else {
			$fields['billing']['billing_phone']['required'] = true;
			$fields['billing']['billing_phone']['label']    = __( 'Phone Number (for WhatsApp Updates)', 'shopchat' );
			if ( empty( $fields['billing']['billing_phone']['placeholder'] ) ) {
				$fields['billing']['billing_phone']['placeholder'] = __( 'e.g. 9876543210', 'shopchat' );
			}
		}
		return $fields;
	}

	/**
	 * Filter default address fields
	 *
	 * @param array $fields Address fields.
	 * @return array
	 */
	public function filter_default_address_fields( $fields ) {
		if ( isset( $fields['phone'] ) ) {
			$fields['phone']['required'] = true;
		}
		return $fields;
	}

	/**
	 * Ensure WooCommerce Gutenberg Checkout Blocks render phone field
	 *
	 * @param array $parsed_block Parsed block data.
	 * @param array $source_block Source block.
	 * @param mixed $parent_block Parent block.
	 * @return array
	 */
	public function filter_checkout_block_attributes( $parsed_block, $source_block = array(), $parent_block = null ) {
		if ( empty( $parsed_block['blockName'] ) ) {
			return $parsed_block;
		}

		$target_blocks = array(
			'woocommerce/checkout-contact-information-block',
			'woocommerce/checkout-shipping-address-block',
			'woocommerce/checkout-billing-address-block',
		);

		if ( in_array( $parsed_block['blockName'], $target_blocks, true ) ) {
			if ( ! isset( $parsed_block['attrs'] ) || ! is_array( $parsed_block['attrs'] ) ) {
				$parsed_block['attrs'] = array();
			}
			$parsed_block['attrs']['requirePhoneField'] = true;
			$parsed_block['attrs']['showPhoneField']    = true;
		}

		return $parsed_block;
	}

	/**
	 * Validate phone field during checkout submission
	 */
	public function validate_checkout_phone() {
		// Nonce is verified by WooCommerce core checkout processing.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) : '';

		if ( empty( $phone ) && function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( __( 'Please enter your phone number to receive WhatsApp order updates.', 'shopchat' ), 'error' );
		}
	}

	/**
	 * Sync settings in database if needed (runs once safely)
	 */
	public function sync_checkout_settings() {
		// Ensure option in database is 'required'
		if ( get_option( 'woocommerce_checkout_phone_field' ) !== 'required' ) {
			update_option( 'woocommerce_checkout_phone_field', 'required' );
		}

		// Ensure checkout page blocks have showPhoneField and requirePhoneField
		$synced = get_option( 'shopchat_blocks_phone_synced', '0' );
		if ( '1' !== $synced && function_exists( 'wc_get_page_id' ) ) {
			$checkout_id = wc_get_page_id( 'checkout' );
			if ( $checkout_id > 0 ) {
				$post = get_post( $checkout_id );
				if ( $post && strpos( $post->post_content, 'wp:woocommerce/checkout' ) !== false ) {
					$content     = $post->post_content;
					$new_content = $content;

					if ( strpos( $new_content, 'woocommerce/checkout-contact-information-block' ) !== false && strpos( $new_content, '"requirePhoneField":true' ) === false ) {
						$new_content = preg_replace(
							'/wp:woocommerce\/checkout-contact-information-block(\s*\{[^\}]*\})?/',
							'wp:woocommerce/checkout-contact-information-block {"requirePhoneField":true,"showPhoneField":true}',
							$new_content
						);
					}

					if ( $new_content !== $content ) {
						wp_update_post(
							array(
								'ID'           => $checkout_id,
								'post_content' => $new_content,
							)
						);
					}
				}
			}
			update_option( 'shopchat_blocks_phone_synced', '1' );
		}
	}
}
