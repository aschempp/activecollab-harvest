<?php

// Extends users controller
use_controller('users', SYSTEM_MODULE);

class ProfileHarvestController extends UsersController
{

	/**
	* Controller name
	*
	* @var string
	*/
	var $controller_name = 'profile_harvest';
	
	
	/**
	 * Add menu option
	 */
	function __construct($request)
	{
		parent::__construct($request);
		$this->wireframe->addBreadCrumb(lang('Harvest Credentials'));
	}
	
	
	/**
	 * Present a form to enter Harvest Credentials for a user
	 * @return void
	 */
	function index()
	{
		if(!$this->logged_user->getSystemPermission('can_submit_harvest') || $this->active_user->getId() != $this->logged_user->getId())
		{
			$this->httpError(HTTP_ERR_FORBIDDEN, null, true, $this->request->isApiCall());
		}
		
		$harvest_data = $this->request->post('harvest');
		
		if (!is_foreachable($harvest_data))
		{
			$harvest_data = array
			(
				'user' => UserConfigOptions::getValue('harvest_user', $this->active_user),
				'pass' => UserConfigOptions::getValue('harvest_pass', $this->active_user),
			);
		}
		
		if ($this->request->isSubmitted())
		{
			$user = array_var($harvest_data, 'user', null);
			$pass = array_var($harvest_data, 'pass', null);
			
			UserConfigOptions::setValue('harvest_user', $user, $this->active_user);
			
			if (strlen($pass))
			{
				UserConfigOptions::setValue('harvest_pass', $pass, $this->active_user);
			}
			
			flash_success("Harvest settings successfully saved");
			$this->redirectTo('people_company_user', array('user_id' => $this->active_user->getId(), 'company_id' => $this->active_user->getCompanyId()));
		}
		
		$this->smarty->assign(array
		(
			'harvest_data' => $harvest_data,
		));
	}
}

