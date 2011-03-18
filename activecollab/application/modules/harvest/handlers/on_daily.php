<?php

/**
 * Do daily taks
 *
 * @param void
 * @return null
 */
function harvest_handle_on_daily()
{
	$arrProjects = Projects::findAll();
	
	foreach( $arrProjects as $objProject )
	{
		
	}
}

