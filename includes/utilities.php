<?
/*
 * A utilities file which contains common functions that are helpful for general use
 */

include('settings.php');


function db_connect() {
	$link = mysql_connect(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD);
	if(!$link)
		die('Could not connect to the database.');
	if(!mysql_select_db(DB_DATABASE, $link))
		die('Could not connect to the database.');
	return $link;
}

function db_close($link) {
	mysql_close($link);
}

function rand_str($length = 6, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
{
	// Length of character list
	$chars_length = (strlen($chars) - 1);

	// Start our string
	$string = $chars{rand(0, $chars_length)};

	// Generate random string
	for ($i = 1; $i < $length; $i++)
	{
		$string .= $chars{rand(0, $chars_length)};
	}
   
	return $string;
}

function sql_get_array($link, $sql) {
	$results = mysql_query($sql, $link);
	if(!$results) {
		return null;
	}
	$sqlarray = array();
	while($row = mysql_fetch_array($results, MYSQL_ASSOC))
		$sqlarray[] = $row;
	return $sqlarray;
}

function prettydate($date) {
	return date('l, jS \of F', $date);
}

function prettytime($date) {
	return date('g:i a', $date);
}

function send_email($to, $subject, $message, $headers='', $log=false) {
	if(EMAIL_LOG || $log) {
		$fh = fopen("email.log", 'a');
		fwrite($fh, "Emailing...\r\n");
		fwrite($fh, "To: $to\r\n");
		fwrite($fh, "Subject: $subject\r\n");
		fwrite($fh, "Message follows:\r\n");
		fwrite($fh, $message);
		fclose($fh);
		return true;
		/*
		$message = str_replace("\r\n.", "\r\n..", $message);
		$smtp_server = fsockopen("smtp.unsw.edu.au", 25, $errno, $errstr, 30);
		if(!$smtp_server)
		{
			return false;
		}
		echo("sending email to $to, $subject, $message");
		fwrite($smtp_server, "HELO unsw\r\n");
		fwrite($smtp_server, "MAIL FROM:<ticketing@cserevue.org.au>\r\n");
		fwrite($smtp_server, "RCPT TO:<$to>\r\n");
		fwrite($smtp_server, "DATA\r\n");
		fwrite($smtp_server, "Date: " . date('r') . "\r\n");
		fwrite($smtp_server, "$headers");
		fwrite($smtp_server, "Subject: $subject\r\n");
		fwrite($smtp_server, "To: $to\r\n");
		fwrite($smtp_server, "\r\n$message\r\n");
		fwrite($smtp_server, ".\r\nQUIT\r\n");
		return true;
		*/
	} else {
		return mail($to, $subject, $message, $headers);
	}
}

/**
 * Cleans a function so multiline stuff can go in json
 */
function json_string($line, $nl2br = false) {
	$line = addslashes($line);
	if($nl2br)
		$line = nl2br($line);
	$line = str_replace(array("\n", "\r"), array('\n', '\r'), $line);
	return $line;
}

if(DEBUG)
	$rbslog = "";

/**
 * Logs a message and takes appropriate action.  $priority can be a number between 0 and 5, 0 being debugging and 5 being an error that must be raised immediately.
 */
function rbslog($message, $priority = 0) {
	switch($priority) {
	case 0: // For simple debug messages.  Will only do anything if debug is on
		$prefix = "Debug ";
		break;
	case 1: // For logins, bookings, general events.
		$prefix = "Event ";
		break;
	case 2: // For mild errors
		$prefix = "Error ";
		break;
	case 3: // For major errors
		$prefix = "ERROR ";
		break;
	default:
		return;
	}

	if(DEBUG)
		$rbslog .= $prefix . date('Y-m-d H:i: ') . $message . "<br>";

	if($priority == 0)
		return;

	$fh = fopen("logs/rbs.log", "a");
	fwrite($fh, $prefix . date('Y-m-d H:i: ') . $message . "\n");
	fclose($fh);
}
?>
