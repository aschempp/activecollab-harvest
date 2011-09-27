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
		$wireframe = Wireframe::instance();
		
		$options = new NamedList();
		
		$options->add('harvest_submit', array
		(
			'text'	=> 'Submit',
			'url'	=> assemble_url('project_time_harvest_submit', array('project_id' => $project->getId())),
		));
		
		$options->add('harvest_sync', array
		(
			'text'	=> lang('Sync'),
			'url'	=> assemble_url('project_time_harvest_sync', array('project_id' => $project->getId())),
		));
		
		$wireframe->addPageAction(lang('Harvest'), '#', $options->data, array('id' => 'project_object_options'), 1000);
	}
}

