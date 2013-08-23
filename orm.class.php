<?php
	
function data_base_connect($db_name, $login, $pass, $host = 'localhost'){
	$db_link = mysql_connect($host, $login, $pass);
	if (!$db_link) {
		die('Connecting Error: ' . mysql_error());
	}		
	mysql_select_db($db_name) or die ("Cannot connect database!");
	mysql_query('SET character set utf8');
	return $db_link;
};


class DBQuery{
	private $tables = array();
	private $limit1 = 0;
	private $limit2 = 0;
	private $sorter = array();
	private $wheres = array();
	private $fields = array();
	private $groupby = '';
	private $query = false;	
	
	private function escape($val){
		$out = '';
		$exp = explode('.', $val);
		if(count($exp)>1){
			foreach($exp as $zn){
				$out .= '`'.mysql_real_escape_string($zn).'`.';
			};
			return substr($out, 0, -1);
		} else {
			return '`'.mysql_real_escape_string($val).'`';
		} 
	}
	
	public function table($tbl){
		if(is_string($tbl)){
			$this->tables[] = $tbl;
		}
		if(is_array($tbl)){
			$this->tables = $tbl;
		}
		return $this;
	}
	public function limit($l1, $l2 = 0){
		$this->limit1 = intval($l1);
		$this->limit2 = intval($l2);
		return $this;
	}
	public function sort($val, $dir = 1){ // 1 = asc ; -1 = desc
		if($dir != 1) $dir = -1;
		$this->sorter[$val] = $dir;
		return $this;
	}
	public function group($val){
		if($dir != 1) $dir = -1;
		$this->groupby = $val;
		return $this;
	}
	public function where($field, $val = ''){
		if(is_string($field)){
			$this->wheres[$field] = $val;
		}
		if(is_array($field)){
			$this->wheres = $field;
		}		
		return $this;
	}
	
	public function select($fld = '*'){
		if(is_string($fld)){
			$this->fields = array($fld);
		}
		if(is_array($fld)){
			$this->fields = $fld;
		}		
	
		$sql = 'SELECT ';
	
		// SELECT
		foreach($this->fields as $fld){
			$sql .= $this->escape($fld).', ';
		}
		$sql = substr($sql, 0, -2);
	
		// FROM
		$sql .= ' FROM ';
		foreach($this->tables as $tbl){
			$sql .= $this->escape($tbl).', ';
		}
		$sql = substr($sql, 0, -2);
	
		//WHERE
		if(count($this->wheres)>0){
			$sql .= ' WHERE ';
		    foreach($this->wheres as $key => $val){
 		    	$qs = explode(':', $key);
 		    	$field = $qs[0];
   				$operat = isset($qs[1]) ? $qs[1] : '=';
   				$sql .= " {$this->escape($field)} $operat '".mysql_real_escape_string($val)."' AND";    
   			}
			$sql = substr($sql, 0, -3);			
		}
	
		// SORTING
		if(count($this->sorter)>0){
			$sql .= ' ORDER BY ';
			foreach($this->sorter as $key => $val){
				$sql .= $this->escape($key).' '.($val==1 ? 'ASC' : 'DESC').', ';
			}
			$sql = substr($sql, 0, -2);
		}	
	
		// GROUPING
		if(!empty($this->groupby)){
			$sql .= ' GROUP BY '.$this->escape($this->groupby).' ';		
		}

		// LIMIT
		if($limit1 > 0){
			$sql .= ' LIMIT '.$limit1.', '.$limit2.' ';		
		}
				
		//echo $sql;
				
		$this->query = mysql_query($sql);
		if($this->query){
			return $this->query;
			return false;
		}
	
	}
	
	public function iterator($func){
		if($this->query && is_callable($func)){		
			while($row = mysql_fetch_assoc($this->query)){
				$func($row);
			}
		} else return false;
	}
	
	
}	
	
	
	
	
	
	
?>