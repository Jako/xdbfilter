<?php
/* -------------------------------------------------------------------------------
 * Logic code for xdbfilter. Default parameter values can be changed in this file.
 * Do not touch anything unless you know what you are doing.
 * -----------------------------------------------------------------------------*/

global $modx;

if (!defined('XDBFILTER_PATH')) {
	$output = 'xdbfilter setup path is not defined, please check the snippet code in MODx manager.';
	return;
}

// Include a custom config file if specified
if (isset($config)) {
	$configFile = MODX_BASE_PATH.XDBFILTER_PATH.'configs/'.$config.'.config.php';
}
if (file_exists($configFile)) {
	include_once ($configFile);
}

/* -------------------------------------------------------
 * Default values (can be overridden in the snippet call),
 * No need to edit unless you know what you're doing!
 * -----------------------------------------------------*/

// Display debug code
$xdbconfig['debug'] = (isset($debug)) ? $debug : 0;
// MODX tablename (defaults to site_content)
$xdbconfig['tablename'] = (isset($tablename)) ? $tablename : 'site_content';
// Chunkname for the filter boxes outer template (@FILE: or @CODE: binding possible)
$xdbconfig['filterOuterTpl'] = (isset($filterOuterTpl)) ? $filterOuterTpl : '@FILE:'.XDBFILTER_PATH.'templates/filterOuterTpl.html';
// Chunkname for a filter box template (@FILE: or @CODE: binding possible)
$xdbconfig['filterTpl'] = (isset($filterTpl)) ? $filterTpl : '@FILE:'.XDBFILTER_PATH.'templates/filterTpl.html';
// Chunkname for a filter box single item template (@FILE: or @CODE: binding possible)
$xdbconfig['filterItemTpl'] = (isset($filterItemTpl)) ? $filterItemTpl : '@FILE:'.XDBFILTER_PATH.'templates/filterItemTpl.html';
// An unique id if there is more than one xdbfilter call on a page
$xdbconfig['id'] = isset($id) ? $id : '';
// Shows a checkbox with this string - if checked the filter displays all items in this section that have no value set in this section
$xdbconfig['showempty'] = isset($showempty) ? $showempty : '0';
// Fot this comma separated fields the filter boxes are generated
$xdbconfig['filterFields'] = isset($filterFields) ? explode(',', $filterFields) : array();
// A Filter that is preselected (can be modified by $_GET and $_REQUEST)
$xdbconfig['filters'] = isset($filters) ? $filters : '';
// Same as $filters, but it can't be modified by $_GET and $_REQUEST
$xdbconfig['preselect'] = isset($preselect) ? $preselect : '';
// The offset for the SQL string filtering the database table
$xdbconfig['offset'] = (isset($offset)) ? $offset : 0;
// The limit for the SQL string filtering the database table
$xdbconfig['limit'] = (isset($limit)) ? $limit : 9999999;
// The the filter boxes will be filtered too, so the result can be refined easier
$xdbconfig['refine'] = (isset($refine)) ? $refine : 0;
// A comma separated list of fields. The content of this (filtered) fields is listed comma separated in a placeholder [+xdbf_FIELDNAME+] (defaults to 'id', particular [+xdbf_id+]).
// If the separator should not be a comma it can be defined by adding :SEPARATOR after the fieldname i.e. `id:|`
$xdbconfig['outputFields'] = isset($outputFields) ? explode(',', $outputFields) : array('id');
// Write an own sql select for the filter query
$xdbconfig['where'] = isset($where) ? $where : '';
// Write an own sql where clause for the filter query
$xdbconfig['sql'] = isset($sql) ? $sql : '';
// Include TVs for MODX documents
$xdbconfig['includeTvs'] = (isset($includeTvs)) ? $includeTvs : 0;
// Show the filter boxes
$xdbconfig['display'] = isset($display) ? $display : 'filterbox';
// comma separated list of TVs that are defined as multiselect variables (the field contains a || separated list of possible values)
$xdbconfig['multiselectTvs'] = (isset($multiselectTvs)) ? $multiselectTvs : '';

$xdbconfig['id_'] = isset($id) ? $id.'_' : '';
$xdbconfig['preselect_arr'] = (trim($xdbconfig['preselect']) !== '') ? explode('||', $xdbconfig['preselect']) : array();
$xdbconfig['multiselectTvs_arr'] = (trim($xdbconfig['multiselectTvs']) !== '') ? explode(',', $xdbconfig['multiselectTvs']) : array();
;
$xdbconfig['where'] = str_replace('eq', '=', $xdbconfig['where']);
$xdbconfig['sql'] = str_replace('eq', '=', $xdbconfig['sql']);

