<?php
session_start();
extract(joshlib());

//define vars
if (!defined('CHAR_DELETE')) define('CHAR_DELETE', '&times;');
if (!defined('CHAR_UNDELETE')) define('CHAR_UNDELETE', '&curren;');
if (!defined('DIRECTORY_BASE')) define('DIRECTORY_BASE', '/login/');

$schema = array(
	'app'=>array('link_color'=>'varchar', 'banner_image'=>'mediumblob'),
	'app_fields'=>array('object_id'=>'int', 'type'=>'varchar', 'title'=>'varchar', 'field_name'=>'varchar', 'visibility'=>'varchar', 'required'=>'tinyint', 'related_field_id'=>'int', 'related_object_id'=>'int', 'width'=>'int', 'height'=>'int', 'additional'=>'text'),
	'app_languages'=>array('title'=>'varchar', 'code'=>'varchar', 'checked'=>'tinyint', 'committed'=>'tinyint'),
	'app_objects'=>array('title'=>'varchar', 'table_name'=>'varchar', 'order_by'=>'varchar', 'direction'=>'varchar', 'group_by_field'=>'int', 'list_help'=>'text', 'form_help'=>'text', 'show_published'=>'tinyint', 'web_page'=>'varchar'),
	'app_objects_links'=>array('object_id'=>'int', 'linked_id'=>'int'),
	'app_users'=>array('firstname'=>'varchar', 'lastname'=>'varchar', 'email'=>'varchar', 'password'=>'varchar', 'secret_key'=>'varchar', 'is_admin'=>'tinyint'),
	'app_users_to_objects'=>array('user_id'=>'int', 'object_id'=>'int')	
);

$visibilty_levels = array('list'=>'Show in List', 'normal'=>'Normal', 'hidden'=>'Hidden');

if (url_action('show_deleted,hide_deleted') && admin()) {
	$_SESSION['show_deleted'] = url_action('show_deleted');
	url_drop('action');
}

//sekurity
if (!user()) {
	if ($posting) {
		//logging in
		if ($r = db_grab('SELECT id, firstname, lastname, email, secret_key, is_admin FROM app_users WHERE email = "' . $_POST['email'] . '" AND password = "' . $_POST['password'] . '" AND is_active = 1')) {
			//good login, set session and cookies
			$_SESSION['user_id']	= $r['id'];
			$_SESSION['show_deleted'] = false;
			$_SESSION['name']		= $r['firstname'];
			$_SESSION['full_name']	= $r['firstname'] . ' ' . $r['lastname'];
			$_SESSION['email']		= $r['email'];
			$_SESSION['is_admin']	= $r['is_admin'];
			$_SESSION['isLoggedIn']	= true;
			cookie('last_email', strToLower($_POST['email']));
			if (!empty($_POST['remember_me'])) cookie('secret_key', $r['secret_key']);
		}
		url_change();
	} elseif (!empty($_COOKIE['secret_key']) && $r = db_grab('SELECT id, firstname, lastname, email, secret_key, is_admin FROM app_users WHERE secret_key = "' . $_COOKIE['secret_key'] . '" AND is_active = 1')) {
		$_SESSION['user_id']	= $r['id'];
		$_SESSION['show_deleted'] = false;
		$_SESSION['name']		= $r['firstname'];
		$_SESSION['full_name']	= $r['firstname'] . ' ' . $r['lastname'];
		$_SESSION['email']		= $r['email'];
		$_SESSION['is_admin']	= $r['is_admin'];
		$_SESSION['isLoggedIn']	= true;
	} else {
		//login form
		echo drawFirst();
		$f = new form('login', false, 'Log In');
		$f->set_field(array('type'=>'text', 'name'=>'email', 'value'=>@$_COOKIE['last_email']));
		$f->set_field(array('type'=>'password', 'name'=>'password'));
		$f->set_field(array('type'=>'checkbox', 'name'=>'remember_me', 'default'=>true));
		echo $f->draw();
		echo drawLast();
		exit;
	}
} elseif (url_action('logout')) {
	//logging out
	$_SESSION['user_id']	= false;
	$_SESSION['isLoggedIn']	= false;
	cookie('secret_key');
	url_drop('action');
}

