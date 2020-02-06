<?php
/**
 * Chabok remote API for tracking events
 * and attributing.
 *
 * @package ChabokIO
 * @subpackage API
 */

class Chabok_API {
	/**
	 * @var string API authorization key.
	 */
	private $api_key;

	/**
	 * @var string Endpoint prefix.
	 */
	private $endpoint_prefix = 'sandbox';

	/**
	 * Sets up the class for using.
	 *
	 * @return void
	 */
	public function __construct() {
		global $chabok_options;

		if ( ! isset( $chabok_options['api_key'] ) ) {
			// missing api key.
			return false;
		}

		$this->api_key = $chabok_options['api_key'];

		if ( isset( $chabok_options['app_id'] ) ) {
			if ( $chabok_options['env'] === 'production' ) {
				$this->endpoint_prefix = $chabok_options['app_id'];
			}
		}
	}

	/**
	 * Sends a request to Chabok endpoint.
	 *
	 * @param string $endpoint REST endpoint.
	 * @param mixed $data Data to send.
	 * @param string $method HTTP verb to use.
	 */
	private function request( $endpoint, $data = array(), $method = 'POST' ) {
		$url = sprintf( 'https://%s.push.adpdigital.com/api%s?access_token=' . $this->api_key, $this->endpoint_prefix, $endpoint );

		$options = array(
			CURLOPT_URL				=> $url,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_HTTPHEADER		=> array(
				'X-Access-Token'	=> $this->api_key,
			),
		);

		if ( 'POST' === $method ) {
			$payload = json_encode( $data );
			$options[ CURLOPT_POSTFIELDS ] = $payload;
			$options[ CURLOPT_POST ] = true;
			$options[ CURLOPT_HTTPHEADER ][] = 'Content-Type: application/json';
			$options[ CURLOPT_HTTPHEADER ][] = 'Content-Length: '. strlen( $payload );
		}

		$ch = curl_init();
		curl_setopt_array( $ch, $options );

		$response = curl_exec( $ch );
		curl_close( $ch );

		return $response;
	}

	/**
	 * Track an event for the specified installation and user.
	 *
	 * @param string $event_name Event name
	 * @param string $user_id User id
	 * @param string $installation_id Device (installation) id
	 * @param array $event_data Additional data to be sent
	 * @return mixed Tracking response
	 */
	public function track_event( $event_name, $user_id, $installation_id, $event_data = array() ) {
		if ( ! $installation_id || ! $user_id ) {
			return false;
		}

		return $this->request(
			'/installations/events',
			array(
				'userId'			=> $user_id,
				'installationId'	=> $installation_id,
				'eventData'			=> array(
					array(
						'type' 			=> 2,
						'data' 			=> array(
							'id' 		=> wp_generate_uuid4(),
							'data' 		=> $event_data,
							'createdAt' => microtime(),
							'eventName'	=> $event_name,
						),
					),
				),
				'deviceType'		=> 'web',
				'sessionId'			=> session_id(),
				'createdAt'			=> microtime(),
			)
		);
	}
}
