<?
include_once('includes/utilities.php');
$link = db_connect();
include_once('includes/adminauth.php');
include_once('includes/prodmanagement.php');
include_once('includes/bookingmanagement.php');

if(isset($_GET['production']) && production_exists($link, $_GET['production'])) {
	check_access_to_production($_GET['production']);
    $_SESSION['admin_production'] = $_GET['production'];
}

if(!isset($_SESSION['admin_production'])) {
	exit;
}


$production = get_production($link, $_SESSION['admin_production']);

$tickettotals = get_ticket_totals($link, $_SESSION['admin_production']);
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
<?php include('includes/page-header.php') ?>
<div class="container">
      <article class="row">
        <section class="padded">
<h1>Production Info for <?=$production['name']?></h1>
<p><a href="admin_booking.php">Administration Booking Screen</a></p>
<p><a href="admin_bookinglist.php">Booking List</a></p>
<p><a href="admin_editproduction.php">Edit Production Details</a></p>
<p><a href="logout.php">Logout</a></p>
<h2>Ticket Totals:</h2>
<?
foreach ( $tickettotals as $tt ) {
?>
<h2><?=$tt['title']?></h2>
<ul>
<li>Booked seats: <?=$tt['bookedseats']?></li>
<li>Paid seats: <?=$tt['paidseats']?></li>
<li>Confirmed seats: <?=$tt['confirmedseats']?></li>
<li>Payment Pending seats: <?=$tt['ppseats']?></li>
<li>VIP seats: <?=$tt['vipseats']?></li>
</ul></p>
<p><strong>Total confirmed or paid:</strong> <?=$tt['paidseats'] + $tt['confirmedseats'] + $tt['ppseats'] + $tt['vipseats']?></p>
<?php
    if (isset($tt['confirmed']) and count($tt['confirmed']) > 0){
        echo "<strong>Total confirmed/paid by price class:</strong><ul>";
        foreach ($tt['confirmed'] as $price){
            echo "<li class='margin-left: 5em;'>".$price['name'].": ".$price['count']."</li>";
        }
        echo "</ul>";
    }
}
?>

        </section>
      </article>
    </div>

<?php include('includes/page-footer.php') ?>

</body>
</html>

