<?php
/**
 * PMPro Pause Engine Engine.
 *
 * A modular pause system for PMPro that allows progressive
 * lockdown of site functionality during migrations and maintenance.
 *
 * @package pmpro_plugin
 * @subpackage classes
 * @since TBD
 */

/**
 * Interface for pause engine modules.
 *
 * @since TBD
 */
interface PMPro_Pause_Module_Interface {
	public function get_slug();
	public function get_label();
	public function activate();
	public function deactivate();
	public function is_active();
	public function on_resume();
}

/**
 * PMPro Pause Engine orchestrator.
 *
 * @since TBD
 */
class PMPro_Pause_Engine {

	const OPTION_KEY = 'pmpro_pause_engine';

	/**
	 * @var PMPro_Pause_Engine
	 */
	protected static $instance = null;

	/**
	 * Registered modules.
	 *
	 * @var PMPro_Pause_Module_Interface[]
	 */
	private $modules = array();

	/**
	 * Cached state from option.
	 *
	 * @var array|null
	 */
	private $state = null;

	/**
	 * Module activation order for pause.
	 *
	 * @var string[]
	 */
	private static $activation_order = array(
		'logged_in_sessions',
		'pmpro_mutations',
		'pmpro_gateways',
		'pmpro_mail',
		'frontend_block',
		'background_schedules',
	);

	/**
	 * Get the singleton instance.
	 *
	 * @return PMPro_Pause_Engine
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register built-in modules.
		$this->register_module( new PMPro_Pause_Module_Mutations() );
		$this->register_module( new PMPro_Pause_Module_Gateways() );
		$this->register_module( new PMPro_Pause_Module_Mail() );
		$this->register_module( new PMPro_Pause_Module_Schedules() );
		$this->register_module( new PMPro_Pause_Module_Frontend() );
		$this->register_module( new PMPro_Pause_Module_Sessions() );

		// Always register the email replay callback so AS can process queued emails after resume.
		add_action( 'pmpro_pause_engine_send_queued_email', array( $this, 'send_queued_email' ) );

		// Re-activate modules if pause engine is active.
		$state = $this->get_state();
		if ( ! empty( $state['enabled'] ) && ! empty( $state['modules'] ) ) {
			foreach ( $this->get_ordered_modules( $state['modules'] ) as $slug ) {
				if ( isset( $this->modules[ $slug ] ) ) {
					$this->modules[ $slug ]->activate();
				}
			}
		}
	}

	/**
	 * Register a module.
	 *
	 * @param PMPro_Pause_Module_Interface $module The module to register.
	 */
	public function register_module( PMPro_Pause_Module_Interface $module ) {
		$this->modules[ $module->get_slug() ] = $module;
	}

	/**
	 * Activate pause engine with the given modules.
	 *
	 * @param string[] $modules      Array of module slugs to enable.
	 * @param string   $activated_by Who/what activated pause engine.
	 * @return bool
	 */
	public function pause( $modules = array(), $activated_by = 'manual' ) {
		// Validate module slugs.
		$modules = array_filter( $modules, function( $slug ) {
			return isset( $this->modules[ $slug ] );
		} );

		if ( empty( $modules ) ) {
			return false;
		}

		// If already paused, merge modules.
		$state = $this->get_state();
		if ( ! empty( $state['enabled'] ) ) {
			$new_modules = array_unique( array_merge( $state['modules'], $modules ) );
			$modules_to_activate = array_diff( $modules, $state['modules'] );
			$state['modules'] = $new_modules;
			update_option( self::OPTION_KEY, $state );
			$this->state = $state;

			// Activate only newly added modules.
			foreach ( $this->get_ordered_modules( $modules_to_activate ) as $slug ) {
				$this->modules[ $slug ]->activate();

				/** Fires when a pause module is activated. */
				do_action( 'pmpro_pause_module_activated', $slug );
			}

			return true;
		}

		// Build state.
		$state = array(
			'enabled'      => true,
			'modules'      => array_values( $modules ),
			'activated_at' => time(),
			'activated_by' => $activated_by,
		);

		// Save state first so modules can read it.
		update_option( self::OPTION_KEY, $state );
		$this->state = $state;

		// Activate modules in order.
		foreach ( $this->get_ordered_modules( $modules ) as $slug ) {
			$this->modules[ $slug ]->activate();
			do_action( 'pmpro_pause_module_activated', $slug );
		}

		$this->log( sprintf( 'Pause mode activated by %s with modules: %s', $activated_by, implode( ', ', $modules ) ) );

		/** Fires after pause engine is fully activated. */
		do_action( 'pmpro_pause_engine_activated', $state );

		return true;
	}

