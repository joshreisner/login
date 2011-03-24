$(document).ready(function(){

	$('ul.sortable').nestedSortable({
		disableNesting: 'no-nest',
		listType: 'ul',
		forcePlaceholderSize: true,
		handle: 'div',
		helper:	'clone',
		items: 'li',
		opacity: 0.8,
		tabSize: 25,
		placeholder: 'placeholder',
		tolerance: 'pointer',
		toleranceElement: '> div',
		update: function(event, ui) { 
			$('#panel').html('<pre>' + $('ul.sortable').nestedSortable('toArray', {startDepthCount: 0}) + '</pre>');
		}
	});
	
	$('#serialize').click(function(){
		serialized = $('ul.sortable').nestedSortable('serialize');
		if(!serialized) {
			$('#serializeOutput').text("undefined");			
		} else {
			$('#serializeOutput').text(serialized);
		}
	});

	$('#toHierarchy').click(function(e){
		hiered = $('ul.sortable').nestedSortable('toHierarchy', {startDepthCount: 0});
		hiered = dump(hiered);
		(typeof($('#toHierarchyOutput')[0].textContent) != 'undefined') ?
		$('#toHierarchyOutput')[0].textContent = hiered : $('#toHierarchyOutput')[0].innerText = hiered;
	});

	$('#toArray').click(function(e){
		arraied = $('ul.sortable').nestedSortable('toArray', {startDepthCount: 0});
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
