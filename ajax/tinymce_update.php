<?php
include('../include.php');
$array = array_ajax();

$fields = db_table('SELECT f.field_name, o.table_name FROM app_fields f JOIN app_objects o ON f.object_id = o.id WHERE f.is_active = 1 AND o.is_active = 1 AND f.type = "textarea"');
foreach ($fields as $f) {
	db_query('UPDATE ' . $f['table_name'] . ' SET ' . $f['field_name'] . ' = REPLACE(' . $f['field_name'] . ', "' . $array['old_server'] . '/", "' . $request['host'] . '/")');
}
echo format_quantitize(mysql_affected_rows(), 'row', false) . ' affected';