<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BuilderCMS_API
{
	protected $apiUrl = ''; //'https://buildercms.com/cms/'; 
	protected $apiKey = null;
	protected $apiUser = null;
	protected $communityId = null;
	

	public function __construct( $integrationSettings )
	{	
		$this->apiKey = $integrationSettings['apiKey'] ?? '';
		$this->apiUser = $integrationSettings['apiUser'] ?? '';	
		$this->communityId = $integrationSettings['clientID'] ?? '';	
	}
	
	/** 
	 * default parameters required for authenticating BuilderCMS API request
	 * @return array
	 */
	public function auth_params()
	{
		return [
			'Username'	=> $this->apiUser,
			'Password'	=> $this->apiKey,
			'CommunityNumber'	=> $this->communityId
		];
	}

	/**
	 * @param 	string 	$action API endpoint (e.g., 'CMSProspectImport')
	 * @param 	array 	$data	Request body
	 * @return 	array/mixed  Decoded JSON response body (array) or error message
	 */
	public function make_request( $action, $data = [] )
	{	
		if (empty($data)){
			return;
		}

		// Modern API using POST
		$apiEndpoint = 'CmsService.svc';

		// Add authentication parameters to data
		$data = array_merge($this->auth_params(), $data);

		// Build request URL
		$request_url = untrailingslashit( $this->apiUrl ) . '/' . $apiEndpoint . '/' . $action;
		
		// BuilderCMS uses POST with JSON body
		$args = array(
			'body'    => json_encode($data),
			'headers' => [
				'Content-Type' => 'application/json'
			],
			'timeout' => 30
		);
		$response = wp_remote_post( $request_url, $args );
					
		// If WP_Error, die. Otherwise, return decoded JSON
		if ( is_wp_error( $response ) || !isset($response['body']) ) {
		   return $response;
		} else {
			return json_decode( $response['body'], true );
		}	
	}
	
	/**
	 * Test the provided API credentials by sending a test prospect
	 * BuilderCMS doesn't have a separate auth test endpoint
	 * 
	 * @access public
	 * @return bool
	 */
	public function auth_test()
	{	
		// BuilderCMS validates credentials on actual import requests
		// A simple validation is to check if required params are set
		if (empty($this->apiUser) || empty($this->apiKey) || empty($this->communityId)) {
			throw new \Exception( 'Missing required API credentials (Username, Password, or Community ID).' );
		}
		
		return true;
	}
	
	
	/**
	 * Import prospect data using either modern POST or legacy GET method
	 * Both methods use the same flat field structure from BuilderCMS documentation
	 * 
	 * @param array $data Contact data (flat structure with PascalCase fields)
	 * @param string $method 'POST' for modern API, 'GET' for legacy API
	 * @return array|WP_Error Response from API
	 */
	public function import_prospect($data, $method = 'POST')
	{
		if ($method === 'GET') {
			// Convert PascalCase to lowercase for legacy API
			$legacyData = array_change_key_case($data, CASE_LOWER);
			return $this->import_prospect_legacy($legacyData);
		}
		
		// For POST modern API, send flat structure to CMSProspectImport endpoint
		return $this->make_request('CMSProspectImport', $data);
	}
	


	/**
	 * LEGACY METHOD: Import contact using ProspectImport.aspx endpoint
	 * Uses GET requests with URL-encoded tilde-separated data.
	 * 
	 * @access public
	 * @param array $data Contact data in lowercase format (matching Elementor)
	 * @return array Response from BuilderCMS
	 */
	public function import_prospect_legacy( $data )
	{
		$endpoint = 'custom/ProspectImport.aspx';
		
		// Encode the data in the legacy format (Key:Value~Key:Value~...)
		$dataString = $this->encode_legacy_data($data);
		
		// Build the request URL
		$requestUrl = untrailingslashit( $this->apiUrl ) . '/' .$endpoint . "?ProspectData=$dataString";
		
		// Send GET request
		$response = wp_remote_get($requestUrl);
		
		// Handle response - match Elementor's error handling
		if ( ! is_wp_error( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			return $body;
		} else {
			return $response;
		}
	}
	
	/**
	 * Encode data in the legacy BuilderCMS format
	 * Format: Key:Value~Key:Value~Key:Value
	 * 
	 * @param array $data Data to encode
	 * @return string URL-encoded data string
	 */
	private function encode_legacy_data($data)
	{
		// Exactly as implemented in Elementor
		$datastring = implode('~', array_map(function($key, $value) {
			return $key.':'.$value;
		}, array_keys($data), $data));
	
		return urlencode($datastring);
	}

}
