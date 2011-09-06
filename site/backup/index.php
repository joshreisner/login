<?php
include('../../include.php');

echo drawFirst();

if ($filename = db_backup()) {
	echo 'backup succeeded: ' . draw_link($filename, $filename);
} else {
	echo 'backup failed';
}

echo drawLast();
