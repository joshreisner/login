<?php
include('../../include.php');

//sekurit
if (!$_SESSION['is_admin']) url_change('../');
url_query_require();

$object = db_grab('SELECT title, table_name FROM app_objects WHERE id = ' . $_GET['id']);

if (url_action('delete')) {
	db_delete('app_fields', $_GET['delete_id']);
	//guess i'm deciding not to delete the field... (maybe rename deleted_)?
	url_drop('action,delete_id');
}


echo drawTop(draw_link('../?id=' . $_GET['id'], $object['title']) . ' &gt; Fields');

echo draw_nav(array('edit/?object_id=' . $_GET['id']=>'Add Field'));

$result = db_table('SELECT 
	f.id, 
	f.title, 
	f.required, 
	f.type, 
	' . db_updated('f') . '
FROM app_fields f
WHERE f.is_active = 1 AND f.object_id = ' . $_GET['id'] . '
ORDER BY f.precedence');

$t = new table('app_fields');
$t->set_column('draggy', 'c', '&nbsp;', 20);
$t->set_column('name');
$t->set_column('type');
$t->set_column('required', 'c', 'Required?', 20);
$t->set_column('updated', 'r');
$t->set_column('delete', 'c', '&nbsp;', 20);

foreach($result as &$r) {
	$r['draggy']	= '&nbsp;';
	$r['name']		= draw_link('edit/?id=' . $r['id'] . '&object_id=' . $_GET['id'], $r['title']);
	$r['updated']	= format_date($r['updated']);
	$r['type']		= $_josh['field_types'][$r['type']];
	$r['required']	= format_boolean($r['required']);
	$r['delete']	= draw_link(url_query_add(array('action'=>'delete', 'delete_id'=>$r['id']), false), 'X');
}
echo $t->draw($result, 'No fields have been added to this object yet!');

echo draw_div('panel', 'These are the fields that belong to the ' . $object['title'] . ' object.');

echo drawBottom();
?>