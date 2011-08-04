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
	
	
	/**
	 * Manually trigger a sync
	 *
	 * @param	void
	 * @return	void
	 */
	function sync()
	{
		if(!$this->logged_user->getSystemPermission('can_submit_harvest') || !TimeRecord::canAdd($this->logged_user, $this->active_project))
		{
			$this->httpError(HTTP_ERR_FORBIDDEN, null, true, $this->request->isApiCall());
		}
		
		$project = ProjectConfigOptions::getValue('harvest_project', $this->active_project);
		
		if (!$project)
		{
			flash_error('Please link this project to a Harvest project.');
			$this->redirectTo('project_overview', array('project_id' => $this->active_project->getId()));
		}
		
		$objRequest = $this->HaPi->getProject($project);
		
		if (!$objRequest->isSuccess())
		{
			flash_error('Failed to load project from Harvest. Please check your credentials.');
			$this->redirectTo('project_overview', array('project_id' => $this->active_project->getId()));
		}
		
		$objProject = $objRequest->data;
		
		$start = strtotime($objProject->{'hint-earliest-record-at'});
		
		if ($this->request->isAsyncCall())
		{
			$stop = mktime(0,0,0);
			
			if ($this->request->get('start') >= $start)
			{
				$start = $this->request->get('start');
				$stop = mktime(0, 0, 0, date('m', $start)+1, 0, date('Y', $start));
			}
			
			$latest = strtotime($objProject->{'hint-latest-record-at'});
			if ($latest !== false && $stop > $latest)
			{
				$stop = $latest;
			}
			
			$arrUsers = array();
			$objPeople = $this->HaPi->getUsers();
			foreach( $objPeople->data as $objPerson )
			{
				$objUser = Users::findByEmail((string)$objPerson->email);
				
				if ($objUser instanceof User)
				{
					$arrUsers[(int)$objPerson->id] = $objUser;
				}
			}
			
			$objRange = new Harvest_Range(date('Ymd', $start), date('Ymd', $stop));
			$objEntries = $this->HaPi->getProjectEntries($objProject->id, $objRange);
			
			if ($objEntries->isSuccess() && count($objEntries->data) > 0)
			{
				// Store tasks assigned to this project
				$arrTasks = array();
				$objTasks = $this->HaPi->getProjectTaskAssignments($objProject->id);
				foreach( $objTasks->data as $objTask )
				{
					$arrTasks[(int)$objTask->{'task-id'}] = array
					(
						'billable'	=> (bool)$objTask->billable,
					);
				}
				
				// Get task names
				$objTasks = $this->HaPi->getTasks();
				foreach( $objTasks->data as $objTask )
				{
					if (is_array($arrTasks[(int)$objTask->id]))
					{
						$arrTasks[(int)$objTask->id]['name'] = (string)$objTask->name;
					}
				}
				
				$arrTimeRecords = HarvestTimeRecords::findByProject($this->active_project);
				
				foreach( $objEntries->data as $objEntry )
				{
					$blnFound = false;
					
					foreach( $arrTimeRecords as $objTimeRecord )
					{
						if ((int)$objTimeRecord->getFloatField2() == (int)$objEntry->id)
						{
							$objTimeRecord->setValue((float)$objEntry->hours);
							$objTimeRecord->setRecordDate((string)$objEntry->{'spent-at'});
							
							$blnFound = true;
						}
						elseif (!(int)$objTimeRecord->getFloatField2() && (string)$objEntry->{'spent-at'} == (string)$objTimeRecord->getRecordDate() && (float)$objEntry->hours == $objTimeRecord->getValue())
						{
							$objTimeRecord->setFloatField2((float)$objEntry->id);
							
							$blnFound = true;
						}
						
						if ($blnFound)
						{
							$objTimeRecord->setBillableStatus(($arrTasks[(int)$objEntry->{'task-id'}]['billable'] ? ($objEntry->{'is-billed'} == 'false' ? BILLABLE_STATUS_PENDING_PAYMENT : BILLABLE_STATUS_BILLED) : BILLABLE_STATUS_NOT_BILLABLE));
							$objTimeRecord->save();
							break;
						}
					}
					
					if (!$blnFound)
					{
						$objUser = $arrUsers[(int)$objEntry->{'user-id'}];
						
						if (!($objUser instanceof User))
							continue;
						
						$objTimeRecord = new TimeRecord();
						
						$arrFields = $objTimeRecord->fields;
						$arrFields[] = 'float_field_2';
						$objTimeRecord->fields = $arrFields;
						
						$arrFields = $objTimeRecord->field_map;
						$arrFields['harvest_id'] = 'float_field_2';
						$objTimeRecord->field_map = $arrFields;
						
						$strTask = $arrTasks[(int)$objEntry->{'task-id'}]['name'];
						$strNote = (string)$objEntry->notes;
						
						if (preg_match('@(complete[d]?[\s]+)?ticket[\s]+[#]?(\d+):?@', strtolower($strNote), $match))
				        {
				        	$objTicket = Tickets::findByTicketId($this->active_project, (int)$match[2]);
				        	
				        	if (instance_of($objTicket, 'Ticket'))
				        	{
				        		$objTimeRecord->setParent($objTicket);
				        		$strNote = trim(preg_replace('@\(?'.$match[0].'\)?@i', '', $strNote));
				        		$strTask = '';
				        		
				        		// Complete ticket
				        		if (strpos($match[1], 'complete') !== false)
				        		{
				        			$objTicket->complete($objUser);
				        		}
				        	}
				        }
						
						$timetracking_data = array
						(
							'user_id'			=> $objUser->getId(),
							'record_user'		=> $objUser,
							'record_date'		=> (string)$objEntry->{'spent-at'},
							'value'				=> (float)$objEntry->hours,
							'billable_status'	=> ($arrTasks[(int)$objEntry->{'task-id'}]['billable'] ? ($objEntry->{'is-billed'} == 'false' ? BILLABLE_STATUS_PENDING_PAYMENT : BILLABLE_STATUS_BILLED) : BILLABLE_STATUS_NOT_BILLABLE),
							'body'				=> ($strNote == '' ? $strTask : $strNote),
							'harvest_id'		=> (float)$objEntry->id,
						);
						
						$objTimeRecord->setAttributes($timetracking_data);
						$objTimeRecord->setProjectId($this->active_project->getId());
				        $objTimeRecord->setCreatedBy($objUser);
				        $objTimeRecord->setState(STATE_VISIBLE);
				        $objTimeRecord->setUser($objUser);
				        
			        	$objTimeRecord->setVisibility(VISIBILITY_NORMAL);
						
						$objTimeRecord->save();
					}
				}
			}
			
			if (mktime(0,0,0) <= $stop || $stop == $latest)
			{
				flash_success('Harvest sync successfull.');
				die('finished');
			}
			else
			{
				die((string)strtotime('+1 day', $stop));
			}
		}
		
		$this->smarty->assign(array
		(
			'harvest_sync_url'	=> str_replace('/', '\/', assemble_url('project_harvest_sync', array('project_id' => $this->active_project->getId()))),
			'harvest_start'		=> $start,
			'indicator_ok'		=> ASSETS_URL.'/images/ok_indicator.gif',
			'success_url'		=> assemble_url('project_time', array('project_id' => $this->active_project->getId())),
		));
	}
}

