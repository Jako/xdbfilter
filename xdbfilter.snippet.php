<?php
/*
 * xdbfilter
 * snippet to filter records from database
 * License GPL
 * Version 0.3.1 (13.09.2011)
 * Author: Bruno Perner <b.perner@gmx.de>
 * 
 * Modifications: Thomas Jakobi <thomas.jakobi@partout.info>
 * - multiselectTvs, PHx Modifier
 * 
 * Parameters see README:
 */

define(XDBFILTER_PATH, 'assets/snippets/xdbfilter/');

$output = '';
include (MODX_BASE_PATH.XDBFILTER_PATH.'xdbfilter.php');
return $output;
?>