<?php
/* This file contains functions used to process required database updates sometimes logged after PMPro is upgraded. */

/*
	Is there an update?
*/
function pmpro_isUpdateRequired() {
	$updates = pmpro_getOption('updates');
	return(!empty($updates));
}

/**
 * Update option to require an update.
 * @param string $update
 *
 * @since 1.8.7
 */
function pmpro_addUpdate($update) {
	$updates = get_option('pmpro_updates', array());
	$updates[] = $update;
	$updates = array_unique($updates);

	update_option('pmpro_updates', $updates, 'no');
}

/**
 * Update option to remove an update.
 * @param string $update
 *
 * @since 1.8.7
 */
function pmpro_removeUpdate($update) {
	$updates = get_option('pmpro_updates', array());
	$key = array_search($update,$updates);
	if($key!==false){
	    unset($updates[$key]);
	}

	update_option('pmpro_updates', $updates, 'no');
}

/*
	Show admin notice if an update is required and not already on the updates page.
*/
if(pmpro_isUpdateRequired() && (empty($_REQUEST['page']) || $_REQUEST['page'] != 'pmpro-updates'))
	add_action('admin_notices', 'pmpro_update_notice');

/*
	Function to show an admin notice linking to the updates page.
*/
function pmpro_update_notice() {
?>
<div class="update-nag">
	<p>
	<?php 
		echo __( 'Paid Memberships Pro Data Update Required', 'pmpro' );
	?>
	</p>
	<p>
	<?php 
		echo '<a class="button button-primary" href="' . admin_url('admin.php?page=pmpro-updates') . '">' . __('Start the Update', 'pmpro') . '</a>';
	?>
	</p>
</div>
<?php
}