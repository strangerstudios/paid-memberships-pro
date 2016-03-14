<?php
/*
	PMPro Report
	Title: Membership Stats
	Slug: memberships
	
	For each report, add a line like:
	global $pmpro_reports;
	$pmpro_reports['slug'] = 'Title';
	
	For each report, also write two functions:
	* pmpro_report_{slug}_widget()   to show up on the report homepage.
	* pmpro_report_{slug}_page()     to show up when users click on the report page widget.
*/

global $pmpro_reports;

$pmpro_reports['memberships'] = __('Membership Stats', 'pmpro');

//queue Google Visualization JS on report page
function pmpro_report_memberships_init() {
	if(is_admin() && isset($_REQUEST['report']) && $_REQUEST['report'] == "memberships" && isset($_REQUEST['page']) && $_REQUEST['page'] == "pmpro-reports")
		wp_enqueue_script("jsapi", "https://www.google.com/jsapi");
}
add_action( 'init', 'pmpro_report_memberships_init' );


//widget
function pmpro_report_memberships_widget() {
	global $wpdb;
?>
<span id="pmpro_report_memberships">	
	<table class="wp-list-table widefat fixed striped">
	<thead>
		<tr>
			<th scope="col">&nbsp;</th>
			<th scope="col"><?php _e('Signups','pmpro'); ?></th>
			<th scope="col"><?php _e('All Cancellations','pmpro'); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th scope="row"><?php _e('Today','pmpro'); ?></th>
			<td><?php echo number_format_i18n(pmpro_getSignups('today')); ?></td>
			<td><?php echo number_format_i18n(pmpro_getCancellations('today')); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('This Month','pmpro'); ?></th>
			<td><?php echo number_format_i18n(pmpro_getSignups('this month')); ?></td>
			<td><?php echo number_format_i18n(pmpro_getCancellations('this month')); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('This Year','pmpro'); ?></th>
			<td><?php echo number_format_i18n(pmpro_getSignups('this year')); ?></td>
			<td><?php echo number_format_i18n(pmpro_getCancellations('this year')); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php _e('All Time','pmpro'); ?></th>
			<td><?php echo number_format_i18n(pmpro_getSignups('all time')); ?></td>
			<td><?php echo number_format_i18n(pmpro_getCancellations('all time')); ?></td>
		</tr>
	</tbody>
	</table>
</span>
<?php
}

