<?
/**
 * Utility functions for managing theatres.
 */

include_once('utilities.php');

function theatre_list() {
	$dir_handle = @opendir("includes/theatres") or die("Unable to open theatre directory");

	$theatres = array();

	while($file = readdir($dir_handle)) {
		if($file[0] == ".")
			continue;
		if(substr($file, -4) !== ".inc")
			continue;
		$theatres[] = substr($file, 0, -4);
	}

	closedir($dir_handle);
	return $theatres;
}

