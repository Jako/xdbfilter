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
					$pusharray = 0;
					$filterValues = $filters[$filterBy];
					if ($filterValues == '')
						unset($filterValues);

					if (isset($filterValues)) {
						$values = explode("|", $filterValues);
						foreach ($values as $filterValue) {

							if ($xdbconfig['showempty'] == $filterValue && (trim($row[$filterBy]) == '' || empty($row[$filterBy]))) {
								$pusharray = 1;
							} elseif (trim($row[$filterBy]) !== '' && !empty($row[$filterBy])) {
								// If filterTv is a multiselectTv
								if (in_array($filterBy, $multiselectTvs_arr)) {
									// If filterTv value matches one part of the multiseletTv value
									if (in_array($filterValue, explode('||', $row[$filterBy]))) {
										$pusharray = 1;
									}
								} else {
									// If filterTv is equal to the value
									if (strtolower($row[$filterBy]) == strtolower($filterValue)) {
										$pusharray = 1;
									}
								}
							}
						}
					} elseif (!empty($row[$filterBy]) || trim($row[$filterBy]) !== '') {
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

	// -----------------------------
	// function to get template vars
	// -----------------------------

	function getTemplateVars($idnames = array (), $fields = "*", $docid = "", $published = 1) {
		global $modx;
		if (($idnames != '*' && !is_array($idnames)) || count($idnames) == 0) {
			return false;
		} else {
			$result = array();

			// get user defined template variables
			$fields = ($fields == "") ? "tv.*" : 'tv.'.implode(',tv.', preg_replace("/^\s/i", "", explode(',', $fields)));
			$sort = ($sort == "") ? "" : 'tv.'.implode(',tv.', preg_replace("/^\s/i", "", explode(',', $sort)));
			if ($idnames == "*")
				$query = "tv.id<>0";
			else
				$query = (is_numeric($idnames[0]) ? "tv.id" : "tv.name")." IN ('".implode("','", $idnames)."')";
			$sql = "SELECT $fields, IF(tvc.value!='',tvc.value,tv.default_text) as value ";
			$sql .= "FROM ".$modx->getFullTableName('site_tmplvars')." tv ";
			$sql .= "INNER JOIN ".$modx->getFullTableName('site_tmplvar_templates')." tvtpl ON tvtpl.tmplvarid = tv.id ";
			$sql .= "LEFT JOIN ".$modx->getFullTableName('site_tmplvar_contentvalues')." tvc ON tvc.tmplvarid=tv.id AND tvc.contentid = '".$docid."' ";
			$sql .= "WHERE ".$query;
			$rs = $modx->dbQuery($sql);
			for ($i = 0; $i < @$modx->recordCount($rs); $i++) {
				array_push($result, @$modx->fetchRow($rs));
			}
			return $result;
		}
	}

}
?>