<?
/**
 * Utility functions for managing performances.
 */

include_once('utilities.php');

function production_exists($link, $prodid) {
	$prodid = (int)$prodid;

	$query = "select id from production where id = $prodid";
	$results = mysql_query($query, $link);
	return ($results && mysql_num_rows($results) > 0); 
}

/**
 * Gets the information for a specific show.
 * prod is assumed to exist.
 */
function get_production($link, $prodid) {

	$q_prod = "select * from production where id = $prodid";
	$r_prod = mysql_query($q_prod, $link);


	if(!$r_prod)
		return array();

	if($row = mysql_fetch_array($r_prod, MYSQL_ASSOC)) {
		return $row;
	}
	else
		return null;
}

/**
 * Gets a full list of productions.
 */
function get_prodlist($link, $onlyopen=true) {
	$sql = "select * from production p";
	if($onlyopen) {
		$sql .= " where isclosed = 0 ";//and (select max(date) from performance pe where pe.production = p.id) >= CURDATE()";
	}
	return sql_get_array($link, $sql);
}

/**
 * Get a production list for the admin section
 */
function get_admin_prodlist($link, $adminid=-1) {
	if($adminid == -1) {
		$adminid = $_SESSION['admin_id'];
		$superadmin = $_SESSION['admin_superadmin'];
	} else {
		$adminid = (int)$adminid;
		$rows_superadmin = sql_get_array($link, "select superadmin from admin where admin = $adminid");
		if($row != null && count($row) > 0)
			$superadmin = $rows_superadmin[0]['superadmin'];
	}
	
	if($superadmin)
		$sql = "select * from production order by closedate desc";
	else
		$sql = "select p.* from production p, prodadmin pa where pa.production = p.id and pa.admin = $adminid order by p.closedate desc";

	return sql_get_array($link, $sql);
}

/**
 * This emails the production admins
 */
function email_prod_admins($link, $prodid, $subject, $message) {
	$prodid = (int)$prodid;
	$sql = "select email from admin where superadmin = 1 union select email from admin a, prodadmin p where p.admin = a.id and p.production = $prodid";
	$emails = sql_get_array($link, $sql);
	foreach($emails as $email) {
		send_email($email['email'], $subject, $message, "From: alerts@cserevue.org.au\r\n");
	}
}

function email_sales_team($link, $prodid, $subject, $message){
	$prodid = (int)$prodid;
	$sql = "select salesemail from production where id=$prodid";
	$emails = sql_get_array($link, $sql);
    $email = $emails[0];
    send_email($email['salesemail'], $subject, $message, "From: ".$email['salesemail']."\r\n");
}

