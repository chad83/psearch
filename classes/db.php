<?php
 /**
 * DB connection class.
 * 
 * @author Chadi Wehbe. 
 */



class db{
	
	/**
	 * Create a database connection.
	 */
	private function dbConnect($databaseName){
		
		// A list of the database configurations, use one entry for each database connection.
		$databases = array(
			'primary' => array(
				'host'		=> 'localhost',
				'username'	=> 'root',
				'password'	=> 'featherlight',
				'dbname'	=> 'tsearchdb'
			)
		);
		
		if(!isset($databases[$databaseName])){
			die('Database name does not exist.');
		} else {
			
			$mysqli = new mysqli($databases[$databaseName]['host'], $databases[$databaseName]['username'], $databases[$databaseName]['password'], $databases[$databaseName]['dbname']);
			// Connection error (PHP 5.3.0 or greater required).
			if ($mysqli->connect_error) {
				die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
			}
			else{
				return $mysqli;
			}
		}
	}
	
	/**
	 * Executes a basic query using binding.
	 * The query should be of a single MySQL query using "?" as binding anchors.
	 * ie: q(SELECT id, name FROM cities WHERE (population < ?), array("1000"))
	 * 
	 * @param string $query the query to use, single query with optional binding anchors.
	 * @param array $parameters an array of parameters to add to replace the bindings.
	 * 		must count-match the number of anchors, will be escaped in the function.
	 * 
	 * @return array / false Results array on success, false on failure.
	 */
	protected function q($query, $parameters = array()){
		// Connect to the database.
		$dbobj = new db;
		$db = $dbobj->dbconnect('primary');
		
		$query = trim($query);
		
		// Prepare a types string for the query builder.
		$parameterTypes = '';
		foreach($parameters as $parameter){
			// Case of numeric (int or not).
			if(is_numeric($parameter)){
				// Integer.
				if(is_int($parameter)){
					$parameterTypes .= 'i';
				}
				// Double.
				else{
					$parameterTypes .= 'd';
				}
			}
			// String.
			else{
				$parameterTypes .= 's';
			}
		}
		
		// Prepend the types string to the paramteres array so it serves as the first arguments for "bind_param()".
		array_unshift($parameters, $parameterTypes);
		
		// Prepare the query.
		$preparedQuery = $db->prepare($query);
		
		// Bind the parameter variables.
		call_user_func_array(array($preparedQuery, 'bind_param'), $parameters);
		
		// Execute the query.
		$queryStatus = $preparedQuery->execute();
		
		// If this is not a SELECT statement, just return the boolean flag of success or failure.
		if(strtoupper(substr($query, 0, 6)) != 'SELECT'){
			return $queryStatus;
		}
		// In case of a select statement, prepare a return array.
		else {
		
			// ** Method to allow binding a variable number of columns.
			
			// Get the results meta data.
			$meta = $preparedQuery->result_metadata();
			// Get the data for the columns.
			$fieldsMeta = $meta->fetch_fields();
			
			// Assign variables based on the column names.
			$columnNamesVarList	= array();
			$columnNamesList	= array(); 
			foreach($fieldsMeta as $columnMeta){
				// $columnNamesVarList .= '$' . $columnMeta->name;
				$columnNamesVarList[]	= '$' . $columnMeta->name;
				$columnNamesList[]		= $columnMeta->name;
				
			}
			
			// Bind the results to the columns list as variable names.
			eval('$preparedQuery->bind_result(' . implode(',', $columnNamesVarList) . ');');
			
			// Prepare the final results array.
			$resultArr = array();
			// Loop through the rows.
			while($preparedQuery->fetch()){
				
				// Loop through the columns.
				$tmpArr = array();
				foreach($columnNamesList as $columnName){
					$tmpArr[$columnName] = $$columnName;
				}
				
				// Save the row to the results array.
				$resultArr[] = $tmpArr;
			}
			
			// Close the open instances.
			$preparedQuery->close();
			$db->close();
			
			return $resultArr;
		}
	}
	
}



?>
 