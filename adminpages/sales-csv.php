<?php
//only admins can get this
if ( ! function_exists( "current_user_can" ) || ( ! current_user_can( "manage_options" ) && ! current_user_can( "pmpro_sales_report_csv" ) ) ) {
	die( esc_html__( "You do not have permissions to perform this action.", 'paid-memberships-pro' ) );
}

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

if( ! empty( $_REQUEST['month'] ) ) {
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

if ( ! empty( $_REQUEST['level'] ) ) {
	$l = intval($_REQUEST['level']);
} else {
	$l = "";
}

if ( ! empty( $_REQUEST['discount_code'] ) ) {
	$discount_code = intval( $_REQUEST[ 'discount_code' ] );
} else {
	$discount_code = '';
}

// Same param hash as found in reports/sales.php.
$param_array = array( $period, $type, $month, $year, $l, $discount_code );
$param_hash = md5( implode( ' ', $param_array ) . PMPRO_VERSION );
$sales_data = get_transient( 'pmpro_sales_data_' . $param_hash );

if ( empty( $sales_data ) ) {
	die( esc_html__('Error finding report data. Make sure transients are working.', 'paid-memberships-pro' ) );
}

$headers   = array();
$headers[] = "Content-Type: text/csv";
$headers[] = "Cache-Control: max-age=0, no-cache, no-store";
$headers[] = "Pragma: no-cache";
$headers[] = "Connection: close";

// Generate a filename based on the params.
$filename = $period . "_" . $type;
if ( $period == 'daily' ) {
	$filename .= '_' . date('M', strtotime( $year . '-' . $month . '-01' ) ) . $year;
}
if ( $period == 'monthly' ) {
	$filename .= '_' . $year;
}
if ( ! empty( $l ) ) {
	$filename .= "_level" . $l;
}
if ( ! empty( $discount_code ) ) {
	$filename .= "_" . $discount_code;
}
$filename .= ".csv";
/*
	Insert logic here for building filename from $filter and other values.
*/
$filename  = apply_filters( 'pmpro_sales_revenue_csv_export_filename', $filename );
$headers[] = "Content-Disposition: attachment; filename={$filename};";

$csv_file_header_array = array(
	"date",
	"total",
	"new",
	"renewals"
);

//these are the meta_keys for the fields (arrays are object, property. so e.g. $theuser->ID)
$default_columns = array(
	array( "date", "date" ),
	array( "total", "total" ),
	array( "new", "new" ),
	array( "renewals", "renewals" )
);

$dateformat = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

$csv_file_header = implode( ',', $csv_file_header_array ) . "\n";

// Generate a temporary file to store the data in.
$tmp_dir  = apply_filters( 'pmpro_sales_report_csv_export_tmp_dir', sys_get_temp_dir() );

$filename = tempnam( $tmp_dir, 'pmpro_salescsv_' );

// open in append mode
$csv_fh = fopen( $filename, 'a' );

//write the CSV header to the file
fprintf( $csv_fh, '%s', $csv_file_header );

// If no data found, just create an empty CSV - this is how other CSV functionality is handled.
if ( empty( count( $sales_data ) ) ) {
	pmpro_transmit_report_data( $csv_fh, $filename, $headers );
}

$i_start    = 0;
$i_limit    = 2000;
$iterations = 1;

$end        = 0;
$time_limit = ini_get( 'max_execution_time' );

for ( $ic = 1; $ic <= $iterations; $ic ++ ) {

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

	foreach ( $sales_data as $sales ) {

		$csvoutput = array();

		//default columns
		if ( ! empty( $default_columns ) ) {
			foreach ( $default_columns as $col ) {			
				array_push( $csvoutput, pmpro_enclose( $sales->{$col[1]} ) );
			}
		}

		$line = implode( ',', $csvoutput ) . "\n";

		//output
		fprintf( $csv_fh, "%s", $line );

		$line      = null;
		$csvoutput = null;

		$end = current_time( 'timestamp' );

	} // end of foreach sales_data

	wp_cache_flush();
}
pmpro_transmit_report_data( $csv_fh, $filename, $headers );

function pmpro_enclose( $s ) {
	return "\"" . str_replace( "\"", "\\\"", $s ) . "\"";
}

function pmpro_transmit_report_data( $csv_fh, $filename, $headers = array() ) {

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
		echo 'the <a href="http://paidmembershipspro.com/support/?utm_source=plugin&utm_medium=pmpro-sales-revenue-csv&utm_campaign=support" target="_blank">Paid Memberships Pro support forum</a><br/>\n';
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

	exit;
}