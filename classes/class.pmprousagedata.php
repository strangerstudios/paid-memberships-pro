<?php
class PMProUsageData {

	/**
	 * API URL
	 *
	 * @since 1.9
	 *
	 * @var string
	 */
	protected $apiUrl = 'https://asimov.paidmembershipspro.com';

	/**
	 * Collected stats
	 *
	 * NOTE: is set using $this->collectStats() $this->getStats() is the public getter, which lazy loads it
	 *
	 * @since 1.9
	 *
	 * @var array
	 */
	protected $stats;

	/**
	 * Name of option key used to track optin status
	 *
	 * True means allowed, false means not allowed, null means no answer yet.
	 *
	 * @since 1.9
	 *
	 * @var string
	 */
	protected $optinKey = '_pmpro_tracking_allowed';

	/**
	 * Name of option key for tracking last time things were sent
	 *
	 * Stored as unix timestamp $this->getLastSent() is public getter and can translate to MySQL format
	 *
	 * @since 1.9
	 *
	 * @var string
	 */
	protected $trackingKey = '_pmpro_tracking_last_sent';


	/**
	 * The "main" instance
	 *
	 * @since 1.9
	 *
	 * @var PMProUsageData
	 */
	protected static $instance;

	/**
	 * PMProUsageData constructor.
	 *
	 * @since 1.9
	 */
	public function __construct()
	{
		//NOTE: this doesn't need to do anything, but implies that you can reuse this class, mainly so you can extend it for other data sets
	}

	/**
	 * Get "main" instance of this class
	 *
	 * NOTE: Not a true-singleton, can make multiple instances.
	 *
	 * @since 1.9
	 *
	 * @return  PMProUsageData
	 */
	public static function get_main_instance()
	{
		if( ! is_object( self::$instance ) ){
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Get usage stats
	 *
	 * Will lazy-load data
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getStats()
	{
		if( empty( $this->stats ) ){
			$this->collectStats();
		}

		return $this->stats;

	}

	/**
	 * Collect the usage data
	 *
	 * @since 1.9
	 */
	protected function collectStats()
	{
		global  $wp_version;
		//@TODO Jason - populate this with array one index per level, with number of members array( 0 => 42, 1 => 3, 2 =>  400000 );
		//BTW Array keys probably should be level ID.
		$levels = array();

		//@TODO Jason - populate this with array of gateways array( 0 => 'stripe', 1 => 'paypal' );
		$gateways = array();

		//@TODO Jason give this total numbers of sales
		$sales = array(
			'live' => 0,
			'test' => 0
		);


		$this->stats = array(
			'url' =>home_url() ,
			'email' => get_option( 'admin_email' ),
			'plugins' => pmpro_getPlugins(),
			'wp_version' => $wp_version,
			'php_version' => PHP_VERSION,
			'pmpro_num_levels' => count( $levels ),
			'pmpro_members_per_level' => $levels,
			'pmpro_gatway' => $gateways,
			//@TODO
			'pmpro_license' => '',
			//@TODO
			'pmpro_num_users' => 0,
			//@TODO
			'pmpro_environment' => 'sandbox',
			//@TODO
			'pmpro_total_revenue' => 0
		);

	}

	/**
	 * Record that usage tracking is allowed for this site
	 *
	 * @since 1.9
	 */
	public function optin()
	{
		update_option( $this->optinKey, true );
	}

	/**
	 * Record that usage tracking IS NOT allowed for this site
	 *
	 * @since 1.9
	 */
	public function optOut()
	{
		update_option( $this->optinKey, false );

	}

	/**
	 * Send the data
	 *
	 * @since 1.9
	 *
	 * @return array|bool|WP_Error Returns false if trying to send more than once in a day. Or will return what wp_remote_response() returns from API call
	 */
	public function send()
	{
		if( time() < $this->getLastSent() + DAY_IN_SECONDS ){
			return false;
		}

		return $this->sendStats();

	}

	/**
	 * Is tracking allowed?
	 *
	 * @since 1.9
	 *
	 * @return bool True if allowed. False if not
	 */
	public function canTrack()
	{
		if( true === $this->getOptinStatus() ){
			return true;
		}

		return false;
	}

	/**
	 * Should we ask for optin
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function shouldAskForOption()
	{
		if ( null === $this->getOptinStatus() ){
			return true;
		}

		return false;
	}

	/**
	 * Find last time we sent data to API
	 *
	 * @since 1.9
	 *
	 * @param bool $unix Optional. If true, the default, UNIX timestap is returned. Formatted to human-readable local time if false.
	 *
	 * @return string|int
	 */
	public function getLastSent( $unix = true )
	{
		$time = get_option( $this->trackingKey, 0 );
		if( ! $unix ){
			$time = gmdate( 'Y-m-d H:i:s', ( $time + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
		}

		return $time;
	}

	/**
	 * Get value of optin tracking key.
	 *
	 * @since 1.9
	 *
	 * @return bool|null
	 */
	protected function getOptinStatus()
	{
		return get_option( $this->optinKey, null );
	}

	/**
	 * Update last sent tracking
	 *
	 * @since 1.9
	 *
	 * @param int $time Optional. Time to record, in UNIX timestamp. Defaults to current time.
	 */
	protected function writeLastSent( $time = 0 )
	{
		if( ! $time ){
			$time = time();
		}

		update_option( $this->trackingKey, $time );
	}

	/**
	 * Send to remote API
	 *
	 * @since 1.9
	 *
	 * @return array|WP_Error
	 */
	protected function sendStats()
	{
		$this->getStats();
		$r = wp_remote_request( $this->apiUrl,
			array(
				'method' => 'PUT',
				'body' => wp_json_encode( $this->stats )
			)
		);

		if( 200 == wp_remote_retrieve_response_code( $r ) ){
			$this->writeLastSent();
		}

		return $r;

	}
}