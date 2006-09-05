<?php
/*
Copyright 2005 Simone Gammeri, Francesco Di Cerbo, LIPS Lab University of Genova, ITALY

This file is part of MoodleSoapPlugin.

MoodleSoapPlugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

MoodleSoapPlugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with MoodleSoapPlugin; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
*/
	if (empty($_SERVER[HTTPS])) {
		$url="https://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI];
		$delay=1;
		die('<meta http-equiv="refresh" content="'.$delay.';url='.$url.'">');
	}
	include_once("moodleplugin.php");
	ini_set('soap.wsdl_cache_enabled', 0);
	$server = new SoapServer("moodleplugin.wsdl",array('soap_version' =>SOAP_1_2, 'encoding'=>"UTF-8"));
	$server->setClass("MoodlePlugin");

	session_start();
	session_name('PHPMOODLEWSSESSID');
	
	/*if (!(adminlogin($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']))) { // Check username and password
		authenticate(); // Send basic authentication headers because username and/or password didnot match
		}
	else {*/
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		$server->setPersistence(SOAP_PERSISTENCE_REQUEST);
		try{
			$server->handle();
			}
		catch(Exception $exc){
			$server->fault($exc->getCode(),$exc->getMessage());
			}
	} 
	else
	{
		#output function list of webserver
		$functions = $server->getFunctions();
		foreach($functions as $func) 
		{
		    echo $func . "<br>";
		}
	}
	//}

// Call authentication display
function authenticate() {
	Header("WWW-Authenticate: Basic");
    	Header("HTTP/1.0 401 Unauthorized");
    	echo "error";
	
}
?>
