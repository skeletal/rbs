<?
/**
 * This page displays a summary of the booked seats for each performance
 * and gives the user the option of choosing what type of tickets to buy.
 * (if there's more than one type of ticket)
 */


include_once('includes/utilities.php');
include_once('includes/prodmanagement.php');
include_once('includes/bookingmanagement.php');
include_once('includes/frames/prodtheme.php');
include_once('includes/frames/priceselection.php');

$link = db_connect();
include_once('includes/userauth.php');

$production = get_production($link, $_SESSION['production']);

$user = $_SESSION['user_id'];
include_once('includes/theatres/' . $production['theatre'] . '.inc');

$htmlheaders = '<link rel="stylesheet" type="text/css" href="css/bookingsummary.css" />';

$message = "";

// Receive a new booking if one's been submitted
if(isset($_POST['submitseats']))
{	
	if(isset($_POST['changeseat'])) {
        $total_seats_changed = 0;
        foreach ($_POST['changeseat'] as $performance_seats){
            $total_seats_changed += count($performance_seats);
        }
        if ($total_seats_changed > $max_booked_seats){
            $message .= "<p class='error'>Error: Cannot book more than $max_booked_seats. Please see the <a href='".$production['faqlocation']."'>FAQ</a> for more information on booking for groups.</p>";
            print_prod_header($link, $production);
            echo $message;
            print_prod_footer($link, $production);
            die();
        }
		$results = user_save_changes($link, $production['id'], $_SESSION['user_id'], $_POST['changeseat'], $theatre);
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
$bookings = get_bookings($link, $user, $production['id']);
print_prod_header($link, $production, $htmlheaders);
?>

<?=$message?>

<h1>Booking Summary</h1>

<!--p id="booktickets"><a href="booking.php#main">Click here to modify your bookings.</a></p-->


<?

// If there are no bookings atm give the user an error message and return the user to the bookings page.
if(!$bookings || count($bookings) == 0) {
	echo('<p class="booking_error">You have not made any bookings yet.  <a href="booking.php#main">Click here to go back to the booking page.</a></p>');
	print_prod_footer($link, $production['id']);
	exit;	
}

?>

<form method="post" action="paymentsummary.php#main">
<input type="hidden" name="submitprices" value="true">
<?
foreach($bookings as $booking) {
	echo('<div class="bookingsummaryperf">');
	print_price_selection($link, $booking);
	echo('</div>');
}
?>

<div class='payforbooking' id='bigformbuttons'>
<a href="booking.php#main" class="bigbutton">Modify Booking</a>
<input type="submit" value="Confirm Booking" class="bigbutton continue">
</div>
</form>

<?
print_prod_footer($link, $production);
?>
