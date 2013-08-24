<?
include_once('utilities.php');
include_once('perfmanagement.php');

function get_booking_bookedseats($link, $bookingid) {
	$bookingid = (int)$bookingid;
	$sql = "SELECT id, seat, status, price FROM bookedseat WHERE booking = $bookingid ORDER BY status ASC, seat ASC";

	return sql_get_array($link, $sql);
}

/**
 * This function simply returns an array performance -> number of seats booked or paid for for a normal user
 * $status specifies the level of which to return above (i.e. where status > $status)
 * - setting this to 0 means seats booked or paid
 * - setting this to 1 means seats paid
 * $equal specifies whether to check greater than or equal (i.e. where status = $status)
 * - setting $status = 1, $equal = true, gives only seats booked but not paid
 */
function get_seats_selected($link, $user, $status = 0, $equal = false) {
	$prodid = (int)$prodid;
	$user = (int)$user;
	$sql = "SELECT b.performance, count(bs.id) numseats FROM booking b, bookedseat bs WHERE bs.booking = b.id AND b.user = $user AND bs.status ".($equal?"=":">")." $status GROUP BY b.performance";
	$results = sql_get_array($link, $sql);

	$perfseats = array();
	foreach($results as $result) {
		$perfseats[$result['performance']] = $result['numseats'];
	}
	return $perfseats;
}

/**
 * This function will get all the bookings of the show.  You can optionally give it restrictions.  Look at admin_bookinglist.php for more info about that.
 */
function get_all_bookings($link, $prodid, $sortby = 'id', $sortdir = 'asc', $restricts = array(), $includeadmin = true) {
	$prodid = (int)$prodid;
	$sortsql = '';
	switch($sortby) {
    case 'paymentid':
        $sortsql = ' order by UPPER(paymentid)';
        break;
	case 'id':
		$sortsql = ' order by b.id';
		break;
	case 'name':
		$sortsql = ' order by name';
		break;
	case 'deadline':
		$sortsql = ' order by deadline';
		break;
	case 'bookedtime':
		$sortsql = ' order by bookedtime';
		break;
	}

	if($sortsql != '') {
		if($sortdir == 'desc')
			$sortsql .= ' desc';
		else
			$sortsql .= ' asc';
	}

	$conditions = '';
	if(isset($restricts['performance']) && $restricts['performance'] >= 0) {
		$conditions .= " and b.performance = " . (int)$restricts['performance'];
	}

	if(isset($restricts['pickedup'])) {
		switch($restricts['pickedup']) {
		case 0:
			$conditions .= " and pickedup = 0";
			break;
		case 1:
			$conditions .= " and pickedup = 1";
			break;
		}
	}

	$usertable = "(select id, production, name, email, phone, paymentid, 0 isadmin from user";
	if($includeadmin)
		$usertable .= " UNION select id, -1 production, name, email, phone, \"\" paymentid, 1 isadmin from admin";
	$usertable .= ")";

	$sql = "select b.*, UNIX_TIMESTAMP(p.date) tsdate, u.paymentid, u.email, u.phone from booking b, performance p, $usertable u where b.performance = p.id and b.user = u.id and b.bookedbyadmin = u.isadmin and p.production = $prodid";
	$sql .= $conditions;
	$sql .= $sortsql;
	$bookings =  sql_get_array($link, $sql);
	foreach($bookings as $key => $booking) {
		$seatconds = '';
		if(isset($restricts['status'])) {
			foreach($restricts['status'] as $status => $ison) {
				$seatconds .= "status = " . (int)$status . " OR ";
			}
			$seatconds = ' AND (' . substr($seatconds, 0, -4) . ')';
		}
		$sql = "SELECT id, seat, status, price FROM bookedseat WHERE booking = " . $booking['id'] . $seatconds . " ORDER BY status ASC";
		$bookings[$key]['seats'] = sql_get_array($link, $sql);
		if(count($bookings[$key]['seats']) == 0)
			unset($bookings[$key]);
	}
	return $bookings;
}

