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
			var arrayed = $('ul.nested').nestedSortable('toArray', {startDepthCount: 0});
			var item_id = $(event.originalEvent.target).attr('id').replace("item_", '');
			for (var i = 0; i < arrayed.length; i++) {
				if (arrayed[i].item_id == item_id) {
					$.ajax({
						url : '/login/ajax/nested_reorder.php',
						type : 'POST',
						data : arrayed[i],
						success : function(data) {
							//$('#panel').html(data);
						}
					});
				}
			}
		}	
	});
});
