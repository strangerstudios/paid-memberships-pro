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
	global $wpdb, $pmpro_currency_symbol;
?>
	<style type="text/css">
		#pmpro_report_memberships .section-label {
			margin: 15px 0;
			font-size: 18px;
			text-align: left;
			display: block;
		}
		
		#pmpro_report_memberships .section-label:first-child {
			margin-top: 0;
		}

		#pmpro_report_memberships div {text-align: center;}
		#pmpro_report_memberships em {display: block; font-style: normal; font-size: 2em; margin: 5px; line-height: 26px;}	
	</style>
	<span id="pmpro_report_memberships">
		<label class="section-label"><?php _e('Signups', 'pmpro');?>:</label>
		<div style="width: 25%; float: left;">	
			<label><?php _e('All Time', 'pmpro');?></label>
			<em><?php echo pmpro_getSignups( 'all time' ); ?></em>		
		</div>
		<div style="width: 25%; float: left;">	
			<label><?php _e('This Year', 'pmpro');?></label>
			<em><?php echo pmpro_getSignups( 'this year' ); ?></em>		
		</div>
		<div style="width: 25%; float: left;">	
			<label><?php _e('This Month', 'pmpro');?></label>
			<em><?php echo pmpro_getSignups( 'this month' ); ?></em>		
		</div>
		<div style="width: 25%; float: left;">
			<label><?php _e('Today', 'pmpro');?></label>
			<em><?php echo pmpro_getSignups( 'today' ); ?></em>		
		</div>	
		<div class="clear"></div>

		<label class="section-label"><?php _e('Cancellations', 'pmpro');?>:</label>
		<div style="width: 25%; float: left;">	
			<label><?php _e('All Time', 'pmpro');?></label>
			<em><?php echo pmpro_getCancellations( 'all time' ); ?></em>		
		</div>
		<div style="width: 25%; float: left;">	
			<label><?php _e('This Year', 'pmpro');?></label>
			<em><?php echo pmpro_getCancellations( 'this year' ); ?></em>		
		</div>
		<div style="width: 25%; float: left;">	
			<label><?php _e('This Month', 'pmpro');?></label>
			<em><?php echo pmpro_getCancellations( 'this month' ); ?></em>		
		</div>
		<div style="width: 25%; float: left;">
			<label><?php _e('Today', 'pmpro');?></label>
			<em><?php echo pmpro_getCancellations( 'today' ); ?></em>		
		</div>	
		<div class="clear"></div>

		<label class="section-label"><?php _e('Other Stats', 'pmpro');?>:</label>
		<div style="width: 33%; float: left;">	
			<label><?php _e('Monthly Recurring Revenue (MRR)', 'pmpro');?></label>
			<em><?php echo $pmpro_currency_symbol . $pmpro_mrr = number_format(pmpro_getMRR( 'all time' ), 2); ?></em>		
		</div>
		<div style="width: 33%; float: left;">	
			<label><?php _e('Cancellation Rate', 'pmpro');?></label>
			<em><?php echo pmpro_getCancellationRate('all time' ); ?>%</em>		
		</div>
		<div style="width: 33%; float: left;">	
			<label><?php _e('Lifetime Value (LTV)', 'pmpro');?></label>
			<em><?php echo $pmpro_currency_symbol . number_format(pmpro_getLTV('all time' ), 2); ?></em>		
		</div>
		<div class="clear"></div>
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
		$type = "signup_v_cancel";
	
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
		$enddate = $year . '-' . substr("0" . $month, strlen($month) - 1, 2) . '-31';		
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
		$date_function = 'YEAR';
	}
	
	//testing or live data
	$gateway_environment = pmpro_getOption("gateway_environment");
	
	//get data
	if ( $type === "signup_v_cancel" ) {
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
		$lastday = date("t", $startdate);
	
		for($i = 1; $i <= $lastday; $i++)
		{
			// Signups vs. Cancellations
			if ( $type === "signup_v_cancel" ) {
				$cols[$i] = new stdClass();
				$cols[$i]->signups = 0;
				foreach($dates as $date)
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
			// Signups vs. Cancellations
			if ( $type === "signup_v_cancel" ) {
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

	// Signups vs. cancellations
	if ( $type === "signup_v_cancel" )
	{
		$sqlQuery = "SELECT $date_function(mu1.modified) as date, COUNT(DISTINCT mu1.user_id) as cancellations
		FROM $wpdb->pmpro_memberships_users mu1
		LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON mu1.user_id = mu2.user_id AND
		mu2.modified > mu1.enddate AND
		DATE_ADD(mu1.modified, INTERVAL 1 DAY) > mu2.startdate
		WHERE mu1.status = 'inactive'
		AND mu2.id IS NULL 
		AND mu1.startdate >= '" . $startdate . "' 
		AND mu1.startdate < '" . $enddate . "' ";
		 
		//restrict by level
		if(!empty($l))
			$sqlQuery .= "AND membership_id IN(" . $l . ") ";
	
		$sqlQuery .= " GROUP BY date ORDER BY date ";

		$cdates = $wpdb->get_results($sqlQuery, OBJECT_K);
	
		foreach( $dates as &$date )
		{
			if(!empty($cdates[$date->date]))
				$date->cancellations = $cdates[$date->date]->cancellations;
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
	<h2>
		<?php _e('Membership Stats', 'pmpro');?>
	</h2>
	<ul class="subsubsub">
		<li>
			<?php _ex('Show', 'Dropdown label, e.g. Show Daily Revenue for January', 'pmpro')?>
			<select id="period" name="period">
				<option value="daily" <?php selected($period, "daily");?>><?php _e('Daily', 'pmpro');?></option>
				<option value="monthly" <?php selected($period, "monthly");?>><?php _e('Monthly', 'pmpro');?></option>
				<option value="annual" <?php selected($period, "annual");?>><?php _e('Annual', 'pmpro');?></option>
			</select>
			<select id="type" name="type">
				<option value="signup_v_cancel" <?php selected($type, "signup_v_cancel");?>><?php _e('Signups vs. Cancellations', 'pmpro');?></option>
				<?php /*
				<option value="mrr_ltv" <?php selected($type, "mrr_ltv");?>><?php _e('MRR & LTV', 'pmpro');?></option>
				*/ ?>
			</select>
			<span id="for"><?php _ex('for', 'Dropdown label, e.g. Show Daily Revenue for January', 'pmpro')?></span>
			<select id="month" name="month">
				<?php for($i = 1; $i < 13; $i++) { ?>
					<option value="<?php echo $i;?>" <?php selected($month, $i);?>><?php echo date("F", mktime(0, 0, 0, $i));?></option>
				<?php } ?>
			</select>
			<select id="year" name="year">
				<?php for($i = $thisyear; $i > 2007; $i--) { ?>
					<option value="<?php echo $i;?>" <?php selected($year, $i);?>><?php echo $i;?></option>
				<?php } ?>
			</select>
			<span id="for"><?php _ex('for', 'Dropdown label, e.g. Show Daily Revenue for January', 'pmpro')?></span>
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
			<input type="submit" value="<?php _ex('Generate Report', 'Submit button value.', 'pmpro');?>" />
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
			<?php if ( $type === "signup_v_cancel" ) : // Signups vs. cancellations ?>
			  ['<?php echo $date_function;?>', 'Signups', 'Cancellations'],
			  <?php foreach($dates as $key => $value) { ?>
				['<?php if($period == "monthly") echo date("M", mktime(0,0,0,$value->date)); else if($period == "daily") echo $key; else echo $value->date;?>', <?php echo $value->signups; ?>, <?php echo $value->cancellations; ?>],
			  <?php } ?>
			<?php endif; ?>

			<?php if ( $type === "mrr_ltv" ) : // Signups vs. cancellations ?>
			  ['<?php echo $date_function;?>', 'MRR', 'LTV'],
			  <?php foreach($dates as $key => $value) { ?>
				['<?php if($period == "monthly") echo date("M", mktime(0,0,0,$value->date)); else if($period == "daily") echo $key; else echo $value->date;?>', <?php echo (($mrr = $value->total / $value->months) && $mrr != 0) ? $mrr : 0; ?>, <?php echo pmpro_getLTV($period, NULL, $mrr ); ?>],
			  <?php } ?>
			<?php endif; ?>
			]);

			var options = {			 
			  colors: ['#0099c6', '#dc3912'],
			  hAxis: {title: '<?php echo $date_function;?>', titleTextStyle: {color: 'black'}, maxAlternation: 1},
			  vAxis: {color: 'green', titleTextStyle: {color: '#51a351'}},			  
			};

			<?php if ( $type === "signup_v_cancel" ) : // Signups vs. cancellations ?>
				var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
			<?php elseif ( $type === "mrr_ltv" ) : // MRR & LTV ?>
				var formatter = new google.visualization.NumberFormat({prefix: '<?php echo html_entity_decode($pmpro_currency_symbol);?>'});
				formatter.format(data, 2);
				var formatter = new google.visualization.NumberFormat({prefix: '<?php echo html_entity_decode($pmpro_currency_symbol);?>'});
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

//get cancellations
function pmpro_getCancellations($period = false, $levels = 'all')
{
	//check for a transient
	$cache = get_transient( 'pmpro_report_memberships_cancellations' );
	if( ! empty( $cache ) && ! empty( $cache[$period] ) && ! empty( $cache[$period][$levels] ) )
		return $cache[$period][$levels];
		
	//figure out start date
	if( $period == 'today' )
		$startdate = date(' Y-m-d' );
	elseif( $period == 'this month')
		$startdate = date( 'Y-m' ) . '-01';
	elseif( $period == 'this year')
		$startdate = date( 'Y' ) . '-01-01';
	else
		$startdate = '';

		$startdate_plus_one = strtotime( $startdate . + ' + 1 day' );

	/*
		build query. 
		cancellations are marked in the memberships users table with status = 'inactive'
		we try to ignore cancellations when the user gets a new level with 24 hours (probably an upgrade or downgrade)
	*/
	global $wpdb;

	//$sqlQuery = "SELECT mu1.user_id, mu2.user_id FROM $wpdb->pmpro_memberships_users mu1 LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON mu1.user_id = mu2.user_id AND mu2.status = 'inactive' AND mu2.startdate > mu1.startdate"; 
	$sqlQuery = "SELECT COUNT(mu1.id)
FROM $wpdb->pmpro_memberships_users mu1
LEFT JOIN $wpdb->pmpro_memberships_users mu2 ON mu1.user_id = mu2.user_id AND
mu2.modified > mu1.enddate AND
DATE_ADD(mu1.modified, INTERVAL 1 DAY) > mu2.startdate
WHERE mu1.status = 'inactive'
AND mu2.id IS NULL 
AND mu1.startdate >= '" . $startdate . "' ";
 
	//restrict by level
	if(!empty($levels) && $levels != 'all')
		$sqlQuery .= "AND membership_id IN(" . $levels . ") ";
	
	$cancellations = $wpdb->get_var($sqlQuery);
		
	//save in cache
	if(!empty($cache) && !empty($cache[$period]) && is_array($cache[$period]))
		$cache[$period][$levels] = $cancellations;
	elseif(!empty($cache))
		$cache[$period] = array($levels => $cancellations);
	else
		$cache = array($period => array($levels => $cancellations));
	
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
function pmpro_getCancellationRate($period, $levels = 'all')
{	
	//check for a transient
	$cache = get_transient("pmpro_report_cancellation_rate");
	if(!empty($cache) && !empty($cache[$period]) && !empty($cache[$period][$levels]))
		return $cache[$period][$levels];	
	
	$signups = pmpro_getSignups($period, $levels);
	$cancellations = pmpro_getCancellations($period, $levels);
	
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
