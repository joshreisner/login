<?php
include('../include.php');

//handle an object delete
if (url_action('delete')) {
	db_delete(db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['delete_object']), $_GET['delete_id']);
	url_change('./?id=' . $_GET['id']);
} elseif (url_action('undelete')) {
	db_undelete(db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['delete_object']), $_GET['delete_id']);
	url_change('./?id=' . $_GET['id']);
}

//ensure id
url_query_require('../');

//get object info
$object = db_grab('SELECT o.title, o.list_help, (SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . ' AND u2o.object_id = o.id) permission FROM app_objects o WHERE o.id = ' . $_GET['id']);

//security
if (!$object['permission'] && !isAdmin()) url_change('../');

//draw the header
echo drawFirst($object['title']);

//draw nav + table
echo drawObjectList($_GET['id']);

//help panel on right side, potentially editable
$panel = str_ireplace("\n", '<br/>', $object['list_help']);

echo drawLast($panel, (isProgrammer() ? 'app_objects.list_help.' . $_GET['id'] : false));

echo draw_javascript_ready('
	var contenteditable_focused = false;
	$("div[contenteditable=true]").focus(function(){ contenteditable_focused = true; });
	$("div[contenteditable=true]").blur(function(){ contenteditable_focused = false; });

	$(document).keypress(function(e) {
		if (!contenteditable_focused && (e.which == 97)) location.href = $("div.nav a").last().attr("href");
	});
');