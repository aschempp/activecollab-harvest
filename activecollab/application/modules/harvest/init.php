<?php


/**
 * Init harvest module
 */
define('HARVEST_MODULE', 'harvest');
define('HARVEST_MODULE_PATH', APPLICATION_PATH . '/modules/harvest');

require_once ANGIE_PATH .'/classes/harvest/init.php';
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';

set_for_autoload(array
(
	'HarvestTimeRecords' => HARVEST_MODULE_PATH . '/models/HarvestTimeRecords.class.php', 
));


// Auto-Sync Intervals
define('HARVEST_SYNC_MANUALLY', 0);
define('HARVEST_SYNC_FREQUENTLY', 1);
define('HARVEST_SYNC_HOURLY', 2);
define('HARVEST_SYNC_DAILY', 3);


/**
* List of sync intervals
*
* @param null
* @return array
*/
function harvest_module_sync_intervals()
{
	return array
	(
		HARVEST_SYNC_MANUALLY	 => lang('Manually'),
		HARVEST_SYNC_FREQUENTLY  => lang('Frequently'),
		HARVEST_SYNC_HOURLY      => lang('Hourly'),
		HARVEST_SYNC_DAILY       => lang('Daily'),
	);
}