function get_ticket_totals($link, $prodid) {
	$prodid = (int)$prodid;
	$basesql = "select count(bs.id) from booking b, bookedseat bs where bs.booking = b.id and b.performance = perf.id";
	$endsql = "group by b.performance";
	$sql = "SELECT ";
	$sql .= "($basesql and bs.status = 1) bookedseats,";
	$sql .= "($basesql and bs.status = 3) confirmedseats,";
	$sql .= "($basesql and bs.status < 8 and bs.status > 3) paidseats,";
	$sql .= "($basesql and bs.status = 8) ppseats,";
	$sql .= "($basesql and bs.status = 10) vipseats,";
	$sql .= "perf.id, perf.title from performance perf where perf.production = $prodid";
	$tt = sql_get_array($link, $sql);
	$tt["Total"] = array("confirmedseats" => 0, "paidseats" => 0, "vipseats" => 0, "ppseats" => 0, "bookedseats" => 0, "title" => "Total");
    $priceclass_basesql = "select p.name, count(bs.price) as count from booking b, bookedseat bs, price p, performance perf where bs.booking = b.id AND b.performance = perf.id AND perf.production = $prodid AND p.id = bs.price";
    $priceclass_confirmed = "$priceclass_basesql AND bs.status > 1 group by p.name";
    $priceclass_booked = "$priceclass_basesql AND bs.status = 1 group by p.name";
    $confirmed = sql_get_array($link, $priceclass_confirmed);
    $booked = sql_get_array($link, $priceclass_booked);
    $tt["Total"]["confirmed"] = $confirmed;
    $tt["Total"]["booked"] = $booked;
	foreach ($tt as $t) {
		$tt["Total"]["bookedseats"] += $t["bookedseats"];
		$tt["Total"]["confirmedseats"] += $t["confirmedseats"];
		$tt["Total"]["paidseats"] += $t["paidseats"];
		$tt["Total"]["vipseats"] += $t["vipseats"];
		$tt["Total"]["ppseats"] += $t["ppseats"];
	}
	return $tt;
}

/**
 * This function gets the bookings for a user.  Inside the returned array is also an array called "seats" which holds seat, status, price arrays.
 */
function get_bookings($link, $user, $production) {
	$user = (int)$user;
	$production = (int)$production;

	$sql = "SELECT b.*, p.date, UNIX_TIMESTAMP(p.date) tsdate, p.starttime, p.description, p.title, p.isclosed";
	$sql .= " FROM booking b, performance p";
	$sql .= " WHERE b.performance = p.id and p.production = $production and user = $user ORDER BY p.date ASC";

	$bookings = sql_get_array($link, $sql);

	if(!$bookings)
		return null;

	// Now for each booking we want to get what seats are being booked.
	foreach($bookings as $key => $booking) {
		$bookings[$key]['seats'] = get_booking_bookedseats($link, $booking['id']);
	}

	return $bookings;
}

/**
 * Gets the information for a single booking.
 */
function get_booking($link, $bookingid) {
	$bookingid = (int)$bookingid;

	$sql = "SELECT b.*, p.date, UNIX_TIMESTAMP(p.date) tsdate, p.starttime, p.description perfdesc, p.title, p.isclosed";
	$sql .= " FROM booking b, performance p";
	$sql .= " WHERE b.performance = p.id and b.id = $bookingid ORDER BY p.date ASC";

	$bookings = sql_get_array($link, $sql);

	if(!$bookings || count($bookings) != 1)
		return null;
	
	$booking = current($bookings);

	$booking['seats'] = get_booking_bookedseats($link, $booking['id']);

	return $booking;
}

/**
 * This function gets all bookings connected with a performance.
 * Similar to the get_bookings function but returns an array of the bookings
 */
function get_perf_bookings($link, $performance) {
	$performance = (int)$performance;

	$usertable = "(select id, production, name, email, phone, UPPER(paymentid) AS paymentid, 0 isadmin from user UNION select id, -1 production, name, email, phone, \"\" paymentid, 1 isadmin from admin)";

	$sql = "SELECT b.*, u.email, u.name username, u.phone FROM booking b, $usertable u WHERE b.user = u.id AND b.performance = $performance AND u.isadmin = b.bookedbyadmin";
	$bookings = sql_get_array($link, $sql);
	if(!$bookings)
		return array();
	
	foreach($bookings as $key => $booking) {
		$sql = "SELECT id, seat, status, price FROM bookedseat WHERE booking = " . $booking['id'] . " ORDER BY status ASC";
		$bookings[$key]['seats'] = sql_get_array($link, $sql);
	}
	return $bookings;
}

function get_current_deadline($link, $performance) {
	$performance = (int)$performance;
	$sql = "SELECT paywindow, expiretimeofday, UNIX_TIMESTAMP(deadline) deadline FROM performance WHERE id = $performance";
	$results = mysql_query($sql, $link);
	if($results && $row = mysql_fetch_array($results, MYSQL_ASSOC)) {
		$paywindow = $row['paywindow'];
		$harddeadline = $row['deadline'];
		$expiretimeofday = $row['expiretimeofday'];
	}
	else {
		echo('could not fetch deadline');
		echo($sql);
		exit();
		return null;
	}
		
	// Sorting out the deadline.
	$deadline = time() + $paywindow;

    // If an expiretimeofday is set, round to the expiry time of day on the
    // same day as $deadline, or the day after if that has already passed.
	if($expiretimeofday > 0 && $expiretimeofday <= 24) {
        $expiretime = mktime($expiretimeofday, 0, 0, date("m", $deadline), date("d", $deadline), date("Y", $deadline));
        if ($expiretime < $deadline) {
            $deadline = $expiretime + 60*60*24;
        } else {
            $deadline = $expiretime;
        }
	}

	if($deadline > $harddeadline)
		$deadline = $harddeadline;

	return $deadline;
}

