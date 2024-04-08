<?php

// only admins can get this
if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'pmpro_loginscsv' ) ) ) {
	die( esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
}

if ( ! defined( 'PMPRO_BENCHMARK' ) ) {
	define( 'PMPRO_BENCHMARK', false );
}

$start_memory = memory_get_usage( true );
$start_time   = microtime( true );

if ( true === PMPRO_BENCHMARK ) {
	error_log( str_repeat( '-', 10 ) . date_i18n( 'Y-m-d H:i:s' ) . str_repeat( '-', 10 ) );
}

/**
 * Filter to set max number records to process at a time
 * for the export (helps manage memory footprint)
 *
 * NOTE: Use the pmpro_before_visits_views_logins_csv_export hook to increase memory "on-the-fly"
 *       Can reset with the pmpro_after_visits_views_logins_csv_export hook
 *
 * @since 2.9
 */
// set the number of records we'll load to try and protect ourselves from OOM errors
$max_record_loops = apply_filters( 'pmpro_set_max_visits_views_logins_records_per_export_loop', 2000 );
global $wpdb;

	// vars
if ( ! empty( $_REQUEST['s'] ) ) {
	$s = sanitize_text_field( $_REQUEST['s'] );
} else {
	$s = '';
}

if ( ! empty( $_REQUEST['l'] ) ) {
	if ( $_REQUEST['l'] == 'all' ) {
		$l = 'all';
	} else {
		$l = intval( $_REQUEST['l'] );
	}
} else {
	$l = '';
}

// some vars for the search
if ( isset( $_REQUEST['pn'] ) ) {
	$pn = intval( $_REQUEST['pn'] );
} else {
	$pn = 1;
}

if ( isset( $_REQUEST['limit'] ) ) {
	$limit = intval( $_REQUEST['limit'] );
} else {
	$limit = false;
}

if ( $limit ) {
	$end   = $pn * $limit;
	$start = $end - $limit;
} else {
	$end   = null;
	$start = null;
}

if ( $s ) {
	$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(CONVERT_TZ(u.user_registered, '+00:00', @@global.time_zone)) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone)) as startdate, UNIX_TIMESTAMP(CONVERT_TZ(mu.enddate, '+00:00', @@global.time_zone)) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id AND mu.status = 'active' LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id WHERE (u.user_login LIKE '%" . esc_sql( $s ) . "%' OR u.user_email LIKE '%" . esc_sql( $s ) . "%' OR um.meta_value LIKE '%" . esc_sql( $s ) . "%') ";

	if ( $l == 'all' ) {
		$sqlQuery .= " AND mu.status = 'active' AND mu.membership_id > 0 ";
	} elseif ( $l ) {
		$sqlQuery .= " AND mu.membership_id = '" . esc_sql( $l ) . "' ";
	}

	$sqlQuery .= "GROUP BY u.ID ORDER BY user_registered DESC";
} else {
	$sqlQuery  = "SELECT SQL_CALC_FOUND_ROWS u.ID, u.user_login, u.user_email, UNIX_TIMESTAMP(CONVERT_TZ(u.user_registered, '+00:00', @@global.time_zone)) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit, UNIX_TIMESTAMP(CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone)) as startdate, UNIX_TIMESTAMP(CONVERT_TZ(mu.enddate, '+00:00', @@global.time_zone)) as enddate, m.name as membership FROM $wpdb->users u LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id AND mu.status = 'active' LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id";
	$sqlQuery .= ' WHERE 1=1 ';

	if ( $l == 'all' ) {
		$sqlQuery .= " AND mu.membership_id > 0  AND mu.status = 'active' ";
	} elseif ( $l ) {
		$sqlQuery .= " AND mu.membership_id = '" . esc_sql( $l ) . "' ";
	}
	$sqlQuery .= "GROUP BY u.ID ORDER BY user_registered DESC";
}

