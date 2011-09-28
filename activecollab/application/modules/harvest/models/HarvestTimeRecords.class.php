<?php

// include ProjectConfigOptions model
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';


/**
* Time records manager class
* 
* @package activeCollab.modules.timetracking
* @subpackage models
*/
class HarvestTimeRecords extends TimeRecords
{

	/**
	* Return time records from a given project
	*
	* @param Project $project
	* @param integer $min_state
	* @param integer $min_visibility
	* @return array
	*/
	function findByProject($project, $min_state = STATE_VISIBLE, $min_visibility = VISIBILITY_NORMAL)
	{
		$arguments = array
		(
			'conditions' => array('project_id = ? AND type = ? AND state >= ? AND visibility >= ?', $project->getId(), 'TimeRecord', $min_state, $min_visibility),
			'order' => 'date_field_1 DESC, id DESC',
		);
		
		return HarvestTimeRecords::findBySQL(DataManager::prepareSelectFromArguments($arguments, TABLE_PREFIX . 'project_objects'), null, array_var($arguments, 'one'));
	}
	
	
	/**
     * Return paginated set of project objects
     *
     * @param array $arguments
     * @param itneger $page
     * @param integer $per_page
     * @return array
     */
    function paginate($arguments = null, $page = 1, $per_page = 10)
    {
		if(!is_array($arguments))
		{
			$arguments = array();
		}
		
		$arguments['limit'] = $per_page;
		$arguments['offset'] = ($page - 1) * $per_page;
		
		$items = HarvestTimeRecords::findBySQL(DataManager::prepareSelectFromArguments($arguments, TABLE_PREFIX . 'project_objects'), null, array_var($arguments, 'one'));
		$total_items = HarvestTimeRecords::count(array_var($arguments, 'conditions'));
		
		return array
		(
			$items,
			new Pager($page, $total_items, $per_page)
		);
    }
	
	
	/**
     * Return object of a specific class by SQL
     *
     * @param string $sql
     * @param array $arguments
     * @param boolean $one
     * @param string $table_name
     * @return array
     */
    function findBySQL($sql, $arguments = null, $one = false)
    {
		if($arguments !== null)
		{
			$sql = db_prepare_string($sql, $arguments);
		} // if
		
		$rows = db_execute_all($sql);
		
		if(is_error($rows))
		{
			return $rows;
		} // if
		
		if(!is_foreachable($rows))
		{
			return null;
		} // if
		
		if($one)
		{
			$row = $rows[0];
			$item_class = array_var($row, 'type');
			
			$item = new $item_class();
			
			$arrFields = $item->fields;
			$arrFields[] = 'float_field_2';
			$item->fields = $arrFields;
			
			$arrFields = $item->field_map;
			$arrFields['harvest_id'] = 'float_field_2';
			$item->field_map = $arrFields;
			
			$item->loadFromRow($row);
			return $item;
		}
		else
		{
			$items = array();
			
			foreach($rows as $row)
			{
				$item_class = array_var($row, 'type');
				
				$item = new $item_class();
				
				$arrFields = $item->fields;
				$arrFields[] = 'float_field_2';
				$item->fields = $arrFields;
				
				$arrFields = $item->field_map;
				$arrFields['harvest_id'] = 'float_field_2';
				$item->field_map = $arrFields;
				
				$item->loadFromRow($row);
				$items[] = $item;
			} // foreach
			
			return count($items) ? $items : null;
		} // if
    } // findBySQL
    
    
    
