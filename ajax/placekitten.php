<?php
include('../include.php');
if ($kitten = url_get('http://placekitten.com/' . $_GET['width'] . '/' . $_GET['height'])) {
	file_download($kitten, 'kitten_' . $_GET['width'] . 'x' . $_GET['height'], 'jpg');
}
