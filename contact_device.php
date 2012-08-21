<?php
	if (isset($_GET["ipaddress"]))
	{
		if (checkiptype($_GET["ipaddress"]) == 'false')
		{
			echo "This is not a valid IPv4 or IPv6 address";
			exit();
		}
		else
		{
			$ip = $_GET["ipaddress"];
		}
	}
	// checking for software image
	if (isset($_GET["swimage"]))
	{
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
	$url = "https://$ip/contact_dls.html/ContactDLS";
	$post = "ContactMe=true&dls_ip_addr=".$_SERVER['SERVER_ADDR']."&dls_ip_port=".$_SERVER['SERVER_PORT'];
	//$post = "ContactMe=true";
	if (isset($_GET["curlerror"]))
	{
		$date = date("d-M-Y H:i:s");
		file_put_contents("$ip.log", "[CURL-ERROR-LOG] [$date] [$ip] ########################################################################\n\n", FILE_APPEND);
		$errfile = fopen("$ip.log", 'a+');
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