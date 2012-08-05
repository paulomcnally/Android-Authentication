<?php
/**
 * Copyright 2012 McNally Developer, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */


if (!function_exists('json_decode')) {
  throw new Exception('JSONResponse needs the JSON PHP extension.');
}

class JSONResponse {
	
	/**
     * Version.
     */
	const VERSION = '1.0.0';
	
	/*
	 * int
	 */
	private $type;
	
	/*
	 * String
	 */
	private $string_response_out = "";
	
	/*
	 * String
	 */
	private $string_response_in;

	/*
	 * Object
	 */
	private $object_response_out;
	
	/*
	 * Object
	 */
	private $error_data;
	
	
	/**
   * Initialize a JSONResponse.
   *
   * The configuration:
   * - type:	0 => return string json
   *			1 => return object
   *
   * @param int $type
   */
	public function __construct(  ) {
		}
	
	/**
	 * Create an object type that contains a mistake with the
	 * type and message  
	 *
	 * @param int $type, String message
	 *
	 * @return none
	 */
	public function makeError( $string_type, $string_message ){
		$this->object_response_out = new stdClass();
		$this->error_data = new stdClass();
		$this->error_data->type = $string_type;
		$this->error_data->message = $string_message;
		$this->object_response_out->error = $this->error_data;
		}
	
	/**
	 * Set in the variable $ object_response_out an object
	 *
	 * @param Object $response
	 *
	 * @return none
	 */
	public function makeResponse( $object_response ){
		$this->object_response_out = $object_response;
		}
	
	/**
	 * Converts an object into a JSON string
	 *
	 * @return String JSON
	 */
	public function getStringResponseOut(){
		if( is_object( $this->object_response_out ) ){
			$this->string_response_out = json_encode( $this->object_response_out );
			}
		return $this->string_response_out;
		}
	
	/**
	 * @return Object
	 */
	public function getObjectResponseOut(){
		return $this->object_response;
		}
	
	
	/**
	 * Converts a JSON string to an object if necessary
	 * and sets the data in the variable $ string_response_in
	 *
	 * @param String $response
	 *
	 * @return none
	 */
	public function setStringResponseIn( $string_response = '{}' ){
		if( !is_object( $response ) ){
			$this->string_response_in = json_decode( $string_response );
			}
		}
	
	/**
	 * Converts a JSON string to an object if necessary
	 * and sets the data in the variable $ string_response_in
	 *
	 * @return boolean
	 */
	public function isError( ){
		if( isset( $this->string_response_in->error ) ){
			return true;
			}
			else{
				return false;
				}
		}
	
}
?>