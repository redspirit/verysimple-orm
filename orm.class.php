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
	private function escaped($val){
		return "'".mysql_real_escape_string($val)."'";
	}
	private function escapes($val){
		return mysql_real_escape_string($val);
	}
	
	function __construct($tbl = ''){
		if(!empty($tbl)) $this->table($tbl);
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
	
	private function make_select(){
		$sql = 'SELECT ';
	
		// SELECT
		foreach($this->fields as $key => $val){
			if(is_string($key)){
				$sql .= $this->escapes($key).' as '.$this->escaped($val).', ';
			} else {
				$sql .= $this->escapes($val).', ';			
			}
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
   				$sql .= " {$this->escapes($field)} $operat {$this->escaped($val)} AND";    
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
		if($this->limit1 > 0){
			$sql .= ' LIMIT '.$this->limit2.', '.$this->limit1.' ';		
		}

		return $sql;
	}
	
	public function select($fld = '*', $iterator = ''){
		if(is_callable($fld)){ // на случай, если передается только функция для итератора
			$iterator = $fld;
			$fld = '*';
		}
		if(is_string($fld)){
			$this->fields = array($fld);
		}
		if(is_array($fld)){
			$this->fields = $fld;
		}		
	
		$sql = $this->make_select();
				
		//echo $sql;
				
		$this->query = mysql_query($sql);
		if($this->query){			
			if(is_callable($iterator)){		
				while($row = mysql_fetch_assoc($this->query)){
					$iterator($row);
				}
			}
			return $this->query;
		} else 
			return false;
	
	}
	
	public function select_one($fld = '*'){
		if(is_string($fld)){
			$this->fields = array($fld);
		}
		if(is_array($fld)){
			$this->fields = $fld;
		}		
	
		$this->limit1 = 1;
		$sql = $this->make_select();
	
		//echo $sql;
	
		$this->query = mysql_query($sql);
	
		if($this->query){
			$result = mysql_fetch_assoc($this->query);
			if(count($result) == 1) $result = reset($result);
			return $result;
		} else 
			return false;
	}
	
	public function insert($data){
		$sql = 'INSERT INTO '.$this->escape($this->tables[0]);
		if(is_array($data)){
			$fields = array();
			$values = array();
			foreach($data as $key => $val){
				$fields[] = $this->escape($key);
				$values[] = $this->escaped($val);
			}
			
			$sql .= ' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
		
			echo $sql;
			
			return mysql_query($sql);
		} else return false;
	
	}
	
	public function update($data){
		$sql = 'UPDATE ';
		
		foreach($this->tables as $tbl){
			$sql .= $this->escape($tbl);
			break;
		}

		$sql .= ' SET ';
		
		if(is_array($data)){
			foreach($data as $key => $val){
				$sql .= $this->escape($key).' = '.$this->escaped($val).', ';
			}
			$sql = substr($sql, 0, -2);
			
			
			//WHERE
			if(count($this->wheres)>0){
				$sql .= ' WHERE ';
				foreach($this->wheres as $key => $val){
					$qs = explode(':', $key);
					$field = $qs[0];
					$operat = isset($qs[1]) ? $qs[1] : '=';
					$sql .= " {$this->escapes($field)} $operat {$this->escaped($val)} AND";    
				}
				$sql = substr($sql, 0, -3);			
			}			
			
			// LIMIT
			if($this->limit1 > 0){
				$sql .= ' LIMIT '.$this->limit2.', '.$this->limit1.' ';		
			}			
			
			//echo $sql;
			
			return mysql_query($sql);
		} else return false;

	}

	public function delete(){
		$sql = 'DELETE FROM ';
		
		foreach($this->tables as $tbl){
			$sql .= $this->escape($tbl).', ';
		}
		$sql = substr($sql, 0, -2);	
	
		if(count($this->wheres)>0){
			$sql .= ' WHERE ';
		    foreach($this->wheres as $key => $val){
 		    	$qs = explode(':', $key);
 		    	$field = $qs[0];
   				$operat = isset($qs[1]) ? $qs[1] : '=';
   				$sql .= " {$this->escapes($field)} $operat {$this->escaped($val)} AND";    
   			}
			$sql = substr($sql, 0, -3);			
		}
		
		return mysql_query($sql);
	}
	
	public function query($sql){
		return mysql_query($sql);
	}


	
	
}	
	
	
	
	
	
	
?>