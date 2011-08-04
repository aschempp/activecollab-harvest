<?php

// Extends users controller
use_controller('admin');

class AdminHarvestController extends AdminController
{

	/**
	* Controller name
	*
	* @var string
	*/
	var $controller_name = 'admin_harvest';
	
	
	/**
	 * Add menu option
	 */
	function __construct($request)
	{
		parent::__construct($request);
		$this->wireframe->addBreadCrumb(lang('Harvest Integration'));
	}
	
	
	/**
	 * Present a form to enter Harvest Credentials for the system administrator
	 * @return void
	 */
	function index()
	{
		$harvest_data = $this->request->post('harvest');
		
		if (!is_foreachable($harvest_data))
		{
			$harvest_data = array
			(
				'account'			=> ConfigOptions::getValue('harvest_account'),
				'user'				=> ConfigOptions::getValue('harvest_user'),
				'pass'				=> ConfigOptions::getValue('harvest_pass'),
				'create_project'	=> ConfigOptions::getValue('harvest_create_project'),
				'create_client'		=> ConfigOptions::getValue('harvest_create_client'),
				'sync_interval'		=> ConfigOptions::getValue('harvest_sync_interval'),
			);
		}
		
		if ($this->request->isSubmitted())
		{
			$user = array_var($harvest_data, 'user', null);
			$pass = array_var($harvest_data, 'pass', null);
			$account = array_var($harvest_data, 'account', null);
			$create_project = array_var($harvest_data, 'create_project', null);
			$create_client = array_var($harvest_data, 'create_client', null);
			$sync_interval = array_var($harvest_data, 'sync_interval', null);
			
			ConfigOptions::setValue('harvest_account', $account);
			ConfigOptions::setValue('harvest_user', $user);
			ConfigOptions::setValue('harvest_create_project', $create_project);
			ConfigOptions::setValue('harvest_create_client', $create_client);
			ConfigOptions::setValue('harvest_sync_interval', $sync_interval);
			
			if (strlen($pass))
			{
				ConfigOptions::setValue('harvest_pass', $pass);
			}
			
			flash_success("Harvest settings successfully saved");
			$this->redirectTo('admin_harvest');
		}
		
		$this->smarty->assign(array
		(
			'harvest_data' => $harvest_data,
			'sync_intervals' => harvest_module_sync_intervals(),
		));
	}
}

