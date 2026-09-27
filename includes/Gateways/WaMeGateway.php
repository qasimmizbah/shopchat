<?php
/**
 * wa.me Click-to-Chat Direct Gateway (100% Free / Zero API Mode)
 *
 * @package ShopChat\Gateways
 */

namespace ShopChat\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WaMeGateway implements GatewayInterface {

	/**
	 * Default country code fallback
	 *
	 * @var string
	 */
	protected $default_country_code;

	/**
	 * Constructor
	 */
	public function __construct() {
		$settings                   = get_option( 'shopchat_general_settings', array() );
		$this->default_country_code = $settings['default_country_code'] ?? '91';
	}

	/**
	 * Clean and format phone number for international WhatsApp standard
	 *
	 * @param string $phone Raw phone string.
	 * @param string $country Optional 2-letter ISO country code or numeric prefix.
	 * @return string Clean numeric phone string without + or symbols.
	 */
	public function format_phone( $phone, $country = '' ) {
		// Strip non-numeric characters
		$clean = preg_replace( '/[^0-9]/', '', (string) $phone );

		// Strip leading zeroes (e.g. 07791026797 -> 7791026797)
		$clean = ltrim( $clean, '0' );

		if ( empty( $clean ) ) {
			return '';
		}

		// If phone seems local (10 digits standard mobile number without country code)
		if ( strlen( $clean ) === 10 ) {
			$prefix = '';

			// 1. If country ISO or numeric code provided (e.g. 'IN' or '91')
			if ( ! empty( $country ) ) {
				if ( is_numeric( $country ) ) {
					$prefix = preg_replace( '/[^0-9]/', '', (string) $country );
				} elseif ( function_exists( 'WC' ) && WC()->countries ) {
					$prefix = preg_replace( '/[^0-9]/', '', (string) WC()->countries->get_country_calling_code( $country ) );
				}
			}

			// 2. Fallback to WooCommerce store base country (e.g. 'IN' -> '91')
			if ( empty( $prefix ) && function_exists( 'WC' ) && WC()->countries ) {
				$base_country = WC()->countries->get_base_country();
				if ( ! empty( $base_country ) ) {
					$prefix = preg_replace( '/[^0-9]/', '', (string) WC()->countries->get_country_calling_code( $base_country ) );
				}
			}

			// 3. Fallback to customer default country code setting
			if ( empty( $prefix ) && ! empty( $this->default_country_code ) ) {
				$prefix = preg_replace( '/[^0-9]/', '', (string) $this->default_country_code );
			}

			// 4. Default fallback to 91
			if ( empty( $prefix ) ) {
				$prefix = '91';
			}

			$clean = $prefix . $clean;
		}

		return $clean;
	}

	/**
	 * Generate Click-to-Chat URL
	 *
	 * @param string $phone Recipient phone.
	 * @param string $message Text content.
	 * @param string $country Optional 2-letter ISO country code or numeric prefix.
	 * @return string URL.
	 */
	public function get_chat_url( $phone, $message, $country = '' ) {
		$formatted_phone = $this->format_phone( $phone, $country );

		// Normalize line endings to \n (CRLF %0D%0A causes WhatsApp Web to strip line breaks)
		$clean_message = str_replace( array( "\r\n", "\r" ), "\n", (string) $message );
		$clean_message = trim( $clean_message );

		$encoded_message = rawurlencode( html_entity_decode( $clean_message, ENT_QUOTES, 'UTF-8' ) );

		if ( empty( $formatted_phone ) ) {
			return 'https://api.whatsapp.com/send?text=' . $encoded_message;
		}

		return 'https://api.whatsapp.com/send?phone=' . $formatted_phone . '&text=' . $encoded_message;
	}

	/**
	 * Send method for 1-click gateway (returns URL for redirection)
	 *
	 * @param string $phone Recipient phone.
	 * @param string $message Message body.
	 * @param array  $args Context args.
	 * @return array
	 */
	public function send( $phone, $message, $args = array() ) {
		$url = $this->get_chat_url( $phone, $message );

		return array(
			'success'  => true,
			'mode'     => 'wame',
			'url'      => $url,
			'message'  => __( 'WhatsApp 1-Click link generated successfully.', 'shopchat' ),
		);
	}

	/**
	 * Safely escape a WhatsApp URL for HTML output while preserving %0A linebreaks.
	 *
	 * WordPress core esc_url() aggressively strips %0A and %0D, squishing message formatting together.
	 * This helper protects %0A during escaping, satisfying WordPress.org security while keeping WhatsApp formatting intact.
	 *
	 * @param string $url WhatsApp Click-to-Chat URL.
	 * @return string Safely escaped URL.
	 */
	public static function escape_url( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		$placeholder = 'SHOPCHATNL';
		$protected   = str_replace( array( '%0A', '%0a', '%0D', '%0d' ), $placeholder, (string) $url );
		$escaped     = esc_url( $protected );

		return str_replace( $placeholder, '%0A', $escaped );
	}
}
