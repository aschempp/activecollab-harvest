<?php

/**
 * Populate object options array
 *
 * @param NamedList $options
 * @param ProjectObject $object
 * @param User $user
 * @return null
 */
function harvest_handle_on_user_options(&$user, &$options, &$logged_user)
{
	if($user->getId() == $logged_user->getId() && $logged_user->getSystemPermission('can_submit_harvest'))
	{
		$options->add('harvest', array
		(
			'text'	=> lang('Harvest Credentials'),
			'url'	=> assemble_url('profile_harvest', array('user_id' => $user->getId(), 'company_id' => $user->getCompanyId())),
		));
	}
}

