<?php
include('../../include.php');

//sekurit
if (!isAdmin()) url_change('../');
url_query_require();

$object = db_grab('SELECT title, table_name FROM app_objects WHERE id = ' . $_GET['id']);

if (url_action('delete')) {
	db_delete('app_fields', $_GET['delete_id']);
	//guess i'm deciding not to delete the field... (maybe rename deleted_)?
	url_drop('action,delete_id');
}


echo drawFirst(draw_link('../?id=' . $_GET['id'], $object['title']) . CHAR_SEPARATOR . 'Fields');

echo draw_nav(array('edit/?object_id=' . $_GET['id']=>'<i class="icon-pencil"></i> Add Field'));

$result = db_table('SELECT 
	f.id, 
	f.title, 
	f.field_name, 
	f.type, 
	f.is_translated,
	' . db_updated('f') . ',
	o.table_name
FROM app_fields f
JOIN app_objects o ON f.object_id = o.id
WHERE f.is_active = 1 AND f.object_id = ' . $_GET['id'] . '
ORDER BY f.precedence');

$t = new table('app_fields');
$t->set_column('draggy', 'c', '&nbsp;', 20);
$t->set_column('name');
$t->set_column('type');
$t->set_column('field_name', 'l', 'Database Field');
$t->set_column('updated', 'r');
$t->set_column('delete', 'c', '&nbsp;', 20);

foreach($result as &$r) {
	$r['class'] = ($r['is_translated']) ? 'admin' : '';
	$r['draggy']	= '<i class="icon-reorder"></i>';
	$r['name']		= draw_link('edit/?id=' . $r['id'] . '&object_id=' . $_GET['id'], $r['title']);
	$r['field_name']	= $r['table_name'] . '.' . $r['field_name'];
	$r['updated']	= format_date($r['updated']);
	$r['type']		= $_josh['field_types'][$r['type']];
	$r['delete']	= draw_link(url_query_add(array('action'=>'delete', 'delete_id'=>$r['id']), false), CHAR_DELETE);
}
echo $t->draw($result, 'No fields have been added to this object yet!');

$help_text = draw_p('These are the fields that belong to the ' . $object['title'] . ' object.');

if ($languages) {
	$help_text .= draw_p('The shaded fields are translated.');
}

echo drawLast($help_text);