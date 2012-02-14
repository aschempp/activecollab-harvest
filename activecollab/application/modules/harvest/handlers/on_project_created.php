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
	$objCompany = $objProject->getCompany();
	
	if (is_null($objCompany))
	{
		$objUser =& Authentication::instance()->provider->getUser();
		$objCompany = $objUser->getCompany();
	}
	
	// Harvest clients
	$objClients = $HaPi->getClients();
	
	if ($objClients->isSuccess())
	{
		$intClientID = false;
		
		foreach( $objClients->data as $objClient )
		{
			if ($objClient->name == $objCompany->getName())
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
				$objClient = new Harvest_Client();
				$objClient->name = $objCompany->getName();
				$objClient->details = $objCompany->getConfigValue('office_address');
				$objClient->highrise_id = $objCompany->getConfigValue('highrise_id');
				$objResult = $HaPi->createClient($objClient);
				
				if (!$objResult->isSuccess())
				{
					return;
				}
				
				$intClientID = $objResult->data;
			}
			else
			{
				return;
			}
		}
		
		$objHarvestProject = new Harvest_Project();
		$objHarvestProject->name = $objProject->getName();
		$objHarvestProject->notes = strip_tags($objProject->getOverview());
		$objHarvestProject->active = true;
		$objHarvestProject->client_id = $intClientID;
		
		$objResponse = $HaPi->createProject($objHarvestProject);
		
		ProjectConfigOptions::setValue('harvest_project', $objResponse->data, $objProject);
	}
}

