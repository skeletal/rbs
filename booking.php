<?
/*
 * This is the booking page.  It is the central page to book tickets.
 */

include_once('includes/utilities.php');
$link = db_connect();
include_once('includes/userauth.php');



include_once('includes/usermanagement.php');

// The rendering code for the theatre
include_once('includes/frames/render_theatre.php');

include_once('includes/perfmanagement.php');
include_once('includes/prodmanagement.php');
include_once('includes/frames/prodtheme.php');
include_once('includes/bookingmanagement.php');

$production = get_production($link, $_SESSION['production']);

include_once('includes/theatres/' . $production['theatre'] . '.inc');

$performances = get_performances($link, $_SESSION['production']);

$perfseats = get_seats_selected($link, $_SESSION['user_id']);
$bookedseats = get_seats_selected($link, $_SESSION['user_id'], 1, true);

$htmlheaders = <<<HEADER
<link rel="stylesheet" type="text/css" href="css/booking.css" />
<link rel="stylesheet" type="text/css" href="css/booking_user.css" />

<script type="text/javascript">
var performances = [];
var segments = [];
var perfseats = [];
var bookedseats = [];
var perfcs = [];
var segseats = [];
HEADER;
$htmlheaders .= "var theatre_width = $theatre_width;\n";

/*
 * We need to define all the performances and segments in javascript so the navigation can work
 */

foreach($performances as $performance) {
	if($performance['isclosed'] == 1)
		continue;
	$htmlheaders .= "performances[" . $performance['id'] . "] = '" . prettydate($performance['tsdate']) . "';\n";
	$closedsegments = get_closed_segments($link, $performance['id']);
	$htmlheaders .= "perfcs[" . $performance['id'] . "] = [];\n";
	foreach($closedsegments as $cs) {
		$htmlheaders .= "perfcs[" . $performance['id'] . "][" . $cs . "] = true;\n";
	}

	if(isset($perfseats[$performance['id']]))
		$htmlheaders .= "perfseats[" . $performance['id'] . "] = " . $perfseats[$performance['id']] . ";\n";
	else
		$htmlheaders .= "perfseats[" . $performance['id'] . "] = 0;\n";

	if(isset($bookedseats[$performance['id']]))
		$htmlheaders .= "bookedseats[" . $performance['id'] . "] = " . $bookedseats[$performance['id']] . ";\n";
	else
		$htmlheaders .= "bookedseats[" . $performance['id'] . "] = 0;\n";
}

foreach($theatre as $segment) {
	$htmlheaders .= "segments['" . $segment['id'] . "'] = '" . $segment['name'] . "';\n";
	$htmlheaders .= "segseats['" . $segment['id'] . "'] = 0;\n";
}

$htmlheaders .= "var max_booked_seats = ".$max_booked_seats.";\n";

$htmlheaders .= <<<HEADER
</script>

<script type="text/javascript" src="js/booking.js" ></script>
<script type="text/javascript" src="js/booking_user.js" ></script>
HEADER;

$bodyattrs = " onresize='widthToWindow()'";

print_prod_header($link, $production, $htmlheaders, $bodyattrs);

?>

<form id="seatform" action="bookingsummary.php#main" method="post">
<span id="seatsubmit"></span>
<input type="hidden" name="submitseats" value="true">
</form>

<div id="performances">
	<h1>When would you like to go?</h1>
	<div id="perfdates">
<?
	foreach($performances as $performance) {
		if($performance['isclosed'] == 1) {
			echo("<div class='perfdateclosed bigbutton'>" . prettydate($performance['tsdate']) . "\n");
			echo("<div class='closedmessage'>" . $performance['closedmessage'] . "</div>\n");
		} else {
			echo("<div class='perfdate bigbutton' onClick='javascript:toPerformance(" . $performance['id'] . ")'>" . prettydate($performance['tsdate']) . "\n");
		}

		echo("<div id='perfseats" . $performance['id'] . "' class='perfseats'>");
		if(!isset($perfseats[$performance['id']]) || $perfseats[$performance['id']] == 0)
			echo('');
		else if($perfseats[$performance['id']] == 1)
			echo("1 seat booked");
		else
			echo($perfseats[$performance['id']] . " seats booked");
		echo("</div></div>");
	}
?>
	</div>
</div>

<div id="segments">
	<h1>Where would you like to sit?</h1>
	<div id="segmentlinks">
<?
	foreach($theatre as $segment) {
		echo("<div id='segmentlink" . $segment['id'] . "' class='segmentlink bigbutton' onClick='javascript:toSegment(" . $segment['id'] . ")'>" . $segment['name'] . "</div>\n");
		echo("<div id='segseats" . $segment['id'] . "'></div>");
	}
?>
	<div class='segmentstage bigbutton'>Stage</div>
	</div>
</div>

<div id="loading">Loading theatre booking system, please wait...</div>

<div id="theatre_render">

<a name='target' id='target'></a>
<h1>Select Your Seats</h1>

<div id="buttonpanel">
<div id="anotherday" onClick="javascript: toShow()" class="bigbutton">See Another Day</div>
<div id="payfortickets" onClick="javascript: payForTickets()" class="bigbutton continue">Save Booking</div>
</div>

<div id="legend">
<div id="legendborder">
<h2>Legend</h2>
<div class="legendentry"><div class="legendimage"><img src="images/free.gif"></div><div class="legendseat">Free</div></div>
<div class="legendentry"><div class="legendimage"><img src="images/booked.gif"></div><div class="legendseat">Booked</div></div>
<div class="legendentry"><div class="legendimage"><img src="images/confirmed.gif"></div><div class="legendseat">Confirmed</div></div>
<div class="legendentry"><div class="legendimage"><img src="images/paid.gif"></div><div class="legendseat">Paid For</div></div>
<div class="legendentry"><div class="legendimage"><img src="images/unavailable.gif"></div><div class="legendseat">Unavailable</div></div>
</div>
</div>

<a name='target' id='target'></a>
<div id="theatre_zoom">
<?

	foreach($theatre as $segment) {
		print_theatre_segment($segment, 'segment' . $segment['id'], $theatre_width, $theatre);
	}

?>
</div>
</div>

<script type="text/javascript">
document.getElementById('loading').style.display = "none";
document.getElementById('performances').style.display = "block";

widthToWindow();
</script>

<?

print_prod_footer($link, $production);
?>
