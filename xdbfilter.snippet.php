<?php
/*
 * xdbfilter
 * snippet to filter records from database
 *
 * @package xdbfilter
 * @subpackage snippet_file
 *
 * @version 0.5 <11.01.2012>
 * @author Bruno Perner <b.perner@gmx.de>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * 
 * Modifications: 
 * Jako <thomas.jakobi@partout.info>
 * - multiselectTvs, PHx Modifier
 * Sammyboy <sam@gmx-topmail.de>
 * - speed improvements, bugfixes
 * 
 * Parameters see README:
 */

define('XDBFILTER_PATH', 'assets/snippets/xdbfilter/');

$output = '';
include (MODX_BASE_PATH.XDBFILTER_PATH.'xdbfilter.php');
return $output;
?>