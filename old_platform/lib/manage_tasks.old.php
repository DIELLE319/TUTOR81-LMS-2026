<?php
require_once 'class_task.php';
$task_obj = new Task();

$op_type = $_REQUEST['op_type'];

if ($op_type == "add_task"){
	if($_REQUEST['short_desc'] != "" && $_REQUEST['company_id'] > 0){	
		$res = $task_obj->addTask($_REQUEST['short_desc'], $_REQUEST['long_desc'], $_REQUEST['company_id']);
	} else {
		$res = false;
	} 
} elseif ($op_type == "delete_task"){
		$res = $task_obj->deletTask($_REQUEST['id_task']);
}

echo $res;