function create_user_booking($link, $performance, $user, $bookedbyadmin = 0) {
	// We first need to get the pay window and hard deadline for each performance
	if($bookedbyadmin != 1)
		$bookedbyadmin = 0;
	
	$deadline = get_current_deadline($link, $performance);
	$now = time();

	if($bookedbyadmin == 1)
		$usernamesql = "(select name from admin where id = $user)";
	else
		$usernamesql = "(select name from user where id = $user)";

	$sql = "INSERT INTO booking(user, name, performance, deadline, modifiedtime, bookedbyadmin) VALUES ($user, $usernamesql, $performance, FROM_UNIXTIME($deadline)";
	$sql .= ", FROM_UNIXTIME($now), $bookedbyadmin)";
	$result = mysql_query($sql, $link);
	if(!$result)
		return null;
	$retid = mysql_insert_id($link);
	return $retid;
}


/**
 * This function gets a user's booking for a performance.  If it doesn't exist it creates one.
 */
function get_user_booking($link, $performance, $user) {
	$performance = (int)$performance;
	$user = (int)$user;

	$sql = "SELECT * FROM booking WHERE performance = $performance AND user = $user";
	$results = mysql_query($sql, $link);

	if(!$results)
		return null;

	if($row = mysql_fetch_array($results, MYSQL_ASSOC))
		return $row;

	create_user_booking($link, $performance, $user);
	return get_user_booking($link, $performance, $user);
}

/**
 * This function gets a list of changes that a user has made to their booking and saves them.
 * It also automatically creates the booking if it hasn't yet been created.
 * It returns a list of seats which were rejected and the reason, or null if no seats were rejected.
 */
