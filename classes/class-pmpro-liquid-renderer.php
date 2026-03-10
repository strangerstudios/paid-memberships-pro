<?php
/**
 * Liquid-style template renderer.
 *
 * Handles {{ variable | filter }} output tags and
 * {% if %} / {% elsif %} / {% else %} / {% endif %} conditionals.
 *
 * @since 3.7
 */
class PMPro_Liquid_Renderer {

	/**
	 * Process Liquid-style template syntax in the given content.
	 *
	 * @since 3.7
	 *
	 * @param string $content The content to process.
	 * @param array  $data    Key-value pairs for variable resolution.
	 * @param array  $args    Optional renderer arguments.
	 * @return string The processed content.
	 */
	public static function render( $content, $data, $args = array() ) {
		if ( empty( $content ) || ! is_array( $data ) ) {
			return $content;
		}

		// Process conditionals first so we only render the winning branches.
		$content = self::process_conditionals( $content, $data, $args );

		// Then process {{ variable | filter }} output tags.
		$content = self::process_output_tags( $content, $data, $args );

		return $content;
	}

	/**
	 * Get the registry of available Liquid filters.
	 *
	 * Returns a map of filter name => array( 'callback' => callable, 'description' => string ).
	 *
	 * @since 3.7
	 *
	 * @return array The filter registry.
	 */
	public static function get_filters() {
		$filters = array(
			'upcase'     => array(
				'callback'    => function ( $value ) {
					return strtoupper( (string) $value );
				},
				'description' => __( 'Convert to uppercase. Example: {{ name | upcase }}', 'paid-memberships-pro' ),
			),
			'downcase'   => array(
				'callback'    => function ( $value ) {
					return strtolower( (string) $value );
				},
				'description' => __( 'Convert to lowercase. Example: {{ name | downcase }}', 'paid-memberships-pro' ),
			),
			'strip'      => array(
				'callback'    => function ( $value ) {
					return trim( (string) $value );
				},
				'description' => __( 'Remove leading and trailing whitespace. Example: {{ name | strip }}', 'paid-memberships-pro' ),
			),
			'default'    => array(
				'callback'    => function ( $value, $default_value = '' ) {
					return ( $value === null || $value === '' || $value === false ) ? $default_value : $value;
				},
				'description' => __( 'Use a default value if empty. Example: {{ discount_code | default: "None" }}', 'paid-memberships-pro' ),
			),
		);

		return $filters;
	}

	/**
	 * Process all {% if %} ... {% endif %} conditional blocks in the content.
	 *
	 * Finds outermost conditional blocks first, evaluates them,
	 * and recursively processes nested conditionals in the winning segment.
	 *
	 * @since 3.7
	 *
	 * @param string $content The content containing conditional blocks.
	 * @param array  $data    Key-value pairs for variable resolution.
	 * @param array  $args    Optional renderer arguments.
	 * @return string The content with all conditional blocks resolved.
	 */
	private static function process_conditionals( $content, &$data, $args ) {
		// Keep processing until no more outermost {% if %} blocks are found.
		while ( true ) {
			// Find the first {% if ... %} tag.
			if ( ! preg_match( '/\{%\s*if\s+(.+?)\s*%\}/s', $content, $match, PREG_OFFSET_CAPTURE ) ) {
				break;
			}

			$block_start = $match[0][1];
			$condition   = $match[1][0];
			$search_from = $block_start + strlen( $match[0][0] );

			// Track nesting depth to find the matching {% endif %}.
			$depth   = 1;
			$pos     = $search_from;
			$found   = false;

			while ( $depth > 0 && preg_match( '/\{%\s*(if\s+|endif)\s*.*?%\}/s', $content, $tag_match, PREG_OFFSET_CAPTURE, $pos ) ) {
				$tag_text = $tag_match[0][0];
				$tag_pos  = $tag_match[0][1];
				$tag_type = trim( $tag_match[1][0] );

				if ( strpos( $tag_type, 'if' ) === 0 ) {
					$depth++;
				} else {
					$depth--;
				}

				if ( $depth === 0 ) {
					// Found matching {% endif %}.
					$block_end   = $tag_pos + strlen( $tag_text );
					$block_inner = substr( $content, $search_from, $tag_pos - $search_from );
					$replacement = self::evaluate_conditional_block( $condition, $block_inner, $data, $args );
					$content     = substr( $content, 0, $block_start ) . $replacement . substr( $content, $block_end );
					$found       = true;
					break;
				}

				$pos = $tag_pos + strlen( $tag_text );
			}

			// If no matching {% endif %} was found, stop to avoid infinite loop.
			if ( ! $found ) {
				break;
			}
		}

		return $content;
	}

