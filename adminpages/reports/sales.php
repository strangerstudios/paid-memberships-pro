<?php
/*
	PMPro Report
	Title: Sales
	Slug: sales

	For each report, add a line like:
	global $pmpro_reports;
	$pmpro_reports['slug'] = 'Title';

	For each report, also write two functions:
	* pmpro_report_{slug}_widget()   to show up on the report homepage.
	* pmpro_report_{slug}_page()     to show up when users click on the report page widget.
*/
global $pmpro_reports;
$gateway_environment = pmpro_getOption("gateway_environment");
if($gateway_environment == "sandbox")
	$pmpro_reports['sales'] = __('Sales and Revenue (Testing/Sandbox)', 'paid-memberships-pro' );
else
	$pmpro_reports['sales'] = __('Sales and Revenue', 'paid-memberships-pro' );

//queue Google Visualization JS on report page
function pmpro_report_sales_init()
{
	if ( is_admin() && isset( $_REQUEST['report'] ) && $_REQUEST[ 'report' ] == 'sales' && isset( $_REQUEST['page'] ) && $_REQUEST[ 'page' ] == 'pmpro-reports' ) {
		wp_enqueue_script( 'corechart', plugins_url( 'js/corechart.js',  plugin_dir_path( __DIR__ ) ) );
	}

}
add_action("init", "pmpro_report_sales_init");

//widget
function pmpro_report_sales_widget() {
	global $wpdb;
?>
<style>
	#pmpro_report_sales tbody td:last-child {text-align: right; }
</style>
<span id="pmpro_report_sales" class="pmpro_report-holder">
	<table class="wp-list-table widefat fixed">
	<thead>
		<tr>
			<th scope="col">&nbsp;</th>
			<th scope="col"><?php esc_html_e('Sales', 'paid-memberships-pro' ); ?></th>
			<th scope="col"><?php esc_html_e('Revenue', 'paid-memberships-pro' ); ?></th>
		</tr>
	</thead>
	<?php
		$reports = array(
			'today'      => __('Today', 'paid-memberships-pro' ),
			'this month' => __('This Month', 'paid-memberships-pro' ),
			'this year'  => __('This Year', 'paid-memberships-pro' ),
			'all time'   => __('All Time', 'paid-memberships-pro' ),
		);

	foreach ( $reports as $report_type => $report_name ) {
		//sale prices stats
		$count = 0;
		$max_prices_count = apply_filters( 'pmpro_admin_reports_max_sale_prices', 5 );
		$prices = pmpro_get_prices_paid( $report_type, $max_prices_count );
		?>
		<tbody>
			<tr class="pmpro_report_tr">
				<th scope="row">
					<?php if( ! empty( $prices ) ) { ?>
						<button class="pmpro_report_th pmpro_report_th_closed"><?php echo esc_html($report_name); ?></button>
					<?php } else { ?>
						<?php echo esc_html($report_name); ?>
					<?php } ?>
				</th>
				<td><?php echo esc_html( number_format_i18n( pmpro_getSales( $report_type, null, 'all' ) ) ); ?></td>
				<td><?php echo pmpro_escape_price( pmpro_formatPrice( pmpro_getRevenue( $report_type ) ) ); ?></td>
			</tr>
			<?php
				//sale prices stats
				$count = 0;
				$max_prices_count = apply_filters( 'pmpro_admin_reports_max_sale_prices', 5 );
				foreach ( $prices as $price => $quantity ) {
					if ( $count++ >= $max_prices_count ) {
						break;
					}
			?>
				<tr class="pmpro_report_tr_sub" style="display: none;">
					<th scope="row">- <?php echo pmpro_escape_price( pmpro_formatPrice( $price ) );?></th>
					<td><?php echo esc_html( number_format_i18n( $quantity['total'] ) ); ?></td>
					<td><?php echo pmpro_escape_price( pmpro_formatPrice( $price * $quantity['total'] ) ); ?></td>
				</tr>
			<?php
			}
			?>
		</tbody>
		<?php
	}
	?>
	</table>
	<?php if ( function_exists( 'pmpro_report_sales_page' ) ) { ?>
		<p class="pmpro_report-button">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports&report=sales' ) ); ?>"><?php esc_html_e('Details', 'paid-memberships-pro' );?></a>
		</p>
	<?php } ?>
</span>

<?php
}

