<?
/**
 * TODO: make sure only proper admins have access to each production specific page
 * TODO: Cleanup the printing checkboxes etc.  The code can be a lot cleaner
 */

include_once('includes/utilities.php');
$link = db_connect();
include_once('includes/adminauth.php');

include_once('includes/perfmanagement.php');
include_once('includes/prodmanagement.php');
include_once('includes/bookingmanagement.php');
include_once('includes/frames/priceselection.php');

if(isset($_GET['production']) && production_exists($link, $_GET['production'])){
	$production = get_production($link, $_GET['production']);
	$performances = get_performances($link, $production['id']);
} else {
	echo("production does not exist");
	exit;
}
/*

if(isset($_POST['restrictstatus'])){
	$rs = $_POST['restrictstatus'];
} else {
	$rs = array();
}
if(isset($_POST['restrictperf'])){
	$rp = $_POST['restrictperf'];
} else {
	$rp = array();
}
?>

<html>
<body>
<p>
	<form>

		<input type="checkbox" name="restrictstatus[1]"<?if(isset($rs[1])) echo( "CHECKED")?>>Booked	
		<input type="checkbox" name="restrictstatus[3]"<?if(isset($rs[3])) echo( "CHECKED")?>>Confirmed
		<input type="checkbox" name="restrictstatus[4]"<?if(isset($rs[4])) echo( "CHECKED")?>>Paid
		<input type="checkbox" name="restrictstatus[5]"<?if(isset($rs[5])) echo( "CHECKED")?>>Paid Sales Desk
		<input type="checkbox" name="restrictstatus[6]"<?if(isset($rs[6])) echo( "CHECKED")?>>Paid DD
		<input type="checkbox" name="restrictstatus[7]"<?if(isset($rs[7])) echo( "CHECKED")?>>Paid Paypal

		<input type="checkbox" name="restrictstatus[8]"<?if(isset($rs[8])) echo( "CHECKED")?>>Payment Pending
		<input type="checkbox" name="restrictstatus[9]"<?if(isset($rs[9])) echo( "CHECKED")?>>Unavailable
		<input type="checkbox" name="restrictstatus[10]"<?if(isset($rs[10])) echo( "CHECKED")?>>VIP
	
</p>
<p>
	
		<?
		foreach ($performances as $performance) {
			echo("<p>");
			echo("<input type='checkbox' name=restrictperf[" . $performance['id']. "]");
			if(isset ($rs[$performance['id']])){ 
				echo ( "CHECKED"); 
			}
			echo(">" . prettydate($performance['tsdate']));
			echo("</p>");
		}
		?>
	</form>
</p>

<?
 // Printing


// Get the bookings

$restrictions = array();
$restrictions['performance'] = $_POST['restrictperf'];
$restrictions['status'] = $_POST['restrictstatus'];
*/

$restrictions = array();
$status = array();
$status[4] = "on";
$status[5] = "on";
$status[6] = "on";
$status[7] = "on";
$restrictions['status'] = $status;


$bookings = get_all_bookings($link, $production['id'], $sortby, $order, $restrictions);

$nseats = 0;

?>
<html>
<head>
	<script type="text/JavaScript">
		function timedRefresh(timeoutPeriod){
			setTimeout("location.reload(true);",timeoutPeriod);
		}
	</script>
</head>
<body onload="JavaScript:timedRefresh(60000);">
<table id="bookinglist">
<?
foreach($bookings as $booking) {
	$nseats += count($booking['seats']);
}
echo("<p style='text-align:center;margin-top: 0px;font-size: 400px;'> $nseats</p>");
?>
</body>
</html>
