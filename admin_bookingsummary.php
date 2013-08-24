<?
/**
 * This page displays a summary of the booked seats
 * and gives the user the option of choosing the type of ticket and entering other details.
 * (if there's more than one type of ticket)
 */


include_once('includes/utilities.php');
$link = db_connect();
include_once('includes/adminauth.php');

include_once('includes/prodmanagement.php');
include_once('includes/bookingmanagement.php');
include_once('includes/pricemanagement.php');
include_once('includes/frames/priceselection.php');

include_once('includes/frames/prodtheme.php');

$production = get_production($link, $_SESSION['admin_production']);

check_access_to_production($_SESSION['admin_production']);

$user = $_SESSION['user_id'];
include_once('includes/theatres/' . $production['theatre'] . '.inc');

$htmlheaders = '<link rel="stylesheet" type="text/css" href="css/bookingsummary.css" />';

$message = "";

// Receive a new booking if one's been submitted
if(isset($_POST['modify']) || isset($_POST['new']))
{
	$performance = (int)$_POST['performance'];
	if(isset($_POST['modify']))
		$bookingid = $_POST['booking'];
	else {
		$bookingid = create_user_booking($link, $performance, $_SESSION['admin_id'], 1);
		if($bookingid === null) {
			echo('Creating the booking failed!');
			exit;
		}
	}

	if(isset($_POST['changeseat'])) {

		$results = admin_save_changes($link, $production['id'], $_SESSION['admin_id'], $bookingid, $_POST['changeseat'], $theatre);

		

		if(count($results) != 0) {
			$message .= "<p class='error'>Error: Unfortunately the following seats have been taken:</p>";
		}
		foreach($results as $performance => $seats) {
			foreach($seats as $seat => $reason) {
				$message .= "<p class='seattaken'>$seat:  $reason</p>";
			}
		}
	}
}

print_prod_header($link, $production, $htmlheaders);
?>

<?=$message?>

<h1>Booking Summary</h1>

<form method="post" action="admin_savebooking.php">
<input type="hidden" name="submitprices" value="true">
<?
$booking = get_booking($link, $bookingid);

print_price_selection($link, $booking, true);
echo("<h4>Booking id: $bookingid</h4>");
?>
<div class="bookingupdate">
	<div class="bookinglabel">Name:</div>
	<div class="bookinginput"><textarea name="name" class="bookingta"><?=htmlspecialchars($booking['name'])?></textarea></div>
</div>
<div class="bookingupdate">
	<div class="bookinglabel">Description:</div>
	<div class="bookinginput"><textarea name="description" class="bookingta"><?=htmlspecialchars($booking['description'])?></textarea></div>
</div>
<div class="bookingupdate">
	<div class="bookinglabel">Phone Number:</div>
	<div class="bookinginput"><input type="text" name="phonenumber" class="bookingin" value="<?=htmlspecialchars($booking['phonenumber'])?>"></div>
</div>
<div class="bookingupdate">
	<div class="bookinglabel">Email Address:</div>
	<div class="bookinginput"><input type="email" name="email" class="bookingin" value="<?=htmlspecialchars($booking['email'])?>"></div>
</div>
<!--
<div class="bookingupdate">
	<div class="bookinglabel">Has it been picked up?:</div>
	<div class="bookinginput"><input type="checkbox" name="pickedup" class="bookingcb" <? if($booking['phonenumber']) echo("CHECKED");?>></div>
</div>
<div class="bookingupdate">
	<div class="bookinglabel">Amount Paid:</div>
	<div class="bookinginput">$<input name="amountpaid" class="bookingin" value="<?=$booking['amountpaid']?>"></div>
</div>
<div class="bookingupdate">
	<div class="bookinglabel">Discount:</div>
	<div class="bookinginput">$<input name="discount" class="bookingin" value="<?=$booking['discount']?>"></div>
</div>
<div class="bookingupdate">
	<div class="bookinglabel">Deadline:</div>
	<div class="bookinginput"><input name="deadline" class="bookingin" value="<?=$booking['deadline']?>"></div>
</div>
<div class="bookingupdate">
	<div class="bookinglabel">Email Sent:</div>
	<div class="bookinginput"><input name="emailsent" class="bookingin" value="<?=$booking['emailsent']?>"></div>
</div>
--!>
<input type="hidden" name="bookingid" value="<?=$bookingid?>">
<?
if(isset($_POST['tosegment']))
	echo('<input type="hidden" name="tosegment" value="' . (int)$_POST['tosegment'] . '">');
if(isset($_POST['performance']))
	echo('<input type="hidden" name="performance" value="' . (int)$_POST['performance'] . '">');
if(isset($_POST['fulltheatre']))
	echo('<input type="hidden" name="fulltheatre" value="true">');
?>
<p class="savebooking"><input type="submit" value="Save The Booking"></p>
</form>
<?
print_prod_footer($link, $production);
?>
