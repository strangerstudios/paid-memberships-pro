<?php
/**
 * Notes in a Pointer dialog box for guiding users in the dashboard interface.
 */


// add_action( 'admin_enqueue_scripts', 'pmpro_enqueue_admin_scripts' );
function pmpro_enqueue_admin_scripts() {
	wp_enqueue_style( 'wp-pointer' );
	wp_enqueue_script( 'wp-pointer' );
	// hook the pointer
	add_action( 'admin_print_footer_scripts', 'pmpro_footer_scripts_for_pointers' );
}
function pmpro_footer_scripts_for_pointers() {
	$pointer_content  = '<h3>New location for Paid Memberships Pro</h3>';
	$pointer_content .= '<p>The Memberships menu has moved. The Members List and Discount Codes pages can now be found under Settings.</p>';
	?>
   <script type="text/javascript">
   //<![CDATA[
   jQuery(document).ready( function($) {
	var pointhere = '#toplevel_page_pmpro-dashboard > a > div.wp-menu-name';
	//jQuery selector to point to 
	 // jQuery('label#expiration-label').pointer({
	 jQuery(pointhere).pointer({
		content: '<?php echo $pointer_content; ?>',
		position: 'left',
		close: function() {
			// This function is fired when you click the close button
		}
	  }).pointer('open');
   });
   //]]>
   </script>
	<?php
}


// Create as a class
class PMPro_WP_Pointers {

	// Define pointer version
	const DISPLAY_VERSION = 'v2.0';