function dbCheck() {
	global $schema;
	if (!db_schema_check($schema)) {
		
		//log in the current user	
		$_SESSION['user_id']		= db_query('INSERT INTO app_users ( firstname, lastname, email, password, secret_key, is_admin, created_user, created_date, is_active ) VALUES ( "Josh", "Reisner", "josh@bureaublank.com", "dude", ' . db_key() . ', 1, 1, NOW(), 1 )');
		$_SESSION['name']			= 'Josh';
		$_SESSION['full_name']		= 'Josh Reisner';
		$_SESSION['email']			= 'josh@joshreisner.com';
		$_SESSION['is_admin']		= true;
		$_SESSION['isLoggedIn']		= true;
		$_SESSION['show_deleted']	= false;
		cookie('last_email', 'josh@joshreisner.com');
		cookie('secret_key', db_grab('SELECT secret_key FROM app_users WHERE id = 1'));
		
		db_save('app', false, array('link_color'=>'0c4b85', 'banner_image'=>file_get(str_replace($_SERVER['SCRIPT_NAME'], '/login/images/banner-cms.jpg', $_SERVER['SCRIPT_FILENAME']))));
		
		db_save('app_languages', false, array('code'=>'fr', 'title'=>'Français'));
		db_save('app_languages', false, array('code'=>'it', 'title'=>'Italiano'));
		db_save('app_languages', false, array('code'=>'es', 'title'=>'Español'));
		db_save('app_languages', false, array('code'=>'pt', 'title'=>'Português'));
		db_save('app_languages', false, array('code'=>'ru', 'title'=>'Русский'));
		db_save('app_languages', false, array('code'=>'uk', 'title'=>'Українська'));
		
		url_change(DIRECTORY_BASE);
	}

	//CMS 
	return true;
}

function drawFirst($title='CMS') {
	global $_josh;
	if (!$app = db_grab('SELECT link_color, ' . db_updated() . ' FROM app WHERE id = 1')) $app = array();
	if (empty($app['link_color'])) $app['link_color'] = '336699';
	if (empty($app['updated'])) $app['updated'] = 0;
	$return = draw_doctype() . draw_container('head',
		draw_meta_utf8() .
		draw_title($title) . 
		draw_css_src(DIRECTORY_BASE . 'styles/global.css') .
		draw_css('a { color:#' . $app['link_color'] . '}')
	);
	
	if (user()) {
		$return .= '<body><div id="page">' . draw_div('banner', draw_img(file_dynamic('app', 'banner_image', 1, 'jpg', $app['updated']), DIRECTORY_BASE));
		if (empty($_josh['request']['subfolder'])) {
			$return .= '<h1>CMS</h1>';
		} else {
			$return .= '<h1>' . draw_link(DIRECTORY_BASE, 'CMS') . ' &gt; ';
			$return .= $title . '</h1>';
		}
	} else {
		$return .= '<body class="login">';
	}
	
	return $return;
}

function drawLast() {
	$return = '</div>' . 
		lib_get('jquery') . 
		draw_javascript_src(DIRECTORY_BASE . 'scripts/jquery-ui-1.8.9.custom.min.js') . 
		draw_javascript_src(DIRECTORY_BASE . 'scripts/jquery.ui.nestedSortable.js') .
		draw_javascript_src(DIRECTORY_BASE . 'scripts/global.js') . 
		draw_javascript_src() . 
		draw_google_analytics('UA-21096000-1') . 
	'</body></html>';
	return $return;
}

