<?php
	function main($ip, $reason, $action, $nonce, $items, $content)
	{
		// Logging incoming message to device specific file
		logWPI($ip.".log", "<<<<<<<<< [ ".date("d-M-Y H:i:s")." ] [ ".$ip." ] ########################################################################\n\n");
		logWPI($ip.".log", $content."\n");
		
		// Check if we want a delay in replying to the phone request for testing purposes
		if (file_exists($ip.".sleep"))
		{
			$delay = file_get_contents($ip.".sleep");
			if ($delay < 600)
			{
				sleep($delay);
			}
		}
		
		// if device boots up, simply read all items from it first
		if ($reason == "start-up")
		{
			trigger_error($_SERVER['REMOTE_ADDR']." Send ReadAllItems after start-up", E_USER_NOTICE);
			$output = ReadAll($nonce);
		}
		
		// current implementation ignores local changes messages.
		elseif ($reason == "local-changes")
		{
			// if there is a write items file for this device, read content and send write items request only if previous request was not a WriteItems request to prevent loop
			if (file_exists($ip.".read") AND $action != 'WriteItems' AND $action != 'ReadItems' AND $action != 'ReadAllItems')
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Device specific read file found. Reading details from ".$ip.".read", E_USER_NOTICE);
				$readitems = file_get_contents($ip.".read");
				$output = ReadItems($nonce, $readitems);
				trigger_error($_SERVER['REMOTE_ADDR']." Device specific read sent", E_USER_NOTICE);
			}
			else
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Ignore local-changes message and send CleanUp", E_USER_NOTICE);
				$output = CleanUp($nonce);
			}
		}
		
		// if device contacts us again either with reply-to (replying to previous action) or solicited (replying after previous contact-me)
		elseif ($reason == "solicited" or $reason == "reply-to")
		{
			// check for existing software file
			if (file_exists($ip.".software"))
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Software Update requested. Reading details from ".$ip.".software", E_USER_NOTICE);
				$swupdateitems = file_get_contents($ip.".software");
				$output = SWUpdate($nonce, $swupdateitems);
				trigger_error($_SERVER['REMOTE_ADDR']." Software Update initiated. Deleting ".$ip.".software", E_USER_NOTICE);
				unlink($ip.".software");
			}
			// check for existing dongle file
			elseif (file_exists($ip.".dongle"))
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Software Dongle requested. Reading details from ".$ip.".dongle", E_USER_NOTICE);
				$swupdateitems = file_get_contents($ip.".dongle");
				$output = FileDeployment($nonce, $swupdateitems);
				trigger_error($_SERVER['REMOTE_ADDR']." Dongle download initiated. Deleting ".$ip.".dongle", E_USER_NOTICE);
				unlink($ip.".software");
			}
			// if there is a write items file for this device, read content and send write items request only if previous request was not a WriteItems request to prevent loop
			elseif (file_exists($ip.".read") AND $action != 'WriteItems' AND $action != 'ReadItems' AND $action != 'ReadAllItems')
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Device specific read file found. Reading details from ".$ip.".read", E_USER_NOTICE);
				$readitems = file_get_contents($ip.".read");
				$output = ReadItems($nonce, $readitems);
				trigger_error($_SERVER['REMOTE_ADDR']." Device specific read sent", E_USER_NOTICE);
			}
			// if there is a write items file for this device, read content and send write items request only if previous request was not a WriteItems request to prevent loop
			elseif (file_exists($ip.".write") AND $action != 'WriteItems')
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
			// if we don't see any file, we do a ReadAllItems only on first contact
			elseif ($action != 'WriteItems' AND $action != 'ReadItems' AND $action != 'ReadAllItems')
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Send ReadAllItems after start-up", E_USER_NOTICE);
				$output = ReadAll($nonce);
			}
			// if nothing exists we just clean up
			else
			{
				trigger_error($_SERVER['REMOTE_ADDR']." Sending CleanUp because no information to send to device", E_USER_NOTICE);
				$output = CleanUp($nonce);
			}
		}
		// if phone pushes status after file deployment just clean-up
		elseif ($reason == "status")
		{
			trigger_error($_SERVER['REMOTE_ADDR']." Sending CleanUp because of device status update after file deployment", E_USER_NOTICE);
			$output = CleanUp($nonce);
		}
		// if we don't understand the device request, just cleanup
		else
		{
			trigger_error($_SERVER['REMOTE_ADDR']." Sending CleanUp because of unknown device request", E_USER_NOTICE);
			$output = CleanUp($nonce);
		}
		
		// logging output of script to device specific file
		logWPI($ip.".log", ">>>>>>>>> [ ".date("d-M-Y H:i:s")." ] [ ".$ip." ] ########################################################################\n\n");
		logWPI($ip.".log", $output."\n");
		exit();
	}
?>