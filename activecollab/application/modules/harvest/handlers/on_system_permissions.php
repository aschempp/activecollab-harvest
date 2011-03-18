<?php

/**
 * Handle on_system_permissions
 *
 * @param array $permissions
 * @return null
 */
function harvest_handle_on_system_permissions(&$permissions)
{
	$permissions[] = 'can_submit_harvest';
}

