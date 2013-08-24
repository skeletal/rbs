<?

include_once('includes/utilities.php');
include_once('includes/usermanagement.php');
$link = db_connect();

if($_GET['admin'] == 'true') {
	include('includes/adminauth.php');
	$isadmin = true;
	$prodid = (int)$_GET['production'];
	$perfid = (int)$_GET['performance'];
	if(!admin_has_access($prodid))
		exit;
} else {
	include('includes/userauth.php');
	$prodid = $_SESSION['production'];
	$perfid = (int)$_GET['performance'];
}

include_once('includes/prodmanagement.php');

$production = get_production($link, $prodid);

include_once('includes/theatres/' . $production['theatre'] . '.inc');

include_once('includes/bookingmanagement.php');


if(isset($_SESSION['user_admin']))
	$isadmin = $_SESSION['user_admin'];
$user = $_SESSION['user_id'];

// Get the closed segments for a performance
$cssql = "SELECT segment FROM closedsegment WHERE performance = $perfid";
$csresults = sql_get_array($link, $cssql);
$closedsegments = array();
foreach($csresults as $result)
	$closedsegments[] = $result['segment'];

// Lets get the seats!
$sql = "SELECT bs.seat, bs.status, b.user FROM bookedseat bs, booking b, performance p WHERE p.production = " . $prodid;
$sql .= " AND p.id = b.performance AND bs.booking = b.id AND p.id = $perfid";
$sql .= " AND bs.status > 0"; // No expired seats

if(!$isadmin) {
	$sql .= " AND p.isclosed = 0";
}

$results = mysql_query($sql);
if($results) {
	while($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
		if($row['status'] <= 0)
			continue;
		foreach($theatre as $key => $segment) {
			if(array_key_exists($row['seat'], $segment['seats'])) {
				if($isadmin) {
					$theatre[$key]['seats'][$row['seat']]['status'] = $row['status'];
				} else {
					// Return either "available", "booked" or "taken".
					if($row['user'] == $user) {
						if($row['status'] == 1)
							$theatre[$key]['seats'][$row['seat']]['status'] = 1;
						else if($row['status'] == 3 || $row['status'] == 8)
							$theatre[$key]['seats'][$row['seat']]['status'] = 3;
						else if($row['status'] >= 4 && $row['status'] <= 7)
							$theatre[$key]['seats'][$row['seat']]['status'] = 4;
						else
							$theatre[$key]['seats'][$row['seat']]['status'] = $row['status'];
					} else {
						$theatre[$key]['seats'][$row['seat']]['status'] = 2;
					}
				}
			}
		}
	}
}

if($isadmin)	// The admin stuff has booking info as well
	echo("{ 'seats' : ");

echo("{");
$out = "";
foreach($theatre as $segment) {
	foreach($segment['seats'] as $name => $seat) {
		$out .= "'" . $name . "' : ";
		if(isset($seat['status']))
			$out .= $seat['status'] . ",";
		else if(!$isadmin && in_array($segment['id'], $closedsegments)) // If the segment is closed
			$out .= "2,";
		else
			$out .= "0,";
	}
}

echo(substr($out, 0, -1));
echo("}");

if($isadmin) {
	$perfbookings = get_perf_bookings($link, $perfid);

	// The admin section has two more arrays:
	// 1: an array that gives a list of bookings and gives the booking information plus the seats booked
	// 2: an array connecting each booked seat to its corresponding booking.

	$bookings = array();
	$bookedseats = array();

	foreach($perfbookings as $booking) {
		$bookings[$booking['id']] = $booking;
		foreach($booking['seats'] as $seat) {
			if($seat['status'] > 0)
				$bookedseats[$seat['seat']] = $booking['id'];
		}
	}

	// Now print out the two arrays
	echo(",'bookings':{");
	$bf = false;
	foreach($bookings as $booking) {
		if(!$bf)
			$bf = true;
		else
			echo(',');
		echo($booking['id'] . ":");
		echo("{");
		echo('"id":"' . json_string($booking['id']) . '"');
		echo(",");
		echo('"email":"' . json_string($booking['email']) . '"');
		echo(",");
		echo('"phone":"' . json_string($booking['phone']) . '"');
		echo(",");
		echo('"username":"' . json_string($booking['username']) . '"');
		echo(",");
		echo('"email":"' . json_string($booking['email']) . '"');
		echo(",");
		echo('"name":"' . json_string($booking['name']) . '"');
		echo(",");
		echo('"description":"' . json_string($booking['description'], true) . '"');
		echo(",");
		echo('"amountpaid":"' . json_string($booking['amountpaid']) . '"');
		echo(",");
		echo('"discount":"' . json_string($booking['discount']) . '"');
		echo(",");
		echo('"deadline":"' . json_string($booking['deadline']) . '"');
		echo(",");
		echo('"pickedup":"' . json_string($booking['pickedup']) . '"');
		echo(",");
		echo('"seats":{');
		$first = false;
		foreach($booking['seats'] as $seat) {
			if($seat['status'] <= 0)
				continue;
			if(!$first)
				$first = true;
			else
				echo(',');
			echo('"' . $seat['seat'] . '":' . $seat['status']);
		}
		echo("}");
		echo("}");
	}

	echo("},'bookedseats':{");
	// Print out the booked seats
	$first = false;
	foreach($bookedseats as $seat => $booking) {
		if(!$first)
			$first = true;
		else
			echo(',');
		echo('"' . $seat . '":' . $booking);
	}
	echo("}");
	echo("}");
}
