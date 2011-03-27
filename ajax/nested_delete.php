<?php
include('../include.php');
$array = array_ajax();

//expecting item_id and table
if ($children = db_array('SELECT id FROM ' . $array['table'] . ' WHERE ' . $array['nesting_column'] . ' = ' . $array['item_id'])) {
	//has children, get parent
	if (!$parent_id = db_grab('SELECT ' . $array['nesting_column'] . ' FROM ' . $array['table'] . ' WHERE id = ' . $array['item_id'])) $parent_id = 'NULL';
	db_query('UPDATE ' . $array['table'] . ' SET ' . $array['nesting_column'] . ' = ' . $parent_id . ' WHERE id IN (' . implode(',', $children) . ')');
}

db_delete($array['table'], $array['item_id']);

nestedTreeRebuild($array['table']);

echo 'ok deleted';