	/**
	 * Evaluate a conditional block and return the content of the winning branch.
	 *
	 * @since 3.7
	 *
	 * @param string $condition   The condition expression from the {% if %} tag.
	 * @param string $block_inner The content between {% if %} and {% endif %}.
	 * @param array  $data        Key-value pairs for variable resolution.
	 * @param array  $args        Optional renderer arguments.
	 * @return string The content of the first branch whose condition is truthy.
	 */
	private static function evaluate_conditional_block( $condition, $block_inner, &$data, $args ) {
		// Split block_inner into branches at top-level {% elsif %} and {% else %} tags.
		$branches = array();
		$depth    = 0;
		$current  = '';
		$pos      = 0;

		while ( $pos < strlen( $block_inner ) ) {
			// Check for nested {% if %} or {% endif %} to track depth.
			if ( preg_match( '/\{%\s*(if\s+|endif)\s*.*?%\}/s', $block_inner, $tag_match, PREG_OFFSET_CAPTURE, $pos ) && $tag_match[0][1] === $pos ) {
				$tag_type = trim( $tag_match[1][0] );
				if ( strpos( $tag_type, 'if' ) === 0 ) {
					$depth++;
				} else {
					$depth--;
				}
				$current .= $tag_match[0][0];
				$pos      = $tag_match[0][1] + strlen( $tag_match[0][0] );
				continue;
			}

			// At top level, check for {% elsif %} or {% else %}.
			if ( $depth === 0 ) {
				if ( preg_match( '/\{%\s*elsif\s+(.+?)\s*%\}/s', $block_inner, $elsif_match, PREG_OFFSET_CAPTURE, $pos ) && $elsif_match[0][1] === $pos ) {
					$branches[] = array( 'condition' => $condition, 'content' => $current );
					$condition  = $elsif_match[1][0];
					$current    = '';
					$pos        = $elsif_match[0][1] + strlen( $elsif_match[0][0] );
					continue;
				}

				if ( preg_match( '/\{%\s*else\s*%\}/s', $block_inner, $else_match, PREG_OFFSET_CAPTURE, $pos ) && $else_match[0][1] === $pos ) {
					$branches[] = array( 'condition' => $condition, 'content' => $current );
					$condition  = '__else__';
					$current    = '';
					$pos        = $else_match[0][1] + strlen( $else_match[0][0] );
					continue;
				}
			}

			$current .= $block_inner[ $pos ];
			$pos++;
		}

		// Add the final branch.
		$branches[] = array( 'condition' => $condition, 'content' => $current );

		// Evaluate each branch and return the first truthy one.
		foreach ( $branches as $branch ) {
			$is_match = ( $branch['condition'] === '__else__' ) || self::evaluate_condition( $branch['condition'], $data, $args );
			if ( $is_match ) {
				// Recursively process nested conditionals in the winning branch.
				return self::process_conditionals( $branch['content'], $data, $args );
			}
		}

		return '';
	}

