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
	$project = Projects::findById((int)Request::get('project_id'));
	
	if (instance_of($project, 'Project') && ANGIE_PATH_INFO == 'projects/'.$project->getId().'/time' && $logged_user->getSystemPermission('can_submit_harvest') && ProjectConfigOptions::getValue('harvest_project', $project) > 0)
	{
		$wf = Wireframe::instance();
		$wf->addPageAction(lang('Submit to Harvest'), assemble_url('project_time_harvest', array('project_id'=>$project->getId())));
	}
	elseif (ANGIE_PATH_INFO == 'time/' . $project_id && $logged_user->getSystemPermission('can_submit_harvest'))
	{
		$wf = Wireframe::instance();
		$wf->addPageAction(lang('Submit to Harvest'), assemble_url('global_time_harvest', array('report_id' => $project_id)));
	}
}

