<?php

// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_reportscsv' ) ) ) {
	die( __( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

define( 'PMPRO_BENCHMARK', true );

if ( ! defined( 'PMPRO_BENCHMARK' ) ) {
	define( 'PMPRO_BENCHMARK', false );
}

$start_memory = memory_get_usage( true );
$start_time   = microtime( true );

if ( true === PMPRO_BENCHMARK ) {
	error_log( str_repeat( '-', 10 ) . date_i18n( 'Y-m-d H:i:s' ) . str_repeat( '-', 10 ) );
}

/**
 * Filter to set max number of records to process at a time
 * for the export (helps manage memory footprint)
 *
 * NOTE: Use the pmpro_before_membership_stats_csv_export hook to increase memory "on-the-fly"
 *       Can reset with the pmpro_after_membership_stats_csv_export hook
 *
 * @since 2.9
 */
// set the number of records we'll load to try and protect ourselves from OOM errors
$max_record_loops = apply_filters( 'pmpro_set_max_membership_stats_records_per_export_loop', 2000 );

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
	$month = date_i18n("n");

$thisyear = date_i18n("Y");
if(isset($_REQUEST['year']))
	$year = intval($_REQUEST['year']);
else
	$year = date_i18n("Y");

if(isset($_REQUEST['level'])) {
	if( $_REQUEST['level'] == 'paid-levels' ) {
		$l = pmpro_report_get_levels( 'paid' ); // String of ints and commas. Already escaped for SQL.
	}elseif( $_REQUEST['level'] == 'free-levels' ) {
		$l = pmpro_report_get_levels( 'free' ); // String of ints and commas. Already escaped for SQL.
	}else{
		$l = intval($_REQUEST['level']); // Escaping for SQL.
	}
} else {
	$l = "";
}

if ( isset( $_REQUEST[ 'discount_code' ] ) ) {
	$discount_code = intval( $_REQUEST[ 'discount_code' ] );
} else {
	$discount_code = '';
}

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
	$startdate = '1970-01-01';	//all time
	$enddate = strval(intval($year)+1) . '-01-01';
	$date_function = 'YEAR';
}

//testing or live data
$gateway_environment = pmpro_getOption("gateway_environment");

//get data
if (
	$type === "signup_v_cancel" ||
	$type === "signup_v_expiration" ||
	$type === "signup_v_all"
) {
	$sqlQuery = "SELECT $date_function(mu.startdate) as date, COUNT(DISTINCT mu.user_id) as signups
	FROM $wpdb->pmpro_memberships_users mu ";

	if ( ! empty( $discount_code ) ) {
		$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON mu.user_id = dc.user_id ";
	}

	$sqlQuery .= "WHERE mu.startdate >= '" . esc_sql( $startdate ) . "' ";

	if ( ! empty( $enddate ) ) {
		$sqlQuery .= "AND mu.startdate <= '" . esc_sql( $enddate ) . "' ";
	}
}

if ( ! empty( $l ) ) {
	$sqlQuery .= "AND mu.membership_id IN(" . $l . ") "; // $l is already escaped. See declaration.
}

if ( ! empty( $discount_code ) ) {
	$sqlQuery .= "AND dc.code_id = '" . esc_sql( $discount_code ) . "' ";
}

$sqlQuery .= " GROUP BY date ORDER BY date ";

$dates = $wpdb->get_results($sqlQuery);

//fill in blanks in dates
$cols = array();
if($period == "daily")
{
	$lastday = date_i18n("t", strtotime($startdate, current_time("timestamp")));

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
	FROM $wpdb->pmpro_memberships_users mu1 ";

	//restrict by discount code
	if ( ! empty( $discount_code ) ) {
		$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON mu1.user_id = dc.user_id ";
	}

	if ( $type === "signup_v_cancel")
		$sqlQuery .= "WHERE mu1.status IN('inactive','cancelled','admin_cancelled') ";
	elseif($type === "signup_v_expiration")
		$sqlQuery .= "WHERE mu1.status IN('expired') ";
	else
		$sqlQuery .= "WHERE mu1.status IN('inactive','expired','cancelled','admin_cancelled') ";

	$sqlQuery .= "AND mu1.enddate >= '" . esc_sql( $startdate ) . "'
	AND mu1.enddate < '" . esc_sql( $enddate ) . "' ";

	//restrict by level
	if ( ! empty( $l ) ) {
		$sqlQuery .= "AND mu1.membership_id IN(" . $l . ") "; // $l is already escaped. See declaration.
	}

	if ( ! empty( $discount_code ) ) {
		$sqlQuery .= "AND dc.code_id = '" . esc_sql( $discount_code ) . "' ";
	}

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

$headers   = array();
$headers[] = 'Content-Type: text/csv';
$headers[] = 'Cache-Control: max-age=0, no-cache, no-store';
$headers[] = 'Pragma: no-cache';
$headers[] = 'Connection: close';

$filename = 'membership-statistics.csv';
/*
	Insert logic here for building filename from $filter and other values.
*/
$filename  = apply_filters( 'pmpro_membership_stats_csv_export_filename', $filename );
$headers[] = "Content-Disposition: attachment; filename={$filename};";

// Default CSV file headers.
$csv_file_header_array = array(
	'date',
	'signups',
	'cancellations'
);

// These are the meta_keys for the fields (arrays are object, property. so e.g. $theuser->ID) - Date items are manually handled further down.
$default_columns = array(
	array( 'each_date', 'date' ),
	array( 'each_date', 'signups' ),
	array( 'each_date', 'cancellations' )
);

$dateformat = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

$csv_file_header = implode( ',', $csv_file_header_array ) . "\n";

// Generate a temporary file to store the data in.
$tmp_dir  = apply_filters( 'pmpro_membership_stats_csv_export_tmp_dir', sys_get_temp_dir() );
$filename = tempnam( $tmp_dir, 'pmpro_olcsv_' );

// open in append mode
$csv_fh = fopen( $filename, 'a' );

// write the CSV header to the file
fprintf( $csv_fh, '%s', $csv_file_header );

$user_ids    = $wpdb->get_col( $sqlQuery );
$users_found = count( $user_ids );

if ( empty( $user_ids ) ) {
	// send data to remote browser
	pmpro_transmit_report_data( $csv_fh, $filename, $headers );
}

if ( PMPRO_BENCHMARK ) {
	$pre_action_time   = microtime( true );
	$pre_action_memory = memory_get_usage( true );
}

do_action( 'pmpro_before_membership_stats_csv_export', $user_ids );

$i_start    = 0;
$i_limit    = 0;
$iterations = 1;

if ( $users_found >= $max_record_loops ) {
	$iterations = ceil( $users_found / $max_record_loops );
	$i_limit    = $max_record_loops;
}

$end        = 0;
$time_limit = ini_get( 'max_execution_time' );

if ( PMPRO_BENCHMARK ) {
	error_log( "PMPRO_BENCHMARK - Total records to process: {$users_found}" );
	error_log( "PMPRO_BENCHMARK - Will process {$iterations} iterations of max {$max_record_loops} records per iteration." );
	$pre_iteration_time   = microtime( true );
	$pre_iteration_memory = memory_get_usage( true );
}

for ( $ic = 1; $ic <= $iterations; $ic ++ ) {

	if ( PMPRO_BENCHMARK ) {
		$start_iteration_time   = microtime( true );
		$start_iteration_memory = memory_get_usage( true );
	}

	// avoiding timeouts (modify max run-time for export)
	if ( $end != 0 ) {

		$iteration_diff = $end - $start;
		$new_time_limit = ceil( $iteration_diff * $iterations * 1.2 );

		if ( $time_limit < $new_time_limit ) {
			$time_limit = $new_time_limit;
			set_time_limit( $time_limit );
		}
	}


	// get the user list we should process
	$user_list = array_slice( $user_ids, $i_start, $max_record_loops );

	if ( PMPRO_BENCHMARK ) {
		$pre_data_time   = microtime( true );
		$pre_data_memory = memory_get_usage( true );
	}

	foreach ( $dates as $each_date ) {

		$csvoutput = array();

		if ( ! empty( $default_columns ) ) {
			$count = 0;

			foreach ( $default_columns as $col ) {

				// checking $object->property. note the double $$
				switch ( count( $col ) ) {
					case 3:
						$val = isset( ${$col[0]}->{$col[1]}->{$col[2]} ) ? ${$col[0]}->{$col[1]}->{$col[2]} : null;
						break;

					case 2:
						$val = isset( ${$col[0]}->{$col[1]} ) ? ${$col[0]}->{$col[1]} : null;
						break;

					default:
						$val = null;
				}
				
				array_push( $csvoutput, pmpro_enclose( $val ) );
			}
		}

		$line = implode( ',', $csvoutput ) . "\n";

		// output
		fprintf( $csv_fh, '%s', $line );

		$line      = null;
		$csvoutput = null;

		$end = current_time( 'timestamp' );



	} // end of foreach users.

	if ( PMPRO_BENCHMARK ) {
		$after_data_time   = microtime( true );
		$after_data_memory = memory_get_peak_usage( true );

		$time_processing_data   = $after_data_time - $start_time;
		$memory_processing_data = $after_data_memory - $start_memory;

		list($sec, $usec) = explode( '.', $time_processing_data );

		error_log( "PMPRO_BENCHMARK - Time processing data: {$sec}.{$usec} seconds" );
		error_log( 'PMPRO_BENCHMARK - Peak memory usage: ' . number_format( $memory_processing_data, false, '.', ',' ) . ' bytes' );
	}
	$user_list = null;
	wp_cache_flush();
}
pmpro_transmit_report_data( $csv_fh, $filename, $headers );


/**
 * Enclose items passed through to ensure data structure is valid for export CSV.
 *
 * @param mixed $string|$date Enclose and return a given string, required for CSV files.
 * @return string
 */
function pmpro_enclose( $s ) {
	return '"' . str_replace( '"', '\\"', $s ) . '"';
}

/**
 * Write the data to the CSV and create the CSV file.
 *
 * @param mixed $csv_fh The temp file we opened and write to.
 * @param string $filename The name of the CSV file.
 * @param array $headers The headers for the CSV file.
 * @return void
 */
function pmpro_transmit_report_data( $csv_fh, $filename, $headers = array() ) {

	// close the temp file
	fclose( $csv_fh );

	if ( version_compare( phpversion(), '5.3.0', '>' ) ) {

		// make sure we get the right file size
		clearstatcache( true, $filename );
	} else {
		// for any PHP version prior to v5.3.0
		clearstatcache();
	}

	// did we accidentally send errors/warnings to browser?
	if ( headers_sent() ) {
		echo str_repeat( '-', 75 ) . "<br/>\n";
		echo 'Please open a support case and paste in the warnings/errors you see above this text to\n ';
		echo 'the <a href="http://paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-membership-stats-csv&utm_campaign=support" target="_blank">Paid Memberships Pro support forum</a><br/>\n';
		echo str_repeat( '=', 75 ) . "<br/>\n";
		echo file_get_contents( $filename );
		echo str_repeat( '=', 75 ) . "<br/>\n";
	}

	// transmission
	if ( ! empty( $headers ) ) {
		// set the download size
		$headers[] = 'Content-Length: ' . filesize( $filename );

		// set headers
		foreach ( $headers as $header ) {
			header( $header . "\r\n" );
		}

		// disable compression for the duration of file download
		if ( ini_get( 'zlib.output_compression' ) ) {
			ini_set( 'zlib.output_compression', 'Off' );
		}

		if ( function_exists( 'fpassthru' ) ) {
			// use fpassthru to output the csv
			$csv_fh = fopen( $filename, 'rb' );
			fpassthru( $csv_fh );
			fclose( $csv_fh );
		} else {
			// use readfile() if fpassthru() is disabled (like on Flywheel Hosted)
			readfile( $filename );
		}

		// remove the temp file
		unlink( $filename );
	}

	// allow user to clean up after themselves
	do_action( 'pmpro_after_membership_stats_csv_export' );
	exit;
}