	/**
	 * Evaluate a condition expression string and return a boolean result.
	 *
	 * Supports truthy checks, comparison operators (==, !=, >, <, >=, <=),
	 * empty checks, and boolean operators (and, or).
	 *
	 * @since 3.7
	 *
	 * @param string $condition_string The condition expression to evaluate.
	 * @param array  $data             Key-value pairs for variable resolution.
	 * @param array  $args             Optional renderer arguments.
	 * @return bool The result of the condition evaluation.
	 */
	private static function evaluate_condition( $condition_string, &$data, $args ) {
		$condition_string = trim( $condition_string );

		// Evaluate top-level boolean operators from right-to-left to match Liquid semantics.
		$boolean_chain = self::parse_boolean_condition_chain( $condition_string );
		if ( ! empty( $boolean_chain ) ) {
			return self::evaluate_boolean_chain( $boolean_chain, $data, $args );
		}

		return self::evaluate_single_condition( $condition_string, $data, $args );
	}

	/**
	 * Evaluate a single condition without top-level boolean chaining.
	 *
	 * @since 3.7
	 *
	 * @param string $condition_string The condition expression to evaluate.
	 * @param array  $data             Key-value pairs for variable resolution.
	 * @param array  $args             Optional renderer arguments.
	 * @return bool The result of the condition evaluation.
	 */
	private static function evaluate_single_condition( $condition_string, &$data, $args ) {
		// Check for comparison operators.
		$comparison = self::parse_comparison_condition( $condition_string );
		if ( ! empty( $comparison ) ) {
			$left_token  = $comparison['left'];
			$operator    = $comparison['operator'];
			$right_token = $comparison['right'];

			$left_value = self::resolve_value( $left_token, $data, $args );

			// Handle "empty" keyword on the right side.
			if ( $right_token === 'empty' ) {
				$is_empty = ( $left_value === null || $left_value === '' || $left_value === false || ( is_array( $left_value ) && empty( $left_value ) ) );
				return ( $operator === '==' ) ? $is_empty : ! $is_empty;
			}

			$right_value = self::resolve_value( $right_token, $data, $args );

			switch ( $operator ) {
				case '==':
					return $left_value == $right_value;
				case '!=':
					return $left_value != $right_value;
				case '>':
					return $left_value > $right_value;
				case '<':
					return $left_value < $right_value;
				case '>=':
					return $left_value >= $right_value;
				case '<=':
					return $left_value <= $right_value;
			}
		}

		// Simple truthy check.
		$value = self::resolve_value( $condition_string, $data, $args );
		return ! empty( $value );
	}

	/**
	 * Evaluate a parsed boolean chain from right-to-left.
	 *
	 * Liquid evaluates mixed boolean operators from right-to-left and does not support
	 * parenthesized grouping in conditional tags.
	 *
	 * @since 3.7
	 *
	 * @param array $boolean_chain The parsed boolean chain.
	 * @param array $data          Key-value pairs for variable resolution.
	 * @param array $args          Optional renderer arguments.
	 * @return bool The result of the boolean evaluation.
	 */
	private static function evaluate_boolean_chain( $boolean_chain, &$data, $args ) {
		$operators = $boolean_chain['operators'];
		$operands  = $boolean_chain['operands'];

		if ( empty( $operands ) ) {
			return false;
		}

		$result = self::evaluate_condition( $operands[ count( $operands ) - 1 ], $data, $args );

		for ( $index = count( $operators ) - 1; $index >= 0; $index-- ) {
			$operator = $operators[ $index ];

			if ( $operator === 'and' ) {
				if ( ! $result ) {
					continue;
				}

				$left_result = self::evaluate_condition( $operands[ $index ], $data, $args );
				$result      = $left_result && $result;
			} else {
				if ( $result ) {
					continue;
				}

				$left_result = self::evaluate_condition( $operands[ $index ], $data, $args );
				$result      = $left_result || $result;
			}
		}

		return $result;
	}

