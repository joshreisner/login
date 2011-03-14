<?php
include('../include.php');

lib_get('simple_html_dom');

//get valid urls
$urls = db_array('SELECT url FROM user_pages_new WHERE is_active = 1');
//echo draw_array($urls);

echo draw_h1('Finding Internal Links');

$pages = db_table('SELECT id, title, url, content, sidebar FROM user_pages_new WHERE is_active = 1 ORDER BY precedence');
foreach ($pages as $p) {
	$text = str_get_html($p['content']);
	$links = $text->find('a');
	$internal_links = array();
	foreach ($links as $l) {
		//get internal links
		if ($l->href && !format_text_starts('mailto:', $l->href) && !format_text_starts('http://', $l->href) && !format_text_starts('https://', $l->href)) {
			$internal_links[] = $l->href;
		}
	}
	$count = count($internal_links);
	if ($count) echo 'found ' . $count . ' in ' . draw_link('http://demo.seedco.org' . $p['url'], $p['title']) . BR;
}

?>