function pmpro_report_memberships_page()
{
	global $wpdb, $pmpro_currency_symbol;
	
	//get values from form
	if(isset($_REQUEST['type']))
		$type = sanitize_text_field($_REQUEST['type']);
	else
		$type = "signup_v_all";
	
	if(isset($_REQUEST['period']))
		$period = sanitize_text_field($_REQUEST['period']);
	else
		$period = "monthly";
		
	if(isset($_REQUEST['month']))
		$month = intval($_REQUEST['month']);
	else
		$month = date("n");

	$thisyear = date("Y");
	if(isset($_REQUEST['year']))
		$year = intval($_REQUEST['year']);
	else
		$year = date("Y");
		
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
	elseif($period == "annual")
	{
		$startdate = '1960-01-01';	//all time
		$enddate = strval(intval($year)+1) . '-01-01';
		$date_function = 'YEAR';
	}
	
	//testing or live data
	$gateway_environment = pmpro_getOption("gateway_environment");
	
	//get data
	if ( $type === "signup_v_cancel" || $type === "signup_v_expiration" || $type === "signup_v_all") {
		$sqlQuery = "SELECT $date_function(startdate) as date, COUNT(DISTINCT user_id) as signups
		FROM $wpdb->pmpro_memberships_users WHERE startdate >= '" . $startdate . "' ";

		if(!empty($enddate))
			$sqlQuery .= "AND startdate < '" . $enddate . "' ";
	}
	if ( $type === "mrr_ltv" ) {
		// Get total revenue, number of months in system, and date
		if ( $period == 'annual' )
			$sqlQuery = "SELECT SUM(total) as total, COUNT(DISTINCT MONTH(timestamp)) as months, $date_function(timestamp) as date
			FROM $wpdb->pmpro_membership_orders WHERE status NOT IN('refunded', 'review', 'token')
			AND timestamp >= '" . $startdate . "' AND gateway_environment = '" . esc_sql($gateway_environment) . "' ";

		if ( $period == 'monthly' )
			$sqlQuery = "SELECT SUM(total) as total, $date_function(timestamp) as date
			FROM $wpdb->pmpro_membership_orders WHERE status NOT IN('refunded', 'review', 'token')
			AND timestamp >= '" . $startdate . "' AND gateway_environment = '" . esc_sql($gateway_environment) . "' ";

		if(!empty($enddate))
			$sqlQuery .= "AND timestamp < '" . $enddate . "' ";
	}
	
	if(!empty($l))
		$sqlQuery .= "AND membership_id IN(" . $l . ") ";

	$sqlQuery .= " GROUP BY date ORDER BY date ";

	$dates = $wpdb->get_results($sqlQuery);
			
	//fill in blanks in dates
	$cols = array();				
	if($period == "daily")
	{
		$lastday = date("t", strtotime($startdate, current_time("timestamp")));
	
		for($i = 1; $i <= $lastday; $i++)
		{
			// Signups vs. Cancellations, Expirations, or All
			if ( $type === "signup_v_cancel" || $type === "signup_v_expiration" || $type === "signup_v_all" ) {
				$cols[$i] = new stdClass();
				$cols[$i]->signups = 0;
				foreach($dates as $day => $date)
				{
					if( $date->date == $i ) {
						$cols[$i]->signups = $date->signups;
					}
				}
			}
		}
	}
	elseif($period == "monthly")
	{
		for($i = 1; $i < 13; $i++)
		{
			// Signups vs. Cancellations, Expirations, or All
			if ( $type === "signup_v_cancel" || $type === "signup_v_expiration" || $type === "signup_v_all" ) {
				$cols[$i] = new stdClass();
				$cols[$i]->date = $i;
				$cols[$i]->signups = 0;
				foreach($dates as $date)
				{
					if( $date->date == $i ) {
						$cols[$i]->date = $date->date;
						$cols[$i]->signups = $date->signups;
					}
				}
			}

			// MRR & LTV
			if ( $type === "mrr_ltv" ) {
				$cols[$i] = new stdClass();
				$cols[$i]->date = $i;
				$cols[$i]->months = 1;
				foreach($dates as $date)
				{
					if( $date->date == $i ) {
						$cols[$i]->total = $date->total;
					}
				}
			}
		}
	}
	elseif($period == "annual") //annual
	{
	}
	
	$dates = ( ! empty( $cols ) ) ? $cols : $dates;

	// Signups vs. all
	if ( $type === "signup_v_cancel" || $type === "signup_v_expiration" || $type === "signup_v_all" )
	{
		$sqlQuery = "SELECT $date_function(mu1.modified) as date, COUNT(DISTINCT mu1.user_id) as cancellations
		FROM $wpdb->pmpro_memberships_users mu1
		LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON mu1.user_id = mu2.user_id AND
		mu2.modified > mu1.enddate AND
		DATE_ADD(mu1.modified, INTERVAL 1 DAY) > mu2.startdate ";
		if ( $type === "signup_v_cancel")
			$sqlQuery .= "WHERE mu1.status IN('inactive','cancelled','cancelled_admin') ";
		elseif($type === "signup_v_expiration")
			$sqlQuery .= "WHERE mu1.status IN('expired') ";
		else
			$sqlQuery .= "WHERE mu1.status IN('inactive','expired','cancelled','cancelled_admin') ";
			
		$sqlQuery .= "AND mu2.id IS NULL 
		AND mu1.startdate >= '" . $startdate . "' 
		AND mu1.startdate < '" . $enddate . "' ";
		 
		//restrict by level
		if(!empty($l))
			$sqlQuery .= "AND mu1.membership_id IN(" . $l . ") ";
	
		$sqlQuery .= " GROUP BY date ORDER BY date ";

		/**
		 * Filter query to get cancellation numbers in signups vs cancellations detailed report.
		 *
		 * @since 1.8.8
		 *
		 * @param string $sqlQuery The current SQL
		 * @param string $type report type
		 * @param string $startdate Start Date in YYYY-MM-DD format
		 * @param string $enddate End Date in YYYY-MM-DD format
		 * @param int $l Level ID
		 */
		$sqlQuery = apply_filters('pmpro_reports_signups_sql', $sqlQuery, $type, $startdate, $enddate, $l);
		
		$cdates = $wpdb->get_results($sqlQuery, OBJECT_K);				
		
		foreach( $dates as $day => &$date )
		{
			if(!empty($cdates) && !empty($cdates[$day]))
				$date->cancellations = $cdates[$day]->cancellations;
			else
				$date->cancellations = 0;
		}
	}

	// MRR & LTV
	if ( $type === "mrr_ltv" && count( $dates ) === 1 ) {
		$dummy_date = new stdClass();
		$dummy_date->total = 0;
		$dummy_date->months = 0;
		$dummy_date->date = $dates[0]->date - 1; 
		array_unshift( $dates, $dummy_date ); // Add to beginning
	}
	?>
	<form id="posts-filter" method="get" action="">		
	<h1>
		<?php _e('Membership Stats', 'pmpro');?>
	</h1>
	<ul class="subsubsub">
		<li>
			<?php _e('Show', 'pmpro')?>
			<select id="period" name="period">
				<option value="daily" <?php selected($period, "daily");?>><?php _e('Daily', 'pmpro');?></option>
				<option value="monthly" <?php selected($period, "monthly");?>><?php _e('Monthly', 'pmpro');?></option>
				<option value="annual" <?php selected($period, "annual");?>><?php _e('Annual', 'pmpro');?></option>
			</select>
			<select id="type" name="type">
				<option value="signup_v_all" <?php selected($type, "signup_v_all");?>><?php _e('Signups vs. All Cancellations', 'pmpro');?></option>
				<option value="signup_v_cancel" <?php selected($type, "signup_v_cancel");?>><?php _e('Signups vs. Cancellations', 'pmpro');?></option>
				<option value="signup_v_expiration" <?php selected($type, "signup_v_expiration");?>><?php _e('Signups vs. Expirations', 'pmpro');?></option>
				<?php /*
				<option value="mrr_ltv" <?php selected($type, "mrr_ltv");?>><?php _e('MRR & LTV', 'pmpro');?></option>
				*/ ?>
			</select>
			<span id="for"><?php _e('for', 'pmpro')?></span>
			<select id="month" name="month">
				<?php for($i = 1; $i < 13; $i++) { ?>
					<option value="<?php echo $i;?>" <?php selected($month, $i);?>><?php echo date("F", mktime(0, 0, 0, $i, 2));?></option>
				<?php } ?>
			</select>
			<select id="year" name="year">
				<?php for($i = $thisyear; $i > 2007; $i--) { ?>
					<option value="<?php echo $i;?>" <?php selected($year, $i);?>><?php echo $i;?></option>
				<?php } ?>
			</select>
			<span id="for"><?php _e('for', 'pmpro')?></span>
			<select name="level">
				<option value="" <?php if(!$l) { ?>selected="selected"<?php } ?>><?php _e('All Levels', 'pmpro');?></option>
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
			<input type="hidden" name="report" value="memberships" />	
			<input type="submit" class="button" value="<?php _e('Generate Report', 'pmpro');?>" />
		</li>
	</ul>
	
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
			<?php if ( $type === "signup_v_all" ) : // Signups vs. all cancellations ?>
			  ['<?php echo $date_function;?>', 'Signups', 'All Cancellations'],
			  <?php foreach($dates as $key => $value) { ?>
				['<?php if($period == "monthly") echo date("M", mktime(0,0,0,$value->date,2)); else if($period == "daily") echo $key; else echo $value->date;?>', <?php echo $value->signups; ?>, <?php echo $value->cancellations; ?>],
			  <?php } ?>
			<?php endif; ?>
			
			<?php if ( $type === "signup_v_cancel" ) : // Signups vs. cancellations ?>
			  ['<?php echo $date_function;?>', 'Signups', 'Cancellations'],
			  <?php foreach($dates as $key => $value) { ?>
				['<?php if($period == "monthly") echo date("M", mktime(0,0,0,$value->date,2)); else if($period == "daily") echo $key; else echo $value->date;?>', <?php echo $value->signups; ?>, <?php echo $value->cancellations; ?>],
			  <?php } ?>
			<?php endif; ?>
			
			<?php if ( $type === "signup_v_expiration" ) : // Signups vs. expirations ?>
			  ['<?php echo $date_function;?>', 'Signups', 'Expirations'],
			  <?php foreach($dates as $key => $value) { ?>
				['<?php if($period == "monthly") echo date("M", mktime(0,0,0,$value->date,2)); else if($period == "daily") echo $key; else echo $value->date;?>', <?php echo $value->signups; ?>, <?php echo $value->cancellations; ?>],
			  <?php } ?>
			<?php endif; ?>

			<?php if ( $type === "mrr_ltv" ) : ?>
			  ['<?php echo $date_function;?>', 'MRR', 'LTV'],
			  <?php foreach($dates as $key => $value) { ?>
				['<?php if($period == "monthly") echo date("M", mktime(0,0,0,$value->date,2)); else if($period == "daily") echo $key; else echo $value->date;?>', <?php echo (($mrr = $value->total / $value->months) && $mrr != 0) ? $mrr : 0; ?>, <?php echo pmpro_getLTV($period, NULL, $mrr ); ?>],
			  <?php } ?>
			<?php endif; ?>
			]);

			var options = {			 
			  colors: ['#0099c6', '#dc3912'],
			  hAxis: {title: '<?php echo $date_function;?>', titleTextStyle: {color: 'black'}, maxAlternation: 1},
			  vAxis: {color: 'green', titleTextStyle: {color: '#51a351'}},			  
			};

			<?php if ( $type === "signup_v_cancel" || $type === "signup_v_expiration" || $type === "signup_v_all" ) : // Signups vs. cancellations ?>
				var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
			
			<?php elseif ( $type === "mrr_ltv" ) : // MRR & LTV ?>
				
				<?php
					//prefix or suffix?
					if(pmpro_getCurrencyPosition() == "right")
						$position = "suffix";
					else
						$position = "prefix";
				?>
				
				var formatter = new google.visualization.NumberFormat({<?php echo $position;?>: '<?php echo html_entity_decode($pmpro_currency_symbol);?>'});
				formatter.format(data, 2);
				var formatter = new google.visualization.NumberFormat({<?php echo $position;?>: '<?php echo html_entity_decode($pmpro_currency_symbol);?>'});
				formatter.format(data, 1);

				var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
			<?php endif; ?>
			chart.draw(data, options);
		}
	</script>
	
	</form>
	<?php
}



