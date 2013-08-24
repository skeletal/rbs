<?
/**
 * Logs the user in then redirects them to the payment summary page.
 */

include_once('includes/utilities.php');
include_once('includes/usermanagement.php');
include_once('includes/frames/prodtheme.php');
include_once('includes/prodmanagement.php');

$link = db_connect();
include_once('includes/session.php');

if(!isset($_REQUEST['production']) || !production_exists($link, $_REQUEST['production'])) { // The page has been accessed directly.
	db_close($link);
	die('production does not exist');
}

$htmlheaders='<link rel="stylesheet" type="text/css" href="css/login.css" />';
$prodid = (int)$_REQUEST['production'];
$production = get_production($link, $prodid);

if(isset($_GET['reset'])){
    $q_resetuser = "select * from user where CAST(MD5(CONCAT(production, email, 'passwordreset')) as char) = '".$_GET['reset']."'";
    $r_resetuser = mysql_query($q_resetuser, $link);
    if (mysql_num_rows($r_resetuser) == 1){
        // Get the user info
        $user = mysql_fetch_array($r_resetuser, MYSQL_ASSOC);
        // Generate a reset token for the user. 
        // This is the token sent to the user's email which they provide to the
        // system in order to reset their password.
        $reset_token = md5($user['id'] . $user['email'] . $user['password'] . "passwordtoken");
        $link = "logintest.php?production=$prodid&amp;uid=".$user['id']."&amp;resettoken=$reset_token#main";
        // Create the multipart email
        $to = $user['email'];
        $subject = "Password Reset for ".$production['name'];

        $boundary = uniqid('np');
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: CSE Revue Webmin Head <webmin.head@cserevue.org.au> \r\n";
        $headers .= "To: ".$user['name']." <".$user['email']."> \r\n";
        $headers .= "Content-Type: multipart/alternative;boundary=$boundary\r\n";

        $message = "Hello,\nYou have requested a password reset for ".$user['email'].". Please follow the link below in order to reset your password:\n\n".$production['bookingslocation']."/$link\n\nIf there are any issues, please feel free to respond to this email or contact webmin.head@cserevue.org.au.\n\nThanks,\nCSE Revue Webmin Head";
        $message .= "\r\n\r\n--$boundary\r\n";
        $message .= "Content-type: text/html;charset=utf-8\r\n\r\n";
        $message .= "Hello,<br/>You have requested a password reset for ".$user['email'].". Please follow the link below in order to reset your password:<br/><br/><a href='".$production['bookingslocation']."/$link'>".$production['bookingslocation']."/$link</a><br/><br/>If there are any issues, please feel free to respond to this email, or contact <a href='mailto:webmin.head@cserevue.org.au'>webmin.head@cserevue.org.au</a>.<br/><br/>Thanks,<br/>CSE Revue Webmin Head";

        // Send out the email.
        mail($user['email'], $subject, $message, $headers);

        // Print out the confirmation message.
        print_prod_header($link, $production, $htmlheaders);

        echo "<div id='loginform'><div id='login'><h1>Password Reset</h1><strong>A password reset confirmation link has been sent to ".$user['email'].". Please see the email for more information.</strong></div></div>";

        print_prod_footer($link, $production);

        // Close the connection
        die();

    } else {
        die("An unknown error occurred when attempting to reset your password. Please contact an administrator or <a href='mailto:webmin.head@cserevue.org.au'>webmin.head@cserevue.org.au</a>");
    }

} else if (isset($_REQUEST['resettoken']) && isset($_REQUEST['uid'])){
    $q_resetuser = "select * from user where CAST(MD5(CONCAT(id, email, password, 'passwordtoken')) as char) = '".$_REQUEST['resettoken']."'";
    $r_resetuser = mysql_query($q_resetuser, $link);
    if (mysql_num_rows($r_resetuser) == 1){
        // Get the user info
        $user = mysql_fetch_array($r_resetuser, MYSQL_ASSOC);

        if (isset($_POST['uid']) && $_POST['uid'] == $user['id'] && isset($_POST['pass']) && isset($_POST['passcheck']) && $_POST['pass'] == $_POST['passcheck']){
            $salt = rand_str();
            $pass = $_POST['pass'];
            $passwordhash = md5($salt . $pass);
            $q_reset = "update user set salt='$salt', password='$passwordhash' where id=".$user['id'];
            if (mysql_query($q_reset)){
                print_prod_header($link, $production, $htmlheaders);

                echo "<div id='loginform'><div id='login'><h1>Password Reset Successful</h1><strong>Your password has been successfully reset. You may now login by <a href='login.php?production=$prodid#main'>clicking here</a>.</strong></div></div>";

                print_prod_footer($link, $production);
                die();

            } else {
                die ("Couldn't reset password. Please contact an administrator or webmin.head@cserevue.org.au");
            }

        } else {

            print_prod_header($link, $production, $htmlheaders);

?>

<div id='loginform'>

<div id='login'>
<h1>Password Reset</h1>
<p>Please enter your new password below.</p>
<p class="error"><?=$message?></p>
<form method="post" action="logintest.php?uid=<?=$user['id']?>&amp;$resettoken=<?=$_GET['resettoken']?>#main">
<div class='loginfield'><div class='loginlabel'>Password:</div><input type="password" autofocus="autofocus" name="pass"></div>
<div class='loginfield'><div class='loginlabel'>Password again:</div><input type="password" name="passcheck"></div>
<input type="hidden" name="production" value="<?=$prodid?>">
<input type="hidden" name="uid" value="<?=$_REQUEST['uid']?>">
<input type="hidden" name="resettoken" value="<?=$_REQUEST['resettoken']?>">
<div class="loginsubmit"><input type="submit" value="Reset password"></div>
</form>

</div>
</div>

<?php
            print_prod_footer($link, $production);
            die();
        }

    } else {
        die ("Invalid password reset token. Please contact an administrator or webmin.head@cserevue.org.au");
    }
}


