<?php
//define settings
if (!defined('CHAR_DELETE'))		define('CHAR_DELETE',		'&times;');
if (!defined('CHAR_UNDELETE'))		define('CHAR_UNDELETE',		'&curren;');
if (!defined('CHAR_SEPARATOR'))		define('CHAR_SEPARATOR',	'&nbsp;&raquo;&nbsp;');
if (!defined('COOKIE_KEY'))			define('COOKIE_KEY',		'cms_key');
if (!defined('DIRECTORY_BASE'))		define('DIRECTORY_BASE',	'/login/');
if (!defined('EMAIL_DEFAULT'))		define('EMAIL_DEFAULT',		'josh@bureaublank.com');
if (!defined('SESSION_USER_ID'))	define('SESSION_USER_ID',	'cms_user_id');
if (!defined('SESSION_ADMIN'))		define('SESSION_ADMIN',		'cms_is_admin');
if (!defined('SESSION_USER_NAME'))	define('SESSION_USER_NAME',	'cms_name');

extract(joshlib());

$schema = array(
	'app'=>array('link_color'=>'varchar', 'banner_image'=>'mediumblob'),
	'app_fields'=>array('object_id'=>'int', 'type'=>'varchar', 'title'=>'varchar', 'field_name'=>'varchar', 'visibility'=>'varchar', 'required'=>'tinyint', 'is_translated'=>'tinyint', 'related_field_id'=>'int', 'related_object_id'=>'int', 'width'=>'int', 'height'=>'int', 'additional'=>'text'),
	'app_languages'=>array('title'=>'varchar', 'code'=>'varchar', 'checked'=>'tinyint'),
	'app_objects'=>array('title'=>'varchar', 'table_name'=>'varchar', 'order_by'=>'varchar', 'direction'=>'varchar', 'group_by_field'=>'int', 'list_help'=>'text', 'form_help'=>'text', 'show_published'=>'tinyint', 'web_page'=>'varchar', 'list_grouping'=>'varchar'),
	'app_objects_links'=>array('object_id'=>'int', 'linked_id'=>'int'),
	'app_users'=>array('firstname'=>'varchar', 'lastname'=>'varchar', 'email'=>'varchar', 'password'=>'varchar', 'secret_key'=>'varchar', 'is_admin'=>'tinyint', 'last_login'=>'datetime'),
	'app_users_to_objects'=>array('user_id'=>'int', 'object_id'=>'int')	
);

$visibilty_levels = array('list'=>'Show in List', 'normal'=>'Normal', 'hidden'=>'Hidden');

if (url_action('show_deleted,hide_deleted') && admin(SESSION_ADMIN)) {
	$_SESSION['show_deleted'] = url_action('show_deleted');
	url_drop('action');
}

//languages
$languages = ($languages = db_table('SELECT code, title FROM app_languages WHERE checked = 1 ORDER BY title')) ? array_key_promote($languages) : false;

//sekurity
if (!user()) {
	if ($posting) {
		//logging in
		login($_POST['email'], $_POST['password']);
		url_change();
	} elseif (!empty($_COOKIE[COOKIE_KEY])) {
		login(false, false, false, $_COOKIE[COOKIE_KEY]);
	} elseif (!url_action('logout')) {
		//login form
		echo drawFirst();
		$f = new form('login', false, 'Log In');
		$f->set_field(array('type'=>'email', 'name'=>'email', 'value'=>@$_COOKIE['last_email'], 'required'=>true));
		$f->set_field(array('type'=>'password', 'name'=>'password', 'required'=>true));
		$f->set_focus(@$_COOKIE['last_email'] ? 'password' : 'email');
		echo $f->draw();
		echo drawLast();
		exit;
	}
}

if (url_action('logout')) {
	//logging out
	logout();
} elseif (url_action('download') && url_id()) {
	$object = db_grab('SELECT id, title, table_name, show_published, order_by, direction FROM app_objects WHERE is_active = 1 AND id = ' . $_GET['id']);
	$fields = db_table('SELECT type, title, field_name, related_object_id FROM app_fields WHERE object_id = ' . $object['id'] . ' AND is_active = 1 AND type NOT IN ("image", "image-alt", "file", "checkboxes", "textarea") ORDER BY precedence');
	
	//build sql select statement
	if (empty($object['order_by'])) $object['order_by'] = 'created_date';
	$object['order_by'] .= ($object['direction'] == 'DESC') ? ' DESC' : ' ASC';
	if ($object['show_published']) {
		$select = array($object['table_name'] . '.' . 'is_published');
		$object['show_published'] = ' AND ' . $object['table_name'] . '.' . 'is_published = 1';
	} else {
		$object['show_published'] = '';
	}
	foreach ($fields as $f) {
		$select[] = $object['table_name'] . '.' . $f['field_name'] . ' "' . $f['title'] . '"';
	}
	$select[] = $object['table_name'] . '.' . 'created_date';
	$select[] = $object['table_name'] . '.' . 'updated_date';

	$sql = 'SELECT ' . implode(',', $select) . ' FROM ' . $object['table_name'] . ' WHERE is_active = 1 ' . $object['show_published'] . ' ORDER BY ' . $object['order_by'];
	//die($sql);
	
	//run and process
	$result = db_table($sql);
	foreach ($result as &$r) {
		if ($object['show_published']) $r['is_published'] = format_boolean($r['is_published']);
	}
	
	
	file_download(file_array($result), $object['title'], 'xls');
}

