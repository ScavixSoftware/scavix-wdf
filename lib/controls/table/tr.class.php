<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) 2013-2019 Scavix Software Ltd. & Co. KG
 * Copyright (c) since 2019 Scavix Software GmbH & Co. KG
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
 * @author Scavix Software Ltd. & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright 2012-2019 Scavix Software Ltd. & Co. KG
 * @author Scavix Software GmbH & Co. KG https://www.scavix.com <info@scavix.com>
 * @copyright since 2019 Scavix Software GmbH & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls\Table;

use ScavixWDF\Base\Control;

/**
 * A table row written as div.
 * 
 */
class Tr extends Control
{
//    public $row_groups = [];
    public $options = false;
    public $current_cell = false;
	public $model = false;
    
    public static $CURRENTLY_ODD = false;

	/**
	 * @param array $options Currently none
	 */
	function __construct($options=false)
	{
		parent::__construct("div");
		$this->class = "tr";
        $this->options = $options;

		if( $options )
		{
			if( isset($options['oddclass']) && isset($options['evenclass']) )
			{
				if( !isset($options['hidden']) || !$options['hidden'] )
				{
//					$this->class = Tr::$CURRENTLY_ODD?$options['oddclass']:$options['evenclass'];
//					Tr::$CURRENTLY_ODD = !Tr::$CURRENTLY_ODD;
				}
			}
			if( isset($options['tr_class']) )
				$this->class = $options['tr_class'];
		}
	}

	/**
	 * Gets the current cell.
	 * 
	 * @return Td The current cell of false if none
	 */
	function &CurrentCell()
	{
		return $this->current_cell;
	}

	/**
	 * Creates a new cell.
	 * 
	 * @param mixed $content Contents for it
	 * @param array $options See <Td::__construct> for options
	 * @return Td The created cell
	 */
    function &NewCell($content=false,$options=false)
    {
        $cell = new Td($options);
        if( $content !== false )
            $cell->content($content);

        $this->current_cell =& $this->content($cell);
        return $this->current_cell;
    }

	/**
	 * Get the cell at index.
	 * 
	 * @param int $index Zero based index
	 * @return Td The cell
	 */
	function &GetCell($index)
	{
		return $this->_content[$index];
	}
	
	/**
	 * Returns all cells.
	 * 
	 * @return array List of <Td> objects
	 */
	function Cells()
	{
		return $this->_content;
	}
	
	/**
	 * Gets the cell count.
	 * 
	 * @return int Count of cells
	 */
	function CountCells()
	{
		return count($this->_content);
	}
    
    /**
     * Applies the format to all cells.
     * 
     * @param Table $table Optional <Table> object (this <Tr> belongs to)
     * @return void
     */
    function FormatCells($table=false)
    {
        if( $this->_parent instanceof THead )
            return;
        
        $tab = $table?$table:$this->closest("Table");
        $culture = $tab?$tab->Culture:false;
        $rcnt = count($this->_content);
        for($i=0; $i<$rcnt; $i++)
        {
            if( !isset($this->_content[$i]) )
                continue;
            if( $this->_content[$i]->CellFormat )
                $this->_content[$i]->CellFormat->Format($this->_content[$i], $culture);
            elseif( isset($tab->ColFormats[$i]) )
                $tab->ColFormats[$i]->Format($this->_content[$i], $culture);
        }
    }

	/**
	 * @override
	 */
	function WdfRender()
	{
		if( isset($this->options['oddclass']) && isset($this->options['evenclass']) )
		{
			if( !isset($this->_css['display']) || $this->_css['display'] != "none" )
			{
				$this->class = Tr::$CURRENTLY_ODD?$this->options['oddclass']:$this->options['evenclass'];
				Tr::$CURRENTLY_ODD = !Tr::$CURRENTLY_ODD;
			}
		}

		return parent::WdfRender();
	}
	
    /**
     * Sets the alignment for all columns.
     * 
     * @param array $alignment Array of alignments for each column l(eft)|r(ight)|c(enter)
     * @return static
     */
	function SetAlignment($alignment)
	{
		foreach( $alignment as $i=>$a )
		{
            $cell = $this->GetCell($i);
            if( !$cell )
                continue;
			switch( strtolower($a) )
			{
				case 'l':
				case 'left':
					$cell->align = 'left';
					break;
				case 'r':
				case 'right':
					$cell->align = 'right';
					break;
				case 'c':
				case 'center':
					$cell->align = 'center';
					break;
			}
		}
		return $this;
	}
}
