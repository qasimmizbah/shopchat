<?php
/**
 * Gateway Interface for WhatsApp dispatchers
 *
 * @package ShopChat\Gateways
 */

namespace ShopChat\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface GatewayInterface {

	/**
	 * Send or prepare a WhatsApp message
	 *
	 * @param string $phone Customer recipient phone number.
	 * @param string $message Formatted text content.
	 * @param array  $args Additional contextual parameters.
	 * @return array Result containing status and redirect URL or API response.
	 */
	public function send( $phone, $message, $args = array() );

	/**
	 * Get direct click-to-chat URL
	 *
	 * @param string $phone Recipient phone number.
	 * @param string $message Pre-filled message.
	 * @return string WhatsApp URL.
	 */
	public function get_chat_url( $phone, $message );
}