/*
	Other code required for your reports. This file is loaded every time WP loads with PMPro enabled.
*/

//get signups
function pmpro_getSignups($period = false, $levels = 'all')
{
	//check for a transient
	$cache = get_transient( 'pmpro_report_memberships_signups' );
	if( ! empty( $cache ) && ! empty( $cache[$period] ) && ! empty( $cache[$period][$levels] ) )
		return $cache[$period][$levels];
		
	//a sale is an order with status = success
	if( $period == 'today' )
		$startdate = date(' Y-m-d' );
	elseif( $period == 'this month')
		$startdate = date( 'Y-m' ) . '-01';
	elseif( $period == 'this year')
		$startdate = date( 'Y' ) . '-01-01';
	else
		$startdate = '';

	
	//build query
	global $wpdb;

	$sqlQuery = "SELECT COUNT(DISTINCT user_id) FROM $wpdb->pmpro_memberships_users WHERE startdate >= '" . $startdate . "' ";

	//restrict by level
	if(!empty($levels) && $levels != 'all')
		$sqlQuery .= "AND membership_id IN(" . $levels . ") ";
	
	$signups = $wpdb->get_var($sqlQuery);
	
	//save in cache
	if(!empty($cache) && !empty($cache[$period]))
		$cache[$period][$levels] = $signups;
	elseif(!empty($cache))
		$cache[$period] = array($levels => $signups);
	else
		$cache = array($period => array($levels => $signups));
	
	set_transient("pmpro_report_memberships_signups", $cache, 3600*24);
	
	return $signups;
}

