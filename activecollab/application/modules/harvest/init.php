<?php


/**
 * Init harvest module
 */
define('HARVEST_MODULE', 'harvest');
define('HARVEST_MODULE_PATH', APPLICATION_PATH . '/modules/harvest');

require_once HARVEST_MODULE_PATH . '/functions.php';

set_for_autoload(array
(
	'HarvestTimeRecords' => HARVEST_MODULE_PATH . '/models/HarvestTimeRecords.class.php', 
));
