<?php
include('../include.php');
$array = array_ajax();

echo draw_array($array);

//update with new parent
db_query('UPDATE ' . $array['table_name'] . ' SET ' . $array['nesting_column'] . ' = ' . $array['parent_id'] . ' WHERE id = ' . $array['id']);

//set precedences
$ids = array_separated($array['list']);
$precedence = 0;
foreach ($ids as $id) db_query('UPDATE ' . $array['table_name'] . ' SET precedence = ' . $precedence++ . ' WHERE id = ' . $id);

nestedTreeRebuild($array['table_name']);

echo 'done!' . $array['parent_id'];