<?
/*
 * This is the booking page.  It is the central page to book tickets.
 *
 * Clicking the "Pay for your Tickets" icon will take the user back to the booking_summary.php page.
 */

include_once('includes/utilities.php');
$link = db_connect();
include_once('includes/adminauth.php');

// The rendering code for the theatre
include_once('includes/frames/render_theatre.php');

include_once('includes/perfmanagement.php');
include_once('includes/prodmanagement.php');
include_once('includes/frames/prodtheme.php');


if(isset($_GET['toperformance'])) {
	$toperformance = $_GET['toperformance'];
    check_access_to_performance($toperformance);
}

if(isset($_GET['tosegment']))
	$tosegment = $_GET['tosegment'];

$prodid = (int)$_SESSION['admin_production'];

$performances = get_performances($link, $prodid);
$production = get_production($link, $prodid);
include_once('includes/theatres/' . $production['theatre'] . '.inc');

// Open and close segments
if (isset($_POST['segment_perfid'])){
    if(isset($_POST['openSegment'])){
        echo open_segment($link, $_POST['segment_perfid'], $_POST['openSegment']);
    } else if (isset($_POST['closeSegment'])){
        echo close_segment($link, $_POST['segment_perfid'], $_POST['closeSegment']);
    }
}

?>


!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]-->
<!--[if IE 7]>   <html class="no-js lt-ie9 lt-ie8"><![endif]-->
<!--[if IE 8]>   <html class="no-js lt-ie9"><![endif]-->
<!--[if gt IE 8]><!-->
<html lang="en" class="no-js">
  <!--<![endif]-->
 <head>
    <title>RBS Admin - Bookings for <?=$production['name']?></title>
    <?php include ('includes/groundwork-header.php') ?>  
 </head>
<body>
<?php include('includes/page-header.php') ?>
<div class="container">
      <article class="row">
        <section class="padded">

<p><a href="admin_production.php">Back to production page</a></p>
<link rel="stylesheet" type="text/css" href="css/booking.css" />
<link rel="stylesheet" type="text/css" href="css/admin_booking.css" />

<script type="text/javascript">
var performances = [];
var segments = [];
var bookings = [];
var bookedseats = [];
var theatre_width = <?=$theatre_width?>;
var production = <?=$production['id']?>;
<?
/*
 * We need to define all the performances and segments in javascript so the navigation can work
 */

foreach($performances as $performance) {
	echo("performances['" . $performance['id'] . "'] = '" . prettydate($performance['tsdate']) . "';\n");
}

foreach($theatre as $segment) {
	echo("segments['" . $segment['id'] . "'] = '" . $segment['name'] . "';\n");
}

echo "window.onload = function() {\n";

if (isset($_POST['segment_perfid'])){
    $segment_perfid = (int)$_POST['segment_perfid'];
    echo "toPerformance(".$segment_perfid.");\n";
}

echo "}\n";

?>
</script>

<script type="text/javascript" src="js/global.js" ></script>
<script type="text/javascript" src="js/booking.js" ></script>
<script type="text/javascript" src="js/booking_admin.js" ></script>
</head>
<body onresize='widthToWindow()'>

<form id="seatform" action="admin_bookingsummary.php" method="post">
<span id="seatsubmit"></span>

<div id="topbuttons">
<div id="status"></div>