	// Initiate construct
	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );  // Hook to admin_enqueue_scripts
	}

	function admin_enqueue_scripts() {

		// Check to see if user has already dismissed the pointer tour
		$dismissed = explode( ',', get_user_meta( wp_get_current_user()->ID, 'dismissed_wp_pointers', true ) );
		$do_tour   = ! in_array( 'pmpro_v2_tour', $dismissed );

		// If not, we are good to continue
		if ( $do_tour ) {

			// Enqueue necessary WP scripts and styles
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );

			// Finish hooking to WP admin areas
			add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ) );  // Hook to admin footer scripts
			add_action( 'admin_head', array( $this, 'admin_head' ) );  // Hook to admin head
		}
	}

	// Used to add spacing between the two buttons in the pointer overlay window.
	function admin_head() {
		?>
		<style type="text/css" media="screen">
			#pointer-primary {
				margin: 0 5px 0 0;
			}
		</style>
		<?php
	}
	// Define footer scripts
	function admin_print_footer_scripts() {

		// Define global variables
		global $pagenow;
		global $current_user;

		// *****************************************************************************************************
		// This is our array of individual pointers.
		// -- The array key should be unique.  It is what will be used to 'advance' to the next pointer.
		// -- The 'id' should correspond to an html element id on the page.
		// -- The 'content' will be displayed inside the pointer overlay window.
		// -- The 'button2' is the text to show for the 'action' button in the pointer overlay window.
		// -- The 'function' is the method used to reload the window (or relocate to a new window).
		// This also creates a query variable to add to the end of the url.
		// The query variable is used to determine which pointer to display.
		// *****************************************************************************************************
		$tour = array(
			'newlevels'      => array(
				'id'       => '#pmpro-new-levels',
				'content'  => '<h3>' . __( 'Congratulations!', 'paid-memberships-pro' ) . '</h3>'
					. '<p><strong>' . __( 'WP Pointers is working properly.', 'paid-memberships-pro' ) . '</strong></p>'
					. '<p>' . __( 'This pointer is attached to the "Quick Draft" admin widget.', 'paid-memberships-pro' ) . '</p>'
					. '<p>' . __( 'Our next pointer will take us to the "Settings" admin menu.', 'paid-memberships-pro' ) . '</p>',
				'button2'  => __( 'Next', 'paid-memberships-pro' ),
			$function = 'document.location="' . $this->get_admin_url( 'admin.php', 'pmpro-memberslist' ) . '";',
			),
			'newsettings'       => array(
				'id'       => '#pmpro-new-settings',
				'content'  => '<h3>' . __( 'Moving along to Site Title.', 'paid-memberships-pro' ) . '</h3>'
				. '<p><strong>' . __( 'Another WP Pointer.', 'paid-memberships-pro' ) . '</strong></p>'
				. '<p>' . __( 'This pointer is attached to the "Blog Title" input field.', 'paid-memberships-pro' ) . '</p>',
			),

		);

		// Determine which tab is set in the query variable
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
		// Define other variables
		$function     = '';
		$button2      = '';
		$options      = array();
		$show_pointer = false;

		// *******************************************************************************************************
		// This will be the first pointer shown to the user.
		// If no query variable is set in the url.. then the 'tab' cannot be determined... and we start with this pointer.
		// *******************************************************************************************************
		if ( ! array_key_exists( $tab, $tour ) ) {

			$show_pointer = true;
			$file_error   = true;

			$id       = '#toplevel_page_pmpro-dashboard';  // Define ID used on page html element where we want to display pointer
			$content  = '<h3>' . sprintf( __( 'New PMPro %s', 'paid-memberships-pro' ), self::DISPLAY_VERSION ) . '</h3>';
			$content .= __( '<p>Welcome to new PMPro tour!</p>', 'paid-memberships-pro' );
			$content .= __( '<p>This pointer is attached to the "At a Glance" dashboard widget.</p>', 'paid-memberships-pro' );
			$content .= '<p>' . __( 'Click the <em>Begin Tour</em> button to get started.', 'paid-memberships-pro' ) . '</p>';

			$options  = array(
				'content'  => $content,
				'position' => array(
					'edge'  => 'top',
					'align' => 'left',
				),
			);
			$button2  = __( 'Begin Tour', 'paid-memberships-pro' );
			$function = 'document.location="' . $this->get_admin_url( 'admin.php', 'pmpro-memberslist' ) . '";';
		}
		// Else if the 'tab' is set in the query variable.. then we can determine which pointer to display
		else {

			if ( $tab != '' && in_array( $tab, array_keys( $tour ) ) ) {

				$show_pointer = true;

				if ( isset( $tour[ $tab ]['id'] ) ) {
					$id = $tour[ $tab ]['id'];
				}

				$options = array(
					'content'  => $tour[ $tab ]['content'],
					'position' => array(
						'edge'  => 'top',
						'align' => 'left',
					),
				);

				$button2  = false;
				$function = '';

				if ( isset( $tour[ $tab ]['button2'] ) ) {
					$button2 = $tour[ $tab ]['button2'];
				}
				if ( isset( $tour[ $tab ]['function'] ) ) {
					$function = $tour[ $tab ]['function'];
				}
			}
		}

		// If we are showing a pointer... let's load the jQuery.
		if ( $show_pointer ) {
			$this->make_pointer_script( $id, $options, __( 'Close', 'paid-memberships-pro' ), $button2, $function );
		}
	}
	// This function is used to reload the admin page.
	// -- $page = the admin page we are passing (index.php or options-general.php)
	// -- $tab = the NEXT pointer array key we want to display
	function get_admin_url( $page, $tab ) {

		$url  = admin_url();
		$url .= $page . '?page=' . $tab;

		return $url;
	}

	// Print footer scripts
	function make_pointer_script( $id, $options, $button1, $button2 = false, $function = '' ) {

		?>
	<script type="text/javascript">

		(function ($) {

			// Define pointer options
			var wp_pointers_tour_opts = <?php echo json_encode( $options ); ?>, setup;

			wp_pointers_tour_opts = $.extend (wp_pointers_tour_opts, {

				// Add 'Close' button
				buttons: function (event, t) {

					button = jQuery ('<a id="pointer-close" class="button-secondary">' + '<?php echo $button1; ?>' + '</a>');
					button.bind ('click.pointer', function () {
						t.element.pointer ('close');
					});
					return button;
				},
				close: function () {

					// Post to admin ajax to disable pointers when user clicks "Close"
					$.post (ajaxurl, {
						pointer: 'pmpro_v2_tour',
						action: 'dismiss-wp-pointer'
					});
				}
			});

			// This is used for our "button2" value above (advances the pointers)
			setup = function () {

				$('<?php echo $id; ?>').pointer(wp_pointers_tour_opts).pointer('open');

				<?php if ( $button2 ) { ?>

					jQuery ('#pointer-close').after ('<a id="pointer-primary" class="button-primary">' + '<?php echo $button2; ?>' + '</a>');
					jQuery ('#pointer-primary').click (function () {
						<?php echo $function; ?>  // Execute button2 function
					});
					jQuery ('#pointer-close').click (function () {

						// Post to admin ajax to disable pointers when user clicks "Close"
						$.post (ajaxurl, {
							pointer: 'pmpro_v2_tour',
							action: 'dismiss-wp-pointer'
						});
				   
					})
				<?php } ?>
			};

			if (wp_pointers_tour_opts.position && wp_pointers_tour_opts.position.defer_loading) {

				$(window).bind('load.wp-pointers', setup);
			}
			else {
				setup ();
			}
		}) (jQuery);
	</script>
		<?php
	}
}
$pmpro_pointers = new PMPro_WP_Pointers();