//get cancellations by status
function pmpro_getCancellations($period = false, $levels = 'all', $status = array('inactive','expired','cancelled','cancelled_admin') )
{
	//make sure status is an array
	if(!is_array($status))
		$status = array($status);

	//check for a transient
	$cache = get_transient( 'pmpro_report_memberships_cancellations' );
	$hash = md5($period . $levels . implode(',', $status));
	if( ! empty( $cache ) && ! empty( $cache[$hash] ) )
		return $cache[$hash];

	//figure out start date
	$now = current_time('timestamp');
	$year = date("Y", $now);
	if( $period == 'today' )
	{
		$startdate = date('Y-m-d', $now) . " 00:00:00";
		$enddate = date('Y-m-d', $now) . " 23:59:59";
	}
	elseif( $period == 'this month')
	{
		$startdate = date( 'Y-m', $now ) . '-01 00:00:00';
		$enddate = date( 'Y-m', $now ) . '-32 00:00:00';
	}
	elseif( $period == 'this year')
	{
		$startdate = date( 'Y', $now ) . '-01-01 00:00:00';
		$enddate = date( 'Y', $now ) . '-12-32 00:00:00';
	}
	else
	{
		//all time
		$startdate = '1960-01-01';	//all time
		$enddate = strval(intval($year)+1) . '-01-01';
	}
		
	/*
		build query.
		cancellations are marked in the memberships users table with status 'inactive', 'expired', 'cancelled', 'cancelled_admin'
		we try to ignore cancellations when the user gets a new level with 24 hours (probably an upgrade or downgrade)
	*/
	global $wpdb;

	$sqlQuery = "SELECT COUNT(mu1.id)
	FROM $wpdb->pmpro_memberships_users mu1
	LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON mu1.user_id = mu2.user_id AND
	mu2.modified > mu1.enddate AND
	DATE_ADD(mu1.modified, INTERVAL 1 DAY) > mu2.startdate
	WHERE mu1.status IN('" . implode("','", $status) . "')
	AND mu2.id IS NULL 
	AND mu1.enddate >= '" . $startdate . "' 
	AND mu1.enddate <= '" . $enddate . "'
	";
 
	//restrict by level
	if(!empty($levels) && $levels != 'all')
		$sqlQuery .= "AND membership_id IN(" . $levels . ") ";
	
	/**
	 * Filter query to get cancellation numbers in signups vs cancellations detailed report.
	 *
	 * @since 1.8.8
	 *
	 * @param string $sqlQuery The current SQL
	 * @param string $period Period for report. today, this month, this year, empty string for all time.
	 * @param array(int) $levels Level IDs to include in report.
	 * @param array(string) $status Statuses to include as cancelled.
	 */
	$sqlQuery = apply_filters('pmpro_reports_get_cancellations_sql', $sqlQuery, $period, $levels, $status);
	
	$cancellations = $wpdb->get_var($sqlQuery);
	
	//save in cache
	if(!empty($cache) && !empty($cache[$hash]))
		$cache[$hash] = $cancellations;
	elseif(!empty($cache))
		$cache[$hash] = $cancellations;
	else
		$cache = array($hash => $cancellations);
	
	set_transient("pmpro_report_memberships_cancellations", $cache, 3600*24);
	
	return $cancellations;
}

