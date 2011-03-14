<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><title>Pages</title><script language="javascript" src="/_demo.livingcities.site/lib/jquery/jquery-1.4.2.min.js" type="text/javascript"></script><script language="javascript" src="/_demo.livingcities.site/javascript.js" type="text/javascript"></script><script language="javascript" src="/login/scripts/global.js" type="text/javascript"></script><link rel="stylesheet" type="text/css" href="/login/styles/screen/global.css"/><style type="text/css">a { color:#336699}</style></head><body><div id="page"><div id="banner">&nbsp;</div><h1><a href="/login/">CMS</a> &gt; Pages</h1><ul class="nav"><li class="option1 loginedit_id_1 first odd"><a name="option_1_1" class="option_1_1" href="/login/edit/?id=1">Object Settings</a></li> 
<li class="option2 loginobjectfields_id_1 even"><a name="option_1_2" class="option_1_2" href="/login/object/fields/?id=1">Fields</a></li> 
<li class="option3 #sql odd"><a name="option_1_3" class="option_1_3" href="#sql">Show SQL</a></li> 
<li class="option4 loginobjectedit_object_id_1 even last"><a name="option_1_4" class="option_1_4" href="/login/object/edit/?object_id=1">Add New</a></li></ul><textarea id="sql" style="display:none;">SELECT
	t.id,
	t.is_published,
	t.title,
	t.url,
	t.parent_id,
	t.precedence,
	t.content,
	t.meta_description,
	t.meta_keywords,
	t.subsequence,
	IFNULL(t.updated_date, t.created_date) updated,
	u1.firstname created_user,
	u2.firstname updated_user,
	t.is_active
FROM user_pages t
LEFT JOIN app_users u1 ON t.created_user = u1.id
LEFT JOIN app_users u2 ON t.updated_user = u2.id
WHERE t.is_active = 1
ORDER BY t.precedence</textarea>

<table cellspacing="0" class="user_pages table" id="user_pages">
	<thead>
		<tr><th class="draggy icon">&nbsp;</th><th class="is_published checkbox">&nbsp;</th><th class="title l">Title</th><th style="width:120px;" class="updated r">Updated</th><th style="width:20px;" class="delete delete">&nbsp;</th></tr>
	</thead>
	<tbody id="user_pages0">
		<tr id="user_pages-row-1" class="odd depth-0 first_row"><td class="draggy icon">&nbsp;</td><td class="is_published checkbox"><input class="checkbox" type="checkbox" id="chk_user-pages_1" name="chk_user-pages_1" onclick="javascript:ajax_publish(this);" checked="checked"/></td><td class="title l"><a href="/login/object/edit/?id=1&amp;object_id=1">Home</a></td><td class="updated r" style="width:120px;"><span class="light">Josh</span>  9:49PM</td><td class="delete delete" style="width:20px;"><a class="delete empty">&times;</a></td></tr> 
		<table id="user_pages1">
		<tbody id="user_pages1">
			<tr id="user_pages-row-2" class="even depth-1"><td class="draggy icon">&nbsp;</td><td class="is_published checkbox"><input class="checkbox" type="checkbox" id="chk_user-pages_2" name="chk_user-pages_2" onclick="javascript:ajax_publish(this);" checked="checked"/></td><td class="title l"><a href="/login/object/edit/?id=2&amp;object_id=1">About Us</a></td><td class="updated r" style="width:120px;"><span class="light">Josh</span> 10:35PM</td><td class="delete delete" style="width:20px;"><a class="delete empty">&times;</a></td></tr> 
			<table id="user_pages2">
			<tbody id="user_pages2">
				<tr id="user_pages-row-3" class="odd depth-2"><td class="draggy icon">&nbsp;</td><td class="is_published checkbox"><input class="checkbox" type="checkbox" id="chk_user-pages_3" name="chk_user-pages_3" onclick="javascript:ajax_publish(this);" checked="checked"/></td><td class="title l"><a href="/login/object/edit/?id=3&amp;object_id=1">Our History</a></td><td class="updated r" style="width:120px;"><span class="light">Josh</span> 10:35PM</td><td class="delete delete" style="width:20px;"><a class="delete empty">&times;</a></td></tr> 
				<tr id="user_pages-row-5" class="even depth-2"><td class="draggy icon">&nbsp;</td><td class="is_published checkbox"><input class="checkbox" type="checkbox" id="chk_user-pages_5" name="chk_user-pages_5" onclick="javascript:ajax_publish(this);" checked="checked"/></td><td class="title l"><a href="/login/object/edit/?id=5&amp;object_id=1">Board of Directors</a></td><td class="updated r" style="width:120px;"><span class="light">Josh</span> 10:36PM</td><td class="delete delete" style="width:20px;"><a class="delete empty">&times;</a></td></tr> 
			</tbody>
			</table>
			<tr id="user_pages-row-4" class="odd depth-1 last_row"><td class="draggy icon">&nbsp;</td><td class="is_published checkbox"><input class="checkbox" type="checkbox" id="chk_user-pages_4" name="chk_user-pages_4" onclick="javascript:ajax_publish(this);" checked="checked"/></td><td class="title l"><a href="/login/object/edit/?id=4&amp;object_id=1">Contact Us</a></td><td class="updated r" style="width:120px;"><span class="light">Josh</span>  9:49PM</td><td class="delete delete" style="width:20px;"><a class="delete empty">&times;</a></td></tr>
		</tbody>
		</table>
	</tbody>
</table>

<script language="javascript" src="/_demo.livingcities.site/lib/jquery/jquery.tablednd_0_5.js" type="text/javascript"></script>
		
<script language="javascript" type="text/javascript">
	$(document).ready(function() { 
		table_dnd("user_pages", "precedence", "draggy");
		table_dnd("user_page1", "precedence", "draggy");
		table_dnd("user_page2", "precedence", "draggy");
	});
</script>
		
		<div id="panel">&nbsp;</div><script language="javascript" src="/_demo.livingcities.site/lib/jquery/jquery.jeditable.mini.js" type="text/javascript"></script><script language="javascript" type="text/javascript"> 
			$(document).ready(function() {
				$("#panel").editable(url_action_add("ajax_set", true), {
					id			: "field_name",
					type		: "textarea",
					style		: "inherit",
					submitdata	:	{ table: "app_objects", column: "list_help", id: "1" },
					cancel	: false,
					submit	: false,
					onblur	: "submit",
					event	: "dblclick",
					data: function(value, settings) { return value.replace(/<br[\s\/]?>/gi, "\n"); }
				});
			});	
		</script></div></body></html>