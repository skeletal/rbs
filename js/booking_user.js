function getBookedSeatsCount() {
    var total_count = 0;
    var performance;
    for (performance in bookedseats){
        if (performance != null){
            total_count += bookedseats[performance];
        }
    }
    return total_count;
}

var original_state = [];
var disabled_state = false;
function disableNonBookedSeats(){
    var perf;
    for (perf in seats){
        var write_original_state = false;
        // If we're not already in a disabled state, or we are but we haven't
        // saved this performance's original state yet, do so.
        if (!disabled_state || original_state[perf] == null){
            original_state[perf] = {};
            write_original_state = true;
        }
        for (seat in seats[perf][0]){
            if (write_original_state) {
                original_state[perf][seat] = seats[perf][0][seat];
            }
            if ((seats[perf][0][seat] <= 0 && 
                    seats[perf][1][seat] == null) || seats[perf][1][seat] <= 0){
                // The seat is free, so we disable it.
                if (seats[perf][1][seat] <= 0){
                    delete seats[perf][1][seat];
                    original_state[perf][seat] = 0;
                }
                seats[perf][0][seat] = 9;
            }
        }
        // If we're in the current performance, update the seats on the screen
        if(perf == performance)
            loadPerformance(perf);
    }
    disabled_state = true;
}
function restoreNonBookedSeats(){
    var perf;
    for (perf in seats){
        for (seat in seats[perf][0]){
            seats[perf][0][seat] = original_state[perf][seat];
        }
        // If we're in the current performance, update the seats on the screen
        if(perf == performance)
            loadPerformance(perf);
    }
    disabled_state = false;
}

function toggleSeat(seat) {

	// Is it still the original value?
	if(seats[performance][1][seat] == null)
	{
        // The seat has not been changed by the user...
        
		if(seats[performance][0][seat] > 1)
			return;  // you can't unbook other people's seats or paid seats
		else if(seats[performance][0][seat] == 1) {
			seats[performance][1][seat] = 0; // unbook your own seat
			perfseats[performance] -= 1;
            bookedseats[performance] -= 1;
			setSeat(seat, 0, true);
		} else {
			seats[performance][1][seat] = 1; // book the seat
			perfseats[performance] += 1;
            bookedseats[performance] += 1;
			setSeat(seat, 1, true);
		}
	} else {
        // The seat has been changed by the user...

		if(seats[performance][1][seat] == 1) {
			delete seats[performance][1][seat]; // restore its original value
			setSeat(seat, 0, true);
			perfseats[performance] -= 1;
			bookedseats[performance] -= 1;
		} else if(seats[performance][1][seat] == 0) {
			delete seats[performance][1][seat]; // restore its original value
			perfseats[performance] += 1;
			bookedseats[performance] += 1;
			setSeat(seat, 1, true);
		}
	}

    var booked_seat_count = getBookedSeatsCount();

    // If they've now booked the maximum number of seats, disable all other seats.
    if (disabled_state && booked_seat_count < max_booked_seats){
        restoreNonBookedSeats();
    }
    if(booked_seat_count >= max_booked_seats){
        disableNonBookedSeats();
        alert("Group bookings ("+(max_booked_seats+1)+" or more seats) are eligible for a discount! Please see the FAQ for more information.\n\nNote that you cannot have more than "+max_booked_seats+" unpaid seats online.\n(You can pay for your seats and book more.)");
    }
    /*if (seats[performance][1][seat] != null){
        if (booked_seat_count >= max_booked_seats-1){
            alert("Please note: Group discounts are available for "+max_booked_seats+" or more seats.");
        }
    }*/
	setSeatsMessage('perfseats' + performance, perfseats[performance]);
}

function highlightSeat(seat) {
	if(seats[performance][1][seat] == null) {
		if(seats[performance][0][seat] == 1)
			setSeat(seat, 1, true);
		else if(seats[performance][0][seat] == 0)
			setSeat(seat, 0, true);
	} else {
		if(seats[performance][1][seat] == 1)
			setSeat(seat, 1, true);
		else if(seats[performance][1][seat] == 0)
			setSeat(seat, 0, true);
	}
}

function unHighlightSeat(seat) {
	if(seats[performance][1][seat] == null) {
		if(seats[performance][0][seat] == 1)
			setSeat(seat, 1, false);
		else if(seats[performance][0][seat] == 0)
			setSeat(seat, 0, false);
	} else {
		if(seats[performance][1][seat] == 1)
			setSeat(seat, 1, false);
		else if(seats[performance][1][seat] == 0)
			setSeat(seat, 0, false);
	}
}

var xmlhttp = [];

function loadPerformanceData(perf) {
	// Maybe they've clicked it twice...
	if(xmlhttp[perf])
		return;

	xmlhttp[perf]=new XMLHttpRequest();
	if (xmlhttp[perf] !=null) {
		xmlhttp[perf].onreadystatechange=loadingCompleteStateChange(perf);
		xmlhttp[perf].open("GET","getseats.php?performance=" + perf,true);
		xmlhttp[perf].send(null);
	} else {
		alert("Error loading data!");
	}
}

function loadingCompleteStateChange(perf) {
	return (function loadingComplete() {
		if(xmlhttp[perf].readyState == 4) {
			if(xmlhttp[perf].status == 200) {
				seats[perf][0] = eval( "(" + xmlhttp[perf].responseText + ")" );
				loading[perf] = false;
				// Now we want to update the segment data.

				// Now if we're on the same performance still, load the performance
				if(perf == performance)
					loadPerformance(perf);

                // If we're already above the booking limit, disable everything.
                // XXX: Have to do this below loadPerformance so that the original
                // state is preserved.
                var booked_seat_count = getBookedSeatsCount();
                if(booked_seat_count >= max_booked_seats){
                    disableNonBookedSeats();
                }

				if(displaysegment[perf] != null) {
					seg = displaysegment[perf];
					displaysegment[perf] = null;
					segment = seg;
					document.getElementById('loading').style.display = 'none';
					document.getElementById('segment' + seg).style.display = 'block';
				}


			}
		}
	})
}

// Updates the x seats booked messages
function setSeatsMessage(id, nseats) {
	var sbm = document.getElementById(id);
	if(nseats == 0)
		sbm.innerHTML = '';
	else if(nseats == 1)
		sbm.innerHTML = '1 seat booked';
	else
		sbm.innerHTML = nseats + ' seats booked';
}


// Lets pay for the tickets!
function payForTickets() {
	// This function works by creating a form and posting back the changed seats to the system
	submitter = document.getElementById('seatsubmit');
	for(perf in seats) {
		for(seat in seats[perf][1]) {
			a = createHiddenElement('changeseat[' + perf + '][' + seat + ']', seats[perf][1][seat]);
			submitter.appendChild(a);
		}
	}

	document.getElementById('seatform').submit();
}

function toShowPre() {}

function toShowSpec() {
	document.getElementById('buttonpanel').style.display = 'none';
}
function toPerformanceSpec() {
	for(var i in segments) {
		slid = document.getElementById('segmentlink' + i);
		if(perfcs[performance][i] == true) {
			slid.style.borderColor = '#444';
			slid.style.color = '#444';
			slid.style.cursor = 'default';
		} else {
			slid.style.borderColor = '';
			slid.style.color = '';
			slid.style.cursor = '';
		}
	}

	document.getElementById('buttonpanel').style.display = 'none';
}
function toSegmentSpec() {
	document.getElementById('buttonpanel').style.display = 'block';
}

