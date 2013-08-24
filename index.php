<?php


include('orm.class.php');


data_base_connect('test', 'root', '');

$q = new DBQuery('users');

/*
$q->insert(array(
	'name' => 'gonza',
	'city' => 'marokko'
));
*/

$q->where(array('id:>' => 0));
$q->sort('id', -1);

/*
$q->select(function($row){
	echo '<br>';
	print_r($row);
});
*/

$r = $q->select_one('name');
print_r($r);


