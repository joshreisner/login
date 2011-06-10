<?php
include('../../include.php');

echo drawFirst(draw_link('../', 'Site Settings') . CHAR_SEPARATOR . 'Cleanup');

$test = (isset($_GET['test']));
$activity = array();

if (!$test) db_query('DELETE FROM app_objects WHERE is_active <> 1');
if (!$test) db_query('DELETE FROM app_fields WHERE is_active <> 1');

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
	if (($t == 'words') || (substr($t, -9) == '_to_words')) continue;
	if (!$test) db_table_drop($t);
	$activity[] = 'dropped table ' . $t;
}

//find out which columns we don't need and drop them
foreach ($user_tables as $t) {
	if ($columns = db_columns($t, true, false)) {
		$good_cols = array();
		$cols = db_table('SELECT f.field_name, f.is_translated FROM app_fields f JOIN app_objects o ON f.object_id = o.id WHERE o.table_name = "' . $t . '" AND f.is_active = 1 AND f.type <> "checkboxes"');
		foreach ($cols as $c) {
			$good_cols[] = $c['field_name'];
			if ($c['is_translated'] && $languages) foreach (array_keys($languages) as $l) $good_cols[] = $c['field_name'] . '_' . $l;
		}
		$columns = array_diff(
			$columns,
			$good_cols
		);
		foreach ($columns as $c) {
			if ($c == 'subsequence') continue; //todo program better system field support, maybe in db_columns
			if (!$test) db_column_drop($t, $c);
			$activity[] = 'dropped column ' . $c . ' from table ' . $t;
		}
	}
}

//see if we can rename any tables
$objects = db_query('SELECT title, table_name FROM app_objects');
while ($o = db_fetch($objects)) {
	if (!db_table_exists($o['table_name'])) {
		//table has been lost, kill record
		if (!$test) db_query('DELETE FROM app_objects WHERE table_name = "' . $o['table_name'] . '"');
		$activity[] = 'deleted empty record in app_objects for ' . $o['table_name'];
	} else {
		$target = format_text_code('user_' . $o['title']);
		if (($o['table_name'] != $target) && !db_table_exists($target)) {
			if (!$test) db_table_rename($o['table_name'], $target);
			$activity[] = 'renamed ' . $o['table_name'] . ' to ' . $target;
		}
	}
}

//todo see if we can rename any columns
//with syntax eg ALTER TABLE spacetime CHANGE wind_direction_id_1 direction_id TINYINT(3) UNSIGNED;

if (!count($activity)) {
	echo 'No cleanup operations required';
} elseif ($test) {
	echo 'The following operations would have been performed: ' . draw_list($activity);
} else {
	echo 'The following operations were completed: ' . draw_list($activity);
}

echo drawLast();