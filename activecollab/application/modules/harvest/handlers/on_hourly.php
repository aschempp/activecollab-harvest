<?php

/**
 * Do daily taks
 *
 * @param void
 * @return null
 */
function harvest_handle_on_hourly()
{
	$interval = ConfigOptions::getValue('harvest_sync_interval');
	
	if ($interval == HARVEST_SYNC_HOURLY)
	{
		HarvestTimeRecords::syncAll();
	}
}