if(isset($_GET['timeout'])) {
	$message = "Your session has timed out.  Please login again.";
} else if(!isset($_POST['email']) || $_POST['email'] == '')
	$message = "You need to enter an email address to log in.";
else if(!isset($_POST['pass']) || $_POST['pass'] == '')
	$message = "Please enter in a password.";
else {
	$user = user_login($link, $prodid, $_POST['email'], $_POST['pass']); // Login and set the session variables
	switch($user) {
	case -1:
		$message = "The password you have entered is incorrect, please try again.";
        // Offer a password reset link as well
        // First, production and email, so we can use it to identify the user 
        // without allowing people to easily reset anyone's password.
        $uid = strtoupper(md5($prodid . strtoupper($_POST['email']) . "passwordreset"));
        $message .= "<br/><a href='logintest.php?production=$prodid&amp;reset=$uid#main'>Click here to reset the password for ".$_POST['email'].".</a>";
		break;
	case -2:
		$message = "The password you have entered is incorrect, however this email has not yet been registered for this show.  Please either press Back and reregister for this show, or reenter your password below.";
		break;
	case -3:
		$message = "The email you have entered cannot be found.  Please either reenter or press Back to go back to the login page and register.";
		break;
	case -4:
		$message = "An unknown error has occurred migrating the old user account across to this show.  Please either try again or reregister for this show through the login page.";
		break;
	case -6:
		$message = "A database error has occurred.  Please try again.";
		break;
	default:
		if(isset($_SESSION['user_id'])){
			header('Location: paymentsummary.php#main');
		} else {
			$message = "An error occurred ($user).  Please try again.";
		}
	}
}

print_prod_header($link, $production, $htmlheaders);
?>

<div id='loginform'>

<div id='login'>
<h1>Login</h1>
<p class="error"><?=$message?></p>
<form method="post" action="logintest.php#main">
<div class='loginfield'><div class='loginlabel'>Email Address:</div><input type="text" autofocus="autofocus" name="email"<? if(isset($user) && $user != -3) echo(" value='" . $_POST['email'] . "'")?>></div>
<div class='loginfield'><div class='loginlabel'>Password:</div><input type="password" name="pass"></div>
<input type="hidden" name="production" value="<?=$prodid?>">
<div class="loginsubmit"><input type="submit" value="Login"></div>
</form>

</div>
</div>
<?
print_prod_footer($link, $production);
?>
