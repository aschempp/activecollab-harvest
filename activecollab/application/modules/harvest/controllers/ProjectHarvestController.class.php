<?php

// Extends users controller
use_controller('project', SYSTEM_MODULE);

// include ProjectConfigOptions model
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';

class ProjectHarvestController extends ProjectController
{

	/**
	* Controller name
	*
	* @var string
	*/
	var $controller_name = 'project_harvest';
	
	
	/**
	 * Add menu option
	 */
	function __construct($request)
	{
		parent::__construct($request);
		$this->wireframe->addBreadCrumb(lang('Harvest Settings'));
	}
	
	
	/**
	 * Present a form to enter Harvest Credentials for a user
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
				'download' => ProjectConfigOptions::getValue('harvest_download', $this->active_project),
			);
		}
		
		if ($this->request->isSubmitted())
		{
			$project = array_var($config, 'project', null);
			$download = array_var($config, 'download', null);
			
			ProjectConfigOptions::setValue('harvest_project', (int)$project, $this->active_project);
			ProjectConfigOptions::setValue('harvest_download', (int)$download, $this->active_project);
						
			flash_success("Harvest settings successfully saved");
			$this->redirectTo('project_overview', array('project_id' => $this->active_project->getId()));
		}
		
		
		// Retrieve active projects from Harvest
		$projects = array();
		$xml = harvest_request_xml($this->logged_user, 'daily');
		
		if (is_object($xml))
		{
			foreach( $xml->projects->project as $project )
			{
				$projects[(int)$project->id] = strval($project->name . ' (' . $project->client . ')');
			}
		}
		
		asort($projects);
		
		$this->smarty->assign(array
		(
			'projects' => $projects,
			'config' => $config,
		));
	}
	
	
	function sync()
	{
		if(!$this->logged_user->getSystemPermission('can_submit_harvest') || !TimeRecord::canAdd($this->logged_user, $this->active_project))
		{
			$this->httpError(HTTP_ERR_FORBIDDEN, null, true, $this->request->isApiCall());
		}
		
		$download = ProjectConfigOptions::getValue('harvest_download', $this->active_project);
		$project = ProjectConfigOptions::getValue('harvest_project', $this->active_project);
		
		if (!$download || !$project)
		{
			flash_error('Please link this project to a Harvest project.');
			$this->redirectTo('project_overview', array('project_id' => $this->active_project->getId()));
		}
		
		$project = harvest_request_xml($this->logged_user, 'projects/'.$project);
		$start = strtotime($project->{'hint-earliest-record-at'});
		
		if ($project === false || $start === false)
		{
			$this->redirectTo('project_overview', array('project_id' => $this->active_project->getId()));
		}
		
		if ($this->request->isAsyncCall())
		{
			$stop = mktime(0,0,0);
			
			if ($this->request->get('start') >= $start)
			{
				$start = $this->request->get('start');
				$stop = mktime(0, 0, 0, date('m', $start)+1, 0, date('Y', $start));
			}
			
			$latest = strtotime($project->{'hint-latest-record-at'});
			if ($latest !== false && $stop > $latest)
			{
				$stop = $latest;
			}
			
			$arrUsers = array();
			$objPeople = harvest_request_xml($this->logged_user, 'people');
			foreach( $objPeople->user as $objPerson )
			{
				$objUser = Users::findByEmail((string)$objPerson->email);
				
				if ($objUser instanceof User)
				{
					$arrUsers[(int)$objPerson->id] = $objUser;
				}
			}
			
			$objEntries = harvest_request_xml($this->logged_user, 'projects/'.(int)$project->id.'/entries?from='.date('Ymd', $start).'&to='.date('Ymd', $stop));
			
			if ($objEntries !== false && count($objEntries->{'day-entry'}) > 0)
			{
				// Store tasks assigned to this project
				$arrTasks = array();
				$objTasks = harvest_request_xml($this->logged_user, 'projects/'.(int)$project->id.'/task_assignments');
				foreach( $objTasks->{'task-assignment'} as $objTask )
				{
					$arrTasks[(int)$objTask->{'task-id'}] = array
					(
						'billable'	=> (bool)$objTask->billable,
					);
				}
				
				// Get task names
				$objTasks = harvest_request_xml($this->logged_user, 'tasks');
				foreach( $objTasks->task as $objTask )
				{
					if (is_array($arrTasks[(int)$objTask->id]))
					{
						$arrTasks[(int)$objTask->id]['name'] = (string)$objTask->name;
					}
				}
				
				$arrTimeRecords = HarvestTimeRecords::findByProject($this->active_project);
				
				foreach( $objEntries->{'day-entry'} as $objEntry )
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
						
						if (preg_match('@Ticket #([0-9]+)@', $strNote, $match))
				        {
				        	$objTicket = Tickets::findByTicketId($this->active_project, (int)$match[1]);
				        	
				        	if (instance_of($objTicket, 'Ticket'))
				        	{
				        		$objTimeRecord->setParent($objTicket);
				        		$strNote = trim(preg_replace('@\(?'.$match[0].'\)?@', '', $strNote));
				        		$strTask = '';
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

