<?php
/**
 * Limit how quickly forms can be submitted in JS and PHP.
 *
 * @since 2.6.8
 */
class PMPro_Submit_Throttle {
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
        add_action( 'wp_footer', array( 'PMPro_Submit_Throttle', 'get_js' ), 99 );
     }

    /**
     * AJAX callback to get clicks stack.
     */

    /**
     * AJAX callback to update clicks stack.
     */

    /**
     * Get the JS
     */
    public static function get_js() {
        ?>
        <script>
        var pmpro_submit_clicks = [];
        // TODO: AJAX call to get stack from server.
        jQuery(document).ready(function(){
            jQuery('form').submit(function(event) {
                // Disable the button
        		jQuery('input[type=submit]', this).attr('disabled', 'disabled');
        		jQuery('input[type=image]', this).attr('disabled', 'disabled');

                // Push the current timestamp onto the stack
                var right_now = Date.now();
                pmpro_submit_clicks.push(right_now);

                // Check for old timestamps in the stack
                var temp = [];
                for (let i = 0; i < pmpro_submit_clicks.length; i++) {
                    if ( pmpro_submit_clicks[i] > right_now-5000*60 ) {
                        temp.push(pmpro_submit_clicks[i]);
                    }
                }
                pmpro_submit_clicks = temp;

                // TODO: AJAX to push stack to server.

                // Calculate delay
                var fibonacci = [0,1];
                for (var i = 2; i < pmpro_submit_clicks.length+2; i++) {
                    fibonacci[i] = fibonacci[i - 2] + fibonacci[i - 1];
                }
                delay = fibonacci.pop() * 100;

                // Delay
                const start = Date.now();
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