<?php

// Extends users controller
use_controller('project', SYSTEM_MODULE);

class ProjectHarvestController extends ProjectController
{

	/**
	* Controller name
	*
	* @var string
	*/
	var $controller_name = 'project_harvest';
	
	/**
	 * Harvest API instance
	 */
	var $HaPi;
	
	
	/**
	 * Add menu option
	 */
	function __construct($request)
	{
		parent::__construct($request);
		
		// Add breadcrumb
		$this->wireframe->addBreadCrumb(lang('Harvest Settings'));
		
		// Initialize Harvest API
		$this->HaPi = new HarvestAPI();
		$this->HaPi->setUser(ConfigOptions::getValue('harvest_user'));
		$this->HaPi->setPassword(ConfigOptions::getValue('harvest_pass'));
		$this->HaPi->setAccount(ConfigOptions::getValue('harvest_account'));
	}
	
	
	/**
	 * Present a form to link a Harvest project with this aC project
	 * @return void
	 */
	function index()
	{
		if(!$this->logged_user->getSystemPermission('can_submit_harvest') || !$this->logged_user->getSystemPermission('project_management'))
		{
			$this->httpError(HTTP_ERR_FORBIDDEN, null, true, $this->request->isApiCall());
		}
		
		$config = $this->request->post('config');
		
		if (!is_foreachable($config))
		{
			$config = array
			(
				'project' => ProjectConfigOptions::getValue('harvest_project', $this->active_project),
			);
		}
		
		if ($this->request->isSubmitted())
		{
			ProjectConfigOptions::setValue('harvest_project', (int)array_var($config, 'project', null), $this->active_project);
			
			cache_remove_by_pattern('*project_config*');
						
			flash_success("Harvest settings successfully saved");
			$this->redirectTo('project_overview', array('project_id' => $this->active_project->getId()));
		}
		
		// Retrieve active projects from Harvest
		$objDaily = $this->HaPi->getDailyActivity();
		$arrProject = array();
		if ($objDaily->isSuccess())
		{
			foreach( $objDaily->data->projects as $objProject )
			{
				$arrProjects[$objProject->client][$objProject->id] = $objProject->name;
			}
		}
		else
		{
			flash_error('Connecting to Harvest failed. Please check your access credentials.');
		}
		
		natcasesort($arrProjects);
		
		$objCompany = $this->active_project->getCompany();
		if (instance_of($objCompany, 'Company') && is_array($arrProjects[$objCompany->getName()]))
		{
			$arrCompany = array($objCompany->getName() => $arrProjects[$objCompany->getName()]);
			unset($arrProjects[$objCompany->getName()]);
			
			$arrBuffer = array_splice($arrProjects, 0, $intIndex);
			$arrProjects = array_merge_recursive($arrBuffer, $arrCompany, $arrProjects);
		}
		
		$this->smarty->assign(array
		(
			'projects'	=> $arrProjects,
			'config'	=> $config,
		));
	}
}

