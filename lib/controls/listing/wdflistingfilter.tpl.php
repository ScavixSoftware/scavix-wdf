<?php
$listings = isset($listings)?$listings:[];
$render_actions = isset($render_actions) ? $render_actions : true;
?>
<form class="listingfilter trivial" method="post" action="<?=$action?>" id="<?=$this->id?>" data-listings="<?=join(',', $listings)?>">
    <?php foreach( $inputs as $i ) echo $i; ?>
    <?php if( $render_actions ): ?>
    <div class="notitle">
        <button class="go" type="submit"><span class="ui-icon ui-icon-check"></span></button>
        <button class="go" type="reset"><span class="ui-icon ui-icon-close"></span></button>
    </div>
    <?php endif; ?>
</form>
