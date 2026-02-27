<?php
//only admins can get this
if ( ! function_exists( "current_user_can" ) || ( ! current_user_can( "manage_options" ) && ! current_user_can( "pmpro_orderscsv" ) ) ) {
	die( esc_html__( "You do not have permissions to perform this action.", 'paid-memberships-pro' ) );
}

if (!defined('PMPRO_BENCHMARK'))
	define('PMPRO_BENCHMARK', false);

$start_memory = memory_get_usage(true);
$start_time = microtime(true);

if (true === PMPRO_BENCHMARK)
{
	error_log(str_repeat('-', 10) . date_i18n('Y-m-d H:i:s') . str_repeat('-', 10));
}

/**
 * Filter to set max number of order records to process at a time
 * for the export (helps manage memory footprint)
 *
 * NOTE: Use the pmpro_before_orders_list_csv_export hook to increase memory "on-the-fly"
 *       Can reset with the pmpro_after_orders_list_csv_export hook
 *
 * @since 1.8.9
 */
//set the number of orders we'll load to try and protect ourselves from OOM errors
$max_orders_per_loop = apply_filters( 'pmpro_set_max_orders_per_export_loop', 2000 );

global $wpdb;

// Get search and pagination params.
$s = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';

if ( ! empty( $_REQUEST['pn'] ) ) {
	$pn = intval( $_REQUEST['pn'] );
} else {
	$pn = 1;
}

if ( ! empty( $_REQUEST['limit'] ) ) {
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

$now = current_time( 'timestamp' );

// Build filter conditions. Supports multiple simultaneous filters combined with AND.
$conditions = array();
$needs_discount_code_join = false;

// Legacy support: translate old single filter= param to new-style individual params.
if ( ! empty( $_REQUEST['filter'] ) && $_REQUEST['filter'] !== 'all' ) {
	$legacy_filter = sanitize_text_field( $_REQUEST['filter'] );
	switch ( $legacy_filter ) {
		case 'within-a-date-range':
			if ( empty( $_REQUEST['start-date'] ) ) {
				$start_month = isset( $_REQUEST['start-month'] ) ? intval( $_REQUEST['start-month'] ) : 1;
				$start_day   = isset( $_REQUEST['start-day'] ) ? intval( $_REQUEST['start-day'] ) : 1;
				$start_year  = isset( $_REQUEST['start-year'] ) ? intval( $_REQUEST['start-year'] ) : date( 'Y', $now );
				$end_month   = isset( $_REQUEST['end-month'] ) ? intval( $_REQUEST['end-month'] ) : date( 'n', $now );
				$end_day     = isset( $_REQUEST['end-day'] ) ? intval( $_REQUEST['end-day'] ) : date( 'j', $now );
				$end_year    = isset( $_REQUEST['end-year'] ) ? intval( $_REQUEST['end-year'] ) : date( 'Y', $now );
				$_REQUEST['start-date'] = sprintf( '%04d-%02d-%02d', $start_year, $start_month, $start_day );
				$_REQUEST['end-date']   = sprintf( '%04d-%02d-%02d', $end_year, $end_month, $end_day );
			}
			break;
		case 'predefined-date-range':
			if ( empty( $_REQUEST['predefined-date'] ) ) {
				$_REQUEST['predefined-date'] = 'This Month';
			}
			break;
		case 'only-paid':
			$_REQUEST['total'] = 'paid';
			break;
		case 'only-free':
			$_REQUEST['total'] = 'free';
			break;
	}
}

// Level filter.
$l = isset( $_REQUEST['l'] ) ? intval( $_REQUEST['l'] ) : 0;
if ( ! empty( $l ) ) {
	$conditions[] = $wpdb->prepare( 'o.membership_id = %d', $l );
}

// Status filter.
$status = isset( $_REQUEST['status'] ) ? sanitize_text_field( $_REQUEST['status'] ) : '';
if ( ! empty( $status ) ) {
	$conditions[] = $wpdb->prepare( "o.status = %s", $status );
}

// Discount code filter.
$discount_code = isset( $_REQUEST['discount-code'] ) ? intval( $_REQUEST['discount-code'] ) : 0;
if ( ! empty( $discount_code ) ) {
	$conditions[] = $wpdb->prepare( 'dc.code_id = %d', $discount_code );
	$needs_discount_code_join = true;
}

// Date filter (predefined or custom range).
$predefined_date = isset( $_REQUEST['predefined-date'] ) ? sanitize_text_field( $_REQUEST['predefined-date'] ) : '';
$start_date_input = isset( $_REQUEST['start-date'] ) ? sanitize_text_field( $_REQUEST['start-date'] ) : '';
$end_date_input = isset( $_REQUEST['end-date'] ) ? sanitize_text_field( $_REQUEST['end-date'] ) : '';

if ( ! empty( $predefined_date ) ) {
	if ( $predefined_date == 'Last Month' ) {
		$start_date = date( 'Y-m-d', strtotime( 'first day of last month', $now ) );
		$end_date   = date( 'Y-m-d', strtotime( 'last day of last month', $now ) );
	} elseif ( $predefined_date == 'This Month' ) {
		$start_date = date( 'Y-m-d', strtotime( 'first day of this month', $now ) );
		$end_date   = date( 'Y-m-d', strtotime( 'last day of this month', $now ) );
	} elseif ( $predefined_date == 'This Year' ) {
		$year       = date( 'Y', $now );
		$start_date = date( 'Y-m-d', strtotime( "first day of January $year", $now ) );
		$end_date   = date( 'Y-m-d', strtotime( "last day of December $year", $now ) );
	} elseif ( $predefined_date == 'Last Year' ) {
		$year       = date( 'Y', $now ) - 1;
		$start_date = date( 'Y-m-d', strtotime( "first day of January $year", $now ) );
		$end_date   = date( 'Y-m-d', strtotime( "last day of December $year", $now ) );
	}

	if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
		$start_date = get_gmt_from_date( $start_date . ' 00:00:00' );
		$end_date   = get_gmt_from_date( $end_date . ' 23:59:59' );
		$conditions[] = $wpdb->prepare( "o.timestamp BETWEEN %s AND %s", $start_date, $end_date );
	}
} elseif ( ! empty( $start_date_input ) && ! empty( $end_date_input ) ) {
	$start_date = get_gmt_from_date( $start_date_input . ' 00:00:00' );
	$end_date   = get_gmt_from_date( $end_date_input . ' 23:59:59' );
	$conditions[] = $wpdb->prepare( "o.timestamp BETWEEN %s AND %s", $start_date, $end_date );
}