// Sends a confirmation email to user after seats are purchased
function send_confirmation_email_bookingid($link, $booking){

    $id = $booking['id'];
	$sql = "SELECT id as seatId";
	$sql .= " FROM bookedseat";
	$sql .= " WHERE booking = $id";

	$results = sql_get_array($link, $sql);

	if(!$results)
		return null;

    $seatList = array();
    foreach($results as $seat) {
        $seatList[] = $seat['seatId'];
    }
    $seatsInQuery = implode(",", $seatList);
    $sql = "SELECT p.name as prod";
    $sql .= "FROM production p, booking b, performance ps";
    $sql .= "WHERE b.id = $id and ps.id = b.performance and p.id = ps.production";
    $result = sql_get_array($link, $sql);
    $result = $result[0];
    $sql = "SELECT bs.* , p.date, p.starttime FROM bookedseat bs, performance p, booking b WHERE bs.id in ($seatsInQuery) and b.id = bs.booking and p.id = b.performance";
    $seats = sql_get_array($link, $sql);


    $name = $booking['name'];
    $message = "<img src=rbs.cserevue.org.au/show_data/2013_law/email_header.png /><p>";
    $message .= "Dear $name,<p>";
    $message .= "Thank you for buying tickets to $result[prod], one of UNSW's largest student-run productions that involves socio-political and legal comedy, singing and dancing.<br>"; 
    $message .= "We are proud to raise funds for the Kingsford Legal Centre, which provides pro bono legal services to the Randwick Botany area, and Nura Gili, which enhances indigenous access to UNSW's tertiary programs.<p>"; 
    $message .= "<b>Venue:</b> Science Theatre<br>";
    $message .= "Anzac Parade<br>";
    $message .= "Kensington NSW 2052<br>";
    $message .= "<b>Time:</b> Doors open at 7:30pm for a 8pm start.<p>";
    $date = "";

    foreach($seats as $seat){
        if(date == "" || $date != $seat[date]){
            if($date != $seat[date]){
                $message .="</ul>";
            }
            $ppdate = date("l jS F", strtotime($seat[date]));
            $message .= "<b>Tickets for $ppdate</b><p>";
            $message .= "<ul>";
            $date = $seat[date];
        }
        $message .= "<li><b>Ticket for seat $seat[seat]:</b> http://rbs.cserevue.org.au/eticket.php?ticket_id=$seat[guid]$seat[seat] </li>";
    }
    $message .= "</ul><hr/>";
    $message .= "<b>Raffle:</b><br>";
    $message .= "You're also in the running to win an iPad Mini by completing the form at bit.ly/lr13raffle<p>";

    $message .= "<b>Ticket Info:</b><br>";
    $message .= "<ul>";
    $message .= "<li>The show will start at 8pm sharp. Doors will close at 8:15pm, after which you can only enter during intermission.</li>";
    $message .= "<li>Please print out each of the tickets found at the links above, or display the link on your phone or other device.</li>"; 
    $message .= "<li>Group booking ticket holders that enter simultaneously need only present one ticket to validate all seats within the group booking.</li>";
    $message .= "<li>Group members arriving separately must present each ticket individually.</li>";
    $message .= "<li>You can email the above seat links to other members of the group booking to facilitate the admissions process</li>";
    $message .= "<li>Any questions, concerns or issues regarding your tickets can be directed to ticketing@lawrevue.org</li>";
    $message .= "</ul>";

    $message .= "<b> Getting there on foot</b><br>";
    $message .= "Enter through Anzac Parade Gate and follow the University Mall. Science Theatre is located on the left, opposite the Red Centre.<p>";
    $message .= "<b> Parking</b><br>";
    $message .= "Enter through Gate 14, Barker St, turn right into Southern Drive.<br>";
    $message .= "Upon exiting vehicle turn right into Engineering Road, turn left into University Mall. Science Theatre is located on the right side, opposite the Red Centre.<p>";
    $message .= "<b> Patron drop-off/Handicapped Parking</b><br>";
    $message .= "Enter through Gate 2, High St, turn left at the Io Myers Theatre, then first right, turn left into Union Rd, then first right. Handicapped patrons may park here in designated bays. Other vehicles should return and exit via Gate 2 and proceed to the Barker St Parking Station.<p>";

    $headers = "Content-type: text/html; charset=iso-8859-1 \r\n";
    $headers .= "From: ticketing@lawrevue.org\r\n" ;

    send_email($booking['email'], $result[prod]." eTickets" , $message, $headers);
    print $message;

	// a an arra of rows, where each row has 'seatId' and 'price'
    print_r($results);
}
function send_confirmation_email($link, $seats){
    error_reporting(-1);
    $seatList = array();
    foreach($seats as $seat) {
        $seatList[] = $seat['seatId'];
    }
    $seatsInQuery = implode(",", $seatList);
    $sql = "SELECT p.name as prod, u.name, u.email ";
    $sql .= "FROM production p, booking b, bookedseat bs, user u ";
    $sql .= "WHERE bs.id = $seatList[0] and b.id = bs.booking and u.id = b.user and p.id = u.production";
    $result = sql_get_array($link, $sql);
    $result = $result[0];
    $sql = "SELECT bs.* , p.date, p.starttime FROM bookedseat bs, performance p, booking b WHERE bs.id in ($seatsInQuery) and b.id = bs.booking and p.id = b.performance";
    $seats = sql_get_array($link, $sql);


    $message = "<img src=show_data/2013_law/email_header.png /><p>";
    $message .= "Dear $result[name],<p>";
    $message .= "Thank you for buying tickets to $result[prod], one of UNSW's largest student-run productions that involves socio-political and legal comedy, singing and dancing.<br>"; 
    $message .= "We are proud to raise funds for the Kingsford Legal Centre, which provides pro bono legal services to the Randwick Botany area, and Nura Gili, which enhances indigenous access to UNSW's tertiary programs.<p>"; 
    $message .= "<b>Venue:</b> Science Theatre<br>";
    $message .= "Anzac Parade<br>";
    $message .= "Kensington NSW 2052<br>";
    $message .= "<b>Time:</b> Doors open at 7:30pm for a 8pm start.<p>";
    $date = "";

    foreach($seats as $seat){
        if(date == "" || $date != $seat[date]){
            if($date != $seat[date]){
                $message .="</ul>";
            }
            $ppdate = date("l jS F", strtotime($seat[date]));
            $message .= "<b>Tickets for $ppdate</b><p>";
            $message .= "<ul>";
            $date = $seat[date];
        }
        $message .= "<li><b>Ticket for seat $seat[seat]:</b> http://rbs.cserevue.org.au/eticket.php?ticket_id=$seat[guid]$seat[seat] </li>";
    }
    $message .= "</ul><hr/>";
    $message .= "<b>Raffle:</b><br>";
    $message .= "You're also in the running to win an iPad Mini by completing the form at bit.ly/lr13raffle<p>";

    $message .= "<b>Ticket Info:</b><br>";
    $message .= "<ul>";
    $message .= "<li>The show will start at 8pm sharp. Doors will close at 8:15pm, after which you can only enter during intermission.</li>";
    $message .= "<li>Please print out each of the tickets found at the links above, or display the link on your phone or other device.</li>"; 
    $message .= "<li>Group booking ticket holders that enter simultaneously need only present one ticket to validate all seats within the group booking.</li>";
    $message .= "<li>Group members arriving separately must present each ticket individually.</li>";
    $message .= "<li>You can email the above seat links to other members of the group booking to facilitate the admissions process</li>";
    $message .= "<li>Any questions, concerns or issues regarding your tickets can be directed to ticketing.head@lawrevue.org</li>";
    $message .= "</ul>";

    $message .= "<b> Getting there on foot</b><br>";
    $message .= "Enter through Anzac Parade Gate and follow the University Mall. Science Theatre is located on the left, opposite the Red Centre.<p>";
    $message .= "<b> Parking</b><br>";
    $message .= "Enter through Gate 14, Barker St, turn right into Southern Drive.<br>";
    $message .= "Upon exiting vehicle turn right into Engineering Road, turn left into University Mall. Science Theatre is located on the right side, opposite the Red Centre.<p>";
    $message .= "<b> Patron drop-off/Handicapped Parking</b><br>";
    $message .= "Enter through Gate 2, High St, turn left at the Io Myers Theatre, then first right, turn left into Union Rd, then first right. Handicapped patrons may park here in designated bays. Other vehicles should return and exit via Gate 2 and proceed to the Barker St Parking Station.<p>";

    $headers = "Content-type: text/html; charset=iso-8859-1 \r\n";
    $headers .= "From: tickets@lawrevue.org\r\n" ;

    send_email($result[email], $result[prod]." eTickets" , $message, $headers);
    print $message;

}
/**
 * Adds a new production.  Should be safe to pass the $_POST parameter to it.
 */
