<?php


class GlobalTimeHarvestController extends ApplicationController
{
	
	/**
	* Controller name
	*
	* @var string
	*/
	var $controller_name = 'global_time_harvest';
	
	
	/**
     * Active time report
     *
     * @var TimeReport
     */
    var $active_report;
    
    /**
     * Constructor
     *
     * @param Request $request
     * @return PeopleController
     */
	function __construct($request)
	{
		parent::__construct($request);
		
		if(!$this->logged_user->isAdministrator() && !$this->logged_user->getSystemPermission('use_time_reports'))
		{
			$this->httpError(HTTP_ERR_FORBIDDEN);
		}
		
		$this->wireframe->addBreadCrumb(lang('Time'), assemble_url('global_time'));
		if(TimeReport::canAdd($this->logged_user))
		{
			$this->wireframe->addPageAction(lang('New Report'), assemble_url('global_time_report_add'));
		} // if
		
		$report_id = $this->request->getId('report_id');
		if($report_id)
		{
			$this->active_report = TimeReports::findById($report_id);
		}
		
		if(instance_of($this->active_report, 'TimeReport'))
		{
			$this->wireframe->addBreadCrumb($this->active_report->getName(), $this->active_report->getUrl());
		}
		else
		{
			$this->active_report = new TimeReport();
		}
		
		$this->wireframe->current_menu_item = 'time';
		
		$this->smarty->assign('active_report', $this->active_report);
	}

	
	/**
	* Present a list of time entries and submit them to the selected project
	*
	* @param void
	* @return null
	*/
	function submit()
	{
		if(!$this->logged_user->getSystemPermission('can_submit_harvest'))
		{
			$this->httpError(HTTP_ERR_FORBIDDEN, null, true, $this->request->isApiCall());
		}
		
		if($this->active_report->isNew())
		{
			$this->httpError(HTTP_ERR_NOT_FOUND);
		}
		
		if(!$this->active_report->canView($this->logged_user))
		{
			$this->httpError(HTTP_ERR_FORBIDDEN);
		}
		
/*
		if(instance_of($this->active_object, 'ProjectObject'))
		{
			$this->wireframe->addPageMessage(lang('Time spent on <a href=":url">:name</a> :type', array
			(
				'url' => $this->active_object->getViewUrl(),
				'name' => $this->active_object->getName(),
				'type' => $this->active_object->getVerboseType(true),
			)), 'info');
		}
*/
		
/*
		$timetracking_data = array
		(
			'record_date' => new DateValue(time() + get_user_gmt_offset($this->logged_user)),
			'user_id' => $this->logged_user->getId(),
		);
*/
		
		
		$is_admin = false;
		$users = array('by_email'=>array(), 'by_id'=>array());
		$xml = harvest_request_xml($this->logged_user, 'people');
		if (is_object($xml))
		{
			foreach( $xml->user as $user )
			{
				if ($user->{'is-active'} != 'true')
					continue;

				if ($user->email == $this->logged_user->getEmail() && $user->{'is-admin'} == 'true')
					$is_admin = true;
				
				$users['by_email'][(string)$user->email] = (int)$user->id;
				$users['by_id'][(string)$user->id] = (string)$user->{'first-name'} . ' ' . (string)$user->{'last-name'};
			}
		}
		
		
		$arrTimeRecords = HarvestTimeRecords::executeReport($this->logged_user, $this->active_report);
		
		$total_time = 0;
		if(is_foreachable($arrTimeRecords))
		{
			if($this->active_report->getSumByUser())
			{
				foreach($arrTimeRecords as $report_record)
				{
					$total_time += $report_record['total_time'];
				}
			}
			else
			{
				foreach($arrTimeRecords as $report_record)
				{
					$total_time += $report_record->getValue();
				}
			}
		}
		
		
		$tasks = array();
		$xml = harvest_request_xml($this->logged_user, 'daily');
		
		if (is_object($xml))
		{
			foreach( $xml->projects->project as $project )
			{
				foreach( $project->tasks->task as $task )
				{
					$tasks[strval($project->name . ' (' . $project->client . ')')][intval($project->id) . ':' . intval($task->id)] = strval($task->name . ($task->billable ? '' : ' (not billable)'));
				}
			}
		}
		
		ksort($tasks);
		$task = explode(':', $this->request->post('task'));
		$user = $this->request->post('user');
		$record_ids = $this->request->post('time_record_ids');
		
		if ($this->request->isSubmitted() && $task[0] > 0 && $task[1] > 0)
		{
			$available = 0;
			$count = 0;
			
			foreach( $arrTimeRecords as $objTimeRecord )
			{
				if ($objTimeRecord->getBillableStatus() >= BILLABLE_STATUS_PENDING_PAYMENT || (!$is_admin && $objTimeRecord->getUserEmail() != $this->logged_user->getEmail()))
					continue;
				
				++$available;
				
				if (!in_array($objTimeRecord->getId(), $record_ids))
					continue;
				
				$of_user = $user;
				if ($is_admin && $user == 0)
				{
					$of_user = $users['by_email'][$objTimeRecord->getUserEmail()];
					
					if (!$of_user)
						continue;
				}
				
				$note = $objTimeRecord->getBody();
				$parent = $objTimeRecord->getParent();
				
				if ($parent)
				{
					$note = $parent->getName() . (strlen($note) ? ' – ' : '') . $note;
				}
				
				$post = '
<request>
  <notes>' . $note . '</notes>
  <hours>' . $objTimeRecord->getValue() . '</hours>
  <project_id type="integer">' . $task[0] . '</project_id>
  <task_id type="integer">' . $task[1] . '</task_id>
  <spent_at type="date">' . date('D, d M Y', $objTimeRecord->getRecordDate()->timestamp) . '</spent_at>
</request>';
				
				if (($timer = harvest_request_xml($this->logged_user, 'daily/add' . ($is_admin ? '?of_user='.$of_user : ''), $post)) !== false)
				{
					$objTimeRecord->setFloatField2((float)$timer->day_entry->id);
					$objTimeRecord->setBillableStatus(BILLABLE_STATUS_PENDING_PAYMENT);
					$objTimeRecord->save();
					++$count;
				}
			}
			
			flash_success($count . " time records have been sent to Harvest.");
			
			if ($count < $available)
			{
				$this->redirectTo('global_time_harvest', array('report_id' => $this->active_report->getId()));
			}
			else
			{
				$this->redirectTo('global_time', array('report_id' => $this->active_report->getId()));
			}
		}


		$this->smarty->assign(array(
			'grouped_reports'	=> TimeReports::findGrouped(),
			'report_records'	=> $arrTimeRecords,
			'total_time'		=> $total_time,
			'show_project'		=> true,
			'user_email'		=> $this->logged_user->getEmail(),
			'tasks'				=> $tasks,
			'users'				=> $users['by_id'],
			'is_admin'			=> $is_admin,
			'submit_url'		=> assemble_url('global_time_harvest', array('report_id' => $this->active_report->getId())),
		));
	}
}

