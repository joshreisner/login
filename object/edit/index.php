<?php
include('../../include.php');

url_query_require('../', 'object_id');

$object = db_grab('SELECT o.title, o.table_name, o.form_help, o.show_published, o.web_page, (SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . ' AND u2o.object_id = o.id) permission FROM app_objects o WHERE o.id = ' . $_GET['object_id']);

//security
if (!$object['permission'] && !admin()) url_change('../../');

/* if (url_action('delete')) {
	//handle an object delete
	db_delete(db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['delete_object']), $_GET['delete_id']);
	url_change('./?id=' . $_GET['id'] . '&object_id=' . $_GET['object_id']);
} else*/ if (url_action('undelete')) {
	//handle an object delete
	db_undelete(db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['delete_object']), $_GET['delete_id']);
	url_change('./?id=' . $_GET['id'] . '&object_id=' . $_GET['object_id']);
} elseif ($posting) {
	//add a new one
	if ($uploading) {
		//fetch any image or file fields, because analytical fields are possible here
		$result = db_query('SELECT id, type, field_name, width, height FROM app_fields WHERE is_active = 1 AND (type = "image" OR type = "file") AND object_id = ' . $_GET['object_id']);
		while ($r = db_fetch($result)) {
			$type = file_type($_FILES[$r['field_name']]['name']);
			if ($file = file_get_uploaded($r['field_name'])) {
				if ($r['type'] == 'image') {
					//get any related images first
					$related = db_query('SELECT field_name, width, height FROM app_fields WHERE is_active = 1 AND type = "image-alt" AND object_id = ' . $_GET['object_id'] . ' AND related_field_id = ' . $r['id']);
					while ($e = db_fetch($related)) $_POST[$e['field_name']] = format_image_resize($file, $e['width'], $e['height']);
					
					//then resize if you should
					$_POST[$r['field_name']] = ($r['width'] || $r['height'])  ? format_image_resize($file, $r['width'], $r['height']) : $file;
				} elseif ($r['type'] == 'file') {
					//get any file_types
					$related = db_query('SELECT field_name FROM app_fields WHERE is_active = 1 AND type = "file-type" AND object_id = ' . $_GET['object_id'] . ' AND related_field_id = ' . $r['id']);
					while ($e = db_fetch($related)) $_POST[$e['field_name']] = file_ext($_FILES[$r['field_name']]['name']);
					
					//get any related images--in this case, these would be thumbnails.  also be sure that it's a PDF that was uploaded
					if ($type == 'pdf') {
						$related = db_query('SELECT field_name, width, height FROM app_fields WHERE is_active = 1 AND type = "image-alt" AND object_id = ' . $_GET['object_id'] . ' AND related_field_id = ' . $r['id']);
						while ($e = db_fetch($related)) $_POST[$e['field_name']] = format_thumbnail_pdf($file, $e['width'], $e['height']);
					}
					
					$_POST[$r['field_name']] = $file;
				}
			}
		}
	}
	
	$fields = db_table('SELECT id, field_name FROM app_fields WHERE is_active = 1 AND type = "url" AND object_id = ' . $_GET['object_id']);
	foreach ($fields as $f) if ($_POST[$f['field_name']] == 'http://') $_POST[$f['field_name']] = '';
	
	$id = db_save($object['table_name']);
	
	//checkboxes
	$fields = db_table('SELECT f.field_name, o.table_name, o2.table_name rel_table FROM app_fields f JOIN app_objects o ON o.id = f.object_id JOIN app_objects o2 ON o2.id = f.related_object_id WHERE f.is_active = 1 AND f.type = "checkboxes" AND o.id = ' . $_GET['object_id']);
	foreach ($fields as $f) db_checkboxes($f['field_name'], $f['field_name'], substr($f['table_name'], 5) . '_id', substr($f['rel_table'], 5) . '_id', $id);
	
	//tree?  rebuild
	if (db_grab('SELECT COUNT(*) FROM app_fields f WHERE object_id = ' . $_GET['object_id'] . ' AND related_object_id = ' . $_GET['object_id'])) {
		nestedTreeRebuild($object['table_name']);
	}
	
	
	//objects -- deprecated?
	$fields = db_table('SELECT f.field_name, o.table_name, o2.table_name rel_table FROM app_fields f JOIN app_objects o ON o.id = f.object_id JOIN app_objects o2 ON o2.id = f.related_object_id WHERE f.is_active = 1 AND f.type = "object" AND o.id = ' . $_GET['object_id']);
	foreach ($fields as $f) {
		$chbxes = array_post_checkboxes($f['field_name']);
		$precedence = 1;
		db_query('DELETE FROM ' . $f['field_name'] . ' WHERE ' . substr($f['table_name'], 5) . '_id = ' . $id);
		foreach ($chbxes as $c) {
			db_query('INSERT INTO ' . $f['field_name'] . ' (
				' . substr($f['table_name'], 5) . '_id,
				' . substr($f['rel_table'], 5) . '_id,
				precedence,
				created_user,
				created_date,
				is_active
			) VALUES (
				' . $id . ',
				' . $c . ',
				' . $precedence . ',
				' . user() . ',
				NOW(),
				1
			)');
			$precedence++;
		}
	}
	
	url_change_post('../?id=' . $_GET['object_id']);
} elseif ($editing) {
	$action = 'Edit';
	$button = 'Save Changes';
} else { //adding
	$action = 'Add New';
	$button = 'Add New';
}

