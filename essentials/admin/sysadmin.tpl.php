<?
    $page = current_controller(false);
    $hasmenu = (isset($navlinks) && $navlinks && (count($navlinks) > 0));
?>
<div id="page_header" class="no-print">
    <div class="left">
        <span class="hamburger"><i class="fas fa-bars"></i></span><div class="logo"><h1><i class="fas fa-cog"></i> &nbsp;SysAdmin</h1></div>
    </div>
<? if( isset($user) && $user): ?>
    <span class="userinfo">
        <?=$user->username?><br/>
        <a class="logout" href="<?=buildQuery('','')?>"><?=gethostname()?><i class="fas fa-backward"></i></a><br/>
        <a class="logout" href="<?=buildQuery('sysadmin', 'logout')?>">Logout<i class="fas fa-power-off"></i></a>
    </span>
<? endif; ?>
</div>
<? if($hasmenu) { ?>
<div class="side-menu">
    <div class="menu"><ul class="main">
        <?php foreach($navlinks as $c) echo $c; ?>	
    </ul></div>
</div>
<? } ?>
<div id="page_content"<?=($hasmenu ? ' class="hasmenu"' : '')?>>
    <div class="content_header"><?=isset($page_title)&&$page_title?"<h4>$page_title</h4>":''?><?=(isset($pagetoolbar) && $pagetoolbar ? $pagetoolbar : '')?><?=$page->GenerateBreadcrumbNavigation()?></div>
    <div class="content <?=current_controller(true)?>_page <?=current_event()?>_subpage">
		<?=isset($intro)&&$intro?"<p>$intro</p>":''?>
		<?php foreach($content as $c) echo $c; ?>	
	</div>
    <div id="page_footer" class="no-print">
        <div class="copyright">
            2012-<?=date('Y')?> Scavix&#174; Software GmbH &amp; Co. KG
        </div>
    </div>
</div>
<div id="loaderoverlay" class="fa-3x"><i class="fas fa-circle-notch fa-spin"></i></div>