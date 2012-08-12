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
		trigger_error($_SERVER['REMOTE_ADDR']." Incoming DCMP request... exit", E_USER_NOTICE);
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
?>
