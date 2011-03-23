<?php
include("tree.php");
echo drawTop('sortable test');
?>
<style type="text/css">

.placeholder {
background:#CCC !important;
border-style: inset;
border:1px solid #999;
}

.ui-nestedSortable-error {
background:#fbe3e4;
color:#8a1f11;
}

ul.sortable,ul.sortable ul {
list-style-type:none;
margin:0 0 0 10px;
padding:0;
}

ul.sortable {
margin:4em 0;
}

.sortable li {
margin:0;
padding:0;
}

.sortable li div {
cursor:move;
margin:0;
padding:4px 0 4px;
background:#f2f2f2;
border-bottom:1px solid #CCC;
border-top:1px solid #FFF;
}

.sortable li div span.col {
display:block;
float:right;
margin-right:5px;
}


ul.tree, ul.tree ul {
list-style-type:none;
background:url(/login/images/vline.png) repeat-y;
margin:0;
padding:0;
}

ul.tree ul {
margin-left:10px;
}

ul.tree li {
line-height:20px;
background:url(/login/images/node.png) no-repeat;
color:#369;
margin:0 -12px 0 0;
padding:0px 12px 0px;
}

ul.tree li input[type=checkbox] {
margin-right:10px;
margin-left:5px;
}

ul.tree li:last-child {
background:url(/login/images/lastnode.png) no-repeat;
}

</style>

<script type="text/javascript" src="/login/scripts/jquery-1.5.min.js"></script>

<?php

treeRebuild('user_pages');
$array = getPages();

echo '<div style="width:650px">' . drawNav($array[0]['children'], 'sortable tree') . "</div>";

?>

<hr />
	<p>
		<input type="submit" name="serialize" id="serialize" value="Serialize" />
	<p id="serializeOutput"></p>

	<p>
		<input type="submit" name="toHierarchy" id="toHierarchy" value="To hierarchy" />
	<pre id="toHierarchyOutput"></pre>

	<p>
		<input type="submit" name="toArray" id="toArray" value="To array" />
	<pre id="toArrayOutput"></pre>

<script type="text/javascript" src="/login/scripts/jquery-ui-1.8.9.custom.min.js"></script>
<script type="text/javascript" src="/login/scripts/jquery.ui.nestedSortable.js"></script>
<script>

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
			toleranceElement: '> div'
		});
		
		
		$('#serialize').click(function(){
			serialized = $('ul.sortable').nestedSortable('serialize');
			if(!serialized)
			{
				$('#serializeOutput').text("undefined");			
			}
			else
			{
				$('#serializeOutput').text(serialized);
			}
		})

		$('#toHierarchy').click(function(e){
			hiered = $('ul.sortable').nestedSortable('toHierarchy', {startDepthCount: 0});
			hiered = dump(hiered);
			(typeof($('#toHierarchyOutput')[0].textContent) != 'undefined') ?
			$('#toHierarchyOutput')[0].textContent = hiered : $('#toHierarchyOutput')[0].innerText = hiered;
		})

		$('#toArray').click(function(e){
			arraied = $('ul.sortable').nestedSortable('toArray', {startDepthCount: 0});
			arraied = dump(arraied);
			(typeof($('#toArrayOutput')[0].textContent) != 'undefined') ?
			$('#toArrayOutput')[0].textContent = arraied : $('#toArrayOutput')[0].innerText = arraied;
		})
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

</script>
</body>
</html>

