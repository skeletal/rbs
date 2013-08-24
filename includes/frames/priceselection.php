<?
/**
 * This is for the bookingsummary and admin_bookingsummary pages.
 * Allows the user to see what type of tickets have been booked and select whether they are adults or children.
 */

include_once('includes/pricemanagement.php');
include_once('includes/utilities.php');

/**
 * Converts the status into a pretty message
 */
function status_message($status) {
	switch($status) {
	case -1:
		return "Expired";
	// case 0: free doesn't apply here
	case 1:
		return "Booked";
	// case 2: taken by another user doesn't apply here
	case 3:
		return "Confirmed";
	case 4:
		return "Paid";
	case 5:
		return "Paid at the Sales Desk";
	case 6:
		return "Paid by Direct Debit";
	case 7:
		return "Paid through Paypal";
	case 8:
		return "Payment Pending";
	case 9:
		return "Unavailable";
	case 10:
		return "VIP";
	}
}

/**
 * print the price selection table
 */
function print_price_selection($link, $booking, $isadmin = false) {
	// The prices of the booking
	$prices = get_prices($link, $booking['performance'], $isadmin);

	if($booking['title'] !== '') {
?>
<h2>Bookings for <?=$booking['title']?>: <? echo(prettydate($booking['tsdate']))?></h2>
<?
	} else {
?>
<h2>Bookings for <?=$booking['date']?> <?=$booking['starttime']?></h2>
<?
	}
	
	// If there are no booked seats then just display a message.
	$seatsbooked = false;
	foreach($booking['seats'] as $seat) {
		if($seat['status'] > 0) {
			$seatsbooked = true;
			break;
		}
	}
	if(!$seatsbooked) {
		echo('<div class="bookingnoseats">There are no seats currently booked for this performance.</div>');
		return;
	}

?>
<table class="bookingtable"><tr><th></th><th></th>
<?
	if(count($prices) > 1) {
		foreach($prices as $price) {
			echo("<th class='priceheader'>" . $price['name'] . " ($" . $price['price'] . ")</th>");
		}
	}
?>
</tr>
<?

	$nexpired = 0;
	foreach($booking['seats'] as $seat) {
		if($seat['status'] == -1) {
			$nexpired += 1;
			continue;
		}
?>
<tr><td class="booking"><?=$seat['seat']?></td><td class="bookedstatus"><?=status_message($seat['status'])?></td>
<?

		if($seat['price'] == null) {
			reset($prices);
			$fprice = current($prices);
			$curprice = $fprice['id'];
		} else
			$curprice = $seat['price'];
		
		if($seat['status'] == 1 || ($isadmin && ($seat['status'] >= 1 && $seat['status'] <= 8))) {
			if(count($prices) > 1) {
				foreach($prices as $price) {
?>
<td class="price"><input type="radio" name="price[<?=$seat['id']?>]" value="<?=$price['id']?>"<?if($curprice == $price['id']) echo(' checked'); ?>></td>
<?
				}
			} else {
				echo("<input type='hidden' name='price[" . $seat['id'] . "]' value='" . $prices[0]['id'] . "'>");
			}
		} else if(count($prices) > 1) {
			foreach($prices as $price) {
?>
<td></td>
<?
			}
		}
?>
</tr>
<?
		
	}
?>
</table>
<?
if($nexpired == 1)
	echo("<div class='bookingexpired'>You have previously booked $nexpired seat, however the bookings have since expired.</div>");
else if($nexpired > 1)
	echo("<div class='bookingexpired'>You have previously booked $nexpired seats, however the bookings have since expired.</div>");

}
?>
