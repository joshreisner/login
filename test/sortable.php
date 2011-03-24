<?php
include('../include.php');

function getPages() {
	$return = array();
	$pages = db_table('SELECT id, title, parent_id, url, precedence, subsequence FROM user_pages WHERE is_active = 1 AND is_published = 1 ORDER BY precedence');
	foreach ($pages as $p) {
		$p['children'] = array();
		if (empty($p['parent_id'])) {
			$return[] = $p;
		} elseif (nodeExists(&$return, $p['parent_id'], $p)) {
			//attached child to parent node
		} else {
			//an error occurred, because a parent exists but is not in the tree
		}
	}
	return $return;
}

function nodeExists(&$array, $parent_id, $child) {
	foreach ($array as &$a) {
		if ($a['id'] == $parent_id) {
			$a['children'][] = $child;
			return true;
		} elseif (count($a['children']) && nodeExists($a['children'], $parent_id, $child)) {
			return true;
		}
	}
	return false;
}

echo drawTop('Sortable Test');

echo draw_css_src('sortable.css');

echo lib_get('jquery');

echo draw_javascript_src('sortable.js');
echo draw_javascript_src('/login/scripts/jquery-ui-1.8.9.custom.min.js');
echo draw_javascript_src('/login/scripts/jquery.ui.nestedSortable.js');

echo '<div style="width:650px">' . drawNav(getPages(), 'sortable tree') . '</div>';

echo'
	<hr />
	
	<p><input type="submit" name="serialize" id="serialize" value="Serialize" />
	<pre id="serializeOutput"></pre></p>

	<p><input type="submit" name="toHierarchy" id="toHierarchy" value="To hierarchy" />
	<pre id="toHierarchyOutput"></pre></p>

	<p><input type="submit" name="toArray" id="toArray" value="To array" />
	<pre id="toArrayOutput"></pre></p>
';

echo draw_div('panel');
echo drawBottom();

?>
