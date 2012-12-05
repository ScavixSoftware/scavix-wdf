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
 
?>
<div>
	<div><?=$title?></div>
	<div style="width:<?=$width?>px; height:<?=$height?>px; float:left;">
	    <img id='current_map' src="<?=$map?>" width="<?=$width?>" height="<?=$height?>"/>
	</div>
	<div style="float:left;">
	<?
		foreach($legends as $legend=>$value)
		{
		?><div style='margin-top:3px; margin-left:5px; background-color:#<?=$value[$i]?>;'><?=$legend?></div><?
			
			$pieces = count($value);
			$factor = 100/$pieces;
			$begin = 100;
			?>
			<div style='margin-top:3px; margin-left:5px;'>
			<?
			for($i=0;$i<$pieces;$i++)
			{
			?>
				<div style="width:1px; float:left; background-color:#<?=$value[$i]?>;">&nbsp;</div>
			<?
			}
			?>
			<div style="clear:both;"></div>
			</div>
			<?
		}
	?>
	</div>
</div>
<div style="clear:both;"></div>