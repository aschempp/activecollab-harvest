<?php

/**
 * Handle on_system_permissions
 *
 * @param array $permissions
 * @return null
 */
function harvest_handle_on_project_created(&$project, &$template)
{
	$user =& Authentication::instance()->provider->getUser();
	
	if(!$user->getSystemPermission('can_submit_harvest') || !$user->getSystemPermission('project_management'))
	{
		return;
	}
	
	// Initialize Harvest API
	$HaPi = new HarvestAPI();
	$HaPi->setUser(UserConfigOptions::getValue('harvest_user', $user));
	$HaPi->setPassword(UserConfigOptions::getValue('harvest_pass', $user));
	$HaPi->setAccount('iserv');
	
	// activeCollab client company
	$company = $project->getCompany();
	
	if (is_null($company))
	{
		$company = $user->getCompany();
	}
	
	// Harvest clients
	$clients = $HaPi->getClients();
	
	if ($clients->isSuccess())
	{
		$client_id = false;
		
		foreach( $clients->data as $client )
		{
			if ($client->name == $company->getName())
			{
				$client_id = $client->id;
				break;
			}
		}
		
		// Add client to Harvest
		if ($client_id === false)
		{
			throw new Exception('Client not available in Harvest');
		}
		
		$harvestProject = new Harvest_Project();
		$harvestProject->set('name', $project->getName());
		$harvestProject->set('active', true);
		$harvestProject->set('client-id', $client_id);
		
		$HaPi->createProject($harvestProject);
	}
}

