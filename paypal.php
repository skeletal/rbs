<?php

include_once('includes/utilities.php');
include_once('includes/prodmanagement.php');
include_once('includes/paymentmanagement.php');

/* This file handles all Paypal notifications and updates the database based on whether the payment was successful or not.

	To use this, you must have the exact URL to it, followed by a query string: ?production=id

	This allows the payments to be processed on a per production basis
*/

set_error_handler("handle_error");

// this is set to sandbox.paypal for testing, and just paypal in live
if(DEBUG)
	$PAYPAL_VERIFY_HOST = "ssl://www.sandbox.paypal.com";
else
	$PAYPAL_VERIFY_HOST = "ssl://www.paypal.com";
	

// global link to the database
$DB_LINK = db_connect();

// The item number prefix used by this system
$RBS_PREFIX = "RBS-";

// We store a log of everything we do, and store it in the database
$LOG_DATA = "";
$LOG_ERROR = false;

// Handle a payment if we have a production ID
$PROD_ID = getProductionId();
if($PROD_ID != NULL) {
	handlePaypal($PROD_ID);
} else {
	addLog("Production ID not set, or production was not found: $PROD_ID");
}

// store the log
storeLog();

function addLog($log, $error = false) {
	global $LOG_DATA, $LOG_ERROR;
	$LOG_DATA .= "$log\n";

	if($error) {
		$LOG_ERROR = TRUE;
	}
}


// Checks the GET argument for the set production ID, NULL if not set or the ID is not valid
function getProductionId() {
	global $DB_LINK;

	if(isset($_GET['production'])) {
		$prodId = $_GET['production'];
		if(production_exists($DB_LINK, $prodId)) {
			return $prodId;
		}
	}
	
	return NULL;
}

// paypal parameters
function getPaymentAmount() {
	return $_POST['mc_gross'];
}
function getPaymentItemNumber() {
	return $_POST['item_number'];
}

// returns whether this is a valid completed payment
function checkIsValidPayment($prodData) {
	global $RBS_PREFIX;

	if(DEBUG)
		$pass1 = ($_POST['payment_status'] == "Completed" || $_POST['payment_status'] == "Pending");
	else
		$pass1 = ($_POST['payment_status'] == "Completed");
	$pass2 = ($_POST['mc_currency'] == "AUD");
	$pass3 = (strcasecmp($_POST['receiver_email'], $prodData['paypalaccount']) == 0);
	$pass4 = (strpos(getPaymentItemNumber(), $RBS_PREFIX) == 0);

	if($pass1 && $pass2 && $pass3 && $pass4) {
		return true;
	}

	addLog("Payment didn't match checks. Pass [$pass1 $pass2 $pass3 $pass4]. Needs to have status Completed, currency AUD, sent to " . $prodData['paypalaccount'] . " with prefix $RBS_PREFIX.");
	return false;
}

// Processes the actual payment data, and marks as paid if all valid
function checkPaymentData($prodData) {
	global $RBS_PREFIX, $DB_LINK;

	$itemNumber = getPaymentItemNumber();
	// paymentID is the part after RBS- in item number, so from position 4
	$paymentId = substr($itemNumber, strlen($RBS_PREFIX));

	addLog("Payment ID is $paymentId");

	// get the payment information for this
	$seats = get_payment_total($DB_LINK, $paymentId);

	addLog("Seats to pay for this payment: " . print_r($seats, 1));

	if(!$seats) {
		addLog("Error: No seats to pay for!", true);
		// TODO: send email about this payment with no seats
	} else {
		$totalPrice = calculate_total_payment($seats);
		addLog("Total price to pay is $totalPrice");

		if($totalPrice == getPaymentAmount()) {
			// We have received a payment with what we expect! Mark as paid

			addLog("Total price matches paypal amount, marking as paid");
			$result = mark_seats_paid($DB_LINK, $seats);
            send_confirmation_email($DB_LINK, $seats);

			$numUpdated = mysql_affected_rows($DB_LINK);

			// ensure numUpdated == number of seats, otherwise email admin
			if($numUpdated == count($seats)) {
				// everything was successful
				addLog("Payment was processed successfully!");
			} else {
				addLog("Error: Number of updated seats in mark as paid was not equal to expected: $numUpdated != " . count($seats), true);
			}

		} else {
			addLog("Error: Total price paid " . getPaymentAmount() . " did not match $totalPrice", true);
		}
	}


}

function handlePaypal($prodId) {
	global $DB_LINK;

	$prodData = get_production($DB_LINK, $prodId);

	// check that the payment is valid first
	if(checkIsPaypalPayment()) {
		if(checkIsValidPayment($prodData)) {
			checkPaymentData($prodData);

		} else {
			addLog("Error: Payment did not match expected payment type", true);
		}


	} else {
		// invalid payment, email contact
		addLog("Error: Invalid payment detected! Paypal did not reply with VALID", true);
	}
	



}

// Posts back the values to PayPal to make sure it is a valid payment, and not a fake POST
function checkIsPaypalPayment() {
	global $PAYPAL_VERIFY_HOST;

	$req = "cmd=_notify-validate";
	foreach ($_POST as $key => $value) {
		$value = urlencode(stripslashes($value));
		$req .= "&$key=$value";
	}

	// post back to PayPal system to validate
	$header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
	$fp = fsockopen ($PAYPAL_VERIFY_HOST, 443, $errno, $errstr, 30);

   if(!$fp) {
      addLog("Error: Could not connect to $PAYPAL_VERIFY_HOST!", true);
      return false;
   }

	fputs ($fp, $header . $req);
	$res = "";
	while (!feof($fp)) {
		$res .= fgets ($fp, 1024);
	}
	if (strstr($res, "VERIFIED")) {
		// Paypal has verified it!
		return true;
	} else {
		addLog("PayPal did not reply VERIFIED. Full return is: $res");
		return false;
	}

}

function storeLog() {
	global $DB_LINK, $PROD_ID, $LOG_DATA, $LOG_ERROR;

	$sql = "INSERT into `paypallog` ";
	$sql .= "(`id`, `time`, `arguments`, `log`) ";
	$sql .= " VALUES (NULL, '" . time() . "', '";
	$sql .=    mysql_real_escape_string(print_r($_POST, 1)) . "', '";
	$sql .=    mysql_real_escape_string($LOG_DATA) . "');";

	mysql_query($sql, $DB_LINK) or die("Unable to store paypal log");

	$logId = mysql_insert_id($DB_LINK);

	if($LOG_ERROR) {
		email_prod_admins($DB_LINK, $PROD_ID, "Unexpected Paypal Error", $LOG_DATA . "\nMore data in the paypallog, ID $logId\n");
	}
}

function handle_error($errno, $errstr, $errfile, $errline) {
   email_prod_admins($DB_LINK, $PROD_ID, "Unexpected Error in PayPal Processing", "PHP Error: $errno\n$errstr\n$errfile\n$errline\n");
}

