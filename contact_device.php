<?php
	// No error messages in XML output, otherwise phone will reject message
	error_reporting(E_ALL);
	ini_set('display_errors', 'Off');
	ini_set('html_errors', 'Off');
	ini_set('error_log', '_log.txt');
	
	if (isset($_GET["ipaddress"]))
	{
		$address_familiy = checkiptype($_GET["ipaddress"]);
		if ($address_familiy == 'false')
		{
			error_log($_GET["ipaddress"]." is not a valid IPv4 or IPv6 address", 0);
			print_r ($_GET["ipaddress"]." is not a valid IPv4 or IPv6 address");
			exit();
		}
		elseif ($address_familiy == 'ipv4')
		{
			error_log($_GET["ipaddress"]." will receive a contact-me request via IPv4", 0);
			$ip = $_GET["ipaddress"];
			// using same IP address for log file since we are in IPv4 mode
			$ip_filesystem = $ip;
		}
		elseif ($address_familiy == 'ipv6')
		{
			error_log($_GET["ipaddress"]." will receive a contact-me request via IPv6", 0);
			$ip = $_GET["ipaddress"];
			// need a special variable for IPv6 addresses, replace : with .
			$ip_filesystem = str_replace(":", ".", $ip);
		}
	}
	// checking for software image
	if (isset($_GET["swimage"]))
	{
		error_log($ip." will receive a software update request: ".$_GET["swimage"], 0);
		preg_match('/^ftp\072\057\057([^\072]*)\072([^\072]{0,})\100([^\072]*)\072([0-9]{1,5})(.*\057)([^\057]+)/', $_GET["swimage"], $swimage);
		if (count($swimage) == 7)
		{
			$output='<Item name="file-username">'.$swimage[1].'</Item>'."\n";
			$output.='<Item name="file-pwd">'.$swimage[2].'</Item>'."\n";
			$output.='<Item name="file-server">'.$swimage[3].'</Item>'."\n";
			$output.='<Item name="file-port">'.$swimage[4].'</Item>'."\n";
			$output.='<Item name="file-path">'.$swimage[5].'</Item>'."\n";
			$output.='<Item name="file-name">'.$swimage[6].'</Item>'."\n";
			$output.='<Item name="file-type">APP</Item>'."\n";
			$output.='<Item name="file-priority">normal</Item>';
			file_put_contents($ip.".software", $output);
		}
		preg_match('/^https\072\057\057(.*)/', $_GET["swimage"], $swimage);
		if (count($swimage) == 2)
		{
			$output='<Item name="file-https-base-url">https://'.$swimage[1].'</Item>'."\n";
			$output.='<Item name="file-type">APP</Item>'."\n";
			$output.='<Item name="file-priority">normal</Item>';
			file_put_contents($ip.".software", $output);
		}
	}
	
	// checking for dongle file
	if (isset($_GET["dongle"]))
	{
		error_log($ip." will receive a file download request (dongle): ".$_GET["dongle"], 0);
		preg_match('/^ftp\072\057\057([^\072]*)\072([^\072]{0,})\100([^\072]*)\072([0-9]{1,5})(.*\057)([^\057]+)/', $_GET["dongle"], $dongle);
		if (count($dongle) == 7)
		{
			$output='<Item name="file-username">'.$dongle[1].'</Item>'."\n";
			$output.='<Item name="file-pwd">'.$dongle[2].'</Item>'."\n";
			$output.='<Item name="file-server">'.$dongle[3].'</Item>'."\n";
			$output.='<Item name="file-port">'.$dongle[4].'</Item>'."\n";
			$output.='<Item name="file-path">'.$dongle[5].'</Item>'."\n";
			$output.='<Item name="file-name">'.$dongle[6].'</Item>'."\n";
			$output.='<Item name="file-type">DONGLE</Item>';
			file_put_contents($ip.".dongle", $output);
		}
		preg_match('/^https\072\057\057(.*)/', $_GET["dongle"], $dongle);
		if (count($dongle) == 2)
		{
			$output='<Item name="file-https-base-url">https://'.$dongle[1].'</Item>'."\n";
			$output.='<Item name="file-type">DONGLE</Item>'."\n";
			$output.='<Item name="file-priority">normal</Item>';
			file_put_contents($ip.".dongle", $output);
		}
	}
	
	$ch = curl_init();
	$timeout = 15;
	if ($address_familiy == 'ipv4')
	{
		$url = "https://$ip/contact_dls.html/ContactDLS";
	}
	elseif ($address_familiy == 'ipv6')
	{
		$url = "https://[$ip]/contact_dls.html/ContactDLS";
	}
	$post = "ContactMe=true&dls_ip_addr=".$_SERVER['SERVER_ADDR']."&dls_ip_port=".$_SERVER['SERVER_PORT'];
	//$post = "ContactMe=true";
	if (isset($_GET["curlerror"]))
	{
		$date = date("d-M-Y H:i:s");
		file_put_contents("$ip_filesystem.log", "[ CURL-ERROR-LOG ] [ $date ] [ $ip ] ########################################################################\n\n", FILE_APPEND);
		$errfile = fopen("$ip_filesystem.log", 'a+');
		curl_setopt($ch,CURLOPT_FILE,$errfile);
		curl_setopt($ch,CURLOPT_STDERR,$errfile);
		curl_setopt($ch,CURLOPT_WRITEHEADER,$errfile);
		curl_setopt($ch,CURLOPT_VERBOSE,true);
	}
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch,CURLOPT_TIMEOUT,30);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
	$data = curl_exec($ch);
	curl_close($ch);
	exit();
	
	function checkiptype($ip)
	{
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
		{
			return 'ipv4';
		}
		elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
		{
			return 'ipv6';
		}
		else
		{
			return 'false';
		}
	}
?>