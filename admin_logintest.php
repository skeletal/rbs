<?
/**
 * Logs the user in then redirects them to the payment summary page.
 */

require_once('includes/utilities.php');
$link = db_connect();
require_once('includes/session.php');
require_once('includes/usermanagement.php');


if(
	!isset($_POST['email']) ||
	!isset($_POST['pass'])
  ) {
	db_close($link);
	header('Location: index.php');
	exit;
}

$admin = admin_login($link, $_POST['email'], $_POST['pass']); // Login and set the session variables

if($admin == null || $admin < 0) {
    $message = "Invalid username or password";
    $message = $_POST['email'] . md5('abc'.$_POST['pass']);
} else {
	header('Location: admin_prodlist.php');
	exit;
}
?>
<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]-->
<!--[if IE 7]>   <html class="no-js lt-ie9 lt-ie8"><![endif]-->
<!--[if IE 8]>   <html class="no-js lt-ie9"><![endif]-->
<!--[if gt IE 8]><!-->
<html lang="en" class="no-js">
  <!--<![endif]-->
 <head>
    <title>RBS Admin - Production Info for <?=$production['name']?></title>
    <?php include ('includes/groundwork-header.php') ?>  
 </head>
<body>
<div class="container">
      <article class="row">
        <section class="padded">

<h2>Login</h2>

<p class="error"><?=$message?></p>
<form method="post" action="admin_logintest.php">
<p>Email Address: <input type="text" name="email"></p>
<p>Password: <input type="password" name="pass"></p>
<input type="submit">
</form>

        </section>
      </article>
    </div>

<?php include('includes/page-footer.php') ?>

</body>
</html>
