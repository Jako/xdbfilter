<?php
/*
 * xdbfilter
 * snippet to filter records from database
 *
 * @package xdbfilter
 * @subpackage main_file
 * Logic code for xdbfilter. Default parameter values can be changed in this file.
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
// The orderby for the SQL string filtering the database table
$xdbconfig['orderby'] = (isset($orderby)) ? $orderby : '';
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

$xdbconfig['id_'] = isset($id) ? $id.'_' : '';
$xdbconfig['preselect_arr'] = (trim($xdbconfig['preselect']) !== '') ? explode('||', $xdbconfig['preselect']) : array();

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
} elseif (isset($_REQUEST[$xdbconfig['id_'].'xdbfilterclear'])) {
    $xdbconfig['clear'] = 1;
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
$xdb->filterFields = $xdb->xdbconfig['filterFields'];
$xdb->outputfield = $xdb->xdbconfig['outputfield'];
$xdb->sql = $xdb->xdbconfig['sql'];

$outerTplData = array();
$pictureTplData = array();
$filterTplData = array();
$allrows = array();

// Display filter form
if ($xdb->xdbconfig['debug']) {
    echo '<pre>'.print_r($xdb, true).'</pre>';
}

if ($xdb->sql != '') {
    $rs = $modx->db->query($query);
} else {
    if ($xdb->xdbconfig['includeTvs'] && $xdb->xdbconfig['tablename'] == 'site_content') {

        // set query filter
        $where = $xdb->xdbconfig['where'].($xdb->xdbconfig['showempty'] === "0" ? (strlen($xdb->xdbconfig['where']) ? " AND " : "")."tvc.value IS NOT NULL AND tvc.value <> '[]'" : "");

        // set field names
        $docfields = $xdb->xdbconfig['outputFields'];
        if (($count = count($docfields)) > 0) {
            for ($i = 0; $i < $count; ++$i) {
                if ((strpos(ltrim($docfields[$i]), "tv") === 0) || ($docfields[$i] == ""))
                    unset($docfields[$i]);
                else {
                    $field = explode(":", $docfields[$i]);
                    $docfields[$i] = $field[0];
                }
            }
        }
        $tvNames = $tvElements = $tvCaption = array();

        array_push($docfields, 'id');
        foreach ($xdb->filterFields as $field) {
            if (strpos($field, "tv") === 0)
                $tvNames[] = substr($field, 2);
            else
                array_push($docfields, $field);
        }
        
        // remove double entries
        $vars = array('docfields', 'tvNames');
        foreach ($vars as $var) {
            ${$var} = array_unique(${$var});
            sort(${$var});
            reset(${$var});
        }

        // get a list of all documents and their tv values from the database
        $rows = $xdb->getAllVars($docfields, $tvNames, "name,caption,elements", $where, $xdb->xdbconfig['orderby'], $xdb->xdbconfig['limit'], $xdb->xdbconfig['offset']);

        if (is_array($rows) && count($rows)) {
            // get field values
            $tvNames = array();
            foreach ($rows as $row) {
                if (!isset($allrows[$row['id']])) {
                    $allrows[$row['id']] = array();
                    foreach ($docfields as $field) {
                        $allrows[$row['id']][$field] = $row[$field];
                    }
                }
                if (isset($row['tvValue'])) {
                    $value = $row['tvValue'];
                    if (stripos($val = trim($value), '@eval') === 0) {
                        $value = eval(ltrim(substr($val, 5), " :"));
                    }
                    $value = str_replace(array('{{', '}}'), '', $value);
                    $tvName = 'tv'.$row['tvName'];
                    if (isset($row['tvCaption']) && !isset($tvCaption[$tvName]))
                        $tvCaption[$tvName] = $row['tvCaption'];
                    if (isset($row['tvElements']) && !isset($tvElements[$tvName]))
                        $tvElements[$tvName] = $row['tvElements'];
                    if ((strpos($value, "||") !== false) && !isset($xdb->multiselectTvs[$tvName]))
                        $xdb->multiselectTvs[$tvName] = 1;

                    $allrows[$row['id']][$tvName] = $value;
                }
            }
        } else
            $allrows = array();

    } else {
        $rs = $modx->db->select('*', $modx->db->config['table_prefix'].$xdb->xdbconfig['tablename'], $xdb->xdbconfig['where'], '', $xdb->xdbconfig['offset'].','.$xdb->xdbconfig['limit']);
    }
}

if (isset($rs)) {
    while ($row = $modx->db->getRow($rs)) {
        array_push($allrows, $row);
    }
}

if ($xdb->xdbconfig['debug']) {
    echo '<pre>'.print_r($xdb, true).'</pre>';
    $modx->logEvent(3, 1, "<pre>".htmlentities(var_export($allrows,true))."</pre>" , 'xdbfilter');
    echo '<pre>allrows: '.count($allrows).'</pre>';
}

if (isset($xdb->xdbconfig['clear'])) {
    $preselectRows = $rows = $allrows;
} else {
    // first filter all rows which are in preselect parameter
    $preselectRows = $xdb->filterrows($allrows, $xdb->xdbconfig['preselect_arr']);

    // make outputFields placeholder
    $rows = $xdb->filterrows($preselectRows, $xdb->xdbconfig['filters_arr']);
}

if ($xdb->xdbconfig['debug']) {
/*    foreach ($rows[0] as $key => $rowfield) {
        echo $key.'<br/>';
    }*/
    $modx->logEvent(3, 1, "<pre>".htmlentities(var_export($rows,true))."</pre>" , 'xdbfilter_filtered');
    echo '<pre>Rows: '.count($rows).'</pre>';
}


