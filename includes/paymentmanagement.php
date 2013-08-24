<?
/**
 * Manages payments and retrieving payment information
 */

include_once('utilities.php');

/**
 * Get the payment summary of a user for the payment summary page.
 */
function get_payment_summary($link, $user) {
	$user = (int)$user;
	$sql = "SELECT b.performance, b.id booking, COUNT(bs.id) num, bs.status, p.name, p.price, b.deadline, UNIX_TIMESTAMP(b.deadline) tsdeadline";
	$sql .= " FROM bookedseat bs, booking b, price p";
	$sql .= " WHERE bs.booking = b.id AND bs.price = p.id AND b.user = $user";
	$sql .= " GROUP BY b.id, bs.status, p.id ORDER BY performance asc, price DESC";

	$results = sql_get_array($link, $sql);

	if(!$results)
		return null;

	$summary = array();

	// For easy use we want to restructure the array
	foreach($results as $result) {
		if(!isset($summary[$result['performance']])) {
			$summary[$result['performance']] = array();
		}

		// Separate into booked and paid
		if($result['status'] == 1)
			$summary[ $result['performance'] ][1][] = $result;
		else if($result['status'] > 1)
			$summary[ $result['performance'] ][2][] = $result;
	}
	return($summary);
}

/**
 * Returns a list of seat IDs, and price for a given userPaymentId. Similar to get_payment_summary
 *  but finds only booked seats based on a user's payment ID.
 */
function get_payment_total($link, $userPaymentId) {
	$sql = "SELECT bs.id as seatId, p.price as price, b.discount as discount";
	$sql .= " FROM bookedseat bs ";
	$sql .= "  JOIN booking b on bs.booking = b.id ";
	$sql .= "  JOIN user u on u.id = b.user ";
	$sql .= "  JOIN price p on bs.price = p.id ";
	$sql .= " WHERE u.paymentId = '" . mysql_real_escape_string($userPaymentId) . "' and ";
	$sql .= "  bs.status = 1";

	$results = sql_get_array($link, $sql);

	if(!$results)
		return null;

	// a an arra of rows, where each row has 'seatId' and 'price'
	return $results;
}

/**
 * Takes a list of seats returned by get_payment_total
 * and calculates the total price
 */
function calculate_total_payment($seats) {
	$totalPrice = 0;
	foreach($seats as $seat) {
		$totalPrice += $seat['price'];
		$totalPrice -= $seat['discount'];
	}
	return $totalPrice;
}

/**
 * Takes a list of seats returned by get_payment_total 
 * and marks them as paid
 */
 function mark_seats_paid($link, $seats) {
	 $seatList = array();
	 foreach($seats as $seat) {
		 $seatList[] = $seat['seatId'];
	 }

	 $seatsInQuery = implode(",", $seatList);

	 $sql = "UPDATE bookedseat bs ";
	 $sql .= " set bs.status = 7 ";
	 $sql .= " where bs.id in ($seatsInQuery) and ";
	 $sql .= "  bs.status = 1";

	 return mysql_query($sql, $link); 
 }
	


 ?>
