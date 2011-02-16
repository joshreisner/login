<?php
include('../include.php');
$array = array_ajax();
$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $array['object_id']);
if (db_grab('SELECT is_active FROM ' . $table . ' WHERE id = ' . $array['id'])) {
	echo 'deleted';
	db_delete($table, $array['id']);
} else {
	echo 'undeleted';
	db_undelete($table, $array['id']);
}
?>