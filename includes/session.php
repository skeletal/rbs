<?php

require_once('utilities.php');

function sess_open($save_path, $session_name)
{
	return true;
}

function sess_close()
{
	return true;
}

function sess_read($id)
{
	global $link;
	$id = mysql_real_escape_string($id);
	$sql = "select * from session where id = '$id'";
	$data = sql_get_array($link, $sql);
	if(count($data) == 0)
		return "";
	$data = array_shift($data);
	$data = $data['data'];
	return $data;

}

function sess_write($id, $sess_data)
{
	$id = mysql_real_escape_string($id);
	$data = mysql_real_escape_string($sess_data);
	$results = mysql_query("SELECT id FROM session WHERE id='$id'");
	if(!$results)
		return false;
	
	$num = mysql_num_rows($results);

	if($num > 0) {
		$sql = 'UPDATE session SET last_access = "'.date('Y-m-d H:i:s').
			'", data="'.$data.'" '.
			' WHERE id="'.$id.'"';
		if (!mysql_query($sql)) {
			return false;
		}
		return true;
	} else {
		$sql = "INSERT INTO session ".
			"(id, last_access, data) ".
			"VALUES ('$id', '".date('Y-m-d H:i:s')."', '$data')";
		if (!mysql_query($sql)) {
			return false;
		}
		return true;
	}
}

function sess_destroy($id)
{
	$id = mysql_real_escape_string($id);
	mysql_query("DELETE FROM session WHERE id = '$id'") or die(mysql_error());
}

function sess_gc($maxlifetime) {
	mysql_query('DELETE FROM session WHERE last_access < "'.get_expiry_time().'"');
	return true;
}

function get_expiry_time() {
	return date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s').' - 24 hours'));
}

/* Setup session */
//ini_set('session.save_path', BASE_DIR.'/tmp');
session_set_save_handler("sess_open", "sess_close", "sess_read", "sess_write", "sess_destroy", "sess_gc");
session_name('rbs');
session_start();

//for security, incase someone puts an attack in their session id.
$session_id = mysql_real_escape_string(session_id());

?>