foreach ($xdb->xdbconfig['outputFields'] as $field) {
    $listid = array();
    $fieldarr = explode(':', $field);
    $field = $fieldarr[0];
    $delimiter = count($fieldarr[1]) > 0 ? $fieldarr[1] : ',';
    foreach ($rows as $row) {
        $listid[] = $row[$field];
    }
    $listid = implode($delimiter, array_unique($listid));
    $listid = preg_replace('/'.$delimiter.'$/', '', $listid);
    $modx->setPlaceholder($xdb->xdbconfig['id_'].'xdbf_'.$field, $listid);

    if ($xdb->xdbconfig['debug']) {
        echo $xdb->xdbconfig['id_'].'xdbf_'.$field.' - '.$listid.'<br/>';
    }
}

// make filterform
if ($xdb->xdbconfig['display']) {

    if ($xdb->xdbconfig['refine'])
        $filterRows = $rows;
    else
        $filterRows = $preselectRows;

    foreach ($xdb->filterFields as $filterField) {
        $filterFieldValues = array();
        $multiselectTvValues = array();

        foreach ($filterRows as $row) {
            $filterRowVal = $row[$filterField];
            if (($xdb->multiselectTvs[$filterField] === 1) && !empty($filterRowVal)) {
                array_push($multiselectTvValues, $filterRowVal);
            }
            if (!in_array($filterRowVal, $filterFieldValues) && !empty($filterRowVal)) {
                array_push($filterFieldValues, $filterRowVal);
            }
        }
        if ($xdb->multiselectTvs[$filterField] === 1) {
            $multiselectTvValues = implode('||', $multiselectTvValues);
            $multiselectTvValues = explode('||', $multiselectTvValues);
            $multiselectTvValues = array_unique($multiselectTvValues, SORT_REGULAR);
            $filterFieldValues = $multiselectTvValues;
        }
        if ($xdb->xdbconfig['showempty'] !== '0') {
            array_push($filterFieldValues, $xdb->xdbconfig['showempty']);
        }

        $counter = 0;
        if (count($filterFieldValues) > 0) {
            
            $filters = array();
            if ($xdb->xdbconfig['filters_arr'] > 0) {
                foreach ($xdb->xdbconfig['filters_arr'] as $filter) {
                    $filter = explode('(', $filter);
                    $filterBy = $filter[0];
                    $filterValues = str_replace(')', '', $filter[1]);
                    $filters[$filterBy] = $filterValues;
                }
            }


            if (strpos($filterField, "tv") === 0) {
                // get tv list elements
                if (isset($tvElements[$filterField])) {
                    $elements = $tvElements[$filterField];
                    if (stripos($val = trim($elements), '@eval') === 0) {
                        $elements = eval(ltrim(substr($val, 5), " :"));
                    }
                    $elements = explode("||", $elements);
                    for ($i = 0, $count = count($elements); $i < $count; ++$i) {
                        list($optionName, $optionValue) = explode("==", $elements[$i]);
                        $elements[$i] = isset($optionValue) ? $optionValue : $optionName;
                        $optionNames[$i] = $optionName;
                    }

                    // sort option list
                    $new = array();
                    foreach ($filterFieldValues as $val) {
                        $val = ($pos = strpos($val, "||")) === false ? $val : substr($val, 0, $pos);
                        if (($pos = array_search($val, $elements)) !== false)
                            $new[$pos] = array('value' => $val, 'name' => $optionNames[$pos]);
                    }
                    ksort($new);

                    $filterFieldValues = $new;
                }
            }

            foreach ($filterFieldValues as $field) {
                $value = isset($field['value']) ? $field['value'] : $field;
                $filterValues = strtolower($filters[$filterField]);
                $values = explode('|', $filterValues);
                if (!isset($xdb->xdbconfig['clear']) && in_array(strtolower($value), $values)) {
                    $filterItemTplData['filteritemchecked'] = '1';
                } else {
                    $filterItemTplData['filteritemchecked'] = '0';
                }
                $filterItemTplData['filteritem'] = trim($filterField, 'tv');
                $filterItemTplData['filteritemname'] = $filterField.'[]';
                $filterItemTplData['filteritemvalue'] = $value;
                $filterItemTplData['filteritemcaption'] = isset($field['name']) ? $field['name'] : $field;
                $tpl = new xdbfChunkie($xdb->xdbconfig['filterItemTpl']);
                $tpl->addVar('xdbfilter', $filterItemTplData);
                $filterTplData['filteritems'] .= $tpl->Render();
                $counter++;
            }
            $filterTplData['filterfield'] = $filterField;
            $filterTplData['filterfieldcaption'] = isset($tvCaption[$filterField]) ? $tvCaption[$filterField] : $filterField;
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