//get MRR
function pmpro_getMRR($period, $levels = 'all')
{
	//check for a transient
	//$cache = get_transient("pmpro_report_mrr");
	if(!empty($cache) && !empty($cache[$period]) && !empty($cache[$period][$levels]))
		return $cache[$period][$levels];	
		
	//a sale is an order with status NOT IN refunded, review, token, error
	if($period == "this month")
		$startdate = date("Y-m") . "-01";
	elseif($period == "this year")
		$startdate = date("Y") . "-01-01";
	else
		$startdate = "";
	
	$gateway_environment = pmpro_getOption("gateway_environment");
	
	//build query
	global $wpdb;
	// Get total revenue
	$sqlQuery = "SELECT SUM(total) FROM $wpdb->pmpro_membership_orders WHERE status NOT IN('refunded', 'review', 'token', 'error') AND timestamp >= '" . $startdate . "' AND gateway_environment = '" . esc_sql($gateway_environment) . "' ";

	//restrict by level
	if(!empty($levels) && $levels != 'all') {
		$sqlQuery .= "AND membership_id IN(" . $levels . ") ";	
	}
	
	$revenue = $wpdb->get_var($sqlQuery);
	
	//when was the first order
	$first_order_timestamp = $wpdb->get_var("SELECT UNIX_TIMESTAMP(`timestamp`) FROM $wpdb->pmpro_membership_orders WHERE `timestamp` IS NOT NULL AND `timestamp` >  '0000-00-00 00:00:00' ORDER BY `timestamp` LIMIT 1");
	
	//if we don't have a timestamp, we can't do this
	if(empty($first_order_timestamp))
		return false;
		
	//how many months ago was the first order
	$months = $wpdb->get_var("SELECT PERIOD_DIFF('" . date("Ym") . "', '" . date("Ym", $first_order_timestamp) . "')");
	
	/* this works in PHP 5.3+ without using MySQL to get the diff
	$date1 = new DateTime(date("Y-m-d", $first_order_timestamp));
	$date2 = new DateTime(date("Y-m-d"));	
	$interval = $date1->diff($date2);
	$years = intval($interval->format('%y'));
	$months = $years*12 + intval($interval->format('%m'));
	*/
	
	if($months > 0)
		$mrr = $revenue / $months;
	else
		$mrr = 0;
		
	//save in cache
	if(!empty($cache) && !empty($cache[$period]))
		$cache[$period][$levels] = $mrr;
	elseif(!empty($cache))
		$cache[$period] = array($levels => $mrr);
	else
		$cache = array($period => array($levels => $mrr));
	
	set_transient("pmpro_report_mrr", $cache, 3600*24);
	
	return $mrr;
}