// Gateway filter.
$gateway = isset( $_REQUEST['gateway'] ) ? sanitize_text_field( $_REQUEST['gateway'] ) : '';
if ( ! empty( $gateway ) ) {
	$conditions[] = $wpdb->prepare( "o.gateway = %s", $gateway );
}

// Total filter.
$total_filter = isset( $_REQUEST['total'] ) ? sanitize_text_field( $_REQUEST['total'] ) : '';
if ( $total_filter === 'paid' ) {
	$conditions[] = "o.total > 0";
} elseif ( $total_filter === 'free' ) {
	$conditions[] = "o.total = 0";
}

// Combine conditions with AND.
if ( empty( $conditions ) ) {
	$condition = '1=1';
} else {
	$condition = implode( ' AND ', $conditions );
}

// Backward-compatible hook.
$filter = ! empty( $_REQUEST['filter'] ) ? sanitize_text_field( $_REQUEST['filter'] ) : 'all';
$condition = apply_filters( 'pmpro_admin_orders_query_condition', $condition, $filter );

//string search
if ( ! empty( $s ) ) {
	$sqlQuery = "
		SELECT SQL_CALC_FOUND_ROWS o.id
		FROM {$wpdb->pmpro_membership_orders} AS o
			LEFT JOIN $wpdb->users u ON o.user_id = u.ID
			LEFT JOIN $wpdb->pmpro_membership_levels l ON o.membership_id = l.id
		";

	// Join with discount codes if filtering by discount code.
	if ( $needs_discount_code_join ) {
		$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON o.id = dc.order_id ";
	}

	// Check if we are searching by a specific key or generally.
	if ( strpos( $s, ':' ) !== false ) {
		// Get the search key and value.
		$parts = explode( ':', $s );
		$search_key = array_shift( $parts );
		$s = implode( ':', $parts );

		$sqlQuery .= 'WHERE (1=2 ';
		// If there's a colon in the search string, make the search smarter.
		if ( in_array( $search_key, array( 'login', 'nicename', 'email', 'url', 'display_name' ), true ) ) {
			$key_column = 'u.user_' . $search_key; // All search key options above are safe for use in a query.
			$sqlQuery .= " OR $key_column LIKE '%" . esc_sql( $s ) . "%' ";
		} else {
			// Assume order table column.
			$sqlQuery .= " OR o.$search_key LIKE '%" . esc_sql( $s ) . "%' ";
		}
		$sqlQuery .= ') ';
	} else {
		$join_with_usermeta = apply_filters( "pmpro_orders_search_usermeta", false );

		if ( ! empty( $join_with_usermeta ) ) {
			$sqlQuery .= "LEFT JOIN $wpdb->usermeta um ON o.user_id = um.user_id ";
		}

		$sqlQuery .= "WHERE (1=2 ";

		$fields = array(
			'o.id',
			'o.code',
			'o.billing_name',
			'o.billing_street',
			'o.billing_street2',
			'o.billing_city',
			'o.billing_state',
			'o.billing_zip',
			'o.billing_country',
			'o.billing_phone',
			'o.payment_type',
			'o.cardtype',
			'o.accountnumber',
			'o.status',
			'o.gateway',
			'o.gateway_environment',
			'o.payment_transaction_id',
			'o.subscription_transaction_id',
			'o.notes',
			'u.user_login',
			'u.user_email',
			'u.display_name',
			'ml.name',
		);

		if ( ! empty( $join_with_usermeta ) ) {
			$fields[] = "um.meta_value";
		}

		$fields = apply_filters( "pmpro_orders_search_fields", $fields );

		foreach ( $fields as $field ) {
			$sqlQuery .= " OR " . esc_sql( $field ) . " LIKE '%" . esc_sql( $s ) . "%' ";
		}

		$sqlQuery .= ") ";
	}
	//Not escaping here because we escape the values in the condition statement
	$sqlQuery .= "AND " . $condition . " ";
	$sqlQuery .= "GROUP BY o.id ORDER BY o.id DESC, o.timestamp DESC ";

} else {
	$sqlQuery = "SELECT SQL_CALC_FOUND_ROWS o.id FROM $wpdb->pmpro_membership_orders o ";

	if ( $needs_discount_code_join ) {
		$sqlQuery .= "LEFT JOIN $wpdb->pmpro_discount_codes_uses dc ON o.id = dc.order_id ";
	}
	//Not escaping here because we escape the values in the condition statement
	$sqlQuery .= "WHERE " . $condition . ' ORDER BY o.id DESC, o.timestamp DESC ';
}

