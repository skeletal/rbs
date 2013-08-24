<?
include('includes/utilities.php');
include('includes/prodmanagement.php');

$link = db_connect();

$prodlist = get_prodlist($link);
if(count($prodlist) == 1) {
	if(isset($_GET['timeout']))
		header("Location: logintest.php?timeout=true&production=".$prodlist[0]['id']);
	else
		header("Location: login.php?production=".$prodlist[0]['id']);
	exit;
}

?>

<html>
<head><title>RBS</title></head>
<body>
<h1>Upcoming Shows</h1>

<?
if(isset($_GET['timeout']))
	echo("<p>Your session has timed out.  If you would like to login again, please choose the show you would like to buy tickets for.</p>");

foreach($prodlist as $production) {
	echo("<p><a href='login.php?production=" . $production['id'] . "'>" . $production['name'] . "</a></p>");
}
?>
</body>
</html>
