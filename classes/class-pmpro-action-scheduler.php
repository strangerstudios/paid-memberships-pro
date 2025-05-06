<?php
/**
 *
 * This class handle tasks added by the core plugin and Add Ons for Action Scheduler (A.S.).
 *
 * @package pb_plugin
 */


class PMPro_Action_Scheduler {

	/**
	 * The hook to schedule or check for
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string $hook The hook A.S. should use to schedule a task, or in searching for a previously scheduled one.
	 */
	public string $hook;

	/**
	 * The data to pass through the hook to the task
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array $args The passed data, default is array(), required by A.S.
	 */
	public array $args = array();

	/**
	 * The group a task or tasks is assigned to
	 *
	 * @since 1.0.0
	 * @access public
	 * @var string $group The group for tasks. Helpful in searching and removing existing similar tasks (i.e "timelogs"). Default is empty string.
	 */
	public string $group = '';

	/**
	 * __construct
	 */
	public function __construct() {

		if ( ! class_exists( \ActionScheduler::class ) ) {
			require_once( PMPRO_DIR . '/includes/lib/action-scheduler/action-scheduler.php' ); // Load our copy of Action Scheduler if needed.
		}

		// Increase the batch size per queue.
		add_filter( 'action_scheduler_queue_runner_batch_size', array( $this, 'modify_batch_size' ) );

		// Increase queue time limit.
		add_filter( 'action_scheduler_queue_runner_time_limit', array( $this, 'modify_batch_time_limit' ) );

		// Reduce retention time.
		add_filter( 'action_scheduler_retention_period', array( $this, 'modify_retention' ) );

	}

