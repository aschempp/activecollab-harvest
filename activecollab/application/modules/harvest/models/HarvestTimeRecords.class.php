<?php

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

}

