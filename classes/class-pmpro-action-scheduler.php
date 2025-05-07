<?php
/**
 *
 * This class handle tasks added by the core plugin and Add Ons for Action Scheduler (AS).
 *
 * It is a wrapper for the Action Scheduler library/plugin, which is a task scheduling library for WordPress.
 * This class provides methods to schedule, manage, and execute tasks asynchronously, and is both a replacement
 * for older wp-cron based tasks and a more efficient way to handle background tasks in WordPress.
 *
 * Keep in mind that: tasks are handled asynchronously, and queued tasks
 *
 * @package pmpro_plugin
 */


class PMPro_Action_Scheduler {

	/**
	 * The single instance of the class.
	 *
	 * @var PMPro_Action_Scheduler
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add our custom hooks for hourly, daily, weekly and monthly tasks.
		add_action( 'action_scheduler_init', array( $this, 'add_recurring_hooks' ) );

		// Add dummy callbacks for the scheduled tasks.
		add_action( 'action_scheduler_init', array( $this, 'add_dummy_callbacks' ) );

		// Handle the monthly tasks.
		add_action( 'pmpro_trigger_monthly', array( $this, 'handle_monthly_tasks' ) );
	}

	/**
	 * Get the single instance of the class.
	 *
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
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent the instance from being unserialized.
	 *
	 * @return void
	 */
	private function __wakeup() {}

	/**
	 * Check if AS has an existing task in the pending/completed queue
	 *
	 * @access public
	 * @since 3.5
	 *
	 * @param string         $hook The hook name for the task.
	 * @param array          $args Arguments passed to the task hook.
	 * @param string         $group The group the task belongs to.
	 * @param boolean        $upcoming True to check pending tasks, false to check completed tasks.
	 * @param string|boolean $timestamp Optional timestamp to compare with task date.
	 *
	 * @return bool True if an existing task is found, false otherwise.
	 */
	public function has_existing_task( $hook, $args = array(), $group = '', $upcoming = true, $timestamp = false ) {
		$status = $upcoming ? ActionScheduler_Store::STATUS_PENDING : ActionScheduler_Store::STATUS_COMPLETE;

		$query_args = array(
			'hook'   => $hook,
			'args'   => $args,
			'group'  => $group,
			'status' => $status,
		);

		// If we are looking for a completed task, we need to provide a timestamp.
		if ( ! $upcoming && $timestamp ) {
			$query_args['date']         = $timestamp;
			$query_args['date_compare'] = '>=';
		} elseif ( ! $upcoming ) {
			$query_args['date']         = current_time( 'mysql' );
			$query_args['date_compare'] = '<=';
		}

		$results = as_get_scheduled_actions( $query_args );
		return ! empty( $results );
	}

	/**
	 * Get the count of AS items currently queued for a group
	 *
	 * @access private
	 * @since 3.5
	 *
	 * @param string $group The task group name.
	 *
	 * @return int The number of tasks in the queue.
	 */
	private function count_existing_tasks_for_group( $group ) {
		$search_args = array(
			'group'  => $group,
			'status' => ActionScheduler_Store::STATUS_PENDING,
		);
		return count( as_get_scheduled_actions( $search_args ) );
	}

	/**
	 * Add task for AS, optionally as a future task, or recurring task
	 *
	 * @access private
	 * @since 3.5
	 *
	 * @param string   $hook The hook name for the task.
	 * @param array    $args Arguments passed to the task hook.
	 * @param string   $group The group the task belongs to.
	 * @param int|null $timestamp Optional timestamp for scheduling the task.
	 *
	 * @return int The scheduled action’s ID.
	 */
	private function queue_task( $hook, $args = array(), $group = '', $timestamp = null ) {
		if ( null !== $timestamp ) {
			return as_schedule_single_action( $timestamp, $hook, $args, $group );
		} else {
			return as_enqueue_async_action( $hook, $args, $group );
		}
	}

	/**
	 * Check if a task exists in the queue of tasks, add it if not and maybe add a queue delay.
	 *
	 * @access public
	 * @since 3.5
	 *
	 * @param string   $hook The hook for the task.
	 * @param mixed    $args The data being passed to the task hook.
	 * @param string   $group The group this task should be assigned to.
	 * @param int|null $timestamp An pmpro_strtotime datetime.
	 * @param boolean  $run_asap Whether to bypass the count delay and run async asap.
	 *
	 * @return void
	 */
	public function maybe_add_task( $hook, $args, $group, int $timestamp = null, $run_asap = false ) {
		// Check for a task in the queue matching this task.
		if ( $this->has_existing_task( $hook, $args, $group ) ) {
			return;
		}

		if ( null === $timestamp && false === $run_asap ) {
			$task_count = $this->count_existing_tasks_for_group( $group );
			$timestamp  = $this->pmpro_strtotime( "+{$task_count} minutes" );
		}

		$this->queue_task( $hook, $args, $group, $timestamp );
	}

