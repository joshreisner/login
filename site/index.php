<?php
include('../include.php');

if (!admin()) url_change(DIRECTORY_BASE);

if ($posting) {
	if ($uploading) $_POST['banner_image'] = format_image_resize(file_get_uploaded('banner_image'), false, 95);
	db_save('app', db_grab('SELECT COUNT(*) FROM app'));

	//process changes to languages
	$languages_checked = array_checkboxes('languages');
	$languages = db_table('SELECT id, checked, committed, code FROM app_languages');
	foreach ($languages as $l) {
		if (in_array($l['id'], $languages_checked) && !$l['checked']) {
			//just checked off a new language
			if (!$l['committed']) {
				$fields = db_table('SELECT f.id field_id, f.type, f.object_id, f.field_name, o.table_name FROM app_fields f JOIN app_objects o ON f.object_id = o.id WHERE f.is_active = 1 AND o.is_active = 1 AND f.type IN ("text", "textarea", "textarea-plain")');
				foreach ($fields as $f) db_column_add($f['table_name'], $f['field_name'] . '_' . $l['code'], $f['type']);
			}
			db_query('UPDATE app_languages SET checked = 1, committed = 1 WHERE id = ' . $l['id']);
		} elseif ($l['checked'] && !in_array($l['id'], $languages_checked)) {
			//just unchecked a language
			db_query('UPDATE app_languages SET checked = 0 WHERE id = ' . $l['id']);
		}
	}
	
	url_change_post('../');
}
echo drawFirst('Site Settings');

$t = new form('app', 1, 'Edit Site Settings');
$t->set_field(array('type'=>'checkboxes', 'name'=>'languages', 'sql'=>'SELECT id, title, checked FROM app_languages ORDER BY title'));
echo $t->draw();

echo draw_div('panel', 
	draw_p('The banner image will be resized to 920px x 105px.') . 
	draw_p('If you have just migrated servers, you may wish to <a class="tinymce_update">update the TinyMCE file and image references</a>.') .
	draw_p('Be careful with the languages, man.')
);

echo drawLast();
?>