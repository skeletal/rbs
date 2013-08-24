<?
// Allows the user to edit production details and add a new production
// TODO: filtering to prevent XSS

include_once('includes/utilities.php');
$link = db_connect();
include_once('includes/adminauth.php');
include_once('includes/prodmanagement.php');
include_once('includes/theatremanagement.php');

$theatres = theatre_list();

if(isset($_SESSION['admin_production'])) {
	$prodid = (int)$_SESSION['admin_production'];
} else {
	$prodid = -1;
}

if(isset($_POST['name'])) {
	// Form has been submitted, lets save
	if($prodid < 0) {
		$ret = add_production($link, $_POST);
		if(is_int($ret)) {
			// Adding the production was successful
			$prodid = $ret;
			$production = get_production($link, $prodid);
			$message = "The production has been successfully added.";
		} else {
			// The function returned an error
			$message = $ret;
			$production = $_POST;
			$prodid = -2;
		}
	} else {
		$message = modify_production($link, $prodid, $_POST);
		if(is_int($message)) {
			$message = "Update of production successful";
			$production = get_production($link, $prodid);
		} else {
			$production = $_POST;
		}
	}
} else if($prodid != -1)
	$production = get_production($link, $prodid);
?>

<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]-->
<!--[if IE 7]>   <html class="no-js lt-ie9 lt-ie8"><![endif]-->
<!--[if IE 8]>   <html class="no-js lt-ie9"><![endif]-->
<!--[if gt IE 8]><!-->
<html lang="en" class="no-js">
  <!--<![endif]-->
 <head>
  	<title>RBS Admin - Add New Production</title>
  	<?php include ('includes/groundwork-header.php') ?>  
 </head>
<body>

<?php include('includes/page-header.php') ?>


<div class="container">
      <article class="row">
        <section class="padded">
<h1>Production Details</h1>

<?if($message) {?>
	<em><?=$message?></em>
<?}?>

<?
if($prodid >= 0) {
?>
<p><a href="admin_production.php?production=<?=$prodid?>">Production Page</a></p>
<?
} else {
?>
<p><a href="admin_prodlist.php">Back to Production List</a></p>
<?
}
?>

<form method="post" action="admin_editproduction.php">

<table>
<tr>
	<td>Production ID:</td>
	<td><?if($prodid >= 0) echo($prodid)?></td>
</tr>
<tr>
	<td>Production Name:</td>
	<td><input type="text" name="name" value="<?if($prodid != -1) echo(htmlspecialchars($production['name']))?>"></td>
</tr>
<tr>
	<td>Show Header:</td>
	<td><textarea name="header"><?if($prodid != -1) echo(htmlspecialchars($production['header']))?></textarea></td>
</tr>
<tr>
	<td>Show Footer:</td>
	<td><textarea name="footer"><?if($prodid != -1) echo(htmlspecialchars($production['footer']))?></textarea></td>
</tr>
<tr>
	<td>CSS File Location:</td>
	<td><input type="text" name="css" value="<?if($prodid != -1) echo(htmlspecialchars($production['css']))?>"></td>
</tr>
<tr>
	<td>Location:</td>
	<td><select name="theatre">
<?
foreach($theatres as $theatre) {
	echo("		<option value='$theatre'");
	if($theatre == $production['theatre'])
		echo(" SELECTED");
	echo(">$theatre</option>");
}
?>
	</select>
	</td>
</tr>
<tr>
	<td>Is the show closed?:</td>
	<td><input type="checkbox" name="isclosed" <?if($prodid != -1 && $production['isclosed'] == 1) echo("CHECKED")?>></td>
</tr>
<tr>
	<td>Closing Date:</td>
	<td><input type="text" name="closedate" value="<?if($prodid != -1) echo(htmlspecialchars($production['closedate']))?>"></td>
</tr>
<tr>
	<td>Minimum Group Ticket Size:</td>
	<td><input type="text" name="groupticketsamount" value="<?if($prodid != -1) echo((int)$production['groupticketsamount'])?>"></td>
</tr>
<tr>
	<td>Group Tickets Message:</td>
	<td><textarea name="groupticketsmessage"><?if($prodid != -1) echo(htmlspecialchars($production['groupticketsmessage']))?></textarea></td>
</tr>
<tr>
	<td>Show Website Location:</td>
	<td><input type="text" name="sitelocation" value="<?if($prodid != -1) echo(htmlspecialchars($production['sitelocation']))?>"></td>
</tr>
<tr>
	<td>Show FAQ Location:</td>
	<td><input type="text" name="faqlocation" value="<?if($prodid != -1) echo(htmlspecialchars($production['faqlocation']))?>"></td>
</tr>
<tr>
	<td>Accept Sales Booth Reservations:</td>
	<td><input type="checkbox" name="acceptsales" <?if($prodid != -1 && $production['acceptsales'] == 1) echo("CHECKED")?>></td>
</tr>
<tr>
	<td>Sales Desk Information:</td>
	<td><textarea name="salesinfo"><?if($prodid != -1) echo(htmlspecialchars($production['salesinfo']))?></textarea></td>
</tr>
<tr>
	<td>Accept Direct Debit:</td>
	<td><input type="checkbox" name="acceptdd" <?if($prodid != -1 && $production['acceptdd'] == 1) echo("CHECKED")?>></td>
</tr>
<tr>
	<td>Direct Debit Information:</td>
	<td><textarea name="ddinfo"><?if($prodid != -1) echo(htmlspecialchars($production['ddinfo']))?></textarea></td>
</tr>
<tr>
	<td>Accept Paypal:</td>
	<td><input type="checkbox" name="acceptpaypal" <?if($prodid != -1 && $production['acceptpaypal'] == 1) echo("CHECKED")?>></td>
</tr>
<tr>
	<td>Paypal Account:</td>
	<td><input type="text" name="paypalaccount" value="<?if($prodid != -1) echo(htmlspecialchars($production['paypalaccount']))?>"></td>
</tr>
<tr>
	<td>Paypal Information:</td>
	<td><textarea name="paypalinfo"><?if($prodid != -1) echo(htmlspecialchars($production['paypalinfo']))?></textarea></td>
</tr>
</table>

<input type="submit">
</form>

        </section>
      </article>
    </div>

<?php include('includes/page-footer.php') ?>


</body>
</html>
