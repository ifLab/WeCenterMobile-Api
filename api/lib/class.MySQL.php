<?php
class MySQL {
	// Base variables
	public $lastError;					// Holds the last error
	public $lastQuery;					// Holds the last query
	public $result;				        // Holds the MySQL query result
	public $records;				    // Holds the total number of records returned
	public $affected;					// Holds the total number of records affected
	public $rawResults;				    // Holds raw 'arrayed' results
	public $arrayedResult;			    // Holds an array of the result
	
	private $hostname = '';				// MySQL Hostname
	private $username = '';	            // MySQL Username
	private $password = '';	            // MySQL Password
	private $database = '';	            // MySQL Database
	
	private $databaseLink;		// Database Connection Link
	

	/* *******************
	 * Class Constructor *
	 * *******************/
	
	function __construct( $hostname, $username, $password, $database ){
		$this->hostname = $hostname;
		$this->username = $username;
		$this->password = $password;
		$this->database = $database;
		$this->Connect();
	}
	
	/* *******************
	 * Private Functions *
	 * *******************/
	
	// Connects class to database
	// $persistant (boolean) - Use persistant connection?
	private function Connect($persistant = false){
		$this->CloseConnection();
		
		if($persistant){
			$this->databaseLink = mysql_pconnect($this->hostname, $this->username, $this->password);
		}else{
			$this->databaseLink = mysql_connect($this->hostname, $this->username, $this->password);
		}
		
		if(!$this->databaseLink){
   		    $this->lastError = 'Could not connect to server: ' . mysql_error($this->databaseLink);
			return false;
		}
		
		if(!$this->UseDB()){
			$this->lastError = 'Could not connect to database: ' . mysql_error($this->databaseLink);
			return false;
		}
		return true;
	}
	
	
	// Select database to use
	private function UseDB(){
		if(!mysql_select_db($this->database, $this->databaseLink)){
			$this->lastError = 'Cannot select database: ' . mysql_error($this->databaseLink);
			return false;
		}else{
			mysql_query("set names utf8");  //default utf-8
			return true;
		}
	}
	
	
	// Performs a 'mysql_real_escape_string' on the entire array/string
	private function SecureData($data){
		if(is_array($data)){
			foreach($data as $key=>$val){
				if(!is_array($data[$key])){
					$data[$key] = mysql_real_escape_string($data[$key], $this->databaseLink);
				}
			}
		}else{
			$data = mysql_real_escape_string($data, $this->databaseLink);
		}
		return $data;
	}
	
	
	
	/* ******************
	 * Public Functions *
	 * ******************/
	
	// Executes MySQL query
	function ExecuteSQL($query){
		$this->lastQuery = $query;
		if( $this->result = mysql_query($query, $this->databaseLink)){
			$this->records = @mysql_num_rows($this->result);
			$this->affected	= @mysql_affected_rows($this->databaseLink);
			if( $this->records > 0 ){
				$this->ArrayResults();
				return $this->arrayedResult;
			}else{
				return true;
			}
		}else{
			$this->lastError = mysql_error($this->databaseLink);
			die( mysql_error($this->databaseLink) );
		}
	}
	
	
	/**
	 * Adds a record to the database based on the array key names
	 * $vars array
	 * success return true
	 * failed return false
	 */
	function Insert($vars, $table){	
		// Prepare Variables
		$vars = $this->SecureData($vars);
		
		$cols = array(); $vals = array();
		foreach($vars as $key => $value){
			$cols[] = $key;
			if( empty( $value ) ){
				$vals[] = "NULL";
			}else{
			    $vals[] = "'".$value."'";
			}
		}
		$col = join(',', $cols);
		$val = join(',', $vals);

		$query = "INSERT INTO {$table} ({$col}) VALUES ({$val})";
		
		return $this->ExecuteSQL($query);
	}
	
