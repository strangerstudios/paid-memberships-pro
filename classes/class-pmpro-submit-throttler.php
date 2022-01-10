<?php
/**
 * Limit how quickly forms can be submitted in JS and PHP.
 *
 * @since 2.6.8
 */
class PMPro_Submit_Throttler {
    /**
	 * The current object instance.
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Initialize class object and use it for future init calls.
	 *
	 * @since 2.6.8
	 *
	 * @return self The class object.
	 */
	public static function init() {
		if ( ! is_object( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

    /**
     * Hook things up in the constructor.
     */
	function __construct() {
		add_action( 'wp_ajax_nopriv_pmpro_get_clicks', array( 'PMPro_Submit_Throttler', 'get_clicks_ajax' ) );
		add_action( 'wp_ajax_pmpro_get_clicks', array( 'PMPro_Submit_Throttler', 'get_clicks_ajax' ) );
		add_action( 'wp_ajax_nopriv_pmpro_update_clicks', array( 'PMPro_Submit_Throttler', 'update_clicks_ajax' ) );
		add_action( 'wp_ajax_pmpro_update_clicks', array( 'PMPro_Submit_Throttler', 'update_clicks_ajax' ) );
		add_action( 'wp', array( 'PMPro_Submit_Throttler', 'wp' ) );
	}

	/**
	 * Maybe load some hooks during the wp action.
	 */
	public static function wp() {
		global $pmpro_pages;
		if ( pmpro_is_checkout() || ! empty( $pmpro_pages['billing'] ) && is_page( $pmpro_pages['billing']) ) {
			add_action( 'wp_footer', array( 'PMPro_Submit_Throttler', 'get_js' ), 99 );
		}
	}

    /**
     * AJAX callback to get clicks stack.
     */
	public static function get_clicks_ajax() {
		$old_clicks = pmpro_get_session_var( 'pmpro_submit_clicks' );
		if ( empty( $old_clicks ) ) { $old_clicks = []; }		
		sort( $old_clicks );
		echo json_encode( $old_clicks );
		exit;
	}

    /**
     * AJAX callback to update clicks stack.
     */
	public static function update_clicks_ajax() {
		// Could perhaps use something better than intval before 2038.
		$new_clicks = array_map( 'intval', (array)$_REQUEST['pmpro_submit_clicks'] );

		$old_clicks = pmpro_get_session_var( 'pmpro_submit_clicks' );
		if ( empty( $old_clicks ) ) { $old_clicks = []; }

		// We don't want to let the JS remove clicks. So we merge new ones in.
		$all_clicks = array_unique( array_merge( $new_clicks, $old_clicks ) );		
		sort( $all_clicks );

		// Remove old items. (5*60=5m)		
		$now = current_time( 'timestamp', true );	// UTC		
		$new_clicks = [];
		foreach( $all_clicks as $click ) {
			if ( $click > $now-(5*60) ) {
				$new_clicks[] = $click;
			}
		}

		// Save to session.
		pmpro_set_session_var( 'pmpro_submit_clicks', $new_clicks );

		exit;
	}

    /**
     * Get the JS
     */
    public static function get_js() {
        ?>
        <script>
        var pmpro_submit_clicks = [];
		// Pull clicks from server session.
		jQuery.ajax({
			url: pmpro.ajaxurl, type:'GET',timeout: pmpro.ajax_timeout,
			dataType: 'html',
			data: {
				'action': 'pmpro_get_clicks',				
			},
			error: function(xml){
				// Keep quiet for this error.
				console.log(xml);
			},
			success: function(clicks){
				pmpro_submit_clicks = JSON.parse( clicks );		
			}
		});
		
		// Add even listener to form submissions.
        jQuery(document).ready(function(){
            jQuery('form').submit(function(event) {
                // Disable the button
        		jQuery('input[type=submit]', this).attr('disabled', 'disabled');
        		jQuery('input[type=image]', this).attr('disabled', 'disabled');

                // Push the current timestamp onto the stack
                var right_now = Date.now()/1000;	// in seconds
                pmpro_submit_clicks.push(right_now);

                // Check for old timestamps in the stack (5*60 = 5min)
                var temp = [];
				for (let i = 0; i < pmpro_submit_clicks.length; i++) {
                    if ( pmpro_submit_clicks[i] > right_now-(5*60) ) {
                        temp.push(pmpro_submit_clicks[i]);
                    }
                }
                pmpro_submit_clicks = temp;

                // Save the clicks to the server.
				jQuery.ajax({
                    url: pmpro.ajaxurl, type:'POST',timeout: pmpro.ajax_timeout,
                    dataType: 'html',
                    data: {
						'action': 'pmpro_update_clicks',
						'pmpro_submit_clicks': pmpro_submit_clicks,
					},
                    error: function(xml){
                    	// Keep quiet for this error.
						console.log(xml);
					},
                    success: function(xml){
                        // Value saved.
						console.log(xml);
                    }
                });

                // Calculate delay
                var fibonacci = [0,1];
                for (var i = 2; i < pmpro_submit_clicks.length+2; i++) {
                    fibonacci[i] = fibonacci[i - 2] + fibonacci[i - 1];
                }
                delay = fibonacci.pop() * 100 - 100;

                // Delay
                const start = Date.now();	// in milliseconds
                let now = start;
                while (now - start < delay) {
                    now = Date.now();
                }
            });
        });
        </script>
        <?php
    }
}