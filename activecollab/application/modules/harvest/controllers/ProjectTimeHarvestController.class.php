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
		
		// Add breadcrumb
		$this->wireframe->addBreadCrumb(lang('Submit to Harvest'));
		
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
					$this->redirectTo('project_time_harvest', array('project_id' => $this->active_project->getId()));
				}
			}
			
			flash_success($count . " time records have been sent to Harvest.");
			
			if ($count < count($arrTimeRecords))
			{
				$this->redirectTo('project_time_harvest', array('project_id' => $this->active_project->getId()));
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
			'submit_url'		=> assemble_url('project_time_harvest', array('project_id' => $this->active_project->getId())),
	  	));
	}
}