	// Deletes a record from the database
	// $where array
	function Delete($table, $where='', $limit='', $like=false){
		$query = "DELETE FROM `{$table}` WHERE ";
		if(is_array($where) && $where != ''){
			// Prepare Variables
			$where = $this->SecureData($where);
			
			foreach($where as $key=>$value){
				if($like){
					//$query .= '`' . $key . '` LIKE "%' . $value . '%" AND ';
					$query .= "`{$key}` LIKE '%{$value}%' AND ";
				}else{
					//$query .= '`' . $key . '` = "' . $value . '" AND ';
					$query .= "`{$key}` = '{$value}' AND ";
				}
			}
			
			$query = substr($query, 0, -5);
		}
		
		if($limit != ''){
			$query .= ' LIMIT ' . $limit;
		}
		
		return $this->ExecuteSQL($query);
	}
	
	
	/*
	 * Gets a single row from $from where $where is true
	 * @param $where array
	 * if get data return array, else if success execut return true, else return false
	 */
	function Select( $cols='*', $table, $where='', $orderBy='', $limit='', $like=false, $operand='AND'){
		// Catch Exceptions
		if(trim($table) == ''){
			return false;
		}
		
		$query = "SELECT {$cols} FROM `{$table}` WHERE ";
		
		if(is_array($where) && $where != ''){
			// Prepare Variables
			$where = $this->SecureData($where);
			
			foreach($where as $key=>$value){
				if($like){
					//$query .= '`' . $key . '` LIKE "%' . $value . '%" ' . $operand . ' ';
					$query .= "`{$key}` LIKE '%{$value}%' {$operand} ";
				}else{
					//$query .= '`' . $key . '` = "' . $value . '" ' . $operand . ' ';
					$query .= "`{$key}` = '{$value}' {$operand} ";
				}
			}

			$query = substr($query, 0, -(strlen($operand)+2));
		}elseif(is_string($where) && $where != '') {
			$query .=  $where;
		}else{
			$query = substr($query, 0, -7);
		}
		
		if($orderBy != ''){
			$query .= ' ORDER BY ' . $orderBy;
		}
		
		if($limit != ''){
			$query .= ' LIMIT ' . $limit;
		}
		
		return $this->ExecuteSQL($query);	
	}
	
	// Updates a record in the database based on WHERE
	// $set array
	// $where array
	function Update($table, $set, $where){
		// Catch Exceptions
		if(trim($table) == '' || !is_array($set) || !is_array($where)){
			return false;
		}
		
		$set = $this->SecureData($set);
		$where 	= $this->SecureData($where);
		
		// SET
		$query = "UPDATE `{$table}` SET ";
		
		foreach($set as $key=>$value){
			if( empty( $value ) ){
				$query .= "`{$key}` = NULL, ";
			}else{
				$query .= "`{$key}` = '{$value}', ";
			}
		}
		$query = substr($query, 0, -2);

		// WHERE
		$query .= ' WHERE ';
		foreach($where as $key=>$value){
			$query .= "`{$key}` = '{$value}' AND ";
		}
		$query = substr($query, 0, -5);
		
		return $this->ExecuteSQL($query);
	}
	
	// 'Arrays' a single result
	function ArrayResult(){
		$this->arrayedResult = mysql_fetch_assoc($this->result) or die (mysql_error($this->databaseLink));
		return $this->arrayedResult;
	}

	// 'Arrays' multiple result
	function ArrayResults(){
		
		if($this->records == 1){
			return $this->ArrayResult();
		}
		
		$this->arrayedResult = array();
		while ($data = mysql_fetch_assoc($this->result)){
			$this->arrayedResult[] = $data;
		}
		return $this->arrayedResult;
	}
	
	// 'Arrays' multiple results with a key
	function ArrayResultsWithKey($key='id'){
		if(isset($this->arrayedResult)){
			unset($this->arrayedResult);
		}
		$this->arrayedResult = array();
		while($row = mysql_fetch_assoc($this->result)){
			foreach($row as $theKey => $theValue){
				$this->arrayedResult[$row[$key]][$theKey] = $theValue;
			}
		}
		return $this->arrayedResult;
	}

	// Returns last insert ID
	function LastInsertID(){
		return mysql_insert_id();
	}

	// Return number of rows
	function CountRows($from, $where=''){
		$result = $this->Select($from, $where, '', '', false, 'AND','count(*)');
		return $result["count(*)"];
	}

	// Closes the connections
	function CloseConnection(){
		if($this->databaseLink){
			mysql_close($this->databaseLink);
		}
	}
}
?>
