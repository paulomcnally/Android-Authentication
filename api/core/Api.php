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

class Api{
	// MySQL var to Databasemanipule class 
	private $mysql = NULL;
	
	// Responses manipule in JSON
	private $json_responses = NULL;
	
	// User from database MySQL
	private $dbuser;
	
	// Password from database MySQL
	private $dbpassword;
	
	// Host from database MySQL
	private $dbhost;
	
	// Name from database MySQL
	private $dbname;
	
	// Data form
	private $post_data;
	
	// Final response
	public $api_response = "";
	
	private $response_validate = "";
	
	// Initialize api
	public function __construct( $dbuser, $dbpassword, $dbhost, $dbname, $method, $post_data_object ){
		$this->dbhost = $dbhost;
		$this->dbname = $dbname;
		$this->dbpassword = $dbpassword;
		$this->dbuser = $dbuser;
		
		$this->post_data = $post_data_object;
		
		// Initialize JSON response
		$this->json_response_init();
		// Initialize MySQL connection
		$this->mysql_init();
		
		if( in_array( $method, array("login","signup") ) ){
			$this->api_response =  $this->{"method_".$method}();
			}
			else{
				if( $method == NULL || $method == "" ){
					$this->json_responses->makeError( "ApiMethodException", "Method is required" );
				}
				else{
					$this->json_responses->makeError( "ApiMethodException", $method . " not is a valid method" );
				}
				
				
				$this->api_response = $this->json_responses->getStringResponseOut();
				}
		
		
		}
		
	private function mysql_init(){
		$this->mysql = new Database( $this->dbhost, $this->dbuser, $this->dbpassword, $this->dbname );
		}
	
	private function json_response_init(){
		$this->json_responses = new JSONResponse();
		}
	/**
	 * Login Method
	 */
	private function method_login(){
		
		// Email Validation
		if( isset( $this->post_data->user_email ) ){
			if( empty( $this->post_data->user_email ) ){
				$this->json_responses->makeError( "FormValidateException", "Email is required" );
				}
				else{
					// Validatin with regular expresion a valid email
					if( !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $this->post_data->user_email ) ){
						$this->json_responses->makeError( "FormValidateException", "The email ".$this->post_data->user_email." not is a valid email" );
					}
				}
			
			}
			else{
				$this->json_responses->makeError( "FormValidateException", "Email is not received" );
			}
		
		$this->response_validate = $this->json_responses->getStringResponseOut();
		if( !empty( $this->response_validate ) ){
			return $this->response_validate;
			}
			else{
				$this->response_validate = "";
				}
		
		// Password Validation
		if( isset( $this->post_data->user_password ) ){
			if( empty( $this->post_data->user_password ) ){
				$this->json_responses->makeError( "FormValidateException", "Password is required" );
				}
				else{
					if( strlen( $this->post_data->user_password ) < 6 ){
					$this->json_responses->makeError( "FormValidateException", "The password need 6 or more char" );
					}
				}
			}
			else{
				$this->json_responses->makeError( "FormValidateException", "Password is not received" );
			}
		
		$this->response_validate = $this->json_responses->getStringResponseOut();
		if( !empty( $this->response_validate ) ){
			return $this->response_validate;
			}
			else{
				$this->response_validate = "";
				}
		
		if( $this->json_responses->getStringResponseOut() == "" || $this->json_responses->getStringResponseOut() == NULL ){
			
			
			$token = $this->mysql->getVar("SELECT user_token FROM `user` WHERE user_email = '".$this->mysql->_real_escape($this->post_data->user_email)."' AND user_password = '".md5(md5($this->post_data->user_password))."'");
			
			if( !is_null( $token ) ){
				$response = new StdClass();
    			$response->message = "Login success ok";
				$response->token = $token;
    			$this->json_responses->makeResponse( $response );
			}
			else{
				$this->json_responses->makeError( "LoginException", "Incorrect data, pleace retry" );
			}
			
		}
		
