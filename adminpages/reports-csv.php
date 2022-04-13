<?php
//only admins can get this
if ( ! function_exists( "current_user_can" ) || ( ! current_user_can( "manage_options" ) && ! current_user_can( "pmpro_salesreport_csv" ) ) ) {
	die( __( "You do not have permissions to perform this action.", 'paid-memberships-pro' ) );
}

$sales_data = get_transient( 'pmpro_sales_data' );

if ( empty( $sales_data ) ) {
}

define('PMPRO_BENCHMARK', true);

if (!defined('PMPRO_BENCHMARK'))
	define('PMPRO_BENCHMARK', false);

$start_memory = memory_get_usage(true);
$start_time = microtime(true);

if (true === PMPRO_BENCHMARK)
{
	error_log(str_repeat('-', 10) . date_i18n('Y-m-d H:i:s') . str_repeat('-', 10));
}

/**
 * Filter to set max number of sales records to process at a time
 * for the export (helps manage memory footprint)
 *
 * NOTE: Use the pmpro_before_orders_list_csv_export hook to increase memory "on-the-fly"
 *       Can reset with the pmpro_after_orders_list_csv_export hook
 *
 * @since TBD
 */
//set the number of orders we'll load to try and protect ourselves from OOM errors
$max_orders_per_loop = apply_filters( 'pmpro_set_max_sales_per_export_loop', 2000 );

$headers   = array();
$headers[] = "Content-Type: text/csv";
$headers[] = "Cache-Control: max-age=0, no-cache, no-store";
$headers[] = "Pragma: no-cache";
$headers[] = "Connection: close";

$filename = "sales-revenue.csv";
/*
	Insert logic here for building filename from $filter and other values.
*/
$filename  = apply_filters( 'pmpro_sales_revenue_csv_export_filename', $filename );
$headers[] = "Content-Disposition: attachment; filename={$filename};";

$csv_file_header_array = array(
	"date",
	"value",
	"renewals",
);

//these are the meta_keys for the fields (arrays are object, property. so e.g. $theuser->ID)
$default_columns = array(
	array( "date", "date" ),
	array( "value", "value" ),
	array( "renewals", "renewals" ),
);


$default_columns = apply_filters( "pmpro_order_list_csv_default_columns", $default_columns );

$csv_file_header_array = apply_filters( "pmpro_order_list_csv_export_header_array", $csv_file_header_array );

