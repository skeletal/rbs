<?php

function rect_theatre($rows,
	$seatsperrow = array(14, 14, 15, 14, 15, 14, 15, 14, 15, 15, 14, 14),
	$rownames = array(/*'A',*/'B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'),
	$maxseatsperrow = 15
	) {
	$seats = array();
	$x = 15;
	$y = 15;
	$stepx = 38;
	$stepy = 50;
	for($i = $rows - 1; $i >= 0; $i--) {
		$x = 15 + ($maxseatsperrow - $seatsperrow[$i]) * ($stepx / 2);
		for($j = 1; $j <= $seatsperrow[$i]; $j++) {
			$seats[$rownames[$i].($j)] = array( "x"=>$x, "y"=>$y );
			$x+=$stepx;
		}
		$y+=$stepy;
		$x = 15;
	}
	return $seats;
}


$theatre_seats_segment1 = rect_theatre(12);

$theatre = array(
	0 => array('id' => 0, 'name' => "Figtree Theatre Seats", 'seats'  => $theatre_seats_segment1)
);

$theatre_width = 30 + 15 * 38;
?>
