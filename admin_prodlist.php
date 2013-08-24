<?
require_once('includes/utilities.php');
$link = db_connect();
require_once('includes/adminauth.php');
require_once('includes/prodmanagement.php');

if(isset($_SESSION['admin_production']))
	unset($_SESSION['admin_production']);

// If the user only has access to one production, send him straight through.
if($_SESSION['admin_superadmin'] == 0 && count($_SESSION['admin_prodlist']) == 1) {
	header("Location: admin_production.php?production=" . $_SESSION['admin_prodlist'][0]);
	exit;
}

// Grab the production list.
$prodlist = get_admin_prodlist($link);
?>

<!DOCTYPE html>
<!--[if lt IE 7]><html class="no-js lt-ie9 lt-ie8 lt-ie7"><![endif]-->
<!--[if IE 7]>   <html class="no-js lt-ie9 lt-ie8"><![endif]-->
<!--[if IE 8]>   <html class="no-js lt-ie9"><![endif]-->
<!--[if gt IE 8]><!-->
<html lang="en" class="no-js">
  <!--<![endif]-->
 <head>
  	<title>RBS Admin - Production List</title>
  	<?php include ('includes/groundwork-header.php') ?>  
 </head>
<body>
<?php include('includes/page-header.php') ?>

<div class="container">
      <article class="row">
        <section class="padded">
          <h1>Production List</h1>
			<?
			foreach($prodlist as $prod) {
				echo("<p>Production " . $prod['id'] . ": ");
				echo("<a href='admin_production.php?production=" . $prod['id'] . "'>" . $prod['name'] . "</a></p>");
			}

			if($_SESSION['admin_superadmin']) {
			?>

			<p><a href="admin_newproduction.php" class="medium button">Add New Production</a></p>

			<?
			}
			?>

        </section>
      </article>
    </div>

<?php include('includes/page-footer.php') ?>

</body>
</html>
