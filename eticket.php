<?php
include('includes/settings.php');
include('includes/utilities.php');
include('includes/prodmanagement.php');
include('includes/paymentmanagement.php');
error_reporting(-1);
$ticket_id = $_GET["ticket_id"];
$seat = substr($ticket_id, 13);
$id = substr($ticket_id,0, 13);
if($ticket_id == NULL){
    print "error";
}else {


    echo "<h1>eTicket for seat $seat</h1><hr/>";

    //set it to writable location, a place for temp generated PNG files
    $PNG_TEMP_DIR = dirname(__FILE__).DIRECTORY_SEPARATOR.'temp'.DIRECTORY_SEPARATOR;

    //html PNG location prefix
    $PNG_WEB_DIR = 'temp/';

    include "phpqrcode/qrlib.php";    

    //ofcourse we need rights to create temp dir
    if (!file_exists($PNG_TEMP_DIR))
        mkdir($PNG_TEMP_DIR);

    $filename = $PNG_TEMP_DIR.$id.'.png';
    $errorCorrectionLevel = 'L';
    $matrixPointSize = 6;

        QRcode::png('rbs.cserevue.org.au/confirm_eticket.php?id='.$id, $filename, $errorCorrectionLevel, $matrixPointSize, 2);    
        
    echo '<img src="'.$PNG_WEB_DIR.basename($filename).'" /><hr/>';  
    $DB_LINK = db_connect();

    $results =  get_payment_total($DB_LINK, 'KbTLyN');
    $seatList = array();
    foreach($results as $seat) {
        $seatList[] = $seat['seatId'];
    }
    print "<p>";
    //$results = send_confirmation_email($DB_LINK, $results);
}


   
?>