function user_save_changes($link, $prodid, $user, $changes, $theatre) {
	$prodid = (int)$prodid;
	$user = (int)$user;

	// $theatre is assumed to be validated already

	// We firstly need to validate that the theatre contains the seats we want to change.  If not then ignore them.
	// We also at the same time sort them into two arrays, unbooking and not unbooking as they are treated differently.

	$unbookings = array();
	$bookings = array();

	foreach($changes as $perfid => $seats) {
		$perfid = (int)$perfid;

		// Don't let the user change seats for a closed performance
		$performance = get_performance($link, $perfid);
		if($performance['isclosed'] == 1)
			continue;

		$isseat = false;
		$closedsegments = get_closed_segments($link, $perfid);
		foreach($seats as $seat => $status) {
			foreach($theatre as $segment) {
				// Don't accept the booking if the segment is closed
				if(in_array($segment['id'], $closedsegments))
					continue;

				if(array_key_exists($seat, $segment['seats'])) {
					$isseat = true;
					break;
				}
			}
			if(!$isseat) {
				unset($changes[$key][$seat]);
			} else if($status == 0) { // Are we unbooking the seat?
				$unbookings[$perfid][$seat] = 0;
			} else {
				$bookings[$perfid][$seat] = (int)$status; // Validate at the same time.
			}
		}
	}

	// Now we have two arrays, $bookings and $unbookings.  Lets handle $unbookings first.
	// There's no "rejecting" here.  If the user has the booking then it's removed.  If not or the user doesn't have permission it's not changed.
	foreach($unbookings as $performance => $seats) {
		foreach($seats as $seat => $status) {
			$bookingsql = "SELECT booking.id FROM booking WHERE user = $user AND performance = $performance";
			$sql = "DELETE FROM bookedseat WHERE seat = '$seat' AND booking = ($bookingsql)";
			mysql_query($sql);
			rbslog("User $user unbooked seat $seat", 2);
		}
	}

	// Now lets deal with $bookings.
	$rejects = array();
	$bookingids = array();

	foreach($bookings as $performance => $seats) {
		foreach($seats as $seat => $status) {
			// First we need to check that the user has permission to make the change.  If not then ignore.
			if($status != 1)
				continue;  // TODO: implement logging to stop people from playing silly buggers

			// Now we check that noone else other than the user has that seat
			$sql = "SELECT b.user, bs.status FROM bookedseat bs, booking b";
			$sql .= " WHERE bs.seat = '$seat' AND bs.booking = b.id AND b.user <> $user";
			$sql .= " AND bs.status != -1"; // The seat hasn't expired
			$sql .= " AND b.performance = $performance";

			$results = mysql_query($sql, $link);
			if(!$results || mysql_num_rows($results) > 0) {
				// If we haven't gotten no results then put on the reject list
				if(!isset($rejects[$performance]))
					$rejects[$performance] = array();
				if(!$results) {
					rbslog("Database failure 0 in user_save_changes.  User $user, seat $seat, performance $performance. The SQL was: " . $sql, 2);
					$rejects[$performance][$seat] = "There has been a database error.  Please try again.  Failure number 0";
				} else {
					rbslog("Seat has already been taken.  User $user, seat $seat, performance $performance. The SQL was: " . $sql, 2);
					$rejects[$performance][$seat] = "This seat has already been taken.  Please choose another or adjust your booking.";
				}
				continue;
			}

			// Lets change the booking.  Firstly we need to ensure there is a booking for that user.
			if(!isset($bookingids[$performance])) {
				$booking = get_user_booking($link, $performance, $user);
				$bookingids[$performance] = $booking['id'];
				$deadline = get_current_deadline($link, $performance);
				$sql = "UPDATE booking SET deadline = FROM_UNIXTIME($deadline) WHERE id = " . $booking['id'];
				mysql_query($sql, $link);
			}

			$bid = $bookingids[$performance];

			// Now see if there's already a seat for that booking
			$sql = "SELECT id FROM bookedseat WHERE booking = $bid AND seat = '$seat'";
			$results = mysql_query($sql, $link);

			$failed = false;
			if(!$results) {
				rbslog("Database failure 1 in user_save_changes.  User $user, seat $seat, performance $performance. The SQL was: " . $sql, 2);
				$failed = 1;
			} else if($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				rbslog("Changing the status of seat $seat of performance $performance for user $user", 1);
				$id = $row['id'];
				// Change the booking
				$sql = "UPDATE bookedseat SET status = $status WHERE id = $id";
				if(!mysql_query($sql, $link)) {
					rbslog("Database failure 2 in user_save_changes.  User $user, seat $seat, performance $performance. The SQL was: " . $sql, 2);
					$failed = 2;
				}
			} else {
				rbslog("Booking seat $seat of performance $performance for user $user", 1);
				// We want to add a new booked seat with that status
				$sql = "INSERT INTO bookedseat(seat, booking, status, guid) VALUES (";
				// We put in the first price as the default.
                // Generate guid
                $guid = uniqid();
				$sql .= "'$seat', $bid, $status, '$guid')";
				if(!mysql_query($sql, $link)) {
					rbslog("Database failure 2 in user_save_changes.  User $user, seat $seat, performance $performance. The SQL was: " . $sql, 2);
					$failed = 3;
				}
			}

			if($failed) {
				if(!isset($rejects[$performance]))
					$rejects[$performance] = array();
				$rejects[$performance][$seat] = "There has been a database error.  Please try again.  Failure number " . $failed;
			}
		}
	}

	return $rejects;	
}


/**
 * This function gets a list of changes that a admin has made to a booking and saves them.
 * It also automatically creates the booking if booking = -1.
 * It returns a list of seats which were rejected and the reason, or null if no seats were rejected.
 */