	/**
	 * Parse a single comparison condition, respecting quoted strings.
	 *
	 * @since 3.7
	 *
	 * @param string $condition_string The condition expression to parse.
	 * @return array|null {
	 *     Parsed comparison condition.
	 *
	 *     @type string $left     Left-hand token.
	 *     @type string $operator Comparison operator.
	 *     @type string $right    Right-hand token.
	 * }
	 */
	private static function parse_comparison_condition( $condition_string ) {
		$current_quote     = '';
		$parentheses_depth = 0;
		$length            = strlen( $condition_string );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $condition_string[ $i ];

			if ( $current_quote !== '' ) {
				if ( $char === $current_quote && ! self::is_escaped_character( $condition_string, $i ) ) {
					$current_quote = '';
				}
				continue;
			}

			if ( $char === '"' || $char === "'" ) {
				$current_quote = $char;
				continue;
			}

			if ( $char === '(' ) {
				$parentheses_depth++;
				continue;
			}

			if ( $char === ')' && $parentheses_depth > 0 ) {
				$parentheses_depth--;
				continue;
			}

			if ( $parentheses_depth > 0 ) {
				continue;
			}

			$operator        = null;
			$operator_length = 0;

			$two_char_operator = substr( $condition_string, $i, 2 );
			if ( in_array( $two_char_operator, array( '==', '!=', '>=', '<=' ), true ) ) {
				$operator        = $two_char_operator;
				$operator_length = 2;
			} elseif ( $char === '>' || $char === '<' ) {
				$operator        = $char;
				$operator_length = 1;
			}

			if ( $operator !== null ) {
				$left_token  = trim( substr( $condition_string, 0, $i ) );
				$right_token = trim( substr( $condition_string, $i + $operator_length ) );

				if ( $left_token === '' || $right_token === '' ) {
					return null;
				}

				return array(
					'left'     => $left_token,
					'operator' => $operator,
					'right'    => $right_token,
				);
			}
		}

