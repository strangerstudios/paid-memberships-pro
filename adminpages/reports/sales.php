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
$gateway_environment = get_option( "pmpro_gateway_environment");
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
	global $wpdb, $pmpro_reports;
?>
<style>
	#pmpro_report_sales tbody td:last-child {text-align: right; }
</style>
<span id="pmpro_report_sales" class="pmpro_report-holder">
	<table class="wp-list-table widefat fixed">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e('Period', 'paid-memberships-pro' ); ?></th>
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

		/**
		 * Filter the periods for the sales widget.
		 * @since 2.10.6
		 * @param array $reports The array of periods.
		 * @return array $reports The array of periods.
		 */
		$reports = apply_filters( 'pmpro_sales_widget_periods', $reports );

		foreach ( $reports as $report_type => $report_name ) {
			//sale prices stats
			$count = 0;
			$max_prices_count = apply_filters( 'pmpro_admin_reports_max_sale_prices', 5 );
			$prices = pmpro_get_prices_paid( $report_type, $max_prices_count );
			?>
			<tbody>
				<tr class="pmpro_report_tr">
					<td>
						<?php if( ! empty( $prices ) ) { ?>
							<button aria-label="<?php echo esc_attr( sprintf( __( 'Toggle orders by price for %s', 'paid-memberships-pro' ), $report_name ) ); ?>" class="pmpro_report_th pmpro_report_th_closed"><?php echo esc_html($report_name); ?></button>
						<?php } else { ?>
							<?php echo esc_html($report_name); ?>
						<?php } ?>
					</td>
					<td><?php echo esc_html( number_format_i18n( pmpro_getSales( $report_type, null, 'all' ) ) ); ?></td>
					<td><?php echo pmpro_escape_price( pmpro_formatPrice( pmpro_getRevenue( $report_type ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
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
						<td aria-label="<?php echo esc_attr( sprintf( __( 'Orders %s at %s price', 'paid-memberships-pro' ), $report_name, pmpro_escape_price( pmpro_formatPrice( $price ) ) ) ); ?>">- <?php echo pmpro_escape_price( pmpro_formatPrice( $price ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
						<td><?php echo esc_html( number_format_i18n( $quantity['total'] ) ); ?></td>
						<td><?php echo pmpro_escape_price( pmpro_formatPrice( $price * $quantity['total'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
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
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=pmpro-reports&report=sales' ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View the full %s report', 'paid-memberships-pro' ), $pmpro_reports['sales'] ) ); ?>"><?php esc_html_e('Details', 'paid-memberships-pro' );?></a>
		</p>
	<?php } ?>
</span>

<?php
}

function pmpro_report_sales_data( $args ){

	global $wpdb;

	$type_function = ! empty( $args['type_function'] ) ? $args['type_function'] : '';
	$report_unit = ! empty( $args['report_unit'] ) ? $args['report_unit'] : '';
	$discount_code = ! empty( $args['discount_code'] ) ? $args['discount_code'] : '';
	$startdate = ! empty( $args['startdate'] ) ? $args['startdate'] : '';
	$enddate = ! empty( $args['enddate'] ) ? $args['enddate'] : '';
	$l = ! empty( $args['l'] ) ? (int) $args['l'] : '';

	//testing or live data
	$gateway_environment = get_option( "pmpro_gateway_environment");

	// Get the estimated second offset to convert from GMT time to local.This is not perfect as daylight
	// savings time can come and go in the middle of a month, but it's a tradeoff that we are making
	// for performance so that we don't need to go through each order manually to calculate the local time.
	$tz_offset = strtotime( $startdate ) - strtotime( get_gmt_from_date( $startdate . " 00:00:00" ) );

 	$sqlQuery = "SELECT date,
				 	$type_function(mo1total) as value,
				 	$type_function( IF( mo2id IS NOT NULL, mo1total, NULL ) ) as renewals
				 FROM ";
	$sqlQuery .= "(";	// Sub query.
	if ( $report_unit == 'DAY' ) {
		$sqlQuery .= "SELECT DATE( DATE_ADD( mo1.timestamp, INTERVAL " . esc_sql( $tz_offset ) . " SECOND ) ) as date,";
	} elseif ( $report_unit == 'MONTH' ) {
		$sqlQuery .= "SELECT DATE_FORMAT( DATE_ADD( mo1.timestamp, INTERVAL " . esc_sql( $tz_offset ) . " SECOND ), '%Y-%m' ) as date,";
	} else {
		$sqlQuery .= "SELECT YEAR( DATE_ADD( mo1.timestamp, INTERVAL " . esc_sql( $tz_offset ) . " SECOND ) ) as date,";
	}
	$sqlQuery .= "mo1.id as mo1id,
					mo1.total as mo1total,
					mo1.timestamp as mo1timestamp, 
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
					AND mo1.timestamp >= DATE_ADD( '" . esc_sql( $startdate ) . "' , INTERVAL - " . esc_sql( $tz_offset ) . " SECOND )
					AND mo1.status NOT IN('refunded', 'review', 'token', 'error')
					AND mo1.gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

	if(!empty($enddate))
		$sqlQuery .= "AND mo1.timestamp <= DATE_ADD( '" . esc_sql( $enddate ) . " 23:59:59' , INTERVAL - " . esc_sql( $tz_offset ) . " SECOND )";

	if(!empty($l))
		$sqlQuery .= "AND mo1.membership_id IN(" . $l . ") "; // $l is already escaped. See declaration.

	if ( ! empty( $discount_code ) ) {
		$sqlQuery .= "AND dc.code_id = '" . esc_sql( $discount_code ) . "' ";
	}

	$sqlQuery .= " GROUP BY mo1.id ";
	$sqlQuery .= ") t1";
	$sqlQuery .= " GROUP BY date ORDER by date";

	return $wpdb->get_results( $sqlQuery );

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

	if ( ! empty( $_REQUEST['month'] ) ) {
		$month = intval($_REQUEST['month']);
	} else {
		$month = date_i18n("n", current_time('timestamp'));
	}

	$thisyear = date_i18n("Y", current_time('timestamp'));
	if( ! empty( $_REQUEST['year'] ) ) {
		$year = intval($_REQUEST['year']);
	} else {
		$year = $thisyear;
	}

	if( ! empty( $_REQUEST['level'] ) ) {
		$l = intval($_REQUEST['level']);
	} else {
		$l = "";
	}

	if ( ! empty( $_REQUEST[ 'discount_code' ] ) ) {
		$discount_code = intval( $_REQUEST[ 'discount_code' ] );
	} else {
		$discount_code = '';
	}

	if ( isset( $_REQUEST[ 'show_parts' ] ) ) {
		$new_renewals = sanitize_text_field( $_REQUEST[ 'show_parts' ] );
	} else {
		$new_renewals = 'new_renewals';
	}

	//calculate start date and how to group dates returned from DB
	if( $period == "daily" ) {
		// Set up the report unit to use.
		$report_unit = 'DAY';
		$axis_date_format = 'd';
		$tooltip_date_format = get_option( 'date_format' );

		// Set up the start and end dates.
		$startdate = $year . '-' . substr("0" . $month, strlen($month) - 1, 2) . '-01';
		$enddate = $year . '-' . substr("0" . $month, strlen($month) - 1, 2) . '-' . date_i18n('t', strtotime( $startdate ) );

		// Set up the compare period. Comparing to same month last year.
		$compare_startdate = date( 'Y-m-d', strtotime( $startdate . ' -1 year' ) );
		$compare_enddate = date( 'Y-m-d', strtotime( $enddate . ' -1 year' ) );
	} else if( $period == "monthly" ) {
		// Set up the report unit to use.
		$report_unit = 'MONTH';
		$axis_date_format = 'M';
		$tooltip_date_format = 'F Y';

		// Set up the start and end dates.
		$startdate = $year . '-01-01';
		$enddate = $year . '-12-' . date_i18n( 't', strtotime( $startdate ) );
		
		// Set up the compare period.
		$compare_startdate = date( 'Y-m-d', strtotime( $startdate . ' -1 year' ) );
		$compare_enddate = date( 'Y-m-d', strtotime( $enddate . ' -1 year' ) );
	} else if ( $period === '7days' || $period === '30days' ) {
		// Set up the report unit to use.
		$report_unit = 'DAY';
		$timeframe = ( $period === '7days' ) ? 7 : 30;
		$axis_date_format = 'd';
		$tooltip_date_format = get_option( 'date_format' );
		$startdate   = date( 'Y-m-d', strtotime( current_time( 'mysql' ) .' -'.$timeframe.' '.$report_unit ) );
		$enddate = current_time( 'mysql' );
	} else if ( $period === '12months' ) {
		$report_unit = 'MONTH';
		$timeframe = 12;
		$axis_date_format = 'M';
		$tooltip_date_format = 'F Y';
		// Set the start date to the first day of the month 12 months ago.
		$startdate = date( 'Y-m-01', strtotime( current_time( 'mysql' ) . ' -12 month' ) );
		// Set the end date to the last day of the previous month.
		$enddate = date('Y-m-t', strtotime( current_time( 'mysql' ) . ' -1 month' ) );
	} else {
		// Set up the report unit to use.
		$report_unit = 'YEAR';
		$axis_date_format = 'Y';
		$tooltip_date_format = 'Y';
		// Set up the start and end dates.
		$startdate = '1970-01-01';	//all time
		$enddate = current_time( 'mysql' );
	}

	// Get the data.
	$report_data_args = array(
		'type_function' => $type_function,
		'report_unit' => $report_unit,
		'discount_code' => $discount_code,
		'startdate' => $startdate,
		'enddate' => $enddate,
		'l' => $l,
	);
	$dates = pmpro_report_sales_data( $report_data_args );
	// Set the array keys to the dates.
	$dates = array_combine( wp_list_pluck( $dates, 'date' ), $dates );
	
	// Get the compare period data if we need it.
	if ( ! empty( $compare_startdate ) && ! empty( $compare_enddate ) ) {
		$report_data_args['startdate'] = $compare_startdate;
		$report_data_args['enddate'] = $compare_enddate;

		$previous_period_dates = pmpro_report_sales_data( $report_data_args );
		// Set the array keys to the dates.
		$previous_period_dates = array_combine( wp_list_pluck( $previous_period_dates, 'date' ), $previous_period_dates );
	}

	// Set up variable to hold CSV data.
	$csvdata = array();

	// Set up variables to calculate average sales/revenue.
	$total_in_period = 0;
	$units_in_period = 0;

	// Fill in missing dates and merge compare data if available.
	if ( $report_unit == 'DAY' ) {
		// Loop through all the dates in this report period.
		$loop_timestamp_index = strtotime( $startdate );
		$loop_end_timestamp = strtotime( $enddate );
		while ( $loop_timestamp_index <= $loop_end_timestamp ) {
			// If we don't have data for this date, add it.
			$loop_date = date( 'Y-m-d', $loop_timestamp_index );
			if ( ! isset( $dates[ $loop_date ] ) ) {
				$dates[ $loop_date ] = (object) array(
					'date' => $loop_date,
					'value' => 0,
					'renewals' => 0,
				);
			}

			// If we have a compare period, add info for the date that we are comparing to as well.
			if ( ! empty( $previous_period_dates ) ) {
				$compare_date = date( 'Y-m-d', strtotime( $loop_date . ' -1 year' ) );
				if ( isset( $previous_period_dates[ $compare_date ] ) ) {
					$dates[ $loop_date ]->compare_value = $previous_period_dates[ $compare_date ]->value;
					$dates[ $loop_date ]->compare_renewals = $previous_period_dates[ $compare_date ]->renewals;
				} else {
					$dates[ $loop_date ]->compare_value = 0;
					$dates[ $loop_date ]->compare_renewals = 0;
				}
			}

			// If the date is today or in the past, update the variables for averaging.
			if ( $loop_date <= date( 'Y-m-d' ) ) {
				if ( $new_renewals == 'new_renewals' ) {
					$total_in_period += $dates[ $loop_date ]->value;
				} elseif ( $new_renewals == 'only_new' ) {
					$total_in_period += $dates[ $loop_date ]->value - $dates[ $loop_date ]->renewals;
				} elseif ( $new_renewals == 'only_renewals' ) {
					$total_in_period += $dates[ $loop_date ]->renewals;
				}
				$units_in_period += 1;
			}

			// Add to CSV data.
			$csvdata[ $loop_date ] = (object) array(
				'date'     => $loop_date,
				'total'    => $dates[ $loop_date ]->value,
				'new'      => $dates[ $loop_date ]->value - $dates[ $loop_date ]->renewals,
				'renewals' => $dates[ $loop_date ]->renewals,
			);

			// Increment the loop timestamp.
			$loop_timestamp_index = strtotime( '+1 day', $loop_timestamp_index );
		}
	} elseif ( $report_unit == 'MONTH' ) {
		// Loop through all the months in this report period.
		$loop_timestamp_index = strtotime( $startdate );
		$loop_end_timestamp = strtotime( $enddate );
		while ( $loop_timestamp_index < $loop_end_timestamp ) {
			// If we don't have data for this month, add it.
			$loop_date = date( 'Y-m', $loop_timestamp_index );
			if ( ! isset( $dates[ $loop_date ] ) ) {
				$dates[ $loop_date ] = (object) array(
					'date' => $loop_date,
					'value' => 0,
					'renewals' => 0,
				);
			}

			// If we have a compare period, add info for the month that we are comparing to as well.
			if ( ! empty( $previous_period_dates ) ) {
				$compare_date = date( 'Y-m', strtotime( $loop_date . ' -1 year' ) );
				if ( isset( $previous_period_dates[ $compare_date ] ) ) {
					$dates[ $loop_date ]->compare_value = $previous_period_dates[ $compare_date ]->value;
					$dates[ $loop_date ]->compare_renewals = $previous_period_dates[ $compare_date ]->renewals;
				} else {
					$dates[ $loop_date ]->compare_value = 0;
					$dates[ $loop_date ]->compare_renewals = 0;
				}
			}

			// If the month is this month or in the past, update the variables for averaging.
			if ( $loop_date <= date( 'Y-m' ) ) {
				if ( $new_renewals == 'new_renewals' ) {
					$total_in_period += $dates[ $loop_date ]->value;
				} elseif ( $new_renewals == 'only_new' ) {
					$total_in_period += $dates[ $loop_date ]->value - $dates[ $loop_date ]->renewals;
				} elseif ( $new_renewals == 'only_renewals' ) {
					$total_in_period += $dates[ $loop_date ]->renewals;
				}
				$units_in_period += 1;
			}

			// Add to CSV data.
			$csvdata[ $loop_date ] = (object) array(
				'date'     => $loop_date,
				'total'    => $dates[ $loop_date ]->value,
				'new'      => $dates[ $loop_date ]->value - $dates[ $loop_date ]->renewals,
				'renewals' => $dates[ $loop_date ]->renewals,
			);

			// Increment the loop timestamp.
			$loop_timestamp_index = strtotime( '+1 month', $loop_timestamp_index );
		}
	} elseif ( $report_unit == 'YEAR' ) {
		// Loop through all the years since the first year that we have data for.
		$start_year = min( array_keys( $dates ) );
		$end_year   = date( 'Y' );
		for ( $year = $start_year; $year <= $end_year; $year++ ) {
			// If we don't have data for this year, add it.
			if ( ! isset( $dates[ $year ] ) ) {
				$dates[ $year ] = (object) array(
					'date' => $year,
					'value' => 0,
					'renewals' => 0,
				);
			}

			// If the year is this year or in the past, update the variables for averaging.
			if ( $year <= date( 'Y' ) ) {
				if ( $new_renewals == 'new_renewals' ) {
					$total_in_period += $dates[ $year ]->value;
				} elseif ( $new_renewals == 'only_new' ) {
					$total_in_period += $dates[ $year ]->value - $dates[ $year ]->renewals;
				} elseif ( $new_renewals == 'only_renewals' ) {
					$total_in_period += $dates[ $year ]->renewals;
				}
				$units_in_period += 1;
			}

			// Add to CSV data.
			$csvdata[ $year ] = (object) array(
				'date'     => $year,
				'total'    => $dates[ $year ]->value,
				'new'      => $dates[ $year ]->value - $dates[ $year ]->renewals,
				'renewals' => $dates[ $year ]->renewals,
			);
		}
	}

	// Order $dates by date.
	ksort( $dates );
	
	// Save a transient for each combo of params. Expires in 1 hour.
	$param_array = array( $period, $type, $month, $year, $l, $discount_code );
	$param_hash = md5( implode( ' ', $param_array ) . PMPRO_VERSION );
	set_transient( 'pmpro_sales_data_' . $param_hash, $csvdata, HOUR_IN_SECONDS );

	// Here, we're goign to build data for the Google Chart.
	// We are doing the calculations up here so that we don't need to weave them into the JS to display the chart.
	$google_chart_column_labels = array();
	$google_chart_row_data = array();
	$google_chart_series_styles = array();

	// For the row data, we need to initialize this with the dates being reported and some other info.
	foreach ( $dates as $date => $data ) {
		$google_chart_row_data[ $date ] = array(); // Will have array keys 'date', 'tooltip', and a nested array 'data'.
		$google_chart_row_data[ $date ][ 'date' ] = is_numeric( $date ) ? $date : date_i18n( $axis_date_format, strtotime( $date ) ); // is_numeric() check for YEAR report unit.

		// Build the tooltip.
		$google_chart_row_data[ $date ][ 'tooltip' ] = '<div style="padding:15px; font-size: 14px; line-height: 20px; color: #000000;">'; // Set up div.
		// Add the date.
		$google_chart_row_data[ $date ][ 'tooltip' ] .= '<strong>';
		$google_chart_row_data[ $date ][ 'tooltip' ] .= is_numeric( $date ) ? $date : date_i18n( $tooltip_date_format, strtotime( $date ) );
		$google_chart_row_data[ $date ][ 'tooltip' ] .= '</strong><br />';
		// Set up a UL for the data.
		$google_chart_row_data[ $date ][ 'tooltip' ] .= '<ul style="margin-bottom: 0px;">';
		// Maybe add renewal sales data.
		if ( in_array( $new_renewals, array( 'only_renewals', 'new_renewals' ) ) ) {
			$google_chart_row_data[ $date ][ 'tooltip' ] .= '<li><span style="margin-right: 3px;">' . sprintf( __( 'Renewals: %s', 'paid-memberships-pro' ), $type === 'sales' ? $data->renewals : pmpro_formatPrice( $data->renewals ) ) . '</li>';
		}
		// Maybe add new sales data.
		if ( in_array( $new_renewals, array( 'only_new', 'new_renewals' ) ) ) {
			$google_chart_row_data[ $date ][ 'tooltip' ] .= '<li><span style="margin-right: 3px;">' . sprintf( __( 'New: %s', 'paid-memberships-pro' ), $type === 'sales' ? $data->value - $data->renewals : pmpro_formatPrice( $data->value - $data->renewals ) ) . '</li>';
		}
		// Maybe add total sales data.
		if ( $new_renewals === 'new_renewals' ) {
			$google_chart_row_data[ $date ][ 'tooltip' ] .= '<li style="border-top: 1px solid #CCC; margin-bottom: 0px; margin-top: 8px; padding-top: 8px;">' . sprintf( __( 'Total: %s', 'paid-memberships-pro' ), $type === 'sales' ? $data->value : pmpro_formatPrice( $data->value ) ) . '</li>';
		}
		// Maybe add compare to previous period data.
		if ( ! empty( $previous_period_dates ) ) {
			if ( $new_renewals === 'new_renewals' ) {
				$google_chart_row_data[ $date ][ 'tooltip' ] .= '<li style="border-top: 1px solid #CCC; margin-bottom: 0px; margin-top: 8px; padding-top: 8px;">' . sprintf( __( 'Previous Year: %s', 'paid-memberships-pro' ), $type === 'sales' ? $data->compare_value : pmpro_formatPrice( $data->compare_value ) ) . '</li>';
			} elseif ( $new_renewals === 'only_new') {
				$google_chart_row_data[ $date ][ 'tooltip' ] .= '<li style="border-top: 1px solid #CCC; margin-bottom: 0px; margin-top: 8px; padding-top: 8px;">' . sprintf( __( 'Previous Year: %s', 'paid-memberships-pro' ), $type === 'sales' ? $data->compare_value - $data->compare_renewals : pmpro_formatPrice( $data->compare_value - $data->compare_renewals ) ) . '</li>';
			} elseif ( $new_renewals === 'only_renewals') {
				$google_chart_row_data[ $date ][ 'tooltip' ] .= '<li style="border-top: 1px solid #CCC; margin-bottom: 0px; margin-top: 8px; padding-top: 8px;">' . sprintf( __( 'Previous Year: %s', 'paid-memberships-pro' ), $type === 'sales' ? $data->compare_renewals : pmpro_formatPrice( $data->compare_renewals ) ) . '</li>';
			}
		}
		// Close the UL and div.
		$google_chart_row_data[ $date ][ 'tooltip' ] .= '</ul></div>';

		// Set up the data array.
		$google_chart_row_data[ $date ][ 'data' ] = array();
	}

	// For now are 4 columns/data points that we may need to create:
	// 1. Renewal sales/revenue
	// 2. New signups/revenue
	// 3. Compare to previous period
	// 4. Average sales/revenue in period

	// Renewal sales/revenue
	if ( in_array( $new_renewals, array( 'only_renewals', 'new_renewals' ) ) ) {
		$google_chart_column_labels[] = sprintf( __( 'Renewal %s', 'paid-memberships-pro' ), $type === 'sales' ? __( 'Signups', 'paid-memberships-pro' ) : __( 'Revenue', 'paid-memberships-pro' ) );
		foreach ( $dates as $date => $data ) {
			$google_chart_row_data[ $date ]['data'][] = (int) $data->renewals;
		}
		$google_chart_series_styles[] = array(
			'color' => ( $type === 'sales' ) ? '#006699' : '#31825D',
		);
	}

	// New signups/revenue
	if ( in_array( $new_renewals, array( 'only_new', 'new_renewals' ) ) ) {
		$google_chart_column_labels[] = sprintf( __( 'New %s', 'paid-memberships-pro' ), $type === 'sales' ? __( 'Signups', 'paid-memberships-pro' ) : __( 'Revenue', 'paid-memberships-pro' ) );
		foreach ( $dates as $date => $data ) {
			$google_chart_row_data[ $date ]['data'][] = (int) ( $data->value - $data->renewals );
		}
		$google_chart_series_styles[] = array(
			'color' => ( $type === 'sales' ) ? '#0099C6' : '#5EC16C',
		);
	}

	// Compare to previous period
	if ( ! empty( $previous_period_dates ) ) {
		$google_chart_column_labels[] = __( 'Previous Period', 'paid-memberships-pro' );
		foreach ( $dates as $date => $data ) {
			if ( $new_renewals === 'new_renewals' ) {
				$google_chart_row_data[ $date ]['data'][] = (int) $data->compare_value;
			} elseif ( $new_renewals === 'only_new') {
				$google_chart_row_data[ $date ]['data'][] = (int) ( $data->compare_value - $data->compare_renewals );
			} elseif ( $new_renewals === 'only_renewals') {
				$google_chart_row_data[ $date ]['data'][] = (int) $data->compare_renewals;
			}
		}
		$google_chart_series_styles[] = array(
			'color' => '#999999',
			'pointsVisible' => true,
			'type' => 'line'
		);
	}

	// Average sales/revenue in period
	$google_chart_column_labels[] = sprintf( __( 'Average %s', 'paid-memberships-pro' ), $type === 'sales' ? __( 'Signups', 'paid-memberships-pro' ) : __( 'Revenue', 'paid-memberships-pro' ) );
	$average = 0;
	if ( 0 !== $units_in_period ) {
		$average = (int) $total_in_period / $units_in_period; // Not including this unit.
	}
	foreach ( $dates as $date => $data ) {
		$google_chart_row_data[ $date ]['data'][] = $average;
	}
	$google_chart_series_styles[] = array(
		'type' => 'line',
		'color' => '#B00000',
		'enableInteractivity' => false,
		'lineDashStyle' => [4,1]
	);

	// We now have all the data for the chart! Let's start building output.

	// Build CSV export link.
	$args = array(
		'action' => 'sales_report_csv',
		'period' => $period,
		'type' => $type,
		'year' => $year,
		'month' => $month,
		'level' => $l,
		'discount_code' => $discount_code
	);
	$csv_export_link = add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
	?>
	<form id="posts-filter" method="get" action="">
	<h1 class="wp-heading-inline">
		<?php esc_html_e('Sales and Revenue', 'paid-memberships-pro' );?>
	</h1>
	<?php if ( current_user_can( 'pmpro_sales_report_csv' ) ) { ?>
		<a target="_blank" href="<?php echo esc_url( $csv_export_link ); ?>" class="page-title-action pmpro-has-icon pmpro-has-icon-download"><?php esc_html_e( 'Export to CSV', 'paid-memberships-pro' ); ?></a>
	<?php } ?>
	<div class="pmpro_report-filters">
		<h3><?php esc_html_e( 'Customize Report', 'paid-memberships-pro'); ?></h3>
		<div class="tablenav top">
			<span><?php echo esc_html_x( 'Show', 'Dropdown label, e.g. Show Period', 'paid-memberships-pro' ); ?></span>
			<label for="period" class="screen-reader-text"><?php esc_html_e( 'Select report time period', 'paid-memberships-pro' ); ?></label>
			<select id="period" name="period">
				<option value="daily" <?php selected($period, "daily");?>><?php esc_html_e('Daily', 'paid-memberships-pro' );?></option>
				<option value="monthly" <?php selected($period, "monthly");?>><?php esc_html_e('Monthly', 'paid-memberships-pro' );?></option>
				<option value="annual" <?php selected($period, "annual");?>><?php esc_html_e('Annual', 'paid-memberships-pro' );?></option>
				<option value='7days' <?php selected( $period, '7days' ); ?>><?php esc_html_e( 'Last 7 Days', 'paid-memberships-pro' ); ?></option>
				<option value='30days' <?php selected( $period, '30days' ); ?>><?php esc_html_e( 'Last 30 Days', 'paid-memberships-pro' ); ?></option>
				<option value='12months' <?php selected( $period, '12months' ); ?>><?php esc_html_e( 'Last 12 Months', 'paid-memberships-pro' ); ?></option>
			</select>
			<label for="type" class="screen-reader-text"><?php esc_html_e( 'Select report type', 'paid-memberships-pro' ); ?></label>
			<select id="type" name="type">
				<option value="revenue" <?php selected($type, "revenue");?>><?php esc_html_e('Revenue', 'paid-memberships-pro' );?></option>
				<option value="sales" <?php selected($type, "sales");?>><?php esc_html_e('Sales', 'paid-memberships-pro' );?></option>
			</select>
			<span id="for"><?php esc_html_e('for', 'paid-memberships-pro' )?></span>
			<label for="month" class="screen-reader-text"><?php esc_html_e( 'Select report month', 'paid-memberships-pro' ); ?></label>
			<select id="month" name="month">
				<?php for($i = 1; $i < 13; $i++) { ?>
					<option value="<?php echo esc_attr( $i );?>" <?php selected($month, $i);?>><?php echo esc_html(date_i18n("F", mktime(0, 0, 0, $i, 2)));?></option>
				<?php } ?>
			</select>
			<label for="year" class="screen-reader-text"><?php esc_html_e( 'Select report year', 'paid-memberships-pro' ); ?></label>
			<select id="year" name="year">
				<?php for($i = $thisyear; $i > 2007; $i--) { ?>
					<option value="<?php echo esc_attr( $i );?>" <?php selected($year, $i);?>><?php echo esc_html( $i );?></option>
				<?php } ?>
			</select>
			<span id="for"><?php esc_html_e('for', 'paid-memberships-pro' )?></span>
			<label for="level" class="screen-reader-text"><?php esc_html_e( 'Filter report by membership level', 'paid-memberships-pro' ); ?></label>
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
			<label for="discount_code" class="screen-reader-text"><?php esc_html_e( 'Filter report by discount code', 'paid-memberships-pro' ); ?></label>
			<select id="discount_code" name="discount_code">
				<option value="" <?php if ( empty( $discount_code ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e('All Codes', 'paid-memberships-pro' );?></option>
				<?php foreach ( $codes as $code ) { ?>
					<option value="<?php echo esc_attr( $code->id ); ?>" <?php selected( $discount_code, $code->id ); ?>><?php echo esc_html( $code->code ); ?></option>
				<?php } ?>
			</select>
			<?php } ?>
			<label for="show_parts" class="screen-reader-text"><?php esc_html_e( 'Select report data to include', 'paid-memberships-pro' ); ?></label>
			<select id="show_parts" name="show_parts">
				<option value='new_renewals' <?php selected( $new_renewals, 'new_renewals' ); ?> ><?php esc_html_e( 'Show New and Renewals', 'paid-memberships-pro' ); ?></option>
				<option value='only_new' <?php selected( $new_renewals, 'only_new' ); ?> ><?php esc_html_e( 'Show Only New', 'paid-memberships-pro' ); ?></option>
				<option value='only_renewals' <?php selected( $new_renewals, 'only_renewals' ); ?> ><?php esc_html_e( 'Show Only Renewals', 'paid-memberships-pro' ); ?></option>
			</select>
			<input type="hidden" name="page" value="pmpro-reports" />
			<input type="hidden" name="report" value="sales" />
			<input type="submit" class="button button-primary action" value="<?php esc_attr_e('Generate Report', 'paid-memberships-pro' );?>" />
			<br class="clear" />
		</div> <!-- end tablenav -->
	</div> <!-- end pmpro_report-filters -->
	<div class="pmpro_chart_area">
		<div id="chart_div"></div>
		<div class="pmpro_chart_description"><p><center><em><?php esc_html_e( 'Average line calculated using data prior to current day, month, or year.', 'paid-memberships-pro' ); ?></em></center></p></div>
	</div>
	<script>
		//update month/year when period dropdown is changed
		jQuery(document).ready(function() {
			jQuery('#period').on('change',function() {
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
			
			// Date
			dataTable.addColumn('string', <?php echo wp_json_encode( esc_html( $report_unit ) ); ?>);

			// Tooltip
			dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});

			<?php
			foreach ( $google_chart_column_labels as $label ) {
				echo "dataTable.addColumn('number', " . wp_json_encode( esc_html( $label ) ) . ");";
			} 
			?>

			dataTable.addRows([
				<?php foreach( $google_chart_row_data as $chart_row_data ) { ?>
					[
						<?php echo wp_json_encode( esc_html( $chart_row_data['date'] ) ); ?>,
						<?php echo wp_json_encode( wp_kses( $chart_row_data['tooltip'], 'post' ) ); ?>,
						<?php
						echo esc_html( implode( ',', $chart_row_data['data'] ) . ',' );
						?>
					],
				<?php } ?>
			]);

			<?php
			// Set the series data.
			?>
			var options = {
				title: pmpro_report_title_sales(),
				titlePosition: 'top',
				titleTextStyle: {
					color: '#555555',
				},
				legend: {position: 'bottom'},
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
				series: <?php echo wp_json_encode( $google_chart_series_styles ); ?>,
				<?php if ( $new_renewals === 'new_renewals' ) { ?>
					isStacked: true,
				<?php } ?>
			};

			var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
			view = new google.visualization.DataView(dataTable);
			chart.draw(view, options);
		}

		function createCustomHTMLContent(period, renewals = false, notRenewals = false, total = false, compare = false, compare_new = false, compare_renewal = false) {

			// Our return var for the Tooltip HTML.
			var content_string;

			// Start building the Tooltip HTML.
			content_string = '<div style="padding:15px; font-size: 14px; line-height: 20px; color: #000000;">' +
				'<strong>' + period + '</strong><br/>';
			content_string += '<ul style="margin-bottom: 0px;">';

			// New Sales/Revenue.
			if ( notRenewals ) {
				content_string += '<li><span style="margin-right: 3px;">' + <?php echo wp_json_encode( esc_html__( 'New:', 'paid-memberships-pro' ) ); ?> + '</span>' + notRenewals + '</li>';
			}

			// Renewal Sales/Revenue.
			if ( renewals ) {
				content_string += '<li><span style="margin-right: 3px;">' + <?php echo wp_json_encode( esc_html__( 'Renewals:', 'paid-memberships-pro' ) ); ?> + '</span>' + renewals + '</li>';
			}

			// Total Sales/Revenue.
			if ( total ) {
				content_string += '<li style="border-top: 1px solid #CCC; margin-bottom: 0px; margin-top: 8px; padding-top: 8px;"><span style="margin-right: 3px;">' + <?php echo wp_json_encode( esc_html__( 'Total:', 'paid-memberships-pro' ) ); ?> + '</span>' + total + '</li>';
			}

			// Comparison Sales/Revenue.
			if ( compare ) {
				// Comparison Period New Sales/Revenue
				if ( compare_new ) {
					content_string += '<li style="border-top: 1px solid #CCC; margin-bottom: 0px; margin-top: 8px; padding-top: 8px;"><span style="margin-right: 3px;">' + <?php echo wp_json_encode( esc_html__( 'Previous Period New:', 'paid-memberships-pro' ) ); ?> + '</span>' + compare_new + '</li>';
				}

				// Comparison Period Renewal Sales/Revenue
				if ( compare_renewal ) {
					content_string += '<li style="border-top: 1px solid #CCC; margin-bottom: 0px; margin-top: 8px; padding-top: 8px;"><span style="margin-right: 3px;">' + <?php echo wp_json_encode( esc_html__( 'Previous Period Renewals:', 'paid-memberships-pro' ) ); ?> + '</span>' + compare_renewal + '</li>';
				}
			}

			// Finish the Tooltip HTML.
			content_string += '</ul>' + '</div>';

			// Return Tooltip HTML.
			return content_string;
		}
		function pmpro_report_title_sales() {
			<?php
				if ( ! empty( $month ) && $period === 'daily' ) {
					$date = date_i18n( 'F', mktime(0, 0, 0, $month, 2) ) . ' ' . $year;
				} elseif( ! empty( $year ) && $period === 'monthly'  ) {
					$date = $year;
				} elseif ( $period === 'annual') {
					$date = __( 'All Time', 'paid-memberships-pro' );
				} else {
					$date = '';
				}

				// Let's make the period read better.
				if ( $period ) {
					switch ( $period ) {
						case '30days':
							$period_title = __( 'Last 30 Days', 'paid-memberships-pro' );
							break;
						case '7days':
							$period_title = __( 'Last 7 Days', 'paid-memberships-pro' );
							break;
						case '12months':
							$period_title = __( 'Last 12 Months', 'paid-memberships-pro' );
							break;
						default:
							$period_title = $period;
							break;
					}
				}
				
				// Adjust the title if we have a date or not so it reads better.
				if ( $date ) {
					// translators: %1$s is the report period, %2$s is the report type, %3$s is the date.
					$title = sprintf( esc_html__( '%1$s %2$s for %3$s', 'paid-memberships-pro' ), ucwords( $period ), ucwords( $type ), ucwords( $date ) );
				} else {
					// translators: %1$s is the report period, %2$s is the report type.
					$title = sprintf( esc_html__( '%1$s %2$s', 'paid-memberships-pro' ) , ucwords( $period_title ), ucwords( $type ) );

				}
			?>
			return <?php echo wp_json_encode( esc_html(  $title )  ); ?>;
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

	$gateway_environment = get_option( "pmpro_gateway_environment");

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
		// Let's make sure that each ID inside of $levels is an integer.
		if ( ! is_array($levels) ) {
			$levels = explode( ',', $levels );
		}
		$levels = implode( ',', array_map( 'intval', $levels ) );
		$sqlQuery .= "AND mo1.membership_id IN(" . $levels . ") ";
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
	$sqlQuery = "SELECT COUNT(*) FROM (" . $sqlQuery  . ") as t1";

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
	if ( ! empty( $cache ) && isset( $cache[$param_hash] ) ) {
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

	$gateway_environment = get_option( 'pmpro_gateway_environment' );

	// Build query.
	global $wpdb;
	$sql_query = "SELECT ROUND(total,8) as rtotal, COUNT(*) as num FROM $wpdb->pmpro_membership_orders WHERE total > 0 AND status NOT IN('refunded', 'review', 'token', 'error') AND timestamp >= '" . esc_sql( $startdate ) . "' AND gateway_environment = '" . esc_sql( $gateway_environment ) . "' ";

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

	$gateway_environment = get_option( "pmpro_gateway_environment");

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
	if ( ! empty( $levels ) ) {
		// Let's make sure that each ID inside of $levels is an integer.
		if ( ! is_array($levels) ) {
			$levels = explode( ',', $levels );
		}
		$levels = implode( ',', array_map( 'intval', $levels ) );
		$sqlQuery .= "AND mo1.membership_id IN(" . $levels . ") ";
	}
		
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
		$sql_query .= ' AND membership_id IN(' . implode( ', ', array_map( 'intval', $level_ids ) ) . ') '; 
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
add_action("pmpro_added_order", "pmpro_report_sales_delete_transients");