        /**
     * Execute report
     *
     * @param User $user
     * @param TimeReport $report
     * @param Project $project
     * @return array
     */
    function executeReport($user, $report, $project = null) {
      $conditions = $report->prepareConditions($user, $project);
      if(empty($conditions)) {
        return null;
      } // if
      
    	if($report->getSumByUser()) {
    	  $rows = db_execute_all('SELECT SUM(float_field_1) AS total_time, integer_field_1 AS user_id FROM ' . TABLE_PREFIX . 'project_objects WHERE ' . $conditions . ' GROUP BY integer_field_1');
    	  if(is_foreachable($rows)) {
    	    $result = array();
    	    foreach($rows as $row) {
    	      $user = Users::findById($row['user_id']);
    	      if(instance_of($user, 'User')) {
    	        $result[] = array(
    	          'user' => $user,
    	          'total_time' => float_format($row['total_time'], 2),
    	        );
    	      } // if
    	    } // foreach
    	    return $result;
    	  } else {
    	    return null;
    	  } // if
    	} else {
    	  return HarvestTimeRecords::findBySQL('SELECT * FROM ' . TABLE_PREFIX . 'project_objects WHERE ' . $conditions . ' ORDER BY date_field_1');
    	} // if
    } // executeReport
    
    
    public static function syncProject($HaPi, $active_project, $objProject, $start, $stop)
    {
		$arrUsers = array();
		$objPeople = $HaPi->getUsers();
		foreach( $objPeople->data as $objPerson )
		{
			$objUser = Users::findByEmail((string)$objPerson->email);
			
			if ($objUser instanceof User)
			{
				$arrUsers[(int)$objPerson->id] = $objUser;
			}
		}
		
		$objRange = new Harvest_Range(date('Ymd', $start), date('Ymd', $stop));
		$objEntries = $HaPi->getProjectEntries($objProject->id, $objRange);
		
		if ($objEntries->isSuccess() && count($objEntries->data) > 0)
		{
			// Store tasks assigned to this project
			$arrTasks = array();
			$objTasks = $HaPi->getProjectTaskAssignments($objProject->id);
			foreach( $objTasks->data as $objTask )
			{
				$arrTasks[(int)$objTask->{'task-id'}] = array
				(
					'billable'	=> ($objTask->billable == 'false' ? false : true),
				);
			}
			
			// Get task names
			$objTasks = $HaPi->getTasks();
			foreach( $objTasks->data as $objTask )
			{
				if (is_array($arrTasks[(int)$objTask->id]))
				{
					$arrTasks[(int)$objTask->id]['name'] = (string)$objTask->name;
				}
			}
			
			$arrTimeRecords = HarvestTimeRecords::findByProject($active_project);
			
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
					
					// @todo validate harvest/ac member
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
			        	$objTicket = Tickets::findByTicketId($active_project, (int)$match[2]);
			        	
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
					$objTimeRecord->setProjectId($active_project->getId());
			        $objTimeRecord->setCreatedBy($objUser);
			        $objTimeRecord->setState(STATE_VISIBLE);
			        $objTimeRecord->setUser($objUser);
			        
		        	$objTimeRecord->setVisibility(VISIBILITY_NORMAL);
					
					$objTimeRecord->save();
				}
			}
		}
    }
    
    
    public static function syncAll()
    {
    	// Initialize Harvest API
		$HaPi = new HarvestAPI();
		$HaPi->setUser(ConfigOptions::getValue('harvest_user'));
		$HaPi->setPassword(ConfigOptions::getValue('harvest_pass'));
		$HaPi->setAccount(ConfigOptions::getValue('harvest_account'));
		
		$arrProjects = Projects::findAll();
		
		foreach( $arrProjects as $active_project )
		{
			$project = ProjectConfigOptions::getValue('harvest_project', $active_project);
		
			if (!$project)
			{
				continue;
			}
			
			$objRequest = $HaPi->getProject($project);
			
			if (!$objRequest->isSuccess())
			{
				continue;
			}
			
			$objProject = $objRequest->data;
			
			$start = strtotime($objProject->{'hint-earliest-record-at'});
			$stop = mktime(0,0,0);
			
			$latest = strtotime($objProject->{'hint-latest-record-at'});
			if ($latest !== false && $stop > $latest)
			{
				$stop = $latest;
			}
			
			HarvestTimeRecords::syncProject($HaPi, $active_project, $objProject, $start, $stop);
		}
    }

}