function pmpro_report_sales_page()
{
	global $wpdb, $pmpro_currency_symbol, $pmpro_currency, $pmpro_currencies;

	//get values from form
	if(isset($_REQUEST['type']))
		$type = sanitize_text_field($_REQUEST['type']);
	else
		$type = "revenue";

	if($type == "sales")
		$type_function = "COUNT";
	else
		$type_function = "SUM";

	if(isset($_REQUEST['period']))
		$period = sanitize_text_field($_REQUEST['period']);
	else
		$period = "daily";

	if(isset($_REQUEST['month']))
		$month = intval($_REQUEST['month']);
	else
		$month = date_i18n("n", current_time('timestamp'));

	$thisyear = date_i18n("Y", current_time('timestamp'));
	if(isset($_REQUEST['year']))
		$year = intval($_REQUEST['year']);
	else
		$year = $thisyear;

	if(isset($_REQUEST['level']))
		$l = intval($_REQUEST['level']);
	else
		$l = "";

	if ( isset( $_REQUEST[ 'discount_code' ] ) ) {
		$discount_code = intval( $_REQUEST[ 'discount_code' ] );
	} else {
		$discount_code = '';
	}

	$currently_in_period = false;

	//calculate start date and how to group dates returned from DB
	if($period == "daily")
	{
		$startdate = $year . '-' . substr("0" . $month, strlen($month) - 1, 2) . '-01';
		$enddate = $year . '-' . substr("0" . $month, strlen($month) - 1, 2) . '-' . date_i18n('t', strtotime( $startdate ) );
		$date_function = 'DAY';
		$currently_in_period = ( intval( date( 'Y' ) ) == $year && intval( date( 'n' ) ) == $month );
	}
	elseif($period == "monthly")
	{
		$startdate = $year . '-01-01';
		$enddate = strval(intval($year)+1) . '-01-01';
		$date_function = 'MONTH';
		$currently_in_period = ( intval( date( 'Y' ) ) == $year );
	}
	else
	{
		$startdate = '1970-01-01';	//all time
		$date_function = 'YEAR';
		$currently_in_period = true;
	}

	//testing or live data
	$gateway_environment = pmpro_getOption("gateway_environment");

	// Get the estimated second offset to convert from GMT time to local.This is not perfect as daylight
	// savings time can come and go in the middle of a month, but it's a tradeoff that we are making
	// for performance so that we don't need to go through each order manually to calculate the local time.
	$tz_offset = strtotime( $startdate ) - strtotime( get_gmt_from_date( $startdate . " 00:00:00" ) );

	//get data
	$sqlQuery = "SELECT date,
				 	$type_function(mo1total) as value,
				 	$type_function( IF( mo2id IS NOT NULL, mo1total, NULL ) ) as renewals
				 FROM ";
	$sqlQuery .= "(";	// Sub query.
	$sqlQuery .= "SELECT $date_function( DATE_ADD( mo1.timestamp, INTERVAL $tz_offset SECOND ) ) as date,
					    mo1.id as mo1id,
						mo1.total as mo1total,
						mo2.id as mo2id
				 FROM $wpdb->pmpro_membership_orders mo1
				 	LEFT JOIN $wpdb->pmpro_membership_orders mo2 ON mo1.user_id = mo2.user_id
                        AND mo2.total > 0
                        AND mo2.status NOT IN('refunded', 'review', 'token', 'error')                                            
                        AND mo2.timestamp < mo1.timestamp
                        AND mo2.gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	if ( ! empty( $discount_code ) ) {
		$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON mo1.id = dc.order_id ";
	}

	$sqlQuery .= "WHERE mo1.total > 0
					AND mo1.timestamp >= DATE_ADD( '$startdate' , INTERVAL - $tz_offset SECOND )
					AND mo1.status NOT IN('refunded', 'review', 'token', 'error')
					AND mo1.gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	if(!empty($enddate))
		$sqlQuery .= "AND mo1.timestamp <= DATE_ADD( '$enddate 23:59:59' , INTERVAL - $tz_offset SECOND )";

	if(!empty($l))
		$sqlQuery .= "AND mo1.membership_id IN(" . esc_sql( $l ) . ") ";

	if ( ! empty( $discount_code ) ) {
		$sqlQuery .= "AND dc.code_id = '" . esc_sql( $discount_code ) . "' ";
	}

	$sqlQuery .= " GROUP BY mo1.id ";
	$sqlQuery .= ") t1";
	$sqlQuery .= " GROUP BY date ORDER by date";

	$dates = $wpdb->get_results($sqlQuery);
		
	//fill in blanks in dates
	$cols = array();
	$total_in_period = 0;
	$units_in_period = 0; // Used for averages.
	
	if($period == "daily")
	{
		$lastday = date_i18n("t", strtotime($startdate, current_time("timestamp")));
		$day_of_month = intval( date( 'j' ) );
		
		for($i = 1; $i <= $lastday; $i++)
		{
			$cols[$i] = array(0, 0);
			if ( ! $currently_in_period || $i < $day_of_month ) {
				$units_in_period++;
			}
			
			foreach($dates as $date)
			{
				if($date->date == $i) {
					$cols[$i] = array( $date->value, $date->renewals );
					if ( ! $currently_in_period || $i < $day_of_month ) {
						$total_in_period += $date->value;
					}
				}	
			}
		}
	}
	elseif($period == "monthly")
	{
		$month_of_year = intval( date( 'n' ) );
		for($i = 1; $i < 13; $i++)
		{
			$cols[$i] = array(0, 0);
			if ( ! $currently_in_period || $i < $month_of_year ) {
				$units_in_period++;
			}

			foreach($dates as $date)
			{
				if($date->date == $i) {
					$cols[$i] = array( $date->value, $date->renewals );
					if ( ! $currently_in_period || $i < $month_of_year ) {
						$total_in_period += $date->value;
					}
				}
			}
		}
	}
	else //annual
	{
		//get min and max years
		$min = 9999;
		$max = 0;
		foreach($dates as $date)
		{
			$min = min($min, $date->date);
			$max = max($max, $date->date);
		}

		$current_year = intval( date( 'Y' ) );
		for($i = $min; $i <= $max; $i++)
		{
			if ( $i < $current_year ) {
				$units_in_period++;
			}
			foreach($dates as $date)
			{
				if($date->date == $i) {
					$cols[$i] = array( $date->value, $date->renewals );
					if ( $i < $current_year ) {
						$total_in_period += $date->value;
					}
				}
			}
		}
	}
	
	$average = 0;
	if ( 0 !== $units_in_period ) {
		$average = $total_in_period / $units_in_period; // Not including this unit.
	}
	?>
	<form id="posts-filter" method="get" action="">
	<h1>
		<?php _e('Sales and Revenue', 'paid-memberships-pro' );?>
	</h1>

	<div class="tablenav top">
		<?php _e('Show', 'paid-memberships-pro' )?>
		<select id="period" name="period">
			<option value="daily" <?php selected($period, "daily");?>><?php esc_html_e('Daily', 'paid-memberships-pro' );?></option>
			<option value="monthly" <?php selected($period, "monthly");?>><?php esc_html_e('Monthly', 'paid-memberships-pro' );?></option>
			<option value="annual" <?php selected($period, "annual");?>><?php esc_html_e('Annual', 'paid-memberships-pro' );?></option>
		</select>
		<select name="type">
			<option value="revenue" <?php selected($type, "revenue");?>><?php esc_html_e('Revenue', 'paid-memberships-pro' );?></option>
			<option value="sales" <?php selected($type, "sales");?>><?php esc_html_e('Sales', 'paid-memberships-pro' );?></option>
		</select>
		<span id="for"><?php esc_html_e('for', 'paid-memberships-pro' )?></span>
		<select id="month" name="month">
			<?php for($i = 1; $i < 13; $i++) { ?>
				<option value="<?php echo esc_attr( $i );?>" <?php selected($month, $i);?>><?php echo esc_html(date_i18n("F", mktime(0, 0, 0, $i, 2)));?></option>
			<?php } ?>
		</select>
		<select id="year" name="year">
			<?php for($i = $thisyear; $i > 2007; $i--) { ?>
				<option value="<?php echo esc_attr( $i );?>" <?php selected($year, $i);?>><?php echo esc_html( $i );?></option>
			<?php } ?>
		</select>
		<span id="for"><?php esc_html_e('for', 'paid-memberships-pro' )?></span>
		<select id="level" name="level">
			<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>><?php esc_html_e('All Levels', 'paid-memberships-pro' );?></option>
			<?php
				$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
				$levels = pmpro_sort_levels_by_order( $levels );
				foreach($levels as $level)
				{
			?>
				<option value="<?php echo esc_attr( $level->id ); ?>" <?php if($l == $level->id) { ?>selected="selected"<?php } ?>><?php echo esc_html( $level->name); ?></option>
			<?php
				}
			?>
		</select>
		<?php
		$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM $wpdb->pmpro_discount_codes ";
		$sqlQuery .= "ORDER BY id DESC ";
		$codes = $wpdb->get_results($sqlQuery, OBJECT);
		if ( ! empty( $codes ) ) { ?>
		<select id="discount_code" name="discount_code">
			<option value="" <?php if ( empty( $discount_code ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e('All Codes', 'paid-memberships-pro' );?></option>
			<?php foreach ( $codes as $code ) { ?>
				<option value="<?php echo esc_attr( $code->id ); ?>" <?php selected( $discount_code, $code->id ); ?>><?php echo esc_html( $code->code ); ?></option>
			<?php } ?>
		</select>
		<?php } ?>
		<input type="hidden" name="page" value="pmpro-reports" />
		<input type="hidden" name="report" value="sales" />
		<input type="submit" class="button action" value="<?php esc_attr_e('Generate Report', 'paid-memberships-pro' );?>" />
		<br class="clear" />
	</div>
	<div class="pmpro_chart_area">
		<div id="chart_div"></div>
		<div class="pmpro_chart_description"><p><center><em><?php esc_html_e( 'Average line calculated using data prior to current day, month, or year.', 'paid-memberships-pro' ); ?></em></center></p></div>
	</div>
	<script>
		//update month/year when period dropdown is changed
		jQuery(document).ready(function() {
			jQuery('#period').change(function() {
				pmpro_ShowMonthOrYear();
			});
		});

		function pmpro_ShowMonthOrYear()
		{
			var period = jQuery('#period').val();
			if(period == 'daily')
			{
				jQuery('#for').show();
				jQuery('#month').show();
				jQuery('#year').show();
			}
			else if(period == 'monthly')
			{
				jQuery('#for').show();
				jQuery('#month').hide();
				jQuery('#year').show();
			}
			else
			{
				jQuery('#for').hide();
				jQuery('#month').hide();
				jQuery('#year').hide();
			}
		}

		pmpro_ShowMonthOrYear();

		//draw the chart
		google.charts.load('current', {'packages':['corechart']});
		google.charts.setOnLoadCallback(drawVisualization);
		function drawVisualization() {
			var dataTable = new google.visualization.DataTable();
			dataTable.addColumn('string', <?php echo wp_json_encode( esc_html( $date_function ) ); ?>);
			dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
			dataTable.addColumn('number', <?php echo wp_json_encode( esc_html__( 'Renewals', 'paid-memberships-pro' ) ); ?>);
			dataTable.addColumn('number', <?php echo wp_json_encode( esc_html( sprintf( __( 'New %s', 'paid-memberships-pro' ), ucwords( $type ) ) ) ); ?>);
			<?php if ( $type === 'sales' ) { ?>
				dataTable.addColumn('number', <?php echo wp_json_encode( esc_html( sprintf( __( 'Average: %s', 'paid-memberships-pro' ), number_format_i18n( $average, 2 ) ) ) ); ?>);
			<?php } else { ?>
				dataTable.addColumn('number', <?php echo wp_json_encode( sprintf( esc_html__( 'Average: %s', 'paid-memberships-pro' ), pmpro_escape_price( html_entity_decode( pmpro_formatPrice( $average ) ) ) ) ); ?>);
			<?php } ?>
			dataTable.addRows([
				<?php foreach($cols as $date => $value) { ?>
					[
						<?php
							$date_value = $date;

							if ( $period === 'monthly' ) {
								$date_value = date_i18n( 'M', mktime( 0, 0, 0, $date, 2 ) );
							}

							echo wp_json_encode( esc_html( $date_value ) );
						?>,
						createCustomHTMLContent(
							<?php
								$date_value = $date;

								if ( $period === 'monthly' ) {
									$date_value = date_i18n( 'F', mktime( 0, 0, 0, $date, 2 ) );
								} elseif ( $period === 'daily' ) {
									$date_value = date_i18n( get_option( 'date_format' ), strtotime( $year . '-' . $month . '-' . $date ) );
								}

								echo wp_json_encode( esc_html( $date_value ) );
							?>,
							<?php if ( $type === 'sales' ) { ?>
								<?php echo wp_json_encode( (int) $value[1] ); ?>,
								<?php echo wp_json_encode( (int) $value[0] - $value[1] ); ?>,
								<?php echo wp_json_encode( (int) $value[0] ); ?>,
							<?php } else { ?>
								<?php echo wp_json_encode( pmpro_escape_price( pmpro_formatPrice( $value[1] ) ) ); ?>,
								<?php echo wp_json_encode( pmpro_escape_price( pmpro_formatPrice( $value[0] - $value[1] ) ) ); ?>,
								<?php echo wp_json_encode( pmpro_escape_price( pmpro_formatPrice( $value[0] ) ) ); ?>,
							<?php } ?>
						),
						<?php if ( $type === 'sales' ) { ?>
							<?php echo wp_json_encode( (int) $value[1] ); ?>,
							<?php echo wp_json_encode( (int) $value[0] - $value[1] ); ?>,
							<?php echo wp_json_encode( (int) $average ); ?>,
						<?php } else { ?>
							<?php echo wp_json_encode( pmpro_round_price( $value[1] ) ); ?>,
							<?php echo wp_json_encode( pmpro_round_price( $value[0] - $value[1] ) ); ?>,
							<?php echo wp_json_encode( pmpro_round_price( $average ) ); ?>,
						<?php } ?>
					],
				<?php } ?>
			]);

			var options = {
				title: pmpro_report_title_sales(),
				titlePosition: 'top',
				titleTextStyle: {
					color: '#555555',
				},
				legend: {position: 'bottom'},
				colors: ['<?php
					if ( $type === 'sales') {
						echo '#006699'; // Blue for "Sales" chart.
					} else {
						echo '#31825D'; // Green for "Revenue" chart.
					}
				?>'],
				chartArea: {
					width: '90%',
				},
				focusTarget: 'category',
				tooltip: {
					isHtml: true
				},
				hAxis: {
					textStyle: {
						color: '#555555',
						fontSize: '12',
						italic: false,
					},
				},
				vAxis: {
					<?php if ( $type === 'sales') { ?>
						format: '0',
					<?php } ?>
					textStyle: {
						color: '#555555',
						fontSize: '12',
						italic: false,
					},
				},
				seriesType: 'bars',
				series: {
					2: {
						type: 'line',
						color: '#B00000',
						enableInteractivity: false,
						lineDashStyle: [4, 1], 
					},
					1: {<?php
						if ( $type === 'sales') {
							echo "color: '#0099C6'"; // Lighter Blue for "Sales" chart.
						} else {
							echo "color: '#5EC16C'"; // Lighter Green for "Revenue" chart.
						} ?>
					},
				},
				isStacked: true,
			};

			var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
			chart.draw(dataTable, options);
		}

		function createCustomHTMLContent(period, renewals, notRenewals, total) {
			return '<div style="padding:15px; font-size: 14px; line-height: 20px; color: #000000;">' +
				'<strong>' + period + '</strong><br/>' +
				'<ul style="margin-bottom: 0px;">' +
				'<li><span style="margin-right: 3px;">' +
				<?php echo wp_json_encode( esc_html__( 'New:', 'paid-memberships-pro' ) ); ?> +
				'</span>' + notRenewals + '</li>' +
				'<li><span style="margin-right: 3px;">' +
				<?php echo wp_json_encode( esc_html__( 'Renewals:', 'paid-memberships-pro' ) ); ?> +
				'</span>' + renewals + '</li>' +
				'<li style="border-top: 1px solid #CCC; margin-bottom: 0px; margin-top: 8px; padding-top: 8px;"><span style="margin-right: 3px;">' +
				<?php echo wp_json_encode( esc_html__( 'Total:', 'paid-memberships-pro' ) ); ?> +
				'</span>' + total + '</li>' + '</ul>' + '</div>';
		}
		function pmpro_report_title_sales() {
			<?php
				if ( ! empty( $month ) && $period === 'daily' ) {
					$date = date_i18n( 'F', mktime(0, 0, 0, $month, 2) ) . ' ' . $year;
				} elseif( ! empty( $year ) && $period === 'monthly'  ) {
					$date = $year;
				} else {
					$date = __( 'All Time', 'paid-memberships-pro' );
				}
			?>
			return <?php echo wp_json_encode( esc_html( sprintf( __( '%s %s for %s', 'paid-memberships-pro' ), ucwords( $period ), ucwords( $type ), ucwords( $date ) ) ) ); ?>;
		}
	</script>

	</form>
	<?php
}

/*
	Other code required for your reports. This file is loaded every time WP loads with PMPro enabled.
*/

//get sales
function pmpro_getSales( $period = 'all time', $levels = 'all', $type = 'all' ) {	
	//check for a transient
	$cache = get_transient( 'pmpro_report_sales' );
	$param_hash = md5( $period . ' ' . $type . PMPRO_VERSION );
	if(!empty($cache) && isset($cache[$param_hash]) && isset($cache[$param_hash][$levels]))
		return $cache[$param_hash][$levels];

	//a sale is an order with status NOT IN('refunded', 'review', 'token', 'error') with a total > 0
	if($period == "today")
		$startdate = date_i18n("Y-m-d", current_time('timestamp'));
	elseif($period == "this month")
		$startdate = date_i18n("Y-m", current_time('timestamp')) . "-01";
	elseif($period == "this year")
		$startdate = date_i18n("Y", current_time('timestamp')) . "-01-01";
	else
		$startdate = date_i18n("Y-m-d", 0);

	$gateway_environment = pmpro_getOption("gateway_environment");

	// Convert from local to UTC.
	$startdate = get_gmt_from_date( $startdate );

	// Build the query.
	global $wpdb;
	$sqlQuery = "SELECT mo1.id FROM $wpdb->pmpro_membership_orders mo1 ";
	
	// Need to join on older orders if we're looking for renewals or new sales.
	if ( $type !== 'all' ) {
		$sqlQuery .= "LEFT JOIN $wpdb->pmpro_membership_orders mo2 ON mo1.user_id = mo2.user_id
                        AND mo2.total > 0
                        AND mo2.status NOT IN('refunded', 'review', 'token', 'error')                                            
                        AND mo2.timestamp < mo1.timestamp
                        AND mo2.gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";
	}
	
	// Get valid orders within the time frame.
	$sqlQuery .= "WHERE mo1.total > 0
				 	AND mo1.status NOT IN('refunded', 'review', 'token', 'error')
					AND mo1.timestamp >= '" . esc_sql( $startdate ) . "'
					AND mo1.gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";									

	// Restrict by level.
	if( ! empty( $levels ) && $levels != 'all' ) {
		$sqlQuery .= "AND mo1.membership_id IN(" . esc_sql( $levels ) . ") ";
	}		
	
	// Filter to renewals or new orders only. 	
	if ( $type == 'renewals' ) {
		$sqlQuery .= "AND mo2.id IS NOT NULL ";
	} elseif ( $type == 'new' ) {
		$sqlQuery .= "AND mo2.id IS NULL ";
	}

	// Group so we get one mo1 order per row.
	$sqlQuery .= "GROUP BY mo1.id ";

	// We want the count of rows produced, so update the query.
	$sqlQuery = "SELECT COUNT(*) FROM (" . $sqlQuery . ") as t1";

	$sales = $wpdb->get_var($sqlQuery);

	//save in cache
	if(!empty($cache) && isset($cache[$param_hash])) {
		$cache[$param_hash][$levels] = (int)$sales;
	} elseif(!empty($cache))
		$cache[$param_hash] = array($levels => $sales);
	else
		$cache = array($param_hash => array($levels => $sales));

	set_transient( 'pmpro_report_sales', $cache, 3600*24 );

	return $sales;
}

/**
 * Gets an array of all prices paid in a time period
 *
 * @param  string $period Time period to query (today, this month, this year, all time)
 * @param  int    $count  Number of prices to query and return.
 */
function pmpro_get_prices_paid( $period, $count = NULL ) {
	// Check for a transient.
	$cache = get_transient( 'pmpro_report_prices_paid' );
	$param_hash = md5( $period . $count . PMPRO_VERSION );
	if ( ! empty( $cache ) && ! empty( $cache[$param_hash] ) ) {
		return $cache[$param_hash];
	}

	// A sale is an order with status NOT IN('refunded', 'review', 'token', 'error') with a total > 0.
	if ( 'today' === $period ) {
		$startdate = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
	} elseif ( 'this month' === $period ) {
		$startdate = date_i18n( 'Y-m', current_time( 'timestamp' ) ) . '-01';
	} elseif ( 'this year' === $period ) {
		$startdate = date_i18n( 'Y', current_time( 'timestamp' ) ) . '-01-01';
	} else {
		$startdate = '1970-01-01';
	}

	// Convert from local to UTC.
	$startdate = get_gmt_from_date( $startdate );

	$gateway_environment = pmpro_getOption( 'gateway_environment' );

	// Build query.
	global $wpdb;
	$sql_query = "SELECT ROUND(total,8) as rtotal, COUNT(*) as num FROM $wpdb->pmpro_membership_orders WHERE total > 0 AND status NOT IN('refunded', 'review', 'token', 'error') AND timestamp >= '" . $startdate . "' AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	// Restrict by level.
	if ( ! empty( $levels ) ) {
		$sql_query .= 'AND membership_id IN(' . $levels . ') ';
	}

	$sql_query .= ' GROUP BY rtotal ORDER BY num DESC ';

	$prices           = $wpdb->get_results( $sql_query );
	
	if( !empty( $count) ) {
		$prices = array_slice( $prices, 0, $count, true );
	}
	
	$prices_formatted = array();
	foreach ( $prices as $price ) {
		if ( isset( $price->rtotal ) ) {
			// Total sales.
			$sql_query = "SELECT COUNT(*)
						  FROM $wpdb->pmpro_membership_orders
						  WHERE ROUND(total, 8) = '" . esc_sql( $price->rtotal ) . "'
						  	AND status NOT IN('refunded', 'review', 'token', 'error')
							AND timestamp >= '" . esc_sql( $startdate ) . "'
							AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";
			$total = $wpdb->get_var( $sql_query );
			
			/* skipping this until we figure out how to make it performant
			// New sales.
			$sql_query = "SELECT mo1.id
						  FROM $wpdb->pmpro_membership_orders mo1
						  	LEFT JOIN $wpdb->pmpro_membership_orders mo2
								ON mo1.user_id = mo2.user_id
								AND mo2.total > 0
								AND mo2.status NOT IN('refunded', 'review', 'token', 'error')
								AND mo2.timestamp < mo1.timestamp
								AND mo1.gateway_environment = '" . esc_sql( $gateway_environment ) . "'
						   WHERE ROUND(mo1.total, 8) = '" . esc_sql( $price->rtotal ) . "'
						  	AND mo1.status NOT IN('refunded', 'review', 'token', 'error')
							AND mo1.timestamp >= '" . esc_sql( $startdate ) . "'
							AND mo1.gateway_environment = '" . esc_sql( $gateway_environment ) . "'
							AND mo2.id IS NULL
						  GROUP BY mo1.id ";
			$sql_query = "SELECT COUNT(*) FROM (" . $sql_query . ") as t1";			
			$new = $wpdb->get_var( $sql_query );
			
			// Renewals.			
			$sql_query = "SELECT mo1.id
						  FROM $wpdb->pmpro_membership_orders mo1
						  	LEFT JOIN $wpdb->pmpro_membership_orders mo2
								ON mo1.user_id = mo2.user_id
								AND mo2.total > 0
								AND mo2.status NOT IN('refunded', 'review', 'token', 'error')
								AND mo2.timestamp < mo1.timestamp
								AND mo1.gateway_environment = '" . esc_sql( $gateway_environment ) . "'
						   WHERE ROUND(mo1.total, 8) = '" . esc_sql( $price->rtotal ) . "'
						  	AND mo1.status NOT IN('refunded', 'review', 'token', 'error')
							AND mo1.timestamp >= '" . esc_sql( $startdate ) . "'
							AND mo1.gateway_environment = '" . esc_sql( $gateway_environment ) . "'
							AND mo2.id IS NOT NULL
						  GROUP BY mo1.id ";
			$sql_query = "SELECT COUNT(*) FROM (" . $sql_query . ") as t1";			
			$renewals = $wpdb->get_var( $sql_query );
			
			$prices_formatted[ $price->rtotal ] = array( 'total' => $total, 'new' => $new, 'renewals' => $renewals );
			*/
			$prices_formatted[ $price->rtotal ] = array( 'total' => $total );
		}
	}

	krsort( $prices_formatted );

	// Save in cache.
	if ( ! empty( $cache ) ) {
		$cache[$param_hash] = $prices_formatted;
	} else {
		$cache = array($param_hash => $prices_formatted );
	}

	set_transient( 'pmpro_report_prices_paid', $cache, 3600 * 24 );

	return $prices_formatted;
}

//get revenue
function pmpro_getRevenue( $period, $levels = NULL, $type = 'all' ) {
	//check for a transient
	$cache = get_transient("pmpro_report_revenue");
	$param_hash = md5( $period . ' ' . $type . PMPRO_VERSION );
	if(!empty($cache) && isset($cache[$param_hash]) && isset($cache[$param_hash][$levels]))
		return $cache[$param_hash][$levels];

	//a sale is an order with status NOT IN('refunded', 'review', 'token', 'error')
	if($period == "today")
		$startdate = date_i18n("Y-m-d", current_time('timestamp'));
	elseif($period == "this month")
		$startdate = date_i18n("Y-m", current_time('timestamp')) . "-01";
	elseif($period == "this year")
		$startdate = date_i18n("Y", current_time('timestamp')) . "-01-01";
	else
		$startdate = date_i18n("Y-m-d", 0);

	// Convert from local to UTC.
	$startdate = get_gmt_from_date( $startdate );

	$gateway_environment = pmpro_getOption("gateway_environment");

	// Build query.
	global $wpdb;
	$sqlQuery = "SELECT mo1.total as total
				 FROM $wpdb->pmpro_membership_orders mo1 ";

	// Need to join on older orders if we're looking for renewals or new sales.			
	if ( $type != 'all' ) {
		$sqlQuery .= "LEFT JOIN $wpdb->pmpro_membership_orders mo2
					 	ON mo1.user_id = mo2.user_id
						AND mo2.total > 0
						AND mo2.status NOT IN('refunded', 'review', 'token', 'error')
						AND mo2.timestamp < mo1.end_timestamp
						AND mo2.gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";
	}
	
	// Get valid orders within the timeframe.		 
	$sqlQuery .= "WHERE mo1.status NOT IN('refunded', 'review', 'token', 'error')
				 	AND mo1.timestamp >= '" . esc_sql( $startdate ) . "'
					AND mo1.gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	// Restrict by level.
	if(!empty($levels))
		$sqlQuery .= "AND mo1.membership_id IN(" . $levels . ") ";
		
	// Filter to renewals or new orders only. 	
	if ( $type == 'renewals' ) {
		$sqlQuery .= "AND mo2.id IS NOT NULL ";
	} elseif ( $type == 'new' ) {
		$sqlQuery .= "AND mo2.id IS NULL ";
	}

	// Group so we get one mo1 order per row.
	$sqlQuery .= "GROUP BY mo1.id ";
	
	// Want the total across the orders found.
	$sqlQuery = "SELECT SUM(total) FROM(" . $sqlQuery . ") as t1";
	
	$revenue = pmpro_round_price( $wpdb->get_var($sqlQuery) );

	//save in cache
	if(!empty($cache) && !empty($cache[$param_hash]))
		$cache[$param_hash][$levels] = $revenue;
	elseif(!empty($cache))
		$cache[$param_hash] = array($levels => $revenue);
	else
		$cache = array($param_hash => array($levels => $revenue));

	set_transient("pmpro_report_revenue", $cache, 3600*24);

	return $revenue;
}

/**
 * Get revenue between dates.
 *
 * @param  string $start_date to track revenue from.
 * @param  string $end_date to track revenue until. Defaults to current date. YYYY-MM-DD format.
 * @param  array  $level_ids to include in report. Defaults to all.
 * @return float  revenue.
 */
function pmpro_get_revenue_between_dates( $start_date, $end_date = '', $level_ids = null ) {
	global $wpdb;
	$sql_query = "SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE status NOT IN('refunded', 'review', 'token', 'error') AND timestamp >= '" . esc_sql( $start_date ) . " 00:00:00'";
	if ( ! empty( $end_date ) ) {
		$sql_query .= " AND timestamp <= '" . esc_sql( $end_date ) . " 23:59:59'";
	}
	if ( ! empty( $level_ids ) ) {
		$sql_query .= ' AND membership_id IN(' . implode( ', ', $levels ) . ') ';
	}
	return $wpdb->get_var($sql_query);
}

//delete transients when an order goes through
function pmpro_report_sales_delete_transients()
{
	delete_transient( 'pmpro_report_sales' );
	delete_transient( 'pmpro_report_revenue' );
	delete_transient( 'pmpro_report_prices_paid' );
}
add_action("pmpro_after_checkout", "pmpro_report_sales_delete_transients");
add_action("pmpro_updated_order", "pmpro_report_sales_delete_transients");
