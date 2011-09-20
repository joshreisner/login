$(function(){
	function log($msg) {
		try {
			console.log($msg);
		} catch(e) {
		}
	}
	
	//show translations
	$('a.show_translations').click(function(e){
		if ($('div.translation:visible').size()) {
			$(this).html('Show Translations');
			$('div.translation').slideUp();
		} else {
			$(this).html('Hide Translations');
			$('div.translation').slideDown();
		}
	});
	
	function printObject(o) {
	  var out = '';
	  for (var p in o) {
	    out += p + ': ' + o[p] + '\n';
	  }
	  return out;
	}

	$('a.translate').click(function(e){
		$('div.translation input').each(function(){
			if (!$(this).val().length) {
				var field = $(this)
				var fname = field.attr('name');
				var lang = fname.substr(fname.length - 2);
				var src = $('div input[name=' + fname.substr(0, fname.length - 3) + ']').val();
				if (src.length) {
					$.ajax({  
					    url: 'https://ajax.googleapis.com/ajax/services/language/translate',  
					    dataType: 'jsonp',
					    data: { q: src,  // text to translate
					            v: '1.0',
					            langpair: 'en|' + lang },   // '|es' for auto-detect
					    success: function(result) {
					    	//alert(printObject(result));
					    	if (result.responseData) field.val(result.responseData.translatedText);
					    },  
					    error: function(XMLHttpRequest, errorMsg, errorThrown) {
					        //console.log(errorMsg);
					    }  
					});
				}
			}
		});
		//alert("finished");
	});
	
	//duplicate object button on settings page
	$('a.object_duplicate').click(function(e){
		e.preventDefault();
		var title = prompt('What should the new object be called?', $('form input#title').val() + ' New');
		if (title) location.href = './?' + $.param({ id: url_query('id'), action:'duplicate', title:title });
	});
	
	//update tinymce file and image references
	$('a.tinymce_update').click(function(e){
		if (old_server = prompt('What was the HTTP_HOST of the old server?')) {
			$.ajax({
				url : '/login/ajax/tinymce_update.php',
				type : 'POST',
				data : { old_server : old_server },
				success : function(data) {
					alert(data);
				}
			});
		}
	});
	
	//lorem ipsum for rich textareas
	$('a.lorem_ipsum').click(function(e){
		e.preventDefault();
		$(this).closest('div.field').find('textarea').tinymce().setContent(LoremIpsum.paragraphs((2 + Math.floor(Math.random()*2)), "<p>%s</p>"));
	});
	
	//placekitten for images
	$('a.placekitten').click(function(e){
		e.preventDefault();
		location.href = '/login/ajax/placekitten.php?width=' + $(this).attr('data-width') + '&height=' + $(this).attr('data-height');
/*
		$.ajax({
			url : '/login/ajax/placekitten.php',
			type : 'POST',
			data : { width:$(this).attr('data-width'), height:$(this).attr('data-height') },
			success : function(data) { 
				alert(data);
			}
		});
*/
	});
	
	//clear images from object/edit forms
	$('a.clear_img').click(function(e){
		e.preventDefault();
		var title = $(this).attr('data-title');
		var table = $(this).attr('data-table');
		var column = $(this).attr('data-column');
		var id = $(this).attr('data-id');
		if (confirm("Are you sure you want to clear the " + title.toLowerCase() + " field?  It will happen right away (before saving).")) {
			ajax_set(table, column, id);
			$('div.field.' + column + ' img.preview').slideUp();
			$('div.field.' + column + ' a.clear_img').fadeOut();
		}
	});
	
	initObjectList();
	function initObjectList() {
		//show sql button on object page
		$('li.sql a').click(function(e){
			e.preventDefault();
			$(this).html(($('#sql').is(':visible') ? 'Show' : 'Hide') + ' SQL');
			$('#sql').slideToggle();
		});
		
		//object value delete
		$('a.delete').click(function(e) {
			e.preventDefault();
			var id			= $(this).attr('data-id');
			var parent		= $(this).closest('div.object_list');
			var table_name	= parent.find('input[name=table_name]').val();
			var from_type	= parent.find('input[name=from_type]').val();
			var from_id		= parent.find('input[name=from_id]').val();
			var object_id	= parent.find('input[name=object_id]').val();
	
			$.ajax({
				url : '/login/ajax/object_value_delete.php',
				type : 'POST',
				data : { id:id, table_name:table_name, from_type:from_type, from_id:from_id, object_id:object_id },
				success : function(data) {
					parent.html(data);
					initObjectList();
				}
			});
		});

		//setup for sortable
		$('ul.nested li').each(function(){ $(this).attr('id', 'list_' + $(this).attr('data-id')); });

		//init sortable
		$('ul.nested').nestedSortable({
			disableNesting: 'no-nest',
			items : "li:not(.disabled)",
			listType: 'ul',
			forcePlaceholderSize: true,
			handle: 'div',
			helper:	'clone',
			items: 'li',
			opacity: 0.8,
			tabSize: 25,
			delay: 300,
			distance: 15,
			placeholder: 'placeholder',
			tolerance: 'pointer',
			toleranceElement: '> div',
			update: function(event, ui) {
				var id				= ui.item.attr('data-id');
				var arrayed			= $('ul.nested').nestedSortable('toArray', {startDepthCount: 0});
				var list			= new Array();
				var parent_id		= false;
				var table_name		= $('#table_name').val();
				var nesting_column	= $('#nesting_column').val();
				for (var i = 0; i < arrayed.length; i++) {
					if (arrayed[i].item_id != 'root') list[list.length] = arrayed[i].item_id;
					if (arrayed[i].item_id == id) parent_id = arrayed[i].parent_id;
				}
				$.ajax({
					url : '/login/ajax/nested_reorder.php',
					type : 'POST',
					data : { 
						id : id,
						table_name : table_name,
						nesting_column : nesting_column,
						parent_id : parent_id, 
						list : list.join(',')
					},
					success : function(data) {
						//$('#panel').html(data);
					}
				});
				fix_depths($('ul.nested'));
			}	
		});
	}
	
	//adjust the css on the rows because the indentation has likely changed
	function fix_depths(ul, level) {
		if (!level) level = 1;
		var needle = 'level_';
		var strlen = needle.length;
		$(ul).children().each(function(){
			var row = $(this).find('div.row');
			var classes = row.attr('class').split(' ');
			for (var j = 0; j < classes.length; j++) {
				if (classes[j].substr(0, strlen) == needle) row.removeClass(classes[j]).addClass(needle + level);
			}
			fix_depths($(this).children('ul'), (level + 1))
		});
	}
});