		return null;
	}

	/**
	 * Parse a condition into top-level boolean operands/operators.
	 *
	 * Returns null when no top-level boolean chain is present.
	 *
	 * @since 3.7
	 *
	 * @param string $condition_string The condition expression to parse.
	 * @return array|null {
	 *     Parsed boolean chain.
	 *
	 *     @type array $operands  The condition operands.
	 *     @type array $operators The boolean operators between operands.
	 * }
	 */
	private static function parse_boolean_condition_chain( $condition_string ) {
		$operands          = array();
		$operators         = array();
		$current_operand   = '';
		$current_quote     = '';
		$parentheses_depth = 0;
		$length            = strlen( $condition_string );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $condition_string[ $i ];

			if ( $current_quote !== '' ) {
				$current_operand .= $char;
				if ( $char === $current_quote && ! self::is_escaped_character( $condition_string, $i ) ) {
					$current_quote = '';
				}
				continue;
			}

			if ( $char === '"' || $char === "'" ) {
				$current_quote   = $char;
				$current_operand .= $char;
				continue;
			}

			if ( $char === '(' ) {
				$parentheses_depth++;
				$current_operand .= $char;
				continue;
			}

			if ( $char === ')' && $parentheses_depth > 0 ) {
				$parentheses_depth--;
				$current_operand .= $char;
				continue;
			}

			if ( $parentheses_depth === 0 ) {
				$operator = self::get_boolean_operator_at_offset( $condition_string, $i );
				if ( $operator !== null ) {
					$operands[]      = trim( $current_operand );
					$operators[]     = $operator;
					$current_operand = '';
					$i              += strlen( $operator ) - 1;
					continue;
				}
			}

			$current_operand .= $char;
		}

		if ( empty( $operators ) ) {
			return null;
		}

		$operands[] = trim( $current_operand );

		// Ignore malformed expressions (e.g., leading/trailing operator).
		foreach ( $operands as $operand ) {
			if ( $operand === '' ) {
				return null;
			}
		}

		return array(
			'operands'  => $operands,
			'operators' => $operators,
		);
	}

	/**
	 * Detect a boolean operator at a specific offset.
	 *
	 * @since 3.7
	 *
	 * @param string $condition_string The condition expression.
	 * @param int    $offset           The character offset to inspect.
	 * @return string|null The boolean operator ('and' or 'or') or null.
	 */
	private static function get_boolean_operator_at_offset( $condition_string, $offset ) {
		foreach ( array( 'and', 'or' ) as $operator ) {
			$operator_length = strlen( $operator );
			if ( substr( $condition_string, $offset, $operator_length ) !== $operator ) {
				continue;
			}

			$before_char = ( $offset > 0 ) ? $condition_string[ $offset - 1 ] : '';
			$after_index = $offset + $operator_length;
			$after_char  = ( $after_index < strlen( $condition_string ) ) ? $condition_string[ $after_index ] : '';

			$before_is_boundary = ( $before_char === '' ) || ! preg_match( '/[A-Za-z0-9_]/', $before_char );
			$after_is_boundary  = ( $after_char === '' ) || ! preg_match( '/[A-Za-z0-9_]/', $after_char );

			if ( $before_is_boundary && $after_is_boundary ) {
				return $operator;
			}
		}

		return null;
	}

	/**
	 * Determine whether a character at a given offset is escaped.
	 *
	 * @since 3.7
	 *
	 * @param string $string The source string.
	 * @param int    $offset The offset to inspect.
	 * @return bool True if escaped; false otherwise.
	 */
	private static function is_escaped_character( $string, $offset ) {
		if ( $offset <= 0 ) {
			return false;
		}

		$backslashes = 0;
		for ( $i = $offset - 1; $i >= 0 && $string[ $i ] === '\\'; $i-- ) {
			$backslashes++;
		}

		return ( $backslashes % 2 ) === 1;
	}

	/**
	 * Resolve a value token to its actual value.
	 *
	 * @since 3.7
	 *
	 * @param string $token The token to resolve.
	 * @param array  $data  Key-value pairs for variable resolution.
	 * @param array  $args  Optional renderer arguments.
	 * @return mixed The resolved value.
	 */
	private static function resolve_value( $token, &$data, $args ) {
		$token = trim( $token );

		// Quoted strings.
		if ( preg_match( '/^([\'"])(.*)\\1$/s', $token, $quote_match ) ) {
			return $quote_match[2];
		}

		// Boolean literals.
		if ( $token === 'true' ) {
			return true;
		}
		if ( $token === 'false' ) {
			return false;
		}

		// Null literals.
		if ( $token === 'empty' || $token === 'nil' || $token === 'null' ) {
			return null;
		}

		// Numeric literals.
		if ( is_numeric( $token ) ) {
			return $token + 0;
		}

		// Variable lookup in $data.
		if ( array_key_exists( $token, $data ) ) {
			return $data[ $token ];
		}

		// Optionally fall back to usermeta for unresolved variables.
		if ( ! empty( $args['usermeta_fallback_user_id'] ) ) {
			$user_id = (int) $args['usermeta_fallback_user_id'];
			if ( $user_id > 0 ) {
				$usermeta      = get_user_meta( $user_id, $token, true );
				$resolved_meta = self::normalize_usermeta_value( $usermeta );

				// Memoize lookups (including misses) for the remainder of this render call.
				$data[ $token ] = $resolved_meta;

				return $resolved_meta;
			}
		}

		return null;
	}

	/**
	 * Normalize usermeta values for Liquid variable usage.
	 *
	 * Mirrors legacy email usermeta handling.
	 *
	 * @since 3.7
	 *
	 * @param mixed $usermeta Raw usermeta value.
	 * @return mixed Normalized value or null when empty.
	 */
	private static function normalize_usermeta_value( $usermeta ) {
		if ( empty( $usermeta ) ) {
			return null;
		}

		if ( is_array( $usermeta ) && ! empty( $usermeta['fullurl'] ) ) {
			return $usermeta['fullurl'];
		}

		if ( is_array( $usermeta ) ) {
			return implode( ', ', $usermeta );
		}

		return $usermeta;
	}

	/**
	 * Process all {{ variable }} and {{ variable | filter }} output tags.
	 *
	 * @since 3.7
	 *
	 * @param string $content The content containing output tags.
	 * @param array  $data    Key-value pairs for variable resolution.
	 * @param array  $args    Optional renderer arguments.
	 * @return string The content with all output tags resolved.
	 */
	private static function process_output_tags( $content, &$data, $args ) {
		return preg_replace_callback(
			'/\{\{(.+?)\}\}/s',
			function ( $matches ) use ( &$data, $args ) {
				$expression = $matches[1];
				$parts      = self::split_filter_expression( $expression );

				// First part is the variable name.
				$var_name = trim( $parts[0] );
				$value    = self::resolve_value( $var_name, $data, $args );

				// Remaining parts are filters.
				for ( $i = 1; $i < count( $parts ); $i++ ) {
					$value = self::apply_filter( $value, $parts[ $i ], $data, $args );
				}

				return ( $value === null ) ? '' : (string) $value;
			},
			$content
		);
	}

	/**
	 * Split an output expression by filter pipes, respecting quoted strings.
	 *
	 * @since 3.7
	 *
	 * @param string $expression The expression inside {{ ... }}.
	 * @return array The split expression parts.
	 */
	private static function split_filter_expression( $expression ) {
		$parts         = array();
		$current_part  = '';
		$current_quote = '';
		$length        = strlen( $expression );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $expression[ $i ];

			if ( $current_quote !== '' ) {
				$current_part .= $char;
				if ( $char === $current_quote && ! self::is_escaped_character( $expression, $i ) ) {
					$current_quote = '';
				}
				continue;
			}

			if ( $char === '"' || $char === "'" ) {
				$current_quote = $char;
				$current_part .= $char;
				continue;
			}

			if ( $char === '|' ) {
				$parts[]      = $current_part;
				$current_part = '';
				continue;
			}

			$current_part .= $char;
		}

		$parts[] = $current_part;

		return $parts;
	}

	/**
	 * Apply a single filter to a value.
	 *
	 * @since 3.7
	 *
	 * @param mixed  $value             The current value.
	 * @param string $filter_expression The filter expression (e.g., "upcase" or "default: 'N/A'").
	 * @param array  $data              Key-value pairs for variable resolution in filter args.
	 * @param array  $render_args       Optional renderer arguments.
	 * @return mixed The filtered value.
	 */
	private static function apply_filter( $value, $filter_expression, &$data, $render_args ) {
		$filter_expression = trim( $filter_expression );

		// Split on first colon to separate filter name from arguments.
		$parts       = explode( ':', $filter_expression, 2 );
		$filter_name = trim( $parts[0] );
		$filter_args = array();

		if ( isset( $parts[1] ) ) {
			$filter_args = self::parse_filter_args( $parts[1], $data, $render_args );
		}

		$filters = self::get_filters();

		if ( isset( $filters[ $filter_name ]['callback'] ) && is_callable( $filters[ $filter_name ]['callback'] ) ) {
			return call_user_func( $filters[ $filter_name ]['callback'], $value, ...$filter_args );
		}

		// Unknown filter: return value unchanged.
		return $value;
	}

	/**
	 * Parse filter arguments from a colon-separated argument string.
	 *
	 * @since 3.7
	 *
	 * @param string $args_string  The arguments string (everything after the colon).
	 * @param array  $data         Key-value pairs for variable resolution.
	 * @param array  $render_args  Optional renderer arguments.
	 * @return array The parsed arguments.
	 */
	private static function parse_filter_args( $args_string, &$data, $render_args ) {
		$args_string = trim( $args_string );
		if ( $args_string === '' ) {
			return array();
		}

		// Split on commas, respecting quoted strings.
		preg_match_all( "/(?:'[^']*'|\"[^\"]*\"|[^,])+/", $args_string, $matches );

		$args = array();
		foreach ( $matches[0] as $arg ) {
			$arg = trim( $arg );

			// Strip surrounding quotes.
			if ( preg_match( '/^([\'"])(.*)\\1$/s', $arg, $quote_match ) ) {
				$args[] = $quote_match[2];
			} elseif ( is_numeric( $arg ) ) {
				$args[] = $arg + 0;
			} else {
				// Resolve as a variable reference.
				$args[] = self::resolve_value( $arg, $data, $render_args );
			}
		}

		return $args;
	}
}