	/**
	 * Resume all services.
	 *
	 * @return bool
	 */
	public function resume() {
		$state = $this->get_state();
		if ( empty( $state['enabled'] ) ) {
			return false;
		}

		// Deactivate in reverse order.
		$reverse = array_reverse( $this->get_ordered_modules( $state['modules'] ) );
		foreach ( $reverse as $slug ) {
			if ( isset( $this->modules[ $slug ] ) ) {
				$this->modules[ $slug ]->deactivate();
				do_action( 'pmpro_pause_module_deactivated', $slug );
			}
		}

		// Run on_resume for each module.
		foreach ( $state['modules'] as $slug ) {
			if ( isset( $this->modules[ $slug ] ) ) {
				$this->modules[ $slug ]->on_resume();
			}
		}

		delete_option( self::OPTION_KEY );
		$this->state = null;

		$this->log( 'Pause mode deactivated.' );
		do_action( 'pmpro_pause_engine_deactivated', $state );

		return true;
	}

	/**
	 * Pause with a named preset.
	 *
	 * @param string $preset_name The preset name.
	 * @return bool
	 */
	public function pause_with_preset( $preset_name ) {
		$presets = self::get_presets();
		if ( ! isset( $presets[ $preset_name ] ) ) {
			return false;
		}
		return $this->pause( $presets[ $preset_name ]['modules'], $preset_name );
	}

	/**
	 * Enable a single module while paused.
	 *
	 * @param string $slug The module slug.
	 * @return bool
	 */
	public function enable_module( $slug ) {
		$state = $this->get_state();
		if ( empty( $state['enabled'] ) || ! isset( $this->modules[ $slug ] ) ) {
			return false;
		}

		if ( in_array( $slug, $state['modules'], true ) ) {
			return true;
		}

		$state['modules'][] = $slug;
		update_option( self::OPTION_KEY, $state );
		$this->state = $state;

		$this->modules[ $slug ]->activate();
		do_action( 'pmpro_pause_module_activated', $slug );

		return true;
	}

	/**
	 * Disable a single module while paused.
	 *
	 * @param string $slug The module slug.
	 * @return bool
	 */
	public function disable_module( $slug ) {
		$state = $this->get_state();
		if ( empty( $state['enabled'] ) || ! isset( $this->modules[ $slug ] ) ) {
			return false;
		}

		$state['modules'] = array_values( array_diff( $state['modules'], array( $slug ) ) );
		update_option( self::OPTION_KEY, $state );
		$this->state = $state;

		$this->modules[ $slug ]->deactivate();
		$this->modules[ $slug ]->on_resume();
		do_action( 'pmpro_pause_module_deactivated', $slug );

		// If no modules remain, fully resume.
		if ( empty( $state['modules'] ) ) {
			return $this->resume();
		}

		return true;
	}

	/**
	 * Check if pause engine is active.
	 *
	 * @return bool
	 */
	public function is_paused() {
		$state = $this->get_state();
		return ! empty( $state['enabled'] );
	}

	/**
	 * Check if a specific module is active.
	 *
	 * @param string $slug Module slug.
	 * @return bool
	 */
	public function is_module_active( $slug ) {
		return isset( $this->modules[ $slug ] ) && $this->modules[ $slug ]->is_active();
	}

	/**
	 * Get the current state.
	 *
	 * @return array
	 */
	public function get_state() {
		if ( is_null( $this->state ) ) {
			$this->state = get_option( self::OPTION_KEY, array() );
		}
		return $this->state;
	}

