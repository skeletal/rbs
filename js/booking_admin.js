// Managing state.  There are three possible states:
//
// 0: Normal - you select a booking to modify it, you click "New Booking" to start a new booking
// 1: Modify Booking - You can only select seats of the current booking you're in or free seats, everything else goes grey
// 2: New Booking - You can only select free seats, everything else goes grey
var state = 0;
var tseat = 0;
var fulltheatre = false;

// If we're modifying or creating a booking we need a "current booking" selector
var currentbooking = -1;
var modifybooking = -1;

function showButton(button) {
	i = document.getElementById(button);
	i.style.display = 'inline-block';
}

function hideButton(button) {
	i = document.getElementById(button);
	i.style.display = 'none';
}

// This function makes the seats not of the target booking grey, but leaves those of the target booking normal
function makeSeatsUnavailable(booking) {
	a = seats[performance][0];
	for(i in a) {
		if(a[i] > 0 && (booking == -1 || bookedseats[performance][i] != booking))
			setSeat(i, 9);
	}
}

// This reloads the performance data to its original state
function resetChanges() {
	// Remove all changes
	a = seats[performance][0];
	b = seats[performance][1];
	// set seats to be original states and not grey
	for(i in b) {
		setSeat(i, a[i]);
	}
	for(i in a) {
		if(a[i] > 0)
			setSeat(i, a[i]);
	}
	seats[performance][1] = [];
	unSelectBooking();
	state = 0;

	s = document.getElementById('status');
	s.style.display = 'none';

	hideButton('modifybooking');
	showButton('startnewbooking');
	hideButton('resetchanges');
	hideButton('savebooking');
	hideButton('cancelbooking');
	document.getElementById('navigation').style.display = 'block';
	document.getElementById('targetseats').style.display = 'none';
}

function showFullTheatre() {
	fulltheatre = true;
	document.getElementById('togglefulltheatre').innerHTML = 'Show Theatre Segment';
	for(var i in segments) {
		document.getElementById('segment' + i).style.display = 'block';
		document.getElementById('navusegment' + i).style.display = 'none';
		document.getElementById('navdsegment' + i).style.display = 'none';
	}
}

function showSegTheatre() {
	fulltheatre = false;
	document.getElementById('togglefulltheatre').innerHTML = 'Show Full Theatre';
	for(var i in segments) {
		document.getElementById('navusegment' + i).style.display = 'block';
		document.getElementById('navdsegment' + i).style.display = 'block';
		if(i != segment)
			document.getElementById('segment' + i).style.display = 'none';
	}
}

function toggleFullTheatre() {
	if(fulltheatre)
		showSegTheatre();
	else
		showFullTheatre();
}

// Changes the state and makes the seats not of this booking grey
function modifyBooking() {
	seats[performance][1] = [];
	if(currentbooking == -1)
		return;
	state = 3;

	var booking = currentbooking;
	unSelectBooking();
	modifybooking = booking;
	// Gray out all the rest of the seats
	makeSeatsUnavailable(booking);

	s = document.getElementById('status');
	s.style.display = 'block';
	s.innerHTML = "Modifying Booking";

	hideButton('modifybooking');
	hideButton('startnewbooking');
	showButton('resetchanges');
	showButton('savebooking');
	showButton('cancelbooking');

	document.getElementById('navigation').style.display = 'none';
	document.getElementById('targetseats').style.display = 'block';
}

function unSelectBooking() {
	if(currentbooking == -1)
		return;

	for(var s in bookings[performance][currentbooking]['seats']) {
		se = document.getElementById(s);
		se.style.backgroundColor = '';
	}

	currentbooking = -1;
	hideButton('modifybooking');
	document.getElementById('bookinginfo').style.display = 'none';
}

function selectBooking(booking) {
	if(currentbooking != -1) {
		unSelectBooking();
	}
	state = 1;

	for(var s in bookings[performance][booking]['seats']) {
		if(bookings[performance][booking]['seats'][s] > 0) {
			se = document.getElementById(s);
			se.style.backgroundColor = '#aaccff';
		}
	}
	currentbooking = booking;

	document.getElementById('bookingid').innerHTML = "Booking ID: " + bookings[performance][currentbooking]['id'];
	document.getElementById('bookingemail').innerHTML = "Booker's Email: " + bookings[performance][currentbooking]['email'];
	document.getElementById('bookingphone').innerHTML = "Booker's Phone: " + bookings[performance][currentbooking]['phone'];
	document.getElementById('bookingusername').innerHTML = "Booker's Name: " + bookings[performance][currentbooking]['username'];
	document.getElementById('bookingname').innerHTML = "Name: " + bookings[performance][currentbooking]['name'];
	document.getElementById('bookingdesc').innerHTML = "Description: " + bookings[performance][currentbooking]['description'];
	document.getElementById('bookingamountpaid').innerHTML = "Amount Paid: " + bookings[performance][currentbooking]['amountpaid'];
	document.getElementById('bookingdeadline').innerHTML = "Deadline: " + bookings[performance][currentbooking]['deadline'];
	if(bookings[performance][currentbooking]['pickedup'] == 1)
		document.getElementById('bookingpickedup').innerHTML = "Has been picked up";
	else
		document.getElementById('bookingpickedup').innerHTML = "Hasn't yet been picked up";
		

	document.getElementById('bookinginfo').style.display = 'block';
	showButton('modifybooking');
}

