<?php
include('../../include.php');

url_query_require('../', 'object_id');

$object = db_grab('SELECT o.title, o.table_name, o.form_help, o.show_published, o.web_page, (SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . ' AND u2o.object_id = o.id) permission FROM app_objects o WHERE o.id = ' . $_GET['object_id']);

//security
if (!$object['permission'] && !admin(SESSION_ADMIN)) url_change('../../');

if (url_action('undelete')) {
	//handle an object delete -- todo ajax
	db_undelete(db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['delete_object']), $_GET['delete_id']);
	url_change('./?id=' . $_GET['id'] . '&object_id=' . $_GET['object_id']);
} elseif ($posting) {

	//handle uploads
	if ($uploading) {
		//fetch any image or file fields, because analytical fields are possible here
		$result = db_query('SELECT id, type, field_name, width, height FROM app_fields WHERE is_active = 1 AND (type = "image" OR type = "file") AND object_id = ' . $_GET['object_id']);
		while ($r = db_fetch($result)) {
			$type = file_type($_FILES[$r['field_name']]['name']);
			if ($file = file_get_uploaded($r['field_name'])) {
				//get any file_types (can be for images or files)
				$related = db_query('SELECT field_name FROM app_fields WHERE is_active = 1 AND type = "file-type" AND object_id = ' . $_GET['object_id'] . ' AND related_field_id = ' . $r['id']);
				while ($e = db_fetch($related)) $_POST[$e['field_name']] = file_ext($_FILES[$r['field_name']]['name']);
				
				if ($r['type'] == 'image') {
					//get any related images first
					$related = db_query('SELECT field_name, width, height FROM app_fields WHERE is_active = 1 AND type = "image-alt" AND object_id = ' . $_GET['object_id'] . ' AND related_field_id = ' . $r['id']);
					while ($e = db_fetch($related)) $_POST[$e['field_name']] = format_image_resize($file, $e['width'], $e['height']);
					
					//then resize if you should
					$_POST[$r['field_name']] = ($r['width'] || $r['height'])  ? format_image_resize($file, $r['width'], $r['height']) : $file;
				} elseif ($r['type'] == 'file') {
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
	
	//postprocess latlon
	$latlons = db_table('SELECT f2.field_name source, f.field_name target FROM app_fields f JOIN app_fields f2 ON f.related_field_id = f2.id WHERE f.type = "latlon" AND f.object_id = ' . $_GET['object_id']);
	foreach ($latlons as $l) {
		if (!empty($_POST[$l['source']]) && $coordinates = geocode($_POST[$l['source']])) $_POST[$l['target']] = $coordinates[0] . ',' . $coordinates[1];
	}
	
	//postprocess urls
	$fields = db_table('SELECT id, field_name FROM app_fields WHERE is_active = 1 AND type = "url" AND object_id = ' . $_GET['object_id']);
	foreach ($fields as $f) if ($_POST[$f['field_name']] == 'http://') $_POST[$f['field_name']] = '';
	
	$id = db_save($object['table_name']);
	
	//handle checkboxes
	$fields = db_table('SELECT f.field_name, o.table_name, o2.table_name rel_table FROM app_fields f JOIN app_objects o ON o.id = f.object_id JOIN app_objects o2 ON o2.id = f.related_object_id WHERE f.is_active = 1 AND f.type = "checkboxes" AND o.id = ' . $_GET['object_id']);
	foreach ($fields as $f) db_checkboxes($f['field_name'], $f['field_name'], substr($f['table_name'], 5) . '_id', substr($f['rel_table'], 5) . '_id', $id);
	
	//if tree, rebuild it
	if (db_grab('SELECT COUNT(*) FROM app_fields f WHERE is_active = 1 AND object_id = ' . $_GET['object_id'] . ' AND related_object_id = ' . $_GET['object_id'])) {
		nestedTreeRebuild($object['table_name']);
	}
	
	url_change_post('../?id=' . $_GET['object_id']);
} elseif ($editing) {
	$action = 'Edit';
	$button = 'Save Changes';
} else { //adding
	$action = 'Add New';
	$button = 'Add New';
}

echo drawFirst(draw_link('../?id=' . $_GET['object_id'], $object['title']) . CHAR_SEPARATOR . $action);

$f = new form($object['table_name'], @$_GET['id'], $button);

if ($editing && $object['web_page']) echo draw_div_class('web_page_msg', draw_link($object['web_page'] . $_GET['id'], 'View Web Version'));
if ($languages && db_grab('SELECT COUNT(*) FROM app_fields WHERE is_translated = 1 AND is_active = 1 AND object_id = ' . $_GET['object_id'])) {
	echo draw_list(array(
		draw_link(false, 'Show Translations', false, 'show_translations'),
		draw_link(false, 'Translate Empty Fields', false, 'translate')
	), 'nav');
}

$order = array();
$result = db_query('SELECT 
				f.id, 
				f.title, 
				f.field_name, 
				f.type, 
				f.required, 
				f.width,
				f.height,
				f.related_object_id, 
				f.additional, 
				f.visibility,
				f.is_active,
				f.is_translated,
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
						(SELECT f.field_name FROM app_fields f WHERE f.is_active = 1 AND f.object_id = o.id AND f.type NOT IN ("file", "image") ORDER BY f.precedence LIMIT 1) field_name,
						(SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . '  AND u2o.object_id = o.id) permission
					FROM app_objects o
					WHERE o.id = ' . $r['related_object_id']);
				$options = array();
				if ($_GET['object_id'] == $rel_object['id']) {
					//nested object select
					$sql = 'SELECT id, ' . $rel_object['field_name'] . ', ROUND((subsequence - precedence - 1) / 2) children, 0 depth FROM ' . $rel_object['table_name'] . ' WHERE is_active = 1';
					if (!$rel_object['order_by']) $rel_object['order_by'] = $rel_object['field_name'];
					$sql = db_table($sql . ' ORDER BY ' . $rel_object['order_by'] . ' ' . $rel_object['direction']);
					$count = count($sql);
					for ($i = 0; $i < $count; $i++) {
						if ($sql[$i]['children']) {
							for ($j = 0; $j < $count; $j++) {
								if ($j > $i && $j <= $i + $sql[$i]['children']) $sql[$j]['depth']++;
							}
						}
						//echo $sql[$i]['depth'] . ' ' . $sql[$i]['title'] . BR;
						if (!url_id() || (url_id() != $sql[$i]['id'])) $options[$sql[$i]['id']] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $sql[$i]['depth']) . $sql[$i]['title']; //can't be its own parent
					}
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
				if (($_GET['object_id'] != $rel_object['id']) && ($rel_object['permission'] || admin(SESSION_ADMIN))) $additional = draw_link(DIRECTORY_BASE . 'object/?id=' . $rel_object['id'], 'Edit ' . $rel_object['title']);

				$f->set_field(array('name'=>$r['field_name'], 'type'=>$r['type'], 'class'=>$class, 'label'=>$r['title'], 'required'=>$r['required'], 'additional'=>$additional, 'sql'=>$sql, 'options'=>$options));
			}
		} elseif ($r['type'] == 'checkboxes') {
			$rel_object = db_grab('SELECT 
					o.id, 
					o.title, 
					o.table_name, 
					(SELECT f.field_name FROM app_fields f WHERE f.object_id = o.id AND f.is_active = 1 AND f.type <> "image" ORDER BY f.precedence LIMIT 1) field_name,
					(SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . '  AND u2o.object_id = o.id) permission
				FROM app_objects o
				WHERE o.id = ' . $r['related_object_id']);
			if ($rel_object['permission'] || admin(SESSION_ADMIN)) $additional = draw_link(DIRECTORY_BASE . 'object/?id=' . $rel_object['id'], 'Edit ' . $rel_object['title']);
			$f->set_field(array('label'=>$r['title'], 'maxlength'=>24, 'additional'=>$additional, 'name'=>$r['field_name'], 'type'=>'checkboxes', 'options_table'=>$rel_object['table_name'], 'linking_table'=>$r['field_name'], 'option_id'=>substr($rel_object['table_name'], 5) . '_id', 'object_id'=>substr($object['table_name'], 5) . '_id', 'option_title'=>$rel_object['field_name'], 'value'=>@$_GET['id']));
		} else {
			$label = $r['title'];
			$maxlength = false;
			
			if ($r['type'] == 'image') {
				$preview = true;
				$r['type'] = 'file';
				//dont' think this is ready
				if (url_id() && db_grab('SELECT CASE WHEN ' . $r['field_name'] . ' IS NULL THEN 0 ELSE 1 END FROM ' . $r['table_name'] . ' WHERE id = ' . $_GET['id'])) {
					if (!$r['required']) $label .= draw_link(false, 'Clear Image', false, array(
						'class'=>'clear_img', 
						'data-table'=>$r['table_name'],
						'data-column'=>$r['field_name'],
						'data-id'=>$_GET['id'],
						'data-title'=>$r['title']
					));
				}

				if (admin(SESSION_ADMIN) && !empty($r['width']) && !empty($r['height'])) $label .= draw_link(false, 'Placekitten', false, array(
					'class'=>'placekitten',
					'data-width'=>$r['width'],
					'data-height'=>$r['height']
				));
				//todo form::set_field should support all these types
			} elseif ($r['type'] == 'text') {
				$maxlength = $r['width'];
			} elseif ($r['type'] == 'textarea') {
				$class = 'tinymce'; //tinymce is the official wysiwyg of the cms
				$maxlength = $r['width'];
				//add lorem ipsum generator to tinymce
				if (admin(SESSION_ADMIN)) {
					echo lib_get('lorem_ipsum');
					$label .= draw_link('#', 'Lorem Ipsum', false, array('class'=>'lorem_ipsum'));
				}
			}
			
			$f->set_field(array('name'=>$r['field_name'], 'type'=>$r['type'], 'class'=>$class, 'label'=>$label, 'required'=>$r['required'], 'additional'=>$additional, 'maxlength'=>$maxlength, 'preview'=>$preview));

			if ($languages) {
				$class = ($class) ? $class . ' translation' : 'translation';
				foreach ($languages as $key=>$lang) {
					if ($r['is_translated']) {
						$f->set_field(array('name'=>$r['field_name'] . '_' . $key, 'type'=>$r['type'], 'class'=>$class, 'label'=>$label . draw_span('translation', $lang), 'required'=>$r['required'], 'additional'=>$additional, 'maxlength'=>$maxlength, 'preview'=>$preview));
						$order[] = $r['field_name'] . '_' . $key;
					} else {
						$f->unset_fields($r['field_name'] . '_' . $key);
					}
				}
			}
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

if ($object['show_published']) $f->set_field(array('name'=>'is_published', 'type'=>'checkbox', 'value'=>$instance['is_published']));

//allow setting created / updated
if (admin(SESSION_ADMIN)) {
	$f->set_field(array('name'=>'created_user', 'type'=>'select', 'sql'=>'SELECT id, CONCAT(firstname, " ", lastname) FROM app_users ORDER BY lastname, firstname', 'required'=>true, 'value'=>$instance['created_user']));
	if ($editing) $f->set_field(array('name'=>'updated_user', 'type'=>'select', 'sql'=>'SELECT id, CONCAT(firstname, " ", lastname) FROM app_users ORDER BY lastname, firstname', 'required'=>true, 'value'=>user()));
}

$f->unset_fields('subsequence');
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
echo draw_div('panel', str_ireplace("\n", BR, $object['form_help']), false, (admin(SESSION_ADMIN) ? 'app_objects.form_help.' . $_GET['object_id'] : false));

echo drawLast();
?>