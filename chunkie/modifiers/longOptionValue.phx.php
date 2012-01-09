<?php
/*
 * description: retreives the 'long' option value for a select/checkbox/radio template variable.
 *              i.e. if the input option values of the tv are Yes==1||No==0 the modifier output
 *              for a given value of 1 is Yes. The option of the modifier is the name of the 
 *              template variable
 * usage:       [+value:longOptionValue=`tvname`+]
 */
$result = $modx->db->select('name, elements', $modx->getFullTableName('site_tmplvars'));
$members = $modx->db->makeArray($result);
foreach ($members as $member) {
	if ($member['name'] == $options) {
		// @EVAL detection
		if (substr($member['elements'], 0, 5) == '@EVAL') {
			$member['elements'] = eval(ltrim(substr($member['elements'], 5), " :"));
		}
		$optionValues = explode('||', $member['elements']);
		foreach ($optionValues as $optionValue) {
			$longOptionValue = explode('==', $optionValue);
			if ($longOptionValue[1] == $output) {
				return $longOptionValue[0];
			}
		}
	}
}
return $output;
?>