function add_production($link, $production) {
	$sql = "INSERT INTO production (";

	$values = "";

	// name
	$sql .= "name";
	if(!isset($production['name']) || $production['name'] == '')
		return "Please enter a name.";
	$values .= "'" . mysql_real_escape_string($production['name']) . "'";

	// These columns have no special handling.  Lets just do them all in one go.
	$stringcols = array("header", "footer", "css", "sitelocation", "faqlocation", "salesinfo", "ddinfo", "paypalinfo", "paypalaccount");
	foreach ($stringcols as $col) {
		$sql .= ", $col";
		if(!isset($production[$col]))
			$values .= ", ''";
		else
			$values .= ", '" . mysql_real_escape_string($production[$col]) . "'";
	}

	// These columns are the checkbox columns.  Again, we can do them all in one go.
	$checkboxcols = array("isclosed", "acceptsales", "acceptdd", "acceptpaypal");
	foreach($checkboxcols as $col) {
		$sql .= ", $col";
		if(!isset($production[$col]) || $production[$col] == 'off')
			$values .= ", 0";
		else
			$values .= ", 1";
	}

	// Now for columns with special handling:

	// closedate
	if(isset($production['closedate']) && $production['closedate'] != '') {
		if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $production['closedate']) !== 1)
			return "Please format the closed date as Year-Month-Day (for example 2010-01-01)";
		$sql .= ", closedate";
		$values .= ", '" . $production['closedate'] . "'";
	}

	// groupticketsamount
	if(isset($production['groupticketsamount'])) {
		$sql .= ", groupticketsamount";
		$values .= ", " . (int)$groupticketsamount;
	}

	// theatre
	$sql .= ", theatre";
	if(!isset($production['theatre']))
		return "Please enter a valid theatre";
	else
		$values .= ", '" . mysql_real_escape_string($production['theatre']) . "'";


	// Complete the SQL command:
	$sql .= ") VALUES (" . $values . ")";

	if(mysql_query($sql, $link)) {
		rbslog("Created production " . $production['name']);
		return mysql_insert_id($link);
	} else
		return "Insert query failed";
}

