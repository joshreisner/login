$(document).ready(function(){
	
	function log($msg) {
		try {
			console.log($msg);
		} catch(e) {
		}
	}
	
	$('ul.sortable').nestedSortable({
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
			var arrayed = $('ul.sortable').nestedSortable('toArray', {startDepthCount: 0});
			var item_id = $(event.originalEvent.target).attr('id').replace("item_", '');
			
			for(var i = 0; i < arrayed.length; i++)
			{
				if(arrayed[i].item_id == item_id)
				{
					$.ajax({
						url : '../ajax/nested_reorder.php',
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
	
	$('#serialize').click(function(){
		//console.log($('ul.sortable').nestedSortable('serialize'));
	});

	$('#toHierarchy').click(function(e){
		hiered = $('ul.sortable').nestedSortable('toHierarchy', {startDepthCount: 0});
		hiered = dump(hiered);
		(typeof($('#toHierarchyOutput')[0].textContent) != 'undefined') ?
		$('#toHierarchyOutput')[0].textContent = hiered : $('#toHierarchyOutput')[0].innerText = hiered;
	});

	$('#toArray').click(function(e){
		arraied = $('ul.sortable').nestedSortable('toArray', {startDepthCount: 0});
		log(arraied);
		
		arraied = dump(arraied);
		(typeof($('#toArrayOutput')[0].textContent) != 'undefined') ?
		$('#toArrayOutput')[0].textContent = arraied : $('#toArrayOutput')[0].innerText = arraied;
	});
});

function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;

	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";

	if(typeof(arr) == 'object') { //Array/Hashes/Objects
		for(var item in arr) {
			var value = arr[item];

			if(typeof(value) == 'object') { //If it is an array,
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}
