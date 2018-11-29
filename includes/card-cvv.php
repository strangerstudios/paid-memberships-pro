<?php

?>
	<div id="cvv-window" class="fade-in dialog-box">
		<div id="small-x-close">x</div>
		<div class="box d">
			<div class="top box e">
				<h3>Locating your Credit Card CVV/CVV2 Security Code</h3>
			</div>
			<div class="box f">
				<p><strong>Visa/MasterCard/Discover</strong><br /> Your card security code for your MasterCard, Visa or Discover card is a three-digit number on the back of your credit card, immediately following your main card number.</p>

				<p><strong>American Express</strong><br /> The card security code for your American Express card is a four-digit number located on the front of your credit card, to the right or left above your main credit card number.</p>

				<p><strong>Why do we ask for this?</strong><br /> We ask for this information for your security, as it verifies for us that a credit card is in the physical possesion of the person attempting to use it.</p>
			</div>
			<div id="cvv-cards" class="box g">
			<img src="<?php echo plugins_url( '/images/CCV-back.jpg', __DIR__ ); ?>" width="250" height="auto" border="0" alt="Back of Card" />
				<br><br>
			<img src="<?php echo plugins_url( '/images/CCV-front.jpg', __DIR__ ); ?>" width="250" height="auto" border="0" alt="Front of Card" />
			</div>
		</div>
	</div>
<?php