	/**
	 * Get active module slugs.
	 *
	 * @return string[]
	 */
	public function get_active_modules() {
		$state = $this->get_state();
		return ! empty( $state['modules'] ) ? $state['modules'] : array();
	}

	/**
	 * Get available presets.
	 *
	 * @return array
	 */
	public static function get_presets() {
		$presets = array(
			'migration' => array(
				'label'   => __( 'Migration (Full Lockdown)', 'paid-memberships-pro' ),
				'modules' => array(
					'pmpro_mutations',
					'pmpro_gateways',
					'pmpro_mail',
					'background_schedules',
					'frontend_block',
					'logged_in_sessions',
				),
			),
			'maintenance' => array(
				'label'   => __( 'Maintenance', 'paid-memberships-pro' ),
				'modules' => array(
					'pmpro_mutations',
					'pmpro_mail',
					'background_schedules',
				),
			),
		);

		/**
		 * Filter available pause engine presets.
		 *
		 * @param array $presets Preset definitions.
		 */
		return apply_filters( 'pmpro_pause_engine_presets', $presets );
	}

	/**
	 * Sort modules into activation order.
	 *
	 * @param string[] $slugs Module slugs.
	 * @return string[]
	 */
	private function get_ordered_modules( $slugs ) {
		$ordered = array();
		foreach ( self::$activation_order as $slug ) {
			if ( in_array( $slug, $slugs, true ) ) {
				$ordered[] = $slug;
			}
		}
		// Append any custom modules not in the default order.
		foreach ( $slugs as $slug ) {
			if ( ! in_array( $slug, $ordered, true ) ) {
				$ordered[] = $slug;
			}
		}
		return $ordered;
	}

	/**
	 * Check if the current user can bypass pause engine.
	 *
	 * @return bool
	 */
	public static function current_user_can_bypass() {
		/**
		 * Filter whether the current user can bypass pause engine.
		 *
		 * @param bool $can_bypass Whether the user can bypass.
		 */
		return apply_filters( 'pmpro_pause_engine_admin_bypass', current_user_can( 'pmpro_manage_pause_mode' ) );
	}

	/**
	 * Send a queued email (Action Scheduler callback).
	 *
	 * @param array $email_data Serialized email data.
	 */
	public function send_queued_email( $email_data ) {
		if ( empty( $email_data['to'] ) || empty( $email_data['subject'] ) ) {
			return;
		}

		$headers     = ! empty( $email_data['headers'] ) ? $email_data['headers'] : '';
		$attachments = ! empty( $email_data['attachments'] ) ? $email_data['attachments'] : array();

		wp_mail( $email_data['to'], $email_data['subject'], $email_data['message'], $headers, $attachments );
	}

	/**
	 * Log a pause engine event.
	 *
	 * @param string $message The message to log.
	 */
	private function log( $message ) {
		error_log( '[PMPro Pause Engine] ' . $message );

		/** Fires when a pause engine event is logged. */
		do_action( 'pmpro_pause_engine_log', $message );
	}
}

/**
 * Module A: Freeze PMPro state changes.
 *
 * @since TBD
 */
class PMPro_Pause_Module_Mutations implements PMPro_Pause_Module_Interface {

	private $active = false;

	public function get_slug() {
		return 'pmpro_mutations';
	}

	public function get_label() {
		return __( 'Freeze Membership Changes', 'paid-memberships-pro' );
	}

	public function activate() {
		if ( $this->active ) {
			return;
		}
		$this->active = true;

		add_filter( 'pmpro_checkout_checks', array( $this, 'block_checkout' ), 1 );
		add_filter( 'pmpro_change_level', array( $this, 'block_level_change' ), 1, 4 );
		add_filter( 'pmpro_checkout_order_creation_checks', array( $this, 'block_order_creation' ), 1 );
	}

	public function deactivate() {
		if ( ! $this->active ) {
			return;
		}
		$this->active = false;

		remove_filter( 'pmpro_checkout_checks', array( $this, 'block_checkout' ), 1 );
		remove_filter( 'pmpro_change_level', array( $this, 'block_level_change' ), 1 );
		remove_filter( 'pmpro_checkout_order_creation_checks', array( $this, 'block_order_creation' ), 1 );
	}

