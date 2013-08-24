<?

include_once('utilities.php');
include_once('session.php');

if(!isset($_SESSION['user_id'])) {
	rbslog('user session timed out for user with ip address ' . $_SERVER['REMOTE_ADDR'], 2);
	$sessvars = '';
	foreach($_SESSION as $key => $sessvar) {
		$sessvars .= "$key => $sessvar, ";
	}
	header('Location: index.php?timeout=true');
	exit;
}

?>