echo drawFirst(draw_link('../?id=' . $_GET['object_id'], $object['title']) . ' &gt; ' . $action);

$f = new form($object['table_name'], @$_GET['id'], $button);

if ($editing && $object['web_page']) echo draw_div('web_page_msg', draw_link($object['web_page'] . $_GET['id'], 'View Web Version'));

$order = array();
$result = db_query('SELECT 
				f.id, 
				f.title, 
				f.field_name, 
				f.type, 
				f.required, 
				f.width,
				f.related_object_id, 
				f.additional, 
				f.visibility,
				f.is_active,
				o.table_name
			FROM app_fields f
			JOIN app_objects o ON f.object_id = o.id
			WHERE o.id = ' . $_GET['object_id'] . '
			ORDER BY f.precedence');
while ($r = db_fetch($result)) {
	if (!$r['is_active'] || ($r['visibility'] == 'hidden')) {
		//need to specify this because the column is still present in the db after it's deleted
		$f->unset_fields($r['field_name']);
	} else {
		$order[] = $r['field_name'];
		$class = $options_table = $option_title = false;
		$additional = $r['additional'];
		$preview = false;
		
		//per field type form adjustments
		if ($r['type'] == 'select') {
			if (url_id('from_type') && url_id('from_id') && $_GET['from_type'] == $r['related_object_id']) {
				//if coming from the linked object, make this a hidden field
				$f->set_field(array('name'=>$r['field_name'], 'type'=>'hidden', 'value'=>$_GET['from_id']));
			} else {
				//otherwise need to do some work to formulate the select; need to gather the linked object's properties
				$rel_object = db_grab('SELECT 
						o.id, 
						o.title, 
						o.table_name, 
						o.show_published, 
						o.order_by, 
						o.direction,
						o.group_by_field,
						(SELECT f.field_name FROM app_fields f WHERE f.is_active = 1 AND f.object_id = o.id ORDER BY f.precedence LIMIT 1) field_name,
						(SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . '  AND u2o.object_id = o.id) permission
					FROM app_objects o
					WHERE o.id = ' . $r['related_object_id']);
				if ($_GET['object_id'] == $rel_object['id']) {
					//nested object
					$sql = 'SELECT id, ' . $rel_object['field_name'] . ' FROM ' . $rel_object['table_name'] . ' WHERE is_active = 1';
					if (url_id()) $sql .= ' AND id <> ' . url_id();
					if (!$rel_object['order_by']) $rel_object['order_by'] = $rel_object['field_name'];
					$sql .= ' ORDER BY ' . $rel_object['order_by'] . ' ' . $rel_object['direction'];
				} elseif ($rel_object['group_by_field']) {
					//this needs to be a grouped select
					$group = db_grab('SELECT o.order_by, o.direction, o.table_name, f.field_name field_name_from, (SELECT f2.field_name FROM app_fields f2 WHERE f2.object_id = o.id AND f2.visibility = "list" ORDER BY f2.precedence LIMIT 1) field_name_to FROM app_fields f JOIN app_objects o ON f.related_object_id = o.id WHERE f.id = ' . $rel_object['group_by_field']);
					$sql = 'SELECT r.id, r.' . $rel_object['field_name'] . ', g.' . $group['field_name_to'] . ' optgroup FROM ' . $rel_object['table_name'] . ' r LEFT JOIN ' . $group['table_name'] . ' g ON r.' . $group['field_name_from'] . ' = g.id WHERE r.is_active = 1';
					if (!$group['order_by']) $group['order_by'] = $group['field_name'];
					$sql .= ' ORDER BY g.' . $group['order_by'] . ' ' . $group['direction'];
					if (!$rel_object['order_by']) $rel_object['order_by'] = $rel_object['field_name'];
					$sql .= ', r.' . $rel_object['order_by'] . ' ' . $rel_object['direction'];
				} else {
					$sql = 'SELECT id, ' . $rel_object['field_name'] . ' FROM ' . $rel_object['table_name'] . ' WHERE is_active = 1';
					if (!$rel_object['order_by']) $rel_object['order_by'] = $rel_object['field_name'];
					$sql .= ' ORDER BY ' . $rel_object['order_by'] . ' ' . $rel_object['direction'];
				}
				if (($_GET['object_id'] != $rel_object['id']) && ($rel_object['permission'] || admin())) $additional = draw_link(DIRECTORY_BASE . 'object/?id=' . $rel_object['id'], 'Edit ' . $rel_object['title']);

				$f->set_field(array('name'=>$r['field_name'], 'type'=>$r['type'], 'class'=>$class, 'label'=>$r['title'], 'required'=>$r['required'], 'additional'=>$additional, 'sql'=>$sql));
			}
		} elseif ($r['type'] == 'checkboxes') {
			$rel_object = db_grab('SELECT 
					o.id, 
					o.title, 
					o.table_name, 
					(SELECT f.field_name FROM app_fields f WHERE f.object_id = o.id AND f.is_active = 1 ORDER BY f.precedence LIMIT 1) field_name,
					(SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . '  AND u2o.object_id = o.id) permission
				FROM app_objects o WHERE o.id = ' . $r['related_object_id']);
			if ($rel_object['permission'] || admin()) $additional = draw_link(DIRECTORY_BASE . 'object/?id=' . $rel_object['id'], 'Edit ' . $rel_object['title']);
			$f->set_field(array('label'=>$r['title'], 'maxlength'=>24, 'additional'=>$additional, 'name'=>$r['field_name'], 'type'=>'checkboxes', 'options_table'=>$rel_object['table_name'], 'linking_table'=>$r['field_name'], 'option_id'=>substr($rel_object['table_name'], 5) . '_id', 'object_id'=>substr($object['table_name'], 5) . '_id', 'option_title'=>$rel_object['field_name'], 'value'=>@$_GET['id']));
		} else {
			$label = $r['title'];
			$maxlength = false;
			
			if ($r['type'] == 'image') {
				$preview = true;
				$r['type'] = 'file';
				//dont' think this is ready
				if (url_id() && db_grab('SELECT CASE WHEN ' . $r['field_name'] . ' IS NULL THEN 0 ELSE 1 END FROM ' . $r['table_name'] . ' WHERE id = ' . $_GET['id'])) {
					$label .= '<br/>' . draw_link(false, 'Clear Image', false, array(
						'class'=>'clear_img', 
						'data-table'=>$r['table_name'],
						'data-column'=>$r['field_name'],
						'data-id'=>$_GET['id'],
						'data-title'=>$r['title']
					));
				}
				//todo form::set_field should support all these types
			} elseif ($r['type'] == 'text') {
				$maxlength = $r['width'];
			} elseif ($r['type'] == 'textarea') {
				$class = 'tinymce'; //tinymce is the official wysiwyg of the cms
				$maxlength = $r['width'];
				//add lorem ipsum generator to tinymce
				if (admin()) {
					echo lib_get('lorem_ipsum');
					$label .= '<br/>' . draw_link('#', 'Lorem Ipsum', false, array('class'=>'lorem_ipsum'));
				}
			}
			
			$f->set_field(array('name'=>$r['field_name'], 'type'=>$r['type'], 'class'=>$class, 'label'=>$label, 'required'=>$r['required'], 'additional'=>$additional, 'maxlength'=>$maxlength, 'preview'=>$preview));
		}
	}
}

if ($editing) {
	//need to get instance for is_published and created / updated meta stuff below
	$instance = db_grab('SELECT created_user, is_published FROM ' . $object['table_name']  . ' WHERE id = ' . $_GET['id']);
} else {
	//otherwise set defaults
	$instance = array('created_user'=>user(), 'is_published'=>true);
}

//allow setting created / updated
if (admin()) {
	$f->set_field(array('name'=>'created_user', 'type'=>'select', 'sql'=>'SELECT id, CONCAT(firstname, " ", lastname) FROM app_users ORDER BY lastname, firstname', 'required'=>true, 'value'=>$instance['created_user']));
	if ($editing) $f->set_field(array('name'=>'updated_user', 'type'=>'select', 'sql'=>'SELECT id, CONCAT(firstname, " ", lastname) FROM app_users ORDER BY lastname, firstname', 'required'=>true, 'value'=>user()));
}

if ($object['show_published']) $f->set_field(array('name'=>'is_published', 'type'=>'checkbox', 'value'=>$instance['is_published']));

$f->set_order(implode(',', $order));
echo $f->draw();

//related objects
if ($editing && $objects = db_table('SELECT o.id, o.title, o.table_name FROM app_objects o JOIN app_objects_links l ON l.linked_id = o.id WHERE l.object_id = ' . $_GET['object_id'])) {
	foreach ($objects as $o) {
		echo '<hr/>' . draw_container('h2', 'Related ' . $o['title']);
		echo drawObjectList($o['id'], $_GET['object_id'], $_GET['id']);
	}	
}

//help panel on right side, potentially editable
echo draw_div('panel', str_ireplace("\n", '<br/>', $object['form_help']), false, (admin() ? 'app_objects.form_help.' . $_GET['object_id'] : false));

echo drawLast();
?>