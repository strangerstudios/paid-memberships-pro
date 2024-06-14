<?php
/*
	PMPro Report
	Title: Members per Level
	Slug: members_per_level

	For each report, add a line like:
	global $pmpro_reports;
	$pmpro_reports['slug'] = 'Title';

	For each report, also write two functions:
	* pmpro_report_{slug}_widget()   to show up on the report homepage.
	* pmpro_report_{slug}_page()     to show up when users click on the report page widget.
*/
global $pmpro_reports;
$pmpro_reports['members_per_level'] = __('Active Members Per Level', 'paid-memberships-pro' );

// Enqueue Google Visualization JS on report page
function pmpro_report_members_per_level_init() {
	if ( is_admin() && ( isset( $_REQUEST['report'] ) && $_REQUEST[ 'report' ] == 'members_per_level' ) || ( isset( $_REQUEST['page'] ) && $_REQUEST[ 'page' ] == 'pmpro-reports' ) ) {
		wp_enqueue_script( 'corechart', plugins_url( 'js/corechart.js',  plugin_dir_path( __DIR__ ) ) );
	}
}
add_action("init", "pmpro_report_members_per_level_init");

// Members Per Level Report Widget on Reports Dashboard
function pmpro_report_members_per_level_widget() {
	global $pmpro_reports; ?>
	<span id="pmpro_report_members_per_level_widget" class="pmpro_report-holder">
		<?php pmpro_report_draw_active_members_per_level_chart(); ?>
		<?php if ( function_exists( 'pmpro_report_members_per_level_page' ) ) { ?>
			<p class="pmpro_report-button">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports&report=members_per_level' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View the full %s report', 'paid-memberships-pro' ), $pmpro_reports['members_per_level'] ) ); ?>"><?php esc_html_e('Details', 'paid-memberships-pro' );?></a>
			</p>
		<?php } ?>
	</span>
	<?php
}

function pmpro_report_members_per_level_page() {
	$pmpro_levels = pmpro_getAllLevels( true );
	?>
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Active Members Per Level', 'paid-memberships-pro' ); ?>
	</h1>
	<div class="pmpro_chart_area">
		<?php pmpro_report_draw_active_members_per_level_chart(); ?>
	</div>
	<div class="pmpro_table_area">
		<table class="widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Level Name', 'paid-memberships-pro' ); ?></th>
					<th><?php esc_html_e( 'Number of Active Members', 'paid-memberships-pro' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					$active_members = pmpro_report_get_active_members_per_level();
					if ( ! empty( $active_members ) ) {
						foreach ( $active_members as $am ) {
							if ( ! empty( $pmpro_levels[$am->membership_id] ) ) { ?>
								<tr>
									<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-memberslist&l='.$am->membership_id ) ); ?>" title="<?php esc_attr_e( 'View Active Members With This Level', 'paid-memberships-pro' ); ?>"><?php echo esc_html( $pmpro_levels[$am->membership_id]->name ); ?></a></td>
									<td><?php echo esc_html( $am->total_active_members ); ?></td>
								</tr>
								<?php
							}
						}
					} else { ?>
						<tr><td colspan="2"><?php esc_html_e( 'No Active Members Found', 'paid-memberships-pro' ); ?></td></tr>
					<?php } ?>
			</tbody>
		</table>
	</div>
	<?php
}

// Returns an array of membership level IDs and the count of active members.
function pmpro_report_get_active_members_per_level() {
	global $wpdb;

	// Query to get active members per level.
	$sqlQuery = "SELECT membership_id, count(*) as total_active_members 
	FROM $wpdb->pmpro_memberships_users as mu 
	LEFT JOIN $wpdb->users as u on u.ID = mu.user_id 
	WHERE mu.status = 'active' 
	AND u.ID IS NOT NULL
	GROUP BY membership_id 
	ORDER BY total_active_members DESC";
	
	$results = $wpdb->get_results( $sqlQuery );

	return $results;
}

// Draw a pie chart of active members per level.
function pmpro_report_draw_active_members_per_level_chart() {
	$pmpro_levels = pmpro_getAllLevels( true );
	$cols = array();
	$active_members = pmpro_report_get_active_members_per_level();
	if ( ! empty( $active_members ) ) {
		foreach ( $active_members as $am ) {
			if ( ! empty( $pmpro_levels[$am->membership_id] ) ) {
				$cols[$pmpro_levels[$am->membership_id]->name] = intval( $am->total_active_members );
			}
		}
	} ?>
	<div id="chart_div"></div>
	<script>
		// Draw the Members Per Level pie chart.
		google.charts.load( 'current', {'packages':['corechart']} );
		google.charts.setOnLoadCallback( drawVisualization );
		function drawVisualization() {
			var data_array = [
				['<?php esc_html_e( 'Level', 'paid-memberships-pro' ); ?>', '<?php esc_html_e( 'Active Members', 'paid-memberships-pro' ); ?>'],
				<?php foreach ( $cols as $level_name => $active_members ) { ?>
					[
						<?php echo wp_json_encode( esc_html( $level_name ) ); ?>,
						<?php echo intval( $active_members ); ?>,
					],
				<?php } ?>
			];
			var data = google.visualization.arrayToDataTable( data_array );
			var options = {
				legend: {
					alignment: 'center',
					position: 'right',
					textStyle: {color: '#2D2D2D', fontSize: '14'}
				},
			};
			var chart = new google.visualization.PieChart( document.getElementById( 'chart_div' ) );
			chart.draw( data, options );
		}
	</script>
	<?php
}