$dateformat = apply_filters( 'pmpro_order_list_csv_dateformat', get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

//any extra columns
$extra_columns = apply_filters( "pmpro_orders_csv_extra_columns", array() );	//the original filter
$extra_columns = apply_filters( "pmpro_order_list_csv_extra_columns", $extra_columns );	//in case anyone used the typo'd filter

if ( ! empty( $extra_columns ) ) {
	foreach ( $extra_columns as $heading => $callback ) {
		$csv_file_header_array[] = $heading;
	}
}

$csv_file_header = implode( ',', $csv_file_header_array ) . "\n";

// Generate a temporary file to store the data in.
$tmp_dir  = apply_filters( 'pmpro_order_list_csv_export_tmp_dir', sys_get_temp_dir() );

$filename = tempnam( $tmp_dir, 'pmpro_salescsv_' );

// open in append mode
$csv_fh = fopen( $filename, 'a' );

//write the CSV header to the file
fprintf( $csv_fh, '%s', $csv_file_header );

$orders_found = count( $sales_data );

// If no data found, just create an empty CSV - this is how other CSV functionality is handled.
if ( empty( $orders_found ) ) {
	pmpro_transmit_order_content( $csv_fh, $filename, $headers );
}

if (PMPRO_BENCHMARK)
{
	$pre_action_time = microtime(true);
	$pre_action_memory = memory_get_usage(true);
}

do_action('pmpro_before_order_list_csv_export', $sales_data);

$i_start    = 0;
$i_limit    = 0;
$iterations = 1;

if ( $orders_found >= $max_orders_per_loop ) {
	$iterations = ceil( $orders_found / $max_orders_per_loop );
	$i_limit    = $max_orders_per_loop;
}

$end        = 0;
$time_limit = ini_get( 'max_execution_time' );

if (PMPRO_BENCHMARK)
{
	error_log("PMPRO_BENCHMARK - Total records to process: {$orders_found}");
	error_log("PMPRO_BENCHMARK - Will process {$iterations} iterations of max {$max_orders_per_loop} records per iteration.");
	$pre_iteration_time = microtime(true);
	$pre_iteration_memory = memory_get_usage(true);
}

for ( $ic = 1; $ic <= $iterations; $ic ++ ) {

	if (PMPRO_BENCHMARK)
	{
		$start_iteration_time = microtime(true);
		$start_iteration_memory = memory_get_usage(true);
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

	$start = current_time( 'timestamp' );

	//increment starting position
	if ( $ic > 1 ) {
		$i_start += $max_orders_per_loop;
	}

	if ( PMPRO_BENCHMARK ) {
		$pre_orderdata_time = microtime(true);
		$pre_orderdata_memory = memory_get_usage(true);
	}

	foreach ( $sales_data as $sales ) {

		$csvoutput = array();

		//default columns
		if ( ! empty( $default_columns ) ) {
			foreach ( $default_columns as $col ) {			
				array_push( $csvoutput, pmpro_enclose( $sales->{$col[1]} ) );
			}
		}

		//any extra columns
		if ( ! empty( $extra_columns ) ) {
			foreach ( $extra_columns as $heading => $callback ) {
				$val = call_user_func( $callback, $order );
				$val = ! empty( $val ) ? $val : null;

				array_push( $csvoutput, pmpro_enclose( $val ) );
			}
		}

		$line = implode( ',', $csvoutput ) . "\n";

		//output
		fprintf( $csv_fh, "%s", $line );

		$line      = null;
		$csvoutput = null;

		$end = current_time( 'timestamp' );

	} // end of foreach orders

	if (PMPRO_BENCHMARK)
	{
		$after_data_time = microtime(true);
		$after_data_memory = memory_get_peak_usage(true);

		$time_processing_data = $after_data_time - $start_time;
		$memory_processing_data = $after_data_memory - $start_memory;

		list($sec, $usec) = explode('.', $time_processing_data);

		error_log("PMPRO_BENCHMARK - Time processing data: {$sec}.{$usec} seconds");
		error_log("PMPRO_BENCHMARK - Peak memory usage: " . number_format($memory_processing_data, false, '.', ',') . " bytes");
	}

	wp_cache_flush();
}
pmpro_transmit_order_content( $csv_fh, $filename, $headers );

function pmpro_enclose( $s ) {
	return "\"" . str_replace( "\"", "\\\"", $s ) . "\"";
}

function pmpro_transmit_order_content( $csv_fh, $filename, $headers = array() ) {

	//close the temp file
	fclose( $csv_fh );

	if ( version_compare( phpversion(), '5.3.0', '>' ) ) {

		//make sure we get the right file size
		clearstatcache( true, $filename );
	} else {
		// for any PHP version prior to v5.3.0
		clearstatcache();
	}

	//did we accidentally send errors/warnings to browser?
	if ( headers_sent() ) {
		echo str_repeat( '-', 75 ) . "<br/>\n";
		echo 'Please open a support case and paste in the warnings/errors you see above this text to\n ';
		echo 'the <a href="http://paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-orders-csv&utm_campaign=support" target="_blank">Paid Memberships Pro support forum</a><br/>\n';
		echo str_repeat( "=", 75 ) . "<br/>\n";
		echo file_get_contents( $filename );
		echo str_repeat( "=", 75 ) . "<br/>\n";
	}

	//transmission
	if ( ! empty( $headers ) ) {
		//set the download size
		$headers[] = "Content-Length: " . filesize( $filename );

		//set headers
		foreach ( $headers as $header ) {
			header( $header . "\r\n" );
		}

		// disable compression for the duration of file download
		if ( ini_get( 'zlib.output_compression' ) ) {
			ini_set( 'zlib.output_compression', 'Off' );
		}

		if( function_exists( 'fpassthru' ) ) {
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

	//allow user to clean up after themselves
	do_action( 'pmpro_after_order_csv_export' );
	exit;
}