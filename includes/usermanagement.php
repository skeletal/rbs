<?

/**
 * File containing functions which interface with the database and provide user management functionality.
 *
 * This file also sanitises and whitelists all input.
 */

include_once('utilities.php');
include_once('prodmanagement.php');

/*
 * Helpful variables.
 */

$regex_email = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/';
$regex_username = '/^[A-Za-z0-9_+-]+$/';
$regex_name = '/^[A-Za-z0-9_\'+ -]+$/'; // IMPORTANT - DOES NOT SANITISE FOR SQL STATEMENTS
$regex_phone = '/^[0-9 \(\)]{4,12}$/';

function user_exists($link, $production, $email) {
	global $regex_email;
	$production = (int)$production;
	if(preg_match($regex_email, $email) !== 1)
		return false;

	$query = "select id from user where email = '$email' and production = '$production'";
	$results = mysql_query($query);
	return ($results && mysql_num_rows($results) > 0); 
}


/**
 * This function creates a new user in the database.  $production should equal -1 for the admin page.
 * Show is assumed to exist already
 * Returns a negative number on failure
 */
function create_user($link, $production, $email, $pass, $name, $phone, $isadmin = 0) {
	global $regex_email, $regex_username, $regex_name, $regex_phone;
	$email = strtoupper($email);
	if(preg_match($regex_email, $email) !== 1)
		return -1;
	if(preg_match($regex_name, $name) !== 1)
		return -3;
	$name = mysql_real_escape_string($name);

	if(preg_match($regex_phone, $phone) !== 1)
		return -4;

	if($isadmin === true)
		$isadmin = 1;
	else
		$isadmin = 0;

	$production = (int)$production;

	if(user_exists($link, $production, $email)) {
		rbslog("Create user attempted for $email but user exists");
		return -5;
	}

	// Create a salt, then put the info in the database
	$salt = rand_str();
	$passwordhash = md5($salt . $pass);

	// Create a random booking id then make sure that it's not taken already
	$paymentid = rand_str();
	while(1) {
		$query = "select id from user where paymentid = '$paymentid'";
		$results = mysql_query($query);
		if($results && mysql_num_rows($results) > 0)
			$paymentid = rand_str();
		else
			break;
	}

	$sql = "insert into user(production, name, email, salt, password, phone, admin, paymentid) values (";
	$sql .= "$production, '$name', '$email', '$salt', '$passwordhash', '$phone', $isadmin, '$paymentid'";
	$sql .= ")";
	if(mysql_query($sql)) {
		rbslog("Created user $email: $name", 1);
		// Lets get the user id
		$query = "select id from user where email = '$email' and production = '$production'";
		$result = mysql_query($query);
		$row = mysql_fetch_array($result);
		return ($row['id']);
	} else {
		rbslog("Create user SQL statement failed: $sql");
		return -6;
	}
}


/**
 * This function firstly sanitises the variables, then it looks through the database for a valid user identifier.  It also sets session variables.
 * 
 * If a user identifier is found it returns the id.
 * If no user identifier is found it returns null.
 */
