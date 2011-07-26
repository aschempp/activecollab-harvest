<?php

// include ProjectConfigOptions model
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';

/**
 * Populate object options array
 *
 * @param NamedList $options
 * @param ProjectObject $object
 * @param User $user
 * @return null
 */
function harvest_handle_on_project_completed(&$project, &$user, &$status)
{
	if(!ProjectConfigOptions::getValue('harvest_project', $project) || !$user->getSystemPermission('can_submit_harvest') || !$user->getSystemPermission('project_management'))
	{
		return;
	}
	
	// Initialize Harvest API
	$HaPi = new HarvestAPI();
	$HaPi->setUser(UserConfigOptions::getValue('harvest_user', $user));
	$HaPi->setPassword(UserConfigOptions::getValue('harvest_pass', $user));
	$HaPi->setAccount('iserv');
	
	$objHarvestProject = $HaPi->getProject(ProjectConfigOptions::getValue('harvest_project', $project));
	
	if ($objHarvestProject->isSuccess() && $objHarvestProject->data->active)
	{
		$HaPi->toggleProject($objHarvestProject->data->id);
	}
}

