<?php

class HarvestModule extends Module
{
	
	/**
	* Plain module name
	*
	* @var string
	*/
	var $name = 'harvest';
	
	/**
	* Is system module flag
	*
	* @var boolean
	*/
	var $is_system = false;
	
	/**
	* Module version
	*
	* @var string
	*/
	var $version = '1.0';
	
	
	// ---------------------------------------------------
	//  Events and Routes
	// ---------------------------------------------------
	
	/**
	* Define module routes
	*
	* @param Router $r
	* @return null
	*/
	function defineRoutes(&$router)
	{
		$router->map('project_time_submit_harvest', 'projects/:project_id/time/harvest', array('controller' => 'harvest', 'action' => 'submit'), array('project_id' => '\d+'));
		$router->map('profile_harvest', 'people/:company_id/users/:user_id/harvest', array('controller' => 'profile_harvest', 'action' => 'index'), array('company_id' => '\d+', 'user_id' => '\d+'));
		$router->map('project_harvest', 'projects/:project_id/harvest', array('controller' => 'project_harvest', 'action' => 'index'), array('project_id' => '\d+'));
		$router->map('project_harvest_sync', 'projects/:project_id/harvest/sync', array('controller' => 'project_harvest', 'action' => 'sync'), array('project_id' => '\d+'));
	}
	
	
	/**
	* Define event handlers
	*
	* @param EventsManager $events
	* @return null
	*/
	function defineHandlers(&$events)
	{
		$events->listen('on_build_menu', 'on_build_menu');
		$events->listen('on_system_permissions', 'on_system_permissions');
		$events->listen('on_user_options', 'on_user_options');
		$events->listen('on_project_options', 'on_project_options');
//		$events->listen('on_daily', 'on_daily');
	}
	
	
	// ---------------------------------------------------
	//  Un(Install)
	// ---------------------------------------------------
	
	
	/**
	* Can this module be installed or not
	*
	* @param array $log
	* @return boolean
	*/
	function canBeInstalled(&$log)
	{
		if(extension_loaded('SimpleXML') && function_exists('simplexml_load_string'))
		{
			$log[] = lang('OK: SimpleXML extension loaded');
			
			if(extension_loaded('curl') && function_exists('curl_init'))
			{
				$log[] = lang('OK: CURL extension loaded');
				
				return true;
			}
			else
			{
				$log[] = lang('This module requires CURL PHP extension to be installed. Read more about CURL extension in PHP documentation: http://www.php.net/manual/en/book.curl.php');
			
				return false;
			}
		}
		else
		{
			$log[] = lang('This module requires SimpleXML PHP extension to be installed. Read more about SimpleXML extension in PHP documentation: http://www.php.net/manual/en/book.simplexml.php');
			
			return false;
		}
	}
	
	
	/**
	* Install this module
	*
	* @param void
	* @return boolean
	*/
	function install()
	{
		// user config options
		$this->addConfigOption('harvest_domain', USER_CONFIG_OPTION, null);
		$this->addConfigOption('harvest_user', USER_CONFIG_OPTION, null);
		$this->addConfigOption('harvest_pass', USER_CONFIG_OPTION, null);
		
		// project config options
		$this->addConfigOption('harvest_project', PROJECT_CONFIG_OPTION, 0);
		$this->addConfigOption('harvest_download', PROJECT_CONFIG_OPTION, 0);
		
		return parent::install();
	}
	
	
	/**
	* Uninstall this module
	*
	* @param void
	* @return boolean
	*/
	function uninstall()
	{
		return parent::uninstall();
	}
	
	
	/**
	* Get module display name
	*
	* @return string
	*/
	function getDisplayName()
	{
		return lang('Harvest');
	}
	
	
	/**
	* Return module description
	*
	* @param void
	* @return string
	*/
	function getDescription()
	{
		return lang('Integrates time tracking with Harvest');
	}
}

