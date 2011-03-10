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
		echo drawTop();
		$f = new form('login', false, 'Log In');
		$f->set_field(array('type'=>'text', 'name'=>'email', 'value'=>@$_COOKIE['last_email']));
		$f->set_field(array('type'=>'password', 'name'=>'password'));
		$f->set_field(array('type'=>'checkbox', 'name'=>'remember_me', 'default'=>true));
		echo $f->draw();
		echo drawBottom();
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
		
		db_schema_check(array( 	//adding these by default
			'user_pages'=>array('title'=>'varchar', 'url'=>'varchar', 'content'=>'text', 'meta_description'=>'varchar', 'meta_keywords'=>'varchar', 'parent_id'=>'int', 'subsequence'=>'int'),
			'user_snippets'=>array('title'=>'varchar', 'content'=>'varchar')
		));
		
		//log in the current user	
		$_SESSION['user_id']		= db_query('INSERT INTO app_users ( firstname, lastname, email, password, secret_key, is_admin, created_user, created_date, is_active ) VALUES ( "Josh", "Reisner", "josh@joshreisner.com", "dude", ' . db_key() . ', 1, 1, NOW(), 1 )');
		$_SESSION['name']			= 'Josh';
		$_SESSION['full_name']		= 'Josh Reisner';
		$_SESSION['email']			= 'josh@joshreisner.com';
		$_SESSION['is_admin']		= true;
		$_SESSION['isLoggedIn']		= true;
		$_SESSION['show_deleted']	= false;
		cookie('last_email', 'josh@joshreisner.com');
		cookie('secret_key', db_grab('SELECT secret_key FROM app_users WHERE id = 1'));
	
		//initially populate with pages, sections, snippets
		$object_id = db_save('app_objects', false, array('title'=>'Pages', 'table_name'=>'user_pages', 'show_published'=>1, 'group_by_field'=>3, 'order_by'=>'precedence'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>1));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'URL', 'field_name'=>'url', 'visibility'=>'normal', 'related_object_id'=>2, 'required'=>1, 'additional'=>'eg /about/'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'select', 'title'=>'Parent', 'field_name'=>'parent_id', 'visibility'=>'normal', 'required'=>0, 'related_object_id'=>1));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'textarea', 'title'=>'Content', 'field_name'=>'content', 'visibility'=>'normal', 'required'=>0));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Meta Description', 'field_name'=>'meta_description', 'visibility'=>'normal', 'required'=>0, 'additional'=>'for search engines'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Meta Keywords', 'field_name'=>'meta_keywords', 'visibility'=>'normal', 'required'=>0));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'int', 'title'=>'Subsequence', 'field_name'=>'subsequence', 'visibility'=>'hidden', 'required'=>0));
		db_save('user_pages', false, array('title'=>'Home', 'url'=>'/', 'content'=>'<p>Pellentesque amet massa mauris justo vitae mauris maecenas nam ligula nulla pellentesque arcu ornare. Ornare integer orci eget integer proin porta quisque cursus eu sit malesuada maecenas eu amet auctor morbi. Mattis pellentesque a molestie auctor commodo ultricies enim a commodo nam commodo nulla cursus orci risus sagittis massa porttitor eros enim proin vivamus. Justo curabitur ornare porttitor molestie at odio magna lorem morbi sit tellus at gravida curabitur donec tempus urna ultricies molestie. Vivamus integer orci eros tellus quam mattis molestie quam maecenas vitae sed. Orci nulla porta et ultricies risus adipiscing nibh maecenas metus sed quam sed pellentesque vitae odio donec sit ornare massa ultricies eros.</p><p>Molestie malesuada risus et ornare metus fusce quisque leo lorem quam proin congue a. Non sagittis magna diam curabitur nulla a molestie ipsum in duis risus porttitor risus ultricies leo pharetra. Justo proin lorem odio at non ipsum diam bibendum orci diam leo nulla. Bibendum commodo auctor curabitur bibendum pellentesque vivamus mattis eget fusce nibh donec pharetra orci arcu. Integer eros integer et a arcu pharetra elementum diam pellentesque integer vivamus ut odio sodales ut magna duis congue malesuada. Diam congue elementum sodales porta auctor arcu leo porttitor amet massa vitae sapien lorem.</p>', 'is_published'=>1));
		db_save('user_pages', false, array('title'=>'About Us', 'parent_id'=>1, 'url'=>'/about/', 'content'=>'<p>Pellentesque amet massa mauris justo vitae mauris maecenas nam ligula nulla pellentesque arcu ornare. Ornare integer orci eget integer proin porta quisque cursus eu sit malesuada maecenas eu amet auctor morbi. Mattis pellentesque a molestie auctor commodo ultricies enim a commodo nam commodo nulla cursus orci risus sagittis massa porttitor eros enim proin vivamus. Justo curabitur ornare porttitor molestie at odio magna lorem morbi sit tellus at gravida curabitur donec tempus urna ultricies molestie. Vivamus integer orci eros tellus quam mattis molestie quam maecenas vitae sed. Orci nulla porta et ultricies risus adipiscing nibh maecenas metus sed quam sed pellentesque vitae odio donec sit ornare massa ultricies eros.</p><p>Molestie malesuada risus et ornare metus fusce quisque leo lorem quam proin congue a. Non sagittis magna diam curabitur nulla a molestie ipsum in duis risus porttitor risus ultricies leo pharetra. Justo proin lorem odio at non ipsum diam bibendum orci diam leo nulla. Bibendum commodo auctor curabitur bibendum pellentesque vivamus mattis eget fusce nibh donec pharetra orci arcu. Integer eros integer et a arcu pharetra elementum diam pellentesque integer vivamus ut odio sodales ut magna duis congue malesuada. Diam congue elementum sodales porta auctor arcu leo porttitor amet massa vitae sapien lorem.</p>', 'is_published'=>1));
		db_save('user_pages', false, array('title'=>'Our History', 'parent_id'=>2, 'url'=>'/about/history/', 'content'=>'<p>Pellentesque amet massa mauris justo vitae mauris maecenas nam ligula nulla pellentesque arcu ornare. Ornare integer orci eget integer proin porta quisque cursus eu sit malesuada maecenas eu amet auctor morbi. Mattis pellentesque a molestie auctor commodo ultricies enim a commodo nam commodo nulla cursus orci risus sagittis massa porttitor eros enim proin vivamus. Justo curabitur ornare porttitor molestie at odio magna lorem morbi sit tellus at gravida curabitur donec tempus urna ultricies molestie. Vivamus integer orci eros tellus quam mattis molestie quam maecenas vitae sed. Orci nulla porta et ultricies risus adipiscing nibh maecenas metus sed quam sed pellentesque vitae odio donec sit ornare massa ultricies eros.</p><p>Molestie malesuada risus et ornare metus fusce quisque leo lorem quam proin congue a. Non sagittis magna diam curabitur nulla a molestie ipsum in duis risus porttitor risus ultricies leo pharetra. Justo proin lorem odio at non ipsum diam bibendum orci diam leo nulla. Bibendum commodo auctor curabitur bibendum pellentesque vivamus mattis eget fusce nibh donec pharetra orci arcu. Integer eros integer et a arcu pharetra elementum diam pellentesque integer vivamus ut odio sodales ut magna duis congue malesuada. Diam congue elementum sodales porta auctor arcu leo porttitor amet massa vitae sapien lorem.</p>', 'is_published'=>1));
		db_save('user_pages', false, array('title'=>'Contact Us', 'parent_id'=>1, 'url'=>'/contact/', 'content'=>'<p>Pharetra eget ligula molestie cursus sit ornare mattis amet eros urna bibendum magna pellentesque. Donec justo porta mattis pharetra ornare lorem sapien nec cursus. Ut mattis et risus ultricies ipsum at congue eu rutrum ultricies congue. Sit massa ipsum sodales sagittis vivamus enim adipiscing maecenas curabitur porta enim in mauris fusce vitae non gravida donec. Mattis cursus molestie urna sit gravida donec sodales maecenas justo bibendum cursus lectus quisque at cursus mattis nam rutrum. Sit quam magna in bibendum gravida ornare enim adipiscing ut fusce eros gravida enim orci in justo donec urna tellus justo sodales integer eget.</p><p>Non metus congue metus molestie integer lectus massa sit arcu integer eu sapien malesuada. In non diam elementum nulla porttitor quisque sit ligula sed nulla quisque vulputate enim massa eu risus et vitae non integer justo. Congue eget mattis integer non magna tempus maecenas sit urna sem gravida sagittis eget porttitor nec arcu.</p>', 'is_published'=>1));
		treeRebuild('user_pages');
		
		$object_id = db_save('app_objects', false, array('title'=>'Snippets', 'table_name'=>'user_snippets', 'show_published'=>0, 'order_by'=>'title'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>1));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Content', 'field_name'=>'content', 'visibility'=>'list', 'required'=>0));
		db_save('user_snippets', false, array('title'=>'Site Name', 'content'=>'Sample Site'));
		db_save('user_snippets', false, array('title'=>'Meta Description', 'content'=>''));
		db_save('user_snippets', false, array('title'=>'Meta Keywords', 'content'=>''));
		db_save('user_snippets', false, array('title'=>'Copyright', 'content'=>'This site copyright &copy; ' . date('Y') . ' All rights reserved.'));
		
		url_change(DIRECTORY_BASE);
	}
	
	//CMS 
	return true;
}

