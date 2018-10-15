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
	if(is_admin() && isset($_REQUEST['report']) && $_REQUEST['report'] == "sales" && isset($_REQUEST['page']) && $_REQUEST['page'] == "pmpro-reports")
	{
		wp_enqueue_script( 'jsapi', plugins_url( 'js/jsapi.js',  plugin_dir_path( __DIR__ ) ) );
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
<span id="pmpro_report_sales">
	<table class="wp-list-table widefat fixed striped">
	<thead>
		<tr>
			<th scope="col">&nbsp;</th>
			<th scope="col"><?php _e('Sales', 'paid-memberships-pro' ); ?></th>
			<th scope="col"><?php _e('Revenue', 'paid-memberships-pro' ); ?></th>
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
		?>
		<tbody>
			<tr class="pmpro_report_tr">
				<th scope="row"><button class="pmpro_report_th pmpro_report_th_closed"><?php echo $report_name; ?></button></th>
				<td><?php echo number_format_i18n( pmpro_getSales( $report_type ) ); ?></td>
				<td><?php echo pmpro_formatPrice( pmpro_getRevenue( $report_type ) ); ?></td>
			</tr>
			<?php
				//sale prices stats
				$count = 0;
				$max_prices_count = apply_filters( 'pmpro_admin_reports_max_sale_prices', 5 );
				$prices = pmpro_get_prices_paid( $report_type, $max_prices_count );
				foreach ( $prices as $price => $quantitiy ) {
					if ( $count++ >= $max_prices_count ) {
						break;
					}
			?>
				<tr class="pmpro_report_tr_sub" style="display: none;">
					<th scope="row">- <?php echo pmpro_formatPrice( $price );?></th>
					<td><?php echo number_format_i18n( $quantitiy ); ?></td>
					<td><?php echo pmpro_formatPrice( $price * $quantitiy ); ?></td>
				</tr>
			<?php
			}
			?>
		</tbody>
		<?php
	}
	?>
	</table>
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

	//calculate start date and how to group dates returned from DB
	if($period == "daily")
	{
		$startdate = $year . '-' . substr("0" . $month, strlen($month) - 1, 2) . '-01';
		$enddate = $year . '-' . substr("0" . $month, strlen($month) - 1, 2) . '-32';
		$date_function = 'DAY';
	}
	elseif($period == "monthly")
	{
		$startdate = $year . '-01-01';
		$enddate = strval(intval($year)+1) . '-01-01';
		$date_function = 'MONTH';
	}
	else
	{
		$startdate = '1960-01-01';	//all time
		$date_function = 'YEAR';
	}

	//testing or live data
	$gateway_environment = pmpro_getOption("gateway_environment");

	//get data
	$sqlQuery = "SELECT $date_function(timestamp) as date, $type_function(total) as value FROM $wpdb->pmpro_membership_orders WHERE total > 0 AND timestamp >= '" . esc_sql( $startdate ) . "' AND status NOT IN('refunded', 'review', 'token', 'error') AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	if(!empty($enddate))
		$sqlQuery .= "AND timestamp < '" . esc_sql( $enddate ) . "' ";

	if(!empty($l))
		$sqlQuery .= "AND membership_id IN(" . esc_sql( $l ) . ") ";

	$sqlQuery .= " GROUP BY date ORDER BY date ";

	$dates = $wpdb->get_results($sqlQuery);

	//fill in blanks in dates
	$cols = array();
	if($period == "daily")
	{
		$lastday = date_i18n("t", strtotime($startdate, current_time("timestamp")));

		for($i = 1; $i <= $lastday; $i++)
		{
			$cols[$i] = 0;
			foreach($dates as $date)
			{
				if($date->date == $i)
					$cols[$i] = $date->value;
			}
		}
	}
	elseif($period == "monthly")
	{
		for($i = 1; $i < 13; $i++)
		{
			$cols[$i] = 0;
			foreach($dates as $date)
			{
				if($date->date == $i)
					$cols[$i] = $date->value;
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

		for($i = $min; $i <= $max; $i++)
		{
			foreach($dates as $date)
			{
				if($date->date == $i)
					$cols[$i] = $date->value;
			}
		}
	}
	?>
	<form id="posts-filter" method="get" action="">
	<h1>
		<?php _e('Sales and Revenue', 'paid-memberships-pro' );?>
	</h1>

	<div class="tablenav top">
		<?php _e('Show', 'paid-memberships-pro' )?>
		<select id="period" name="period">
			<option value="daily" <?php selected($period, "daily");?>><?php _e('Daily', 'paid-memberships-pro' );?></option>
			<option value="monthly" <?php selected($period, "monthly");?>><?php _e('Monthly', 'paid-memberships-pro' );?></option>
			<option value="annual" <?php selected($period, "annual");?>><?php _e('Annual', 'paid-memberships-pro' );?></option>
		</select>
		<select name="type">
			<option value="revenue" <?php selected($type, "revenue");?>><?php _e('Revenue', 'paid-memberships-pro' );?></option>
			<option value="sales" <?php selected($type, "sales");?>><?php _e('Sales', 'paid-memberships-pro' );?></option>
		</select>
		<span id="for"><?php _e('for', 'paid-memberships-pro' )?></span>
		<select id="month" name="month">
			<?php for($i = 1; $i < 13; $i++) { ?>
				<option value="<?php echo $i;?>" <?php selected($month, $i);?>><?php echo date_i18n("F", mktime(0, 0, 0, $i, 2));?></option>
			<?php } ?>
		</select>
		<select id="year" name="year">
			<?php for($i = $thisyear; $i > 2007; $i--) { ?>
				<option value="<?php echo $i;?>" <?php selected($year, $i);?>><?php echo $i;?></option>
			<?php } ?>
		</select>
		<span id="for"><?php _e('for', 'paid-memberships-pro' )?></span>
		<select name="level">
			<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>><?php _e('All Levels', 'paid-memberships-pro' );?></option>
			<?php
				$levels = $wpdb->get_results("SELECT id, name FROM $wpdb->pmpro_membership_levels ORDER BY name");
				foreach($levels as $level)
				{
			?>
				<option value="<?php echo $level->id?>" <?php if($l == $level->id) { ?>selected="selected"<?php } ?>><?php echo $level->name?></option>
			<?php
				}
			?>
		</select>

		<input type="hidden" name="page" value="pmpro-reports" />
		<input type="hidden" name="report" value="sales" />
		<input type="submit" class="button action" value="<?php _e('Generate Report', 'paid-memberships-pro' );?>" />
	</div>

	<div id="chart_div" style="clear: both; width: 100%; height: 500px;"></div>

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
		google.load("visualization", "1", {packages:["corechart"]});
		google.setOnLoadCallback(drawChart);
		function drawChart() {

			var data = google.visualization.arrayToDataTable([
			  ['<?php echo $date_function;?>', '<?php echo ucwords($type);?>'],
			  <?php foreach($cols as $date => $value) { ?>
				['<?php if($period == "monthly") echo date_i18n("M", mktime(0,0,0,$date,2)); else echo $date;?>', <?php echo $value;?>],
			  <?php } ?>
			]);

			var options = {
			  colors: ['#51a351', '#387038'],
			  hAxis: {title: '<?php echo $date_function;?>', titleTextStyle: {color: 'black'}, maxAlternation: 1},
			  vAxis: {color: 'green', titleTextStyle: {color: '#51a351'}},
			};

			<?php
				if($type != "sales")
				{
					if(pmpro_getCurrencyPosition() == "right")
						$position = "suffix";
					else
						$position = "prefix";
					?>
					var formatter = new google.visualization.NumberFormat({<?php echo $position;?>: '<?php echo html_entity_decode($pmpro_currency_symbol);?>'});
					formatter.format(data, 1);
					<?php
				}
			?>

			var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
			chart.draw(data, options);
		}
	</script>

	</form>
	<?php
}

/*
	Other code required for your reports. This file is loaded every time WP loads with PMPro enabled.
*/

//get sales
function pmpro_getSales($period, $levels = NULL)
{
	//check for a transient
	$cache = get_transient("pmpro_report_sales");
	if(!empty($cache) && !empty($cache[$period]) && !empty($cache[$period][$levels]))
		return $cache[$period][$levels];

	//a sale is an order with status NOT IN('refunded', 'review', 'token', 'error') with a total > 0
	if($period == "today")
		$startdate = date_i18n("Y-m-d", current_time('timestamp'));
	elseif($period == "this month")
		$startdate = date_i18n("Y-m", current_time('timestamp')) . "-01";
	elseif($period == "this year")
		$startdate = date_i18n("Y", current_time('timestamp')) . "-01-01";
	else
		$startdate = "";

	$gateway_environment = pmpro_getOption("gateway_environment");

	//build query
	global $wpdb;
	$sqlQuery = "SELECT COUNT(*) FROM $wpdb->pmpro_membership_orders WHERE total > 0 AND status NOT IN('refunded', 'review', 'token', 'error') AND timestamp >= '" . esc_sql( $startdate ) . "' AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	//restrict by level
	if(!empty($levels))
		$sqlQuery .= "AND membership_id IN(" . esc_sql( $levels ) . ") ";

	$sales = $wpdb->get_var($sqlQuery);

	//save in cache
	if(!empty($cache) && !empty($cache[$period]))
		$cache[$period][$levels] = $sales;
	elseif(!empty($cache))
		$cache[$period] = array($levels => $sales);
	else
		$cache = array($period => array($levels => $sales));

	set_transient("pmpro_report_sales", $cache, 3600*24);

	return $sales;
}

/**
 * Gets an array of all prices paid in a time period
 *
 * @param  string $period time period to query.
 */
function pmpro_get_prices_paid( $period, $count = NULL ) {
	// Check for a transient.
	$cache = get_transient( 'pmpro_report_prices_paid' );
	if ( ! empty( $cache ) && ! empty( $cache[ $period . $count ] ) ) {
		return $cache[ $period . $count ];
	}

	// A sale is an order with status NOT IN('refunded', 'review', 'token', 'error') with a total > 0.
	if ( 'today' === $period ) {
		$startdate = date_i18n( 'Y-m-d', current_time( 'timestamp' ) );
	} elseif ( 'this month' === $period ) {
		$startdate = date_i18n( 'Y-m', current_time( 'timestamp' ) ) . '-01';
	} elseif ( 'this year' === $period ) {
		$startdate = date_i18n( 'Y', current_time( 'timestamp' ) ) . '-01-01';
	} else {
		$startdate = '';
	}

	$gateway_environment = pmpro_getOption( 'gateway_environment' );

	// Build query.
	global $wpdb;
	$sql_query = "SELECT total, COUNT(*) as num FROM $wpdb->pmpro_membership_orders WHERE total > 0 AND status NOT IN('refunded', 'review', 'token', 'error') AND timestamp >= '" . $startdate . "' AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	// Restrict by level.
	if ( ! empty( $levels ) ) {
		$sql_query .= 'AND membership_id IN(' . $levels . ') ';
	}

	$sql_query .= ' GROUP BY total ORDER BY num DESC ';

	$prices           = $wpdb->get_results( $sql_query );

	if( !empty( $count) ) {
		$prices = array_slice( $prices, 0, $count, true );
	}

	$prices_formatted = array();
	foreach ( $prices as $price ) {
		if ( isset( $price->total ) ) {
			$sql_query                         = "SELECT COUNT(*) FROM $wpdb->pmpro_membership_orders WHERE total = '" . esc_sql( $price->total ) . "' AND status NOT IN('refunded', 'review', 'token', 'error') AND timestamp >= '" . esc_sql( $startdate ) . "' AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";
			$sales                             = $wpdb->get_var( $sql_query );
			$prices_formatted[ $price->total ] = $sales;
		}
	}

	krsort( $prices_formatted );

	// Save in cache.
	if ( ! empty( $cache ) ) {
		$cache[ $period . $count ] = $prices_formatted;
	} else {
		$cache = array( $period . $count => $prices_formatted );
	}

	set_transient( 'pmpro_report_sales', $cache, 3600 * 24 );

	return $prices_formatted;
}

//get revenue
function pmpro_getRevenue($period, $levels = NULL)
{
	//check for a transient
	$cache = get_transient("pmpro_report_revenue");
	if(!empty($cache) && !empty($cache[$period]) && !empty($cache[$period][$levels]))
		return $cache[$period][$levels];

	//a sale is an order with status NOT IN('refunded', 'review', 'token', 'error')
	if($period == "today")
		$startdate = date_i18n("Y-m-d", current_time('timestamp'));
	elseif($period == "this month")
		$startdate = date_i18n("Y-m", current_time('timestamp')) . "-01";
	elseif($period == "this year")
		$startdate = date_i18n("Y", current_time('timestamp')) . "-01-01";
	else
		$startdate = "";

	$gateway_environment = pmpro_getOption("gateway_environment");

	//build query
	global $wpdb;
	$sqlQuery = "SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE status NOT IN('refunded', 'review', 'token', 'error') AND timestamp >= '" . esc_sql( $startdate ) . "' AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	//restrict by level
	if(!empty($levels))
		$sqlQuery .= "AND membership_id IN(" . $levels . ") ";

	$revenue = $wpdb->get_var($sqlQuery);

	//save in cache
	if(!empty($cache) && !empty($cache[$period]))
		$cache[$period][$levels] = $revenue;
	elseif(!empty($cache))
		$cache[$period] = array($levels => $revenue);
	else
		$cache = array($period => array($levels => $revenue));

	set_transient("pmpro_report_revenue", $cache, 3600*24);

	return $revenue;
}

//delete transients when an order goes through
function pmpro_report_sales_delete_transients()
{
	delete_transient("pmpro_report_sales");
	delete_transient("pmpro_report_revenue");
}
add_action("pmpro_after_checkout", "pmpro_report_sales_delete_transients");
add_action("pmpro_updated_order", "pmpro_report_sales_delete_transients");
