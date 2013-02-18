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
 * Use <PhpDocComment::Parse> to create an instance.
 */
class PhpDocComment
{
	var $ShortDesc = "";
	var $LongDesc = "";
	var $Tags = array();
	var $Attributes = array();
	
	/**
	 * Creates a PhpDocComment instance from a string
	 * 
	 * See <System_Reflector::getCommentObject> for how to use this best.
	 * @param string $comment Valid DocComment string
	 * @return boolean|PhpDocComment False on error, else a PhpDocComment object
	 */
	static function Parse($comment)
	{
		$res = new PhpDocComment();
		
		if( !preg_match('/^\s*\/\*\*(.*)\*\/\s*$/s',$comment,$m) )
			return false;
		
		$comment = explode("\n",$m[1]);
		foreach( $comment as $i=>$l )
			$comment[$i] = trim(ltrim($l,"\t *"));
		$comment = implode("\n",$comment);
		
//		$comment = trim($m[1]);
//		$comment = preg_replace('/\s+\*\s+/',"\n",$comment);
//		$comment = preg_replace('/\s*\*[\x20\t]*(.*)(\n*)/',"$1$2",$comment);
		$comment = trim($comment);
//		log_debug($comment);
		$m = explode("\n@",$comment,2);
//		log_debug($m);
		$m = explode("\n\n",$m[0],2);
//		log_debug($m);
		
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
	
	/**
	 * Lists all param docs
	 * 
	 * Every method parameter should have an <at>param block in the DocComment.
	 * This returns all of them
	 * @return array All param block
	 */
	function getParams()
	{
		return $this->getTag('param',array('type','var','desc'));
	}
	
	/**
	 * Returns docs for a specified parameter
	 * 
	 * Every method parameter should have an <at>param block in the DocComment.
	 * This method returns it
	 * @param string $name Parameter name
	 * @return mixed The parameter description or false on error
	 */
	function getParam($name)
	{
		foreach( $this->getParams() as $p )
			if( $p->var == $name )
				return $p;
		return false;
	}
	
	/**
	 * Gets the return documentation
	 * 
	 * Every method should have a <at>return block in the DocComment.
	 * This method returns it
	 * @return mixed The return doc or false on error
	 */
	function getReturn()
	{
		$res = $this->getTag('return',array('type','desc'));
		return ($res && isset($res[0]))?$res[0]:false;
	}
	
	/**
	 * Gets the deprecated note if present
	 * 
	 * Every DocComment may contain a <at>deprecated part.
	 * This method returns it
	 * @return mixed The deprecated note if present or false
	 */
	function getDeprecated()
	{
		$tag = $this->getTag('deprecated',array('desc'));
		if( count($tag) == 0 )
			return false;
		return $tag[0]->desc;
	}
	
	/**
	 * Returns the description ready for use in markdown syntax
	 * 
	 * Markdown is our favorite for automated documentation creation as GitHub supports it directly for their Wiki.
	 * This method makes some preparations for the doccomment to be complatible with MD.
	 * @return string MD prepared string
	 */
	function RenderAsMD()
	{
		$desc = $this->ShortDesc?$this->ShortDesc:'';
		$desc .= $this->LongDesc?"\n{$this->LongDesc}":'';
		$desc = str_replace(array('<at>','<code>','</code>'),array('@','```','```'),$desc);
		$desc = preg_replace('/<code ([^>]*)>/','```$1', $desc);
		return $desc;
	}
}