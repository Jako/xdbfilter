<?php
/* ----------------------------------------------------------
 * This file includes the functions used in xdbfilter snippet
 * --------------------------------------------------------*/

class xdbfilter {
    // Declaring private variables
    var $xdbconfig;

    // -----------------
    // Constructor class
    // -----------------

    function xdbfilter($xdbconfig, $strings) {
        // Set template variables to empty var
        $this->xdbconfig = $xdbconfig;
        $this->strings = $strings;
    }

    // ---------------------------
    // function to filter the rows
    // ---------------------------

    function filterrows($rows, $filters_arr, $multiselectTvs_arr = array()) {

        $filters = array();
        $filterBys = array();
        if ($filters_arr > 0) {
            foreach ($filters_arr as $filter) {
                $filter = explode('(', $filter);
                $filterBy = $filter[0];
                array_push($filterBys, $filterBy);
                $filterValues = str_replace(')', '', $filter[1]);
                $filters[$filterBy] = $filterValues;
            }
        }

        $outputrows = array();
        foreach ($rows as $row) {
            if (count($filterBys) == 0) {
                $pusharray = 1;
            } else {
                foreach ($filterBys as $filterBy) {
                    $filterRowVal = $row[$filterBy];
                    $pusharray = 0;
                    $filterValues = $filters[$filterBy];
                    if ($filterValues == '')
                        unset($filterValues);

                    if (isset($filterValues)) {
                        $values = explode("|", $filterValues);
                        foreach ($values as $filterValue) {

                            if ($xdbconfig['showempty'] == $filterValue && (trim($filterRowVal) == '' || empty($filterRowVal))) {
                                $pusharray = 1;
                            } elseif (trim($filterRowVal) !== '' && !empty($filterRowVal)) {
                                // If filterTv is a multiselectTv
                                if (in_array($filterBy, $multiselectTvs_arr)) {
                                    // If filterTv value matches one part of the multiseletTv value
                                    if (in_array($filterValue, explode('||', $filterRowVal))) {
                                        $pusharray = 1;
                                    }
                                } else {
                                    // If filterTv is equal to the value
                                    if (strtolower($filterRowVal) == strtolower($filterValue)) {
                                        $pusharray = 1;
                                    }
                                }
                            }
                        }
                    } elseif (!empty($filterRowVal) || trim($filterRowVal) !== '') {
                        $pusharray = 0;
                    }
                    if ($pusharray == 0)
                        break;
                }
            }
            if ($pusharray == 1) {
                array_push($outputrows, $row);
            }
        }
        return $outputrows;
    }

    // ---------------------------------
    // function to get all template vars
    // ---------------------------------
    
    function getAllVars($docFields = "*", $tvs = "*", $tvFields = "*", $where = "", $orderby = "", $limit = "", $offset = "0") {
        global $modx;
        if (!is_array($tvs) && ($tvs === ""))
            $tvs = "*";

        $result = array();

        // get document fields
        if ($docFields === "*")
            $docFields = "doc.*";
        elseif (!is_array($docFields))
            $docFields = explode(',', $docFields);

        for ($i = 0, $count = count($docFields); $i < $count; ++$i) {
            $docFields[$i] = "doc.".trim($docFields[$i]);
        }
        $docFields = implode(',', $docFields);

        // get user defined template variables
        if (($tvs !== "*") && !is_array($tvs)) {
            $tvs = preg_replace("/^\s/i", "", explode(',', $tvs));
        }

        // get the names of the database fields of template variables
        if ($tvFields === "*") {
            $table = $modx->db->select('*', $modx->getFullTableName('site_tmplvars'));
            $tvFields = $modx->db->getColumnNames($table);
        } else
            $tvFields = explode(',', $tvFields);

        for ($i = 0, $count = count($tvFields); $i < $count; ++$i) {
            $tvFields[$i] = "tv.".($tvFields[$i] = trim($tvFields[$i]))." AS tv".ucfirst($tvFields[$i]);
        }
        $tvFields = implode(',', $tvFields);

        // set filter rules
        $where .=
            (strlen($where) ? " AND " : "").
            (($tvs === "*") ? "tv.id<>0" : (is_numeric($idnames[0]) ? "tv.id" : "tv.name")." IN ('".implode("','", $tvs)."')");

        $orderby = strlen($orderby) ? "tv.".implode(",tv.", preg_replace("/^\s/i", "", explode(',', $orderby))) : "";

        // query
        $sql =
            "SELECT ".
                $docFields.",".$tvFields.", IF(tvc.value!='',tvc.value,tv.default_text) as tvValue ".
            "FROM ( ".
                $modx->getFullTableName('site_content')." AS doc, ".
                $modx->getFullTableName('site_tmplvars')." AS tv ) ".
            "LEFT JOIN ".
                $modx->getFullTableName('site_tmplvar_contentvalues')." AS tvc ".
                "ON (tvc.tmplvarid=tv.id) AND (tvc.contentid=doc.id) ".
            "WHERE ".$where." ".
            ($orderby !== "" ? "ORDER BY ".$orderby." " : "").
            ($limit !== "" ? "LIMIT ".$offset.",".$limit." " : "");

        $rs = $modx->db->query($sql);
        
        $result = ($modx->db->getRecordCount($rs) >= 1) ?  $modx->db->makeArray($rs) : false;

        return $result;
    }
}
?>
