<?php
include('../include.php');

if (!admin()) url_change(DIRECTORY_BASE);

if (url_action('delete')) {
	db_delete('app_users');
	url_drop('action,id');
}

echo drawFirst('Users');

echo draw_nav(array('edit/'=>'Add New User'));

$result = db_table('SELECT 
	u.id, 
	u.firstname, 
	u.lastname, 
	u.email, 
	u.is_admin,
	' . db_updated() . '
FROM app_users u
WHERE u.is_active = 1
ORDER BY u.lastname, u.firstname, u.id');

$t = new table;
$t->set_column('name');
$t->set_column('email');
$t->set_column('updated', 'r');
$t->set_column('delete', 'c', '&nbsp;', 20);

foreach($result as &$r) {
	$r['class'] = $r['is_admin'] ? 'admin' : false;
	$r['name'] = draw_link('edit/?id=' . $r['id'], $r['lastname'] . ', ' . $r['firstname']);
	$r['updated'] = format_date($r['updated']);
	$r['delete'] = draw_link(url_query_add(array('action'=>'delete', 'id'=>$r['id']), false), CHAR_DELETE);
}
echo $t->draw($result, 'No users have been added yet!');

echo draw_div('panel', 'These users have access to the CMS.  Shaded users are admins.');

echo drawLast();
?>