function drawObjectList($object_id, $from_type=false, $from_id=false) {
	
	//get content
	if (!$object = db_grab('SELECT o.title, o.table_name, o.order_by, o.direction, o.show_published, o.group_by_field, (SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . ' AND u2o.object_id = o.id) permission FROM app_objects o WHERE o.id = ' . $object_id)) error_handle('This object does not exist', '', __file__, __line__);
	
	//security
	if (!$object['permission'] && !admin()) return false;

	//define variables
	$selects	= array(TAB . 't.id');
	$joins = $columns = $list = $rel_fields = $nav = $classes = array();
	$t			= new table($object['table_name']);
	$where		= $where_str = '';
	$nested		= false;
	$return		= draw_form_hidden('table_name', $object['table_name']); //need this for nested reorder ajax
	
	//handle draggy or default sort
	if ($object['order_by'] == 'precedence') {
		$t->set_column('draggy', 'icon', '&nbsp;');
		$orderingByPrecedence = true;
	} else {
		$orderingByPrecedence = false;
		if (empty($object['order_by'])) $object['order_by'] = 'created_date';
		$object['order_by'] .= (empty($object['direction'])) ? ' DESC' : ' ' . $object['direction'];
	}
	$object['order_by'] = 't.' . $object['order_by'];
	
	//add publish checkbox
	if ($object['show_published']) {
		$t->set_column('is_published', 'checkbox', '&nbsp;');
		$selects[] = TAB . 't.is_published';
	}
	
	//set up sql, start by getting fields
	$fields = db_table('SELECT 
				f.id,
				f.title, 
				f.field_name, 
				f.type, 
				f.visibility,
				o.table_name related_table,
				o.title object_title,
				f.related_object_id
			FROM app_fields f 
			LEFT JOIN app_objects o ON o.id = f.related_object_id
			WHERE f.is_active = 1 AND f.type NOT IN ("checkboxes", "file") AND f.object_id = ' . $object_id . ' 
			ORDER BY f.precedence');
	foreach ($fields as &$f) {
		if ($f['visibility'] == 'list') {
			$columns[] = $f;
			if (($f['type'] == 'date') || ($f['type'] == 'datetime')) {
				$t->set_column($f['field_name'], 'r', $f['title']);
			} elseif ($f['type'] == 'file-type') {
				$t->set_column($f['field_name'],'l', '&nbsp;');
			} elseif (($f['type'] == 'image') && ($f['type'] == 'image-alt')) {
				$t->set_column($f['field_name'],'l', $f['title']);
			} else {
				$t->set_column($f['field_name'], 'l', $f['title']);
			}
		}
		
		if ($f['type'] != 'image') $selects[] = TAB . 't.' . $f['field_name'];
		
		if ($f['type'] == 'select') {
			
			//need transpose field for select groupings and columns
			$rel_fields[$f['id']] = db_grab('SELECT f1.field_name FROM app_fields f1 JOIN app_fields f2 ON f1.object_id = f2.related_object_id WHERE f2.id = ' . $f['id'] . ' AND f1.visibility = "list" AND f1.type NOT IN ("textarea", "int", "image", "image-alt") AND f1.is_active = 1 ORDER BY f1.precedence');
			
			//handle select groupings
			if ($f['id'] == $object['group_by_field']) {
				if ($f['related_object_id'] == $object_id) {
					//nested object
					$nested = $f['field_name']; //might need this later?
					$selects[] = TAB . 't.precedence';
					$selects[] = TAB . 't.' . $f['field_name'];
				} elseif ($f['related_object_id'] != $from_type) {
					//skip this if it's the from_type
					//figure out which column to group by and label it group
					$more = db_columns($f['related_table'], true);
					foreach ($more as $m) $selects[] = TAB . $f['related_table'] . '.' . $m['name'] . ' ' . (($m['name'] == $rel_fields[$f['id']]) ? '"group"' : $f['related_table'] . '_' . $m['name']);
					$joins[] = 'LEFT JOIN ' . $f['related_table'] . ' ON ' . $f['related_table'] . '.id = t.' . $f['field_name'];
		
					//also figure out which column to order the group by and put it before the regular order by
					$rel_order = db_grab('SELECT o.table_name, o.order_by, o.direction FROM app_objects o JOIN app_fields f ON f.related_object_id = o.id WHERE f.id = ' . $f['id']);
					if (empty($rel_order['order_by'])) $rel_order['order_by'] = 'created_date'; //might be blank, go with default
					$object['order_by'] = $rel_order['table_name'] . '.' . $rel_order['order_by'] . ' ' . $rel_order['direction'] . ', ' . $object['order_by'];
					//die('hi');
				} else {
					//filter down to show just this group, because we're on the group's object page
					$joins[] = 'JOIN ' . $f['related_table'] . ' ON ' . $f['related_table'] . '.id = t.' . $f['field_name'];
					$where = $f['related_table'] . '.id = ' . $from_id;
					$where_str = ' to this ' . strToLower(format_singular($f['object_title']));
				}

				$rel_fields[$f['id']] = 'group';			
			} else {
				$more = db_columns($f['related_table'], true);
				foreach ($more as $m) $selects[] = TAB . $f['related_table'] . '.' . $m['name'] . ' ' . $f['related_table'] . '_' . $m['name'];
				$joins[] = 'LEFT JOIN ' . $f['related_table'] . ' ON ' . $f['related_table'] . '.id = t.' . $f['field_name'];

				//add table prefix to the transpose
				$rel_fields[$f['id']] = $f['related_table'] . '_' . $rel_fields[$f['id']];
			}
			
		}
	}
	$joins[] = 'LEFT JOIN app_users u1 ON t.created_user = u1.id'; //might be user-less (might have been generated from a web page for ex)
	$joins[] = 'LEFT JOIN app_users u2 ON t.updated_user = u2.id';
	$selects[] = TAB . db_updated('t');
	$selects[] = TAB . 'u1.firstname created_user';
	$selects[] = TAB . 'u2.firstname updated_user';
	$selects[] = TAB . 't.is_active';
	
	//for statement below
	$del_sql = implode(NEWLINE, array('SELECT COUNT(*) FROM ' . $object['table_name'] . ' t', implode(NEWLINE, $joins), 'WHERE t.is_active = 0' . (empty($where) ? '' : ' AND ') . $where, 'ORDER BY ' . $object['order_by']));
	
	if (!$_SESSION['show_deleted']) $where = 't.is_active = 1' . (empty($where) ? '' : ' AND ') . $where;
	if (!empty($where)) $where = 'WHERE ' . $where;
	$sql = implode(NEWLINE, array('SELECT', implode(',' . NEWLINE, $selects), 'FROM ' . $object['table_name'] . ' t', implode(NEWLINE, $joins), $where, 'ORDER BY ' . $object['order_by']));

	//testing
	//die(draw_container('pre', $sql));
	
	//set up nav
	if (admin()) {
		if (!$from_type) {
			$nav[] = draw_link(DIRECTORY_BASE . 'edit/?id=' . $_GET['id'], 'Object Settings');
			$classes[] = 'settings';
			$nav[] = draw_link(DIRECTORY_BASE . 'object/fields/?id=' . $_GET['id'], 'Fields');
			$classes[] = 'fields';
		}
		if ($deleted = db_grab($del_sql)) {
			if ($_SESSION['show_deleted']) {
				$nav[] = draw_link(url_action_add('hide_deleted'), 'Hide ' . format_quantity($deleted) . ' Deleted');
			} else {
				$nav[] = draw_link(url_action_add('show_deleted'), 'Show ' . format_quantity($deleted) . ' Deleted');
			}
			$classes[] = 'deleted';
		}
		$nav[] = draw_link(false, 'Show SQL');
		$classes[] = 'sql';
		$return .= draw_container('textarea', $sql, array('id'=>'sql', 'style'=>'display:none;')); //todo disambiguate
	}
	if ($from_type && $from_id) {
		//we're going to pass this stuff so the add new page can have this field as a hidden value rather than a select
		$nav[] = draw_link(DIRECTORY_BASE . 'object/edit/?object_id=' . $object_id . '&from_type=' . $from_type . '&from_id=' . $from_id, 'Add New');
	} else {
		$nav[] = draw_link(DIRECTORY_BASE . 'object/edit/?object_id=' . $object_id, 'Add New');
	}
	$classes[] = 'new';
	$return = draw_list($nav, 'nav', 'ul', false, $classes) . $return; //todo pass $classes to draw_nav
		
	$t->set_column('updated', 'r', 'Updated', 120);
	$t->set_column('delete', 'delete', '&nbsp;', 20);
	
	//get rows, iterate
	$rows = db_table($sql);
	foreach($rows as &$r) {
		$link = DIRECTORY_BASE . 'object/edit/?id=' . $r['id'] . '&object_id=' . $object_id;
		if ($nested && $orderingByPrecedence) {
			//do nesty things (see test/sortable.php for a simpler version of this)
			if (empty($r['updated_user'])) $r['updated_user'] = $r['created_user'];
			$r['children'] = array();
			$r['url'] = $link;
			if (empty($r[$nested])) { //nested is for ex parent_id
				$list[] = $r;
			} elseif (nestedNodeExists($list, $r[$nested], $r)) {
				//attached child to parent node
			} else {
				//an error occurred, because a parent exists but is not in the tree
			}
		} else {
			if ($orderingByPrecedence) $r['draggy'] = '&nbsp;';
			if ($object['show_published']) $r['is_published'] = draw_form_checkbox('chk_' . str_replace('_', '-', $object['table_name']) . '_' . $r['id'], $r['is_published'], false, 'ajax_publish(this);');
			$linked = false;
			foreach ($columns as $f) {
				if ($f['type'] == 'checkbox') {
					$r[$f['field_name']] = format_boolean($r[$f['field_name']]);
				} elseif ($f['type'] == 'date') {
					$r[$f['field_name']] = format_date($r[$f['field_name']]);
				} elseif ($f['type'] == 'datetime') {
					$r[$f['field_name']] = format_date_time($r[$f['field_name']]);
				} elseif ($f['type'] == 'file-type') {
					$r[$f['field_name']] = file_icon($r[$f['field_name']]);
				} elseif (($f['type'] == 'image') || ($f['type'] == 'image-alt')) {
					$img = file_dynamic($object['table_name'], $f['field_name'], $r['id'], 'jpg', $r['updated']);
					$r[$f['field_name']] = draw_img_thumbnail($img, DIRECTORY_BASE . 'object/edit/?id=' . $r['id'] . '&object_id=' . $object_id, 60);
				} elseif ($f['type'] == 'select') {
					$r[$f['field_name']] = $r[$rel_fields[$f['id']]];
				} elseif ($f['type'] == 'textarea') {
					$r[$f['field_name']] = format_string(strip_tags($r[$f['field_name']]), 50);
				} elseif ($f['type'] == 'text') {
					//$r[$f['field_name']] = 'text';
					//$r[$f['field_name']] = format_string($r[$f['field_name']], 50);
				} else {
					$r[$f['field_name']] = 'unhandled type';
				}
				if (!$linked) {
					if (empty($r[$f['field_name']])) $r[$f['field_name']] = draw_div_class('empty', 'No ' . $f['title'] . ' entered');
					$r[$f['field_name']] = draw_link($link, $r[$f['field_name']]);
					if (($f['type'] != 'file-type') && ($f['type'] != 'image') && ($f['type'] != 'image-alt')) $linked = true; //just linking the image isn't enough visually
				}
			}
			$r['updated'] = draw_span('light', ($r['updated_user'] ? $r['updated_user'] : $r['created_user'])) . ' ' . format_date($r['updated'], '', '%b %d, %Y', true, true);
			if (!$r['is_active']) {
				array_argument($r, 'deleted');
				$r['delete'] = draw_link(false, CHAR_UNDELETE, false, array('class'=>'delete', 'rel'=>$object_id . '-' . $r['id']));
			} else {
				$r['delete'] = draw_link(false, CHAR_DELETE, false, array('class'=>'delete', 'rel'=>$object_id . '-' . $r['id']));
			}
		}
	}
	
	if ($nested && $orderingByPrecedence) {
		return $return . draw_form_hidden('nesting_column', $nested) . nestedList($list, $object['table_name'], 'nested');
	} else {
		return $return . $t->draw($rows, 'No ' . strToLower($object['title']) . ' have been added' . $where_str . ' yet.');
	}
}

function getNewObjectName($table, $field=false) {
	//recursively find the next available code-formatted table (or column) name.  eg testing, testing_1, testing_2, etc.
	$table = format_text_code($table);

	if ($field) {
		$field = format_text_code($field);
		if (!db_column_exists($table, $field)) return $field;
		$parts = explode('_', $field);
	} else {
		if (!db_table_exists($table)) return $table;
		$parts = explode('_', $table);
	}
	
	//object already exists, so increment name
	$count = count($parts);
	if (format_check($parts[$count - 1])) {
		$parts[$count - 1] = $parts[$count - 1] + 1;
	} else {
		$parts[] = 1;
	}
	
	if ($field) {
		$field = implode('_', $parts);
	} else {
		$table = implode('_', $parts);
	}

	//iterate
	return getNewObjectName($table, $field);
}

function joshlib() {
	//look for joshlib at joshlib/index.php, ../joshlib/index.php, all the way down
	global $_josh;
	$count = substr_count($_SERVER['DOCUMENT_ROOT'] . $_SERVER['SCRIPT_NAME'], '/');
	for ($i = 0; $i < $count; $i++) if (@include(str_repeat('../', $i) . 'joshlib/index.php')) return $_josh;
	die('Could not find Joshlib.');
}

function nestedList($object_values, $table_name, $class=false, $level=1) {
	
	if (!count($object_values)) return false;
	
	$classes = array();
	
	foreach ($object_values as &$o) {
		//die(draw_array($o));
		$classes[] = array('data-id'=>$o['id']);
		$o = draw_div('item_' . $o['id'], 
			draw_div_class('column published', draw_form_checkbox('chk_' . str_replace('_', '-', $table_name) . '_' . $o['id'], $o['is_published'], false, 'ajax_publish(this)')) .
			draw_div_class('column link', draw_link($o['url'], $o['title'])) . 
			draw_div_class('column updated', draw_span('light', $o['updated_user']) . ' ' . format_date($o['updated'])) .
			draw_div_class('column delete', draw_link(false, CHAR_DELETE))
		, array('class'=>'row level_' . $level)) . nestedList($o['children'], $table_name, false, ($level + 1));
	}

	return draw_list($object_values, $class, 'ul', false, $classes);
}

function nestedNodeExists(&$array, $parent_id, $child) {
	foreach ($array as &$a) {
		if ($a['id'] == $parent_id) {
			$a['children'][] = $child;
			return true;
		} elseif (count($a['children']) && nestedNodeExists($a['children'], $parent_id, $child)) {
			return true;
		}
	}
	return false;
}

function nestedTreeRebuild($table, $nesting_column='parent_id', $parent_id=false, $left=0) {
	//the right value of this node (in case there are no children) is + 1
	$right = $left + 1;
	
	//get all children of this node   
	$ids = db_array('SELECT id FROM ' . $table . ' WHERE ' . $nesting_column . ' ' . (($parent_id) ? '= ' . $parent_id : 'IS NULL') . ' AND is_active = 1 ORDER BY precedence ASC');

	//recursive execution of this function for each child of this node   
	//$right is the current right value, which is incremented by recursive calls to this function
	foreach ($ids as $id) $right = nestedTreeRebuild($table, $nesting_column, $id, $right);   
	
	//we've got the left value, and now that we've processed the children of this node we also know the right value   
	if ($parent_id) db_query('UPDATE ' . $table . ' SET precedence = ' . $left . ', subsequence = ' . $right . ' WHERE id = ' . $parent_id);   
	
	//return the right value of this node + 1   
	return $right + 1;
}