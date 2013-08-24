<?php

include_once('includes/utilities.php');

function generate_paypal_button($production, $paymentId, $amount) {

if(DEBUG) {
?>


<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="<?php echo $production['paypalaccount']; ?>">
<input type="hidden" name="lc" value="GB">
<input type="hidden" name="item_name" value="<?php echo $production['name']; ?>">
<input type="hidden" name="item_number" value="RBS-<?php echo $paymentId ?>">
<input type="hidden" name="amount" value="<?php echo $amount; ?>">
<input type="hidden" name="currency_code" value="AUD">
<input type="hidden" name="button_subtype" value="products">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="rm" value="1">
<input type="hidden" name="return" value="http://www.cserevue.org.au">
<input type="hidden" name="cancel_return" value="http://www.cserevue.org.au">
<input type="hidden" name="tax_rate" value="0.000">
<input type="hidden" name="shipping" value="0.00">
<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHosted">
<input type="hidden" name="notify_url" value="http://teletran2.cse.unsw.edu.au/~cserevue/rms/rbs/paypal.php?production=<?php echo $production['id']; ?>">
<input type="image" src="https://www.sandbox.paypal.com/en_US/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.sandbox.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>

<?php
} else {
rbslog("Generating paypal button", 1);
?>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="<?php echo $production['paypalaccount']; ?>">
<input type="hidden" name="lc" value="AU">
<input type="hidden" name="item_name" value="<?php echo $production['name']; ?>">
<input type="hidden" name="item_number" value="RBS-<?php echo $paymentId ?>">
<input type="hidden" name="amount" value="<?php echo($amount);?>">
<input type="hidden" name="currency_code" value="AUD">
<input type="hidden" name="button_subtype" value="products">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="rm" value="1">
<input type="hidden" name="return" value="<?=$production['bookingslocation']?>/paymentsummary.php">
<input type="hidden" name="cancel_return" value="<?=$production['bookingslocation']?>/paymentsummary.php">
<input type="hidden" name="tax_rate" value="0.000">
<input type="hidden" name="shipping" value="0.00">
<input type="hidden" name="notify_url" value="<?=$production['bookingslocation']?>/paypal.php?production=<?php echo $production['id']; ?>">
<input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHosted">
<input type="image" src="https://www.paypal.com/en_AU/i/btn/btn_buynowCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
<img alt="" border="0" src="https://www.paypal.com/en_AU/i/scr/pixel.gif" width="1" height="1">
</form>
<?php
}

}

?>
