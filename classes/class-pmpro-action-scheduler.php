<?php
/**
 *
 * Paid Memberships Pro — Action Scheduler (AS).
 *
 * This class provides methods to schedule, manage, and execute tasks asynchronously, and is both a replacement
 * for older wp-cron based tasks and a more efficient and performant way to handle background and asynchronous tasks in WordPress.
 *
 * Keep in mind that: tasks are always handled asynchronously;
 * Queued tasks are not guaranteed to run immediately;
 * Action Scheduler always processes tasks in batches with potentially different sizes.
 *
 * @package pmpro_plugin
 * @subpackage classes
 * @since 3.5
 */

class PMPro_Action_Scheduler {

	/**
	 * The single instance of the class.
	 *
	 * @var PMPro_Action_Scheduler
	 * @access protected
	 * @since 3.5
	 */
	protected static $instance = null;

	/**
	 * The default queue threshold for async tasks.
	 * This is the maximum number of async tasks that can be queued before a delay is added to incoming tasks.
	 * This prevent overly taxing a server with task running over longer periods of time.
	 * The default is 500 tasks.
	 */
	public static $pmpro_as_queue_limit = 500;

	/**
	 * The minimum version of Action Scheduler required for PMPro.
	 */
	private static $required_as_version = '3.9';

	/**
	 * Get the queue limit for async tasks.
	 *
	 * @return int The maximum number of tasks that can be queued before a delay is added.
	 * @access public
	 * @since 3.5
	 */
	public static function get_pmpro_as_queue_limit() {
		/**
		 * Filter the queue limit for async tasks.
		 *
		 * @param int $queue_limit The default queue limit.
		 */
		return apply_filters( 'pmpro_action_scheduler_queue_limit', self::$pmpro_as_queue_limit );
	}

	/**
	 * Constructor
	 */
	public function __construct() {

		// Check if Action Scheduler is installed and activated.
		if ( ! class_exists( \ActionScheduler::class ) ) {
			return;
		}

		// Track library conflicts if detected.
		self::track_library_conflicts();

		// Show admin notice if Action Scheduler is out of date.
		add_action( 'admin_notices', array( $this, 'show_outdated_notice' ) );

		// Add custom hooks for quarter-hourly, hourly, daily and weekly tasks.
		add_action( 'action_scheduler_init', array( $this, 'add_recurring_hooks' ) );

		// Remove recurring hooks if PMPro is deactivated.
		add_action( 'pmpro_deactivation', array( $this, 'remove_recurring_hooks' ) );

		// Handle the monthly
		add_action( 'pmpro_trigger_monthly', array( $this, 'handle_monthly_tasks' ) );

		// Add dummy callbacks for scheduled tasks that may not have a handler.
		add_action( 'action_scheduler_init', array( $this, 'add_dummy_callbacks' ) );

		// Add late filters to modify the AS batch size and time limit. We effectively control the batch size and time limit,
		// which is intentional since some of our tasks can be heavy and we want to ensure they run smoothly and don't slow down a site.
		add_filter( 'action_scheduler_queue_runner_batch_size', array( $this, 'modify_batch_size' ), 999 );
		add_filter( 'action_scheduler_queue_runner_time_limit', array( $this, 'modify_batch_time_limit' ), 999 );

		// If PMPro is paused or the halt() method was called, don't allow async requests.
		add_filter( 'action_scheduler_allow_async_request_runner', array( $this, 'action_scheduler_allow_async_request_runner' ), 999 );
		add_action( 'admin_notices', array( $this, 'show_async_requests_paused_notice' ) );
	}

	/**
	 * Load the Action Scheduler library.
	 *
	 * @since 3.5
	 */
	private static function track_library_conflicts() {
		// Get the version of Action Scheduler that is currently loaded and the plugin file it was loaded from.
		$action_scheduler_version = ActionScheduler_Versions::instance()->latest_version(); // This is only available after plugins_loaded priority 0 which is why we do this here.
		$previously_loaded_class  = self::get_active_source_path();

		// If we loaded Action Scheduler, this will do nothing.
		pmpro_track_library_conflict( 'action-scheduler', $previously_loaded_class, $action_scheduler_version );
	}

