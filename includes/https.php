<?php
/*
	Code related to HTTPS/SSL
*/

/**
 * Check if we have set the $isapage variable,
 * and if so prevents WP from sending a 404.
 * @since 2.10 This is only used by the services/getfile.php script now.
 */
function pmpro_status_filter( $s ) {
	global $isapage;
	if($isapage && strpos( $s, '404' ) )
		return false;	//don't send the 404
	else
		return $s;
}
add_filter('status_header', 'pmpro_status_filter');

/**
 * Detect HTTPS context for PMPro's own URL rewriting.
 *
 * Mirrors WordPress's is_ssl() by default and exposes a `pmpro_is_ssl` filter
 * so sites behind a TLS-terminating reverse proxy (Cloudflare Flexible, NGINX
 * with TLS upstream, AWS ELB, etc.) can opt into HTTPS detection based on
 * their proxy's signaling. Why a filter and not a default fallback to common
 * forwarded headers:
 *
 * - Trusting `X-Forwarded-Proto` is only safe when the origin is not directly
 *   reachable by clients. Whether that's true depends on the site's network,
 *   not the plugin. WordPress core itself keeps `is_ssl()` conservative for
 *   this reason.
 * - Multi-proxy setups send comma-separated values like `https, http` that
 *   need site-specific parsing; the safe default is to do nothing.
 *
 * Example: enable trust of `X-Forwarded-Proto` on a site that's always
 * fronted by a proxy. Apply once in a mu-plugin or theme functions.php:
 *
 *     add_filter( 'pmpro_is_ssl', function( $is_ssl ) {
 *         if ( $is_ssl ) {
 *             return true;
 *         }
 *         if ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
 *             $first = trim( strtok( $_SERVER['HTTP_X_FORWARDED_PROTO'], ',' ) );
 *             if ( 'https' === strtolower( $first ) ) {
 *                 return true;
 *             }
 *         }
 *         return $is_ssl;
 *     } );
 *
 * Sites that need the rest of WordPress (cookies, redirects, etc.) to also
 * recognize HTTPS from a proxy header should normalize at the WP level by
 * setting $_SERVER['HTTPS'] = 'on' early in wp-config.php.
 *
 * @since TBD
 *
 * @return bool True if the current request should be treated as HTTPS for
 *              PMPro URL rewriting purposes.
 */
function pmpro_is_ssl() {
	/**
	 * Filter PMPro's HTTPS detection.
	 *
	 * Default mirrors WordPress's is_ssl(). Hook this to extend detection
	 * for proxy-fronted sites — see pmpro_is_ssl() docblock for an example.
	 *
	 * @since TBD
	 *
	 * @param bool $is_ssl Whether the current request is HTTPS.
	 */
	return (bool) apply_filters( 'pmpro_is_ssl', is_ssl() );
}

/**
 * Filters links/etc to add HTTPS to URL if needed.
 */
function pmpro_https_filter( $s ) {
	global $besecure;
	$besecure = apply_filters( 'pmpro_besecure', $besecure );

	if( $besecure || pmpro_is_ssl() )
		return str_replace( 'http:', 'https:', $s );
	else
		return str_replace( 'https:', 'http:', $s );
}
add_filter('bloginfo_url', 'pmpro_https_filter');
add_filter('wp_list_pages', 'pmpro_https_filter');
add_filter('option_home', 'pmpro_https_filter');
add_filter('option_siteurl', 'pmpro_https_filter');
add_filter('logout_url', 'pmpro_https_filter');
add_filter('login_url', 'pmpro_https_filter');
add_filter('home_url', 'pmpro_https_filter');

/**
 * This function updates the besecure global
 * with post data and redirects if needed.
 * Will only redirect if the Force SSL setting is true.
 */
