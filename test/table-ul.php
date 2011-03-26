<!-- 
this page is attempting to replicate a table layout using only ULs.  it's not working.
-->

<style>	
	* { margin: 0; padding: 0; }
	div.container { background: yellow; display: table; width: 100%; }
	ul.rows { display: table; background: pink; width: 100%; }
	ul.cells { display: table; width: 100%; }
	ul.cells > li { display: table-cell;  text-align: left;}
	/*ul.rows > li { background-color: grey; overflow: auto; display: table-row; }
	ul.cells > li { display: table-cell; border: 1px solid red; }*/
</style>

<div class="container">
	<ul class="rows">
		<li>
			<ul class="cells">
				<li>Title</li>
				<li>Content</li>
				<li>Updated</li>
				<li>Blah</li>
			</ul>
		</li>
		<li>
			<ul class="cells">
				<li>Site Name</li>
				<li>Sample Site</li>
				<li>Josh Mar 08, 2011</li>
				<li>×</li>
			</ul>
		</li>
		<li>
			<ul class="cells">
				<li>Meta Keywords</li>
				<li>Foo Bar</li>
				<li>Josh Mar 08, 2011</li>
				<li>×</li>
			</ul>
		</li>
		<li>
			<ul class="cells">
				<li>Meta Description</li>
				<li>Sample Site</li>
				<li>Josh Mar 08, 2011</li>
				<li>×</li>
			</ul>
		</li>
		<li>
			<ul class="cells">
				<li>Copyright</li>
				<li>This site copyright © 2011 All rights reserved.	Josh Mar 08, 2011</li>
				<li>Josh Mar 08, 2011</li>
				<li>×</li>
			</ul>
		</li>
	</ul>
</div>