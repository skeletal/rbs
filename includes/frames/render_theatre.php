<?

/*
 * This file renders the theatre in a way that allows users to select seats using javascript.
 */

?>

<?
function print_theatre_segment($segment, $divid, $divwidth, $theatre) {
	// Get the width and height of the display area by getting he biggest numbers in the positions
	$maxy = 0;
	foreach ($segment['seats'] as $position) {
		if($position['y'] > $maxy)
			$maxy = $position['y'];
	}

	$scale = 1.0/16;

	// Now display the box
?>
<div class="segment" id="<?=$divid?>" style="position:relative; border:1px solid #888;width:100%;height:<?=($maxy + 80) * $scale?>em">
<div id="navu<?=$divid?>" class="segmentnavup">
<?
	if($segment['id'] != 0)
		echo("<a name='navup' href='javascript:toSegment(" . ($segment['id'] - 1) . ")'>&uarr; Go to " . $theatre[$segment['id'] - 1]['name'] . " &uarr;</a>");
?>
</div>
<?
	foreach ($segment['seats'] as $name => $position) {
		echo("<div class='seatdiv' id='".$name."div' onClick='toggleSeat(\"" . $name . "\");' onMouseOver='highlightSeat(\"" . $name . "\");' onMouseOut='unHighlightSeat(\"" . $name . "\");'");
		echo(" style='position:absolute;top:" . $position['y'] * $scale . "em;left:" . $position['x'] / $divwidth * 100 . "%'>");
		echo("<img class='seatimg' style='width:" . $scale*35 . "em;height:" . $scale * 30 . "em;' src='images/free.gif' id='" . $name . "'>");
		echo("<p class='seatname'>$name</div>\n");
	}
?>
<div id="navd<?=$divid?>" class="segmentnavdown">
<?
	if($segment['id'] != count($theatre) && $theatre[$segment['id'] + 1]['name'] != ""){
		echo("<a href='javascript:toSegment(" . ($segment['id'] + 1) . ")'>&darr; Go to " . $theatre[$segment['id'] + 1]['name'] . " &darr; </a>");
    }
?>
</div>
</div>
<?
} /* End of function render_segment */
?>
