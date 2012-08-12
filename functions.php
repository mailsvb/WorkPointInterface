<?php
	function main($ip, $reason, $action, $nonce, $items, $content)
	{
		// Logging incoming message to device specific file
		logWPI($ip.".log", "<<<<<<<<< [ ".date("d-M-Y H:i:s")." ] [ ".$ip." ] [ ".$items['e164']['value']." ] [ ".$items['mac-addr']['value']." ] ########################################################################\n\n");
		logWPI($ip.".log", $content."\n");
		
		// if device boots up, simply read all items from it first
		if ($reason == "start-up")
		{
			$output = ReadAll($nonce);
			trigger_error($_SERVER['REMOTE_ADDR']." Send ReadAllItems after start-up", E_USER_NOTICE);
		}
		
		// current implementation ignores local changes messages.
		elseif ($reason == "local-changes")
		{
			trigger_error($_SERVER['REMOTE_ADDR']." Ignore local-changes message and send CleanUp", E_USER_NOTICE);
			$output = CleanUp($nonce);
		}
		
		// if device contacts us again either with reply-to (replying to previous action) or solicited (replying after previous contact-me)
		elseif ($reason == "solicited" or $reason == "reply-to")
		{		
			// if there is a write items file for this device, read content and send write items request only if previous request was not a WriteItems request to prevent loop
			if (file_exists($ip.".write") AND $action != 'WriteItems')
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Device specific configuration file found. Reading details from ".$ip.".write", E_USER_NOTICE);
				$writeitems = file_get_contents($ip.".write");
				$output = WriteItems($nonce, $writeitems);
				trigger_error($_SERVER['REMOTE_ADDR']." Device specific configuration sent", E_USER_NOTICE);
			}
			// if there is a config default file for this device, read content and send write items request only if previous request was not a WriteItems request to prevent loop
			elseif (file_exists("configuration.default") AND $action != 'WriteItems')
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Default configuration file found. Reading details from file", E_USER_NOTICE);
				$writeitems = file_get_contents("configuration.default");
				$output = WriteItems($nonce, $writeitems);
				trigger_error($_SERVER['REMOTE_ADDR']." Default configuration sent", E_USER_NOTICE);
			}
			// if nothing exists we just clean up
			else
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Sending CleanUp because no information to send to device", E_USER_NOTICE);
				$output = CleanUp($nonce);
			}
		}
		
		// if we don't understand the device request, just cleanup
		else
		{
			trigger_error($_SERVER['REMOTE_ADDR']." Sending CleanUp because of unknown device request", E_USER_NOTICE);
			$output = CleanUp($nonce);
		}
		
		// logging output of script to device specific file
		logWPI($ip.".log", ">>>>>>>>> [ ".date("d-M-Y H:i:s")." ] [ ".$ip." ] [ ".$items['e164']['value']." ] [ ".$items['mac-addr']['value']." ] ########################################################################\n\n");
		logWPI($ip.".log", $output."\n");
		exit();
	}
	
	function ReadAll($nonce)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='	<Action>ReadAllItems</Action>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		echo $output;
		//logWPI($output, $_SERVER['REMOTE_ADDR']);
		Return $output;
	}
	
	function CleanUp($nonce)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='	<Action>CleanUp</Action>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		echo $output;
		//logWPI($output, $_SERVER['REMOTE_ADDR']);
		Return $output;
	}
	
	function WriteItems($nonce, $writeitems)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='	<Action>WriteItems</Action>'."\n";
		$output.='	<ItemList>'."\n";
		$output.=$writeitems."\n";
		$output.='	</ItemList>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		echo $output;
		//logWPI($output, $_SERVER['REMOTE_ADDR']);
		Return $ouput;
	}
	
	function logWPI($file, $output)
	{
		file_put_contents($file, $output, FILE_APPEND);
		Return;
	}
?>