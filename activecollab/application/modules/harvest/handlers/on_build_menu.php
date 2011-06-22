<?php

/**
 * Handle on_build_menu event
 *
 * @param Company $company
 * @param NamedList $options
 * @param User $logged_user
 * @return null
 */
function harvest_handle_on_build_menu(&$menu, &$logged_user) 
{
	$project_id = $this->request->get('project_id');
	
	if (strlen($project_id) && ANGIE_PATH_INFO == 'projects/'.$project_id.'/time' && $logged_user->getSystemPermission('can_submit_harvest'))
	{
		$wf = Wireframe::instance();
		$wf->addPageAction(lang('Submit to Harvest'), assemble_url('project_time_harvest', array('project_id'=>$project_id)));
	}
	elseif (ANGIE_PATH_INFO == 'time/' . $project_id && $logged_user->getSystemPermission('can_submit_harvest'))
	{
		$wf = Wireframe::instance();
		$wf->addPageAction(lang('Submit to Harvest'), assemble_url('global_time_harvest', array('report_id' => $project_id)));
	}
}

