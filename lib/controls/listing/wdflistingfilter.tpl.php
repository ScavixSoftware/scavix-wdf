<?
$listings = isset($listings)?$listings:[];
?>
<form class="listingfilter trivial" method="post" action="<?=$action?>" id="<?=$this->id?>" data-listings="<?=join(',', $listings)?>">
    <? foreach( $inputs as $i ) echo $i; ?>
    <div class="notitle">
        <button class="go" type="submit"><span class="ui-icon ui-icon-check"></span></button>
        <button class="go" type="reset"><span class="ui-icon ui-icon-close"></span></button>
    </div>
</form>
