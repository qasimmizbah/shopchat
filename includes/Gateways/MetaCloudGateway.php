<?php
/**
 * Meta Cloud API Official Gateway
 *
 * @package ShopChat\Gateways
 */

namespace ShopChat\Gateways;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MetaCloudGateway implements GatewayInterface {

	/**
	 * Meta Phone Number ID
	 *
	 * @var string
	 */
	protected $phone_number_id;

	/**
	 * Meta System User Permanent Access Token
	 *
	 * @var string
	 */
	protected $access_token;

	/**
	 * Graph API Version
	 *
	 * @var string
	 */
	protected $api_version = 'v19.0';

	/**
	 * WaMe Fallback Gateway
	 *
	 * @var WaMeGateway
	 */
	protected $fallback_gateway;

	/**
	 * Constructor
	 */
	public function __construct() {
		$settings               = get_option( 'shopchat_general_settings', array() );
		$this->phone_number_id  = sanitize_text_field( $settings['meta_phone_number_id'] ?? '' );
		$this->access_token     = sanitize_text_field( $settings['meta_access_token'] ?? '' );
		$this->fallback_gateway = new WaMeGateway();
	}

	/**
	 * Get direct chat URL fallback
	 *
	 * @param string $phone Phone number.
	 * @param string $message Text content.
	 * @param string $country Optional 2-letter ISO country code or numeric prefix.
	 * @return string
	 */
	public function get_chat_url( $phone, $message, $country = '' ) {
		return $this->fallback_gateway->get_chat_url( $phone, $message, $country );
	}

	/**
	 * Send WhatsApp message via Meta Cloud Graph API
	 *
	 * @param string $phone Recipient phone number.
	 * @param string $message Text or template parameters.
	 * @param array  $args Context args (e.g. country, template_name, language, parameters).
	 * @return array
	 */
	public function send( $phone, $message, $args = array() ) {
		$country = $args['country'] ?? '';

		if ( empty( $this->phone_number_id ) || empty( $this->access_token ) ) {
			return array(
				'success' => false,
				'mode'    => 'meta_cloud',
				'message' => __( 'Meta Cloud API credentials are missing. Please configure Phone Number ID and Access Token in settings.', 'shopchat' ),
				'url'     => $this->get_chat_url( $phone, $message, $country ),
			);
		}

		$formatted_phone = $this->fallback_gateway->format_phone( $phone, $country );
		$endpoint        = sprintf( 'https://graph.facebook.com/%s/%s/messages', $this->api_version, $this->phone_number_id );

		// Prepare request payload
		$payload = array(
			'messaging_product' => 'whatsapp',
			'recipient_type'    => 'individual',
			'to'                => $formatted_phone,
		);

		// If a specific approved Meta template name is provided
		if ( ! empty( $args['template_name'] ) ) {
			$payload['type']     = 'template';
			$payload['template'] = array(
				'name'       => sanitize_text_field( $args['template_name'] ),
				'language'   => array( 'code' => sanitize_text_field( $args['language'] ?? 'en_US' ) ),
				'components' => $args['components'] ?? array(),
			);
		} else {
			// Free-form text message (for customer care window or direct dispatch)
			$payload['type'] = 'text';
			$payload['text'] = array(
				'preview_url' => false,
				'body'        => $message,
			);
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'mode'    => 'meta_cloud',
				'message' => $response->get_error_message(),
				'url'     => $this->get_chat_url( $phone, $message, $country ),
			);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body['messages'] ) ) {
			return array(
				'success'    => true,
				'mode'       => 'meta_cloud',
				'message_id' => $body['messages'][0]['id'] ?? '',
				'message'    => __( 'Message dispatched via Meta Cloud API successfully.', 'shopchat' ),
			);
		}

		$error_message = $body['error']['message'] ?? __( 'Unknown Meta API error occurred.', 'shopchat' );

		return array(
			'success' => false,
			'mode'    => 'meta_cloud',
			'message' => $error_message,
			'url'     => $this->get_chat_url( $phone, $message, $country ),
		);
	}
}
