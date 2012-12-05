<?
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
 
/**
 * Represents a PHP DocComment as described in http://en.wikipedia.org/wiki/PHPDoc
 * 
 * Use PhpDocComment::Parse to create an instance.
 */
class PhpDocComment
{
	var $ShortDesc = "";
	var $LongDesc = "";
	var $Tags = array();
	var $Attributes = array();
	
	static function Parse($comment)
	{
		$res = new PhpDocComment();
		
		preg_match('/^\s*\/\*\*(.*)\*\/\s*$/s',$comment,$m); 
		$comment = trim($m[1]);
		$comment = preg_replace('/\s*\*\s*/',"\n",$comment);
		$comment = preg_replace('/\s*\*[\x20\t]*(.*)(\n*)/',"$1$2",$comment);
		$comment = trim($comment);
		
		$m = explode("\n@",$comment,2);
		$m = explode("\n\n",$m[0],2);
		
		$isMatch = preg_match('/^@attribute/',trim($m[0]));
		
		if ($isMatch !== false && $isMatch == 0)
			$res->ShortDesc = trim($m[0]);
		
		if (isset($m[1]))
		{
			$isMatch = preg_match('/^@attribute/',trim($m[1]));
			if ($isMatch !== false && $isMatch == 0)
				$res->LongDesc = trim($m[1]);
		}
		
		preg_match_all('/^@([^\s]+)\s([^@]*)/ms',$comment,$m,PREG_SET_ORDER);
		foreach( $m as $p )
		{
			$res->Tags[] = array(
				'tag' => $p[1],
				'data' => $p[2]
			);
		}
		
		preg_match_all('/^@attribute\[([^\]]*)\]/ms',$comment,$m,PREG_SET_ORDER);
		foreach( $m as $p )
		{
			$res->Attributes[] = array(
				'data' => $p[1]
			);
		}
		
		return $res;
	}
	
	static function RenderHtml($string)
	{
		// todo: process inline tags like @see and @link
		//       and everything else from http://en.wikipedia.org/wiki/PHPDoc#Tags
		return nl2br($string);
	}
	
	function ShortDescAsHtml()
	{
		return self::RenderHtml($this->ShortDesc);
	}
	
	function LongDescAsHtml()
	{
		return self::RenderHtml($this->LongDesc);
	}
	
	private function getTag($name,$properties)
	{
		if( !isset($this->_tagbuf) )
			$this->_tagbuf = array();
		
		if( !isset($this->_tagbuf[$name]) )
		{
			$pat = "/";
			for($i=0;$i<count($properties)-1;$i++)
				$pat .= '([^\s]+)\s+';
			$pat .= '(.*)/s';
			
			$this->_tagbuf[$name] = array();
			foreach( $this->Tags as $t )
			{
				if( $t['tag'] != $name )
					continue;

				preg_match($pat,$t['data'],$m);
				$p = new stdClass();
				for($i=0;$i<count($properties);$i++)
				{
					$v = "";
					if (array_key_exists($i+1,$m))
						$v = $m[$i+1];
					$p->{$properties[$i]} = $v;
				}
				$this->_tagbuf[$name][] = $p;
			}
		}
		return $this->_tagbuf[$name];
	}
	
	function getParam()
	{
		return $this->getTag('param',array('type','var','desc'));
	}
	
	function getReturn()
	{
		return $this->getTag('return',array('type','desc'));
	}
	
	function getDeprecated()
	{
		$tag = $this->getTag('deprecated',array('desc'));
		if( count($tag) == 0 )
			return false;
		return $tag[0]->desc;
	}
}