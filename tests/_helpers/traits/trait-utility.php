<?php
namespace PMPro\Tests\Helpers\Traits;

trait Utility {

	/**
	 * Captures output from a function.
	 *
	 * @param       $callback
	 * @param array $params
	 *
	 * @return false|string
	 */
	public function pmpro_utility_return_output( $callback, $params = [] ) {

		ob_start();

		call_user_func_array( $callback, $params );

		return ob_get_clean();

	}

}

//EOF