function pmpro_besecure() {
	global $besecure, $post;
	
	//check the post option
	if( ! is_admin() && ! empty( $post->ID ) && ! $besecure ) {
		$besecure = get_post_meta( $post->ID, 'besecure', true );
	}

	//if forcing ssl on admin, be secure in admin and login page
	if( ! $besecure && force_ssl_admin() && ( is_admin() || pmpro_is_login_page() ) ) {
		$besecure = true;
	}

	$besecure = apply_filters( 'pmpro_besecure', $besecure );

	$use_ssl = get_option( 'pmpro_use_ssl' );
	if( $use_ssl == 1 ) {
		if( $besecure && ( empty( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] == 'off' || $_SERVER['HTTPS'] == 'false' ) ) {
			//need to be secure		
			wp_safe_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			exit;
		} elseif ( ! $besecure && ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != 'off' && $_SERVER['HTTPS'] != 'false' ) {
			//don't need to be secure		
			wp_safe_redirect('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			exit;
		}	
	}
}
add_action( 'wp', 'pmpro_besecure', 2 );
add_action( 'login_init', 'pmpro_besecure', 2 );

/**
 * Echo the JavaScript SSL redirect
 * if the Force SSL option is set.
 */
function pmpro_ssl_javascript_redirect() {
	global $besecure;
	$use_ssl = get_option( 'pmpro_use_ssl' );
	if( ! is_admin() && $use_ssl == 2 ) {
		if( ! empty( $besecure ) ) {
		?>
			<script lang="JavaScript">
				//needs to be secure
				if (window.location.protocol != "https:")
					window.location.href = "https:" + window.location.href.substring(window.location.protocol.length);
			</script>
		<?php
		} else {
		?>
			<script lang="JavaScript">
				//should be over http
				if (window.location.protocol != "http:")
					window.location.href = "http:" + window.location.href.substring(window.location.protocol.length);
			</script>
		<?php
		}
	}
}
add_action( 'wp_print_scripts', 'pmpro_ssl_javascript_redirect' );

//If the site URL starts with https:, then force SSL/besecure to true. (Added 1.5.2)
function pmpro_check_site_url_for_https( $besecure = NULL ) {	
	global $wpdb, $pmpro_siteurl;

	//need to get this from the database because we filter get_option
	if( empty( $pmpro_siteurl ) ) {
		$pmpro_siteurl = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'siteurl' LIMIT 1" );
	}
	
	//entire site is over https?
	if( strpos( $pmpro_siteurl, 'https:' ) !== false ) {
		$besecure = true;
	}
	
	return $besecure;
}
add_filter( 'pmpro_besecure', 'pmpro_check_site_url_for_https' );

//capturing case where a user links to https admin without admin over https
function pmpro_admin_https_handler() {
	if ( ! empty( $_SERVER['HTTPS'] ) ) {
		$https = sanitize_text_field( $_SERVER['HTTPS'] );
		if( strtolower( $https ) != 'off' && strtolower( $https ) != 'false' && is_admin() ) {
			if( substr( get_option( 'siteurl' ), 0, 5 ) == 'http:' && ! force_ssl_admin() ) {
				//need to redirect to non https
				wp_safe_redirect( esc_url_raw( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) );
				exit;
			}
		}
	}
}
add_action( 'init', 'pmpro_admin_https_handler' );

/*
	This code is for the "nuke" option to make URLs secure on secure pages.
*/
function pmpro_NuclearHTTPS() {
	//did they choose the option?
	$nuking = get_option( 'pmpro_nuclear_HTTPS' );
	if(!empty($nuking)) {
		ob_start( 'pmpro_replaceURLsInBuffer' );
	}
}
add_action( 'init', 'pmpro_NuclearHTTPS' );

function pmpro_replaceURLsInBuffer($buffer) {
	global $besecure;
	
	//only swap URLs if this page is secure
	if($besecure) {
		/*
			okay swap out all links like these:
			* http://domain.com
			* http://anysubdomain.domain.com
			* http://any.number.of.sub.domains.domain.com
		*/
		$buffer = preg_replace("/http\:\/\/([a-zA-Z0-9\.\-]*" . str_replace(".", "\.", PMPRO_DOMAIN) . ")/i", "https://$1", $buffer);		
	}
	
	return $buffer;
}