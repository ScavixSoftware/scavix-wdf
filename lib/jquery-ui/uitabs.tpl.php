<?php
?>
<div id="<?=$id?>" <?=isset($width)?"style='width:$width;'":""?>>
	<ul>
		<?foreach($tab_ids as $tabid => $title):?>
			<li><a href="#<?=$tabid?>"><?=$title?></a></li>
		<?endforeach;?>
	</ul>
	<?foreach($tab_ids as $tabid => $title):?>
		<div id="<?=$tabid?>">
			<?foreach($tab_content[$tabid] as $content):?>
				<?=$content?>
			<?endforeach;?>
		</div>
	<?endforeach;?>
</div>

<script>
	$(function() {
		$("#<?=$id?>").tabs({
			<?=$options?>
		});
		
		<?if($sortable):?>
			$("#<?=$id?>").find(".ui-tabs-nav").sortable({axis:'x'});
		<?endif;?>
	});
</script>