	public function is_active() {
		return $this->active;
	}

	public function on_resume() {
		// No cleanup needed.
	}

	/**
	 * Block checkout for non-admins.
	 */
	public function block_checkout( $value ) {
		if ( PMPro_Pause_Engine::current_user_can_bypass() ) {
			return $value;
		}

		global $pmpro_msg, $pmpro_msgt;
		$pmpro_msg  = __( 'This site is currently in maintenance mode. Please try again later.', 'paid-memberships-pro' );
		$pmpro_msgt = 'pmpro_error';
		return false;
	}

	/**
	 * Block level changes for non-admins.
	 */
	public function block_level_change( $level, $user_id, $old_level_status, $cancel_level ) {
		if ( PMPro_Pause_Engine::current_user_can_bypass() ) {
			return $level;
		}
		return false;
	}

	/**
	 * Block order creation for non-admins.
	 */
	public function block_order_creation( $value ) {
		if ( PMPro_Pause_Engine::current_user_can_bypass() ) {
			return $value;
		}
		return false;
	}
}

/**
 * Module B: Block gateway communication.
 *
 * @since TBD
 */
class PMPro_Pause_Module_Gateways implements PMPro_Pause_Module_Interface {

	private $active = false;

	/**
	 * Known gateway API domains to block outbound requests to.
	 *
	 * @var string[]
	 */
	private static $gateway_domains = array(
		'api.stripe.com',
		'api.paypal.com',
		'api.sandbox.paypal.com',
		'api-3t.paypal.com',
		'api-3t.sandbox.paypal.com',
		'ipnpb.paypal.com',
		'ipnpb.sandbox.paypal.com',
		'api.braintreegateway.com',
		'payments.braintree-api.com',
		'apitest.authorize.net',
		'api2.authorize.net',
		'api.authorize.net',
	);

	/**
	 * Webhook AJAX actions to intercept.
	 *
	 * @var string[]
	 */
	private static $webhook_actions = array(
		'wp_ajax_nopriv_stripe_webhook',
		'wp_ajax_nopriv_ipnhandler',
		'wp_ajax_nopriv_authnet_silent_post',
		'wp_ajax_nopriv_braintree_webhook',
		'wp_ajax_nopriv_twocheckout-ins',
	);

	public function get_slug() {
		return 'pmpro_gateways';
	}

	public function get_label() {
		return __( 'Block Gateway Communication', 'paid-memberships-pro' );
	}

	public function activate() {
		if ( $this->active ) {
			return;
		}
		$this->active = true;

		add_action( 'pmpro_checkout_before_processing', array( $this, 'block_gateway_outbound' ), 0 );
		add_filter( 'pre_http_request', array( $this, 'block_outbound_http' ), 1, 3 );

		/** Filter whether to block inbound webhooks with 503. */
		if ( apply_filters( 'pmpro_pause_engine_block_inbound_webhooks', true ) ) {
			foreach ( self::$webhook_actions as $action ) {
				add_action( $action, array( $this, 'block_inbound_webhook' ), 0 );
			}
		}
	}

	public function deactivate() {
		if ( ! $this->active ) {
			return;
		}
		$this->active = false;

		remove_action( 'pmpro_checkout_before_processing', array( $this, 'block_gateway_outbound' ), 0 );
		remove_filter( 'pre_http_request', array( $this, 'block_outbound_http' ), 1 );

		foreach ( self::$webhook_actions as $action ) {
			remove_action( $action, array( $this, 'block_inbound_webhook' ), 0 );
		}
	}

	public function is_active() {
		return $this->active;
	}

	public function on_resume() {
		// No cleanup needed.
	}

	/**
	 * Block checkout from reaching the gateway.
	 */
	public function block_gateway_outbound() {
		global $pmpro_msg, $pmpro_msgt;
		$pmpro_msg  = __( 'Payment processing is temporarily suspended. Please try again later.', 'paid-memberships-pro' );
		$pmpro_msgt = 'pmpro_error';

		wp_die(
			esc_html( $pmpro_msg ),
			esc_html__( 'Service Unavailable', 'paid-memberships-pro' ),
			array( 'response' => 503 )
		);
	}

