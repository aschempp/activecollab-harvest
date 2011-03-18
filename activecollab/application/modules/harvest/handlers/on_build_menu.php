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
	if (strlen($_GET['project_id']) && ANGIE_PATH_INFO == 'projects/'.$_GET['project_id'].'/time' && $logged_user->getSystemPermission('can_submit_harvest'))
	{
		$wf = Wireframe::instance();
		$wf->addPageAction(lang('Submit to Harvest'), assemble_url('project_time_submit_harvest', array('project_id'=>$_GET['project_id'])));
	}
}