	/**
	 * Maybe add a recurring task (if not exists)
	 *
	 * @access public
	 * @since 3.5
	 *
	 * @param string   $hook The hook for the task.
	 * @param int|null $interval_in_seconds The interval in seconds this recurring task should run.
	 * @param int|null $first_run_datetime An pmpro_strtotime datetime in the future this task should first run.
	 * @param string   $group The group this task should be assigned to.
	 *
	 * @return void
	 */
	public function maybe_add_recurring_task( $hook, $interval_in_seconds = null, $first_run_datetime = null, $group = 'pmpro_recurring_tasks' ) {
		if ( ! $this->has_existing_task( $hook, array(), $group ) ) {
			$this->queue_recurring_task( $interval_in_seconds, $first_run_datetime, $hook, array(), $group );
		}
	}

	/**
	 * Add a recurring task for AS
	 *
	 * @access private
	 * @since 3.5
	 *
	 * @param int|null $interval_in_seconds The interval in seconds this recurring task should run.
	 * @param int|null $first_run_datetime An pmpro_strtotime datetime in the future this task should first run.
	 * @param string   $hook The hook for the task.
	 * @param array    $args The data being passed to the task hook.
	 * @param string   $group The group this task should be assigned to.
	 * @return int The scheduled action’s ID.
	 * @throws WP_Error If no interval is provided.
	 */
	private function queue_recurring_task( $interval_in_seconds, $first_run_datetime, $hook, $args, $group ) {
		// Make sure first run datetime has been set.
		$first_run_datetime = $first_run_datetime ?: $this->pmpro_strtotime( 'now +5 minutes' );
		// Schedule this task in the future, and make it recurring.
		if ( ! empty( $interval_in_seconds ) ) {
			return as_schedule_recurring_action( $first_run_datetime, $interval_in_seconds, $hook, $args, $group );
		} else {
			throw new WP_Error( 'pmpro_action_scheduler_warning', __( 'An interval is required to queue an Action Scheduler recurring task.', 'paid-memberships-pro' ) );
		}
	}

	/**
	 * Clear all tasks in the queue for a given hook.
	 *
	 * @access public
	 * @since 3.5
	 *
	 * @param string $hook The hook name for the task.
	 * @param string $group The group the task belongs to.
	 * @return void
	 * @throws WP_Error If no hook is provided.
	 */
	public static function clear_task_queue( $hook ) {
		if ( ! empty( $hook ) ) {
			return as_unschedule_all_actions( $hook );
		} else {
			throw new WP_Error( 'pmpro_action_scheduler_warning', __( 'No hook provided to clear the Action Scheduler task queue.', 'paid-memberships-pro' ) );
		}
	}

	/**
	 * Setup our custom scheduled hooks that run daily & weekly. You can use these hooks to perform any scheduler
	 * task that needs to run regularly each day or week, overnight.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function add_recurring_hooks() {
		$this->maybe_add_recurring_task(
			'pmpro_schedule_hourly',
			HOUR_IN_SECONDS,
			$this->pmpro_strtotime( 'now +1 hour' ),
			'pmpro_recurring_tasks'
		);

		$this->maybe_add_recurring_task(
			'pmpro_schedule_daily',
			DAY_IN_SECONDS,
			$this->pmpro_strtotime( 'tomorrow 10:30am' ),
			'pmpro_recurring_tasks'
		);

		$this->maybe_add_recurring_task(
			'pmpro_schedule_weekly',
			WEEK_IN_SECONDS,
			$this->pmpro_strtotime( 'next sunday 8:00am' ),
			'pmpro_recurring_tasks'
		);
		// Schedule the first instance of our monthly action if none exists.
		if ( ! $this->has_existing_task( 'pmpro_trigger_monthly', array(), 'pmpro_recurring_tasks' ) ) {
			$first = $this->pmpro_strtotime( 'first day of next month 8:00am' );
			as_schedule_single_action( $first, 'pmpro_trigger_monthly', array(), 'pmpro_recurring_tasks' );
		}
	}

	/**
	 * Add dummy callbacks for the scheduled tasks.
	 *
	 * @access public
	 * @since 3.5
	 * @return void
	 */
	public function add_dummy_callbacks() {
		add_action( 'pmpro_schedule_hourly', function () { return; } );
		add_action( 'pmpro_schedule_daily', function () { return; } );
		add_action( 'pmpro_schedule_weekly', function () { return; } );
		add_action( 'pmpro_trigger_monthly', function () { return; } );
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

		// Schedule the next run for exactly one calendar month from now.
		$next_month = $this->pmpro_strtotime( 'first day of next month 8:00am' );
		as_schedule_single_action( $next_month, 'pmpro_trigger_monthly', array(), 'recurring_tasks' );
	}