	/**
	 * Block inbound webhooks with 503.
	 */
	public function block_inbound_webhook() {
		$retry_after = apply_filters( 'pmpro_pause_engine_retry_after', 3600 );

		status_header( 503 );
		header( 'Retry-After: ' . intval( $retry_after ) );
		header( 'Content-Type: text/plain; charset=utf-8' );
		echo 'Service temporarily unavailable. Retry later.';
		exit;
	}

	/**
	 * Block outbound HTTP requests to gateway domains.
	 *
	 * @param false|array|WP_Error $response  Whether to preempt the request.
	 * @param array                $parsed_args Request arguments.
	 * @param string               $url        The request URL.
	 * @return false|array|WP_Error
	 */
	public function block_outbound_http( $response, $parsed_args, $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return $response;
		}

		/**
		 * Filter the list of blocked gateway domains.
		 *
		 * @param string[] $domains Gateway API domains.
		 */
		$blocked_domains = apply_filters( 'pmpro_pause_engine_blocked_gateway_domains', self::$gateway_domains );

		if ( in_array( $host, $blocked_domains, true ) ) {
			return new WP_Error(
				'pmpro_pause_engine_blocked',
				__( 'Outbound gateway request blocked during pause engine.', 'paid-memberships-pro' )
			);
		}

		return $response;
	}
}

/**
 * Module C: Queue all outgoing email.
 *
 * @since TBD
 */
class PMPro_Pause_Module_Mail implements PMPro_Pause_Module_Interface {

	private $active = false;

	public function get_slug() {
		return 'pmpro_mail';
	}

	public function get_label() {
		return __( 'Queue Outgoing Email', 'paid-memberships-pro' );
	}

	public function activate() {
		if ( $this->active ) {
			return;
		}
		$this->active = true;

		add_filter( 'pre_wp_mail', array( $this, 'intercept_email' ), 999, 2 );
	}

	public function deactivate() {
		if ( ! $this->active ) {
			return;
		}
		$this->active = false;

		remove_filter( 'pre_wp_mail', array( $this, 'intercept_email' ), 999 );
	}

	public function is_active() {
		return $this->active;
	}

	public function on_resume() {
		// Queued emails will be processed by AS naturally once Module D is deactivated.
	}

	/**
	 * Intercept outgoing email, queue it in AS.
	 *
	 * @param null|bool $return Short-circuit return value.
	 * @param array     $atts   Email attributes (to, subject, message, headers, attachments).
	 * @return false
	 */
	public function intercept_email( $return, $atts ) {
		$email_data = array(
			'to'          => $atts['to'],
			'subject'     => $atts['subject'],
			'message'     => $atts['message'],
			'headers'     => $atts['headers'],
			'attachments' => $atts['attachments'],
			'queued_at'   => time(),
		);

		PMPro_Action_Scheduler::instance()->maybe_add_task(
			'pmpro_pause_engine_send_queued_email',
			array( $email_data ),
			'pmpro_pause_engine_email_queue'
		);

		// Return false to prevent sending.
		return false;
	}
}

/**
 * Module D: Halt background processing.
 *
 * @since TBD
 */
class PMPro_Pause_Module_Schedules implements PMPro_Pause_Module_Interface {

	private $active = false;

	public function get_slug() {
		return 'background_schedules';
	}

	public function get_label() {
		return __( 'Halt Background Processing', 'paid-memberships-pro' );
	}

	public function activate() {
		if ( $this->active ) {
			return;
		}
		$this->active = true;

		PMPro_Action_Scheduler::halt();
		add_filter( 'action_scheduler_before_execute', '__return_false', 999 );
		add_filter( 'spawn_cron', '__return_false', 999 );
	}

	public function deactivate() {
		if ( ! $this->active ) {
			return;
		}
		$this->active = false;

		PMPro_Action_Scheduler::resume();
		remove_filter( 'action_scheduler_before_execute', '__return_false', 999 );
		remove_filter( 'spawn_cron', '__return_false', 999 );
	}

	public function is_active() {
		return $this->active;
	}

