<?

/**
 * This is to be included at the head of every single authenticated user page as well as the production user login page
 */
function print_prod_header($link, $production, $htmlheaders = "", $bodyattrs = "") {
?>

<html>
<head>
<title><?=$production['name']?></title>
<link href='http://fonts.googleapis.com/css?family=Muli:300,400' rel='stylesheet' type='text/css'>
<link href='http://fonts.googleapis.com/css?family=Bitter:700' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" href="css/global.css" />
<link rel="shortcut icon" type="image/ico" href="<?=$production['sitelocation']?>/favicon.ico" />
<script type="text/javascript" src="js/global.js" ></script>


<?=$htmlheaders?>

<?php if  (substr($production['css'], 0, 1) == "/"){ ?>
<link rel="stylesheet" type="text/css" href="<?=$production['css']?>">
<?php } else { ?>
<link rel="stylesheet" type="text/css" href="show_data/<?=$production['css']?>">
<?php } ?>
</head>
<body <?=$bodyattrs?>>
<?=$production['header']?>
<?
}

/**
 * This is to be included at the base of every single authenticated user page as well as the production login page
 */
function print_prod_footer($link, $production) {
?>

<?=$production['footer']?>
</body>
</html>
<?
}
?>
