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
 
function mod_phpexcel_init()
{
	define('PHPEXCEL_ROOT', dirname(__FILE__) . '/phpexcel/');
	require_once(PHPEXCEL_ROOT."PHPExcel.php");
	// manually enable autoloading:
	require_once(PHPEXCEL_ROOT.'PHPExcel/Autoloader.php');
	require_once(PHPEXCEL_ROOT."excelculture.class.php");
}

//function mod_phpexcel_export_table($table)
//{
//	$wl_export = array(
//		'orders'
//	);
//
//	if( !in_array($table,$wl_export) )
//		return false;
//
//	// connection with the database
//	$ds = model_datasource("system");
//	$sql = "SELECT * FROM $table ORDER by created DESC";
//
//	$res = $ds->ExecuteSql($sql);
//	$objPHPExcel = new PHPExcel();
//	$objPHPExcel->getActiveSheet()->setTitle($table);
//
//	$row = 1;
//	while( !$res->EOF )
//	{
//		$col = 0;
//		$cnt = 1;
//		foreach( $res->fields as $cell )
//		{
//			if( $cnt%2==0 )
//			{
//				$cnt++;
//				continue;
//			}
//			
//			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col,$row,$cell);
//			$cnt++;
//			$col++;
//		}
//		$row++;
//		$res->MoveNext();
//	}
//
//	// Save as an Excel BIFF (xls) file
//	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
//
//   header('Content-Type: application/vnd.ms-excel');
//   header('Content-Disposition: attachment;filename="export.xls"');
//   header('Cache-Control: max-age=0');
//
//   $objWriter->save('php://output');
//   exit();
//
//}