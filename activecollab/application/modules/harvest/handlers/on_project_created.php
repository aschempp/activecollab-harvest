<?php

/**
 * Handle on_system_permissions
 *
 * @param array $permissions
 * @return null
 */
function harvest_handle_on_project_created(&$objProject, &$objTemplate)
{
	if (!ConfigOptions::getValue('harvest_create_project'))
	{
		return;
	}
	
	// Initialize Harvest API
	$HaPi = new HarvestAPI();
	$HaPi->setUser(ConfigOptions::getValue('harvest_user'));
	$HaPi->setPassword(ConfigOptions::getValue('harvest_pass'));
	$HaPi->setAccount(ConfigOptions::getValue('harvest_account'));
	
	// activeCollab client company
	$strCompany = $objProject->getCompany();
	
	if (is_null($strCompany))
	{
		$objUser =& Authentication::instance()->provider->getUser();
		$strCompany = $objUser->getCompany();
	}
	
	// Harvest clients
	$objClients = $HaPi->getClients();
	
	if ($objClients->isSuccess())
	{
		$intClientID = false;
		
		foreach( $objClients->data as $objClient )
		{
			if ($objClient->name == $strCompany->getName())
			{
				$intClientID = $objClient->id;
				break;
			}
		}
		
		// Client not found in Harvest
		if ($intClientID === false)
		{
			if (ConfigOptions::getValue('harvest_create_client'))
			{
				// @todo implement client creation
				throw new Exception('Client not found in Harvest');
			}
			else
			{
				return;
			}
		}
		
		$objHarvestProject = new Harvest_Project();
		$objHarvestProject->set('name', $objProject->getName());
		$objHarvestProject->set('active', true);
		$objHarvestProject->set('client-id', $intClientID);
		
		$objResponse = $HaPi->createProject($objHarvestProject);
		
		ProjectConfigOptions::setValue('harvest_project', $objResponse->data, $objProject);
	}
}

