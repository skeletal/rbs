<?

/**
 * Register the new user and automatically redirect them to paymentsummary.php upon success or back to login.php on failure
 */

include_once('includes/utilities.php');
$link = db_connect();

include_once('includes/session.php');

include_once('includes/usermanagement.php');
include_once('includes/frames/prodtheme.php');


if(!isset($_POST['production']) || !production_exists($link, $_POST['production'])) { // The page has been accessed directly.
	db_close($link);
	die('production does not exist');
}

if(!isset($_POST['email']) || $_POST['email'] == '')
	die('You need to give us an email address.  Please press back and enter in a valid email');
if(!isset($_POST['pass']) || $_POST['pass'] == '')
	die('Please give a password.  Press back to register.');
if(!isset($_POST['fname']) || $_POST['fname'] == '')
	die('Please give us a valid first name.  Press back to register.');
if(!isset($_POST['lname']) || $_POST['lname'] == '')
   die('Please give us a valid last name.  Press back to register.');
if(!isset($_POST['phone']) || $_POST['phone'] == '')
	die("Please give us a phone number so that we can contact you if we need to (if you're running late or there's a ticketing issue.)");

if ($_POST['pass'] != $_POST['pass_repeat']) {
	die('The passwords given were not equal.  Please press Back and try again.');
}

$reason = '';

$user = create_user($link, $_POST['production'], $_POST['email'], $_POST['pass'], $_POST['fname']." ".$_POST['lname'], $_POST['phone'], false, $reason);

if($user < 0)
{
	switch($user) {
	case -1:
		die('The email given is invalid.  Please press Back and reenter a valid email.');
	case -3:
		die('The name given is invalid.  Please press Back and enter a name without hyphens or apostrophes.');
	case -4:
		die('The phone number given is invalid.  Please press Back and reenter the phone number.');
	case -5:
		die('Email address has already been registered for this production.  Please try another email address or contact the production address.');
	default:
		die('Error registering user.  Please try again.');
	}
} else {
	user_login($link, $_POST['production'], $_POST['email'], $_POST['pass']); // Login and set the session variables.
	header('Location: paymentsummary.php');
	exit;
}
?>
