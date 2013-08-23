<?php


include('orm.class.php');


data_base_connect('test', 'root', '');

$q = new DBQuery();
$q->table('users');
$q->where(array('id:>' => '0', 'city' => 'abakan'));
$q->sort('id', -1);
$q->select(array('id', 'name'));
$q->iterator(function($row){
	print_r($row);
});
