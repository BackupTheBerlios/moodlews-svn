<pre>
<?php
/*
Copyright 2005 Simone Gammeri, Francesco Di Cerbo, LIPS Lab University of Genova, ITALY

This file is part of MoodleSoapPlugin.

MoodleSoapPlugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

MoodleSoapPlugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with MoodleSoapPlugin; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
*/

function out($str)
{ echo $str."\n"; }

	ini_set('soap.wsdl_cache_enabled', 0);
//	$xml_old = new DOMDocument();
//	$xml_old->load("triennale.xml");
//	$xsl = new DOMDocument();
//	$xsl->load("courses.xsl");
//	$xsltproc= new XSLTProcessor();
//	$xsltproc->importStyleSheet($xsl);
///	$xsltproc->transformToUri($xml_old,"courses.xml");
#	$xml=new DOMDocument();
#	$xml->load("courses.xml");
#	$xml_string=$xml->saveXML();
	
	//if ($_SERVER["REQUEST_METHOD"] == "POST") 
	{
		$username='admin';//$_POST['username'];
		$password='secret';//md5($_POST['password']);
		//$client = new SoapClient("https://srvfh13.fh-luebeck.de/moodle/moodleplugin.wsdl",array('trace'=>1,'login'=>$username,'password'=>$password));
		$client = new SoapClient("https://www.myserver.com/moodle/moodleplugin.wsdl");
		try
		{
			$sessid = '';
			out($sessid = $client->login($username, $password));
			out($client->addUser($sessid, 'testheinz', '1234', 'Heinz', 'Test', 'anonymous@somewhere.de'));
			out($client->updateUser($sessid, 'testheinz', 'Heinzi', 'Tester', 'someone@somewhere.de'));
			out($sessid = $client->logout($sessid));
			out($client->deleteUser($sessid, 'testheinz'));
			
			//echo $client->exportUsers(2, 'CF101');
			//echo $client->importCourses($xml_string,"Prova");
			//echo $client->exportCourses();
			//echo $client->exportCourses(3,"Prova");
			//echo $client->exportCategories();
		}
		catch (SoapFault $sf)
		{
			//echo $client->__getLastRequest();
			echo $client->__getLastResponse();
			echo $sf->faultstring;
		}
	} 
	/*else
	{
		if (empty($_SERVER[HTTPS])) {
			$url="https://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI];
			$delay=1;
			die('<meta http-equiv="refresh" content="'.$delay.';url='.$url.'">');
			}
		require_once("form.html");
//		output function list of webserver
/* 		$functions = $client->__getFunctions();
		foreach($functions as $func) 
		{
			echo $func . "<br>";
		}
*/	
	//}
?>
</pre>