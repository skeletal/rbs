<?
/**
 * The login page.
 */
include_once('includes/utilities.php');
include_once('includes/prodmanagement.php');

$link = db_connect();

?>

<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]-->
<!--[if IE 7]>   <html class="no-js lt-ie9 lt-ie8"><![endif]-->
<!--[if IE 8]>   <html class="no-js lt-ie9"><![endif]-->
<!--[if gt IE 8]><!-->
<html lang="en" class="no-js">
  <!--<![endif]-->
 <head>
    <title>RBS Admin></title>
    <?php include ('includes/groundwork-header.php') ?>  
 </head>
<body>

<div class="container">
      <article class="row">
        <section class="padded">
			<h1>RBS Admin Login Page</h1>
			<h2>Login</h2>
			<form method="post" action="admin_logintest.php">
			<p>Email Address or Username: <input type="text" name="email"></p>
			<p>Password: <input type="password" name="pass"></p>
			<input type="submit">
			</form>
 		</section>
      </article>
    </div>

<?php include('includes/page-footer.php') ?>

</body>
</html>
