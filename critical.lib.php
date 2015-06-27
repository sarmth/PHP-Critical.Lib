<?php
/**
*	
*	Critical PHP
*	
*	Critical PHP is a public, open source PHP library created by Critical Web Solutions as a solution to speeding up the development of PHP Applictions
*	by using various different programming methods such as daiseychaining, and Object Oriented PHP.
*	
*/
class Common {
	public static $username;
	public static $id;
	public static $active = false;
	
	public function __construct() {
		if(isset($_SESSION['loggedIn'])) {
			Common::$username = $_SESSION['user'];
			Common::$id = $_SESSION['userid'];
			Common::$active = true;
		}
	}
	public static function error($additionals) {
		print_r(var_dump(debug_backtrace()) . var_dump(error_get_last()) . var_dump($additionals));
	}
	
}
class Template {
	public static $templateComponents;
	public static function addComponent($tag, $value) {
		if (isset(Template::$templateComponents[$tag])) 
			Template::$templateComponents[$tag] .= $value;
		else
			Template::$templateComponents[$tag] = $value;
	}
	public static function output($file = null) {
		if(!empty($file)) {
			if (file_exists($file)) {
				$f = fopen($file, 'R');
				$fconts = fread($f, filesize($file));
			}
		} else {
			//	Use default file.
			$file = _ROOT . "templates/default.tpl";
			if (file_exists($file)) {
				$f = fopen($file, 'R');
				$fconts = fread($f, filesize($file));
			}
		}
		foreach (Template::$templateComponents AS $tag=>$value) {
			$fconts = str_replace($tag, $value, $fconts);
		}
		echo $fconts;	//	Output Data
	}
}
class Database {
/**
*	This class handles all database functions.
*/
	public $databaseName;
	public $databaseUser;
	public $databasePass;
	public $databaseHost;
	private $connection;
	public $quickQueries;
	public $val;
	private $errorLog;
	private $debugLog;
	private $field;
	
