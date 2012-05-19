<?php
include('../include.php');

if (!admin(SESSION_ADMIN)) url_change(DIRECTORY_BASE);

if ($posting) {
	if ($uploading) $_POST['banner_image'] = format_image_resize(file_get_uploaded('banner_image'), false, 105);
	db_save('app', 1, 'post', false);

	//process changes to languages
	$codes		= array_key_promote(db_table('SELECT id, code FROM app_languages'));
	$languages_checked	= array_checkboxes('languages');
	db_query('UPDATE app_languages SET checked = 0');
	foreach ($languages_checked as $l) {
		db_query('UPDATE app_languages SET checked = 1 WHERE id = ' . $l['id']);

		//check to make sure that fields are translated
		$fields = db_table('SELECT f.type, f.field_name, o.table_name FROM app_fields f JOIN app_objects o ON f.object_id = o.id WHERE f.is_translated = 1 and f.is_active = 1');
		foreach ($fields as $f) if (!db_column_exists($f['table_name'], $f['field_name'] . '_' . $codes[$l])) db_column_add($f['table_name'], $f['field_name'] . '_' . $codes[$l], $f['type']);
	}
	url_change_post('../');
}

echo drawFirst('Site Settings');

$t = new form('app', 1, 'Edit Site Settings');
$t->set_field(array('type'=>'checkboxes', 'name'=>'languages', 'sql'=>'SELECT id, title, checked FROM app_languages ORDER BY title'));
echo $t->draw();

$panel =
	draw_p('The banner image will be resized to 920px x 105px.') . 
	draw_p('If you have just migrated servers, you may wish to <a class="tinymce_update">update the TinyMCE file and image references</a>.') .
	draw_p('You can also <a href="cleanup/">clean up</a> deleted or unused columns (irreversibly), or <a href="cleanup/?test=true">run the cleanup in test mode</a> to see what operations 
		would be performed.') . 
	draw_p('You can <a href="backup/">run a backup</a> on the database');

echo drawLast($panel);