		return $this->json_responses->getStringResponseOut();		
		
	}
		
		
	
	/**
	 * Signup Method
	 */
	private function method_signup(){
		//user_id, user_first_name, user_last_name, user_email, user_password, user_registered, user_token
		
		// First Name Validation
		if( isset( $this->post_data->user_first_name ) ){
			if( empty( $this->post_data->user_first_name ) ){
				$this->json_responses->makeError( "FormValidateException", "First name is required" );
			}
			else{
				if( strlen( $this->post_data->user_first_name ) < 3 ){
					$this->json_responses->makeError( "FormValidateException", "First name need 3 or more char" );
				}
			}
		}
		else{
			$this->json_responses->makeError( "FormValidateException", "First name is not received" );
		}
		
		$this->response_validate = $this->json_responses->getStringResponseOut();
		if( !empty( $this->response_validate ) ){
			return $this->response_validate;
			}
			else{
				$this->response_validate = "";
				}
		

		// Last Name Validation
		if( isset( $this->post_data->user_last_name ) ){
			if( empty( $this->post_data->user_last_name ) ){
				$this->json_responses->makeError( "FormValidateException", "Last name is required" );
				}
				else{
					if( strlen( $this->post_data->user_last_name ) < 3 ){
					$this->json_responses->makeError( "FormValidateException", "Last name need 3 or more char" );
					}
				}
			}
			else{
				$this->json_responses->makeError( "FormValidateException", "Last name is not received" );
			}
		
		$this->response_validate = $this->json_responses->getStringResponseOut();
		if( !empty( $this->response_validate ) ){
			return $this->response_validate;
			}
			else{
				$this->response_validate = "";
				}
		

		
		// Email Validation
		if( isset( $this->post_data->user_email ) ){
			if( empty( $this->post_data->user_email ) ){
				$this->json_responses->makeError( "FormValidateException", "Email is required" );
				}
				else{
					// Validatin with regular expresion a valid email
					if( !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $this->post_data->user_email ) ){
						$this->json_responses->makeError( "FormValidateException", "The email ".$this->post_data->user_email." not is a valid email" );
					}
					else{
						// Validate email exist
						if( $e = $this->mysql->query("SELECT user_id FROM `user` WHERE user_email = '".$this->mysql->_real_escape($this->post_data->user_email)."'") ){
							$this->json_responses->makeError( "FormValidateException", "The email ".$this->post_data->user_email." is registered, use other" );
							}
						}
				}
			
			}
			else{
				$this->json_responses->makeError( "FormValidateException", "Email is not received" );
			}
		
		$this->response_validate = $this->json_responses->getStringResponseOut();
		if( !empty( $this->response_validate ) ){
			return $this->response_validate;
			}
			else{
				$this->response_validate = "";
				}
		
		// Password Validation
		if( isset( $this->post_data->user_password ) ){
			if( empty( $this->post_data->user_password ) ){
				$this->json_responses->makeError( "FormValidateException", "Password is required" );
				}
				else{
					if( strlen( $this->post_data->user_password ) < 6 ){
					$this->json_responses->makeError( "FormValidateException", "The password need 6 or more char" );
					}
				}
			}
			else{
				$this->json_responses->makeError( "FormValidateException", "Password is not received" );
			}
		
		$this->response_validate = $this->json_responses->getStringResponseOut();
		if( !empty( $this->response_validate ) ){
			return $this->response_validate;
			}
			else{
				$this->response_validate = "";
				}
		
		if( $this->json_responses->getStringResponseOut() == "" || $this->json_responses->getStringResponseOut() == NULL ){
			$token = substr( md5( time()."apiKeyToken"),0,6);
			$data = array(  
				"user_first_name" => $this->post_data->user_first_name, 
				"user_last_name" => $this->post_data->user_last_name, 
				"user_email" => $this->post_data->user_email, 
				"user_password" => md5(md5($this->post_data->user_password)), 
				"user_registered" => date("Y-m-d H:i:s"), 
				"user_token" => $token
			 );
			$this->mysql->insert("user", $data );
			$response = new StdClass();
    		$response->message = "Registration success ok";
			$response->token = $token;
    		$this->json_responses->makeResponse( $response );
			}
		
		return $this->json_responses->getStringResponseOut();
		
		}

}
?>