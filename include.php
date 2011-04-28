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
		
		db_schema_check(array( 	//adding these by default
			'user_pages'=>array('title'=>'varchar', 'url'=>'varchar', 'content'=>'text', 'meta_description'=>'varchar', 'meta_keywords'=>'varchar', 'parent_id'=>'int', 'subsequence'=>'int'),
			'user_news'=>array('title'=>'varchar', 'date'=>'date', 'content'=>'text'),
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
		
		db_save('app', false, array('link_color'=>'0c4b85', 'banner_image'=>file_get('/assets/images/banner-cms.jpg')));
		
		//create and populate sample site pages
		$object_id = db_save('app_objects', false, array('title'=>'Pages', 'table_name'=>'user_pages', 'show_published'=>1, 'group_by_field'=>3, 'order_by'=>'precedence'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>1));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'URL', 'field_name'=>'url', 'visibility'=>'normal', 'related_object_id'=>2, 'required'=>1, 'additional'=>'eg /about/'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'select', 'title'=>'Parent', 'field_name'=>'parent_id', 'visibility'=>'normal', 'required'=>0, 'related_object_id'=>$object_id));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'textarea', 'title'=>'Content', 'field_name'=>'content', 'visibility'=>'normal', 'required'=>0));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Meta Description', 'field_name'=>'meta_description', 'visibility'=>'normal', 'required'=>0, 'additional'=>'for search engines'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Meta Keywords', 'field_name'=>'meta_keywords', 'visibility'=>'normal', 'required'=>0));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'int', 'title'=>'Subsequence', 'field_name'=>'subsequence', 'visibility'=>'hidden', 'required'=>0));
		/*1*/ db_save('user_pages', false, array('title'=>'Your Own Headline is Placed Here.', 'url'=>'/', 'content'=>'<p>Text lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec vitae nulla. Donec elementum sagittis nulla. Nullam eget pede sed metus accumsan faucibus ibus. Donec vitae nulla ibus. Donec elementum sagittis nulla.</p><p><a href="/company/">Read More</a></p>', 'is_published'=>1));
		/*2*/ db_save('user_pages', false, array('title'=>'Company', 'parent_id'=>1, 'url'=>'/company/', 'content'=>'<p>Pellentesque amet massa mauris justo vitae mauris maecenas nam ligula nulla pellentesque arcu ornare. Ornare integer orci eget integer proin porta quisque cursus eu sit malesuada maecenas eu amet auctor morbi. Mattis pellentesque a molestie auctor commodo ultricies enim a commodo nam commodo nulla cursus orci risus sagittis massa porttitor eros enim proin vivamus. Justo curabitur ornare porttitor molestie at odio magna lorem morbi sit tellus at gravida curabitur donec tempus urna ultricies molestie. Vivamus integer orci eros tellus quam mattis molestie quam maecenas vitae sed. Orci nulla porta et ultricies risus adipiscing nibh maecenas metus sed quam sed pellentesque vitae odio donec sit ornare massa ultricies eros.</p><p>Molestie malesuada risus et ornare metus fusce quisque leo lorem quam proin congue a. Non sagittis magna diam curabitur nulla a molestie ipsum in duis risus porttitor risus ultricies leo pharetra. Justo proin lorem odio at non ipsum diam bibendum orci diam leo nulla. Bibendum commodo auctor curabitur bibendum pellentesque vivamus mattis eget fusce nibh donec pharetra orci arcu. Integer eros integer et a arcu pharetra elementum diam pellentesque integer vivamus ut odio sodales ut magna duis congue malesuada. Diam congue elementum sodales porta auctor arcu leo porttitor amet massa vitae sapien lorem.</p>', 'is_published'=>1));
		/*3*/ db_save('user_pages', false, array('title'=>'History', 'parent_id'=>2, 'url'=>'/company/history/', 'content'=>'<p>Maecenas proin sagittis sem bibendum nec pharetra nam molestie metus nulla ligula risus a mattis. Lorem integer maecenas ultricies mauris eget curabitur pharetra integer sed lectus bibendum malesuada leo gravida risus auctor eget. Porta odio proin ut leo sodales enim magna lectus risus et sed curabitur porta malesuada porttitor risus mattis odio malesuada duis eu. Ligula curabitur nec integer fusce mattis nibh commodo mauris ligula arcu et maecenas ut nam adipiscing tempus morbi. Amet congue duis nulla ipsum elementum diam adipiscing lorem sagittis nulla pellentesque mattis tempus odio risus sem malesuada morbi sem metus ultricies massa.<p>Justo porttitor ut mauris cursus auctor sed auctor metus a mattis pellentesque tellus proin lorem odio quam lorem. Urna sodales arcu quam pharetra tellus nec donec lectus odio cursus eget molestie massa justo diam. Vitae non eros vitae sagittis sodales donec adipiscing maecenas non massa sit ipsum urna rutrum sit sagittis rutrum commodo non donec nibh. Sit et auctor morbi ligula non ultricies risus donec diam elementum diam donec sapien eget. Duis eget rutrum risus cursus lorem vitae amet ut sed amet at.</p><p>Cursus nibh porta adipiscing fusce malesuada rutrum orci tellus lectus nulla elementum cursus mauris gravida tempus morbi orci risus. Urna auctor mauris pharetra ipsum justo sodales leo justo sapien orci nec donec magna. Risus molestie congue tempus enim proin morbi commodo maecenas elementum metus adipiscing.</p>', 'is_published'=>1));
		/*4*/ db_save('user_pages', false, array('title'=>'Board of Directors', 'parent_id'=>2, 'url'=>'/company/board/', 'content'=>'<p>Sed porta morbi duis nam tempus urna ut eu adipiscing. Vivamus diam vivamus cursus non nibh cursus rutrum in eros adipiscing pellentesque orci tellus pellentesque metus fusce curabitur diam ligula diam tempus massa auctor adipiscing.</p><p>Adipiscing odio auctor lectus ornare commodo tempus porttitor in duis sit. Malesuada morbi adipiscing malesuada adipiscing maecenas et duis leo sagittis commodo donec a. Congue orci rutrum orci proin porta nec et in eros lectus justo at molestie nam amet. Ornare vitae porta non quisque nulla duis cursus gravida pharetra leo commodo risus magna ipsum magna orci maecenas tellus vulputate risus et eget adipiscing. Massa vivamus lorem metus eget porttitor magna bibendum commodo non elementum at donec ligula et integer lectus vitae rutrum adipiscing pellentesque. Ultricies eros leo gravida enim magna odio elementum donec lorem sagittis morbi metus duis malesuada enim amet auctor bibendum ipsum tempus maecenas orci. Non vulputate lorem nulla lectus orci pellentesque molestie bibendum auctor rutrum maecenas donec pharetra vivamus elementum eros elementum justo lectus.</p>', 'is_published'=>1));
		/*5*/ db_save('user_pages', false, array('title'=>'Leadership Bios', 'parent_id'=>2, 'url'=>'/company/leadership/', 'content'=>'<p>Maecenas rutrum pellentesque adipiscing ornare odio proin in ultricies quisque odio magna sem maecenas sodales proin gravida maecenas at eros. Sagittis ut auctor amet mauris amet urna elementum adipiscing rutrum nec cursus fusce eget nec rutrum maecenas gravida ornare curabitur in porta quam risus. Eget commodo ipsum sed vitae ipsum pharetra quisque in massa nulla mauris auctor. Ut eget cursus et mattis ut odio lectus vitae ornare eu pellentesque rutrum mattis duis sodales ultricies pellentesque. Molestie proin amet ornare proin nam porta vivamus molestie sodales cursus sapien risus in vitae. Pellentesque magna vivamus tellus ipsum in metus risus ligula arcu sit ultricies lorem vulputate gravida magna congue pharetra non vivamus arcu vitae. Commodo malesuada gravida curabitur mauris sapien bibendum rutrum commodo ipsum pharetra nibh ligula at.</p><p>Vitae ipsum commodo non sagittis pellentesque et maecenas elementum porttitor non lorem nibh sem commodo malesuada massa. Sodales urna pellentesque ut gravida pellentesque curabitur eget urna orci ultricies arcu nam ornare vulputate non lorem justo gravida bibendum vivamus fusce porttitor nec. Eros mauris integer gravida mauris nam massa ornare pharetra sed vivamus elementum sem ornare metus in quisque a enim nam urna sodales mauris arcu. Eros molestie maecenas nibh nam sagittis magna bibendum nec lorem magna odio malesuada. Odio tellus quam amet cursus arcu fusce duis ligula molestie non lorem nec.</p><p>Mattis quam eget at elementum vitae eu curabitur integer porta arcu orci metus curabitur quam maecenas commodo. Metus quam metus urna vivamus gravida porta eu rutrum mattis at proin lectus. Leo sit morbi congue amet sed commodo tellus commodo malesuada. Proin ligula nam cursus pharetra duis fusce tempus adipiscing maecenas non ornare morbi quisque. Sagittis eu ligula lectus orci nec morbi malesuada ligula lectus duis sit sem tempus pharetra. Duis in ipsum pellentesque eget non integer gravida commodo tempus duis mauris sem in eros ornare cursus in porta sem nibh sem nibh sem sapien. Porta sodales nec malesuada ut malesuada sagittis ultricies auctor congue nec porta sit justo integer. Lorem vitae nibh commodo eu enim rutrum amet arcu orci curabitur magna ut maecenas gravida sit quisque leo elementum in magna vitae maecenas commodo.</p>', 'is_published'=>1));
		/*6*/ db_save('user_pages', false, array('title'=>'Operations Map', 'parent_id'=>2, 'url'=>'/company/operations/', 'content'=>'<p>Leo urna enim nibh ultricies orci eget tellus ut curabitur ornare odio ultricies justo sapien fusce vitae. Donec gravida ut auctor lorem a vitae justo eget quisque sit justo vitae lectus maecenas quisque. Lorem sodales duis a malesuada auctor morbi non sapien maecenas gravida proin porta ligula eros. Vitae vulputate congue proin vitae bibendum eget orci pharetra proin elementum odio quisque eros auctor in quam porttitor vitae. Adipiscing in nibh malesuada ut tempus quisque nulla tempus integer odio integer cursus malesuada cursus tellus et orci sem quam ultricies gravida donec tempus. Rutrum nulla pharetra amet a urna a duis auctor nulla sed vulputate vitae vulputate porttitor in ipsum quam auctor tempus lectus congue. Pellentesque elementum massa a lectus proin pellentesque sed auctor sem proin ut fusce. Fusce enim leo proin nec tempus at eget vulputate bibendum eu sit duis tempus bibendum leo nibh justo massa duis adipiscing ornare tempus.</p><p>Malesuada sodales ultricies tempus leo ligula nibh at maecenas curabitur metus sem lectus et integer eu. Odio mauris nulla risus pharetra sapien elementum ultricies nec arcu urna nibh duis molestie. Vitae donec malesuada metus morbi mauris molestie maecenas porttitor mauris risus leo vulputate ornare morbi ligula nulla orci maecenas non porttitor ornare vivamus. Diam morbi eget non proin adipiscing lorem enim sit elementum curabitur adipiscing. Integer vitae ipsum maecenas curabitur lorem magna porta malesuada duis maecenas odio curabitur. Metus morbi integer eu proin bibendum in metus nulla auctor eu leo sit molestie morbi integer. Odio eu eros lectus donec malesuada at duis maecenas donec eu eros in ligula donec non quisque ligula massa leo tellus magna nibh.</p><p>Pharetra eu molestie porttitor donec molestie tempus rutrum enim diam enim amet quisque integer porta auctor urna donec. Adipiscing tempus leo cursus eros malesuada tempus mattis donec quam integer eros at integer mauris. Eu non nec porta quisque porta quisque tellus lorem eget porttitor risus leo curabitur eros sem sagittis. Quam sodales mauris auctor ornare porta molestie a sit rutrum duis adipiscing ornare. Sit mattis massa elementum integer arcu duis arcu in pellentesque malesuada mauris donec malesuada bibendum diam ligula eget sagittis in porttitor bibendum curabitur massa. Diam vitae cursus curabitur eget vivamus sed integer porttitor ligula massa adipiscing elementum commodo lectus justo enim. Eros mauris maecenas eget pharetra duis ornare porta ut lorem in sit leo ligula ut sapien quam.</p>', 'is_published'=>1));
		/*7*/ db_save('user_pages', false, array('title'=>'Directors', 'parent_id'=>2, 'url'=>'/company/directors/', 'content'=>'<p>Bibendum porttitor odio curabitur molestie nibh amet diam lorem tellus vivamus sit adipiscing sit bibendum tellus ut integer. Bibendum malesuada elementum leo rutrum pellentesque rutrum duis orci cursus. Sed rutrum quisque ornare nibh urna duis massa sodales quam proin vitae integer odio. Congue pharetra eget ornare rutrum sed in ut porttitor adipiscing enim commodo nam auctor enim malesuada. Morbi non morbi eu malesuada nec porttitor vitae integer curabitur congue eget pharetra congue elementum massa maecenas donec. Sit mauris risus nec donec non duis ligula ipsum massa commodo quam gravida nibh sapien magna auctor nec vulputate sit vitae malesuada nibh.</p><p>Quisque in sem magna metus fusce enim ultricies morbi porta nulla a ligula bibendum eget amet risus pellentesque. Ligula quam elementum mauris justo bibendum eu ornare at diam lorem vivamus.</p>', 'is_published'=>1));
		/*8*/ db_save('user_pages', false, array('title'=>'News', 'parent_id'=>1, 'url'=>'/news/', 'content'=>'<p>Arcu sagittis risus diam lectus mauris urna ut eros adipiscing lorem enim auctor. Eros vulputate integer donec quam eu sodales leo rutrum eros ornare arcu sit integer auctor sagittis. Quisque adipiscing nulla pellentesque sed a nulla duis lorem pellentesque eu nec mattis sapien eget odio eget integer.</p>', 'is_published'=>1));
		/*9*/ db_save('user_pages', false, array('title'=>'Contact Us', 'parent_id'=>1, 'url'=>'/contact/', 'content'=>'<p>Pharetra eget ligula molestie cursus sit ornare mattis amet eros urna bibendum magna pellentesque. Donec justo porta mattis pharetra ornare lorem sapien nec cursus. Ut mattis et risus ultricies ipsum at congue eu rutrum ultricies congue. Sit massa ipsum sodales sagittis vivamus enim adipiscing maecenas curabitur porta enim in mauris fusce vitae non gravida donec. Mattis cursus molestie urna sit gravida donec sodales maecenas justo bibendum cursus lectus quisque at cursus mattis nam rutrum. Sit quam magna in bibendum gravida ornare enim adipiscing ut fusce eros gravida enim orci in justo donec urna tellus justo sodales integer eget.</p><p>Non metus congue metus molestie integer lectus massa sit arcu integer eu sapien malesuada. In non diam elementum nulla porttitor quisque sit ligula sed nulla quisque vulputate enim massa eu risus et vitae non integer justo. Congue eget mattis integer non magna tempus maecenas sit urna sem gravida sagittis eget porttitor nec arcu.</p>', 'is_published'=>1));
		nestedTreeRebuild('user_pages');

		//create and populate sample news
		$object_id = db_save('app_objects', false, array('title'=>'News', 'table_name'=>'user_news', 'show_published'=>1, 'order_by'=>'date', 'sort_by'=>'DESC'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>1));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'date', 'title'=>'Date', 'field_name'=>'date', 'visibility'=>'list', 'required'=>1));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'textarea', 'title'=>'Content', 'field_name'=>'content', 'visibility'=>'normal', 'required'=>0));
		/*1*/ db_save('user_news', false, array('date'=>'2011-03-25', 'title'=>'Your own news is placed in chronological order.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1));
		/*2*/ db_save('user_news', false, array('date'=>'2011-03-21', 'title'=>'If your users click on the headline they will be directed to a page where they can read more about your news story.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1));
		/*3*/ db_save('user_news', false, array('date'=>'2011-03-17', 'title'=>'Lorem ipsum dolor sit amet, consectetuer.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1));
		/*4*/ db_save('user_news', false, array('date'=>'2011-03-14', 'title'=>'Donec vitae nulla. Donec elementum sagittis nulla.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1));
		/*5*/ db_save('user_news', false, array('date'=>'2011-03-10', 'title'=>'Lorem ipsum dolor sit amet, consectetuer.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1));
		/*6*/ db_save('user_news', false, array('date'=>'2011-02-28', 'title'=>'Donec vitae nulla. Donec elementum sagittis nulla.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1));
		/*7*/ db_save('user_news', false, array('date'=>'2011-02-15', 'title'=>'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec vitae nulla. Donec elementum sagittis nulla.', 'content'=>'<p>Morbi leo amet fusce duis mauris tellus molestie gravida congue molestie duis massa fusce non et mauris et integer vivamus diam maecenas. Justo integer lectus eget rutrum sapien tellus maecenas tempus congue enim commodo sed commodo malesuada quam proin. Orci enim vulputate commodo malesuada in integer amet sem vitae congue duis adipiscing rutrum gravida leo vitae orci ut vitae morbi pellentesque. Congue magna sit porttitor commodo lorem rutrum commodo pharetra porta molestie. Malesuada leo orci molestie porttitor justo at curabitur pellentesque commodo fusce sapien bibendum quisque auctor. At non a elementum sapien quisque bibendum massa adipiscing donec sapien cursus duis risus enim rutrum lorem justo nam. Ligula congue ornare pharetra sit congue lorem enim nibh lorem risus curabitur adipiscing diam commodo orci. Ultricies tellus tempus orci at curabitur justo diam pellentesque in sagittis lectus nec quam integer magna porttitor lectus ultricies vulputate.</p><p>Magna porta risus massa cursus lorem quisque vitae lorem porta orci vivamus leo sagittis. Sem morbi nam donec in arcu quisque eu auctor sit nibh porta eu metus. Orci ipsum eu elementum leo integer vitae fusce ipsum curabitur at massa a vitae maecenas adipiscing quam vitae ultricies. Bibendum quisque proin morbi arcu lectus justo at lectus mauris.</p>', 'is_published'=>1));

		//create and populate sample site snippets
		$object_id = db_save('app_objects', false, array('title'=>'Snippets', 'table_name'=>'user_snippets', 'show_published'=>0, 'order_by'=>'title'));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Title', 'field_name'=>'title', 'visibility'=>'list', 'required'=>1));
		db_save('app_fields', false, array('object_id'=>$object_id, 'type'=>'text', 'title'=>'Content', 'field_name'=>'content', 'visibility'=>'list', 'required'=>0));
		db_save('user_snippets', false, array('title'=>'Site Name', 'content'=>'Sample Site'));
		db_save('user_snippets', false, array('title'=>'Meta Description', 'content'=>''));
		db_save('user_snippets', false, array('title'=>'Meta Keywords', 'content'=>''));
		db_save('user_snippets', false, array('title'=>'Copyright', 'content'=>'<p>Copyright ' . date('Y') . ', Your Own Company Here. &#149; info@YourOwnCo.com &#149; site by ' . draw_link('http://www.bureaublank.com/', 'Bureau Blank') . '</p><p>2040 S. Main Street, Suite 243 Everycity, XX 54321</p>'));
		
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
		draw_css_src(DIRECTORY_BASE . 'styles/stylesheet.css') .
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
			WHERE f.is_active = 1 AND f.type NOT IN ("checkboxes", "file", "image") AND f.object_id = ' . $object_id . ' 
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
					//$r[$f['field_name']] = format_string($r[$f['field_name']], 50);
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
		return $return . draw_form_hidden('nesting_column', $nested) . 
			draw_javascript_src(DIRECTORY_BASE . 'scripts/jquery-ui-1.8.9.custom.min.js') . 
			draw_javascript_src(DIRECTORY_BASE . 'scripts/jquery.ui.nestedSortable.js') . 
			draw_javascript_src(DIRECTORY_BASE . 'scripts/nested.js') . 
			nestedList($list, $object['table_name'], 'nested');
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
		$classes[] = 'list_' . $o['id'];
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