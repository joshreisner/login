<?php
include('../include.php');

if (!admin()) url_change($base);

if ($posting) {
	if ($uploading) $_POST['banner_image'] = format_image_resize(file_get_uploaded('banner_image'), false, 95);
	db_save('app', db_grab('SELECT COUNT(*) FROM app'));
	url_change_post('../');
}
echo drawTop('Site Settings');

$t = new form('app', 1, 'Edit Site Settings');
echo $t->draw();

echo draw_div('panel', 'The banner image will be resized to 920px x 105px.');

echo drawBottom();
?>