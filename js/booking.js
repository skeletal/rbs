var performance =  null;
var segment = null;

// An array to hold the current state of the seats
var seats = [];

var loading = [];
var displaysegment = [];

// This function shows the main menu.  It hides all other divs
function toShow() {
	toShowPre();
	if(segment != null) {
		document.getElementById('segment' + segment).style.display = 'none';
		segment = null;
	}

	performance = null;

	segmentsElem = document.getElementById('segments');
    if (segmentsElem) { segmentsElem.style.display = 'none'; }
	document.getElementById('loading').style.display = 'none';
	document.getElementById('theatre_render').style.display = 'none';
	performancesElem = document.getElementById('performances');
    if (performancesElem) { performancesElem.style.display = 'block'; }

	toShowSpec();
}

// This function shows the performance screen.  It hides all other divs.
function toPerformance(perf) {
	if(segment != null) {
		document.getElementById('segment' + segment).style.display = 'none';
		segment = null;
	}

	performancesElem = document.getElementById('performances');
	if (performancesElem) { performancesElem.style.display = 'none'; }
	document.getElementById('loading').style.display = 'none';
	document.getElementById('theatre_render').style.display = 'none';
	segmentsElem = document.getElementById('segments');
    if (segmentsElem){ segmentsElem.style.display = 'block'; }

	if(performance == perf)
		return;

	performance = perf;

	// Set up the seats array
	if(!seats[performance]) {
		seats[performance] = []
		var a = [];
		a[0] = {}; // The original seats state
		a[1] = {}; // Any new seats
		seats[performance] = a;

		// Now we'll grab the seat data and fill the original seat state.  Firstly, make the loading screen show when the user clicks a segment.
		loading[performance] = true;

		// This is the variable which is set if the loading screen is loaded.
		// It's what segment will be displayed immediately after the screen is open
		displaysegment[performance] = null

		// Now make the request.  This requires a function in either booking_user.js or booking_admin.js, depending on which page is loaded
		loadPerformanceData(performance);
	} else {
		loadPerformance(performance);
	}

	toPerformanceSpec();
	if(segments.length == 1) {
		for(i in segments) {
			toSegment(i);
			break;
		}
	}
}

// This function moves the screen to a specific segment.  It hides all other divs and shows the segment screen.
function toSegment(seg) {
	if(segment != null) {
		document.getElementById('segment' + segment).style.display = 'none';
	}

	segment = seg;
    performancesElem = document.getElementById('performances');
    if (performancesElem) { performancesElem.style.display = 'none'; }
	segmentsElem = document.getElementById('segments');
    if (segmentsElem) { segmentsElem.style.display = 'none'; }
	document.getElementById('theatre_render').style.display = 'block';

	if(loading[performance]) {
		displaysegment[performance] = segment;
		document.getElementById('loading').style.display = 'block';
	} else {
		document.getElementById('loading').style.display = 'none';
		document.getElementById('segment' + seg).style.display = 'block';
	}

	window.location.hash='target';

	toSegmentSpec();
}

// The seat state images
var seatimages = [];
seatimages[-1] = 'images/free.gif'; // Expired
seatimages[0] = 'images/free.gif'; // Free and Available
seatimages[1] = 'images/booked.gif'; // Booked
seatimages[2] = 'images/unavailable.gif'; // Taken by another user or otherwise unavailable
seatimages[3] = 'images/confirmed.gif'; // Confirmed
seatimages[4] = 'images/paid.gif'; // Paid
seatimages[5] = 'images/paid.gif'; // Paid at Sales Desk
seatimages[6] = 'images/paid_dd.gif'; // Paid Direct Debit
seatimages[7] = 'images/paid_paypal.gif'; // Paid Paypal
seatimages[8] = 'images/red.gif'; // Payment Pending
seatimages[9] = 'images/unavailable.gif'; // Unavailable
seatimages[10] = 'images/vip.gif'; // VIP

var seatimages_hi = [];
seatimages_hi[-1] = 'images/free_hi.gif'; // Expired
seatimages_hi[0] = 'images/free_hi.gif'; // Free and Available
seatimages_hi[1] = 'images/booked_hi.gif'; // Booked
seatimages_hi[2] = 'images/unavailable_hi.gif'; // Taken by another user or otherwise unavailable
seatimages_hi[3] = 'images/confirmed_hi.gif'; // Confirmed
seatimages_hi[4] = 'images/paid_hi.gif'; // Paid
seatimages_hi[5] = 'images/paid_hi.gif'; // Paid at Sales Desk
seatimages_hi[6] = 'images/paid_dd_hi.gif'; // Paid Direct Debit
seatimages_hi[7] = 'images/paid_paypal_hi.gif'; // Paid Paypal
seatimages_hi[8] = 'images/red_hi.gif'; // Payment Pending
seatimages_hi[9] = 'images/unavailable_hi.gif'; // Unavailable
seatimages_hi[10] = 'images/vip_hi.gif'; // VIP


function setSeat(seat, state, highlight) {
	d = document.getElementById(seat);
	if(highlight)
		d.src = seatimages_hi[state];
	else
		d.src = seatimages[state];
}

function reloadPerformanceData() {
	if(performance != null)
		loadPerformanceData(performance);
}

function loadPerformance(perf) {
	a = seats[perf][0];
	for(i in a) {
		setSeat(i, a[i]);
	}
	a = seats[perf][1];
	for(i in a) {
		setSeat(i, a[i]);
	}
}

function scale(zoomval) {
	segs = document.getElementById('theatre_zoom');
	theatre_scale *= zoomval;
	segs.style.fontSize = theatre_scale + 'em';
}

function resetzoom() {
	segs = document.getElementById('theatre_zoom');
	theatre_scale = 1;
	segs.style.fontSize = '1em';
}

function setScale(zoom) {
	segs = document.getElementById('theatre_zoom');
	theatre_scale = zoom;
	segs.style.fontSize = theatre_scale + 'em';
}

function widthToWindow() {
        if(window.innerWidth)
                var width = window.innerWidth;
        else if (document.body.clientWidth)
                var width = document.body.clientWidth;

        if(0.95 * width < theatre_width)
			setScale(0.95 * width / theatre_width);
		else
			setScale(1);
}
