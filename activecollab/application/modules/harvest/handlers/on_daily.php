<?php

/**
 * Do daily taks
 *
 * @param void
 * @return null
 */
function harvest_handle_on_daily()
{
	$interval = ConfigOptions::getValue('harvest_sync_interval');
	
	if ($interval == HARVEST_SYNC_DAILY)
	{
		HarvestTimeRecords::syncAll();
	}
}