if ( ! empty( $start ) && ! empty( $limit ) ) {
	$sqlQuery .= "LIMIT " . (int) $start . "," . (int) $limit;
}

		$sqlQuery = apply_filters( 'pmpro_visits_views_logins_csv_sql', $sqlQuery );

		$theusers = $wpdb->get_results( $sqlQuery );

$headers   = array();
$headers[] = 'Content-Type: text/csv';
$headers[] = 'Cache-Control: max-age=0, no-cache, no-store';
$headers[] = 'Pragma: no-cache';
$headers[] = 'Connection: close';

$filename = 'views-logins-visits.csv';
/*
	Insert logic here for building filename from $filter and other values.
*/
$filename  = apply_filters( 'pmpro_visits_views_logins_csv_export_filename', $filename );
$headers[] = "Content-Disposition: attachment; filename={$filename};";

// Default CSV file headers.
$csv_file_header_array = array(
	'ID',
	'username',
	'level',
	'last_visit',
	'visits_this_week',
	'visits_this_month',
	'visits_this_year',
	'visits_all_time',
	'views_this_week',
	'views_this_month',
	'views_this_year',
	'views_all_time',
	'last_login',
	'logins_this_week',
	'logins_this_month',
	'logins_this_year',
	'logins_all_time',
	'joined',
	'expires',
);

// These are the meta_keys for the fields (arrays are object, property. so e.g. $theuser->ID) - Date items are manually handled further down.
$default_columns = array(
	array( 'user', 'ID' ),
	array( 'user', 'user_login' ),
	array( 'user', 'membership' ),
	array( 'visits', 'last' ),
	array( 'visits', 'week' ),
	array( 'visits', 'month' ),
	array( 'visits', 'ytd' ),
	array( 'visits', 'alltime' ),
	array( 'views', 'week' ),
	array( 'views', 'month' ),
	array( 'views', 'ytd' ),
	array( 'views', 'alltime' ),
	array( 'logins', 'last' ),
	array( 'logins', 'week' ),
	array( 'logins', 'month' ),
	array( 'logins', 'ytd' ),
	array( 'logins', 'alltime' ),
);

$dateformat = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

$csv_file_header = implode( ',', $csv_file_header_array ) . "\n";

// Generate a temporary file to store the data in.
$tmp_dir  = apply_filters( 'pmpro_visits_views_logins_csv_export_tmp_dir', sys_get_temp_dir() );
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

do_action( 'pmpro_before_visits_views_logins_csv_export', $user_ids );

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

	foreach ( $theusers as $user ) {
		$csvoutput = array();

		// Get the visits, views and logins arrays. Set it to an object.
		$visits = (object) pmpro_reports_get_values_for_user( 'visits', $user->ID );
		$views  = (object) pmpro_reports_get_values_for_user( 'views', $user->ID );
		$logins = (object) pmpro_reports_get_values_for_user( 'logins', $user->ID );

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

		// Check if the user has an enddate or not.
		if ( $user->enddate ) {
			$enddate = pmpro_enclose( date_i18n( $dateformat, $user->enddate ) );
		} else {
			$enddate = __( 'Never', 'paid-memberships-pro' );
		}
		
		// Add joindate and enddate to the CSV export.
		array_push( $csvoutput, pmpro_enclose( date_i18n( $dateformat, $user->joindate ) ) );
		array_push( $csvoutput, $enddate ); // Don't pmpro_enclose, handled further up for date and not needed for strings.

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
		echo esc_html( str_repeat( '-', 75 ) ) . "<br/>\n";
		echo 'Please open a support case and paste in the warnings/errors you see above this text to\n ';
		echo 'the <a href="http://paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-visits-views-logins-csv&utm_campaign=support" target="_blank">Paid Memberships Pro support forum</a><br/>\n';
		echo esc_html( str_repeat( '-', 75 ) ) . "<br/>\n";
		echo wp_kses_post( file_get_contents( $filename ) );
		echo esc_html( str_repeat( '-', 75 ) ) . "<br/>\n";
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
	do_action( 'pmpro_after_visits_views_logins_csv_export' );
	exit;
}
