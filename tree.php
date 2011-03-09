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
?>