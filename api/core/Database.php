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
 

class Database {
	
	/**
	 * Database Username
	 *
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $dbuser;
	
	/**
	 * Database Password
	 *
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $dbpassword;
	
	/**
	 * Database Name
	 *
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $dbname;
	
	/**
	 * Database Host
	 *
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $dbhost;
	
	/**
	 * Results of the last query made
	 *
	 * @since 0.1
	 * @access public
	 * @var array|null
	 */
	public $last_result;
	
	/**
	 * Saved result of the last query made
	 *
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	public $last_query;
	
	/**
	 * JSON Response
	 *
	 * @since 0.1
	 * @access private
	 * @var class
	 */
	private $json_response = NULL;
	
	/**
	 * MySQLi
	 *
	 * @since 0.1
	 * @access private
	 * @var class
	 */
	private $mysqli = NULL;
	
	/**
	 * Exception Name
	 *
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $exception	=	"DatabaseException";
	
	/**
	 * MySQL affected_rows
	 *
	 * @since 0.1
	 * @access public
	 * @var int
	 */
	public $affected_rows = 0;
	
	
	/**
	 * MySQL insert_id
	 *
	 * @since 0.1
	 * @access public
	 * @var int
	 */
	public $insert_id = 0;
	
	/**
	 * MySQL result
	 *
	 * @since 0.1
	 * @access private
	 * @var mysql_result
	 */
	private $result;
	
	
	/**
	 * Saved info on the table column
	 *
	 * @since 0.1
	 * @access private
	 * @var array
	 */
	private $column_info;
	
	/**
	 * Count of rows returned by previous query
	 *
	 * @since 0.1
	 * @access private
	 * @var int
	 */
	private $num_rows = 0;
	
	/**
	 * Format specifiers for DB columns. Columns not listed here default to %s.
	 *
	 * Keys are column names, values are format types: 'ID' => '%d'
	 *
	 * @since 2.8.0
	 * @see Database::prepare()
	 * @see Database::insert()
	 * @see Database::update()
	 * @see Database::delete()
	 * @access public
	 * @var array
	 */
	private $field_types = array();
	
	/**
	 * Whether to use mysql_real_escape_string
	 *
	 * @since 2.8.0
	 * @access public
	 * @var bool
	 */
	private $real_escape = false;
	
	/**
	 * Count of affected rows by previous query
	 *
	 * @since 0.71
	 * @access private
	 * @var int
	 */
	public $rows_affected = 0;
	
	
	/**
	 * Connects to the database server and selects a database
	 *
	 * PHP5 style constructor for compatibility with PHP5. Does
	 * the actual setting up of the class properties and connection
	 * to the database.
	 *
	 * @since 0.1
	 *
	 * @param string $dbhost MySQL database host
	 * @param string $dbuser MySQL database user
	 * @param string $dbpassword MySQL database password
	 * @param string $dbname MySQL database name
	 */
	public function __construct( $dbhost, $dbuser, $dbpassword, $dbname ) {
		register_shutdown_function( array( &$this, '__destruct' ) );
		
		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;
		
		$this->json_response = new JSONResponse();
		@$this->mysqli = new MySQLi( $this->dbhost, $this->dbuser, $this->dbpassword, $this->dbname );
		
		if( $this->mysqli->connect_errno ){
			$this->json_response->makeError( $this->exception, $this->mysqli->connect_error);
			$this->print_error();
		}
	}
	
	
	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @see Database::__construct()
	 * @since 0.1
	 * @return bool true
	 */
	public function __destruct() {
		return true;
	}
	
	
	/**
	 * Show error messaje in json format
	 *
	 * @since 0.1
	 * @return bool true
	 */
	private function print_error(  ) {
		die( $this->json_response->getStringResponseOut() );
	}
	
