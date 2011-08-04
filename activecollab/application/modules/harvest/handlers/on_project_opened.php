<?php

// include ProjectConfigOptions model
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';

/**
 * Populate object options array
 *
 * @param NamedList $options
 * @param ProjectObject $object
 * @return null
 */
function harvest_handle_on_project_opened(&$project, &$status)
{
	$intHarvestID = ProjectConfigOptions::getValue('harvest_project', $project);
	
	if (!ConfigOptions::getValue('harvest_create_project') || !$intHarvestID)
	{
		return;
	}
	
	// Initialize Harvest API
	$HaPi = new HarvestAPI();
	$HaPi->setUser(ConfigOptions::getValue('harvest_user'));
	$HaPi->setPassword(ConfigOptions::getValue('harvest_pass'));
	$HaPi->setAccount(ConfigOptions::getValue('harvest_account'));
	
	$objHarvestProject = $HaPi->getProject($intHarvestID);
	
	if ($objHarvestProject->isSuccess() && !$objHarvestProject->data->active)
	{
		$HaPi->toggleProject($objHarvestProject->data->id);
	}
}