//get Cancellation Rate
function pmpro_getCancellationRate($period, $levels = 'all', $status = NULL)
{	
	//make sure status is an array
	if(!is_array($status))
		$status = array($status);

	//check for a transient
	$cache = get_transient("pmpro_report_cancellation_rate");
	$hash = md5($period . $levels . implode('',$status));
	if(!empty($cache) && !empty($cache[$hash]))
		return $cache[$hash];
	
	$signups = pmpro_getSignups($period, $levels);
	$cancellations = pmpro_getCancellations($period, $levels, $status);
	
	if(empty($signups))
		return false;
	
	$rate = number_format(($cancellations / $signups)*100, 2);
	
	//save in cache
	if(!empty($cache) && !empty($cache[$period]))
		$cache[$period][$levels] = $rate;
	elseif(!empty($cache))
		$cache[$period] = array($levels => $rate);
	else
		$cache = array($period => array($levels => $rate));
	
	set_transient("pmpro_report_cancellation_rate", $cache, 3600*24);

	return $rate;
}

//get LTV
function pmpro_getLTV($period, $levels = 'all', $mrr = NULL, $signups = NULL, $cancellation_rate = NULL)
{	
	if(empty($mrr))
		$mrr = pmpro_getMRR($period, $levels);
	if(empty($signups))
		$signups = pmpro_getSignups($period, $levels);
	if(empty($cancellation_rate))
		$cancellation_rate = pmpro_getCancellationRate($period, $levels);
	
	//average monthly spend
	if(empty($signups))
		return false;
	
	if($signups > 0)
		$ams = $mrr / $signups;
	else
		$ams = 0;
		
	if($cancellation_rate > 0)
		$ltv = $ams * (1/$cancellation_rate);
	else
		$ltv = $ams;

	return $ltv;
}

//delete transients when an order goes through
function pmpro_report_memberships_delete_transients()
{
	delete_transient("pmpro_report_mrr");
	delete_transient("pmpro_report_cancellation_rate");
	delete_transient("pmpro_report_memberships_cancellations");
	delete_transient("pmpro_report_memberships_signups");
}
add_action("pmpro_after_checkout", "pmpro_report_memberships_delete_transients");
add_action("pmpro_updated_order", "pmpro_report_memberships_delete_transients");
