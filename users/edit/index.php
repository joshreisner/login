<?php
include('../../include.php');

if (!admin(SESSION_ADMIN)) url_change(DIRECTORY_BASE);

if ($posting) {
	if ($uploading) $_POST['photo'] = format_image_resize(file_get_uploaded('photo'), 52, 52);
	$id = db_save('app_users', url_id(), 'post', false);
	db_checkboxes('permissions', 'app_users_to_objects', 'user_id', 'object_id', $id);
	url_change_post('../');
} elseif ($editing) {
	$action = 'Edit User';
} else { //adding
	$action = 'Add New User';
}

echo drawFirst(draw_link('../', 'Users') . CHAR_SEPARATOR . $action);

$user = new form('app_users', @$_GET['id']);
$user->unset_fields('secret_key,last_login');
$user->set_field(array('name'=>'password', 'type'=>'text', 'required'=>true));
$user->set_field(array('name'=>'permissions', 'type'=>'checkboxes', 'options_table'=>'app_objects', 'linking_table'=>'app_users_to_objects', 'option_id'=>'object_id', 'object_id'=>'user_id', 'value'=>@$_GET['id'], 'default'=>true));
echo $user->draw(false, false);

echo drawLast();