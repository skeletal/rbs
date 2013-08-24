<?
/**
 * The login page.
 */

// TODO: Need to make the webmaster and producers emails be generic 

include_once('includes/utilities.php');
include_once('includes/prodmanagement.php');
include_once('includes/frames/prodtheme.php');

if(isset($_GET['p']))
    $prodid = (int)$_GET['p'];
else
    $prodid = (int)$_GET['production'];

$link = db_connect();
if(!production_exists($link, $prodid))
	die('Production does not exist');

$production = get_production($link, $prodid);
include_once('includes/theatres/' . $production['theatre'] . '.inc');

$htmlheaders='<link rel="stylesheet" type="text/css" href="css/login.css" />';
print_prod_header($link, $production, $htmlheaders);
?>

<div id="loginform">
<div id="loginblurb"><p>Having trouble with the ticketing system?
<? if($production['faqlocation'] == '') { ?>
Please <a href="mailto:tickets@cserevue.org.au">email the Sales Team</a> and we'll help as soon as we can!
<? } else { ?>
Please read the <a href="<?=$production['faqlocation']?>">FAQ</a> or <a href="mailto:tickets@cserevue.org.au">email the Sales Team</a> and we'll help as soon as we can!
</p><p>
Please note, <strong>group bookings (<?=($max_booked_seats+1)?> or more seats)</strong> are eligible for a group discount.<br/><em>Group bookings cannot be made online</em> &mdash; please see the <a href='<?=$production['faqlocation']?>#group_bookings'>FAQ</a> for more information or <a href='mailto:tickets@cserevue.org.au'>contact the Sales Team</a>.
</p>
<? } ?>
</div>
<br>

<div id="login">

<h1>Login</h1>
<form method="post" action="logintest.php#main">
<p class="loginblurb">If you have an existing booking please login below.</p>
<div class="loginfield"><div class="loginlabel">Email Address:</div><input type="text" name="email"></div>
<div class="loginfield"><div class="loginlabel">Password:</div><input type="password" name="pass"></div>
<input type="hidden" name="production" value="<?=$prodid?>">
<div class="loginsubmit"><input type="submit" value="Login"></div>
</form>
</div>
<div id="register">
<h1>Register</h1>
<p class="loginblurb">Please enter in your contact details.</p>
<form method="post" action="register.php#main">
<div class="loginfield"><div class="loginlabel">Email Address:</div><input type="text" name="email" autofocus="autofocus"></div>
<div class="loginfield"><div class="loginlabel">Password:</div><input type="password" name="pass"></div>
<div class="loginfield"><div class="loginlabel">Repeat Password:</div><input type="password" name="pass_repeat"></div>
<div class="loginfield"><div class="loginlabel">First Name:</div><input type="text" name="fname"></div>
<div class="loginfield"><div class="loginlabel">Last Name:</div><input type="text" name="lname"></div>
<div class="loginfield"><div class="loginlabel">Phone Number:</div><input type="text" name="phone"></div>
<input type="hidden" name="production" value="<?=$prodid?>">
<div class="loginsubmit"><input type="submit" value="Register"></div>
</form>
<div class="loginblurb disclaimer">Your contact details will not be used for any purposes other than the following:
<ul>
<li>To send you your booking details and notify you of any necessary changes to your booking.</li>
<li>To confirm your arrival in case you are running late.</li>
</ul>
Your details will not be used for distribution of spam or for marketing purposes and will never be shared with any external entities without your prior permission.</div>
</div>

</div>

<?
print_prod_footer($link, $production);
?>
