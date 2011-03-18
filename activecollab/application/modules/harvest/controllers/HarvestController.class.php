<?php

// Extend company profile
use_controller('timetracking', TIMETRACKING_MODULE);

// include ProjectConfigOptions model
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';

class HarvestController extends TimetrackingController
{
	
	/**
	* Controller name
	*
	* @var string
	*/
	var $controller_name = 'harvest';
	
	
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
		
		
		$per_page = 20;
		$page = (integer) $this->request->get('page');
		
		if($page < 1)
		{
			$page = 1;
		}
		
		list($arrTimeRecords, $pagination) = HarvestTimeRecords::paginate(array
		(
			'conditions' => array('project_id = ? AND type = ? AND state >= ? AND visibility >= ? AND integer_field_2=?' . ($is_admin ? '' : ' AND integer_field_1=?'), $this->active_project->getId(), 'TimeRecord', STATE_VISIBLE, $this->logged_user->getVisibility(), BILLABLE_STATUS_BILLABLE, $this->logged_user->getId()),
			'order' => 'date_field_1 DESC, id DESC',
		), $page, $per_page);
		
		
		$tasks = array();
		$xml = harvest_request_xml($this->logged_user, 'daily');
		
		if (is_object($xml))
		{
			$active_project = (int)ProjectConfigOptions::getValue('harvest_project', $this->active_project);
			
			foreach( $xml->projects->project as $project )
			{
				if ($active_project > 0 && (int)$project->id != $active_project)
					continue;
				
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
			$count = 0;
			
			foreach( $arrTimeRecords as $objTimeRecord )
			{
				if ($objTimeRecord->getBillableStatus() >= BILLABLE_STATUS_PENDING_PAYMENT || !in_array($objTimeRecord->getId(), $record_ids))
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
					$count++;
				}
			}
			
			flash_success($count . " time records have been sent to Harvest.");
			
			if ($count < count($arrTimeRecords))
			{
				$this->redirectTo('project_time_submit_harvest', array('project_id' => $this->active_project->getId()));
			}
			else
			{
				$this->redirectTo('project_time', array('project_id' => $this->active_project->getId()));
			}
		}
			
		$this->smarty->assign(array(
			'timetracking_data' => $timetracking_data,
			'timerecords'		=> $arrTimeRecords,
			'pagination'		=> $pagination,
			'tasks'				=> $tasks,
			'users'				=> $users['by_id'],
			'is_admin'			=> $is_admin,
			'submit_url'		=> assemble_url('project_time_submit_harvest', array('project_id' => $this->active_project->getId())),
	  	));
	}
}

