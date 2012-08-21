<?php
	// No error messages in XML output, otherwise phone will reject message
	error_reporting(E_ALL);
	ini_set('display_errors', 'Off');
	ini_set('html_errors', 'Off');
	ini_set('error_log', '_log.txt');
	
	// writing POST data to content variable
	$content = file_get_contents('php://input');
	
	/*
	 * checking if DCMP request
	 */
	if (isset($_REQUEST['device-id'])) {
		error_log($_SERVER['REMOTE_ADDR']." Incoming DCMP request...", 0);
		
		// default dcmp response is 200
		$dcmpresponse = 200;
		
		if (file_exists($_SERVER['REMOTE_ADDR'].".dcmp")) 
		{
			// reading dcmp action for device from file
			$dcmpcode = file_get_contents($_SERVER['REMOTE_ADDR'].".dcmp");
			
			if ($dcmpcode == "always" )
			{
				$dcmpresponse = 202;
				error_log($_SERVER['REMOTE_ADDR']." Device needs to contact DLS always (".$dcmpresponse.")", 0);
			}
			elseif ($dcmpcode == "true" )
			{
				$dcmpresponse = 202;
				error_log($_SERVER['REMOTE_ADDR']." Device needs to contact DLS only once (".$dcmpresponse.")", 0);
				file_put_contents($_SERVER['REMOTE_ADDR'].".dcmp", "false");
			}
			else
			{
				$dcmpresponse = 200;
				error_log($_SERVER['REMOTE_ADDR']." No need to contact DLS for device (".$dcmpresponse.")", 0);
			}
		}
		
		// setting response header code and exit
		http_response_code($dcmpresponse);
		exit();
	}
	
	/*
	 * checking for device contact reason
	 */
	$reason = $action = '';
	preg_match('@<(ReasonForContac.*)>(.*)</ReasonForContact>@', $content, $tmp_reason);
	if (count($tmp_reason) > 0)
	{
		$reason = $tmp_reason[2];
		if ($reason == "reply-to")
		{
			preg_match('@action="([^"]*)"@', $tmp_reason[1], $tmp_action);
			if (count($tmp_action) > 0)
			{
				$action = $tmp_action[1];
			}
		}
		trigger_error($_SERVER['REMOTE_ADDR']." Incoming connection. Reason: ".$reason.", Action: ".$action, E_USER_NOTICE);
	}
	else
	{
		trigger_error($_SERVER['REMOTE_ADDR']." Incoming connection. Reason: UNKNOWN", E_USER_NOTICE);
		exit();
	}
	
	/*
	 * checking message nonce
	 */
	$nonce = '';
	preg_match('@<Message.nonce="([A-Z0-9]*)"@', $content, $nonce);
	if (count($nonce) > 0)
	{
		$nonce=$nonce[1];
		trigger_error($_SERVER['REMOTE_ADDR']." Succesfully found nonce: ".$nonce, E_USER_NOTICE);
	}
	else
	{
		trigger_error($_SERVER['REMOTE_ADDR']." Unable to find nonce...".$content, E_USER_NOTICE);
		exit();
	}
	
	/*
	 * checking items send by device
	 */
	$items = '';
	preg_match_all('@name="([^"]*)(" index=")*([0-9])*">([^<]*)</Item>@', $content, $tmp);
	if (count($tmp) > 0)
	{
		for($i=0; $i < count($tmp[0]); $i++) {
			$items[$tmp[1][$i]] = array('value' => $tmp[4][$i], 'index' => $tmp[3][$i]);
		}
	}
	
	/*
	 * Call main function
	 */
	
	include_once ('functions.php');
	main($_SERVER['REMOTE_ADDR'], $reason, $action, $nonce, $items, $content);
	
	function ReadAll($nonce)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='	<Action>ReadAllItems</Action>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		
		echo $output;
		Return $output;
	}
	
	function CleanUp($nonce)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		//$output ='<Object Id="DLSMessage">'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='	<Action>CleanUp</Action>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		//$output.='</Object>';
		
		//$output=hash_sign_message($output);
		echo $output;
		Return $output;
	}
	
	function WriteItems($nonce, $writeitems)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='<Action>WriteItems</Action>'."\n";
		$output.='	<ItemList>'."\n";
		$output.=$writeitems."\n";
		$output.='	</ItemList>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		echo $output;
		Return $output;
	}
	
	function ReadItems($nonce, $readitems)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='<Action>ReadItems</Action>'."\n";
		$output.='	<ItemList>'."\n";
		$output.=$readitems."\n";
		$output.='	</ItemList>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		echo $output;
		Return $output;
	}
	
	function SWUpdate($nonce, $updateitems)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='<Action>SoftwareDeployment</Action>'."\n";
		$output.='	<ItemList>'."\n";
		$output.=$updateitems."\n";
		$output.='	</ItemList>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		echo $output;
		Return $output;
	}
	
	function FileDeployment($nonce, $updateitems)
	{
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<DLSMessage xmlns="http://www.siemens.com/DLS" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.siemens.com/DLS">'."\n";
		$output.='<Message nonce="'.$nonce.'">'."\n";
		$output.='<Action>FileDeployment</Action>'."\n";
		$output.='	<ItemList>'."\n";
		$output.=$updateitems."\n";
		$output.='	</ItemList>'."\n";
		$output.='</Message>'."\n";
		$output.='</DLSMessage>'."\n";
		echo $output;
		Return $output;
	}
	
	function hash_sign_message($message)
	{
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$doc->loadXML($message);
		$digestvalue = base64_encode(pack("H*", sha1($doc->C14N(FALSE, FALSE))));
		
		$output ='<SignedInfo>'."\n";
		$output.='<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></CanonicalizationMethod>'."\n";
		$output.='<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod>'."\n";
		$output.='<Reference URI="#DLSMessage">'."\n";
		$output.='<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod>'."\n";
		$output.='<DigestValue>'.$digestvalue.'</DigestValue>'."\n";
		$output.='</Reference>'."\n";
		$output.='</SignedInfo>';
		
		$doc = new DOMDocument();
		$doc->formatOutput = true;
		$doc->loadXML($output);
		$element = $doc->C14N(FALSE, FALSE);
		//$element = strToHex($doc->C14N(FALSE, FALSE));

		$key = openssl_get_privatekey(file_get_contents(dirname(__FILE__) . '/privkey.pem'));
		openssl_sign($element, $sig, $key, OPENSSL_ALGO_SHA1);
		$signaturevalue = base64_encode($sig);
		
		$output ='<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$output.='<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">'."\n";
		$output.='<SignedInfo>'."\n";
		$output.='<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></CanonicalizationMethod>'."\n";
		$output.='<SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod>'."\n";
		$output.='<Reference URI="#DLSMessage">'."\n";
		$output.='<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod>'."\n";
		$output.='<DigestValue>'.$digestvalue.'</DigestValue>'."\n";
		$output.='</Reference>'."\n";
		$output.='</SignedInfo>'."\n";
		$output.='<SignatureValue>'.$signaturevalue.'</SignatureValue>'."\n";
		$output.=$message;
		$output.='</Signature>'."\n";
		
		Return $output;
	}
	
	function logWPI($file, $output)
	{
		file_put_contents($file, $output, FILE_APPEND);
		Return;
	}
	
	function strToHex($string)
	{
		$hex='';
		for ($i=0; $i < strlen($string); $i++)
		{
			$hex .= strtoupper(dechex(ord($string[$i])));
		}
		return $hex;
	}
?>
