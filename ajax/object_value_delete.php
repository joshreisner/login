<?php
include('../include.php');
$array = array_ajax();
$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $array['object_id']);
if (db_grab('SELECT is_active FROM ' . $table . ' WHERE id = ' . $array['id'])) {
	db_delete($table, $array['id']);
} else {
	db_undelete($table, $array['id']);
}
echo (($_SESSION['show_deleted']) ? 'Hide ' : 'Show ') . db_grab('SELECT COUNT(*) FROM ' . $table . ' WHERE is_active = 0') . ' Deleted';
?>