<?php

/**
* Select repository widget
*
* @param array $params
* @param Smarty $smarty
* @return string
*/
function smarty_function_select_harvest_sync_interval($params, &$smarty)
{
	$selected = null;
	
	if (isset($params['selected']))
	{
		$selected = $params['selected'];
		unset($params['selected']);
	}
	
	$options = array();
	
	foreach ($params['data'] as $key=>$item)
	{
		$option_attributes = $key == $selected ? array('selected' => true) : null;
		$options[] = option_tag($item, $key, $option_attributes);
	}
	
	return select_box($options, $params);
}

