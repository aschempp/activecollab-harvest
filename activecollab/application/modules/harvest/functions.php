<?php

/**
 * Initiate a http request to Harvest
 * @param  string
 * @param  string
 * @return bool
 */
function harvest_request_xml($user, $file, $data)
{
	$logged_user = clone $user;
	$domain = UserConfigOptions::getValue('harvest_domain', $logged_user);
	$user = UserConfigOptions::getValue('harvest_user', $logged_user);
	$pass = UserConfigOptions::getValue('harvest_pass', $logged_user);
	
	$ch = curl_init($domain . $file);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$pass);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml', 'Accept: application/xml'));
	
	if ($data)
	{
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}
	
	$data = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	curl_close($ch);
	
	switch( $code )
	{
		case '200':
		case '201':
			return simplexml_load_string($data);
			break;
			
		case '401':
			flash_error('Harvest authentication failed. Please check your access credentials.');
			break;
			
		default:
			flash_error('Connecting to Harvest failed. Please check your access credentials.');
			break;
	}
	
	return false;
}