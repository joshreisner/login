<?php 
//add a new object to the CMS or edit its settings
include('../include.php');

if (!admin()) url_change($base);

if ($posting) {
	if (!$editing) {
		//create new table
		$_POST['table_name'] = getNewObjectName('user ' . $_POST['title']);
		db_table_create($_POST['table_name']);
	}
	$id = db_save('app_objects');
	db_checkboxes('permissions', 'app_users_to_objects', 'object_id', 'user_id', $id);
	if ($editing) {
		db_checkboxes('object_links', 'app_objects_links', 'object_id', 'linked_id', $_GET['id']);
		url_change_post('../');
	} else {
		//add new title column because we nearly always need it
		db_column_add($_POST['table_name'], 'title', 'text');
		db_save('app_fields', false, array('object_id'=>$id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>true));
		url_change('../object/?id=' . $id);
	}
} elseif (url_action('delete')) {
	//ok you're going to delete this object
	$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']);
	if (db_table_drop($table)) {
		db_table_drop($table . '_to_words');
		db_query('DELETE FROM app_fields WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_objects_links WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_users_to_objects WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_objects WHERE id = ' . $_GET['id']);
	}
	url_change($base);
} elseif ($editing) {
	$title = db_grab('SELECT title FROM app_objects WHERE id = ' . $_GET['id']);
	$action = 'Edit Settings';
	echo drawTop(draw_link('../object/?id=' . $_GET['id'], $title) . ' &gt; ' . $action);
} else { //adding
	$action = 'Add New Object';
	echo drawTop($action);
}

$f = new form('app_objects', @$_GET['id'], $action);

if (url_id()) {
	//if editings present more options
	$order_by = db_table('SELECT field_name, title FROM app_fields WHERE object_id = ' . $_GET['id'] . ' AND is_active = 1 ORDER BY precedence');
	$order_by['precedence'] = 'Precedence';
	$order_by['created_date'] = 'Created';
	$order_by['updated_date'] = 'Updated';
	$f->set_field(array('name'=>'order_by', 'type'=>'select', 'options'=>$order_by));
	$f->set_field(array('name'=>'table_name', 'type'=>'text', 'allow_changes'=>false));
	$f->set_field(array('name'=>'direction', 'type'=>'select', 'options'=>array_2d(array('ASC', 'DESC')), 'default'=>'ASC', 'required'=>true));
	if ($options = db_table('SELECT id, title FROM app_fields WHERE type = "select" AND is_active = 1 AND object_id = ' . $_GET['id'])) {
		$f->set_field(array('name'=>'group_by_field', 'label'=>'Group By', 'type'=>'select', 'options'=>$options));
	}
	if ($options = db_table('SELECT o.id, o.title, (SELECT COUNT(*) FROM app_objects_links l WHERE l.object_id = ' . $_GET['id'] . ' AND l.linked_id = o.id) checked FROM app_objects o JOIN app_fields f ON o.id = f.object_id WHERE f.related_object_id = ' . $_GET['id'])) {
		$f->set_field(array('name'=>'object_links', 'type'=>'checkboxes', 'label'=>'Linked Objects', 'linking_table'=>'app_objects_links', 'options_table'=>'app_objects', 'option_id'=>'object_id', 'option_title'=>'title', 'options'=>$options));
	}
} else {
	$f->unset_fields('table_name,order_by,web_page,show_published');
	$f->set_field(array('name'=>'direction', 'type'=>'hidden', 'value'=>'ASC'));
}

//permissions
if (db_grab('SELECT COUNT(*) FROM app_users WHERE is_active = 1 AND is_admin <> 1 AND id <> ' . user())) {
	$sql = 'SELECT u.id, CONCAT(u.firstname, " ", u.lastname) title, ' . (url_id() ? '(SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = u.id AND u2o.object_id = ' . $_GET['id'] . ')' : 1) . ' checked FROM app_users u WHERE u.is_active = 1 and u.is_admin <> 1 ORDER BY title';
	$f->set_field(array('name'=>'permissions', 'type'=>'checkboxes', 'sql'=>$sql));
}

//table name handled automatically, help handled with in-place editor
$f->unset_fields('list_help,form_help');
echo $f->draw();

if (url_id()) echo draw_div('panel', 'You can drop this object and all its associated fields and values by ' . draw_link(url_action_add('delete'), 'clicking here') . '.');

echo drawBottom();
?>