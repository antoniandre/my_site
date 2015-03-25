<?php
/**
 * Design pattern: singleton
 */
include __DIR__.'/database.entity.php';
include __DIR__.'/database.query.where.php';

Class Query extends DatabaseEntity
{
	private static $instance= null;
	private $mysqli;
	private $secureInternalData;
	private $currentQuery;
	private $currentQueryType;
	private $table;
	private $where;// Where object handling the current query WHERE clause.
	private $orderBy;
	private $limit;
	private $info;// After-run information on query.
	private $lastQuery;
	private $lastQueryType;
	private $error;

	/**
	 * Class constructor.
	 */
	protected function __construct($mysqli)
	{
		parent::__construct();
		$this->mysqli= $mysqli;
		$this->secureInternalData= false;
		$this->currentQuery= null;
		$this->currentQueryType= null;
		$this->table= null;
		$this->where= null;
		$this->orderBy= '';
		$this->limit= '';
		$this->info= null;
		$this->lastQuery= null;
		$this->lastQueryType= null;
		$this->error= false;
	}

	/**
	 * Get the only instance of this class
	 *
	 * @return the only instance of this class
	 */
	public static function getInstance($mysqli= null)
	{
		if (!isset(self::$instance)) self::$instance = new self($mysqli);
		return self::$instance;
	}

	/**
	 * Intercept any call to a method to redispatch to the proper method.
	 * This is used to allow a call to a reserved-keyword-method like or() and and()
	 * while you can't define this method.
	 *
	 * @param  string $method: the name of the method that was initially called
	 * @param  array $args: the parameters to provide to the method that was initially called
	 * @return The current Query instance.
	 */
    function __call($method, $args)
    {
    	$return= null;
        switch ($method)
        {
        	case 'as':
        	case 'count':
        		$method= "_$method";
	        	break;
        	default:
	        	break;
        }
		if (!method_exists($this, $method)) Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__).'(): Method "'.__CLASS__."::$method()\" does not exist.");
        else return call_user_func_array(array(self::$instance, $method), $args);
        return $this;
    }

	/**
	 * Get information on the last run query
	 *
	 * @return StdClass object containing miscelaneous information.
	 */
	public function info()
	{
		return $this->info;
	}

	/**
	 * Set a table to work on for the query.
	 *
	 * @param string $table: the table name.
	 */
	private function setTable($table)
	{
		// Handles simple alphanumeric, backquoted or not
		if (!preg_match('~^`?(\w+)`?$~i', $table, $matches)) return $this->abort(ucfirst(__FUNCTION__)."(): The field syntax \"$field\" is not recognized.");
		else
		{
			$this->table= $matches[1];
			$this->begin();
		}
	}

	/**
	 * Insert a new row in database table. Set an error in case of failure.
	 * If a duplicate entry (same primary key) is found, make an update of this entry with 'REPLACE INTO'
	 *
	 * @param string $tableName: the table name to insert in
	 * @param array $pairs: array of pairs of 'col_name' => 'New value'.
	 * @param boolean $replaceIfExists: use the 'REPLACE' keyword if set to true, so the row is overwritten if it exists.
	 * @param boolean $secureInternalData: use to also secure data coming from the PHP. Since all user vars are already secured ($posts, $gets, etc.)
	 *                                     Do not over secure otherwise the string will get double slashes that won't go off when in db.
	 * @return The current Query instance.
	 * Example of use:
	 * $q->insert('misc',
	 *            array('id' => 3,
	 *                  'key' => 'key3',
	 *                  'value' => 'A third value with date, line breaks: '."\n\n".date("Y-m-d H:i:s")."\n\nn other fuckin' stuff like \"àccéñts\"..."),
	 *            true,
	 *            true)//true since we have a simple quote. In PHP side made code this should never happen...
	 *   ->run()
	 */
	public function insert($tableName, $pairs, $replaceIfExists= false, $secureInternalData= false)
	{
		$this->currentQueryType= $replaceIfExists? 'replace' : 'insert';
		$this->setTable($tableName);
		$this->checkPairs($pairs, $secureInternalData);
		return $this;
	}

	/**
	 * Update the given fields in database, set an error in case of failure.
	 *
	 * @param string $tableName: the table name to update in
	 * @param array $pairs: array of pairs of 'col_name' => 'New value'.
	 *		  If the value must be complex like column name or concat, etc. make an array around it. E.g.
	 *		  $db->update('table_name',
	 *					  array('column_name1' => 'updated_value1',
	 *						    'column_name2' => array('CONCAT', array('The `column_name1` value is: ', array('column', 'column_name1')))),
	 *					  array('`column_name1`="value1"'));
	 *
	 * @param boolean $secureInternalData: use to also secure data coming from the PHP. Since all user vars are already secured ($posts, $gets, etc.)
	 *                                     Do not over secure otherwise the string will get double slashes that won't go off when in db.
	 * @return The current Query instance.
	 *
	 * Example of use:
	 * $q->update('misc',
	 *            array('value' => 'a very longer second value with date: '.date("Y-m-d H:i:s")))
	 *   ->where('`key`="test2"')
	 *   ->run();
	 */
	public function update($tableName, $pairs, $secureInternalData= false)
	{
		$this->currentQueryType= __FUNCTION__;
		$this->setTable($table);
		$this->checkPairs($pairs, $secureInternalData);
		return $this;
	}

	/**
	 * Delete a row of data from database table
	 *
	 * @param string $tableName: the table name to delete from
	 * @return The current Query instance.
	 *
	 * Example of use:
	 * $q->delete('misc')
	 *   ->where('`id`=5')
	 *   ->run();
	 */
	public function delete($table)
	{
		$this->currentQueryType= __FUNCTION__;
		$this->setTable($table);

		return $this;
	}

	/**
	 * Create a select query.
	 *
	 * @param string $tableName: the table name to delete from.
	 * @return string secured field string
	 *
	 * Example of use:
	 * $q->select('misc', '*')
	 *   ->where('`id`=3')
	 *   ->run();
	 */
	public function select($table, $fields, $secureInternalData= false)
	{
		$this->currentQueryType= __FUNCTION__;
		$this->setTable($table);

		if ($fields === '*') $this->tempPieces= array('*');
		else $this->tempPieces[]= implode(', ', $this->gatherArgs($fields));

		return $this;
	}

	/**
	 * Check the key-value pairs provided in insert and update functions.
	 *
	 * @param  array $pairs: the key-value pairs to analyse.
	 * @param  boolean $secureInternalData: whether or not should escape quotes.
	 *                                      Should never since it is done in secure_vars() for all user vars $posts, $gets, etc.
	 * @return boolean: true if success, false if failure.
	 */
	private function checkPairs($pairs, $secureInternalData)
	{
		$this->secureInternalData= $secureInternalData;
		// Check the key-value pairs to update in db from the $pairs param.
		if (!is_array($pairs)) return $this->abortQuery('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): The values to $this->currentQueryType must be provided in an indexed array. E.g. array('col_name' => 'New value').");
		elseif (!count($pairs)) return $this->abortQuery('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): Please set at least one value to $this->currentQueryType.");
		else
		{
			// Store in temp array while looping in case of failure (don't store uncomplete array in class attribute).
			$cleanedPairs= array();

			foreach ($pairs as $dbColName => $value)
			{
				if (is_numeric($dbColName) && in_array($this->currentQueryType, array('update', 'insert', 'replace'))) return $this->abortQuery('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): Missing column name for the pair: $dbColName => $value.");
				elseif (is_numeric($value) || is_bool($value)) $cleanedPairs[$dbColName]= $value;
				elseif (is_string($value))
				{
					if (!$value) $cleanedPairs[$dbColName]= "'$value'";// If null write empty string instead of nothing.
					elseif ($value{0} == '`' && $value{strlen($value)-1} == '`') $cleanedPairs[$dbColName]= $value;
					else $cleanedPairs[$dbColName]= "'".($this->secureInternalData? $this->escape($value) : $value)."'";
				}
				elseif(is_array($value))
				{
					$complexValueObj= $this->checkComplexValue($dbColName, $value);
					if ($complexValueObj->error) return false;// Prevent the query to be launched afterwards.
					$value= $this->checkComplexValue($dbColName, $value);
					$cleanedPairs[$dbColName]= $value;
				}
				elseif (!$value) $cleanedPairs[$dbColName]= 'null';
			}
			// Everything went fine, store in class attributes.
			$this->tempPieces= array_merge($this->tempPieces, $cleanedPairs);
		}
		$this->secureInternalData= false;// Set back to no extra security
	}

	/**
	 * Check the fields we want to select.
	 *
	 * @param  string $field
	 * @return string secured field string
	 */
	protected function checkField($field)
	{
		return parent::{__FUNCTION__}(func_get_arg(0));
	}

	/**
	 * Assemble the mysqli query and stores it in $this->currentQuery.
	 * Also cleanup current instance attributes.
	 *
	 * @param  array $args: the arguments to concat.
	 * @return The current Query instance.
	 */
	private function assemble()
	{
		// if (!$this->currentQuery) return $this->abort(ucfirst(__FUNCTION__).'(): There is currently no query to assemble.');
		/*else*/if (!$this->where && in_array($this->currentQueryType, array('update', 'delete')))
		{
			return $this->abort(ucfirst(__FUNCTION__)."(): Omitted WHERE clause.\n"
								."You may have forgotten the WHERE clause for the $this->currentQueryType query.\n"
								."If you intend to impact all the table rows, please set a WHERE clause to 1: "
								."\"\$query->where(1);\".");
		}
		else
		{
			$queryType= strtoupper($this->currentQueryType);
			switch ($queryType)
			{
				case 'INSERT':
				case 'REPLACE':
					$keys= '`'.implode('`, `', array_keys($this->tempPieces)).'`';
					$values= implode(', ', $this->tempPieces);
					$this->currentQuery= "$queryType INTO `$this->table` ($keys) VALUES ($values)";
					break;
				case 'UPDATE':
					foreach ($this->tempPieces as $key => $value) $pairs[]= "`$key`= $value";
					$pairs= implode(', ', $pairs);
					$this->currentQuery= "$queryType `$this->table` SET $pairs";
					break;
				case 'DELETE':
					$this->currentQuery= "$queryType FROM `$this->table`";
					break;
				case 'SELECT':
					$fields= implode(', ', $this->tempPieces);
					$this->currentQuery= "$queryType $fields FROM `$this->table`";
					break;
				default:
					return $this->abort(ucfirst(__FUNCTION__).'(): This case is not developed!');
					break;
			}

			// Add the WHERE clause to the query if any and reset class attributes to null.
			if ($this->where!== null) $this->currentQuery.= " WHERE ({$this->where->get()})";
			$this->currentQuery.= $this->orderBy.$this->limit;
			$this->table= null;
			$this->tempPieces= null;
			if ($this->where instanceof Where)
			{
				$this->where->kill();
				$this->where= null;
			}
		}
		return $this;
	}

	/**
	 * Run the query and update the $this->info object to store multiple after-query information like:
	 * Number of affected rows, number of returned rows, inserted id, error.
	 *
	 * @todo ?? $this->mysqli->multi_query($query)
	 * @return The current Query instance.
	 */
	public function run()
	{
		$info= new StdClass();
		$info->error= true;
		$info->errorMessage= '';
		$info->insertId= null;
		$info->affectedRows= 0;
		$info->numRows= null;
		$info->mysqliResult= null;

		if ($this->assemble())
		{
			$result= $this->mysqli->query($this->currentQuery);
			if (!$result) $info->errorMessage= $this->setError();
			else
			{
				switch ($this->currentQueryType)
				{
				 	case 'insert':
				 	case 'replace':
				 		$info->insertId= $this->mysqli->insert_id;
				 		break;
				 	case 'select':
				 		$info->numRows= $result->num_rows;
				 		break;
				 	default:
						Debug::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__).'(): This case is not developed!');
				 		break;
				}
				$info->mysqliResult= $result;
				$info->error= false;
				$info->affectedRows= $this->mysqli->affected_rows;
			}
		}

		// The query is now finished: set it as last query, clear current query and class attributes.
		$this->lastQuery= $this->currentQuery;
		$this->lastQueryType= $this->currentQueryType;
		$this->info= $info;
		$this->end();

		return $this;
	}

	/**
	 * Aborts the query when an error occured and sets an error message.
	 *
	 * @param  string $message: the message to forward to the Error class.
	 * @return The current Query instance.
	 */
	private function abort($message)
	{
		$this->error= true;
		Error::getInstance()->add('Mysqli '.__CLASS__."::$message\nQuery was aborted.", null, true);
		return $this->end();
	}

	/**
	 * Perform tasks when beginning a query.
	 */
	private function begin()
	{
		$this->info= null;
	}

	/**
	 * Ends a query and reset current instance attributes.
	 *
	 * @return The current Query instance.
	 */
	private function end()
	{
		$this->secureInternalData= false;
		$this->currentQuery= null;
		$this->currentQueryType= null;
		$this->table= null;
		$this->tempPieces= array();
		$this->orderBy= '';
		$this->limit= '';
		// Comment because we need the mysqli result after run() when loadObjects() and other after-run functions.
		// $this->info->mysqliResult= null;
		return $this;
	}

	/**
	 * Gather function args: if args are instanceOf Query, they are treated previously and
	 * appended to the $this->tempPieces attribute. So This function get the treated args from this array
	 * and return all the params in the right order.
	 * If a param is non-database-entity-object (Query or Where) it is treated as a simple string.
	 *
	 * Ex:
	 * $q->select('pages', [
     *                         $q->concat($q->col('id'), $q->col('page'), ': ', $q->col('url_en'))->as('c'),
     *                         'value',
     *                         $q->count('*')->as('cd')
     *                     ]);
     *
     * @param  array $args: the arguments raw array to treat.
     * @return array: All the args in the right order.
     */
	protected function gatherArgs($args)
	{
		return parent::gatherArgs($args);
	}

	/**
	 * Prepares a mysql AS command to rename a result and stores it in the $this->tempPieces.
	 *
	 * @param  string $string: what you want to count.
	 * @return The current Query instance.
	 */
	public function _as($alias)
	{
		$alias= $this->checkField($alias);

		$currIndex= count($this->tempPieces)-1;
		if ($currIndex<0) return $this->abort(ucfirst(__FUNCTION__).'(): You are trying to add an alias to nothing, no field is provided.');
		else $this->tempPieces[$currIndex].= " AS $alias";
		return $this;
	}

	/**
	 * Secure the mysqli CONCAT command with the given arguments.
	 *
	 * @param  array func_get_args(): the arguments to concat.
	 * @return The current Query instance.
	 */
	public function concat()
	{
		// PHP5.6+
		// parent::{__FUNCTION__}(...func_get_args());
		// PHP5.5-
		return call_user_func_array(['parent', __FUNCTION__], func_get_args());
	}

	/**
	 * Prepares a mysql COUNT command and stores it in the $this->tempPieces.
	 *
	 * @param  string $string: what you want to count.
	 * @return The current Query instance.
	 */
	public function _count($string)
	{
		return parent::{__FUNCTION__}(func_get_arg(0));
	}

	/**
	 * Tells the query this word is not a string but a column name.
	 *
	 * @param string $column: the column name.
	 * @return The current Query instance.
	 */
	public function col($column)
	{
		return parent::{__FUNCTION__}(func_get_arg(0));
	}

	/**/
	protected function lower($string)
	{
		return parent::{__FUNCTION__}(func_get_arg(0));
	}

	/**/
	protected function upper($string)
	{
		return parent::{__FUNCTION__}(func_get_arg(0));
	}

	/**/
	public function orderBy($column, $direction= 'ASC')
	{
		$direction= in_array($direction, ['ASC', 'DESC'])? $direction : 'ASC';
		$this->orderBy= " ORDER BY `$column` $direction";
		return $this;
	}

	/**/
	public function limit()
	{
		if (func_num_args()== 2) list($from, $num)= func_get_args();
		elseif (func_num_args()== 1) list($from, $num)= [0, func_get_arg(0)];
		$this->limit= " LIMIT $from, $num";
		return $this;
	}

/*TODO:
  ALTER TABLE `articles`
  DROP `title_en`,
  DROP `title_fr`;*/


	/**
	 * Postpone the query to the next page load: save it in the database table `postponed_queries`.
	 *
	 */
	public function postpone()
	{
		if (!$this->currentQuery) Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__).'(): There is currently no query to postpone.');
		elseif (!$this->currentQueryType== 'select') Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__).'(): You can\'t postpone a select query.');
		elseif (!$this->where && in_array($this->currentQueryType, array('update', 'delete')))
		{
			Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): omitted WHERE clause.\n"
									 ."You may have forgotten the WHERE clause for the $this->currentQueryType query.\n"
									 ."If you intend to impact all the table rows, please set a WHERE clause to 1: "
									 ."\"\$query->where(1);\".");
		}
		else
		{
			$this->insert('postponed_queries',
						  array('query' => $this->currentQuery));
			$this->currentQuery= null;
			$this->currentQueryType= null;
		}
	}

	/**
	 * Run postponed queries.
	 *
	 * @todo Retrieve each postponed query from the database table `postponed_queries`
	 */
	public function runPostponed()
	{
		$this->select('postponed_queries',
					  array('*'));
		$this->run();

		foreach ($queries as $query)
		{
			$this->currentQuery= $query;
			$this->currentQueryType= null;
			$this->run();
		}

		$this->currentQuery= null;
		$this->currentQueryType= null;
	}

	/**
	 * Create the WHERE clause instance
	 *
	 * @param string $condition: the first WHERE clause condition
	 */
	public function where()
	{
		$this->where= Where::getInstance(func_get_args());
		return $this->where;
	}

	/**
	 * Proceed to real_escape_string()
	 *
	 * @param the string to escape
	 * @return the escaped string
	 */
	public function escape($string)
	{
		if (is_array($string) || is_object($string))
		{
			Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__).'(): Expecting parameter to be a string.');
			return $string;
		}
		return $this->mysqli->real_escape_string($string);
	}

	/**
	 * Check complex value given in an array form
	 *
	 * @param string $key
	 * @param array $value
	 * @return a {error:int, value:'secured_value'} object containing the secured string value
	 */
	private function checkComplexValue($key, $value)
	{
		$error= 0;// Initiate with no error

		switch(strtolower($value[0]))
		{
		 	case 'increment':
		 		if (!isset($keys[$i]))
		 		{
		 			Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__).'(): you can\'t increment an unknown column.');
		 			$error= 1;
		 		}
		 		else $value= "`{$keys[$i]}`+{$value[1]}";
		 		break;
		 	case 'column':
		 		if (is_string($value[1]))// This is the column name.
		 		{
		 			$value= "`{$value[1]}`";
		 		}
		 		else
		 		{
		 			Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): the column name must be a string not \"$value[1]\".");
		 			$error= 1;
				}
		 		break;
		 	case 'concat':
		 		if (is_array($value[1]))// This is the array of values to concatenate.
		 		{
		 			foreach ($value[1] as $k => $v)
		 			{
		 				if (is_array($v) && $v[0]== 'column')
		 				{
					 		if (is_string($value[1]))// This is the column name.
					 		{
					 			$value[1][$k]= "`{$v[1]}`";
					 		}
					 		else
					 		{
								Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): the column name must be a string not \"$v[1]\".");
								$error= 1;
								break 2;
					 		}
					 	}
		 				else $value[1][$k]= "'".($this->secureInternalData? $this->escape($v) : $v)."'";
		 			}
		 			$value= 'CONCAT('.implode(',', $value[1]).')';
		 		}
		 		break;
		 	case 'count':
		 		if (is_array($value[1])) $value= "COUNT(`".implode("`,`", $value[1])."`)";
		 		break;
		 	default:
		 		Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): This case does not exist: ".strtolower($value[0]).'. Given array was: '.print_r($value, true));
		 		$error= 1;
		 		break;
		}

		$return= new StdClass();
		$return->error= $error;
		$return->value= $value;
		return $return;
	}

	/**
	 * Check the last inserted content in DB and compare with a concatenated string of all the fields to check.
	 *
	 * @param string $table: the table name to look into.
	 * @param array $fields: the DB cols to look into for comparison.
	 * @param string $compareString: a concatenation of all the fields.
	 * @return boolean true if OK, false if insert has previously been done.
	 */
	public function checkLastInsert($table, $fields= [], $compareString)
	{
		// PHP 5.5- -- OLD WAY.
		$concat = call_user_func_array([$this, 'concat'], $fields);
		$q= $this->select($table, [$concat]);
		// PHP 5.6+
		// $q= $this->select($table, [$this->concat(...$fields)]);

		$result= $q->orderBy('id', 'DESC')
		           ->limit(1)
		           ->run()
		           ->loadResult();

		return UserData::secureVars($result) === $compareString;
	}

	/**
	 * Returns The number of rows for the provided query.
	 */
	public function numRows()
	{
		$return= null;
		if (!$result= $this->mysqli->query($query)) return $this->setError('numRows', $query);
		if ($numRows= $result->num_rows) $return= $numRows;
		$result->free();
		return $return;
	}

	/**
	 * Returns the first unused id of the sequence of auto-incremented ids
	 */
	public function missingId($table)
	{
		/*$row= loadObject("SHOW KEYS FROM $table");
		return loadResult("SELECT l.$row->Column_name+1 AS start FROM $table AS l
						   LEFT OUTER JOIN $table AS r ON l.$row->Column_name+1=r.$row->Column_name
						   WHERE r.$row->Column_name IS null;");*/
	}

	/*
		This function loads the first field of the first row returned by the query.
		access	public
		return The value returned in the query or null if the query failed.
	*/
	public function loadResult()
	{
		$return= null;

		if (!$this->info) Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): there is currently no query result to exploit.");
		elseif (isset($this->info->mysqliResult))
		{
			$result= $this->info->mysqliResult;
			if ($result->num_rows && $row= $this->info->mysqliResult->fetch_array()) $return= $row[0];
			$result->free();
		}

		$this->info= null;
		return $return;
	}

	/*
		Load one object from DB
		Returns the error (string) if the query fails, or an object of the returned record.
	*/
	public function loadObject()
	{
		$return= null;

		if (!$this->info) Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): there is currently no query result to exploit.");
		elseif (isset($this->info->mysqliResult))
		{
			$result= $this->info->mysqliResult;
			if ($result->num_rows && $row= $this->info->mysqliResult->fetch_object()) $return= $row;
			$result->free();
		}

		$this->info= null;
		return $return;
	}

	/**
	 * Load a list of objects from database.
	 *
	 * @param  string $key: a key to index the array on if needed.
	 * @return array: the array of objects resulting from the query.
	 *                or null if the query fails (with an error message).
	 *
	 * Example of use:
	 * $q= $db->query();
     * $pagesFromDB= $q->select('pages', '*')->run()->loadObjects('page');
	 */
	public function loadObjects($key= '')
	{
		$return= null;

		if (!$this->info) Error::getInstance()->add('Mysqli '.__CLASS__.'::'.ucfirst(__FUNCTION__)."(): there is currently no query result to exploit.");
		elseif (isset($this->info->mysqliResult))
		{
			$result= $this->info->mysqliResult;
			if ($result->num_rows)
			{
				$array= array();
				while ($row= $result->fetch_object())
				{
				   if ($key) $array[$row->$key]= $row;
				   else $array[]= $row;
				}
				$return= $array;
			}
			$result->free();
			unset($this->info->mysqliResult);
		}

		$this->info= null;
		return $return;
	}

	/*
		Load a list of database in array.
		If $key (The field name of a primary key) is not empty then the returned array is indexed by the
		value of the database key.
		Returns the error (string) if the query fails, or an array of returned records.
	*/
	public function loadArray($key= '')
	{
		/*$return= null;
		if (!$result= $this->mysqli->query($query)) return $this->setError('Select', $query);
		if ($result->num_rows)
		{
			$array= array();
			while ($row= $result->fetch_assoc())
			{
				if ($key) $array[$row[$key]]= $row;
				else $array[]= $row;
			}
			$return= $array;
			$result->free();
		}
		return $return;*/
	}


	/**
	 * Set an error if the query failed.
	 *
	 * @param string $function: the name of the function that triggered the error
	 * @param string $query: the original query
	 */
	public function setError()
	{
		Error::getInstance()->add(__CLASS__.'::'.ucfirst($this->currentQueryType)."(): {$this->mysqli->error}\n  SQL = \"$this->currentQuery\".", 'MYSQLI');
		return $this->mysqli->error;
	}


	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone()
	{
	}
}
?>