	public function on_resume() {
		// AS resume is handled in deactivate().
	}
}

/**
 * Module E: Block non-admin frontend traffic.
 *
 * @since TBD
 */
class PMPro_Pause_Module_Frontend implements PMPro_Pause_Module_Interface {

	private $active = false;

	public function get_slug() {
		return 'frontend_block';
	}

	public function get_label() {
		return __( 'Block Frontend Access', 'paid-memberships-pro' );
	}

	public function activate() {
		if ( $this->active ) {
			return;
		}
		$this->active = true;

		add_action( 'template_redirect', array( $this, 'block_frontend' ), 0 );
		add_filter( 'rest_authentication_errors', array( $this, 'block_rest_api' ), 0 );
		add_action( 'init', array( $this, 'block_post_requests' ), 0 );
		add_action( 'admin_init', array( $this, 'block_nopriv_ajax' ), 0 );
		add_filter( 'authenticate', array( $this, 'block_non_admin_login' ), 999, 2 );
	}

	public function deactivate() {
		if ( ! $this->active ) {
			return;
		}
		$this->active = false;

		remove_action( 'template_redirect', array( $this, 'block_frontend' ), 0 );
		remove_filter( 'rest_authentication_errors', array( $this, 'block_rest_api' ), 0 );
		remove_action( 'init', array( $this, 'block_post_requests' ), 0 );
		remove_action( 'admin_init', array( $this, 'block_nopriv_ajax' ), 0 );
		remove_filter( 'authenticate', array( $this, 'block_non_admin_login' ), 999 );
	}

	public function is_active() {
		return $this->active;
	}

	public function on_resume() {
		// No cleanup needed.
	}