	/**
	 * Kill cached query results.
	 *
	 * @since 0.1
	 * @return void
	 */
	private function flush_var() {
		$this->last_result = array();
		$this->column_info    = NULL;
		$this->last_query  = NULL;
	}
	
	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.1
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	public function query( $query ) {
		
		$return_val = 0;
		
		$this->flush_var();
		
		$this->last_query = $query;
		
		$this->result = $this->mysqli->query( $query );
		
		// If there is an error then take note of it..
		if ( $this->mysqli->errno ) {
			$this->json_response->makeError( $this->exception, $this->mysqli->error );
			$this->print_error();
			return false;
		}
		
		if ( preg_match( '/^\s*(create|alter|truncate|drop) /i', $query ) ) {
			$return_val = $this->result;
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace) /i', $query ) ) {
			$this->affected_rows = $this->mysqli->affected_rows;
			// Take note of the insert_id
			if ( preg_match( '/^\s*(insert|replace) /i', $query ) ) {
				$this->insert_id = $this->mysqli->insert_id;
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$i = 0;
			while ( $i < @$this->result->field_count ) {
				$this->column_info[$i] = @$this->result->fetch_field();
				$i++;
			}
			$num_rows = 0;
			while ( $row = @$this->result->fetch_object() ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			$this->result->free();

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		return $return_val;
	}
	
	/**
	 * Retrieve one variable from the database.
	 *
	 * Executes a SQL query and returns the value from the SQL result.
	 * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
	 * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
	 *
	 * @since 0.1
	 *
	 * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
	 * @param int $x Optional. Column of value to return. Indexed from 0.
	 * @param int $y Optional. Row of value to return. Indexed from 0.
	 * @return string|null Database query result (as string), or null on failure
	 */
	public function getVar( $query = null, $x = 0, $y = 0 ) {
		if ( $query )
			$this->query( $query );

		// Extract var out of cached results based x,y vals
		if ( !empty( $this->last_result[$y] ) ) {
			$values = array_values( get_object_vars( $this->last_result[$y] ) );
		}

		// If there is a value return it else return null
		return ( isset( $values[$x] ) && $values[$x] !== '' ) ? $values[$x] : null;
	}
	
	/**
	 * Retrieve one row from the database.
	 *
	 * Executes a SQL query and returns the row from the SQL result.
	 *
	 * @since 0.1
	 *
	 * @param string|null $query SQL query.
	 * @param int $y Optional. Row to return. Indexed from 0.
	 * @return mixed Database query result in format specified by $output or null on failure
	 */
	public function getRow( $query = NULL, $y = 0 ) {
		if ( $query ){
			$this->query( $query );
		}
		else{
			return NULL;
		}

		if ( !isset( $this->last_result[$y] ) ){
			return NULL;
		}
		return $this->last_result[$y] ? $this->last_result[$y] : NULL;
	}
	
	/**
	 * Retrieve one column from the database.
	 *
	 * Executes a SQL query and returns the column from the SQL result.
	 * If the SQL result contains more than one column, this function returns the column specified.
	 * If $query is null, this function returns the specified column from the previous SQL result.
	 *
	 * @since 0.71
	 *
	 * @param string|null $query Optional. SQL query. Defaults to previous query.
	 * @param int $x Optional. Column to return. Indexed from 0.
	 * @return array Database query result. Array indexed from 0 by SQL result row number.
	 */
	public function getColumn( $query = NULL , $x = 0 ) {
		if ( $query ){
			$this->query( $query );
		}

		$new_array = array();
		// Extract the column values
		for ( $i = 0, $j = count( $this->last_result ); $i < $j; $i++ ) {
			$new_array[$i] = $this->getVar( NULL, $x, $i );
		}
		return $new_array;
	}
	
	/**
	 * Retrieve an entire SQL result set from the database (i.e., many rows)
	 *
	 * Executes a SQL query and returns the entire SQL result.
	 *
	 * @since 0.1
	 *
	 * @param string $query SQL query.
	 * @return mixed Database query results
	 */
	public function getResults( $query = NULL ) {
		if ( $query ){
			$this->query( $query );
		}
		else{
			return null;
		}
			
	return $this->last_result;
	}
	
	/**
	 * Retrieve column metadata from the last query.
	 *
	 * @since 0.71
	 *
	 * @param string $info_type Optional. Type one of name, table, def, max_length, not_null, primary_key, multiple_key, unique_key, numeric, blob, type, unsigned, zerofill
	 * @param int $col_offset Optional. 0: col name. 1: which table the col's in. 2: col's max length. 3: if the col is numeric. 4: col's type
	 * @return mixed Column Results
	 */
	public function getColumnInfo( $info_type = 'name', $column_offset = -1 ) {
		if ( $this->column_info ) {
			if ( $column_offset == -1 ) {
				$i = 0;
				$new_array = array();
				foreach( (array) $this->column_info as $col ) {
					$new_array[$i] = $col->{$info_type};
					$i++;
				}
				return $new_array;
			} else {
				return $this->column_info[$column_offset]->{$info_type};
			}
		}
	}
	
	/**
	 * Helper function for insert and replace.
	 *
	 * Runs an insert or replace query based on $type argument.
	 *
	 * @access private
	 * @since 3.0.0
	 * @see Database::prepare()
	 * @see Database::$field_types
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in Database::$field_types.
	 * @param string $type Optional. What type of operation is this? INSERT or REPLACE. Defaults to INSERT.
	 * @return int|false The number of rows affected, or false on error.
	 */
	private function _insert_replace_helper( $table, $data, $format = NULL, $type = 'INSERT' ) {
		if ( ! in_array( strtoupper( $type ), array( 'REPLACE', 'INSERT' ) ) )
			return false;
		$formats = $format = (array) $format;
		$fields = array_keys( $data );
		$formatted_fields = array();
		foreach ( $fields as $field ) {
			if ( !empty( $format ) )
				$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
			elseif ( isset( $this->field_types[$field] ) )
				$form = $this->field_types[$field];
			else
				$form = '%s';
			$formatted_fields[] = $form;
		}
		$sql = "{$type} INTO `$table` (`" . implode( '`,`', $fields ) . "`) VALUES (" . implode( ",", $formatted_fields ) . ")";
		return $this->query( $this->prepare( $sql, $data ) );
	}
	
	/**
	 * Prepares a SQL query for safe execution. Uses sprintf()-like syntax.
	 *
	 * The following directives can be used in the query format string:
	 *   %d (integer)
	 *   %f (float)
	 *   %s (string)
	 *   %% (literal percentage sign - no argument needed)
	 *
	 * All of %d, %f, and %s are to be left unquoted in the query string and they need an argument passed for them.
	 * Literals (%) as parts of the query must be properly written as %%.
	 *
	 * This function only supports a small subset of the sprintf syntax; it only supports %d (integer), %f (float), and %s (string).
	 * Does not support sign, padding, alignment, width or precision specifiers.
	 * Does not support argument numbering/swapping.
	 *
	 * May be called like {@link http://php.net/sprintf sprintf()} or like {@link http://php.net/vsprintf vsprintf()}.
	 *
	 * Both %d and %s should be left unquoted in the query string.
	 *
	 * <code>
	 * Database::prepare( "SELECT * FROM `table` WHERE `column` = %s AND `field` = %d", 'foo', 1337 )
	 * Database::prepare( "SELECT DATE_FORMAT(`field`, '%%c') FROM `table` WHERE `column` = %s", 'foo' );
	 * </code>
	 *
	 * @link http://php.net/sprintf Description of syntax.
	 * @since 2.3.0
	 *
	 * @param string $query Query statement with sprintf()-like placeholders
	 * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like
	 * 	{@link http://php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if
	 * 	being called like {@link http://php.net/sprintf sprintf()}.
	 * @param mixed $args,... further variables to substitute into the query's placeholders if being called like
	 * 	{@link http://php.net/sprintf sprintf()}.
	 * @return null|false|string Sanitized query string, null if there is no query, false if there is an error and string
	 * 	if there was something to prepare
	 */
	function prepare( $query = NULL ) { // ( $query, *$args )
		if ( is_null( $query ) )
			return;

		$args = func_get_args();
		array_shift( $args );
		// If args were passed as an array (as in vsprintf), move them up
		if ( isset( $args[0] ) && is_array($args[0]) )
			$args = $args[0];
		$query = str_replace( "'%s'", '%s', $query ); // in case someone mistakenly already singlequoted it
		$query = str_replace( '"%s"', '%s', $query ); // doublequote unquoting
		$query = preg_replace( '|(?<!%)%s|', "'%s'", $query ); // quote the strings, avoiding escaped strings like %%s
		array_walk( $args, array( &$this, 'escape_by_ref' ) );
		return @vsprintf( $query, $args );
	}
	
	/**
	 * Insert a row into a table.
	 *
	 * <code>
	 * Database::insert( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * Database::insert( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @since 2.5.0
	 * @see Database::prepare()
	 * @see Database::$field_types
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in Database::$field_types.
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert( $table, $data, $format = NULL ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'INSERT' );
	}
	
	/**
	 * Replace a row into a table.
	 *
	 * <code>
	 * Database::replace( 'table', array( 'column' => 'foo', 'field' => 'bar' ) )
	 * Database::replace( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( '%s', '%d' ) )
	 * </code>
	 *
	 * @since 3.0.0
	 * @see Database::prepare()
	 * @see Database::$field_types
	 *
	 * @param string $table table name
	 * @param array $data Data to insert (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array|string $format Optional. An array of formats to be mapped to each of the value in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in Database::$field_types.
	 * @return int|false The number of rows affected, or false on error.
	 */
	function replace( $table, $data, $format = null ) {
		return $this->_insert_replace_helper( $table, $data, $format, 'REPLACE' );
	}
	
	/**
	 * Update a row in the table
	 *
	 * <code>
	 * Database::update( 'table', array( 'column' => 'foo', 'field' => 'bar' ), array( 'ID' => 1 ) )
	 * Database::update( 'table', array( 'column' => 'foo', 'field' => 1337 ), array( 'ID' => 1 ), array( '%s', '%d' ), array( '%d' ) )
	 * </code>
	 *
	 * @since 2.5.0
	 * @see Database::prepare()
	 * @see Database::$field_types
	 *
	 * @param string $table table name
	 * @param array $data Data to update (in column => value pairs). Both $data columns and $data values should be "raw" (neither should be SQL escaped).
	 * @param array $where A named array of WHERE clauses (in column => value pairs). Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @param array|string $format Optional. An array of formats to be mapped to each of the values in $data. If string, that format will be used for all of the values in $data.
	 * 	A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $data will be treated as strings unless otherwise specified in Database::$field_types.
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where. If string, that format will be used for all of the items in $where. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $where will be treated as strings.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( ! is_array( $data ) || ! is_array( $where ) )
			return false;

		$formats = $format = (array) $format;
		$bits = $wheres = array();
		foreach ( (array) array_keys( $data ) as $field ) {
			if ( !empty( $format ) )
				$form = ( $form = array_shift( $formats ) ) ? $form : $format[0];
			elseif ( isset($this->field_types[$field]) )
				$form = $this->field_types[$field];
			else
				$form = '%s';
			$bits[] = "`$field` = {$form}";
		}

		$where_formats = $where_format = (array) $where_format;
		foreach ( (array) array_keys( $where ) as $field ) {
			if ( !empty( $where_format ) )
				$form = ( $form = array_shift( $where_formats ) ) ? $form : $where_format[0];
			elseif ( isset( $this->field_types[$field] ) )
				$form = $this->field_types[$field];
			else
				$form = '%s';
			$wheres[] = "`$field` = {$form}";
		}

		$sql = "UPDATE `$table` SET " . implode( ', ', $bits ) . ' WHERE ' . implode( ' AND ', $wheres );
		return $this->query( $this->prepare( $sql, array_merge( array_values( $data ), array_values( $where ) ) ) );
	}
	
	/**
	 * Delete a row in the table
	 *
	 * <code>
	 * Database::delete( 'table', array( 'ID' => 1 ) )
	 * Database::delete( 'table', array( 'ID' => 1 ), array( '%d' ) )
	 * </code>
	 *
	 * @since 0.1
	 *
	 * @param string $table table name
	 * @param array $where A named array of WHERE clauses (in column => value pairs). Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @param array|string $where_format Optional. An array of formats to be mapped to each of the values in $where. If string, that format will be used for all of the items in $where. A format is one of '%d', '%f', '%s' (integer, float, string). If omitted, all values in $where will be treated as strings unless otherwise specified in Database::$field_types.
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete( $table, $where, $where_format = null ) {
		if ( ! is_array( $where ) )
			return false;

		$bits = $wheres = array();

		$where_formats = $where_format = (array) $where_format;

		foreach ( array_keys( $where ) as $field ) {
			if ( !empty( $where_format ) ) {
				$form = ( $form = array_shift( $where_formats ) ) ? $form : $where_format[0];
			} elseif ( isset( $this->field_types[ $field ] ) ) {
				$form = $this->field_types[ $field ];
			} else {
				$form = '%s';
			}

			$wheres[] = "$field = $form";
		}

		$sql = "DELETE FROM $table WHERE " . implode( ' AND ', $wheres );
		return $this->query( $this->prepare( $sql, $where ) );
	}
	
	/**
	 * Escapes content by reference for insertion into the database, for security
	 *
	 * @since 0.1
	 * @param string $string to escape
	 * @return void
	 */
	public function escape_by_ref( &$string ) {
		$string = $this->_real_escape( $string );
	}
	
	/**
	 * Real escape, using mysql_real_escape_string() or addslashes()
	 *
	 * @see addslashes()
	 * @since 0.1
	 * @access private
	 *
	 * @param  string $string to escape
	 * @return string escaped
	 */
	public function _real_escape( $string ) {
		if ( $this->real_escape ){
			return $this->mysqli->real_escape_string( $string );
		}	
		else{
			return addslashes( $string );
		}
	}

}

?>