<div id="adminbuttons">
<div class="button" id='cancelbooking'><a href="admin_booking.php">Cancel booking</a></div>
<div class="button" id="savebooking"><a href="javascript: saveThisBooking()">Save This Booking</a></div>
<div id="startnewbooking" class="button"><a href="javascript: startNewBooking()">Start New Booking</a></div>
<div id="modifybooking" class="button"><a href="javascript: modifyBooking()">Modify Booking</a></div>
<div id="resetchanges" class="button"><a href="javascript: resetChanges()">Reset</a></div>
<div id="fulltheatre" class="button"><a href="javascript: toggleFullTheatre()" id="togglefulltheatre">Show Full Theatre</a></div>
</div>
<div id="navigation"></div>
<div id="targetseats">
<div id="targetseats_0" class="button targetseat" onClick="targetSeat(0);" style="background-color:#acf"><img src="images/free.gif"><br>Free</div>
<div id="targetseats_1" class="button targetseat" onClick="targetSeat(1);"><img src="images/booked.gif"><br>Booked</div>
<div id="targetseats_3" class="button targetseat" onClick="targetSeat(3);"><img src="images/red.gif"><br>Confirmed</div>
<div id="targetseats_4" class="button targetseat" onClick="targetSeat(4);"><img src="images/paid.gif"><br>Paid</div>
<div id="targetseats_5" class="button targetseat" onClick="targetSeat(5);"><img src="images/paid.gif"><br>Paid Sales</div>
<div id="targetseats_6" class="button targetseat" onClick="targetSeat(6);"><img src="images/paid_dd.gif"><br>Paid DD</div>
<div id="targetseats_7" class="button targetseat" onClick="targetSeat(7);"><img src="images/paid_paypal.gif"><br>Paid Paypal</div>
<div id="targetseats_8" class="button targetseat" onClick="targetSeat(8);"><img src="images/red.gif"><br>Payment Pending</div>
<div id="targetseats_9" class="button targetseat" onClick="targetSeat(9);"><img src="images/unavailable.gif"><br>Unavailable</div>
<div id="targetseats_10" class="button targetseat" onClick="targetSeat(10);"><img src="images/vip.gif"><br>VIP</div>
</div>
</div>

<input type="hidden" name="submitseats" value="true">
</form>

<div id="performances">
<?
	foreach($performances as $performance) {
		if($performance['title'] && $performance['title'] != '')
			echo("<a href='javascript:toPerformance(" . $performance['id'] . ")'>" . $performance['title'] . " (" . prettydate($performance['tsdate']) . ")</a><br>\n");
		else
			echo("<a href='javascript:toPerformance(" . $performance['id'] . ")'>" . prettydate($performance['tsdate']) . "</a><br>\n");
	}
?>
</div>

<?php
    foreach($performances as $performance){
        echo "<div id='segments_".$performance['id']."' style='display:none;'>";
        $closedsegments = get_closed_segments($link, $performance['id']);
        echo "<table border='1' class='segmentlist_admin' ><tr>";
        foreach($theatre as $segment) {
            echo("<th><a class='segmentlink_admin' href='javascript:toSegment(" . $segment['id'] . ")'>" . $segment['name'] . "</a></th>\n");
        }
        echo "</tr><tr>";
        foreach($theatre as $segment) {
            echo "<td><form method='post' class='closedsegmentform' action='admin_booking.php' ><input type='hidden' name='segment_perfid' value='".$performance['id']."'/>";
            if (in_array($segment['id'], $closedsegments)){
                echo "<input type='hidden' name='openSegment' value='".$segment['id']."'/><input type='button' class='closed on' disabled='disabled'  value='Closed'/><input type='submit' class='opened off'  value='Open'/>";
            } else {
                echo "<input type='hidden' name='closeSegment' value='".$segment['id']."'/><input type='submit' class='closed off'  value='Closed'/><input type='button' class='opened on' disabled='disabled'  value='Open'/>";
            }
            echo "</form></td>";
        }
        echo "</tr></table></div>";
    }
?>

<div id="loading">Loading</div>

<div id="theatre_render">
<a name='target' id='target'>target</a>
<div id="theatre_zoom">
<?

	foreach($theatre as $segment) {
		print_theatre_segment($segment, 'segment' . $segment['id'], $theatre_width, $theatre);
	}

?>
</div>
</div>

<?
// The booking information
?>
<div id="bookinginfo">
<h2>Booking Information</h2>
<p id="bookingid">Booking ID: </p>
<p id="bookingemail">Booker's Email: </p>
<p id="bookingphone">Booker's Phone Number: </p>
<p id="bookingusername">Booker's Name: </p>
<p id="bookingname">Name: </p>
<p id="bookingdesc">Description: </p>
<p id="bookingamountpaid">Amount Paid: </p>
<p id="bookingdeadline">Deadline: </p>
<p id="bookingpickedup">Has it been picked up? </p>
</div>

<script type="text/javascript">
document.getElementById('loading').style.display = "none";
document.getElementById('performances').style.display = "block";

widthToWindow();
<?
if(isset($toperformance)) {
	echo("toPerformance($toperformance);");
	if(isset($tosegment))
		echo("toSegment($tosegment);");
	if(isset($fulltheatre))
		echo("toggleFullTheatre()");
}
?>

</script>


        </section>
      </article>
    </div>

<?php include('includes/page-footer.php') ?>

</body>
</html>