	/**
	 * Block frontend for non-admins.
	 */
	public function block_frontend() {
		if ( PMPro_Pause_Engine::current_user_can_bypass() ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		$this->show_maintenance_page();
	}

	/**
	 * Block REST API for non-admins.
	 *
	 * @param WP_Error|null|true $errors WP_Error if authentication error.
	 * @return WP_Error|null|true
	 */
	public function block_rest_api( $errors ) {
		if ( PMPro_Pause_Engine::current_user_can_bypass() ) {
			return $errors;
		}

		return new WP_Error(
			'pmpro_pause_engine_rest_blocked',
			__( 'Site is temporarily unavailable for maintenance.', 'paid-memberships-pro' ),
			array( 'status' => 503 )
		);
	}

	/**
	 * Block POST requests from non-admins.
	 */
	public function block_post_requests() {
		if ( PMPro_Pause_Engine::current_user_can_bypass() ) {
			return;
		}

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] && ! is_admin() ) {
			wp_die(
				esc_html__( 'This site is currently in maintenance mode. Please try again later.', 'paid-memberships-pro' ),
				esc_html__( 'Service Unavailable', 'paid-memberships-pro' ),
				array( 'response' => 503 )
			);
		}
	}

	/**
	 * Block AJAX requests from non-logged-in users.
	 */
	public function block_nopriv_ajax() {
		if ( ! wp_doing_ajax() || is_user_logged_in() ) {
			return;
		}

		wp_die(
			esc_html__( 'Site temporarily unavailable.', 'paid-memberships-pro' ),
			esc_html__( 'Service Unavailable', 'paid-memberships-pro' ),
			array( 'response' => 503 )
		);
	}

	/**
	 * Block non-admin logins.
	 *
	 * @param WP_User|WP_Error|null $user     The user object or error.
	 * @param string                $username  The username.
	 * @return WP_User|WP_Error|null
	 */
	public function block_non_admin_login( $user, $username ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( $user && ! user_can( $user, 'pmpro_manage_pause_mode' ) ) {
			return new WP_Error(
				'pmpro_pause_engine_login_blocked',
				__( 'This site is temporarily unavailable for maintenance. Only administrators can log in.', 'paid-memberships-pro' )
			);
		}

		return $user;
	}

	/**
	 * Display the maintenance page.
	 */
	private function show_maintenance_page() {
		$retry_after = apply_filters( 'pmpro_pause_engine_retry_after', 3600 );

		$html = '<!DOCTYPE html>
<html>
<head>
	<title>' . esc_html__( 'Site Under Maintenance', 'paid-memberships-pro' ) . '</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; text-align: center; padding: 50px; background: #f4f4f4; }
		.container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
		h1 { color: #333; margin-bottom: 20px; }
		p { color: #666; line-height: 1.6; }
	</style>
</head>
<body>
	<div class="container">
		<h1>' . esc_html__( 'Site Under Maintenance', 'paid-memberships-pro' ) . '</h1>
		<p>' . esc_html__( 'We are currently performing maintenance on this site. This process should be completed shortly.', 'paid-memberships-pro' ) . '</p>
		<p>' . esc_html__( 'Thank you for your patience.', 'paid-memberships-pro' ) . '</p>
	</div>
</body>
</html>';

		/**
		 * Filter the maintenance page HTML.
		 *
		 * @param string $html The maintenance page HTML.
		 */
		$html = apply_filters( 'pmpro_pause_engine_maintenance_template', $html );

		status_header( 503 );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Retry-After: ' . intval( $retry_after ) );
		echo $html;
		exit;
	}
}

/**
 * Module F: Clear non-admin sessions.
 *
 * @since TBD
 */
class PMPro_Pause_Module_Sessions implements PMPro_Pause_Module_Interface {

	private $active = false;

	public function get_slug() {
		return 'logged_in_sessions';
	}

	public function get_label() {
		return __( 'Clear Non-Admin Sessions', 'paid-memberships-pro' );
	}

	public function activate() {
		if ( $this->active ) {
			return;
		}
		$this->active = true;

		// Clear non-admin sessions immediately.
		$this->clear_non_admin_sessions();

		// Block non-admin logins going forward.
		add_filter( 'authenticate', array( $this, 'block_non_admin_login' ), 999, 2 );
	}

	public function deactivate() {
		if ( ! $this->active ) {
			return;
		}
		$this->active = false;

		remove_filter( 'authenticate', array( $this, 'block_non_admin_login' ), 999 );
	}

	public function is_active() {
		return $this->active;
	}

	public function on_resume() {
		// Sessions regenerate naturally on login.
	}

	/**
	 * Block non-admin logins.
	 *
	 * @param WP_User|WP_Error|null $user     The user object or error.
	 * @param string                $username  The username.
	 * @return WP_User|WP_Error|null
	 */
	public function block_non_admin_login( $user, $username ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( $user && ! user_can( $user, 'pmpro_manage_pause_mode' ) ) {
			return new WP_Error(
				'pmpro_pause_engine_login_blocked',
				__( 'This site is temporarily unavailable for maintenance. Only administrators can log in.', 'paid-memberships-pro' )
			);
		}

		return $user;
	}

	/**
	 * Clear sessions for all non-admin users.
	 */
	private function clear_non_admin_sessions() {
		$admins = get_users( array(
			'capability' => 'pmpro_manage_pause_mode',
			'fields'     => 'ID',
		) );

		$users = get_users( array(
			'exclude' => $admins,
			'fields'  => 'ID',
			'number'  => 500,
		) );

		foreach ( $users as $user_id ) {
			$sessions = WP_Session_Tokens::get_instance( $user_id );
			$sessions->destroy_all();
		}

		// If there are more users, schedule a follow-up batch.
		$total = count_users();
		if ( ( $total['total_users'] - count( $admins ) ) > 500 ) {
			PMPro_Action_Scheduler::instance()->maybe_add_task(
				'pmpro_pause_engine_clear_sessions_batch',
				array( $admins, 500 ),
				'pmpro_pause_engine_sessions'
			);
		}
	}
}

/**
 * Convenience function: check if the pause engine is active.
 *
 * @since TBD
 * @return bool
 */
function pmpro_pause_engine_is_active() {
	return PMPro_Pause_Engine::instance()->is_paused();
}

/**
 * Convenience function: check if a specific pause module is active.
 *
 * @since TBD
 * @param string $slug Module slug.
 * @return bool
 */
function pmpro_pause_module_is_active( $slug ) {
	return PMPro_Pause_Engine::instance()->is_module_active( $slug );
}
