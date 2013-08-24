var curpay = '';

function togglePay(payid) {
	if(curpay != '')
		document.getElementById(curpay + 'info').style.display = 'none';
	curpay = payid;
	document.getElementById(curpay + 'info').style.display = 'block';
}
