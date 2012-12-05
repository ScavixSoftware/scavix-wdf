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
 
//require_once 'Image/Graph/Dataset.php';

class Graph
{
	var $Graph;
	var $Font;
	var $Legend;
	var $Plotarea;
	var $Plots = array();

	var $Width;
	var $Height;

	function Graph($title,$width = 800, $height = 300, $gridLines = true, $legend = true)
	{
		$this->Width = $width;
		$this->Height = $height;

		// create the graph
		$this->Graph = new Image_Graph($width, $height);
		$this->Graph->setBorderColor("#606060@1.0");

		$this->Font =& $this->Graph->addNew('font', 'Verdana');
		$this->Font->setSize(8);
		$this->Graph->setFont($this->Font);

		$this->Plotarea = $this->Graph->factory('plotarea');
		$this->Legend = $this->Graph->factory('legend');
		$this->Legend->setPlotarea($this->Plotarea);

		if( $legend )
		{
			$this->Graph->add(
			    $this->Graph->vertical(
			        $this->Graph->factory('title', array($title, 12)),
			        $this->Graph->vertical(
			            $this->Plotarea,
			            $this->Legend,
			            90
			        ),
			        5
			    )
			);
		}
		else
		{
			$this->Graph->add(
			    $this->Graph->vertical(
			        $this->Graph->factory('title', array($title, 12)),
		            $this->Plotarea,
			        5
			    )
			);
		}

		if( $gridLines )
		{
			$X_Linien = $this->Plotarea->addNew('line_grid', null, IMAGE_GRAPH_AXIS_X);
			$Y_Linien = $this->Plotarea->addNew('line_grid', null, IMAGE_GRAPH_AXIS_Y);

			$line_style = $this->Graph->factory('Image_Graph_Line_Dotted', array('Lavender','transparent'));
		    $X_Linien->setLineStyle($line_style);
		    $Y_Linien->setLineStyle($line_style);
		}
	}

	function FetchData($sql,$key_field,$val_field)
	{
		$res = array();
		$rs = model_datasource('system')->DB->execute($sql);
		while( !$rs->EOF )
		{
			$res[$rs->fields[$key_field]] = $rs->fields[$val_field];
			$rs->MoveNext();
		}
		return $res;
	}

	function &AddPlot($plot)
	{
		$plot_data =& $this->Graph->factory('dataset');
		foreach($plot->Data as $x=>$y)
			$plot_data->addPoint($x, $y);

		$plot_obj =& $this->Plotarea->addNew($plot->Style,$plot_data);
		$plot_obj->setTitle($plot->Title);
		$plot_obj->setLineColor($plot->LineColor);
		$plot_obj->setFillColor($plot->FillColor);

		return $plot_obj;
	}

	function Execute()
	{
		$this->Graph->done();
	}
}

?>