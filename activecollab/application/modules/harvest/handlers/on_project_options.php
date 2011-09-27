<?php

// include ProjectConfigOptions model
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';

/**
 * Populate object options array
 *
 * @param NamedList $options
 * @param ProjectObject $object
 * @param User $user
 * @return null
 */
function harvest_handle_on_project_options(&$options, &$project, &$logged_user)
{
	if($logged_user->getSystemPermission('can_submit_harvest') && $logged_user->getSystemPermission('project_management'))
	{
		$options->add('harvest', array
		(
			'text'	=> lang('Harvest Settings'),
			'url'	=> assemble_url('project_harvest', array('project_id' => $project->getId())),
		));
	}
}

