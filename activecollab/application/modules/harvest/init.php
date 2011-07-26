<?php


/**
 * Init harvest module
 */
define('HARVEST_MODULE', 'harvest');
define('HARVEST_MODULE_PATH', APPLICATION_PATH . '/modules/harvest');

require_once HARVEST_MODULE_PATH . '/functions.php';
require_once ANGIE_PATH .'/classes/harvest/init.php';
require_once SYSTEM_MODULE_PATH . '/models/ProjectConfigOptions.class.php';

set_for_autoload(array
(
	'HarvestTimeRecords' => HARVEST_MODULE_PATH . '/models/HarvestTimeRecords.class.php', 
));