/**
 * Updates production details.  Should be safe to pass the $_POST parameter to it.
 */
function modify_production($link, $prodid, $production) {
	$sql = "UPDATE production SET ";

	// name
	$sql .= "name = ";
	if(!isset($production['name']) || $production['name'] == '')
		return "Please enter a name.";
	$sql .= "'" . mysql_real_escape_string($production['name']) . "'";

	// These columns have no special handling.  Lets just do them all in one go.
	$stringcols = array("header", "footer", "css", "sitelocation", "faqlocation", "salesinfo", "ddinfo", "paypalinfo", "paypalaccount");
	foreach ($stringcols as $col) {
		$sql .= ", $col = ";
		if(!isset($production[$col]))
			$sql .= "''";
		else
			$sql .= "'" . mysql_real_escape_string($production[$col]) . "'";
	}

	// These columns are the checkbox columns.  Again, we can do them all in one go.
	$checkboxcols = array("isclosed", "acceptsales", "acceptdd", "acceptpaypal");
	foreach($checkboxcols as $col) {
		$sql .= ", $col = ";
		if(!isset($production[$col]) || $production[$col] == 'off')
			$sql .= "0";
		else
			$sql .= "1";
	}

	// Now for columns with special handling:

	// closedate
	if(isset($production['closedate']) && $production['closedate'] != '') {
		if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $production['closedate']) !== 1)
			return "Please format the closed date as Year-Month-Day (for example 2010-01-01)";
		$sql .= ", closedate = '" . $production['closedate'] . "'";
	}

	// groupticketsamount
	if(isset($production['groupticketsamount'])) {
		$sql .= ", groupticketsamount = " . (int)$groupticketsamount;
	}

	// theatre
	$sql .= ", theatre = ";
	if(!isset($production['theatre']))
		return "Please enter a valid theatre";
	else
		$sql .= "'" . mysql_real_escape_string($production['theatre']) . "'";


	// Complete the SQL command:
	$sql .= " WHERE id = " . (int)$prodid . "";

	if(mysql_query($sql, $link)) {
		rbslog("Updated production " . $production['name']);
		return $prodid;
	} else
		return "Update query failed ".mysql_error();
}

/**
 *a HTML drop-down box of all productions for admin login
 */
function production_dropdown($link, $prodid) {
	$prods = get_prodlist($link);
	echo "<select name=\"production\">\n";
	echo "<option value=\"NULL\">Select Production</option>\n";
	foreach($prods as $prod){
		$val = $prod['id'];
		$name = $prod['name'];
		if($val == $prodid){
			echo "<option value=\"$val\" selected=\"selected\">$name</option>\n";
		}else{
			echo "<option value=\"$val\">$name</option>\n";
		}
	}

	echo "</select>\n";

}
?>