	/**
	 * Add the ability to store custom messages with Action Scheduler's logs when running a task.
	 *
	 * @access public
	 * @since 3.5
	 *
	 * @param string $action The action hook name.
	 * @param string $status The status of the action.
	 * @param string $message The log message to add.
	 * @return void
	 */
	public function add_log_msg( $action, $status, $message = '' ) {
		if ( empty( $action ) || empty( $message ) ) {
			// If we don't have a message or action, we can't log anything.
			return;
		}
		$action_id = ActionScheduler::store()->find_action( $action, array( 'status' => ActionScheduler_Store::STATUS_RUNNING ) );

		if ( empty( $action_id ) ) {
			// If we don't have an action ID, we can't log anything.
			return;
		}
		// Log the message with the action ID when running a task.
		ActionScheduler::logger()->log( $action_id, $message );
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
	 *
	 * @param int $batch_size The current batch size.
	 * @return int Modified batch size.
	 */
	public function modify_batch_size( $batch_size ) {

		// If we are on Pantheon, we can set it to 50.
		if ( defined( 'PANTHEON_ENVIRONMENT' ) ) {
			$batch_size = 50;
			// If we are on WP Engine, we should set it to 20.
		} elseif ( defined( 'WP_ENGINE' ) ) {
			$batch_size = 20;
		}

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
	 * We can increase this for hosts like Pantheon and WP Engine.
	 *
	 * You can also set this to a different value using the pmpro_action_scheduler_time_limit_seconds filter.
	 *
	 * For more details on the Action Scheduler time limit, see: https://actionscheduler.org/perf/#increasing-time-limit
	 *
	 * @access public
	 * @since 3.5
	 *
	 * @param int $time_limit The current time limit in seconds.
	 * @return int Modified time limit in seconds.
	 */
	public function modify_batch_time_limit( $time_limit ) {

		// Set sensible defaults based on known environment limits.
		// If we are on Pantheon, we can set it to 120.
		if ( defined( 'PANTHEON_ENVIRONMENT' ) ) {
			$time_limit = 120;
			// If we are on WP Engine, we can set it to 60.
		} elseif ( defined( 'WP_ENGINE' ) ) {
			$time_limit = 60;
		}

		/**
		 * Public filter for adjusting the time limit for Action Scheduler batches.
		 *
		 * @param int $time_limit The time limit in seconds.
		 */
		$time_limit = apply_filters( 'pmpro_action_scheduler_time_limit_seconds', $time_limit );

		return $time_limit;
	}

	/**
	 * Get a UTC timestamp for a given local time string, using the site's timezone.
	 *
	 * @access private
	 * @since 3.5
	 *
	 * @param string      $time_string Time string in 'H:i:s' or 'Y-m-d H:i:s' format. Defaults to 'now'.
	 * @param string|null $date Optional. If provided, use this date (in Y-m-d format) with the time. Defaults to today.
	 * @return int UTC timestamp suitable for scheduling.
	 */
	private function get_local_timestamp( $time_string = 'now', $date = null ) {
		$timezone = wp_timezone();

		if ( empty( $date ) ) {
			$date = ( new DateTime( 'now', $timezone ) )->format( 'Y-m-d' );
		}

		$datetime = new DateTime( "{$date} {$time_string}", $timezone );
		return $datetime->getTimestamp();
	}

	/**
	 * Convert a relative or absolute time string into a timestamp using the site's timezone.
	 *
	 * @access private
	 * @since 3.5
	 *
	 * @param string $time_string A string like "+10 minutes" or "tomorrow 5pm".
	 * @return int UTC timestamp adjusted to the WordPress timezone.
	 */
	private function pmpro_strtotime( $time_string ) {
		$timezone = wp_timezone();
		$datetime = new DateTimeImmutable( 'now', $timezone );
		$modified = $datetime->modify( $time_string );
		return $modified->getTimestamp();
	}
}