	/**
	 * Show an admin notice if Action Scheduler is out of date.
	 *
	 * @since 3.5
	 */
	public static function show_outdated_notice() {
		// Only show on PMPro admin pages.
		if ( empty( $_REQUEST['page'] ) || strpos( $_REQUEST['page'], 'pmpro' ) === false ) {
			return;
		}

		// Get the loaded version of Action Scheduler.
		$action_scheduler_version = ActionScheduler_Versions::instance()->latest_version();

		// If the version is current, we don't need to show the notice.
		if ( version_compare( $action_scheduler_version, self::$required_as_version, '>=' ) ) {
			return;
		}

		// If the version is outdated, show the notice.
		?>
		<div class="notice notice-error">
			<p><strong><?php esc_html_e( 'Important Notice: An Outdated Version of Action Scheduler is Loaded', 'paid-memberships-pro' ); ?></strong></p>
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						__( 'An outdated version of Action Scheduler (version %1$s) is being loaded by %2$s which may affect Paid Memberships Pro functionalilty on this website.', 'paid-memberships-pro' ),
						$action_scheduler_version,
						'<code>' . self::get_active_source_path() . '</code>'
					)
				)
				?>
			</p>
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						__( 'For more information, please <a href="%s" target="_blank">review our documentation</a> on how to resolve library conflicts.', 'paid-memberships-pro' ),
						'https://www.paidmembershipspro.com/fix-library-conflict/?utm_source=pmpro&utm_medium=plugin&utm_campaign=blog&utm_content=action-scheduler-plugin-conflict'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Get the single instance of the class.
	 *
	 * @access public
	 * @since 3.5
	 * @return PMPro_Action_Scheduler
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Prevent the instance from being cloned.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 * @throws Exception If the instance is cloned.
	 */
	public function __clone() {
		throw new Exception( esc_html__( 'Action Scheduler instance cannot be cloned', 'paid-memberships-pro' ) );
	}

	/**
	 * Prevent the instance from being unserialized.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 * @throws Exception If the instance is unserialized.
	 */
	public function __wakeup() {
		throw new Exception( esc_html__( 'Action Scheduler instance cannot be unserialized', 'paid-memberships-pro' ) );
	}

	/**
	 * Check if AS has an existing task in the pending or completed queue
	 *
	 * @access public
	 * @since 3.5
	 * @param string         $hook The hook name for the task.
	 * @param array          $args Arguments passed to the task hook.
	 * @param string         $group The group the task belongs to.
	 * @param boolean        $pending True to check pending tasks, false to check completed tasks.
	 * @param string|boolean $timestamp Optional timestamp to compare with task date.
	 * @return bool True if an existing task is found, false otherwise.
	 */
	public function has_existing_task( $hook, $args = array(), $group = '', $pending = true, $timestamp = false ) {
		$status = $pending ? ActionScheduler_Store::STATUS_PENDING : ActionScheduler_Store::STATUS_COMPLETE;

		$query_args = array(
			'hook'   => $hook,
			'args'   => $args,
			'group'  => $group,
			'status' => $status,
		);

		// If we are looking for a task that's not in the future, we must provide a timestamp.
		if ( ! $pending && $timestamp ) {
			$query_args['date']         = $timestamp;
			$query_args['date_compare'] = '>=';
		} elseif ( ! $pending ) {
			$query_args['date']         = current_time( 'mysql' );
			$query_args['date_compare'] = '<=';
		}

		$results = as_get_scheduled_actions( $query_args );
		return ! empty( $results );
	}

	/**
	 * Get the count of AS items currently queued for a group
	 *
	 * @access public
	 * @since 3.5
	 * @param string $group The task group name.
	 * @return int The number of tasks in the queue.
	 */
	public static function count_existing_tasks_for_group( $group ) {
		$search_args = array(
			'group'  => $group,
			'status' => ActionScheduler_Store::STATUS_PENDING,
		);
		return count( as_get_scheduled_actions( $search_args ) );
	}

	/**
	 * Check if a task exists in the queue of tasks before adding it; and maybe add a queue delay.
	 *
	 * @access public
	 * @since 3.5
	 * @param string          $hook The hook for the task.
	 * @param mixed           $args The data being passed to the task hook.
	 * @param string          $group The group this task should be assigned to. Default is 'pmpro_async_tasks'.
	 * @param int|string|null $timestamp An as_strtotime datetime or human-readable string.
	 * @param boolean         $run_asap Whether to bypass the count delay and run async asap.
	 * @return void
	 */
	public function maybe_add_task( $hook, $args, $group = 'pmpro_async_tasks', $timestamp = null, $run_asap = false ) {
		// Convert human-readable string to timestamp if needed.
		if ( ! is_null( $timestamp ) && ! is_int( $timestamp ) ) {
			$converted = self::as_strtotime( $timestamp );
			if ( $converted !== false ) {
				$timestamp = $converted;
			} else {
				$timestamp = null;
			}
		}
		// Check for a task in the queue matching this task exactly (hook, args, group).
		if ( $this->has_existing_task( $hook, $args, $group ) ) {
			return;
		}

		// We're going to add a timestamp
		if ( null === $timestamp && false === $run_asap ) {
			$task_count = self::count_existing_tasks_for_group( $group );
			// If we have more than self::get_pmpro_as_queue() tasks in the queue, add a delay to the task.
			// This will space out tasks and prevent overwhelming the server if the tasking is heavy.
			if ( $task_count > self::get_pmpro_as_queue_limit() ) {
				$delay     = $task_count - self::get_pmpro_as_queue_limit();
				$timestamp = self::as_strtotime( "+{$delay} seconds" );
			} else {
				// Less than self::get_pmpro_as_queue() tasks in the queue, queue this task immediately.
				$timestamp = self::as_strtotime( 'now' );
			}
		}

		// If we are running this task immediately, we need to use the async queue.
		if ( $run_asap ) {
			return as_enqueue_async_action( $hook, $args, $group );
		}
		// If we have a timestamp, we will schedule the task for the time provided.
		if ( null !== $timestamp ) {
			return as_schedule_single_action( $timestamp, $hook, $args, $group );
		}
	}

	/**
	 * Maybe add a recurring task (if not exists)
	 *
	 * @access public
	 * @since 3.5
	 * @param string   $hook The hook for the task.
	 * @param int|null $interval_in_seconds The interval in seconds this recurring task should run.
	 * @param int|null $first_run_datetime An as_strtotime datetime in the future this task should first run.
	 * @param string   $group The group this task should be assigned to.
	 * @return void
	 */
	public function maybe_add_recurring_task( $hook, $interval_in_seconds = null, $first_run_datetime = null, $group = 'pmpro_recurring_tasks' ) {
		if ( ! as_next_scheduled_action( $hook, array(), $group ) ) {
			// Make sure first run datetime has been set.
			$first_run_datetime = $first_run_datetime ?: self::as_strtotime( 'now +5 minutes' );
			// Schedule this task in the future, and make it recurring.
			if ( ! empty( $interval_in_seconds ) ) {
				return as_schedule_recurring_action( $first_run_datetime, $interval_in_seconds, $hook, array(), $group, true );
			} else {
				throw new WP_Error( 'pmpro_action_scheduler_warning', esc_html__( 'An interval is required to queue an Action Scheduler recurring task.', 'paid-memberships-pro' ) );
			}
		}
	}

	/**
	 * Add the ability to store custom messages with Action Scheduler's logs when running a task.
	 *
	 * @access public
	 * @since 3.5
	 * @param string $hook The hook name.
	 * @param string $status The status of the action.
	 * @param string $message The log message to add.
	 * @return void
	 */
	public static function add_task_log( $hook, $status, $message = '' ) {
		if ( empty( $hook ) || empty( $message ) ) {
			// If we don't have a message or hook, we can't log anything.
			return;
		}
		// We have to use ActionScheduler::store()->find_action() to get the action ID.
		// This is because the action ID is not available in the context of the task.
		$action_id = ActionScheduler::store()->find_action( $hook, array( 'status' => ActionScheduler_Store::STATUS_RUNNING ) );

		if ( empty( $action_id ) ) {
			// If we don't have an action ID, we can't log anything.
			return;
		}
		// Log the message with the action ID when running a task.
		ActionScheduler::logger()->log( $action_id, $message );
	}

	/**
	 * List all tasks in the queue for a given group or hook.
	 *
	 * At least one of $group or $hook must be provided.
	 *
	 * @access public
	 * @since 3.5.x
	 * @param string|null $group The task group name.
	 * @param string|null $hook The task hook name.
	 * @return array The list of tasks in the queue.
	 * @throws WP_Error If neither $group nor $hook is provided.
	 */
	public static function list_tasks( $group = null, $hook = null ) {
		$args = array();
		if ( ! empty( $group ) ) {
			$args['group'] = $group;
		}
		if ( ! empty( $hook ) ) {
			$args['hook'] = $hook;
		}
		if ( empty( $args ) ) {
			// If neither group nor hook is provided, we cannot list tasks.
			throw new WP_Error( 'pmpro_action_scheduler_warning', esc_html__( 'You must provide either a group or a hook to list tasks.', 'paid-memberships-pro' ) );
		}
		return as_get_scheduled_actions( $args );
	}


	/**
	 * Clear all tasks in the queue matching the given hook, args, and/or group.
	 *
	 * @access public
	 * @since 3.5
	 * @param string|null $hook  The hook name for the task.
	 * @param array       $args  The arguments for the task.
	 * @param string|null $group The group the task belongs to.
	 * @param string      $status The status of the task to delete (default: 'completed').
	 * @return int Number of tasks deleted.
	 * @throws WP_Error If no parameters are provided.
	 */
	public static function remove_actions( $hook = null, $args = array(), $group = null, $status = 'completed' ) {

		// For cases where we're filtering by args or group only
		$search_args = array(
			'status'   => self::get_as_status( $status ),
			'per_page' => 100, // Process in batches
		);

		if ( ! empty( $hook ) ) {
			$search_args['hook'] = $hook;
		}

		if ( ! empty( $args ) ) {
			$search_args['args'] = $args;
		}

		if ( ! empty( $group ) ) {
			$search_args['group'] = $group;
		}

		$count             = 0;
		$deleted_something = true;

		// Continue deleting in batches until no more actions are found
		while ( $deleted_something ) {
			$deleted_something = false;
			$actions           = as_get_scheduled_actions( $search_args, 'ids' );

			if ( ! empty( $actions ) && is_array( $actions ) ) {
				foreach ( $actions as $action_id ) {
					ActionScheduler::store()->delete_action( $action_id );
					++$count;
					$deleted_something = true;
				}
			} else {
				break;
			}
		}

		return $count;
	}

	/**
	 * Registers PMPro recurring scheduled hooks and ensures they are scheduled if not already.
	 *
	 * The following hooks are scheduled via Action Scheduler, using the 'pmpro_recurring_tasks' group:
	 * - pmpro_schedule_quarter_hourly: Runs every 15 minutes.
	 * - pmpro_schedule_hourly: Runs every hour.
	 * - pmpro_schedule_daily: Runs daily at 10:30am.
	 * - pmpro_schedule_weekly: Runs every Sunday at 8:00am.
	 * - pmpro_trigger_monthly: Runs on the first day of each month at 8:00am.
	 *
	 * Filters:
	 * - `pmpro_action_scheduler_recurring_schedules`: Modify or extend the recurring schedule definitions.
	 *
	 * Notes:
	 * - Each hook gets a dummy callback (see `add_dummy_callbacks()`) to prevent logging failures for unhandled actions.
	 * - The `pmpro_trigger_monthly` task is handled by `handle_monthly_tasks()` and automatically reschedules itself.
	 *
	 * @access public
	 * @since 3.5
	 */
	public function add_recurring_hooks() {
		$schedules = apply_filters(
			'pmpro_action_scheduler_recurring_schedules',
			array(
				array(
					'hook'     => 'pmpro_schedule_quarter_hourly',
					'interval' => 15 * MINUTE_IN_SECONDS,
					'start'    => self::as_strtotime( 'now +15 minutes' ),
				),
				array(
					'hook'     => 'pmpro_schedule_hourly',
					'interval' => HOUR_IN_SECONDS,
					'start'    => self::as_strtotime( 'now +1 hour' ),
				),
				array(
					'hook'     => 'pmpro_schedule_daily',
					'interval' => DAY_IN_SECONDS,
					'start'    => self::as_strtotime( 'tomorrow 10:30am' ),
				),
				array(
					'hook'     => 'pmpro_schedule_weekly',
					'interval' => WEEK_IN_SECONDS,
					'start'    => self::as_strtotime( 'next sunday 8:00am' ),
				),
			)
		);

		foreach ( $schedules as $schedule ) {
			if ( ! empty( $schedule['hook'] ) && ! empty( $schedule['interval'] ) ) {
				$this->maybe_add_recurring_task(
					$schedule['hook'],
					$schedule['interval'],
					$schedule['start'],
					'pmpro_recurring_tasks'
				);
			}
		}

		// Schedule the first instance of our monthly action if none exists.
		if ( ! $this->has_existing_task( 'pmpro_trigger_monthly', array(), 'pmpro_recurring_tasks' ) ) {
			$first = self::as_strtotime( 'first day of next month 8:00am' );
			as_schedule_single_action( $first, 'pmpro_trigger_monthly', array(), 'pmpro_recurring_tasks' );
		}
	}

	/**
	 * Remove all recurring hooks from Action Scheduler.
	 *
	 * This method unschedules all recurring tasks that were registered by PMPro.
	 *
	 * @access public
	 * @since 3.5.3
	 */
	public function remove_recurring_hooks() {
		// Find all hooks that belong to the group 'pmpro_recurring_tasks'.
		$hooks = as_get_scheduled_actions(
			array(
				'group' => 'pmpro_recurring_tasks',
				'status' => ActionScheduler_Store::STATUS_PENDING,
			),
			ARRAY_A
		);
		// If no hooks are found, we can exit early.
		if ( empty( $hooks ) ) {
			return;
		}
		foreach ( $hooks as $hook ) {
			as_unschedule_all_actions( $hook['hook'], array(), 'pmpro_recurring_tasks' );
		}
	}

	/**
	 * Add dummy callbacks for our scheduled tasks to prevent AS from logging failed actions.
	 *
	 * This ensures that scheduled hooks without real handlers do not trigger "no callback" errors in the Action Scheduler log.
	 *
	 * NOTE: If you are using a custom schedule, you will need to add a dummy callback for it here.
	 *
	 * Filters:
	 * - `pmpro_action_scheduler_recurring_schedules`: Allows you to modify or extend the list of recurring hooks needing dummy callbacks.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function add_dummy_callbacks() {
		$schedules = apply_filters(
			'pmpro_action_scheduler_recurring_schedules',
			array(
				array( 'hook' => 'pmpro_schedule_quarter_hourly' ),
				array( 'hook' => 'pmpro_schedule_hourly' ),
				array( 'hook' => 'pmpro_schedule_daily' ),
				array( 'hook' => 'pmpro_schedule_weekly' ),
			)
		);

		// Add dummy callbacks for the scheduled tasks.
		// This is to prevent PHP notices when the tasks are run.
		foreach ( $schedules as $schedule ) {
			if ( ! empty( $schedule['hook'] ) ) {
				add_action(
					$schedule['hook'],
					function () use ( $schedule ) {
						return;
					}
				);
			}
		}

		// Ensure our custom monthly task also has a dummy callback.
		add_action(
			'pmpro_trigger_monthly',
			function () {
				return;
			}
		);
	}

	/**
	 * Handle the monthly schedule and reschedule the next instance.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function handle_monthly_tasks() {
		// Run any logic needed for monthly jobs here.
		do_action( 'pmpro_schedule_monthly' );

		// Schedule the next run for the first day of next month.
		$next_month = self::as_strtotime( 'first day of next month 8:00am' );
		as_schedule_single_action( $next_month, 'pmpro_trigger_monthly', array(), 'pmpro_recurring_tasks' );
	}

	/**
	 * Action scheduler claims a batch of actions to process in each request. It keeps the batch
	 * fairly small (by default, 25) in order to prevent errors, like memory exhaustion.
	 *
	 * This method increases or decreases it so that more/less actions are processed in each queue, which speeds up the
	 * overall queue processing time due to latency in requests and the minimum 1 minute between each
	 * queue being processed.
	 *
	 * You can also set this to a different value using the pmpro_action_scheduler_batch_size filter.
	 *
	 * For more details on Action Scheduler batch sizes, see: https://actionscheduler.org/perf/#increasing-batch-size
	 *
	 * @access public
	 * @since 3.5
	 * @param int $batch_size The current batch size.
	 * @return int Modified batch size.
	 */
	public function modify_batch_size( $batch_size ) {
		/**
		 * Public filter for adjusting the batch size in Action Scheduler.
		 *
		 * @param int $batch_size The batch size.
		 */
		$batch_size = apply_filters( 'pmpro_action_scheduler_batch_size', $batch_size );

		return $batch_size;
	}


	/**
	 * Modify the default time limit for processing a batch of actions.
	 *
	 * Action Scheduler provides a default of 30 seconds in which to process actions.
	 *
	 * You can also set this to a different value using the pmpro_action_scheduler_time_limit_seconds filter.
	 *
	 * For more details on the Action Scheduler time limit, see: https://actionscheduler.org/perf/#increasing-time-limit
	 *
	 * @access public
	 * @since 3.5
	 * @param int $time_limit The current time limit in seconds.
	 * @return int Modified time limit in seconds.
	 */
	public function modify_batch_time_limit( $time_limit ) {
		/**
		 * Public filter for adjusting the time limit for Action Scheduler batches.
		 *
		 * @param int $time_limit The time limit in seconds.
		 */
		$time_limit = apply_filters( 'pmpro_action_scheduler_time_limit_seconds', $time_limit );

		return $time_limit;
	}

	/**
	 * Halt Action Scheduler if PMPro is paused or if our halt() method was called.
	 *
	 * @access public
	 * @since 3.5.5
	 *
	 * @param bool $allow Whether to allow Action Scheduler to run asynchronously.
	 * @return bool
	 */
	public function action_scheduler_allow_async_request_runner( $allow ) {
		// If PMPro is paused, don't allow async requests.
		if ( pmpro_is_paused() ) {
			return false;
		}

		// If the halt() method was called, don't allow async requests.
		if ( get_option( 'pmpro_as_halted', false ) ) {
			return false;
		}

		return $allow;
	}

	/**
	 * Show a notice on the Action Scheduler page if PMPro is preventing async requests.
	 *
	 * @access public
	 * @since 3.5.5
	 */
	public function show_async_requests_paused_notice() {
		// If this is not the action-scheduler page in the admin area, bail.
		if ( ! is_admin() || empty( $_REQUEST['page'] ) || 'action-scheduler' !== $_REQUEST['page'] ) {
			return;
		}

		if ( pmpro_is_paused() ) {
			$message = __( 'Paid Memberships Pro services are currently paused. Scheduled actions will not be run automatically until services are resumed.', 'paid-memberships-pro' );
		} elseif ( get_option( 'pmpro_as_halted', false ) ) {
			$message = __( 'Paid Memberships Pro has temporarily halted scheduled actions while additional tasks are added. Actions will resume automatically once this process is complete.', 'paid-memberships-pro' );
		}

		if ( ! empty( $message ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	/**
	 * Map text $status to the Action Scheduler status.
	 *
	 * Accepts fuzzy matches for the following statuses: queue, queued, waiting, pending | error, failed | in-progress, running, processing | completed, complete, done.
	 *
	 * @access private
	 * @since 3.5
	 * @param string $status The text status to map to AS status.
	 * @return string The mapped status.
	 */
	public static function get_as_status( $status ) {
		// Map a text $status to the Action Scheduler status.
		switch ( $status ) {
			case 'queue':
			case 'queued':
			case 'waiting':
			case 'pending':
				$status = ActionScheduler_Store::STATUS_PENDING;
				break;
			case 'error':
			case 'failed':
				$status = ActionScheduler_Store::STATUS_FAILED;
				break;
			case 'in-progress':
			case 'running':
			case 'processing':
				$status = ActionScheduler_Store::STATUS_RUNNING;
				break;
			case 'completed':
			case 'complete':
			case 'done':
			default:
				$status = ActionScheduler_Store::STATUS_COMPLETE;
		}
		return $status;
	}

	/**
	 * Convert a relative or absolute time string into a timestamp using the site's timezone.
	 *
	 * @access private
	 * @since 3.5
	 * @param string $time_string A string like "+10 minutes" or "tomorrow 5pm".
	 * @return int UTC timestamp adjusted to the WordPress timezone.
	 */
	private static function as_strtotime( $time_string ) {
		$timezone = wp_timezone();
		$datetime = new DateTimeImmutable( 'now', $timezone );
		$modified = $datetime->modify( $time_string );
		return $modified->getTimestamp();
	}

	/**
	 * Halt Action Scheduler.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public static function halt() {
		update_option( 'pmpro_as_halted', true );
	}

	/**
	 * Resume Action Scheduler.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public static function resume() {
		update_option( 'pmpro_as_halted', false );
	}

	/**
	 * Get the active source path for Action Scheduler.
	 *
	 * @access private
	 * @since 3.5
	 * @return string The path to the active source of Action Scheduler.
	 */
	public static function get_active_source_path() {
		if ( class_exists( '\ActionScheduler_SystemInformation' ) ) {
			return ActionScheduler_SystemInformation::active_source_path();
		} else {
			// This was deprecated in Action Scheduler v3.9,2 when the SystemInformation class was introduced.
			return ActionScheduler_Versions::instance()->active_source_path();
		}
	}

	/**
	 * Clear scheduled recurring tasks.
	 *
	 * This method is used to clear any previously scheduled recurring tasks, typically when the plugin is updated.
	 *
	 * @access public
	 * @since 3.5.3
	 */
	public static function clear_recurring_tasks() {
		self::remove_actions( null, array(), 'pmpro_recurring_tasks', 'pending' );
	}

	/**
	 * Check if Action Scheduler has any health issues.
	 *
	 * @since 3.5.3
	 * @return array Array of issues found, empty if no issues.
	 */
	public static function check_action_scheduler_table_health() {
		global $wpdb;

		$issues = array();

		// List of required Action Scheduler tables
		$required_tables = array(
			'actionscheduler_actions',
			'actionscheduler_claims',
			'actionscheduler_groups',
			'actionscheduler_logs',
		);

		// Check if each table exists
		foreach ( $required_tables as $table ) {
			$full_table_name    = $wpdb->prefix . $table;
			$escaped_table_name = $wpdb->esc_like( $full_table_name );

			$table_exists = $wpdb->get_var(
				$wpdb->prepare(
					'SHOW TABLES LIKE %s',
					$escaped_table_name
				)
			);

			if ( $table_exists !== $full_table_name ) {
				$issues[] = sprintf( __( 'Missing table: %s', 'paid-memberships-pro' ), $full_table_name );
			}
		}

		// Check for 'priority' column in 'actionscheduler_actions' table (3.6+ requirement)
		$actions_table         = $wpdb->prefix . 'actionscheduler_actions';
		$escaped_actions_table = $wpdb->esc_like( $actions_table );

		$actions_table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$escaped_actions_table
			)
		);

		if ( $actions_table_exists === $actions_table ) {
			$priority_column = $wpdb->get_results(
				$wpdb->prepare(
					"SHOW COLUMNS FROM `{$actions_table}` LIKE %s",
					'priority'
				)
			);

			if ( empty( $priority_column ) ) {
				$issues[] = __( 'Missing priority column in actionscheduler_actions table (required for Action Scheduler 3.6+)', 'paid-memberships-pro' );
			}
		} else {
			// Already flagged as missing earlier, but could be handled separately if needed
			// $issues[] = __( 'actionscheduler_actions table does not exist.', 'paid-memberships-pro' );
		}

		return $issues;
	}
}
