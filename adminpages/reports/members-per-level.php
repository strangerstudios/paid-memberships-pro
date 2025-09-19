<?php
/*
	PMPro Report
	Title: Members per Level
	Slug: members_per_level

	For each report, write three functions:
	* pmpro_report_{slug}_register() to register the widget (slug and title).
	* pmpro_report_{slug}_widget()   to show up on the report homepage.
	* pmpro_report_{slug}_page()     to show up when users click on the report page widget.
*/
function pmpro_report_members_per_level_register( $pmpro_reports ) {
	$pmpro_reports['members_per_level'] = __('Active Members Per Level', 'paid-memberships-pro' );

	return $pmpro_reports;
}

add_filter( 'pmpro_registered_reports', 'pmpro_report_members_per_level_register' );

// Enqueue Chart.js on report page
function pmpro_report_members_per_level_init() {
	if ( is_admin() && ( ( isset( $_REQUEST['report'] ) && $_REQUEST[ 'report' ] == 'members_per_level' ) || ( isset( $_REQUEST['page'] ) && $_REQUEST[ 'page' ] == 'pmpro-reports' ) ) ) {
		// Register Chart.js (CDN) and helper.
		wp_register_script( 'pmpro-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js', array(), '4.4.3', true );
	$pmpro_main = dirname( dirname( dirname( __FILE__ ) ) ) . '/paid-memberships-pro.php';
		// Register Chart.js Data Labels plugin for percentage labels on the pie chart.
		wp_register_script( 'pmpro-chartjs-datalabels', 'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js', array( 'pmpro-chartjs' ), '2.2.0', true );
		wp_register_script( 'pmpro-reports-charts', plugins_url( 'js/admin-reports-charts.js',  $pmpro_main ), array( 'pmpro-chartjs', 'pmpro-chartjs-datalabels', 'jquery' ), PMPRO_VERSION, true );
		wp_enqueue_script( 'pmpro-reports-charts' );
	}
}
add_action( 'init', 'pmpro_report_members_per_level_init' );

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
	<div style="width:100%; min-height: 320px;">
		<canvas id="pmpro-chart-members-per-level" style="height:320px;"></canvas>
	</div>
	<script>
	(function(){
		function render(){
			if (!window.pmproCharts) { return; }
			var labels = [
				<?php foreach ( $cols as $level_name => $_count ) { echo wp_json_encode( esc_html( $level_name ) ) . ","; } ?>
			];
			var data = [
				<?php foreach ( $cols as $_name => $active_members ) { echo intval( $active_members ) . ","; } ?>
			];
			if (!data.length) { return; }
			var total = data.reduce(function(sum, v){ return sum + v; }, 0);
			var colors = (window.pmproCharts && window.pmproCharts.palette) ? window.pmproCharts.palette : undefined;
			var cfg = {
				type: 'pie',
				data: {
					labels: labels,
					datasets: [{
						data: data,
						backgroundColor: colors && colors.length >= data.length ? colors.slice(0, data.length) : undefined
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: { position: 'right', labels: { color: '#2D2D2D', font: { size: 14 } } },
						tooltip: {
							callbacks: {
								label: function(ctx){
									var value = ctx.parsed; var pct = total ? ((value/total)*100).toFixed(1) : 0;
									return ctx.label + ': ' + value + ' (' + pct + '%)';
								}
							}
						},
						datalabels: {
							color: '#ffffff',
							font: { weight: '600' },
							formatter: function(value){
								var pct = total ? ((value/total)*100).toFixed(1) : 0;
								return pct + '%';
							}
						}
					}
				}
			};
			if (window.Chart && window.ChartDataLabels && !window.pmproChartsDataLabelsRegistered) {
				try { window.Chart.register(window.ChartDataLabels); window.pmproChartsDataLabelsRegistered = true; } catch(e) {}
			}
			pmproCharts.ensure('pmpro-chart-members-per-level', cfg);
		}
		if (document.readyState === 'complete') { render(); }
		else { window.addEventListener('load', render); }
	})();
	</script>
	<?php
}