function startNewBooking() {
	seats[performance][1] = [];
	unSelectBooking();
	state = 2;
	makeSeatsUnavailable(-1);

	s = document.getElementById('status');
	s.style.display = 'block';
	s.innerHTML = "Creating a Booking";

	hideButton('modifybooking');
	hideButton('startnewbooking');
	showButton('resetchanges');
	showButton('savebooking');
	showButton('cancelbooking');
	document.getElementById('navigation').style.display = 'none';
	document.getElementById('targetseats').style.display = 'block';
}

function toggleSeat(seat) {
	switch(state) {
	case 0:	// no booking selected
	case 1:	// selected
		if(seats[performance][0][seat] <= 0) {
			unSelectBooking();
		} else if(bookedseats[performance][seat] != undefined) {
			selectBooking(bookedseats[performance][seat]);
		}
		break;
	case 2: // new booking
		if(seats[performance][0][seat] > 0)
			return;  // you can't modify other bookings
		else if(tseat == 0) {
			delete seats[performance][1][seat]; // unbook it
			setSeat(seat, 0, true);
		} else {
			seats[performance][1][seat] = tseat; // book the seat
			setSeat(seat, tseat, true);
		}
		break
	case 3: // modify booking
		if(seats[performance][0][seat] > 0 && bookedseats[performance][seat] != modifybooking)
			return;
		// Is it still the original value?
		if(seats[performance][1][seat] == null)
		{
			if(seats[performance][0][seat] == tseat) {
				return;  // No point setting it to the same value
			} else {
				seats[performance][1][seat] = tseat; // set the seat
				setSeat(seat, tseat, true);
			}
		} else {
			if(seats[performance][0][seat] == tseat) {
				delete seats[performance][1][seat]; // restore it's original value
				setSeat(seat, 0, true);
			} else {
				seats[performance][1][seat] = tseat; // set a new value
				setSeat(seat, tseat, true);
			}
		}
		break;
	}
}

function hlSeat(seat) {
	if(seats[performance][1][seat] == undefined) {
		if(seats[performance][0][seat] != undefined)
			setSeat(seat, seats[performance][0][seat], true);
	} else {
		setSeat(seat, seats[performance][1][seat], true);
	}
}

function uhlSeat(seat) {
	if(seats[performance][1][seat] == undefined) {
		if(seats[performance][0][seat] != undefined)
			setSeat(seat, seats[performance][0][seat], false);
	} else {
		setSeat(seat, seats[performance][1][seat], false);
	}
}

function highlightBooking(seat) {
	if(bookedseats[performance][seat] != undefined) {
		for(var s in bookings[performance][bookedseats[performance][seat]]['seats']) {
			hlSeat(s);
		}
	}
}

function unHighlightBooking(seat) {
	if(bookedseats[performance][seat] != undefined) {
		for(var s in bookings[performance][bookedseats[performance][seat]]['seats']) {
			uhlSeat(s);
		}
	}
}


function highlightSeat(seat) {
	switch(state) {
	case 0: // No booking
	case 1: // Selected Booking
		highlightBooking(seat);
		break;
	case 2: // Modify booking
		if(seats[performance][0][seat] > 0) {
			return;  // you can't modify other bookings
		} else {
			if(seats[performance][1][seat] == undefined)
				setSeat(seat, 0, true);
			else
				setSeat(seat, seats[performance][1][seat], true);
		}
		break;
	case 3: // Start New Booking
		if(seats[performance][0][seat] > 0 && bookedseats[performance][seat] != modifybooking)
			return; // Can't modify another person's booking
		hlSeat(seat);
		break;
	}
}

function unHighlightSeat(seat) {
	switch(state) {
	case 0:
	case 1:
		unHighlightBooking(seat);
		break;
	case 2:
		if(seats[performance][0][seat] > 0) {
			return;  // you can't modify other bookings
		} else {
			if(seats[performance][1][seat] == null)
				setSeat(seat, 0, false);
			else
				setSeat(seat, seats[performance][1][seat], false);
		}
		break;
	case 3:
		if(seats[performance][0][seat] > 0 && bookedseats[performance][seat] != modifybooking)
			return; // Can't modify another person's booking
		uhlSeat(seat);
		break;
	}
}

function targetSeat(seat) {
	if(tseat != -1 && tseat != seat) {
		tse = document.getElementById('targetseats_' + tseat);
		tse.style.backgroundColor = '';
	}

	tseat = seat;

	tse = document.getElementById('targetseats_' + tseat);
	tse.style.backgroundColor = '#acf';
}

// Set the streaming of data
setInterval("reloadPerformanceData()", 10000);

var xmlhttp = [];

function reloadPerformanceData() {
	if(performance != null)
		loadPerformanceData(performance);
}

