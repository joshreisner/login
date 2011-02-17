<?php
include('../include.php');
$array = array_ajax();
$object = db_grab('SELECT table_name, title FROM app_objects WHERE id = ' . $array['object_id']);
if (db_grab('SELECT is_active FROM ' . $object['table_name'] . ' WHERE id = ' . $array['id'])) {
	db_delete($object['table_name'], $array['id']);
} else {
	db_undelete($object['table_name'], $array['id']);
}
echo (($_SESSION['show_deleted']) ? 'Hide ' : 'Show ') . format_quantitize(db_grab('SELECT COUNT(*) FROM ' . $object['table_name'] . ' WHERE is_active = 0'), 'Deleted ' . $object['title']);
?>