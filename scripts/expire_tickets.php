<?
/**
 * This script expires tickets which removes tickets which have passed their deadline.
 */

date_default_timezone_set("Australia/Sydney");
include('../includes/utilities.php');
include("../includes/prodmanagement.php");
include("../includes/bookingmanagement.php");

$link = db_connect();

if (isset($argv[1])){
    $prod = $argv[1];
}
//$et = email_prod_admins($link, 1, "hi there now", "This is a test message emailing all production admins");

// First find tickets which will expire and are for performances which have autoexpire turned on.
$sql = "SELECT b.id, u.name, u.email, p.date, p.production, p.title, pd.name AS prodname, b.deadline, b.bookedtime, pd.sitelocation, pd.paypalaccount, count(bs.id) seats";
$sql .= " FROM user u, performance p, booking b, bookedseat bs, production pd";
$sql .= " WHERE b.deadline < NOW() AND pd.id = p.production AND b.user = u.id AND b.performance = p.id AND p.autoexpire = 1";
if (isset($prod)){$sql .= " AND pd.id = $prod"; }
$sql .= " AND bs.booking = b.id AND bs.status = 1";
$sql .= " GROUP BY b.id";

$results = sql_get_array($link, $sql);

// We need to sort them into productions so we can email en mass
$bookings = array();

foreach($results as $result) {
	$bookings[$result['production']][] = $result;
}


foreach($bookings as $production => $bookinglist) {
	// Create the email and expire the bookings.
	$email = "";
	foreach($bookinglist as $booking) {
        $extraEmail = "You can re-book your tickets by visiting ".$booking['sitelocation']."\r\n\r\n";
        $thisemail = "";
		$thisemail .= "Expired booking number " . $booking['id'] . ".\r\n";
		$thisemail .= "User " . $booking['name'] . " with " . $booking['seats'] . " seats expired";
		$thisemail .= " for performance " . $booking['date'] . ": " . $booking['title'] . "\r\n";
		$thisemail .= "The deadline was at " . $booking['deadline'] . "\r\n\r\n";
		expire_booking($link, $booking['id']);
        // FIXME: Add a contact email field to productions
        send_email($booking['email'], "Your tickets have expired for ".$booking['prodname'], $thisemail.$extraEmail, "From: ".$booking['paypalaccount']."\r\n");
        $email .= $thisemail;

	}
    //email_prod_admins($link, $production, "Expired Tickets for " . $booking['prodname'], $email);
    email_sales_team($link, $production, "Expired Tickets for " . $booking['prodname'], $email);
}

?>