// get or request filters
$xdbconfig['filters'] = isset($_GET[$xdbconfig['id_'].'filters']) ? $_GET[$xdbconfig['id_'].'filters'] : $xdbconfig['filters'];
$xdbconfig['filters'] = isset($_REQUEST[$xdbconfig['id_'].'filters']) ? $_REQUEST[$xdbconfig['id_'].'filters'] : $xdbconfig['filters'];
$xdbconfig['filters_arr'] = (trim($xdbconfig['filters']) !== '') ? explode('||', $xdbconfig['filters']) : array();

// get or request refine
$xdbconfig['refine'] = isset($_GET['refine']) ? $_GET['refine'] : $xdbconfig['refine'];
$xdbconfig['refine'] = isset($_REQUEST['refine']) ? $_REQUEST['refine'] : $xdbconfig['refine'];

// read filter-checkboxes and make filterstring
$link = isset($xdbconfig['showempty']) ? '&showempty='.$xdbconfig['showempty'] : '';

if (isset($_REQUEST[$xdbconfig['id_'].'xdbfiltersubmit'])) {
	$filter = '';
	$filtercounter = 0;
	foreach ($xdbconfig['filterFields'] as $filterField) {
		$count = count($_REQUEST[$filterField]);
		if ($count > 0) {
			if ($filtercounter > 0)
				$filter .= ')||';
			$filter .= $filterField.'(';
			for ($i = 0; $i < $count; $i++) {
				if ($i > 0)
					$filter .= '|';
				$filter .= $_REQUEST[$filterField][$i];
			}
			$filtercounter++;
		}
	}
	$filter .= $filter !== '' ? ')' : '';
	$xdbconfig['filters'] = $filter;
	$xdbconfig['filters_arr'] = (trim($xdbconfig['filters']) !== '') ? explode('||', $xdbconfig['filters']) : array();
	$link .= '&filters='.$filter;
} else {
	$link .= '&filters='.$xdbconfig['filters'];
}
$modx->setPlaceholder($xdbconfig['id_'].'filterlink', $link);

/* -------------------------
 * Snippet logic starts here
 * ---------------------- */

if (!class_exists('xdbfilter')) {
	$xdbclass = MODX_BASE_PATH.XDBFILTER_PATH.'xdbfilter.class.inc.php';
	if (file_exists($xdbclass)) {
		include_once ($xdbclass);
	} else {
		$output = 'Cannot find xdbfilter class file! ('.$xdbclass.')';
		return;
	}
}

// Initialize class
if (class_exists('xdbfilter')) {
	$xdb = new xdbfilter($xdbconfig, $strings);
} else {
	$output = 'xdbfilter class not found';
	return;
}

if (!class_exists('xdbfChunkie')) {
	$chunkieclass = MODX_BASE_PATH.XDBFILTER_PATH.'chunkie/chunkie.class.inc.php';
	if (file_exists($chunkieclass)) {
		include_once $chunkieclass;
	} else {
		$output = 'Cannot find chunkie class file! ('.$chunkieclass.')';
		return;
	}
}

// Initialize variables
$xdb->rows_tbl = $modx->db->config['dbase'].'.'.$modx->db->config['table_prefix'].$xdb->xdbconfig['tablename'];
$xdb->filterFields = $xdbconfig['filterFields'];
$xdb->outputfield = $xdbconfig['outputfield'];
$xdb->sql = $xdbconfig['sql'];

$outerTplData = array();
$pictureTplData = array();
$filterTplData = array();

// Display filter form
if ($xdb->xdbconfig['debug']) {
	echo '<pre>'.print_r($xdb, true).'</pre>';
}

if ($xdb->sql != '') {
	$rs = $modx->db->query($query);
} else {
	$rs = $modx->db->select('*', $modx->db->config['table_prefix'].$xdb->xdbconfig['tablename'], $xdb->xdbconfig['where'], '', $xdb->xdbconfig['offset'].','.$xdb->xdbconfig['limit']);
}

$allrows = array();
while ($row = $modx->db->getRow($rs)) {

	// append TVs to all rows
	if ($xdb->xdbconfig['includeTvs'] && $xdbconfig['tablename'] == 'site_content') {
		$idnames = '*';
		$fields = '*';
		$docid = $row['id'];
		$published = 1;

		$templatevars = $xdb->getTemplateVars($idnames, $fields, $docid, $published);

		foreach ($templatevars as $tv) {
			$value = preg_replace(array('/{{/', '/}}/'), '', $tv['value']);
			$row['tv'.$tv['name']] = $value;
		}
	}

	array_push($allrows, $row);
}