function dbCheck() {
	global $schema;
	if (!db_schema_check($schema)) {
		if (db_grab('SELECT COUNT(*) FROM app_users WHERE email = "' . EMAIL_DEFAULT . '" AND is_active = 1')) {
			login(EMAIL_DEFAULT);
		} else {
			//create record
			$id = db_query('INSERT INTO app_users ( firstname, lastname, email, password, secret_key, is_admin, created_user, created_date, is_active ) VALUES ( "Josh", "Reisner", "' . EMAIL_DEFAULT . '", "dude", ' . db_key() . ', 1, 1, NOW(), 1 )');
			login(false, false, $id);
		}
		
		if (db_table_exists('app') && !db_grab('SELECT COUNT(*) FROM app')) db_save('app', false, array('link_color'=>'0c4b85', 'banner_image'=>file_get(str_replace($_SERVER['SCRIPT_NAME'], '/login/images/banner-cms.jpg', $_SERVER['SCRIPT_FILENAME']))), false);
		
		if (db_table_exists('app_languages') && !db_grab('SELECT COUNT(*) FROM app_languages'))  {
			db_save('app_languages', false, array('code'=>'fr', 'title'=>'Français'), false);
			db_save('app_languages', false, array('code'=>'it', 'title'=>'Italiano'), false);
			db_save('app_languages', false, array('code'=>'es', 'title'=>'Español'), false);
			db_save('app_languages', false, array('code'=>'pt', 'title'=>'Português'), false);
			db_save('app_languages', false, array('code'=>'ru', 'title'=>'Русский'), false);
			db_save('app_languages', false, array('code'=>'uk', 'title'=>'Українська'), false);
		}
		
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
		draw_css_src(DIRECTORY_BASE . 'css/global.css') .
		draw_css('a { color:#' . $app['link_color'] . '}')
	);
	
	if (user()) {
		$return .= '<body><div id="page">' . draw_div('#banner', draw_img(file_dynamic('app', 'banner_image', 1, 'jpg', $app['updated']), DIRECTORY_BASE));
		if (empty($_josh['request']['subfolder'])) {
			$return .= draw_h1('Objects');
		} else {
			$return .= draw_h1(draw_link(DIRECTORY_BASE, 'Objects') . CHAR_SEPARATOR . $title);
		}
	} else {
		$return .= '<body class="login">';
	}
	
	return $return;
}

function drawLast() {
	$return = '</div>' . 
		lib_get('jquery') . 
		draw_javascript_src(DIRECTORY_BASE . 'js/jquery-ui-1.8.9.custom.min.js') . 
		draw_javascript_src(DIRECTORY_BASE . 'js/jquery.ui.nestedSortable.js') .
		draw_javascript_src(DIRECTORY_BASE . 'js/global.js') . 
		draw_javascript_src() . 
		draw_google_analytics('UA-21096000-1') . 
	'</body></html>';
	return $return;
}

