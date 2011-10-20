<?php
include('../include.php');

lib_get('simple_html_dom');

echo draw_h1('Finding External Links');

$fields = db_table('SELECT o.id, o.title, o.table_name, f.field_name FROM app_fields f JOIN app_objects o ON f.object_id = o.id WHERE f.type = "textarea" and f.is_active = 1 AND o.is_active = 1 ORDER BY o.title');

foreach ($fields as $f) {
	echo draw_h2('Checking ' . $f['title'] . ' (' . $f['table_name'] . '.' . $f['field_name'] . ')');
	$values = db_table('SELECT id, ' . $f['field_name'] . ' FROM ' . $f['table_name'] . ' WHERE is_active = 1');
	foreach ($values as $v) {
		$text = str_get_html($v[$f['field_name']]);
		$links = $text->find('a');
		foreach ($links as $l) {
			if (!empty($l->href)) {
				if (substr($l->href, 0, 1) == '/') {
					$l->href = url_base() . $l->href;
				} elseif (substr($l->href, 0, 3) == '../') {
					echo 'CANT CHECK RELATIVE LINKS' . $l->href;
					continue;
				}
				if (!is_available($l->href)) {
					echo draw_link('../object/edit/?id=' . $v['id'] . '&object_id=' . $f['id'], 'this') . ' could not fetch link to ' . draw_link($l->href, $l->href, true) . BR;
				} else {
					echo 'found ' . format_string($l->href) . BR;
				}
				flush();
			}
		}
	}
}

//todo search url fields

function is_available($url, $timeout=30) {
	$ch = curl_init();
	curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER=>true, CURLOPT_URL=>$url, CURLOPT_NOBODY=>true, CURLOPT_TIMEOUT=>$timeout)); 
	curl_exec($ch);
	$return = curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200;
	curl_close($ch);
	return $return;
}