function user_login($link, $production, $email, $pass) {
	global $regex_email;
	// Sanitise variables
	$email = strtoupper($email);
	if(preg_match($regex_email, $email) !== 1)
		return -3;
	$production = (int)$production;

	rbslog("attempting to log in for user $email with ip address " . $_SERVER['REMOTE_ADDR'], 1);

	// First see if the user has an account in the current production
	$q_thisproduction = "select * from user where email = '$email' and production = $production";
	$r_thisproduction = mysql_query($q_thisproduction, $link);

	if(mysql_num_rows($r_thisproduction) == 1) {
		// Lets test the password
		$row = mysql_fetch_array($r_thisproduction, MYSQL_ASSOC);

		$passwordhash = md5($row['salt'] . $pass);
		if($passwordhash === $row['password']) { // Log the user in!
			rbslog("logging in user " . $row['id'] . ": " . $row['name'] . " for ip address " . $_SERVER['REMOTE_ADDR'], 1);
			$_SESSION['user_id'] = $row['id'];
			$_SESSION['user_name'] = $row['name'];
			$_SESSION['user_email'] = $row['email'];
			$_SESSION['user_phone'] = $row['phone'];
			$_SESSION['production'] = $production;
			return $row['id'];
		} else
			return -1; // Password is invalid
	} else {
		// Lets see if the user's registered for other productions
		$q_anyproduction = "select * from user where email = '$email'";
		$r_anyproduction = mysql_query($q_anyproduction);
		if(!$r_anyproduction || mysql_num_rows($r_anyproduction) == 0)
			return -3;
		while($row = mysql_fetch_array($r_anyproduction, MYSQL_ASSOC)) {
			$passwordhash = md5($row['salt'] . $pass);
			if($passwordhash === $row['password']) {
				$newid = create_user($link, $production, $email, $pass, $row['name'], $row['phone']);
				if(isint($newid) && $newid >= 0) {
					$_SESSION['user_id'] = $newid;
					$_SESSION['user_name'] = $row['name'];
					$_SESSION['user_email'] = $row['email'];
					$_SESSION['user_phone'] = $row['phone'];
					$_SESSION['production'] = $production;
					rbslog("Converted an old account from " . $row['id'] . ", logging in user " . $newid . ": " . $row['name'] . " for ip address " . $_SERVER['REMOTE_ADDR'], 1);
					return $newid;
				}
				return -4;
			}
		}
		return -2;
	}
}

function get_prodadmin_shows($link, $userid) {
	$userid = (int)$userid;
	$sql = "select * from prodadmin where user = $userid";
	return sql_get_array($link, $sql);
}
function admin_pass($link, $id) {
    $sql = "select password from admin where id = $id";
    $results = mysql_query($sql, $link);

	if($results && mysql_num_rows($results) >= 1) {
		// Lets test the password
		$row = mysql_fetch_array($results, MYSQL_ASSOC);
        return $row['password'];
    } else {
        return false;
    }
}

/**
 * This function logs an admin user into the application.
 */
function admin_login($link, $email, $pass) {
	global $regex_email;
	// Sanitise variables
	$email = strtoupper($email);
	if(preg_match($regex_email, $email) !== 1)
		return null;

	// Check if user is superadmin
	$sql = "select * from admin where email = '$email'";
	$results = mysql_query($sql, $link);

	if($results && mysql_num_rows($results) >= 1) {
		// Lets test the password
		$row = mysql_fetch_array($results, MYSQL_ASSOC);

		$passwordhash = md5($row['salt'] . $pass);
		if($passwordhash === $row['password']) { // Log the user in!
			$_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_pass'] = $row['password'];
			$_SESSION['admin_name'] = $row['name'];
			$_SESSION['admin_email'] = $row['email'];
			$_SESSION['admin_phone'] = $row['phone'];
			$_SESSION['admin_superadmin'] = $row['superadmin'];
			if($row['superadmin'] == 0) {
				// If the admin is not a super admin, we need to grab a list of productions that the admin is admin for.
				$sql = "select production from prodadmin where admin = " . $row['id'];
				$prodadminresults = mysql_query($sql, $link);
				$prodlist = array();
				if($results) {
					while($prodadmin_row = mysql_fetch_array($prodadminresults, MYSQL_ASSOC)) {
						$prodlist[] = $prodadmin_row['production'];
					}
					$_SESSION['admin_prodlist'] = $prodlist;
				}
			}

			rbslog("logging in admin " . $row['id'] . ": " . $row['name'] . " for ip address " . $_SERVER['REMOTE_ADDR'], 1);
			return $row['id'];
		} else
			return -1; // Password is invalid
	}
	return null;
}

/**
 * Check to see if an admin user has access to a specific production
 */
function admin_has_access($prodid) {
	include_once('adminauth.php');
	if($_SESSION['admin_superadmin'] == 1 || in_array($prodid, $_SESSION['admin_prodlist']))
		return true;
	return false;
}

/**
 * Get a user by ID
 */
function get_user($link, $userid) {
	$userid = (int)$userid;
	$sql = "SELECT * ";
	$sql .= "FROM user ";
	$sql .= "WHERE user.id = '" . mysql_real_escape_string($userid) . "'";

	$res = mysql_query($sql, $link) or die("Unable to get the user");

	// now return the rows
	return mysql_fetch_assoc($res);
}


?>
