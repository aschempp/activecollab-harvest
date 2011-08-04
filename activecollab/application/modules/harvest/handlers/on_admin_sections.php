<?php

/**
* System module on_admin_sections event handler
*
* @package activeCollab.modules.system
* @subpackage handlers
*/

/**
* Add system admin tools sections
*
* @param array $sections
* @return null
*/
function harvest_handle_on_admin_sections(&$sections)
{
	$sections[ADMIN_SECTION_OTHER][SYSTEM_MODULE][] = array
	(
		'name'        => lang('Harvest'),
		'description' => lang('Settings'),
		'url'         => assemble_url('admin_harvest'),
		'icon'        => get_image_url('icon_big.gif', HARVEST_MODULE),
	);
}

