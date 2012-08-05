<?php
// Requirement
// JSONString for responses in JSON - https://github.com/paulomcnally/JSONResponse-php
// DatabaseMySQLi for database mysql connect - https://github.com/paulomcnally/DatabaseMysqli-php

require 'load.php';

$method = ( isset( $_GET['method'] ) ) ? @$_GET['method'] : NULL;

//$post_data_string = ( isset( $_POST['post_data_string'] ) ) ? @$_POST['post_data_string'] : NULL;
// Only from debug GET
$post_data_string = ( isset( $_GET['post_data_string'] ) ) ? @$_GET['post_data_string'] : NULL;

//echo "Debug: " . $post_data_string;

if( !is_null( $post_data_string ) ){
	$post_data_object = json_decode( stripslashes( $post_data_string ) );
	}
	else{
		$post_data_object = new stdClass();
		}
	
//echo "Debug: ";
//echo print_r( $post_data_object );

$api = new Api( $mysql_user, $mysql_password, $mysql_host, $mysql_database_name, $method, $post_data_object );
echo $api->api_response;
?>