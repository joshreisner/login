<?php
include('include.php');
/*
get all ancestors
SELECT title FROM user_pages WHERE precedence < 4 AND subsequnce > 5 ORDER BY precedence ASC;

How Many Descendants
descendants = (subsequence – precedence - 1) / 2

Automating the Tree Traversal

UPDATE user_pages SET subsequence = subsequence + 2 WHERE subsequence > 5;   
UPDATE user_pages SET precedence = precedence + 2 WHERE precedence > 5;
INSERT INTO user_pages SET precedence = 6, subsequence = 7, title='Strawberry';


*/
treeRebuild('user_pages');

$array = getPages();

echo drawNav($array[0]['children']);


exit;

$ids = db_array('SELECT id FROM user_pages WHERE parent_id IS NULL');
foreach ($ids as $id) {
	echo treeDisplay('user_pages', $id);
}

//echo treeDisplay('user_pages', 1);


function treeDisplay($table, $root=false, $show_parent=true) {
	//default to main page
	
	//retrieve the left and right value of the $root node  
	$root = db_grab('SELECT precedence, subsequence FROM ' . $table . ' WHERE id = ' . $root);
	
	//now, retrieve all descendants of the $root node  
	$result = db_table('SELECT title, precedence, subsequence, parent_id FROM ' . $table . ' WHERE precedence BETWEEN ' . $root['precedence'] . ' AND ' . $root['subsequence'] . ' ORDER BY precedence');
	
	//display each row  
	$return = '<ul>' . NEWLINE;
	$last_depth = -1;
	foreach ($result as $r) {
		
		//root of a tree
		if (!$r['parent_id'] || !isset($right)) $right = array();
		
		$descendants = ($r['subsequence'] - $r['precedence'] - 1) / 2;
		$depth = count($right);
		
		//only check stack if there is one  
		if ($depth > 0) {  
			//check if we should remove a node from the stack  
			while ($right[$depth - 1] < $r['subsequence']) {
				array_pop($right);
				$depth--;
			}
		}
		
		//shrinking?
		if ($depth < $last_depth) $return .= str_repeat('</ul>' . NEWLINE, $last_depth - $depth);

		//display indented node title  
		$return .= str_repeat(TAB, $depth) . draw_li($r['title'] . ' (' . $descendants . ',' . $depth . ',' . $r['precedence'] . ', ' . $r['subsequence'] . ')') . NEWLINE;
		
		//add this node to the stack  
		$right[] = $r['subsequence'];
		
		//growing?
		if ($descendants) $return .= '<ul>' . NEWLINE;
		
		//save last depth for next loop
		$last_depth = $depth;
	}
	$return .= str_repeat('</ul>' . NEWLINE, $depth);
	return $return . '</ul>' . NEWLINE;
}  

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

?>