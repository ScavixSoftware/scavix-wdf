<?php
/**
 * PamConsult Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
 
class SocialBookmarks extends Template
{
	function __initialize()
	{
		parent::__initialize();

		$links = array();
		$links[] = $this->CreateLink("TwitThis",
			"http://twitter.com/home?status={url}",
			skinFile("socialbookmarks/twitter.png"));
		$links[] = $this->CreateLink("LinkedIn",
			"http://www.linkedin.com/shareArticle?mini=true&url={url}&title={title}&source={domain}&summary=EXCERPT",
			skinFile("socialbookmarks/linkedin.png"));
		$links[] = $this->CreateLink("MySpace",
			"http://www.myspace.com/Modules/PostTo/Pages/?u={url}&t={title}",
			skinFile("socialbookmarks/myspace.png"));
		$links[] = $this->CreateLink("del.icio.us",
			"http://delicious.com/post?url={url}&title={title}",
			skinFile("socialbookmarks/delicious.png"));
		$links[] = $this->CreateLink("Digg",
			"http://digg.com/submit?phase=2&url={url}&title={title}",
			skinFile("socialbookmarks/digg.png"));
		$links[] = $this->CreateLink("StumbleUpon",
			"http://www.stumbleupon.com/submit.php?url={url}",
			skinFile("socialbookmarks/stumbleupon.png"));
		$links[] = $this->CreateLink("Reddit",
			"http://reddit.com/submit?url={url}&title={title}",
			skinFile("socialbookmarks/reddit.png"));
		$links[] = $this->CreateLink("Y!GG",
			"http://yigg.de/neu?exturl={url}&exttitle={title}",
			skinFile("socialbookmarks/yigg.png"));
		$links[] = $this->CreateLink("Google Bookmarks",
			"http://www.google.com/bookmarks/mark?op=edit&output=popup&bkmk={url}&title={title}",
			skinFile("socialbookmarks/google.png"));
		$links[] = $this->CreateLink("Webnews",
			"http://www.webnews.de/einstellen?url={url}&title={title}",
			skinFile("socialbookmarks/webnews.png"));
		$links[] = $this->CreateLink("YahooMyWeb",
			"http://myweb2.search.yahoo.com/myresults/bookmarklet?u={url}&={title}",
			skinFile("socialbookmarks/yahoomyweb.png"));
		$links[] = $this->CreateLink("Furl",
			"http://www.furl.net/storeIt.jsp?u={url}&t={title}",
			skinFile("socialbookmarks/furl.png"));
		$links[] = $this->CreateLink("Live-MSN",
			"https://favorites.live.com/quickadd.aspx?marklet=1&mkt=en-us&url={url}&title={title}&top=1",
			skinFile("socialbookmarks/live.png"));
		$links[] = $this->CreateLink("BlinkList",
			"http://www.blinklist.com/index.php?Action=Blink/addblink.php&Url={url}&Title={title}",
			skinFile("socialbookmarks/blinklist.png"));
		$links[] = $this->CreateLink("co.mments",
			"http://co.mments.com/track?url={url}&title={title}",
			skinFile("socialbookmarks/co.mments.png"));
		$links[] = $this->CreateLink("Facebook",
			"http://www.facebook.com/share.php?u={url}",
			skinFile("socialbookmarks/facebook.png"));
		$links[] = $this->CreateLink("Faves",
			"http://faves.com/Authoring.aspx?u={url}&title={title}",
			skinFile("socialbookmarks/faves.png"));
		$links[] = $this->CreateLink("Folkd",
			"http://www.folkd.com/submit/{url}",
			skinFile("socialbookmarks/folkd.png"));
		$links[] = $this->CreateLink("Squidoo",
			"http://www.squidoo.com/lensmaster/bookmark?{url}",
			skinFile("socialbookmarks/squidoo.png"));
		$links[] = $this->CreateLink("Wikio",
			"http://www.wikio.com/vote?url={url}",
			skinFile("socialbookmarks/wikio.png"));
		$links[] = $this->CreateLink("Ma.gnolia",
			"http://ma.gnolia.com/bookmarklet/add?url={url}&title={title}",
			skinFile("socialbookmarks/magnolia.png"));
		$links[] = $this->CreateLink("NewsVine",
			"http://www.newsvine.com/_tools/seed&save?u={url}&h={title}",
			skinFile("socialbookmarks/newsvine.png"));
		$links[] = $this->CreateLink("Shadows",
			"http://www.shadows.com/features/tcr.htm?url={url}&title={title}",
			skinFile("socialbookmarks/shadows.png"));
		$this->set("links", $links);
	}

	private function CreateLink($title, $href, $img)
	{
		$href = str_replace("{url}",urlencode($_SERVER['SCRIPT_URI']),$href);
		$href = str_replace("{title}",urlencode($title),$href);
		$href = str_replace("{domain}",urlencode($_SERVER['SERVER_NAME']),$href);
		$img = new Image($img,$title);

		$res = new Anchor($href);
		$res->title = $title;
		$res->target = "_blank";
		$res->content($img);
		return $res;
	}
}

?>