	public function __get($name) {
		if(isset($this->field[$name]))
			return $this->field[$name];
		else
			return false;
		//throw new Exception("$name does not exists");
	}
	public function __construct($parameters = null) {
		if (isset($parameters)) {
			//	We have construct parameters, let's use them.
			if (isset($parameters['connect'])) {
				$this->connect($parameters['connect']['user'], $parameters['connect']['pass'], $parameters['connect']['db'], $parameters['connect']['host']);	//	Connect to database.
			}
			if (isset($parameters['quickQueries'])) {
				foreach($parameters['quickQueries'] AS $query) {
					$this->quickQueries[$query['name']] = $query['sql'];
				}
			}
		}
		
	}
	public function connect($user, $pass, $db, $host="localhost", $port="3306", $socket=null) {
		$this->databaseName = $db;
		$this->databaseUser = $user;
		$this->databasePass = $pass;
		$this->databaseHost = $host;
		$this->connection = mysqli_connect($host, $user, $pass, $db, $port, $socket) OR Common::error(mysqli_connect_error($this->connection));
		$this->debugLog .= "Successfully connected to MySQL.";
		return $this;
	}
	public function string($value) {
		return mysqli_real_escape_string($this->connection, htmlentities(strip_tags($value)));// protected string.
	}
	public function query($query, $vars = null) {
		if (is_array($vars)) {
			//Loop Through and change index references with the index value.
		} else {
			//Replace first index reference with value of $var;
			$query = str_ireplace("{0}", $var, $query);
		}
		$response = mysqli_query($this->connection, $query) or Common::error(mysqli_error($this->connection));
		if($response) {
			//All Good.
			$this->debugLog .= "\r\nQuery " . $query . " executed successfully!";
		} else {
			//Error;
			$this->errorLog .= "\r\n" . mysqli_error($this->connection);	//	Add Log Entry, then continue returning object.
		}
		
		return $this;
	}
	public function row($table, $joins, $where, $limit=null) {
		$w = "";	//	Declare empty $w as String.
		$j = ""; //	Declare empty $j as String.
		
		if (!empty($table)) {
			if (!empty($joins)) {
				//	JOIN IN TABLES.
			}
			if (!empty($where)) {
				foreach($where AS $con) {
					if ($con['type'] == "and") {
						//	Type "AND" Condition.
						$w .= (!empty($w)?" AND ":null) . $con['column'] . $con['logic'] . $this->string($con['value']);
					} else {
						//	Type "OR" Condition.
						$w .= (!empty($w)?" OR ":null) . $con['column'] . $con['logic'] . $this->string($con['value']);
					}
				}
			}
		}
		//	Actual Query;
		$query = mysqli_query($this->connection, "SELECT * FROM `" . $this->string($table) . "` {$j} {$w}");
		return mysqli_fetch_array($query);
	}
	public function _get($expression, $value, $col = null) {
		//	Perform a minimal Simplistic MySQL Query with Return values added to the current object.
		//	This function works in conjunction with Daiseychaining.
		//	Clear existing gotten data.
		$this->field="";
		$e = explode(",", $expression);
		$multiple = false;
		if(count($e)>1)
			$multiple = true;
		
		if (stripos($expression,".") === false) {
			$multiple = true;
				$expression .= ".*";
			}
		$e1 = array();
		foreach ($e as $ee) {
			$e1[] = explode(".", $ee);
		}

		
		if (is_array($value)) {
			$va = "";
			foreach($value AS $v) {
				$va .= (!empty($va) ? $v['type'] : null) . "";
			}
		} else {
			//	DB DATA QUERY BASED ON ID.
			$value = $this->string($value);	//	Add inherant protection.
			$query = "SELECT {$expression} FROM " . $e1[0][0] . " WHERE `" . (!empty($col) ? $col : 'id') . "` = '{$value}'";

			$q = mysqli_query($this->connection, $query) OR Common::error(mysqli_error($this->connection));
		}
		$data = array();
		while($row = mysqli_fetch_array($q)) {
			$data[] = $row;
		}
		//var_dump($data);
		if(count($data) > 1)
			$this->field = array("values"=>$data);
		else
			$this->field = $data[0];
		
			return $this;
	}
	public function _set($tableAndCol, $oldVal, $newVal, $id = 0) {
		$idConstruct = "";
		if (!empty($id))
			$idConstruct = "AND `id` = '" . $id . "'";
		
		$table = explode(".", $tableAndCol);
		$this->query("UPDATE " . $table[0] . " SET " . $table[1] . " = '" . $this->string($newVal) . "' WHERE " . $table[1] . " = '" . $this->string($oldVal) . "' " . $idConstruct);
		
		return $this;
	}
	public function _unset($tableAndCol, $value) {
		$table = explode(".", $tableAndCol);
		$this->query("DELETE FROM " . $table[0] . " WHERE " . $table[1] . " = " . $this->string($value));
	}
	public function insert($table, $values){
		var_dump($values);
		if (is_array($values)) {
			$vConstruct = "";
			foreach($values AS $v) {
				$vConstruct .= (empty($vConstruct) ? null : ",") . "'" . $this->string($v) . "'";	//	Add Protection to values.
			}
			var_dump($vConstruct);
			$this->query("INSERT INTO `{$table}` VALUES('',{$vConstruct});");			
		} else {
			$this->query("INSERT INTO `{$table}` VALUES('',{$values});");
		}
	}
	public function import($table, $data) {
		if (is_array($data)) {
			$vConstruct = "";
			foreach($data AS $row) {
				$r = "''";
				foreach($row as $v)
				$r .= ",'" . $this->string($v) . "'";	//	Add Protection to values.
				
				$vConstruct .= (empty($vConstruct) ? null : ",") . "(" . $r . ")";	//	Add Protection to values.
			}
			var_dump($vConstruct);
		}
	}
	public function debug() {
		echo "<hr><h2>Debug Information</h2>" . $this->debugLog . "<hr>" . $this->errorLog;
		return $this;
	}
}

?>