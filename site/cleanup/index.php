<?php
include('../../include.php');

echo drawFirst(draw_link('../', 'Site Settings') . ' &gt; Cleanup');

db_query('DELETE FROM app_objects WHERE is_active <> 1');
db_query('DELETE FROM app_fields WHERE is_active <> 1');

$user_tables = db_array('SELECT table_name FROM app_objects');

//find out which tables we don't need
$tables = array_diff(
		db_tables(),
		$user_tables,
		db_array('SELECT field_name FROM app_fields WHERE type = "checkboxes"'),
		array_keys($schema)
	);

//and drop them
foreach ($tables as $t) {
	db_table_drop($t);
	echo 'dropped table ' . $t . '<br/>';
}

//find out which columns we don't need and drop them
foreach ($user_tables as $t) {
	$columns = array_diff(
			db_columns($t, true, false),
			db_array('SELECT f.field_name FROM app_fields f JOIN app_objects o ON f.object_id = o.id WHERE o.table_name = "' . $t . '" AND f.type <> "checkboxes"')
		);
	foreach ($columns as $c) {
		db_column_drop($t, $c);
		echo 'dropped column ' . $c . ' from table ' . $t . '<br/>';
	}
}

//see if we can rename any tables
$objects = db_query('SELECT title, table_name FROM app_objects');
while ($o = db_fetch($objects)) {
	$target = format_text_code('user_' . $o['title']);
	if (($o['table_name'] != $target) && !db_table_exists($target)) {
		db_table_rename($o['table_name'], $target);
		echo 'renamed ' . $o['table_name'] . ' to ' . $target; . '<br/>';
	}
}

//see if we can rename any columns
//todo alter table spacetime change wind_direction_id direction_id tinyint(3) unsigned;


echo drawLast();
?>