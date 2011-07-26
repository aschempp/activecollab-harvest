<?php

/**
* Initialization file of HarvestAPI package
*/

define('HARVEST_API_PATH', ANGIE_PATH . '/classes/harvest');

require_once HARVEST_API_PATH . '/HarvestAPI.php';

set_for_autoload(array
(
	'Harvest_Abstract'				=> HARVEST_API_PATH . '/Harvest/Abstract.php',
	'Harvest_Category'				=> HARVEST_API_PATH . '/Harvest/Category.php',
	'Harvest_Client'				=> HARVEST_API_PATH . '/Harvest/Client.php',
	'Harvest_Currency'				=> HARVEST_API_PATH . '/Harvest/Currency.php',
	'Harvest_DailyActivity'			=> HARVEST_API_PATH . '/Harvest/DailyActivity.php',
	'Harvest_DayEntry'				=> HARVEST_API_PATH . '/Harvest/DayEntry.php',
	'Harvest_Exception'				=> HARVEST_API_PATH . '/Harvest/Exception.php',
	'Harvest_Expense'				=> HARVEST_API_PATH . '/Harvest/Expense.php',
	'Harvest_ExpenseCategory'		=> HARVEST_API_PATH . '/Harvest/Expense.php',
	'Harvest_Invoice'				=> HARVEST_API_PATH . '/Harvest/Invoice.php',
	'Harvest_Invoice_Filter'		=> HARVEST_API_PATH . '/Harvest/Invoice/Filter.php',
	'Harvest_InvoiceItemCategory'	=> HARVEST_API_PATH . '/Harvest/InvoiceItemCategory.php',
	'Harvest_InvoiceMessage'		=> HARVEST_API_PATH . '/Harvest/InvoiceMessage.php',
	'Harvest_Payment'				=> HARVEST_API_PATH . '/Harvest/Payment.php',
	'Harvest_Project'				=> HARVEST_API_PATH . '/Harvest/Project.php',
	'Harvest_Range'					=> HARVEST_API_PATH . '/Harvest/Range.php',
	'Harvest_Result'				=> HARVEST_API_PATH . '/Harvest/Result.php',
	'Harvest_Task'					=> HARVEST_API_PATH . '/Harvest/Task.php',
	'Harvest_TaskAssignment'		=> HARVEST_API_PATH . '/Harvest/TaskAssignment.php',
	'Harvest_Throttle'				=> HARVEST_API_PATH . '/Harvest/Throttle.php',
	'Harvest_Timer'					=> HARVEST_API_PATH . '/Harvest/Timer.php',
	'Harvest_TimeZone'				=> HARVEST_API_PATH . '/Harvest/TimeZone.php',
	'Harvest_User'					=> HARVEST_API_PATH . '/Harvest/User.php',
	'Harvest_UserAssignment'		=> HARVEST_API_PATH . '/Harvest/UserAssignment.php',
));