function drawObjectList($object_id, $from_type=false, $from_id=false, $from_ajax=false) {
	
	//get content
	if (!$object = db_grab('SELECT o.title, o.table_name, o.order_by, o.direction, o.show_published, o.group_by_field, (SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . ' AND u2o.object_id = o.id) permission FROM app_objects o WHERE o.id = ' . $object_id)) error_handle('This object does not exist', '', __file__, __line__);
	
	//security
	if (!$object['permission'] && !admin(SESSION_ADMIN)) return false;

	//define variables
	$selects = array(TAB . 't.id');
	$joins = $columns = $list = $rel_fields = $nav = $classes = array();
	$t = new table($object['table_name']);
	$where = $where_str = '';
	$nested = false;
	
	//start output with hidden fields for ajax
	$return	=	draw_form_hidden('table_name', $object['table_name']) . 
				draw_form_hidden('from_type', $from_type) . 
				draw_form_hidden('from_id', $from_id) .
				draw_form_hidden('object_id', $object_id); 
	
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
	
	//todo: figure out way to implement this
	//$f['order_by'] = 'TRIM(LEADING \'The \' FROM ' . $object['order_by'] . ')';
	
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
				f.related_object_id,
				f.width
			FROM app_fields f 
			LEFT JOIN app_objects o ON o.id = f.related_object_id
			WHERE f.is_active = 1 AND f.type NOT IN ("checkboxes", "file") AND f.object_id = ' . $object_id . ' 
			ORDER BY f.precedence');
	foreach ($fields as &$f) {
		if ($f['visibility'] == 'list') {
			$columns[] = $f;
			if (($f['type'] == 'date') || ($f['type'] == 'datetime')) {
				$t->set_column($f['field_name'], 'r ' . $f['type'], $f['title']);
			} elseif (($f['type'] == 'file-type') || ($f['type'] == 'file-type')) {
				$t->set_column($f['field_name'],'l ' . $f['type'], '&nbsp;');
			} elseif (($f['type'] == 'image') && ($f['type'] == 'image-alt')) {
				$t->set_column($f['field_name'],'l ' . $f['type'], $f['title']);
			} else {
				$t->set_column($f['field_name'], 'l ' . $f['type'], $f['title']);
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
					//figure out which column to group by and label it group.  skip this if it's the from_type
					$selects[] = TAB . $f['related_table'] . '.' . $rel_fields[$f['id']] . ' "group"';
					$joins[] = 'LEFT JOIN ' . $f['related_table'] . ' ON ' . $f['related_table'] . '.id = t.' . $f['field_name'];
		
					//also figure out which column to order the group by and put it before the regular order by
					$rel_order = db_grab('SELECT o.table_name, o.order_by, o.direction FROM app_objects o JOIN app_fields f ON f.related_object_id = o.id WHERE f.id = ' . $f['id']);
					if (empty($rel_order['order_by'])) $rel_order['order_by'] = 'created_date'; //might be blank, go with default
					$object['order_by'] = $rel_order['table_name'] . '.' . $rel_order['order_by'] . ' ' . $rel_order['direction'] . ', ' . $object['order_by'];
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
	
	//die('<hr>' . nl2br($sql));
	
	//set up nav
	if (admin(SESSION_ADMIN)) {
		if (!$from_type) {
			$nav[] = draw_link(DIRECTORY_BASE . 'edit/?id=' . $object_id, 'Object Settings');
			$classes[] = 'settings';
			$nav[] = draw_link(DIRECTORY_BASE . 'object/fields/?id=' . $object_id, 'Fields');
			$classes[] = 'fields';
		}
		if ($deleted = db_grab($del_sql)) {
			if ($_SESSION['show_deleted']) {
				$nav[] = draw_link(url_action_add('hide_deleted'), 'Hide ' . format_title(format_quantity($deleted)) . ' Deleted');
			} else {
				$nav[] = draw_link(url_action_add('show_deleted'), 'Show ' . format_title(format_quantity($deleted)) . ' Deleted');
			}
			$classes[] = 'toggle_deleted';
		}
		$nav[] = draw_link(false, 'Show SQL');
		$classes[] = 'sql';
		$return .= draw_container('textarea', $sql, array('id'=>'sql', 'style'=>'display:none;')); //todo disambiguate
	}
	$nav[] = draw_link(url_query_add(array('action'=>'download', 'id'=>$object_id), false), file_icon('xls') . 'Download');
	$classes[] = 'download';
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
				} elseif ($f['type'] == 'file-size') {
					$r[$f['field_name']] = format_size($r[$f['field_name']]);
				} elseif ($f['type'] == 'file-type') {
					$r[$f['field_name']] = file_icon($r[$f['field_name']]);
				} elseif (($f['type'] == 'image') || ($f['type'] == 'image-alt')) {
					$img = file_dynamic($object['table_name'], $f['field_name'], $r['id'], 'jpg', $r['updated']);
					$max = (!empty($f['width']) && ($f['width'] < 60)) ? $f['width'] : 60;
					$r[$f['field_name']] = draw_img_thumbnail($img, DIRECTORY_BASE . 'object/edit/?id=' . $r['id'] . '&object_id=' . $object_id, $max);
				} elseif ($f['type'] == 'select') {
					$r[$f['field_name']] = $r[$rel_fields[$f['id']]];
				} elseif (($f['type'] == 'textarea') || ($f['type'] == 'textarea-plain')) {
					$r[$f['field_name']] = format_string(strip_tags($r[$f['field_name']]), 50);
				} elseif (($f['type'] == 'text') || ($f['type'] == 'email')) {
					$r[$f['field_name']] = format_string(strip_tags($r[$f['field_name']]), 50);
				} else {
					$r[$f['field_name']] = 'unhandled type';
				}
				if (!$linked) {
					if (empty($r[$f['field_name']]) && ($f['type'] != 'file-type') && ($f['type'] != 'image') && ($f['type'] != 'image-alt')) $r[$f['field_name']] = draw_div('empty', 'No ' . $f['title'] . ' entered');
					$r[$f['field_name']] = draw_link($link, $r[$f['field_name']]);
					if (($f['type'] != 'file-type') && ($f['type'] != 'image') && ($f['type'] != 'image-alt')) $linked = true; //just linking the image isn't enough visually
				}
			}
			$r['updated'] = draw_span('light', ($r['updated_user'] ? $r['updated_user'] : $r['created_user'])) . ' ' . format_date($r['updated'], '', '%b %d, %Y', true, true);
			if (!$r['is_active']) {
				array_argument($r, 'deleted');
				$r['delete'] = draw_link(false, CHAR_UNDELETE, false, array('class'=>'delete',  'data-id'=>$r['id']));
			} else {
				$r['delete'] = draw_link(false, CHAR_DELETE, false, array('class'=>'delete',  'data-id'=>$r['id']));
			}
		}
	}
	
	//draw table or list
	if ($nested && $orderingByPrecedence) {
		$return .= draw_form_hidden('nesting_column', $nested) . nestedList($list, $object['table_name'], 'nested');
	} else {
		$return .= $t->draw($rows, 'No ' . strToLower($object['title']) . ' have been added' . $where_str . ' yet.');
	}
	
	//wrap non-ajax output in a div (whose contents can be replaced via ajax)
	if (!$from_ajax) $return = draw_div('object_list', $return);
	
	return $return;
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

function login($email=false, $password=false, $id=false, $secret_key=false) {
	if ($secret_key) {
		//logging in via cookie
		$where = 'secret_key = "' . $secret_key . '"';
	} elseif ($id) {
		//logging in after just creating database
		$where = 'id = ' . $id;
	} elseif ($password) {
		//logging in via form
		$where = 'email = "' . $email . '" AND password = "' . $password . '"';
	} else {
		//logging in via database tweak
		$where = 'email = "' . $email . '"';
	}
	if ($r = db_grab('SELECT id, firstname, lastname, email, secret_key, is_admin FROM app_users WHERE ' . $where . ' AND is_active = 1')) {
		//good login, set session and cookies
		$_SESSION[SESSION_USER_ID]		= $r['id'];
		$_SESSION['show_deleted']	= false;
		$_SESSION[SESSION_USER_NAME]	= $r['firstname'];
		$_SESSION['full_name']		= $r['firstname'] . ' ' . $r['lastname'];
		$_SESSION['email']			= $r['email'];
		$_SESSION[SESSION_ADMIN]	= $r['is_admin'];
		$_SESSION['isLoggedIn']		= true;
		cookie('last_email', strToLower($r['email']));
		cookie(COOKIE_KEY, $r['secret_key']);
		db_query('UPDATE app_users SET last_login = NOW() WHERE id = ' . $r['id']);
		return true;
	}
	logout();
}

function logout() {
	cookie(COOKIE_KEY);
	$_SESSION[SESSION_USER_ID]	= false;
	$_SESSION['isLoggedIn']	= false;
	url_change(((isset($_GET['return_to'])) ? $_GET['return_to'] : DIRECTORY_BASE));
}

function nestedList($object_values, $table_name, $class=false, $level=1) {
	
	if (!count($object_values)) return false;
	
	$classes = array();
	
	foreach ($object_values as &$o) {
		$classes[] = array('data-id'=>$o['id'], 'class'=>($o['is_active'] ? '' : 'deleted'));
		
		if (!$o['is_active']) {
			//$classes[] = 'deleted';
			$o['delete'] = draw_link(false, CHAR_UNDELETE, false, array('class'=>'delete', 'data-id'=>$o['id']));
		} else {
			$o['delete'] = draw_link(false, CHAR_DELETE, false, array('class'=>'delete', 'data-id'=>$o['id']));
		}

		$o = draw_div('#item_' . $o['id'], 
			draw_div('column published', draw_form_checkbox('chk_' . str_replace('_', '-', $table_name) . '_' . $o['id'], $o['is_published'], false, 'ajax_publish(this)')) .
			draw_div('column link', draw_link($o['url'], $o['title'])) . 
			draw_div('column updated', draw_span('light', $o['updated_user']) . ' ' . format_date($o['updated'])) .
			draw_div('column delete', $o['delete'])
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