function loadPerformanceData(perf) {
	if(perf == null)
		return;
	// Maybe they've clicked it twice...
	if(xmlhttp[perf])
		return;

	xmlhttp[perf]=new XMLHttpRequest();
	if (xmlhttp[perf] !=null) {
		xmlhttp[perf].onreadystatechange=loadingCompleteStateChange(perf);
		xmlhttp[perf].open("GET","getseats.php?admin=true&production=" + production + "&performance=" + perf,true);
		xmlhttp[perf].send(null);
	} else {
		alert("Error loading data!");
	}
}

function loadingCompleteStateChange(perf) {
	return (function loadingComplete() {
		if(xmlhttp[perf].readyState == 4) {
			if(xmlhttp[perf].status == 200) {
				callback = eval( "(" + xmlhttp[perf].responseText + ")" );
				var isnew = true;
				for(var i in seats[perf][0]) { // Is this a totally new array?
					isnew = false;
					break;
				}
				if(isnew || perf != performance) {
					seats[perf][0] = callback['seats'];
					bookings[perf] = callback['bookings'];
					bookedseats[perf] = callback['bookedseats'];
					loading[perf] = false;
					// Now if we're on the same performance still, load the performance
					if(perf == performance)
						loadPerformance(perf);
					if(displaysegment[perf] != null) {
						seg = displaysegment[perf];
						displaysegment[perf] = null;
						segment = seg;
						document.getElementById('loading').style.display = 'none';
						document.getElementById('segment' + seg).style.display = 'block';
					}
				} else if(state == 0 || state == 1) { // We're loading new data but no modifying or creating bookings yet
					var cb = currentbooking;

					newseats = callback['seats'];
					bookings[perf] = callback['bookings'];
					bookedseats[perf] = callback['bookedseats'];

					if(state == 1)
						unSelectBooking();
					for(var i in seats[perf][0]) {
						if(newseats[i] != seats[perf][0][i]) {
							seats[perf][0][i] = newseats[i];
							setSeat(i, newseats[i]);
						}
					}
					if(state == 1)
						selectBooking(cb);
				} else if(state == 2 || state == 3) { // We're modifying a booking
					newseats = callback['seats'];
					bookings[perf] = callback['bookings'];
					bookedseats[perf] = callback['bookedseats'];
					for(var i in seats[perf][0]) {
						if(newseats[i] != seats[perf][0][i]) {
							// If it hasn't been modified then just reset it
							if(seats[perf][1][i] == undefined) {
								seats[perf][0][i] = newseats[i];
								if(bookings[perf][i] == currentbooking)
									setSeat(i, newseats[i]);
								else if(newseats[i] <= 0)
									setSeat(i, 0);
								else
									setSeat(i, 9);
							} else { // It's been modified, let's handle it.
								alert("The state of one of the seats you have modified has changed (i.e. someone took the seat).  Your changes have been reverted.");
								seats[perf][0] = newseats;
								resetChanges();
								loadPerformance(perf);
								break;
							}
						}
					}
				}
				xmlhttp[perf] = undefined;
			}
		}
	})
}

function saveThisBooking() {
	// This function works by creating a form and posting back the changed seats to the system
	submitter = document.getElementById('seatsubmit');

	// Say whether it's new or a modification
	if(state == 2) {
		a = createHiddenElement('new', 'true');
		submitter.appendChild(a);
	} else if (state == 3) {
		a = createHiddenElement('modify', 'true');
		submitter.appendChild(a);
		a = createHiddenElement('booking', modifybooking);
		submitter.appendChild(a);
	}
	else
		return;

	a = createHiddenElement('performance', performance);
	submitter.appendChild(a);
	if(fulltheatre) {
		a = createHiddenElement('fulltheatre', 'true');
		submitter.appendChild(a);	
	} else if(segment != null) {
		a = createHiddenElement('tosegment', segment);
		submitter.appendChild(a);
	}

	for(perf in seats) {
		for(seat in seats[perf][1]) {
			a = createHiddenElement('changeseat[' + perf + '][' + seat + ']', seats[perf][1][seat]);
			submitter.appendChild(a);
		}
	}


	document.getElementById('seatform').submit();
}

function toShowPre() {
	unSelectBooking();
}

current_performance = null;
function toShowSpec() {
	hideButton('startnewbooking');
	document.getElementById('navigation').innerHTML = '';
    document.getElementById('segments_'+current_performance).style.display = 'none';
}

function toPerformanceSpec() {
	hideButton('startnewbooking');
	var nav = '<a href="javascript:toShow()">Main Menu</a> >> ';
	nav += performances[performance];
	document.getElementById('navigation').innerHTML = nav;
	document.getElementById('segments_'+performance).style.display = 'block';
    current_performance = performance;
}

function toSegmentSpec() {
	showButton('startnewbooking');
	var nav = '<a href="javascript:toShow()">Main Menu</a> >> ';
	nav += '<a href="javascript:toPerformance(' + performance + ')">' + performances[performance] + '</a> >> ';
	nav += segments[segment];
	document.getElementById('navigation').innerHTML = nav;
	if(fulltheatre)
		showFullTheatre();
}
