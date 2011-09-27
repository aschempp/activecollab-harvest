<?php

// Extend timetracking controller
use_controller('timetracking', TIMETRACKING_MODULE);

// include ProjectConfigOptions model
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';

class ProjectTimeHarvestController extends TimetrackingController
{
	
	/**
	 * Controller name
	 * @var string
	 */
	var $controller_name = 'project_time_harvest';

	/**
	 * Harvest API instance
	 */
	var $HaPi;
	
	
	/**
	 * Add menu option and initialize Harvest API
	 */
	function __construct($request)
	{
		parent::__construct($request);
		
		// Initialize Harvest API
		$this->HaPi = new HarvestAPI();
		$this->HaPi->setUser(ConfigOptions::getValue('harvest_user'));
		$this->HaPi->setPassword(ConfigOptions::getValue('harvest_pass'));
		$this->HaPi->setAccount(ConfigOptions::getValue('harvest_account'));
	}

	
	
	/**
	 * Present a list of time entries and submit them to the selected project
	 *
	 * @param void
	 * @return null
	 */
	function submit()
	{
		$intHarvestID = ProjectConfigOptions::getValue('harvest_project', $this->active_project);
		
		if (!$this->logged_user->getSystemPermission('can_submit_harvest') || !$intHarvestID)
		{
			$this->httpError(HTTP_ERR_FORBIDDEN, null, true, $this->request->isApiCall());
		}
		
		// Add breadcrumb
		$this->wireframe->addBreadCrumb(lang('Submit to Harvest'));
		
		$blnAdmin = false;
		$arrUsers = array('by_email'=>array(), 'by_id'=>array());
		$objUsers = $this->HaPi->getUsers();
		
		if ($objUsers->isSuccess())
		{
			foreach( $objUsers->data as $objUser )
			{
				if ($objUser->{'is-active'} != 'true')
					continue;

				if ($objUser->email == $this->logged_user->getEmail() && $objUser->{'is-admin'} == 'true')
					$blnAdmin = true;
				
				$arrUsers['by_email'][(string)$objUser->email] = (int)$objUser->id;
				$arrUsers['by_id'][(string)$objUser->id] = (string)$objUser->{'first-name'} . ' ' . (string)$objUser->{'last-name'};
			}
		}
		
		$per_page = 20;
		$page = (integer)$this->request->get('page');
		
		if ($page < 1)
		{
			$page = 1;
		}
		
		list($arrTimeRecords, $pagination) = HarvestTimeRecords::paginate(array
		(
			'conditions' => array('project_id = ? AND type = ? AND state >= ? AND visibility >= ? AND integer_field_2=?' . ($blnAdmin ? '' : ' AND integer_field_1=?'), $this->active_project->getId(), 'TimeRecord', STATE_VISIBLE, $this->logged_user->getVisibility(), BILLABLE_STATUS_BILLABLE, $this->logged_user->getId()),
			'order' => 'date_field_1 DESC, id DESC',
		), $page, $per_page);
		
		
		$arrTasks = array();
		$objDaily = $this->HaPi->getDailyActivity();

		if ($objDaily->isSuccess())
		{
			foreach( $objDaily->data->projects as $objProject )
			{
				if ($objProject->id != $intHarvestID)
					continue;
				
				foreach( $objProject->tasks as $objTask )
				{
					if ($objAssignment->deactivated == 'true')
						continue;
				
					$arrTasks[$objTask->id] = $objTask->name . ($objTask->billable == 'true' ? '' : ' (not billable)');
				}
			}
		}
		
		// Input submitted
		if ($this->request->isSubmitted())
		{
			$intTask = $this->request->post('task');
			$user = $this->request->post('user');
			$record_ids = $this->request->post('time_record_ids');
		
			$count = 0;
			
			foreach( $arrTimeRecords as $objTimeRecord )
			{
				if ($objTimeRecord->getBillableStatus() >= BILLABLE_STATUS_PENDING_PAYMENT || !in_array($objTimeRecord->getId(), $record_ids))
					continue;
				
				// @todo non-admin will always submit to the Admin account
				$of_user = $user;
				if ($user == 0)
				{
					$of_user = $arrUsers['by_email'][$objTimeRecord->getUserEmail()];
				}
				
				if (!$of_user)
					continue;
				
				$note = $objTimeRecord->getBody();
				$parent = $objTimeRecord->getParent();
				
				if ($parent)
				{
					$note = $parent->getName() . (strlen($note) ? ' – ' : '') . $note;
				}
				
				$objEntry = new Harvest_DayEntry();
				$objEntry->set('notes', $note);
				$objEntry->set('hours', $objTimeRecord->getValue());
				$objEntry->set('project_id', $intHarvestID);
				$objEntry->set('task_id', $intTask);
				$objEntry->set('spent_at', date('D, d M Y', $objTimeRecord->getRecordDate()->timestamp));
				
				$objResult = $this->HaPi->createEntry($objEntry, $of_user);
				
				if ($objResult->isSuccess())
				{
					$objTimeRecord->setFloatField2((float)$objResult->data->id);
					$objTimeRecord->setBillableStatus(BILLABLE_STATUS_PENDING_PAYMENT);
					$objTimeRecord->save();
					$count++;
				}
				else
				{
					flash_error($objResult->data);
					$this->redirectTo('project_time_harvest_submit', array('project_id' => $this->active_project->getId()));
				}
			}
			
			flash_success($count . " time records have been sent to Harvest.");
			
			if ($count < count($arrTimeRecords))
			{
				$this->redirectTo('project_time_harvest_submit', array('project_id' => $this->active_project->getId()));
			}
			else
			{
				$this->redirectTo('project_time', array('project_id' => $this->active_project->getId()));
			}
		}
			
		$this->smarty->assign(array
		(
			'timerecords'		=> $arrTimeRecords,
			'pagination'		=> $pagination,
			'tasks'				=> $arrTasks,
			'users'				=> $arrUsers['by_id'],
			'is_admin'			=> $blnAdmin,
			'submit_url'		=> assemble_url('project_time_harvest_submit', array('project_id' => $this->active_project->getId())),
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
		
		// Add breadcrumb
		$this->wireframe->addBreadCrumb(lang('Sync with Harvest'));
		
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
						'billable'	=> ($objTask->billable == 'false' ? false : true),
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
			'harvest_sync_url'	=> str_replace('/', '\/', assemble_url('project_time_harvest_sync', array('project_id' => $this->active_project->getId()))),
			'harvest_start'		=> $start,
			'indicator_ok'		=> ASSETS_URL.'/images/ok_indicator.gif',
			'success_url'		=> assemble_url('project_time', array('project_id' => $this->active_project->getId())),
		));
	}
}

