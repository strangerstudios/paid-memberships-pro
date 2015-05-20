<?php
function pmpro_init_recaptcha()
{
	//don't load in admin
	if(is_admin())
		return;
	
	//use recaptcha?
	global $recaptcha;
	$recaptcha = pmpro_getOption("recaptcha");
	if($recaptcha)
	{
		global $recaptcha_publickey, $recaptcha_privatekey;
		
		require_once(PMPRO_DIR . "/includes/lib/recaptchalib.php");
		
		function pmpro_recaptcha_get_html ($pubkey, $error = null, $use_ssl = false)
		{
			$locale = get_locale();
			if(!empty($locale))
			{
				$parts = explode("_", $locale);
				$lang = $parts[0];
			}
			else
				$lang = "en";
				
			//filter
			$lang = apply_filters('pmpro_recaptcha_lang', $lang);
			?>
			<div class="g-recaptcha" data-sitekey="<?php echo $pubkey;?>"></div>
			<script type="text/javascript"
				src="https://www.google.com/recaptcha/api.js?hl=<?php echo $lang;?>">
			</script>
			<?php				
		}
		
		//for templates using the old recaptcha_get_html
		if(!function_exists('recaptcha_get_html'))
		{
			function recaptcha_get_html($pubkey, $error = null, $use_ssl = false)
			{
				return pmpro_recaptcha_get_html($pubkey, $error, $use_ssl);
			}
		}
		
		$recaptcha_publickey = pmpro_getOption("recaptcha_publickey");
		$recaptcha_privatekey = pmpro_getOption("recaptcha_privatekey");
	}
}
add_action("init", "pmpro_init_recaptcha", 20);