function admin_save_changes($link, $prodid, $user, $bookingid, $changes, $theatre) {
	$prodid = (int)$prodid;
	$bookingid = (int)$bookingid;
	$user = (int)$user;
	$perfid = (int)$perfid;

	// Create a booking for the performance if it hasn't already been created.
	if($bookingid == -1) {
		$bookingid = create_user_booking($link, key($changes), $userid, 1);
	}

	// $theatre is assumed to be validated already

	// We firstly need to validate that the theatre contains the seats we want to change.  If not then ignore them.
	// We also at the same time sort them into two arrays, unbooking and not unbooking as they are treated differently.

	$unbookings = array();
	$bookings = array();

	foreach($changes as $performance => $seats) {
		$isseat = false;
		foreach($seats as $seat => $status) {
			foreach($theatre as $segment)
				if(array_key_exists($seat, $segment['seats'])) {
					$isseat = true;
					break;
				}
			if(!$isseat) {
				unset($changes[$key][$seat]);
			} else if($status == 0) { // Are we unbooking the seat?
				$unbookings[$performance][$seat] = 0;
			} else {
				$bookings[$performance][$seat] = (int)$status; // Validate at the same time.
			}
		}
	}

	// Now we have two arrays, $bookings and $unbookings.  Lets handle $unbookings first.
	// Unset the seats
	foreach($unbookings as $performance => $seats) {
		foreach($seats as $seat => $status) {
			rbslog("Removing seat $seat for user $user and booking $bookingid", 1);
			$sql = "DELETE FROM bookedseat WHERE seat = '$seat' AND booking = $bookingid";
			mysql_query($sql);
		}
	}

	// Now lets deal with $bookings.
	$rejects = array();

	foreach($bookings as $performance => $seats) {
		foreach($seats as $seat => $status) {
			// Now we check that noone else other than the user has that seat
			$sql = "SELECT b.user, bs.status FROM bookedseat bs, booking b";
			$sql .= " WHERE bs.seat = '$seat' AND bs.booking <> $bookingid";
			$sql .= " AND b.id = bs.booking AND bs.status != -1"; // The seat hasn't expired
			$sql .= " AND b.performance = $performance";

			$results = mysql_query($sql, $link);
			if(!$results || mysql_num_rows($results) > 0) {
				// If we haven't gotten no results then put on the reject list
				if(!isset($rejects[$performance]))
					$rejects[$performance] = array();
				if(!$results) {
					rbslog("Admin - database failure 0 in admin_save_changes.  User $user, booking $bookingid, seat $seat. The SQL was: " . $sql, 2);
					$rejects[$performance][$seat] = "There has been a database error.  Please try again";
				} else {
					rbslog("Admin - Seat already taken.  User $user, booking $bookingid, seat $seat. The SQL was: " . $sql, 2);
					$rejects[$performance][$seat] = "This seat has already been taken.  Please choose another or adjust your booking.";
				}
				continue;
			}

			// Now see if there's already a seat for that booking
			$sql = "SELECT id FROM bookedseat WHERE booking = $bookingid AND seat = '$seat'";
			$results = mysql_query($sql, $link);

			$failed = false;
			if(!$results) {
				$failed = true;
				rbslog("Admin - database failure 1 in admin_save_changes.  User $user, booking $bookingid, seat $seat. The SQL was: " . $sql, 2);
			} else if($row = mysql_fetch_array($results, MYSQL_ASSOC)) {
				rbslog("Admin changed status of $seat for user $user with booking $bookingid to $status", 1);
				$id = $row['id'];
				// Change the booking
				$sql = "UPDATE bookedseat SET status = $status WHERE id = $id";
				$failed = !mysql_query($sql, $link);
			} else {
				rbslog("Admin booked seat $seat status $status with booking $bookingid", 1);
				// We want to add a new booked seat with that status
                $guid = uniqid();
				$sql = "INSERT INTO bookedseat(seat, booking, status, guid) VALUES (";
				// We put in the first price as the default.
				$sql .= "'$seat', $bookingid, $status, '$guid')";
				$failed = !mysql_query($sql, $link);
			}

			if($failed) {
				if(!isset($rejects[$performance]))
					$rejects[$performance] = array();
				$rejects[$performance][$seat] = "There has been a database error.  Please try again.";
			}
		}
	}

	return $rejects;	
}

function update_booking($link, $booking) {
	if(!isset($booking['id']))
		return false;

	$sql = "UPDATE booking SET ";
	if(isset($booking['name']))
		$sql .= "name='".stripslashes($booking['name'])."',";
	if(isset($booking['description']))
		$sql .= "description='".mysql_real_escape_string(stripslashes($booking['description']))."',";
	if(isset($booking['phonenumber']))
		$sql .= "phonenumber='".mysql_real_escape_string(stripslashes($booking['phonenumber']))."',";
	if(isset($booking['pickedup']))
		$sql .= "pickedup=".mysql_real_escape_string(stripslashes($booking['pickedup'])).",";
	if(isset($booking['amountpaid']))
		$sql .= "amountpaid=".(int)$booking['amountpaid'].",";
	if(isset($booking['discount']))
		$sql .= "discount=".(int)$booking['discount'].",";
	if(isset($booking['deadline']))
		$sql .= "deadline='".mysql_real_escape_string(stripslashes($booking['deadline']))."',";
	if(isset($booking['emailsent']))
		$sql .= "emailsent=".(int)$booking['emailsent'].",";
	$sql = substr($sql,0,-1);
	$sql .= " WHERE id = " . (int)$booking['id'];
	return mysql_query($sql);	
}

function expire_booking($link, $bookingid) {
	$bookingid = (int)$bookingid;
	$sql = "UPDATE bookedseat SET status = -1 WHERE booking = $bookingid AND status = 1";
	mysql_query($sql, $link);
}