function drawNav($pages) {
	$return = '<ul>';
	foreach ($pages as $p) {
		$return .= '<li>' . draw_link($p['url'], $p['title']);
		if (count($p['children'])) $return .= drawNav($p['children']);
		$return .= '</li>';
	}
	$return .= '</ul>';
	return $return;
}

function drawTop($title='CMS') {
	global $_josh;
	if (!$app = db_grab('SELECT link_color, ' . db_updated() . ' FROM app WHERE id = 1')) $app = array();
	if (empty($app['link_color'])) $app['link_color'] = '336699';
	if (empty($app['updated'])) $app['updated'] = 0;
	$return = draw_doctype() . draw_container('head',
		draw_meta_utf8() .
		draw_title($title) . 
		lib_get('jquery') . 
		draw_javascript_src() . 
		draw_javascript_src(DIRECTORY_BASE . 'scripts/global.js') . 
		draw_css_src(DIRECTORY_BASE . 'styles/screen/global.css') .
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

function drawBottom() {
	$return = '</div>' . draw_google_analytics('UA-21096000-1') . '</body></html>';
	return $return;
}

function drawObjectTable($object_id, $from_type=false, $from_id=false) {
	
	//get content
	if (!$object = db_grab('SELECT o.title, o.table_name, o.order_by, o.direction, o.show_published, o.group_by_field, (SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = ' . user() . ' AND u2o.object_id = o.id) permission FROM app_objects o WHERE o.id = ' . $object_id)) error_handle('This object does not exist', '', __file__, __line__);
	
	//security
	if (!$object['permission'] && !admin()) return false;

	//define variables
	$selects	= array(TAB . 't.id');
	$joins		= $columns = $nav = array();
	$t			= new table($object['table_name']);
	$where		= $where_str = $return = '';
	$rel_fields = $nav = $classes = array();
	$nested		= false;
	$depth		= 0;
	
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
			WHERE f.is_active = 1 AND f.type <> "checkboxes" AND f.type <> "object" AND f.object_id = ' . $object_id . ' 
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
		
		$selects[] = TAB . 't.' . $f['field_name'];
		if ($f['type'] == 'select') {
			
			//need transpose field for select groupings and columns
			$rel_fields[$f['id']] = db_grab('SELECT f1.field_name FROM app_fields f1 JOIN app_fields f2 ON f1.object_id = f2.related_object_id WHERE f2.id = ' . $f['id'] . ' AND f1.visibility = "list" AND f1.is_active = 1 ORDER BY f1.precedence');
			
			//handle select groupings
			if ($f['id'] == $object['group_by_field']) {
				if ($f['related_object_id'] == $object_id) {
					//nested object
					$nested = true;
					$selects[] = TAB . 't.precedence';
					$selects[] = TAB . 't.parent_id';
				} elseif ($f['related_object_id'] != $from_type) {
					//skip this if it's the from_type
					//figure out which column to group by and label it group
					$more = db_columns($f['related_table'], true);
					foreach ($more as $m) $selects[] = TAB . $f['related_table'] . '.' . $m['name'] . ' ' . (($m['name'] == $rel_fields[$f['id']]) ? '"group"' : $f['related_table'] . '_' . $m['name']);
					$joins[] = 'LEFT JOIN ' . $f['related_table'] . ' ON ' . $f['related_table'] . '.id = t.' . $f['field_name'];
		
					//also figure out which column to order the group by and put it before the regular order by
					$rel_order = db_grab('SELECT o.table_name, o.order_by, o.direction FROM app_objects o JOIN app_fields f ON f.related_object_id = o.id WHERE f.id = ' . $f['id']);
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

	//testing
	//die(draw_container('pre', $sql));
	
	//set up nav
	if (admin()) {
		if (!$from_type) {
			$nav[DIRECTORY_BASE . 'edit/?id=' . $_GET['id']] = 'Object Settings';
			$classes[] = 'settings';
			$nav[DIRECTORY_BASE . 'object/fields/?id=' . $_GET['id']] = 'Fields';
			$classes[] = 'fields';
		}
		if ($deleted = db_grab($del_sql)) {
			if ($_SESSION['show_deleted']) {
				$nav[url_action_add('hide_deleted')] = 'Hide ' . format_quantity($deleted) . ' Deleted';
			} else {
				$nav[url_action_add('show_deleted')] = 'Show ' . format_quantity($deleted) . ' Deleted';
			}
		}
		$classes[] = 'deleted';
		$nav['#sql'] = 'Show SQL';
		$classes[] = 'sql';
		$return .= draw_container('textarea', $sql, array('id'=>'sql', 'style'=>'display:none;')); //todo disambiguate
	}
	if ($from_type && $from_id) {
		//we're going to pass this stuff so the add new page can have this field as a hidden value rather than a select
		$nav[DIRECTORY_BASE . 'object/edit/?object_id=' . $object_id . '&from_type=' . $from_type . '&from_id=' . $from_id] = 'Add New';
	} else {
		$nav[DIRECTORY_BASE . 'object/edit/?object_id=' . $object_id] = 'Add New';
	}
	$classes[] = 'new';
	$return = draw_nav($nav) . $return; //todo pass $classes to draw_nav
		
	$t->set_column('updated', 'r', 'Updated', 120);
	$t->set_column('delete', 'delete', '&nbsp;', 20);
	
	//get rows, iterate
	$rows = db_table($sql);
	foreach($rows as &$r) {
		if ($nested) {
			//do nesty things (see treeDisplay for a simpler version of this)
			$t->set_nested();
			if (empty($r['parent_id'])) $right = array();
			$r['descendants'] = ($r['subsequence'] - $r['precedence'] - 1) / 2;
			$r['depth'] = count($right);
			if ($r['depth'] > 0) {  
				//check if we should remove a node from the stack
				while ($right[$r['depth'] - 1] < $r['subsequence']) {
					array_pop($right);
					$r['depth']--;
				}
			}
			$right[] = $r['subsequence'];
			array_argument($r, 'depth-' . $r['depth']);
		}
		if ($orderingByPrecedence) $r['draggy'] = '&nbsp;'; //'<img src="../images/move.png" alt="move" width="16" height="16" border="0"/>';
		//$b['is_published'] = draw_form_checkbox('chk_posts_' . $b['id'], $b['is_published']);
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
				//$r[$f['field_name']] = format_string($r[$f['field_name']], 50);
			}
			if (!$linked) {
				if (empty($r[$f['field_name']])) $r[$f['field_name']] = draw_div_class('empty', 'No ' . $f['title'] . ' entered');
				$r[$f['field_name']] = draw_link(DIRECTORY_BASE . 'object/edit/?id=' . $r['id'] . '&object_id=' . $object_id, $r[$f['field_name']]);
				if (($f['type'] != 'file-type') && ($f['type'] != 'image') && ($f['type'] != 'image-alt')) $linked = true; //just linking the image isn't enough visually
			}
		}
		$r['updated'] = draw_span('light', ($r['updated_user'] ? $r['updated_user'] : $r['created_user'])) . ' ' . format_date($r['updated'], '', '%b %d, %Y', true, true);
		if (!$r['is_active']) {
			array_argument($r, 'deleted');
			$r['delete'] = draw_link(false, CHAR_UNDELETE, false, 'delete');
		} else {
			$r['delete'] = draw_link(false, CHAR_DELETE, false, 'delete');
		}
	}
	return $return . $t->draw($rows, 'No ' . strToLower($object['title']) . ' have been added' . $where_str . ' yet.');
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

function treeRebuild($table, $parent_id=false, $left=1) {
	//the right value of this node (in case there are no children) is + 1
	$right = $left + 1;
	
	//get all children of this node   
	$ids = db_array('SELECT id FROM ' . $table . ' WHERE parent_id ' . (($parent_id) ? '= ' . $parent_id : 'IS NULL'));

	//recursive execution of this function for each child of this node   
	//$right is the current right value, which is incremented by the treeRebuild function   
	foreach ($ids as $id) $right = treeRebuild($table, $id, $right);   
	
	//we've got the left value, and now that we've processed the children of this node we also know the right value   
	if ($parent_id) db_query('UPDATE ' . $table . ' SET precedence = ' . $left . ', subsequence = ' . $right . ' WHERE id = ' . $parent_id);   
	
	//return the right value of this node + 1   
	return $right + 1;
}   

?> 