$(function(){
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
			var item_id = $(event.originalEvent.target).attr('id').replace('item_', '');
			console.log('item_id was ' + $(event.originalEvent.target).attr('id'));
			var arrayed = $('ul.nested').nestedSortable('toArray', {startDepthCount: 0});
			for (var i = 0; i < arrayed.length; i++) {
				if (arrayed[i].item_id == item_id) {
					arrayed[i]['table'] = $('#table_name').val();
					$.ajax({
						url : '/login/ajax/nested_reorder.php',
						type : 'POST',
						data : arrayed[i],
						success : function(data) {
							$('#panel').html(data);
						}
					});
				}
			}
			fix_depths($('ul.nested'));
		}	
	});
	
	//delete items out of a nested list
	$('ul.nested div.delete a').click(function(){
		var item_id = $(this).closest('div.row').attr('id').replace('item_', '');
		var item = $('li.list_' + item_id);
		var children = $('li.list_' + item_id + ' > ul').children();
		if (confirm('Are you sure?')) {
			$.ajax({
				url : '/login/ajax/nested_delete.php',
				type : 'POST',
				data : { item_id : item_id },
				success : function(data) {
					if (children.size()) {
						//console.log('has ' + children.size() + ' children');
						item.before(children);
						fix_depths($('ul.nested'));
					}
					item.slideUp();
				}
			});
		}
	});
	
	function fix_depths(ul, level) {
		if (!level) level = 1;
		var needle = 'level_';
		var strlen = needle.length;
		$(ul).children().each(function(){
			var row = $(this).find('div.row');
			var classes = row.attr('class').split(' ');
			for (var j = 0; j < classes.length; j++) {
				//console.log('checking ' + classes[j]);
				if (classes[j].substr(0, strlen) == needle) {
					row.removeClass(classes[j]).addClass(needle + level);
					//console.log('updating ' + row.find('div.link a').html() + ', removing ' + classes[j] + ' and adding ' + needle + level);
				}
			}
			fix_depths($(this).children('ul'), (level + 1))
		});
	}
});
