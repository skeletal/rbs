<?

require_once('includes/utilities.php');
$link = db_connect();
require_once('includes/session.php');
require_once('includes/usermanagement.php');

if(!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_pass'])) {
	header('Location: admin_login.php');
	exit;
} else {
    if ($_SESSION['admin_pass'] !== admin_pass($link, $_SESSION['admin_id'])){
        header('Location: admin_login.php');
        exit;
    }
}



/* check if the user actually has admin access to this show */
function check_access_to_production($current_prod) {
    
    if ((int)$_SESSION['admin_superadmin'] != 0) {
        return;
    }


    if(isset($_SESSION['admin_prodlist'])) {
        $can_access = false;

        /* go through each production they can access and stop
         * when the current production is found
         */
        foreach ($_SESSION['admin_prodlist'] as $prod) {
            if ($prod == $current_prod) {
                /* found it! */
                $can_access = true;
                break;
            }
        }

        if (!$can_access) {
            /* they don't have permission to access this show */
            die("This hack is no longer supported. Talk to webmin to get admin access to the current production.");
        }

    }
}

function check_access_to_performance($performance) {
    
    if ((int)$_SESSION['admin_superadmin'] != 0) {
        return;
    }
    
    $user = $_SESSION['admin_id'];

    $sql = "select * 
            from performance inner join prodadmin on performance.production = prodadmin.production
            where prodadmin.admin = $user and performance.id = $performance";
    
    
    $res = mysql_query($sql);

    if (mysql_num_rows($res) == 0) {
        die("This hack is no longer supported. Talk to webmin to get admin access to the current production.");
    }

}

?>
