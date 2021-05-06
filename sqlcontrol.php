<?php
if(count(get_included_files()) ==1) exit("Direct access not permitted.");

class sqlControl {
// will send an ARRAY, with Key-Value pairs for all applicable things we need, which the database will parse
// then send it to SQL. no direct sql should be written anywhere in the code, only created, referenced, and released.
	private $user = "username";
	private $pass = "password";
	private $dbh;
	private $STH;

	function __construct(){
		// load the SQL system
		try {  
			$this->dbh = new PDO('mysql:host=host;dbname=CityGen', $this->user, $this->pass);
		}  
		catch(PDOException $e) {  
			echo $e->getMessage();  
		} 
	}
	
	public function sqlCommand($stmt, $value){// $type='none'){ <-- for when we need to add REPLACE functionality.
		// this will be used for general constructs. note that there should be almost no sql
		// statements that are outside of classes, mostly everything should be static calls within functions.
	
		foreach($value as &$val){
			$val = $this->VarFilter($val);
		}
		// unset the last variable
		unset($val);
		
		// all values now filtered for special characters. we'll run a second filter before release.
		// this will allow for simple comprehension and release for all information.
	
		$this->STH = $this->dbh->prepare($stmt);
	
		$this->STH->execute($value);
	
	}
	
	private function VarFilter($var){
		return filter_var($var, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	
	}
	
	public function lastInsert(){
		return $this->dbh->lastInsertId();
	}
	
	public function returnResults(){
		// if there is results, (depending of case) it will find them.
		return $this->STH->fetch();	
	}
	
	public function returnAllResults(){
		// if there is results, (depending of case) it will find them.
		return $this->STH->fetchAll();	
	}
	
	
	function __destruct(){
		// destroy the SQL system
		$this->dbh = '';
	}

}
?>