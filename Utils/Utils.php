<?php

namespace Utils;

use \PhalconRest\Exceptions\HTTPException;
use \Phalcon\Http\Request;

Class Utils {

	public function __construct() {
		$this->logger = \Phalcon\DI::getDefault()->get('logger');
	}
	
	public function json_decode($json, $inArray = false) {
		$rest = json_decode($json, $inArray);
		$this->logger->debug(">>> [Utils][json_decode] - Json: " . print_r($json, true));
		if (json_last_error() == JSON_ERROR_NONE){
			if($rest['_meta']['status'] == "SUCCESS"){
				return $rest['records'];
			}else{
				throw new HTTPException(
					$rest['records']['userMessage'], // Error Message
					$rest['records']['errorCode'],  // Error Code
					$rest['records']
				);
			}
		}
		
		throw new HTTPException("response of projects service is not Json",
			404,
			array(
				'internalCode' => json_last_error(),
				'more' => $json
			)
		);
	}

	/**
	 * Extract value from array and given key.
	 */
	public function getValueFromArray($arr, $key) {
		return (isset($arr[$key]) && !empty($arr[$key]) ? $arr[$key] : null);
	}

	/**
	 * Extract jsonRawBody from request payload.
	 * As array or object.
	 */
	public function getJsonBody($as_array = false) {
		$req = new Request();
		$json_body = $req->getJsonRawBody();
		return ($as_array ? (array) $json_body : $json_body);
	}

	/**
	 * Extract payload from POST request.
	 */
	public function post() {
		$req = new Request();
		$json_body = (array) $req->getJsonRawBody();
		if (!$json_body) $json_body = $req->getPost();
		return $json_body;
	}
	
	/**
	 * Get GET params.
	 */
	public function get() {
		return $_GET;
	}

	/**
	 * Extract JWT from header.
	 */
	public function getJWT() {
        /**
         * Define header key.
         */
		$authorization_key = "Authorization";
		
		/**
		 * Catch request.
		 */
		$req = new Request();
		$headers = $req->getHeaders();

		/**
         * Check if sent and sent correctly.
         */
        if (!isset($headers[$authorization_key]) || empty($headers[$authorization_key]) || strpos($headers[$authorization_key], "Bearer ") < 0) {
            return false;
		}
		
        /**
         * Return it.
         */
        return $token = trim(explode("Bearer ", $headers[$authorization_key])[1]);
	}
}