if ( ! empty( $start ) && ! empty( $limit ) ) {
	$sqlQuery .= "LIMIT " . (int) $start . "," . (int) $limit;
}

$headers   = array();
$headers[] = "Content-Type: text/csv";
$headers[] = "Cache-Control: max-age=0, no-cache, no-store";
$headers[] = "Pragma: no-cache";
$headers[] = "Connection: close";

$filename = "orders.csv";
/*
	Insert logic here for building filename from $filter and other values.
*/
$filename  = apply_filters( 'pmpro_orders_csv_export_filename', $filename );
$headers[] = "Content-Disposition: attachment; filename={$filename};";

$csv_file_header_array = array(
	"id",
	"code",
	"user_id",
	"user_login",
	"first_name",
	"last_name",
	"user_email",
	"billing_name",
	"billing_street",
	"billing_city",
	"billing_state",
	"billing_zip",
	"billing_country",
	"billing_phone",
	"membership_id",
	"level_name",
	"subtotal",
	"tax",
	"total",
	"payment_type",
	"cardtype",
	"accountnumber",
	"expirationmonth",
	"expirationyear",
	"status",
	"gateway",
	"gateway_environment",
	"payment_transaction_id",
	"subscription_transaction_id",
	"discount_code_id",
	"discount_code",
	"timestamp"
);

