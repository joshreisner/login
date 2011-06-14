<?php
include('../include.php');
$array = array_ajax();
$nested = false;

//determine whether object is nested 
$object = db_grab('SELECT f.object_id group_by_object_id, f.field_name nesting_column FROM app_objects o JOIN app_fields f ON o.group_by_field = f.id WHERE o.id = 1');
if ($object['group_by_object_id'] == $array['object_id']) {
	$nested = true;
	if ($children = db_array('SELECT id FROM ' . $array['table_name'] . ' WHERE ' . $object['nesting_column'] . ' = ' . $array['id'])) {
		//has children, get parent
		if (!$parent_id = db_grab('SELECT ' . $object['nesting_column'] . ' FROM ' . $array['table_name'] . ' WHERE id = ' . $array['id'])) $parent_id = 'NULL';
		db_query('UPDATE ' . $array['table_name'] . ' SET ' . $object['nesting_column'] . ' = ' . $parent_id . ' WHERE id IN (' . implode(',', $children) . ')');
	}
}

//delete or undelete as the case may be
if (db_grab('SELECT is_active FROM ' . $array['table_name'] . ' WHERE id = ' . $array['id'])) {
	db_delete($array['table_name'], $array['id']);
} else {
	db_undelete($array['table_name'], $array['id']);
}

//rebuild tree if nested
if ($nested) nestedTreeRebuild($array['table_name']);

//output whole new list
echo drawObjectList($array['object_id'], $array['from_type'], $array['from_id'], true);	