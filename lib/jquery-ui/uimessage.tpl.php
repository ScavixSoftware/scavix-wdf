<?
$type = isset($type)?$type:'highlight';
$icon = $type=='highlight'?'info':'alert';
$message = isset($message)?$message:'Unknown error';
?>
<div class="ui-widget ui-message">
	<div style="padding: 0 .7em;" class="ui-state-<?=$type?> ui-corner-all">
		<p>
			<span style="float: left; margin-right: 0.3em; margin-top: 0.1em;" class="ui-icon ui-icon-<?=$icon?>"></span>
			<?=$message?>
		</p>
	</div>
</div>