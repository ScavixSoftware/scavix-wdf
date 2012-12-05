<div id="<?=$id?>_container">
	<input id="<?=$id?>" name="<?=$id?>" type="text" value="<?=$default_date?>">
</div>
<script>
	$(function(){
		$("#<?=$id?>").<?=$init_code?>({ <?=$options?> });
	});
</script>