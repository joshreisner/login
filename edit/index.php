<?php 
//add a new object to the CMS or edit its settings
include('../include.php');

if (!admin(SESSION_ADMIN)) url_change(DIRECTORY_BASE);

if ($posting) {
	if (!$editing) {
		//create new table
		$_POST['table_name'] = getNewObjectName('user ' . $_POST['title']);
		db_table_create($_POST['table_name']);
	}
	$id = db_save('app_objects', url_id(), 'post', false);
	db_checkboxes('permissions', 'app_users_to_objects', 'object_id', 'user_id', $id);
	if ($editing) {
		db_checkboxes('object_links', 'app_objects_links', 'object_id', 'linked_id', $_GET['id']);
		url_change_post('../');
	} else {
		//add new title column because we nearly always need it
		db_column_add($_POST['table_name'], 'title', 'text');
		db_save('app_fields', false, array('object_id'=>$id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>true), false);
		url_change('../object/?id=' . $id);
	}
} elseif (url_action('delete')) {
	//ok you're going to delete this object
	$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']);
	if (db_table_drop($table)) {
		db_table_drop($table . '_to_words');
		db_query('DELETE FROM app_fields WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_objects_links WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_users_to_objects WHERE object_id = ' . $_GET['id']);
		db_query('DELETE FROM app_objects WHERE id = ' . $_GET['id']);
	}
	url_change(DIRECTORY_BASE);
} elseif (url_action('duplicate')) {
	//duplicate an object and all its meta and values
	//todo fix app_objects precedence
	$table_name = getNewObjectName('user ' . $_GET['title']);
	$object_id = db_query('INSERT INTO app_objects ( title, table_name, order_by, direction, group_by_field, list_help, form_help, show_published, web_page, created_date, created_user, is_active ) SELECT "' . $_GET['title'] . '", "' . $table_name . '", order_by, direction, group_by_field, list_help, form_help, show_published, web_page, ' . db_date() . ', ' . user() . ', 1 FROM app_objects WHERE id = ' . $_GET['id']);
	db_table_duplicate(db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']), $table_name);
	//going to skip copying permissions
	db_query('INSERT INTO app_objects_links ( object_id, linked_id ) SELECT ' . $object_id . ', linked_id FROM app_objects_links WHERE object_id = ' . $_GET['id']);
	db_query('INSERT INTO app_fields ( object_id, type, title, field_name, visibility, required, related_field_id, related_object_id, width, height, additional, created_date, created_user, is_active ) SELECT ' . $object_id . ', type, title, field_name, visibility, required, related_field_id, related_object_id, width, height, additional, ' . db_date() . ', ' . user() . ', 1 FROM app_fields WHERE object_id = ' . $_GET['id']);
	
	//fix app_objects.group_by_field
	if ($field_name = db_grab('SELECT f.field_name FROM app_fields f JOIN app_objects o ON f.id = o.group_by_field WHERE o.id = ' . $object_id)) {
		$field_id = db_grab('SELECT id FROM app_fields WHERE field_name = "' . $field_name . '" AND object_id = ' . $object_id);
		db_query('UPDATE app_objects SET group_by_field = ' . $field_id . ' WHERE id = ' . $object_id);
	}
	
	//fix app_fields.related_field_id
	if ($field_names = db_table('SELECT f1.id, f2.field_name FROM app_fields f1 JOIN app_fields f2 ON f1.related_field_id = f2.id WHERE f1.object_id = ' . $object_id)) {
		foreach ($field_names as $field) {
			$field_id = db_grab('SELECT id FROM app_fields WHERE field_name = "' . $field['field_name'] . '" AND object_id = ' . $object_id);
			db_query('UPDATE app_fields SET related_field_id = ' . $field_id . ' WHERE id = ' . $field['id']);
		}
	}
	
	url_change(DIRECTORY_BASE . 'object/?id=' . $object_id);
} elseif (url_action('expunge')) {
	//wipe out all object values while maintaining structure
	$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']);
	db_query('DELETE FROM ' . $table);
	db_query('ALTER TABLE ' . $table . ' AUTO_INCREMENT = 1');
	if (db_table_exists($table . '_to_words')) db_query('DELETE FROM ' . $table . '_to_words');
	url_drop('action');
} elseif (url_action('resize')) {
	//resize all images in object according to new field rules
	//todo move this to field edit?
	$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']);
	$cols = db_table('SELECT field_name, width, height FROM app_fields WHERE object_id = ' . $_GET['id'] . ' AND (type = "image" OR type = "image-alt") AND (width IS NOT NULL OR height IS NOT NULL)');
	$rows = db_table('SELECT id, ' . implode(', ', array_key_values($cols, 'field_name')) . ' FROM ' . $table);
	foreach ($rows as $r) {
		$updates = array();
		foreach ($cols as $c) {
			if ($r[$c['field_name']]) $updates[] = $c['field_name'] . ' = ' . format_binary(format_image_resize($r[$c['field_name']], $c['width'], $c['height']));
		}
		if (count($updates)) db_query('UPDATE ' . $table . ' SET ' . implode(', ', $updates) . ', updated_date = NOW(), updated_user = ' . user() . ' WHERE id = ' . $r['id']);
	}
	url_drop('action');
} elseif (url_action('template_news')) {
	//create and populate sample news
	$table = getNewObjectName('user_news'); //user_pages might already be taken

	db_table_create($table, array('title'=>'varchar', 'date'=>'date', 'content'=>'text'));		

	$object_id = db_save('app_objects', false, array('title'=>'News', 'table_name'=>$table, 'show_published'=>1, 'order_by'=>'date', 'sort_by'=>'DESC'), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>1), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'date', 'title'=>'Date', 'field_name'=>'date', 'visibility'=>'list', 'required'=>1), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'textarea', 'title'=>'Content', 'field_name'=>'content', 'visibility'=>'normal', 'required'=>0), false);
	/*1*/ db_save($table, false, array('date'=>'2011-03-25', 'title'=>'Your own news is placed in chronological order.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1), false);
	/*2*/ db_save($table, false, array('date'=>'2011-03-21', 'title'=>'If your users click on the headline they will be directed to a page where they can read more about your news story.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1), false);
	/*3*/ db_save($table, false, array('date'=>'2011-03-17', 'title'=>'Lorem ipsum dolor sit amet, consectetuer.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1), false);
	/*4*/ db_save($table, false, array('date'=>'2011-03-14', 'title'=>'Donec vitae nulla. Donec elementum sagittis nulla.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1), false);
	/*5*/ db_save($table, false, array('date'=>'2011-03-10', 'title'=>'Lorem ipsum dolor sit amet, consectetuer.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1), false);
	/*6*/ db_save($table, false, array('date'=>'2011-02-28', 'title'=>'Donec vitae nulla. Donec elementum sagittis nulla.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1), false);
	/*7*/ db_save($table, false, array('date'=>'2011-02-15', 'title'=>'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec vitae nulla. Donec elementum sagittis nulla.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1), false);
		
	url_change(DIRECTORY_BASE . 'object/?id=' . $object_id);
} elseif (url_action('template_pages')) {
	$table = getNewObjectName('user_pages'); //user_pages might already be taken
	
	db_table_create($table, array('title'=>'varchar', 'url'=>'varchar', 'content'=>'text', 'meta_description'=>'varchar', 'meta_keywords'=>'varchar', 'parent_id'=>'int', 'subsequence'=>'int'));

	//create object, fields
	$object_id = db_save('app_objects', false, array('title'=>'Pages', 'table_name'=>$table, 'show_published'=>1, 'order_by'=>'precedence'), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>1), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'url-local', 'title'=>'URL', 'field_name'=>'url', 'visibility'=>'normal', 'related_object_id'=>2, 'required'=>1, 'additional'=>'eg /about/'), false);
	$parent_id = db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'select', 'title'=>'Parent', 'field_name'=>'parent_id', 'visibility'=>'normal', 'required'=>0, 'related_object_id'=>$object_id), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'textarea', 'title'=>'Content', 'field_name'=>'content', 'visibility'=>'normal', 'required'=>0), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Meta Description', 'field_name'=>'meta_description', 'visibility'=>'normal', 'required'=>0, 'additional'=>'for search engines'), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Meta Keywords', 'field_name'=>'meta_keywords', 'visibility'=>'normal', 'required'=>0), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'int', 'title'=>'Subsequence', 'field_name'=>'subsequence', 'visibility'=>'hidden', 'required'=>0), false);
	
	//group by parent
	db_query('UPDATE app_objects SET group_by_field = ' . $parent_id . ' WHERE id = ' . $object_id);
	
	//populate sample pages
	/*1*/ db_save($table, false, array('title'=>'Your Own Headline is Placed Here.', 'url'=>'/', 'content'=>'<p>Text lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec vitae nulla. Donec elementum sagittis nulla. Nullam eget pede sed metus accumsan faucibus ibus. Donec vitae nulla ibus. Donec elementum sagittis nulla.</p><p><a href="/company/">Read More</a></p>', 'is_published'=>1), false);
	/*2*/ db_save($table, false, array('title'=>'Company', 'parent_id'=>1, 'url'=>'/company/', 'content'=>'<p>Pellentesque amet massa mauris justo vitae mauris maecenas nam ligula nulla pellentesque arcu ornare. Ornare integer orci eget integer proin porta quisque cursus eu sit malesuada maecenas eu amet auctor morbi. Mattis pellentesque a molestie auctor commodo ultricies enim a commodo nam commodo nulla cursus orci risus sagittis massa porttitor eros enim proin vivamus. Justo curabitur ornare porttitor molestie at odio magna lorem morbi sit tellus at gravida curabitur donec tempus urna ultricies molestie. Vivamus integer orci eros tellus quam mattis molestie quam maecenas vitae sed. Orci nulla porta et ultricies risus adipiscing nibh maecenas metus sed quam sed pellentesque vitae odio donec sit ornare massa ultricies eros.</p><p>Molestie malesuada risus et ornare metus fusce quisque leo lorem quam proin congue a. Non sagittis magna diam curabitur nulla a molestie ipsum in duis risus porttitor risus ultricies leo pharetra. Justo proin lorem odio at non ipsum diam bibendum orci diam leo nulla. Bibendum commodo auctor curabitur bibendum pellentesque vivamus mattis eget fusce nibh donec pharetra orci arcu. Integer eros integer et a arcu pharetra elementum diam pellentesque integer vivamus ut odio sodales ut magna duis congue malesuada. Diam congue elementum sodales porta auctor arcu leo porttitor amet massa vitae sapien lorem.</p>', 'is_published'=>1), false);
	/*3*/ db_save($table, false, array('title'=>'History', 'parent_id'=>2, 'url'=>'/company/history/', 'content'=>'<p>Maecenas proin sagittis sem bibendum nec pharetra nam molestie metus nulla ligula risus a mattis. Lorem integer maecenas ultricies mauris eget curabitur pharetra integer sed lectus bibendum malesuada leo gravida risus auctor eget. Porta odio proin ut leo sodales enim magna lectus risus et sed curabitur porta malesuada porttitor risus mattis odio malesuada duis eu. Ligula curabitur nec integer fusce mattis nibh commodo mauris ligula arcu et maecenas ut nam adipiscing tempus morbi. Amet congue duis nulla ipsum elementum diam adipiscing lorem sagittis nulla pellentesque mattis tempus odio risus sem malesuada morbi sem metus ultricies massa.<p>Justo porttitor ut mauris cursus auctor sed auctor metus a mattis pellentesque tellus proin lorem odio quam lorem. Urna sodales arcu quam pharetra tellus nec donec lectus odio cursus eget molestie massa justo diam. Vitae non eros vitae sagittis sodales donec adipiscing maecenas non massa sit ipsum urna rutrum sit sagittis rutrum commodo non donec nibh. Sit et auctor morbi ligula non ultricies risus donec diam elementum diam donec sapien eget. Duis eget rutrum risus cursus lorem vitae amet ut sed amet at.</p><p>Cursus nibh porta adipiscing fusce malesuada rutrum orci tellus lectus nulla elementum cursus mauris gravida tempus morbi orci risus. Urna auctor mauris pharetra ipsum justo sodales leo justo sapien orci nec donec magna. Risus molestie congue tempus enim proin morbi commodo maecenas elementum metus adipiscing.</p>', 'is_published'=>1), false);
	/*4*/ db_save($table, false, array('title'=>'Board of Directors', 'parent_id'=>2, 'url'=>'/company/board/', 'content'=>'<p>Sed porta morbi duis nam tempus urna ut eu adipiscing. Vivamus diam vivamus cursus non nibh cursus rutrum in eros adipiscing pellentesque orci tellus pellentesque metus fusce curabitur diam ligula diam tempus massa auctor adipiscing.</p><p>Adipiscing odio auctor lectus ornare commodo tempus porttitor in duis sit. Malesuada morbi adipiscing malesuada adipiscing maecenas et duis leo sagittis commodo donec a. Congue orci rutrum orci proin porta nec et in eros lectus justo at molestie nam amet. Ornare vitae porta non quisque nulla duis cursus gravida pharetra leo commodo risus magna ipsum magna orci maecenas tellus vulputate risus et eget adipiscing. Massa vivamus lorem metus eget porttitor magna bibendum commodo non elementum at donec ligula et integer lectus vitae rutrum adipiscing pellentesque. Ultricies eros leo gravida enim magna odio elementum donec lorem sagittis morbi metus duis malesuada enim amet auctor bibendum ipsum tempus maecenas orci. Non vulputate lorem nulla lectus orci pellentesque molestie bibendum auctor rutrum maecenas donec pharetra vivamus elementum eros elementum justo lectus.</p>', 'is_published'=>1), false);
	/*5*/ db_save($table, false, array('title'=>'Leadership Bios', 'parent_id'=>2, 'url'=>'/company/leadership/', 'content'=>'<p>Maecenas rutrum pellentesque adipiscing ornare odio proin in ultricies quisque odio magna sem maecenas sodales proin gravida maecenas at eros. Sagittis ut auctor amet mauris amet urna elementum adipiscing rutrum nec cursus fusce eget nec rutrum maecenas gravida ornare curabitur in porta quam risus. Eget commodo ipsum sed vitae ipsum pharetra quisque in massa nulla mauris auctor. Ut eget cursus et mattis ut odio lectus vitae ornare eu pellentesque rutrum mattis duis sodales ultricies pellentesque. Molestie proin amet ornare proin nam porta vivamus molestie sodales cursus sapien risus in vitae. Pellentesque magna vivamus tellus ipsum in metus risus ligula arcu sit ultricies lorem vulputate gravida magna congue pharetra non vivamus arcu vitae. Commodo malesuada gravida curabitur mauris sapien bibendum rutrum commodo ipsum pharetra nibh ligula at.</p><p>Vitae ipsum commodo non sagittis pellentesque et maecenas elementum porttitor non lorem nibh sem commodo malesuada massa. Sodales urna pellentesque ut gravida pellentesque curabitur eget urna orci ultricies arcu nam ornare vulputate non lorem justo gravida bibendum vivamus fusce porttitor nec. Eros mauris integer gravida mauris nam massa ornare pharetra sed vivamus elementum sem ornare metus in quisque a enim nam urna sodales mauris arcu. Eros molestie maecenas nibh nam sagittis magna bibendum nec lorem magna odio malesuada. Odio tellus quam amet cursus arcu fusce duis ligula molestie non lorem nec.</p><p>Mattis quam eget at elementum vitae eu curabitur integer porta arcu orci metus curabitur quam maecenas commodo. Metus quam metus urna vivamus gravida porta eu rutrum mattis at proin lectus. Leo sit morbi congue amet sed commodo tellus commodo malesuada. Proin ligula nam cursus pharetra duis fusce tempus adipiscing maecenas non ornare morbi quisque. Sagittis eu ligula lectus orci nec morbi malesuada ligula lectus duis sit sem tempus pharetra. Duis in ipsum pellentesque eget non integer gravida commodo tempus duis mauris sem in eros ornare cursus in porta sem nibh sem nibh sem sapien. Porta sodales nec malesuada ut malesuada sagittis ultricies auctor congue nec porta sit justo integer. Lorem vitae nibh commodo eu enim rutrum amet arcu orci curabitur magna ut maecenas gravida sit quisque leo elementum in magna vitae maecenas commodo.</p>', 'is_published'=>1), false);
	/*6*/ db_save($table, false, array('title'=>'Operations Map', 'parent_id'=>2, 'url'=>'/company/operations/', 'content'=>'<p>Leo urna enim nibh ultricies orci eget tellus ut curabitur ornare odio ultricies justo sapien fusce vitae. Donec gravida ut auctor lorem a vitae justo eget quisque sit justo vitae lectus maecenas quisque. Lorem sodales duis a malesuada auctor morbi non sapien maecenas gravida proin porta ligula eros. Vitae vulputate congue proin vitae bibendum eget orci pharetra proin elementum odio quisque eros auctor in quam porttitor vitae. Adipiscing in nibh malesuada ut tempus quisque nulla tempus integer odio integer cursus malesuada cursus tellus et orci sem quam ultricies gravida donec tempus. Rutrum nulla pharetra amet a urna a duis auctor nulla sed vulputate vitae vulputate porttitor in ipsum quam auctor tempus lectus congue. Pellentesque elementum massa a lectus proin pellentesque sed auctor sem proin ut fusce. Fusce enim leo proin nec tempus at eget vulputate bibendum eu sit duis tempus bibendum leo nibh justo massa duis adipiscing ornare tempus.</p><p>Malesuada sodales ultricies tempus leo ligula nibh at maecenas curabitur metus sem lectus et integer eu. Odio mauris nulla risus pharetra sapien elementum ultricies nec arcu urna nibh duis molestie. Vitae donec malesuada metus morbi mauris molestie maecenas porttitor mauris risus leo vulputate ornare morbi ligula nulla orci maecenas non porttitor ornare vivamus. Diam morbi eget non proin adipiscing lorem enim sit elementum curabitur adipiscing. Integer vitae ipsum maecenas curabitur lorem magna porta malesuada duis maecenas odio curabitur. Metus morbi integer eu proin bibendum in metus nulla auctor eu leo sit molestie morbi integer. Odio eu eros lectus donec malesuada at duis maecenas donec eu eros in ligula donec non quisque ligula massa leo tellus magna nibh.</p><p>Pharetra eu molestie porttitor donec molestie tempus rutrum enim diam enim amet quisque integer porta auctor urna donec. Adipiscing tempus leo cursus eros malesuada tempus mattis donec quam integer eros at integer mauris. Eu non nec porta quisque porta quisque tellus lorem eget porttitor risus leo curabitur eros sem sagittis. Quam sodales mauris auctor ornare porta molestie a sit rutrum duis adipiscing ornare. Sit mattis massa elementum integer arcu duis arcu in pellentesque malesuada mauris donec malesuada bibendum diam ligula eget sagittis in porttitor bibendum curabitur massa. Diam vitae cursus curabitur eget vivamus sed integer porttitor ligula massa adipiscing elementum commodo lectus justo enim. Eros mauris maecenas eget pharetra duis ornare porta ut lorem in sit leo ligula ut sapien quam.</p>', 'is_published'=>1), false);
	/*7*/ db_save($table, false, array('title'=>'Directors', 'parent_id'=>2, 'url'=>'/company/directors/', 'content'=>'<p>Bibendum porttitor odio curabitur molestie nibh amet diam lorem tellus vivamus sit adipiscing sit bibendum tellus ut integer. Bibendum malesuada elementum leo rutrum pellentesque rutrum duis orci cursus. Sed rutrum quisque ornare nibh urna duis massa sodales quam proin vitae integer odio. Congue pharetra eget ornare rutrum sed in ut porttitor adipiscing enim commodo nam auctor enim malesuada. Morbi non morbi eu malesuada nec porttitor vitae integer curabitur congue eget pharetra congue elementum massa maecenas donec. Sit mauris risus nec donec non duis ligula ipsum massa commodo quam gravida nibh sapien magna auctor nec vulputate sit vitae malesuada nibh.</p><p>Quisque in sem magna metus fusce enim ultricies morbi porta nulla a ligula bibendum eget amet risus pellentesque. Ligula quam elementum mauris justo bibendum eu ornare at diam lorem vivamus.</p>', 'is_published'=>1), false);
	/*8*/ db_save($table, false, array('title'=>'News', 'parent_id'=>1, 'url'=>'/news/', 'content'=>'<p>Arcu sagittis risus diam lectus mauris urna ut eros adipiscing lorem enim auctor. Eros vulputate integer donec quam eu sodales leo rutrum eros ornare arcu sit integer auctor sagittis. Quisque adipiscing nulla pellentesque sed a nulla duis lorem pellentesque eu nec mattis sapien eget odio eget integer.</p>', 'is_published'=>1), false);
	/*9*/ db_save($table, false, array('title'=>'Contact Us', 'parent_id'=>1, 'url'=>'/contact/', 'content'=>'<p>Pharetra eget ligula molestie cursus sit ornare mattis amet eros urna bibendum magna pellentesque. Donec justo porta mattis pharetra ornare lorem sapien nec cursus. Ut mattis et risus ultricies ipsum at congue eu rutrum ultricies congue. Sit massa ipsum sodales sagittis vivamus enim adipiscing maecenas curabitur porta enim in mauris fusce vitae non gravida donec. Mattis cursus molestie urna sit gravida donec sodales maecenas justo bibendum cursus lectus quisque at cursus mattis nam rutrum. Sit quam magna in bibendum gravida ornare enim adipiscing ut fusce eros gravida enim orci in justo donec urna tellus justo sodales integer eget.</p><p>Non metus congue metus molestie integer lectus massa sit arcu integer eu sapien malesuada. In non diam elementum nulla porttitor quisque sit ligula sed nulla quisque vulputate enim massa eu risus et vitae non integer justo. Congue eget mattis integer non magna tempus maecenas sit urna sem gravida sagittis eget porttitor nec arcu.</p>', 'is_published'=>1), false);
	nestedTreeRebuild('user_pages');
	
	url_change(DIRECTORY_BASE . 'object/?id=' . $object_id);
} elseif (url_action('template_snippets')) {

	$table = getNewObjectName('user_snippets'); //user_pages might already be taken

	db_table_create($table, array('title'=>'varchar', 'content'=>'varchar'));

	//create and populate sample site snippets
	$object_id = db_save('app_objects', false, array('title'=>'Snippets', 'table_name'=>$table, 'show_published'=>0, 'order_by'=>'title'), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>1), false);
	db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Content', 'field_name'=>'content', 'visibility'=>'list', 'required'=>0), false);
	db_save($table, false, array('title'=>'Site Name', 'content'=>'Sample Site'), false);
	db_save($table, false, array('title'=>'Meta Description', 'content'=>''), false);
	db_save($table, false, array('title'=>'Meta Keywords', 'content'=>''), false);
	db_save($table, false, array('title'=>'Copyright', 'content'=>'<p>Copyright ' . date('Y') . ', Your Own Company Here. &#149; info@YourOwnCo.com &#149; site by ' . draw_link('http://www.bureaublank.com/', 'Bureau Blank') . '</p><p>2040 S. Main Street, Suite 243 Everycity, XX 54321</p>'), false);

	url_change(DIRECTORY_BASE . 'object/?id=' . $object_id);
} elseif ($editing) {
	$title = db_grab('SELECT title FROM app_objects WHERE id = ' . $_GET['id']);
	$action = 'Edit Settings';
	echo drawFirst(draw_link('../object/?id=' . $_GET['id'], $title) . ' &gt; ' . $action);
} else { //adding
	$action = 'Add New Object';
	echo drawFirst($action);
}

$f = new form('app_objects', url_id(), $action);

if (url_id()) {
	//if editings present more options
	$order_by = db_table('SELECT field_name, title FROM app_fields WHERE object_id = ' . $_GET['id'] . ' AND is_active = 1 ORDER BY precedence');
	$order_by['precedence'] = 'Precedence';
	$order_by['created_date'] = 'Created';
	$order_by['updated_date'] = 'Updated';
	$f->set_field(array('name'=>'order_by', 'type'=>'select', 'options'=>$order_by));
	$f->set_field(array('name'=>'table_name', 'type'=>'text', 'allow_changes'=>false));
	$f->set_field(array('name'=>'direction', 'type'=>'select', 'options'=>array_2d(array('ASC', 'DESC')), 'default'=>'ASC', 'required'=>true));
	if ($options = db_table('SELECT id, title FROM app_fields WHERE type = "select" AND is_active = 1 AND object_id = ' . $_GET['id'])) {
		$f->set_field(array('name'=>'group_by_field', 'label'=>'Group By', 'type'=>'select', 'options'=>$options));
	}
	if ($options = db_table('SELECT o.id, o.title, (SELECT COUNT(*) FROM app_objects_links l WHERE l.object_id = ' . $_GET['id'] . ' AND l.linked_id = o.id) checked FROM app_objects o JOIN app_fields f ON o.id = f.object_id WHERE f.related_object_id = ' . $_GET['id'])) {
		$f->set_field(array('name'=>'object_links', 'type'=>'checkboxes', 'label'=>'Linked Objects', 'linking_table'=>'app_objects_links', 'options_table'=>'app_objects', 'option_id'=>'object_id', 'option_title'=>'title', 'options'=>$options));
	}
} else {
	$f->unset_fields('table_name,group_by_field,order_by,web_page,show_published');
	$f->set_field(array('name'=>'direction', 'type'=>'hidden', 'value'=>'ASC'));
}

//permissions
if (db_grab('SELECT COUNT(*) FROM app_users WHERE is_active = 1 AND is_admin <> 1 AND id <> ' . user())) {
	$sql = 'SELECT u.id, CONCAT(u.firstname, " ", u.lastname) title, ' . (url_id() ? '(SELECT COUNT(*) FROM app_users_to_objects u2o WHERE u2o.user_id = u.id AND u2o.object_id = ' . $_GET['id'] . ')' : 1) . ' checked FROM app_users u WHERE u.is_active = 1 and u.is_admin <> 1 ORDER BY title';
	$f->set_field(array('name'=>'permissions', 'type'=>'checkboxes', 'sql'=>$sql));
}

//table name handled automatically, help handled with in-place editor
$f->unset_fields('list_help,form_help');
echo $f->draw();

if (url_id()) {
	$images = false;
	if (db_grab('SELECT COUNT(*) FROM app_fields WHERE object_id = ' . $_GET['id'] . ' AND (type = "image" OR type = "image-alt") AND (width IS NOT NULL OR height IS NOT NULL)')) {
		$images = draw_p('You can also ' . draw_link(url_action_add('resize'), 'resize all images') . '.');
	}

	$table = db_grab('SELECT table_name FROM app_objects WHERE id = ' . $_GET['id']);
	if ($values = db_grab('SELECT COUNT(*) FROM ' . $table)) {
		$values = draw_p('Or you can ' . draw_link(url_action_add('expunge'), 'expunge') . ' the ' . format_quantitize($values, 'value', false) . ' in this object.');
	} else {
		$values = '';
	}
	
	echo draw_div('panel', 
		draw_p('You can drop this object and all its associated fields and values by ' . draw_link(url_action_add('delete'), 'clicking here') . '.') . 
		$images . $values . 
		draw_p('You can also ' . draw_link(false, 'duplicate this object', false, array('class'=>'object_duplicate')) . ' and all of its values.')
	);
} else {
	//add new object
	echo draw_div('panel', 
		draw_p('You can also choose an object template from the list below:' . draw_nav(array(
			url_action_add('template_news')=>'News',
			url_action_add('template_pages')=>'Pages',
			url_action_add('template_snippets')=>'Snippets'
		), 'text', 'templates'))
	);
}

echo drawLast();
?>