//these are the meta_keys for the fields (arrays are object, property. so e.g. $theuser->ID)
$default_columns = array(
	array( "order", "id" ),
	array( "order", "code" ),
	array( "user", "ID" ),
	array( "user", "user_login" ),
	array( "user", "first_name" ),
	array( "user", "last_name" ),
	array( "user", "user_email" ),
	array( "order", "billing", "name" ),
	array( "order", "billing", "street" ),
	array( "order", "billing", "city" ),
	array( "order", "billing", "state" ),
	array( "order", "billing", "zip" ),
	array( "order", "billing", "country" ),
	array( "order", "billing", "phone" ),
	array( "order", "membership_id" ),
	array( "level", "name" ),
	array( "order", "subtotal" ),
	array( "order", "tax" ),
	array( "order", "total" ),
	array( "order", "payment_type" ),
	array( "order", "cardtype" ),
	array( "order", "accountnumber" ),
	array( "order", "expirationmonth" ),
	array( "order", "expirationyear" ),
	array( "order", "status" ),
	array( "order", "gateway" ),
	array( "order", "gateway_environment" ),
	array( "order", "payment_transaction_id" ),
	array( "order", "subscription_transaction_id" ),
	array( "discount_code", "id" ),
	array( "discount_code", "code" )
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
$filename = tempnam( $tmp_dir, 'pmpro_olcsv_' );

// open in append mode
$csv_fh = fopen( $filename, 'a' );

//write the CSV header to the file
fprintf( $csv_fh, '%s', $csv_file_header );

$order_ids    = $wpdb->get_col( $sqlQuery );
$orders_found = count( $order_ids );

if ( empty( $order_ids ) ) {
	// send data to remote browser
	pmpro_transmit_order_content( $csv_fh, $filename, $headers );
}

if (PMPRO_BENCHMARK)
{
	$pre_action_time = microtime(true);
	$pre_action_memory = memory_get_usage(true);
}

do_action('pmpro_before_order_list_csv_export', $order_ids);

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

	// get the first order id
	$first_oid = $order_ids[ $i_start ];

	//get last UID, will depend on which iteration we're on.
	if ( $ic != $iterations ) {
		$last_oid = $order_ids[ ( $i_start + ( $max_orders_per_loop - 1 ) ) ];
	} else {
		// Final iteration, so last UID is the last record in the users array
		$last_oid = $order_ids[ ( $orders_found - 1 ) ];
	}

	//increment starting position
	if ( $ic > 1 ) {
		$i_start += $max_orders_per_loop;
	}
	// get the order list we should process
	$order_list = array_slice( $order_ids, $i_start, $max_orders_per_loop );

	if (PMPRO_BENCHMARK)
	{
		$pre_orderdata_time = microtime(true);
		$pre_orderdata_memory = memory_get_usage(true);
	}

	foreach ( $order_list as $order_id ) {
		$csvoutput = array();

		$order            = new MemberOrder();
		$order->nogateway = true;

		$order->getMemberOrderByID( $order_id );

		$user  = get_userdata( $order->user_id );
		$level = $order->getMembershipLevel();

		$sqlQuery = $wpdb->prepare( "
			SELECT c.id, c.code
			FROM {$wpdb->pmpro_discount_codes_uses} AS cu
				LEFT JOIN {$wpdb->pmpro_discount_codes} AS c
				ON cu.code_id = c.id
			WHERE cu.order_id = %s
			LIMIT 1",
			$order_id
		);

		$discount_code = $wpdb->get_row( $sqlQuery );

		//default columns
		if ( ! empty( $default_columns ) ) {
			$count = 0;
			foreach ( $default_columns as $col ) {

				//checking $object->property. note the double $$
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

		//timestamp
		$ts = date_i18n( $dateformat, $order->getTimestamp() );
		array_push( $csvoutput, pmpro_enclose( $ts ) );

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
	$order_list = null;
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
		echo esc_html( str_repeat( '-', 75 ) ) . "<br/>\n";
		echo 'Please open a support case and paste in the warnings/errors you see above this text to\n ';
		echo 'the <a href="http://paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-orders-csv&utm_campaign=support" target="_blank">Paid Memberships Pro support forum</a><br/>\n';
		echo esc_html( str_repeat( '-', 75 ) ) . "<br/>\n";
		echo wp_kses_post( file_get_contents( $filename ) );
		echo esc_html( str_repeat( '-', 75 ) ) . "<br/>\n";
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
