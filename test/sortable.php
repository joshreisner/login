<?php
include('../include.php');

function getPages() {
	$return = array();
	$pages = db_table('SELECT 
			p.id, 
			p.title, 
			p.parent_id, 
			p.url, 
			p.precedence, 
			p.subsequence, 
			p.is_published,
			u1.firstname created_user, 
			u2.firstname updated_user, 
			IFNULL(p.updated_date, p.created_date) updated 
		FROM user_pages p 
		LEFT JOIN app_users u1 ON p.created_user = u1.id 
		LEFT JOIN app_users u2 ON p.updated_user = u2.id 
		WHERE p.is_active = 1 AND p.is_published = 1 
		ORDER BY p.precedence');
	foreach ($pages as $p) {
		if (empty($p['updated_user'])) $p['updated_user'] = $p['created_user'];
		$p['children'] = array();
		if (empty($p['parent_id'])) {
			$return[] = $p;
		} elseif (nestedNodeExists($return, $p['parent_id'], $p)) {
			//attached child to parent node
		} else {
			//an error occurred, because a parent exists but is not in the tree
		}
	}
	return $return;
}

echo drawFirst('Sortable Test');

echo draw_css_src('sortable.css');
echo draw_javascript_src('sortable.js');
echo draw_javascript_src(DIRECTORY_BASE . 'scripts/jquery-ui-1.8.9.custom.min.js');
echo draw_javascript_src(DIRECTORY_BASE . 'scripts/jquery.ui.nestedSortable.js');

echo '<div style="width:650px">' . nestedList(getPages(), 'sortable tree') . '</div>';

echo'
	<hr />
	
	<p><input type="submit" name="serialize" id="serialize" value="Serialize" />
	<pre id="serializeOutput"></pre></p>

	<p><input type="submit" name="toHierarchy" id="toHierarchy" value="To hierarchy" />
	<pre id="toHierarchyOutput"></pre></p>

	<p><input type="submit" name="toArray" id="toArray" value="To array" />
	<pre id="toArrayOutput"></pre></p>
';

echo drawLast();