	/**
	 * Check if AS has an existing task in the upcoming queue (default) or alternatively past completed items
	 *
	 * @since 1.0.0
	 * @access public
	 * @param boolean        $upcoming
	 * @param string|boolean $timeframe A unix timestamp.
	 * @return array|boolean The results (if any), or false.
	 */
	private function has_existing_task( $upcoming = true, $timestamp = false ) {

		$status = $upcoming ? ActionScheduler_Store::STATUS_PENDING : ActionScheduler_Store::STATUS_COMPLETE;

		$args = array(
			'hook'   => $this->hook,
			'args'   => $this->args,
			'group'  => $this->group,
			'status' => $status,
		);

		// Check that the past tasks are in the timeframe provided
		if ( $timestamp ) {
			$args['date']         = $timestamp;
			$args['date_compare'] = '>=';
		}

		$results = as_get_scheduled_actions( $args );

		// Return results.
		if ( ! empty( $results ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the count of AS items currently queued for a group
	 *
	 * Returned number is used to increment the delay between scheduled tasks to reduce Teamwork API load.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return int The number of tasks in the queue, if any
	 */
	private function existing_tasks_for_group_count() {
		$search_args = array(
			'group'  => $this->group, // Only check against the same group of tasks.
			'status' => ActionScheduler_Store::STATUS_PENDING, // Only check pending tasks, not complete ones.
		);

		$results = as_get_scheduled_actions( $search_args );

		// Can use this number to increase the delay in task queuing (see maybe_add_task).
		return count( $results );
	}

	/**
	 * Add task for AS, optionally as a future task, or recurring task
	 *
	 * @since 1.0.0
	 * @access public
	 * @param int $timestamp The time in the future this task should run (optional).
	 * @return int The scheduled action’s ID
	 */
	private function queue_task( $timestamp = null ) {
		if ( null !== $timestamp ) {
			// Run at a future date.
			return as_schedule_single_action( $timestamp, $this->hook, $this->args, $this->group );
		} else {
			// Run as soon as possible.
			return as_enqueue_async_action( $this->hook, $this->args, $this->group );
		}
	}

	/**
	 * Check if a task exists in the queue of tasks, add it if not and maybe add a queue delay.
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string  $hook The hook for the task.
	 * @param mixed   $args The data being passed to the task hook.
	 * @param string  $group The group this task should be assigned to.
	 * @param ?int    $timestamp An pb_strtotime datetime.
	 * @param boolean $run_asap Whether to bypass the count delay and run async asap.
	 * @return void
	 */
	public function maybe_add_task( $hook, $args, $group, int $timestamp = null, $run_asap = false ) {
		$this->hook  = $hook;
		$this->args  = array( $args );
		$this->group = $group;

		// Check for a task in the queue matching this task.
		if ( $this->has_existing_task() ) {
			// Logger::info( 'Task already scheduled for ' . $this->hook . ' in the ' . $this->group . ' group' );
			return;
		} else {
			// If we don't have an existing task, add it.
			if ( null === $timestamp && false === $run_asap ) {
				// Logger::info( 'No timestamp provided, and not set to async.' );
				// The total count of tasks for a group.
				$task_count = $this->existing_tasks_for_group_count();
				// Add the count delay to the timestamp.
				$timestamp = $this->pb_strtotime( "+{$task_count} minutes" );
			}

			$this->queue_task( $timestamp );
		}
	}

	/**
	 * Maybe add a recurring task (if not exists)
	 *
	 * Check if a recurring task exists in the queue of tasks, add it if not.
	 *
	 * @param string $hook  The hook for the task.
	 * @param int    $interval_in_seconds  The interval in seconds this recurring task should run.
	 * @param int    $first_run_datetime   An pb_strtotime datetime in the future this task should first run.
	 * @param string $group     The group this task should be assigned to.
	 * @return void
	 */
	public function maybe_add_recurring_task( $hook, $interval_in_seconds = null, $first_run_datetime = null, $group = 'recurring_tasks' ) {
		$this->hook  = $hook;
		$this->group = $group;

		if ( ! $this->has_existing_task( true ) ) {
			$this->queue_recurring_task( $interval_in_seconds, $first_run_datetime );
		}
	}

	/**
	 * Add a recurring task for A.S.
	 *
	 * @since 1.0.0
	 * @access private
	 * @param int $interval_in_seconds The interval in seconds this recurring task should run.
	 * @param int $first_run_datetime The datetime in the future this task should first run.
	 * @return int The scheduled action’s ID
	 */
	private function queue_recurring_task( $interval_in_seconds = null, $first_run_datetime = null ) {
		// Make sure first run datetime has been set.
		$first_run_datetime = $first_run_datetime ?: strtotime( 'now +5 minutes' );
		// Schedule this task in the future, and make it recurring.
		if ( ! empty( $interval_in_seconds ) ) {
			return as_schedule_recurring_action( $first_run_datetime, $interval_in_seconds, $this->hook, $this->args, $this->group );
		} else {
			return new WP_Error( 'action_scheduler_warning', __( 'An interval is required to queue a recurring task.', 'pb-plugin' ) );
		}
	}

	/**
	 * Clear the task queue for a given hook.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array|null|WP_Error The scheduled action IDs in an array if scheduled action(s) were found, null if no action(s) found, WP_Error if passed incorrectly
	 */
	public static function clear_task_queue( $hook ) {
		if ( ! empty( $hook ) ) {
			return as_unschedule_all_actions( $hook );
		} else {
			throw new WP_Error( 'no_hook_provided', __( 'No hook provided to clear the task queue.', 'pb-plugin' ) );
		}
	}


	/**
	 * Add the ability to store custom messages with Action Scheduler's logs.
	 *
	 * @param string $message
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function add_log( $action, $message = 'This is a test' ) {
		$action_id = ActionScheduler::store()->find_action( $action, array( 'status' => ActionScheduler_Store::STATUS_RUNNING ) );
		ActionScheduler::logger()->log( $action_id, $message );
	}

	/**
	 * Setup our custom scheduled hooks that run daily & weekly. You can use these hooks to perform any scheduler
	 * task that needs to run regularly each day or week, overnight.
	 *
	 * @return void
	 */
	public function add_recurring_hooks() {
		$this->maybe_add_recurring_task( 'pmpro_schedule_daily', DAY_IN_SECONDS, strtotime( 'tomorrow 1am' ) );
		$this->maybe_add_recurring_task( 'pb_schedule_weekly', WEEK_IN_SECONDS, strtotime( 'next monday 1am' ) );
		$this->add_monthly_hook();
	}

	/**
	 * Setup our custom monthly hook.
	 *
	 * This needs to be a custom recurring hook— since months have different lengths, it will run on the first of the month,
	 * and each task that is hooked to it should call and re-schedule this task.
	 * for the first of the next month.
	 *
	 * You can use this hook to perform any scheduler task that needs to run monthly on the first day of the month.
	 *
	 * @return void
	 */
	public function add_monthly_hook() {
		$this->maybe_add_task( 'pb_schedule_monthly', null, 'recurring_tasks', strtotime( 'first day of next month 1am' ) );
	}

	/**
	 * Action scheduler claims a batch of actions to process in each request. It keeps the batch
	 * fairly small (by default, 25) in order to prevent errors, like memory exhaustion.
	 *
	 * This method increases or decreases it so that more/less actions are processed in each queue, which speeds up the
	 * overall queue processing time due to latency in requests and the minimum 1 minute between each
	 * queue being processed.
	 *
	 * For more details, see: https://actionscheduler.org/perf/#increasing-batch-size
	 */
	public function pmpro_modify_batch_size( $batch_size ) {

		// Apple filters here so that others can mofiy these values.
		// For example, if you are using WP Engine, you may want to set this to 10.
		// If you are using Pantheon, you may want to set this to 50.
		// If you are using a shared host, you may want to set this to 5.
		$batch_size = 25;

		if ( defined( 'PANTHEON_ENVIRONMENT' ) ) {
			$batch_size = 50;
		} elseif ( defined( 'WP_ENGINE' ) ) {
			$batch_size = 20;
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$batch_size = 5;
		}

		apply_filters( 'pmpro_action_scheduler_batch_size', $batch_size );

		return $batch_size;
	}

	/**
	 * Action Scheduler provides a default maximum of 30 seconds in which to process actions. Increase this to 120
	 * seconds for hosts like Pantheon which support such a long time limit, or if you know your PHP and Apache, Nginx
	 * or other web server configs support a longer time limit.
	 *
	 * Note, WP Engine only supports a maximum of 60 seconds - if using WP Engine, this will need to be decreased to 60.
	 */
	public function modify_batch_time_limit() {
		return 30;
	}

	/**
	 * How long to store completed Action Scheduler items.
	 * Action Scheduler default is 30 days.
	 */
	public function modify_retention() {
		// One month.
		return 30 * DAY_IN_SECONDS;
	}
}