// first filter all rows which are in preselect parameter
$preselectRows = $xdb->filterrows($allrows, $xdbconfig['preselect_arr'], $xdbconfig['multiselectTvs_arr']);

// make outputFields placeholder
$rows = $xdb->filterrows($preselectRows, $xdbconfig['filters_arr'], $xdbconfig['multiselectTvs_arr']);

if ($xdb->xdbconfig['debug']) {
	foreach ($rows[0] as $key => $rowfield) {
		echo $key.'<br/>';
	}
}

foreach ($xdbconfig['outputFields'] as $field) {
	$listid = '';
	$fieldarr = explode(':', $field);
	$field = $fieldarr[0];
	$delimiter = count($fieldarr[1]) > 0 ? $fieldarr[1] : ',';
	foreach ($rows as $row) {
		$listid .= $row[$field].$delimiter;
	}
	$listid = preg_replace('/'.$delimiter.'$/', '', $listid);
	$modx->setPlaceholder($xdbconfig['id_'].'xdbf_'.$field, $listid);

	if ($xdb->xdbconfig['debug']) {
		echo $xdbconfig['id_'].'xdbf_'.$field.' - '.$listid.'<br/>';
	}
}

// make filterform
if ($xdbconfig['display']) {

	if ($xdbconfig['refine'])
		$filterRows = $rows;
	else
		$filterRows = $preselectRows;

	foreach ($xdb->filterFields as $filterField) {
		$filterFieldValues = array();
		$multiselectTvValues = array();

		foreach ($filterRows as $row) {
			if (in_array($filterField, $xdbconfig['multiselectTvs_arr']) && !empty($row[$filterField])) {
				array_push($multiselectTvValues, $row[$filterField]);
			}
			if (!in_array($row[$filterField], $filterFieldValues) && !empty($row[$filterField])) {
				array_push($filterFieldValues, $row[$filterField]);
			}
		}
		if (in_array($filterField, $xdbconfig['multiselectTvs_arr'])) {
			$multiselectTvValues = implode('||', $multiselectTvValues);
			$multiselectTvValues = explode('||', $multiselectTvValues);
			$multiselectTvValues = array_unique($multiselectTvValues, SORT_REGULAR);
			$filterFieldValues = $multiselectTvValues;
		}
		if ($xdbconfig['showempty'] !== '0') {
			array_push($filterFieldValues, $xdbconfig['showempty']);
		}

		$counter = 0;
		if (count($filterFieldValues) > 0) {
			$filters = array();
			if ($xdbconfig['filters_arr'] > 0) {
				foreach ($xdbconfig['filters_arr'] as $filter) {
					$filter = explode('(', $filter);
					$filterBy = $filter[0];
					$filterValues = str_replace(')', '', $filter[1]);
					$filters[$filterBy] = $filterValues;
				}
			}

			foreach ($filterFieldValues as $value) {
				$filterValues = strtolower($filters[$filterField]);
				$values = explode('|', $filterValues);
				if (in_array(strtolower($value), $values)) {
					$filterItemTplData['filteritemchecked'] = '1';
				} else {
					$filterItemTplData['filteritemchecked'] = '0';
				}
				$filterItemTplData['filteritem'] = trim($filterField, 'tv');
				$filterItemTplData['filteritemname'] = $filterField.'[]';
				$filterItemTplData['filteritemvalue'] = $value;
				$tpl = new xdbfChunkie($xdb->xdbconfig['filterItemTpl']);
				$tpl->addVar('xdbfilter', $filterItemTplData);
				$filterTplData['filteritems'] .= $tpl->Render();
				$counter++;
			}
			$filterTplData['filterfield'] = $filterField;
			$tpl = new xdbfChunkie($xdb->xdbconfig['filterTpl']);
			$tpl->addVar('xdbfilter', $filterTplData);
			$outerTplData['filterfields'] .= $tpl->Render();
			$filterTplData = array();
		}
	}

	$tpl = new xdbfChunkie($xdb->xdbconfig['filterOuterTpl']);
	$outerTplData['filterfieldnames'] = implode(',', $xdb->filterFields);
	$outerTplData['strings'] = $xdb->strings;
	$outerTplData['config'] = $xdb->xdbconfig;
	$tpl->addVar('xdbfilter', $outerTplData);
	$output = $tpl->Render();
}
return;
?>
