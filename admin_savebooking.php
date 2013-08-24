<?
/**
 * Accepts the booking update from admin_bookingsummary.php and allows the user to navigate back to the booking page.
 */

include_once('includes/utilities.php');
$link = db_connect();
include_once('includes/adminauth.php');
include_once('includes/pricemanagement.php');
include_once('includes/prodmanagement.php');
include_once('includes/paymentmanagement.php');
include_once('includes/bookingmanagement.php');
include_once('includes/frames/prodtheme.php');

$user = $_SESSION['admin_id'];

// Get the prices from the booked seats
if(isset($_POST['price'])) {
	foreach($_POST['price'] as $seat => $price) {
		set_price($link, $seat, $price);
	}
}

$booking = array();
if(isset($_POST['bookingid'])) {
	$booking['id'] = (int)$_POST['bookingid'];

	if(isset($_POST['name']))
		$booking['name'] = $_POST['name'];
	if(isset($_POST['description']))
		$booking['description'] = $_POST['description'];
	if(isset($_POST['phonenumber']))
		$booking['phonenumber'] = $_POST['phonenumber'];
	if(isset($_POST['email']))
		$booking['email'] = $_POST['email'];
//	if(isset($_POST['pickedup']))
//		$booking['pickedup'] = 1;
//	else
//		$booking['pickedup'] = 0;
//	if(isset($_POST['amountpaid']))
//		$booking['amountpaid'] = (int)$_POST['amountpaid'];
//	if(isset($_POST['discount']))
//		$booking['discount'] = (int)$_POST['discount'];
//	if(isset($_POST['deadline']))
//		$booking['deadline'] = $_POST['deadline'];
//	if(isset($_POST['emailsent']))
//		$booking['emailsent'] = (int)$_POST['emailsent'];

	update_booking($link, $booking);

    send_confirmation_email_bookingid($link, $booking);
}


$production = get_production($link, $_SESSION['admin_production']);
print_prod_header($link, $production);

?>

<h1>Saving the booking</h1>

<p id="bookingsaved">The booking has been saved.</p>

<?
$message = '';
if(isset($_POST['performance']))
	$message .= '&toperformance=' . (int)$_POST['performance'];
if(isset($_POST['tosegment']))
	$message .= '&tosegment=' . (int)$_POST['tosegment'];
if(isset($_POST['fulltheatre']))
	$message .= '&fulltheatre=true';
?>
<p id="logout"><a href="admin_logout.php">Click here to logout.</a></p>
<p id="booktickets"><a href="admin_booking.php?<?=$message?>">Go back to the booking screen</a></p>

<?
print_prod_footer($link, $production);
?>
